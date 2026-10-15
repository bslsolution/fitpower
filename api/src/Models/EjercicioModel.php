<?php
namespace App\Models;

class EjercicioModel {
    private $db;
    public const GIF_BASE = '/front/dist/imagenes/ejercicios/';

    public function __construct($db) {
        $this->db = $db;
        try {
            self::asegurarColumnaImagen($db);
        } catch (\Throwable $e) {
            // Si la columna no se puede crear, no bloquea el resto de la API.
        }
    }

    public static function asegurarColumnaImagen($db): void {
        static $hecho = false;
        if ($hecho) {
            return;
        }
        $hecho = true;
        $col = $db->query("SHOW COLUMNS FROM ejercicios LIKE 'imagen'")->fetch();
        if (!$col) {
            $db->exec('ALTER TABLE ejercicios ADD COLUMN imagen VARCHAR(255) NULL');
        }
        $filas = $db->query(
            'SELECT id_ejercicio, nombre, grupo_muscular, imagen FROM ejercicios'
        )->fetchAll(\PDO::FETCH_ASSOC);
        $st = $db->prepare('UPDATE ejercicios SET imagen = :img WHERE id_ejercicio = :id');
        foreach ($filas as $fila) {
            if (!empty($fila['imagen'])) {
                continue;
            }
            $st->execute([
                'img' => self::rutaGif($fila['nombre'] ?? '', $fila['grupo_muscular'] ?? ''),
                'id' => $fila['id_ejercicio'],
            ]);
        }
    }

    public static function rutaGif(string $nombre, string $grupo = ''): string {
        $n = self::normalizar($nombre);
        $reglas = [
            ['sentadilla bulgara', 'sentadilla-bulgara.gif'],
            ['press inclinado con mancuerna', 'press-inclinado-mancuernas.gif'],
            ['press inclinado', 'press-inclinado.gif'],
            ['press banca', 'press-banca.gif'],
            ['press de banca', 'press-banca.gif'],
            ['remo en t', 'remo-t.gif'],
            ['remo con mancuerna', 'remo-mancuerna.gif'],
            ['curl martillo', 'curl-martillo.gif'],
            ['elevaciones laterales', 'elevaciones-laterales.gif'],
            ['press militar', 'press-militar.gif'],
            ['press frances', 'press-frances.gif'],
            ['extension de cuadriceps', 'extension-cuadriceps.gif'],
            ['cuadriceps', 'extension-cuadriceps.gif'],
            ['peck deck', 'peck-deck.gif'],
            ['jalon', 'jalon-pecho.gif'],
            ['triceps en polea', 'triceps-polea.gif'],
            ['polea', 'triceps-polea.gif'],
            ['sentadilla', 'sentadilla.gif'],
            ['remo', 'remo.gif'],
            ['plancha', 'plancha.gif'],
            ['fondos', 'fondos.gif'],
            ['dominada', 'dominadas.gif'],
            ['flexion', 'flexiones.gif'],
            ['laterales', 'elevaciones-laterales.gif'],
            ['militar', 'press-militar.gif'],
            ['frances', 'press-frances.gif'],
            ['martillo', 'curl-martillo.gif'],
            ['peso muerto', 'peso-muerto.gif'],
            ['curl', 'curl-martillo.gif'],
            ['crunch', 'crunch.gif'],
            ['burpee', 'burpees.gif'],
        ];
        foreach ($reglas as [$clave, $archivo]) {
            if ($n !== '' && str_contains($n, $clave)) {
                return self::GIF_BASE . $archivo;
            }
        }
        $g = self::normalizar($grupo);
        $porGrupo = [
            'pierna' => 'sentadilla.gif',
            'pecho' => 'press-banca.gif',
            'espalda' => 'remo.gif',
            'core' => 'plancha.gif',
            'hombros' => 'press-militar.gif',
            'hombro' => 'press-militar.gif',
            'brazos' => 'curl-martillo.gif',
            'biceps' => 'curl-martillo.gif',
            'triceps' => 'triceps-polea.gif',
        ];
        return self::GIF_BASE . ($porGrupo[$g] ?? 'sentadilla.gif');
    }

    private static function normalizar(string $texto): string {
        $texto = mb_strtolower($texto, 'UTF-8');
        return str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $texto
        );
    }

    public function listar(): array {
        $stmt = $this->db->query(
            'SELECT id_ejercicio, id_entrenador_creador, nombre, grupo_muscular, descripcion, imagen
             FROM ejercicios
             ORDER BY nombre ASC'
        );
        return array_map([$this, 'conGif'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function porId(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT id_ejercicio, id_entrenador_creador, nombre, grupo_muscular, descripcion, imagen
             FROM ejercicios WHERE id_ejercicio = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ? $this->conGif($fila) : null;
    }

    public function crear(array $datos): int {
        $imagen = $datos['imagen'] ?? self::rutaGif(
            (string) ($datos['nombre'] ?? ''),
            (string) ($datos['grupo_muscular'] ?? '')
        );
        $stmt = $this->db->prepare(
            'INSERT INTO ejercicios (id_entrenador_creador, nombre, grupo_muscular, descripcion, imagen)
             VALUES (:ent, :nombre, :grupo, :desc, :img)'
        );
        $stmt->execute([
            'ent' => $datos['id_entrenador_creador'] ?? null,
            'nombre' => $datos['nombre'],
            'grupo' => $datos['grupo_muscular'] ?? null,
            'desc' => $datos['descripcion'] ?? null,
            'img' => $imagen,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void {
        $imagen = $datos['imagen'] ?? self::rutaGif(
            (string) ($datos['nombre'] ?? ''),
            (string) ($datos['grupo_muscular'] ?? '')
        );
        $stmt = $this->db->prepare(
            'UPDATE ejercicios
             SET nombre = :nombre, grupo_muscular = :grupo, descripcion = :desc, imagen = :img
             WHERE id_ejercicio = :id'
        );
        $stmt->execute([
            'nombre' => $datos['nombre'],
            'grupo' => $datos['grupo_muscular'] ?? null,
            'desc' => $datos['descripcion'] ?? null,
            'img' => $imagen,
            'id' => $id,
        ]);
    }

    public function borrar(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM ejercicios WHERE id_ejercicio = :id');
        $stmt->execute(['id' => $id]);
    }

    private function conGif(array $fila): array {
        if (empty($fila['imagen'])) {
            $fila['imagen'] = self::rutaGif(
                (string) ($fila['nombre'] ?? ''),
                (string) ($fila['grupo_muscular'] ?? '')
            );
        }
        return $fila;
    }
}
