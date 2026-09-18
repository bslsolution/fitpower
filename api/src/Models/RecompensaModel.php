<?php
namespace App\Models;

class RecompensaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->asegurarTabla();
    }

    public function asegurarTabla(): void {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS recompensas_fitpoints (
                id_recompensa INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(120) NOT NULL,
                costo_puntos INT NOT NULL,
                imagen VARCHAR(255) NOT NULL,
                descripcion VARCHAR(255) NULL,
                fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function listar(): array {
        $stmt = $this->db->query(
            'SELECT id_recompensa, nombre, costo_puntos, imagen, descripcion, fecha_alta
             FROM recompensas_fitpoints
             ORDER BY fecha_alta DESC, id_recompensa DESC'
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT id_recompensa, nombre, costo_puntos, imagen, descripcion, fecha_alta
             FROM recompensas_fitpoints
             WHERE id_recompensa = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function crear(array $datos): int {
        $stmt = $this->db->prepare(
            'INSERT INTO recompensas_fitpoints (nombre, costo_puntos, imagen, descripcion)
             VALUES (:nombre, :costo, :imagen, :desc)'
        );
        $stmt->execute([
            'nombre' => $datos['nombre'],
            'costo' => $datos['costo_puntos'],
            'imagen' => $datos['imagen'],
            'desc' => $datos['descripcion'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void {
        $stmt = $this->db->prepare(
            'UPDATE recompensas_fitpoints
             SET nombre = :nombre, costo_puntos = :costo, imagen = :imagen, descripcion = :desc
             WHERE id_recompensa = :id'
        );
        $stmt->execute([
            'id' => $id,
            'nombre' => $datos['nombre'],
            'costo' => $datos['costo_puntos'],
            'imagen' => $datos['imagen'],
            'desc' => $datos['descripcion'],
        ]);
    }

    public function borrar(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM recompensas_fitpoints WHERE id_recompensa = :id');
        $stmt->execute(['id' => $id]);
    }
}
