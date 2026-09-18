<?php
namespace App\Models;

class ProgresoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function crearEntrenamiento(int $idSocio, ?int $idRutina, string $nombre): int {
        $stmt = $this->db->prepare(
            'INSERT INTO entrenamientos (id_socio, id_rutina, nombre)
             VALUES (:socio, :rutina, :nombre)'
        );
        $stmt->execute([
            'socio' => $idSocio,
            'rutina' => $idRutina,
            'nombre' => $nombre,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function porId(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT id_entrenamiento, id_socio, id_rutina, nombre, fecha
             FROM entrenamientos WHERE id_entrenamiento = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function guardarSerie(int $idEntrenamiento, array $datos): void {
        $stmt = $this->db->prepare(
            'INSERT INTO entrenamiento_series
                (id_entrenamiento, id_serie_origen, id_ejercicio, nombre_ejercicio, grupo_muscular,
                 serie_orden, repeticiones, peso_kg, descanso_segundos)
             VALUES
                (:ent, :serie, :ej, :nombre, :grupo, :orden, :reps, :peso, :descanso)
             ON DUPLICATE KEY UPDATE
                peso_kg = VALUES(peso_kg),
                repeticiones = VALUES(repeticiones),
                descanso_segundos = VALUES(descanso_segundos)'
        );
        $stmt->execute([
            'ent' => $idEntrenamiento,
            'serie' => $datos['id_serie_origen'],
            'ej' => $datos['id_ejercicio'],
            'nombre' => $datos['nombre_ejercicio'],
            'grupo' => $datos['grupo_muscular'],
            'orden' => $datos['serie_orden'],
            'reps' => $datos['repeticiones'],
            'peso' => $datos['peso_kg'],
            'descanso' => $datos['descanso_segundos'],
        ]);
    }

    public function listarDeSocio(int $idSocio): array {
        $stmt = $this->db->prepare(
            'SELECT e.id_entrenamiento, e.id_rutina, e.nombre, e.fecha,
                    COUNT(s.id_entrenamiento_serie) AS series_count
             FROM entrenamientos e
             LEFT JOIN entrenamiento_series s ON s.id_entrenamiento = e.id_entrenamiento
             WHERE e.id_socio = :id
             GROUP BY e.id_entrenamiento, e.id_rutina, e.nombre, e.fecha
             ORDER BY e.fecha DESC, e.id_entrenamiento DESC'
        );
        $stmt->execute(['id' => $idSocio]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function seriesDe(int $idEntrenamiento): array {
        $stmt = $this->db->prepare(
            'SELECT id_entrenamiento_serie, id_serie_origen, id_ejercicio, nombre_ejercicio, grupo_muscular,
                    serie_orden, repeticiones, peso_kg, descanso_segundos
             FROM entrenamiento_series
             WHERE id_entrenamiento = :id
             ORDER BY id_entrenamiento_serie ASC'
        );
        $stmt->execute(['id' => $idEntrenamiento]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
