<?php
namespace App\Models;

class RutinaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listarDeEntrenador(int $idEntrenador): array {
        $stmt = $this->db->prepare(
            'SELECT r.*, u.nombre AS socio_nombre, u.apellido AS socio_apellido
             FROM rutinas r
             LEFT JOIN socios s ON s.id_socio = r.id_socio
             LEFT JOIN usuarios u ON u.id_usuario = s.id_usuario
             WHERE r.id_entrenador = :id
             ORDER BY r.fecha_creacion DESC'
        );
        $stmt->execute(['id' => $idEntrenador]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listarDeSocio(int $idSocio): array {
        $stmt = $this->db->prepare(
            'SELECT r.*,
                    ue.nombre AS entrenador_nombre, ue.apellido AS entrenador_apellido
             FROM rutinas r
             JOIN entrenadores e ON e.id_entrenador = r.id_entrenador
             JOIN usuarios ue ON ue.id_usuario = e.id_usuario
             WHERE r.id_socio = :id
             ORDER BY r.fecha_creacion DESC'
        );
        $stmt->execute(['id' => $idSocio]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT r.*,
                    u.nombre AS socio_nombre, u.apellido AS socio_apellido,
                    ue.nombre AS entrenador_nombre, ue.apellido AS entrenador_apellido,
                    e.especialidad
             FROM rutinas r
             JOIN entrenadores e ON e.id_entrenador = r.id_entrenador
             JOIN usuarios ue ON ue.id_usuario = e.id_usuario
             LEFT JOIN socios s ON s.id_socio = r.id_socio
             LEFT JOIN usuarios u ON u.id_usuario = s.id_usuario
             WHERE r.id_rutina = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function crear(array $datos): int {
        $stmt = $this->db->prepare(
            'INSERT INTO rutinas (id_entrenador, id_socio, id_rutina_origen, nombre, objetivo, estado)
             VALUES (:ent, :socio, :origen, :nombre, :objetivo, :estado)'
        );
        $stmt->execute([
            'ent' => $datos['id_entrenador'],
            'socio' => $datos['id_socio'] ?? null,
            'origen' => $datos['id_rutina_origen'] ?? null,
            'nombre' => $datos['nombre'],
            'objetivo' => $datos['objetivo'] ?? null,
            'estado' => $datos['estado'] ?? 'activa',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void {
        $stmt = $this->db->prepare(
            'UPDATE rutinas
             SET nombre = :nombre, objetivo = :objetivo, estado = :estado
             WHERE id_rutina = :id'
        );
        $stmt->execute([
            'nombre' => $datos['nombre'],
            'objetivo' => $datos['objetivo'] ?? null,
            'estado' => $datos['estado'] ?? 'activa',
            'id' => $id,
        ]);
    }

    public function borrar(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM rutinas WHERE id_rutina = :id');
        $stmt->execute(['id' => $id]);
    }

    public function ejerciciosDe(int $idRutina): array {
        $stmt = $this->db->prepare(
            'SELECT re.id_rutina_ejercicio, re.id_rutina, re.id_ejercicio, re.orden, re.notas,
                    e.nombre, e.grupo_muscular, e.descripcion
             FROM rutina_ejercicios re
             JOIN ejercicios e ON e.id_ejercicio = re.id_ejercicio
             WHERE re.id_rutina = :id
             ORDER BY re.orden ASC, re.id_rutina_ejercicio ASC'
        );
        $stmt->execute(['id' => $idRutina]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function seriesDe(int $idRutinaEjercicio): array {
        $stmt = $this->db->prepare(
            'SELECT id_serie, id_rutina_ejercicio, orden, repeticiones, peso_kg, descanso_segundos
             FROM series
             WHERE id_rutina_ejercicio = :id
             ORDER BY orden ASC, id_serie ASC'
        );
        $stmt->execute(['id' => $idRutinaEjercicio]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function rutinaEjercicioPorId(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT * FROM rutina_ejercicios WHERE id_rutina_ejercicio = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function seriePorId(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT s.*, re.id_rutina, re.id_ejercicio, r.id_socio, r.nombre AS rutina_nombre,
                    e.nombre AS ejercicio_nombre, e.grupo_muscular
             FROM series s
             JOIN rutina_ejercicios re ON re.id_rutina_ejercicio = s.id_rutina_ejercicio
             JOIN rutinas r ON r.id_rutina = re.id_rutina
             JOIN ejercicios e ON e.id_ejercicio = re.id_ejercicio
             WHERE s.id_serie = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function agregarEjercicio(int $idRutina, int $idEjercicio, ?string $notas): int {
        $orden = $this->proximoOrdenEjercicio($idRutina);
        $stmt = $this->db->prepare(
            'INSERT INTO rutina_ejercicios (id_rutina, id_ejercicio, orden, notas)
             VALUES (:r, :e, :o, :n)'
        );
        $stmt->execute([
            'r' => $idRutina,
            'e' => $idEjercicio,
            'o' => $orden,
            'n' => $notas,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizarEjercicioRutina(int $id, array $datos): void {
        $stmt = $this->db->prepare(
            'UPDATE rutina_ejercicios SET notas = :notas, id_ejercicio = :ej WHERE id_rutina_ejercicio = :id'
        );
        $stmt->execute([
            'notas' => $datos['notas'] ?? null,
            'ej' => $datos['id_ejercicio'],
            'id' => $id,
        ]);
    }

    public function borrarEjercicioRutina(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM rutina_ejercicios WHERE id_rutina_ejercicio = :id');
        $stmt->execute(['id' => $id]);
    }

    public function reordenarEjercicios(int $idRutina, array $ids): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE rutina_ejercicios SET orden = :orden
                 WHERE id_rutina_ejercicio = :id AND id_rutina = :rutina'
            );
            foreach (array_values($ids) as $i => $id) {
                $stmt->execute([
                    'orden' => $i + 1,
                    'id' => (int) $id,
                    'rutina' => $idRutina,
                ]);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function agregarSerie(int $idRutinaEjercicio, array $datos): int {
        $orden = $datos['orden'] ?? $this->proximoOrdenSerie($idRutinaEjercicio);
        $stmt = $this->db->prepare(
            'INSERT INTO series (id_rutina_ejercicio, orden, repeticiones, peso_kg, descanso_segundos)
             VALUES (:re, :orden, :reps, :peso, :descanso)'
        );
        $stmt->execute([
            're' => $idRutinaEjercicio,
            'orden' => $orden,
            'reps' => $datos['repeticiones'],
            'peso' => $datos['peso_kg'] ?? null,
            'descanso' => $datos['descanso_segundos'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizarPesoSerie(int $id, ?float $pesoKg): void {
        $stmt = $this->db->prepare('UPDATE series SET peso_kg = :peso WHERE id_serie = :id');
        $stmt->execute([
            'peso' => $pesoKg,
            'id' => $id,
        ]);
    }

    public function actualizarSerie(int $id, array $datos): void {
        $stmt = $this->db->prepare(
            'UPDATE series
             SET repeticiones = :reps, peso_kg = :peso, descanso_segundos = :descanso, orden = :orden
             WHERE id_serie = :id'
        );
        $stmt->execute([
            'reps' => $datos['repeticiones'],
            'peso' => $datos['peso_kg'] ?? null,
            'descanso' => $datos['descanso_segundos'] ?? null,
            'orden' => $datos['orden'] ?? 1,
            'id' => $id,
        ]);
    }

    public function borrarSerie(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM series WHERE id_serie = :id');
        $stmt->execute(['id' => $id]);
    }

    public function reordenarSeries(int $idRutinaEjercicio, array $ids): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE series SET orden = :orden
                 WHERE id_serie = :id AND id_rutina_ejercicio = :re'
            );
            foreach (array_values($ids) as $i => $id) {
                $stmt->execute([
                    'orden' => $i + 1,
                    'id' => (int) $id,
                    're' => $idRutinaEjercicio,
                ]);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function asignarCopia(int $idPlantilla, int $idSocio): int {
        $origen = $this->porId($idPlantilla);
        if (!$origen) {
            throw new \RuntimeException('Rutina no encontrada');
        }

        $this->db->beginTransaction();
        try {
            $nuevoId = $this->crear([
                'id_entrenador' => (int) $origen['id_entrenador'],
                'id_socio' => $idSocio,
                'id_rutina_origen' => $idPlantilla,
                'nombre' => $origen['nombre'],
                'objetivo' => $origen['objetivo'],
                'estado' => 'activa',
            ]);

            $ejercicios = $this->ejerciciosDe($idPlantilla);
            foreach ($ejercicios as $ej) {
                $stmt = $this->db->prepare(
                    'INSERT INTO rutina_ejercicios (id_rutina, id_ejercicio, orden, notas)
                     VALUES (:r, :e, :o, :n)'
                );
                $stmt->execute([
                    'r' => $nuevoId,
                    'e' => $ej['id_ejercicio'],
                    'o' => $ej['orden'],
                    'n' => $ej['notas'],
                ]);
                $nuevoRe = (int) $this->db->lastInsertId();
                foreach ($this->seriesDe((int) $ej['id_rutina_ejercicio']) as $serie) {
                    $st = $this->db->prepare(
                        'INSERT INTO series (id_rutina_ejercicio, orden, repeticiones, peso_kg, descanso_segundos)
                         VALUES (:re, :orden, :reps, :peso, :descanso)'
                    );
                    $st->execute([
                        're' => $nuevoRe,
                        'orden' => $serie['orden'],
                        'reps' => $serie['repeticiones'],
                        'peso' => $serie['peso_kg'],
                        'descanso' => $serie['descanso_segundos'],
                    ]);
                }
            }

            $this->db->commit();
            return $nuevoId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function resumenEntrenador(int $idEntrenador): array {
        $st1 = $this->db->prepare(
            'SELECT COUNT(*) AS total FROM socios WHERE id_entrenador = :id'
        );
        $st1->execute(['id' => $idEntrenador]);
        $socios = (int) $st1->fetch(\PDO::FETCH_ASSOC)['total'];

        $st2 = $this->db->prepare('SELECT COUNT(*) AS total FROM rutinas WHERE id_entrenador = :id');
        $st2->execute(['id' => $idEntrenador]);
        $rutinas = (int) $st2->fetch(\PDO::FETCH_ASSOC)['total'];

        $st3 = $this->db->prepare(
            'SELECT COUNT(*) AS total
             FROM socios s
             JOIN usuarios u ON u.id_usuario = s.id_usuario
             WHERE s.id_entrenador = :id
               AND DATE_ADD(u.fecha_alta, INTERVAL 1 YEAR) < NOW()'
        );
        $st3->execute(['id' => $idEntrenador]);
        $vencidas = (int) $st3->fetch(\PDO::FETCH_ASSOC)['total'];

        return [
            'socios_asignados' => $socios,
            'rutinas_creadas' => $rutinas,
            'mensualidades_vencidas' => $vencidas,
        ];
    }

    public function resumenAdmin(): array {
        $socios = (int) $this->db->query('SELECT COUNT(*) AS t FROM socios')->fetch(\PDO::FETCH_ASSOC)['t'];
        $rutinas = (int) $this->db->query('SELECT COUNT(*) AS t FROM rutinas')->fetch(\PDO::FETCH_ASSOC)['t'];
        $vencidas = (int) $this->db->query(
            'SELECT COUNT(*) AS t
             FROM socios s
             JOIN usuarios u ON u.id_usuario = s.id_usuario
             WHERE DATE_ADD(u.fecha_alta, INTERVAL 1 YEAR) < NOW()'
        )->fetch(\PDO::FETCH_ASSOC)['t'];
        return [
            'socios_asignados' => $socios,
            'rutinas_creadas' => $rutinas,
            'mensualidades_vencidas' => $vencidas,
        ];
    }

    public function entrenadorDeSocio(int $idSocio): ?array {
        $asignado = (new SocioModel($this->db))->entrenadorAsignado($idSocio);
        if ($asignado) {
            return $asignado;
        }

        $stmt = $this->db->prepare(
            'SELECT ue.nombre, ue.apellido, e.especialidad, e.id_entrenador
             FROM rutinas r
             JOIN entrenadores e ON e.id_entrenador = r.id_entrenador
             JOIN usuarios ue ON ue.id_usuario = e.id_usuario
             WHERE r.id_socio = :id
             ORDER BY r.fecha_creacion DESC
             LIMIT 1'
        );
        $stmt->execute(['id' => $idSocio]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function proximoOrdenEjercicio(int $idRutina): int {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(MAX(orden), 0) + 1 AS n FROM rutina_ejercicios WHERE id_rutina = :id'
        );
        $stmt->execute(['id' => $idRutina]);
        return (int) $stmt->fetch(\PDO::FETCH_ASSOC)['n'];
    }

    private function proximoOrdenSerie(int $idRe): int {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(MAX(orden), 0) + 1 AS n FROM series WHERE id_rutina_ejercicio = :id'
        );
        $stmt->execute(['id' => $idRe]);
        return (int) $stmt->fetch(\PDO::FETCH_ASSOC)['n'];
    }
}
