<?php
namespace App\Models;

class UsuarioModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        FitpointsModel::asegurarColumnas($db);
    }

    public function contarUsuarios() {
        try {
            $stmt = $this->db->query('SELECT COUNT(*) AS total FROM usuarios');
            $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int) $fila['total'];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function porEmail(string $email): ?array {
        $sql = $this->sqlBase() . ' WHERE u.email = :email LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function porId(int $id): ?array {
        $sql = $this->sqlBase() . ' WHERE u.id_usuario = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function listar(): array {
        $stmt = $this->db->query($this->sqlBase() . ' ORDER BY u.fecha_alta DESC');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function idRolPorNombre(string $nombre): ?int {
        $stmt = $this->db->prepare('SELECT id_rol FROM roles WHERE nombre = :n LIMIT 1');
        $stmt->execute(['n' => $nombre]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ? (int) $fila['id_rol'] : null;
    }

    public function crear(array $datos): int {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO usuarios (id_rol, nombre, apellido, email, password_hash, telefono, activo)
                 VALUES (:id_rol, :nombre, :apellido, :email, :password_hash, :telefono, 1)'
            );
            $stmt->execute([
                'id_rol' => $datos['id_rol'],
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'email' => $datos['email'],
                'password_hash' => $datos['password_hash'],
                'telefono' => $datos['telefono'] ?? null,
            ]);
            $id = (int) $this->db->lastInsertId();

            if ($datos['rol'] === 'entrenador') {
                $st = $this->db->prepare(
                    'INSERT INTO entrenadores (id_usuario, especialidad, bio) VALUES (:id, :esp, :bio)'
                );
                $st->execute([
                    'id' => $id,
                    'esp' => $datos['especialidad'] ?? null,
                    'bio' => $datos['bio'] ?? null,
                ]);
            }

            if ($datos['rol'] === 'socio') {
                $st = $this->db->prepare(
                    'INSERT INTO socios (id_usuario, id_entrenador, fecha_nacimiento, notas)
                     VALUES (:id, :ent, :fn, :notas)'
                );
                $st->execute([
                    'id' => $id,
                    'ent' => $datos['id_entrenador'] ?? null,
                    'fn' => $datos['fecha_nacimiento'] ?? null,
                    'notas' => $datos['notas'] ?? null,
                ]);
            }

            $this->db->commit();
            return $id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function publico(array $fila): array {
        return [
            'id_usuario' => (int) $fila['id_usuario'],
            'rol' => $fila['rol'],
            'nombre' => $fila['nombre'],
            'apellido' => $fila['apellido'],
            'email' => $fila['email'],
            'telefono' => $fila['telefono'],
            'activo' => (int) $fila['activo'] === 1,
            'fecha_alta' => $fila['fecha_alta'] ?? null,
            'id_entrenador' => $fila['id_entrenador'] !== null ? (int) $fila['id_entrenador'] : null,
            'id_socio' => $fila['id_socio'] !== null ? (int) $fila['id_socio'] : null,
            'id_entrenador_asignado' => isset($fila['id_entrenador_asignado']) && $fila['id_entrenador_asignado'] !== null
                ? (int) $fila['id_entrenador_asignado'] : null,
            'entrenador_nombre' => $fila['entrenador_nombre'] ?? null,
            'entrenador_apellido' => $fila['entrenador_apellido'] ?? null,
            'especialidad' => $fila['especialidad'] ?? null,
            'fitpoints' => isset($fila['fitpoints']) ? (int) $fila['fitpoints'] : 0,
        ];
    }

    private function sqlBase(): string {
        return 'SELECT u.id_usuario, u.id_rol, r.nombre AS rol, u.nombre, u.apellido,
                       u.email, u.password_hash, u.telefono, u.activo, u.fecha_alta,
                       e.id_entrenador, e.especialidad, e.bio,
                       s.id_socio, s.fecha_nacimiento, s.notas, s.fitpoints,
                       s.id_entrenador AS id_entrenador_asignado,
                       ua.nombre AS entrenador_nombre, ua.apellido AS entrenador_apellido
                FROM usuarios u
                JOIN roles r ON r.id_rol = u.id_rol
                LEFT JOIN entrenadores e ON e.id_usuario = u.id_usuario
                LEFT JOIN socios s ON s.id_usuario = u.id_usuario
                LEFT JOIN entrenadores ea ON ea.id_entrenador = s.id_entrenador
                LEFT JOIN usuarios ua ON ua.id_usuario = ea.id_usuario';
    }
}
