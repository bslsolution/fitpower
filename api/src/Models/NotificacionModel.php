<?php
namespace App\Models;

class NotificacionModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        self::asegurarTabla($db);
    }

    public static function asegurarTabla($db): void {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS notificaciones (
                id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
                destinatario_rol VARCHAR(20) NOT NULL,
                id_usuario_destinatario INT NULL,
                tipo VARCHAR(40) NOT NULL,
                datos JSON NULL,
                leida TINYINT(1) NOT NULL DEFAULT 0,
                fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_notif_rol_fecha (destinatario_rol, fecha),
                INDEX idx_notif_usuario (id_usuario_destinatario, leida)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $col = $db->query("SHOW COLUMNS FROM notificaciones LIKE 'leida'")->fetch();
        if (!$col) {
            $db->exec('ALTER TABLE notificaciones ADD COLUMN leida TINYINT(1) NOT NULL DEFAULT 0');
        }
        $colUser = $db->query("SHOW COLUMNS FROM notificaciones LIKE 'id_usuario_destinatario'")->fetch();
        if (!$colUser) {
            $db->exec('ALTER TABLE notificaciones ADD COLUMN id_usuario_destinatario INT NULL');
        }
    }

    public function crear(string $rol, string $tipo, array $datos, ?int $idUsuario = null): int {
        $stmt = $this->db->prepare(
            'INSERT INTO notificaciones (destinatario_rol, id_usuario_destinatario, tipo, datos)
             VALUES (:rol, :usuario, :tipo, :datos)'
        );
        $stmt->execute([
            'rol' => $rol,
            'usuario' => $idUsuario,
            'tipo' => $tipo,
            'datos' => json_encode($datos, JSON_UNESCAPED_UNICODE),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function listarParaUsuario(string $rol, int $idUsuario): array {
        $stmt = $this->db->prepare(
            'SELECT id_notificacion, destinatario_rol, tipo, datos, leida, fecha
             FROM notificaciones
             WHERE destinatario_rol = :rol
               AND (id_usuario_destinatario IS NULL OR id_usuario_destinatario = :usuario)
             ORDER BY fecha DESC, id_notificacion DESC'
        );
        $stmt->execute(['rol' => $rol, 'usuario' => $idUsuario]);
        $filas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'publico'], $filas);
    }

    private function publico(array $fila): array {
        $datos = $fila['datos'] ?? null;
        if (is_string($datos)) {
            $datos = json_decode($datos, true);
        }
        if (!is_array($datos)) {
            $datos = [];
        }
        return [
            'id_notificacion' => (int) $fila['id_notificacion'],
            'tipo' => $fila['tipo'],
            'datos' => $datos,
            'leida' => (int) ($fila['leida'] ?? 0) === 1,
            'fecha' => $fila['fecha'],
        ];
    }

    public function contarNuevas(string $rol, int $idUsuario): int {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notificaciones
             WHERE destinatario_rol = :rol
               AND (id_usuario_destinatario IS NULL OR id_usuario_destinatario = :usuario)
               AND leida = 0'
        );
        $stmt->execute(['rol' => $rol, 'usuario' => $idUsuario]);
        return (int) $stmt->fetchColumn();
    }

    public function marcarLeidas(string $rol, int $idUsuario): void {
        $stmt = $this->db->prepare(
            'UPDATE notificaciones SET leida = 1
             WHERE destinatario_rol = :rol
               AND (id_usuario_destinatario IS NULL OR id_usuario_destinatario = :usuario)
               AND leida = 0'
        );
        $stmt->execute(['rol' => $rol, 'usuario' => $idUsuario]);
    }

    public function existeNueva(string $rol, string $tipo, int $idUsuario, ?int $idSocio = null): bool {
        $sql = 'SELECT id_notificacion FROM notificaciones
                WHERE destinatario_rol = :rol
                  AND tipo = :tipo
                  AND id_usuario_destinatario = :usuario
                  AND leida = 0';
        $params = ['rol' => $rol, 'tipo' => $tipo, 'usuario' => $idUsuario];
        if ($idSocio !== null) {
            $sql .= ' AND JSON_UNQUOTE(JSON_EXTRACT(datos, \'$.id_socio\')) = :socio';
            $params['socio'] = (string) $idSocio;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }
}
