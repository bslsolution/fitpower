<?php
namespace App\Models;

class GrupoMuscularModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar(): array {
        $stmt = $this->db->query(
            'SELECT id_grupo, nombre FROM grupos_musculares ORDER BY nombre ASC'
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT id_grupo, nombre FROM grupos_musculares WHERE id_grupo = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function porNombre(string $nombre): ?array {
        $stmt = $this->db->prepare(
            'SELECT id_grupo, nombre FROM grupos_musculares WHERE LOWER(nombre) = LOWER(:n) LIMIT 1'
        );
        $stmt->execute(['n' => $nombre]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function crear(string $nombre): int {
        $stmt = $this->db->prepare('INSERT INTO grupos_musculares (nombre) VALUES (:n)');
        $stmt->execute(['n' => $nombre]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, string $nombreAnterior, string $nombreNuevo): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE grupos_musculares SET nombre = :n WHERE id_grupo = :id'
            );
            $stmt->execute(['n' => $nombreNuevo, 'id' => $id]);
            $st = $this->db->prepare(
                'UPDATE ejercicios SET grupo_muscular = :nuevo WHERE grupo_muscular = :viejo'
            );
            $st->execute(['nuevo' => $nombreNuevo, 'viejo' => $nombreAnterior]);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function contarEjercicios(string $nombre): int {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM ejercicios WHERE LOWER(grupo_muscular) = LOWER(:n)'
        );
        $stmt->execute(['n' => $nombre]);
        return (int) $stmt->fetchColumn();
    }

    public function borrar(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM grupos_musculares WHERE id_grupo = :id');
        $stmt->execute(['id' => $id]);
    }
}
