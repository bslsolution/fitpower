<?php
namespace App\Controllers;

use App\Models\EjercicioModel;
use App\Models\GrupoMuscularModel;
use App\Utils\ApiResponse;
use App\Utils\Auth;

class EjercicioController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        Auth::exigir(['administrador', 'entrenador', 'socio']);
        $modelo = new EjercicioModel($this->db);
        ApiResponse::ok(['ejercicios' => $modelo->listar()]);
    }

    public function crear() {
        $usuario = Auth::exigir(['entrenador', 'administrador']);
        $datos = ApiResponse::input();
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        if ($nombre === '') {
            ApiResponse::error('El nombre del ejercicio es obligatorio');
            return;
        }
        $grupo = $this->exigirGrupo($datos['grupo_muscular'] ?? '');
        if ($grupo === null) {
            return;
        }
        $modelo = new EjercicioModel($this->db);
        $id = $modelo->crear([
            'id_entrenador_creador' => $usuario['id_entrenador'] ?? null,
            'nombre' => $nombre,
            'grupo_muscular' => $grupo,
            'descripcion' => trim((string) ($datos['descripcion'] ?? '')) ?: null,
        ]);
        ApiResponse::ok(['ejercicio' => $modelo->porId($id)], 201);
    }

    public function actualizar($id) {
        Auth::exigir(['entrenador', 'administrador']);
        $modelo = new EjercicioModel($this->db);
        $actual = $modelo->porId((int) $id);
        if (!$actual) {
            ApiResponse::error('Ejercicio no encontrado', 404);
            return;
        }
        $datos = ApiResponse::input();
        $nombre = trim((string) ($datos['nombre'] ?? $actual['nombre']));
        if ($nombre === '') {
            ApiResponse::error('El nombre del ejercicio es obligatorio');
            return;
        }
        $grupo = $this->exigirGrupo($datos['grupo_muscular'] ?? $actual['grupo_muscular']);
        if ($grupo === null) {
            return;
        }
        $modelo->actualizar((int) $id, [
            'nombre' => $nombre,
            'grupo_muscular' => $grupo,
            'descripcion' => trim((string) ($datos['descripcion'] ?? '')) ?: null,
        ]);
        ApiResponse::ok(['ejercicio' => $modelo->porId((int) $id)]);
    }

    public function borrar($id) {
        Auth::exigir(['entrenador', 'administrador']);
        $modelo = new EjercicioModel($this->db);
        if (!$modelo->porId((int) $id)) {
            ApiResponse::error('Ejercicio no encontrado', 404);
            return;
        }
        try {
            $modelo->borrar((int) $id);
        } catch (\Exception $e) {
            ApiResponse::error('No se puede borrar: el ejercicio está usado en alguna rutina', 409);
            return;
        }
        ApiResponse::ok(['message' => 'Ejercicio eliminado']);
    }

    private function exigirGrupo($valor): ?string {
        $grupo = trim((string) $valor);
        if ($grupo === '') {
            ApiResponse::error('El grupo muscular es obligatorio');
            return null;
        }
        $catalogo = new GrupoMuscularModel($this->db);
        $fila = $catalogo->porNombre($grupo);
        if (!$fila) {
            ApiResponse::error('Elegí un grupo de la lista');
            return null;
        }
        return $fila['nombre'];
    }
}
