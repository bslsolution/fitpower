<?php
namespace App\Models;

class EjercicioModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar(): array {
        $stmt = $this->db->query(
            'SELECT id_ejercicio, id_entrenador_creador, nombre, grupo_muscular, descripcion
             FROM ejercicios
             ORDER BY nombre ASC'
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT id_ejercicio, id_entrenador_creador, nombre, grupo_muscular, descripcion
             FROM ejercicios WHERE id_ejercicio = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function crear(array $datos): int {
        $stmt = $this->db->prepare(
            'INSERT INTO ejercicios (id_entrenador_creador, nombre, grupo_muscular, descripcion)
             VALUES (:ent, :nombre, :grupo, :desc)'
        );
        $stmt->execute([
            'ent' => $datos['id_entrenador_creador'] ?? null,
            'nombre' => $datos['nombre'],
            'grupo' => $datos['grupo_muscular'] ?? null,
            'desc' => $datos['descripcion'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): void {
        $stmt = $this->db->prepare(
            'UPDATE ejercicios
             SET nombre = :nombre, grupo_muscular = :grupo, descripcion = :desc
             WHERE id_ejercicio = :id'
        );
        $stmt->execute([
            'nombre' => $datos['nombre'],
            'grupo' => $datos['grupo_muscular'] ?? null,
            'desc' => $datos['descripcion'] ?? null,
            'id' => $id,
        ]);
    }

    public function borrar(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM ejercicios WHERE id_ejercicio = :id');
        $stmt->execute(['id' => $id]);
    }
}
