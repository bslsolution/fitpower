<?php
namespace App\Models;

class SocioModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar(): array {
        $stmt = $this->db->query($this->sqlListar() . ' ORDER BY u.apellido, u.nombre');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listarDeEntrenador(int $idEntrenador): array {
        $stmt = $this->db->prepare(
            $this->sqlListar() . ' WHERE s.id_entrenador = :id ORDER BY u.apellido, u.nombre'
        );
        $stmt->execute(['id' => $idEntrenador]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listarEntrenadores(): array {
        $stmt = $this->db->query(
            'SELECT e.id_entrenador, u.nombre, u.apellido, u.email, u.activo, e.especialidad
             FROM entrenadores e
             JOIN usuarios u ON u.id_usuario = e.id_usuario
             ORDER BY u.apellido, u.nombre'
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array {
        $stmt = $this->db->prepare($this->sqlListar() . ' WHERE s.id_socio = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function entrenadorExiste(int $id): bool {
        $stmt = $this->db->prepare('SELECT 1 FROM entrenadores WHERE id_entrenador = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function asignarEntrenador(int $idSocio, ?int $idEntrenador): void {
        $stmt = $this->db->prepare(
            'UPDATE socios SET id_entrenador = :entrenador WHERE id_socio = :id'
        );
        $stmt->execute([
            'entrenador' => $idEntrenador,
            'id' => $idSocio,
        ]);
    }

    public function entrenadorAsignado(int $idSocio): ?array {
        $stmt = $this->db->prepare(
            'SELECT ue.nombre, ue.apellido, e.especialidad, e.id_entrenador
             FROM socios s
             JOIN entrenadores e ON e.id_entrenador = s.id_entrenador
             JOIN usuarios ue ON ue.id_usuario = e.id_usuario
             WHERE s.id_socio = :id LIMIT 1'
        );
        $stmt->execute(['id' => $idSocio]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function inactivos(int $dias = 7): array {
        $dias = max(1, $dias);
        $stmt = $this->db->query(
            'SELECT s.id_socio, s.id_usuario, s.id_entrenador,
                    u.nombre, u.apellido,
                    eu.id_usuario AS id_usuario_entrenador
             FROM socios s
             JOIN usuarios u ON u.id_usuario = s.id_usuario
             LEFT JOIN entrenadores e ON e.id_entrenador = s.id_entrenador
             LEFT JOIN usuarios eu ON eu.id_usuario = e.id_usuario
             LEFT JOIN (
                 SELECT id_socio, MAX(fecha) AS ultima
                 FROM entrenamientos
                 GROUP BY id_socio
             ) ult ON ult.id_socio = s.id_socio
             WHERE COALESCE(ult.ultima, u.fecha_alta) < DATE_SUB(NOW(), INTERVAL ' . $dias . ' DAY)'
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function mensualidadVencida(): array {
        $stmt = $this->db->query(
            'SELECT s.id_socio, s.id_usuario, s.id_entrenador,
                    u.nombre, u.apellido,
                    eu.id_usuario AS id_usuario_entrenador
             FROM socios s
             JOIN usuarios u ON u.id_usuario = s.id_usuario
             LEFT JOIN entrenadores e ON e.id_entrenador = s.id_entrenador
             LEFT JOIN usuarios eu ON eu.id_usuario = e.id_usuario
             WHERE DATE_ADD(u.fecha_alta, INTERVAL 1 YEAR) < NOW()'
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function sqlListar(): string {
        return 'SELECT s.id_socio, s.id_usuario, s.id_entrenador, s.fecha_nacimiento, s.notas,
                       u.nombre, u.apellido, u.email, u.activo,
                       ue.nombre AS entrenador_nombre, ue.apellido AS entrenador_apellido
                FROM socios s
                JOIN usuarios u ON u.id_usuario = s.id_usuario
                LEFT JOIN entrenadores e ON e.id_entrenador = s.id_entrenador
                LEFT JOIN usuarios ue ON ue.id_usuario = e.id_usuario';
    }
}
