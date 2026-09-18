<?php
namespace App\Controllers;

use App\Models\RecompensaModel;
use App\Utils\ApiResponse;
use App\Utils\Auth;

class RecompensaController {
    private $db;
    private const MAX_BYTES = 2097152;
    private const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        Auth::exigir(['administrador', 'socio']);
        $modelo = new RecompensaModel($this->db);
        ApiResponse::ok(['recompensas' => $modelo->listar()]);
    }

    public function crear() {
        Auth::exigir(['administrador']);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? '')) ?: null;
        $costo = $this->costoDe($_POST['costo_puntos'] ?? '');
        if ($nombre === '') {
            ApiResponse::error('El nombre de la recompensa es obligatorio');
            return;
        }
        if ($costo === null) {
            ApiResponse::error('El costo en FitPoints tiene que ser un número entero mayor a 0');
            return;
        }
        $imagen = $this->guardarImagen($_FILES['imagen'] ?? null, null, true);
        if ($imagen === false) {
            return;
        }
        $modelo = new RecompensaModel($this->db);
        $id = $modelo->crear([
            'nombre' => $nombre,
            'costo_puntos' => $costo,
            'imagen' => $imagen,
            'descripcion' => $descripcion,
        ]);
        ApiResponse::ok(['recompensa' => $modelo->porId($id)], 201);
    }

    public function actualizar($id) {
        Auth::exigir(['administrador']);
        $modelo = new RecompensaModel($this->db);
        $actual = $modelo->porId((int) $id);
        if (!$actual) {
            ApiResponse::error('Recompensa no encontrada', 404);
            return;
        }
        $nombre = trim((string) ($_POST['nombre'] ?? $actual['nombre']));
        $descripcion = array_key_exists('descripcion', $_POST)
            ? (trim((string) $_POST['descripcion']) ?: null)
            : $actual['descripcion'];
        $costoRaw = $_POST['costo_puntos'] ?? $actual['costo_puntos'];
        $costo = $this->costoDe($costoRaw);
        if ($nombre === '') {
            ApiResponse::error('El nombre de la recompensa es obligatorio');
            return;
        }
        if ($costo === null) {
            ApiResponse::error('El costo en FitPoints tiene que ser un número entero mayor a 0');
            return;
        }
        $imagen = $this->guardarImagen($_FILES['imagen'] ?? null, $actual['imagen'], false);
        if ($imagen === false) {
            return;
        }
        $modelo->actualizar((int) $id, [
            'nombre' => $nombre,
            'costo_puntos' => $costo,
            'imagen' => $imagen,
            'descripcion' => $descripcion,
        ]);
        if ($imagen !== $actual['imagen']) {
            $this->borrarArchivo($actual['imagen']);
        }
        ApiResponse::ok(['recompensa' => $modelo->porId((int) $id)]);
    }

    public function borrar($id) {
        Auth::exigir(['administrador']);
        $modelo = new RecompensaModel($this->db);
        $actual = $modelo->porId((int) $id);
        if (!$actual) {
            ApiResponse::error('Recompensa no encontrada', 404);
            return;
        }
        $modelo->borrar((int) $id);
        $this->borrarArchivo($actual['imagen']);
        ApiResponse::ok(['message' => 'Recompensa eliminada']);
    }

    private function costoDe($valor): ?int {
        if ($valor === '' || $valor === null) {
            return null;
        }
        if (!is_numeric($valor) || strpos((string) $valor, '.') !== false) {
            return null;
        }
        $n = (int) $valor;
        return $n > 0 ? $n : null;
    }

    private function guardarImagen($file, ?string $anterior, bool $obligatoria) {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($obligatoria) {
                ApiResponse::error('La imagen de la recompensa es obligatoria');
                return false;
            }
            return $anterior;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            ApiResponse::error('No se pudo subir la imagen');
            return false;
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            ApiResponse::error('La imagen no puede superar los 2 MB');
            return false;
        }
        $tmp = $file['tmp_name'] ?? '';
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            ApiResponse::error('No se pudo subir la imagen');
            return false;
        }
        $finfo = class_exists('finfo') ? new \finfo(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? $finfo->file($tmp) : (mime_content_type($tmp) ?: '');
        $ext = self::TIPOS[$mime] ?? null;
        if ($ext === null) {
            ApiResponse::error('La imagen tiene que ser JPG, PNG, GIF o WEBP');
            return false;
        }
        $dir = dirname(__DIR__, 3) . '/front/dist/imagenes/recompensas';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            ApiResponse::error('No se pudo guardar la imagen', 500);
            return false;
        }
        $nombre = bin2hex(random_bytes(8)) . '.' . $ext;
        $destino = $dir . '/' . $nombre;
        if (!move_uploaded_file($tmp, $destino)) {
            ApiResponse::error('No se pudo guardar la imagen', 500);
            return false;
        }
        return '/front/dist/imagenes/recompensas/' . $nombre;
    }

    private function borrarArchivo(?string $ruta): void {
        if (!$ruta || strpos($ruta, '/front/dist/imagenes/recompensas/') !== 0) {
            return;
        }
        $archivo = dirname(__DIR__, 3) . $ruta;
        if (is_file($archivo)) {
            @unlink($archivo);
        }
    }
}
