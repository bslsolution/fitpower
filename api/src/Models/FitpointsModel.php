<?php
namespace App\Models;

class FitpointsModel {
    public const PUNTOS_POR_RUTINA = 10;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        self::asegurarColumnas($db);
    }

    public static function asegurarColumnas($db): void {
        $socio = $db->query("SHOW COLUMNS FROM socios LIKE 'fitpoints'")->fetch();
        if (!$socio) {
            $db->exec('ALTER TABLE socios ADD COLUMN fitpoints INT NOT NULL DEFAULT 0');
        }
        $ent = $db->query("SHOW COLUMNS FROM entrenamientos LIKE 'fitpoints_otorgados'")->fetch();
        if (!$ent) {
            $db->exec(
                'ALTER TABLE entrenamientos ADD COLUMN fitpoints_otorgados TINYINT(1) NOT NULL DEFAULT 0'
            );
        }
        $db->exec(
            'CREATE TABLE IF NOT EXISTS canjes_fitpoints (
                id_canje INT AUTO_INCREMENT PRIMARY KEY,
                id_socio INT NOT NULL,
                id_recompensa INT NULL,
                nombre VARCHAR(120) NOT NULL,
                costo_puntos INT NOT NULL,
                fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_canje_socio
                    FOREIGN KEY (id_socio) REFERENCES socios(id_socio)
                    ON DELETE CASCADE,
                CONSTRAINT fk_canje_recompensa
                    FOREIGN KEY (id_recompensa) REFERENCES recompensas_fitpoints(id_recompensa)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        NotificacionModel::asegurarTabla($db);
    }

    public function puntosDeSocio(int $idSocio): int {
        $stmt = $this->db->prepare('SELECT fitpoints FROM socios WHERE id_socio = :id LIMIT 1');
        $stmt->execute(['id' => $idSocio]);
        $valor = $stmt->fetchColumn();
        return $valor === false ? 0 : (int) $valor;
    }

    public function completarRutina(int $idSocio, int $idEntrenamiento): array {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT id_entrenamiento, id_socio, fitpoints_otorgados
                 FROM entrenamientos
                 WHERE id_entrenamiento = :id
                 LIMIT 1
                 FOR UPDATE'
            );
            $stmt->execute(['id' => $idEntrenamiento]);
            $ent = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$ent || (int) $ent['id_socio'] !== $idSocio) {
                $this->db->rollBack();
                return ['ok' => false, 'error' => 'Entrenamiento no encontrado'];
            }

            $stSeries = $this->db->prepare(
                'SELECT COUNT(*) FROM entrenamiento_series WHERE id_entrenamiento = :id'
            );
            $stSeries->execute(['id' => $idEntrenamiento]);
            if ((int) $stSeries->fetchColumn() < 1) {
                $this->db->rollBack();
                return ['ok' => false, 'error' => 'Terminá al menos una serie para sumar FitPoints'];
            }

            $stPuntos = $this->db->prepare(
                'SELECT fitpoints FROM socios WHERE id_socio = :id LIMIT 1 FOR UPDATE'
            );
            $stPuntos->execute(['id' => $idSocio]);
            $puntos = (int) $stPuntos->fetchColumn();

            $otorgados = false;
            if ((int) $ent['fitpoints_otorgados'] !== 1) {
                $puntos += self::PUNTOS_POR_RUTINA;
                $upSocio = $this->db->prepare(
                    'UPDATE socios SET fitpoints = :puntos WHERE id_socio = :id'
                );
                $upSocio->execute(['puntos' => $puntos, 'id' => $idSocio]);
                $upEnt = $this->db->prepare(
                    'UPDATE entrenamientos SET fitpoints_otorgados = 1 WHERE id_entrenamiento = :id'
                );
                $upEnt->execute(['id' => $idEntrenamiento]);
                $otorgados = true;
            }

            $this->db->commit();
            return ['ok' => true, 'puntos' => $puntos, 'otorgados' => $otorgados];
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function canjear(int $idSocio, int $idRecompensa): array {
        $this->db->beginTransaction();
        try {
            $stR = $this->db->prepare(
                'SELECT id_recompensa, nombre, costo_puntos
                 FROM recompensas_fitpoints
                 WHERE id_recompensa = :id
                 LIMIT 1
                 FOR UPDATE'
            );
            $stR->execute(['id' => $idRecompensa]);
            $recompensa = $stR->fetch(\PDO::FETCH_ASSOC);
            if (!$recompensa) {
                $this->db->rollBack();
                return ['ok' => false, 'error' => 'Recompensa no encontrada'];
            }

            $stPuntos = $this->db->prepare(
                'SELECT fitpoints FROM socios WHERE id_socio = :id LIMIT 1 FOR UPDATE'
            );
            $stPuntos->execute(['id' => $idSocio]);
            $puntos = (int) $stPuntos->fetchColumn();
            $costo = (int) $recompensa['costo_puntos'];
            if ($puntos < $costo) {
                $this->db->rollBack();
                return ['ok' => false, 'error' => 'No tenés FitPoints suficientes'];
            }

            $puntos -= $costo;
            $up = $this->db->prepare('UPDATE socios SET fitpoints = :puntos WHERE id_socio = :id');
            $up->execute(['puntos' => $puntos, 'id' => $idSocio]);

            $ins = $this->db->prepare(
                'INSERT INTO canjes_fitpoints (id_socio, id_recompensa, nombre, costo_puntos)
                 VALUES (:socio, :rec, :nombre, :costo)'
            );
            $ins->execute([
                'socio' => $idSocio,
                'rec' => (int) $recompensa['id_recompensa'],
                'nombre' => $recompensa['nombre'],
                'costo' => $costo,
            ]);
            $idCanje = (int) $this->db->lastInsertId();

            $stSocio = $this->db->prepare(
                'SELECT u.nombre, u.apellido
                 FROM socios s
                 JOIN usuarios u ON u.id_usuario = s.id_usuario
                 WHERE s.id_socio = :id
                 LIMIT 1'
            );
            $stSocio->execute(['id' => $idSocio]);
            $socio = $stSocio->fetch(\PDO::FETCH_ASSOC) ?: [];

            $notif = new NotificacionModel($this->db);
            $notif->crear('administrador', 'canje_fitpoints', [
                'id_canje' => $idCanje,
                'id_socio' => $idSocio,
                'id_recompensa' => (int) $recompensa['id_recompensa'],
                'nombre' => $socio['nombre'] ?? '',
                'apellido' => $socio['apellido'] ?? '',
                'recompensa' => $recompensa['nombre'],
                'costo' => $costo,
            ]);

            $this->db->commit();
            return [
                'ok' => true,
                'puntos' => $puntos,
                'nombre' => $recompensa['nombre'],
                'costo' => $costo,
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
