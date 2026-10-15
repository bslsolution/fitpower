<?php
namespace App\Models;

class UsuarioModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        try {
            FitpointsModel::asegurarColumnas($db);
        } catch (\Throwable $e) {
            // Tabla o columna aún no lista: no bloquea el login.
        }
        try {
            self::asegurarColumnaMembresia($db);
        } catch (\Throwable $e) {
            // Si la columna no se puede crear, no bloquea el resto de la API.
        }
        try {
            self::asegurarColumnaVisitaDashboard($db);
        } catch (\Throwable $e) {
            // Si la columna no se puede crear, no bloquea el resto de la API.
        }
        try {
            self::asegurarUsuariosDemo($db);
        } catch (\Throwable $e) {
            // Si el seed falla, no bloquea el login ni el resto de la API.
        }
    }

    public static function asegurarUsuariosDemo($db): void {
        static $hecho = false;
        if ($hecho) {
            return;
        }
        $hecho = true;

        $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $admins = [
            ['nombre' => 'Bruno', 'apellido' => 'Silva', 'email' => 'admin2@gym.local', 'telefono' => '099000004'],
            ['nombre' => 'Carla', 'apellido' => 'Gómez', 'email' => 'admin3@gym.local', 'telefono' => '099000005'],
        ];
        $entrenadores = [
            ['nombre' => 'Martín', 'apellido' => 'Perez', 'email' => 'martin.perez@gym.local', 'telefono' => '099000006', 'especialidad' => 'Musculación', 'bio' => 'Entrenador de fuerza e hipertrofia'],
            ['nombre' => 'Camila', 'apellido' => 'Torres', 'email' => 'camila.torres@gym.local', 'telefono' => '099000007', 'especialidad' => 'Entrenamiento funcional', 'bio' => 'Funcional, HIT y movilidad'],
            ['nombre' => 'Lucas', 'apellido' => 'Ferrari', 'email' => 'lucas.ferrari@gym.local', 'telefono' => '099000008', 'especialidad' => 'Preparación física', 'bio' => 'Resistencia, cardio y cross training'],
            ['nombre' => 'Sofía', 'apellido' => 'Ramírez', 'email' => 'sofia.ramirez@gym.local', 'telefono' => '099000009', 'especialidad' => 'Movilidad y técnica', 'bio' => 'Técnica, movilidad y control de carga'],
            ['nombre' => 'Diego', 'apellido' => 'Álvarez', 'email' => 'diego.alvarez@gym.local', 'telefono' => '099000010', 'especialidad' => 'Acondicionamiento', 'bio' => 'Acondicionamiento general y fuerza'],
        ];
        $socios = [
            ['nombre' => 'Valentina', 'apellido' => 'Rossi', 'email' => 'valentina.rossi@gym.local', 'telefono' => '099000011', 'entrenador' => 'entrenador@gym.local', 'fecha' => '1998-03-21', 'fitpoints' => 20],
            ['nombre' => 'Mateo', 'apellido' => 'López', 'email' => 'mateo.lopez@gym.local', 'telefono' => '099000012', 'entrenador' => 'entrenador@gym.local', 'fecha' => '1996-11-02', 'fitpoints' => 10],
            ['nombre' => 'Emilia', 'apellido' => 'Suárez', 'email' => 'emilia.suarez@gym.local', 'telefono' => '099000013', 'entrenador' => 'entrenador@gym.local', 'fecha' => '2001-07-18', 'fitpoints' => 40],
            ['nombre' => 'Joaquín', 'apellido' => 'Castro', 'email' => 'joaquin.castro@gym.local', 'telefono' => '099000014', 'entrenador' => 'entrenador@gym.local', 'fecha' => '1999-01-09', 'fitpoints' => 0],
            ['nombre' => 'Martina', 'apellido' => 'Pereira', 'email' => 'martina.pereira@gym.local', 'telefono' => '099000015', 'entrenador' => 'martin.perez@gym.local', 'fecha' => '1997-08-14', 'fitpoints' => 30],
            ['nombre' => 'Thiago', 'apellido' => 'Rodríguez', 'email' => 'thiago.rodriguez@gym.local', 'telefono' => '099000016', 'entrenador' => 'martin.perez@gym.local', 'fecha' => '2002-04-27', 'fitpoints' => 0],
            ['nombre' => 'Lucía', 'apellido' => 'Fernández', 'email' => 'lucia.fernandez@gym.local', 'telefono' => '099000017', 'entrenador' => 'martin.perez@gym.local', 'fecha' => '1995-12-05', 'fitpoints' => 60],
            ['nombre' => 'Facundo', 'apellido' => 'Méndez', 'email' => 'facundo.mendez@gym.local', 'telefono' => '099000018', 'entrenador' => 'camila.torres@gym.local', 'fecha' => '1994-06-30', 'fitpoints' => 15],
            ['nombre' => 'Camila', 'apellido' => 'Acosta', 'email' => 'camila.acosta@gym.local', 'telefono' => '099000019', 'entrenador' => 'camila.torres@gym.local', 'fecha' => '2003-09-11', 'fitpoints' => 25],
            ['nombre' => 'Nicolás', 'apellido' => 'Vega', 'email' => 'nicolas.vega@gym.local', 'telefono' => '099000020', 'entrenador' => 'camila.torres@gym.local', 'fecha' => '1998-02-16', 'fitpoints' => 5],
            ['nombre' => 'Florencia', 'apellido' => 'Díaz', 'email' => 'florencia.diaz@gym.local', 'telefono' => '099000021', 'entrenador' => 'lucas.ferrari@gym.local', 'fecha' => '2000-10-08', 'fitpoints' => 35],
            ['nombre' => 'Agustín', 'apellido' => 'Silva', 'email' => 'agustin.silva@gym.local', 'telefono' => '099000022', 'entrenador' => 'lucas.ferrari@gym.local', 'fecha' => '1993-05-22', 'fitpoints' => 0],
            ['nombre' => 'Julieta', 'apellido' => 'Romero', 'email' => 'julieta.romero@gym.local', 'telefono' => '099000023', 'entrenador' => 'lucas.ferrari@gym.local', 'fecha' => '1999-07-03', 'fitpoints' => 45],
            ['nombre' => 'Tomás', 'apellido' => 'Herrera', 'email' => 'tomas.herrera@gym.local', 'telefono' => '099000024', 'entrenador' => 'sofia.ramirez@gym.local', 'fecha' => '2001-01-25', 'fitpoints' => 10],
            ['nombre' => 'Mía', 'apellido' => 'González', 'email' => 'mia.gonzalez@gym.local', 'telefono' => '099000025', 'entrenador' => 'sofia.ramirez@gym.local', 'fecha' => '1996-08-19', 'fitpoints' => 20],
            ['nombre' => 'Lautaro', 'apellido' => 'Núñez', 'email' => 'lautaro.nunez@gym.local', 'telefono' => '099000026', 'entrenador' => 'sofia.ramirez@gym.local', 'fecha' => '1992-03-07', 'fitpoints' => 70],
            ['nombre' => 'Antonella', 'apellido' => 'Ruiz', 'email' => 'antonella.ruiz@gym.local', 'telefono' => '099000027', 'entrenador' => 'diego.alvarez@gym.local', 'fecha' => '2004-11-13', 'fitpoints' => 0],
            ['nombre' => 'Renata', 'apellido' => 'Olivera', 'email' => 'renata.olivera@gym.local', 'telefono' => '099000028', 'entrenador' => 'diego.alvarez@gym.local', 'fecha' => '1997-12-28', 'fitpoints' => 15],
            ['nombre' => 'Paula', 'apellido' => 'Benítez', 'email' => 'paula.benitez@gym.local', 'telefono' => '099000029', 'entrenador' => 'diego.alvarez@gym.local', 'fecha' => '1995-04-01', 'fitpoints' => 55],
            ['nombre' => 'Ignacio', 'apellido' => 'Pintos', 'email' => 'ignacio.pintos@gym.local', 'telefono' => '099000030', 'entrenador' => 'diego.alvarez@gym.local', 'fecha' => '1998-09-06', 'fitpoints' => 5],
        ];

        try {
            $idAdmin = self::idRol($db, 'administrador');
            $idEnt = self::idRol($db, 'entrenador');
            $idSocio = self::idRol($db, 'socio');
            if (!$idAdmin || !$idEnt || !$idSocio) {
                return;
            }

            foreach ($admins as $fila) {
                self::insertarSiFalta($db, $idAdmin, $fila, $hash, null);
            }
            $saltoMartin = false;
            foreach ($entrenadores as $fila) {
                if (self::existePersona($db, $fila['nombre'], $fila['apellido'])) {
                    if ($fila['email'] === 'martin.perez@gym.local') {
                        $saltoMartin = true;
                    }
                    continue;
                }
                self::insertarSiFalta($db, $idEnt, $fila, $hash, 'entrenador');
            }
            if ($saltoMartin) {
                self::insertarSiFalta($db, $idEnt, [
                    'nombre' => 'Andrés',
                    'apellido' => 'Molina',
                    'email' => 'andres.molina@gym.local',
                    'telefono' => '099000031',
                    'especialidad' => 'Fuerza funcional',
                    'bio' => 'Fuerza, técnica y control de carga',
                ], $hash, 'entrenador');
            }

            $mapaEntrenador = [];
            $stMap = $db->query(
                "SELECT u.email, u.nombre, u.apellido, e.id_entrenador
                 FROM usuarios u
                 JOIN entrenadores e ON e.id_usuario = u.id_usuario"
            );
            foreach ($stMap->fetchAll(\PDO::FETCH_ASSOC) as $fila) {
                $idEntRow = (int) $fila['id_entrenador'];
                $mapaEntrenador[$fila['email']] = $idEntRow;
                $mapaEntrenador[self::normalizar($fila['nombre'] . ' ' . $fila['apellido'])] = $idEntRow;
            }
            if (empty($mapaEntrenador['martin.perez@gym.local'])) {
                $mapaEntrenador['martin.perez@gym.local'] = $mapaEntrenador['entrenador1@gym.local']
                    ?? $mapaEntrenador[self::normalizar('Martin Perez')]
                    ?? $mapaEntrenador[self::normalizar('Martín Perez')]
                    ?? null;
            }

            foreach ($socios as $fila) {
                $idEntrenador = $mapaEntrenador[$fila['entrenador']] ?? null;
                self::insertarSiFalta($db, $idSocio, $fila, $hash, 'socio', $idEntrenador);
            }
        } catch (\Throwable $e) {
            // Si el seed falla, no bloquea el login ni el resto de la API.
        }
    }

    public static function asegurarColumnaMembresia($db): void {
        static $hecho = false;
        if (!$hecho) {
            $hecho = true;
            $col = $db->query("SHOW COLUMNS FROM socios LIKE 'membresia_activa'")->fetch();
            if (!$col) {
                $db->exec('ALTER TABLE socios ADD COLUMN membresia_activa TINYINT(1) NOT NULL DEFAULT 1');
                $db->exec(
                    'UPDATE socios s
                     JOIN usuarios u ON u.id_usuario = s.id_usuario
                     SET s.membresia_activa = 0
                     WHERE DATE_ADD(u.fecha_alta, INTERVAL 1 YEAR) < NOW()'
                );
            }
        }
        self::asegurarColumnaMembresiaVence($db);
    }

    public static function asegurarColumnaMembresiaVence($db): void {
        static $hecho = false;
        if ($hecho) {
            self::vencerMembresiasCaducadas($db);
            return;
        }
        $hecho = true;
        $col = $db->query("SHOW COLUMNS FROM socios LIKE 'membresia_vence'")->fetch();
        if (!$col) {
            $db->exec('ALTER TABLE socios ADD COLUMN membresia_vence DATE NULL');
        }
        $db->exec(
            'UPDATE socios
             SET membresia_vence = DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
             WHERE membresia_activa = 1 AND membresia_vence IS NULL'
        );
        $db->exec(
            'UPDATE socios
             SET membresia_vence = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
             WHERE membresia_activa = 0 AND membresia_vence IS NULL'
        );
        self::vencerMembresiasCaducadas($db);
    }

    public static function vencerMembresiasCaducadas($db): void {
        $col = $db->query("SHOW COLUMNS FROM socios LIKE 'membresia_vence'")->fetch();
        if (!$col) {
            return;
        }
        $db->exec(
            'UPDATE socios
             SET membresia_activa = 0
             WHERE membresia_activa = 1
               AND membresia_vence IS NOT NULL
               AND membresia_vence < CURDATE()'
        );
    }

    public static function venceFormato(?string $fecha): ?string {
        if (!$fecha) {
            return null;
        }
        try {
            return (new \DateTime($fecha))->format('d/m/Y');
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function asegurarColumnaVisitaDashboard($db): void {
        static $hecho = false;
        if ($hecho) {
            return;
        }
        $hecho = true;
        $col = $db->query("SHOW COLUMNS FROM usuarios LIKE 'ultima_visita_dashboard'")->fetch();
        if (!$col) {
            $db->exec('ALTER TABLE usuarios ADD COLUMN ultima_visita_dashboard DATETIME NULL');
            $db->exec(
                "UPDATE usuarios
                 SET ultima_visita_dashboard = DATE_SUB(NOW(), INTERVAL 8 DAY)
                 WHERE email = 'joaquin.castro@gym.local'"
            );
        }
        self::limpiarVisitasMasivas($db);
    }

    private static function limpiarVisitasMasivas($db): void {
        $db->exec(
            'UPDATE usuarios u
             INNER JOIN (
                 SELECT ultima_visita_dashboard FROM (
                     SELECT ultima_visita_dashboard
                     FROM usuarios
                     WHERE ultima_visita_dashboard IS NOT NULL
                     GROUP BY ultima_visita_dashboard
                     HAVING COUNT(*) >= 8
                 ) t
             ) g ON g.ultima_visita_dashboard = u.ultima_visita_dashboard
             SET u.ultima_visita_dashboard = NULL'
        );
    }

    public function registrarVisitaDashboard(int $idUsuario): void {
        if ($idUsuario <= 0) {
            return;
        }
        self::asegurarColumnaVisitaDashboard($this->db);
        $st = $this->db->prepare(
            'UPDATE usuarios SET ultima_visita_dashboard = NOW() WHERE id_usuario = :id'
        );
        $st->execute(['id' => $idUsuario]);
    }

    private static function normalizar(string $texto): string {
        $texto = mb_strtolower($texto, 'UTF-8');
        return str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $texto
        );
    }

    private static function existePersona($db, string $nombre, string $apellido): bool {
        $stmt = $db->query('SELECT nombre, apellido FROM usuarios');
        $clave = self::normalizar($nombre . ' ' . $apellido);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $fila) {
            if (self::normalizar($fila['nombre'] . ' ' . $fila['apellido']) === $clave) {
                return true;
            }
        }
        return false;
    }

    private static function idRol($db, string $nombre): ?int {
        $stmt = $db->prepare('SELECT id_rol FROM roles WHERE nombre = :n LIMIT 1');
        $stmt->execute(['n' => $nombre]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ? (int) $fila['id_rol'] : null;
    }

    private static function insertarSiFalta(
        $db,
        int $idRol,
        array $fila,
        string $hash,
        ?string $tipo,
        ?int $idEntrenador = null
    ): void {
        $existe = $db->prepare('SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1');
        $existe->execute(['email' => $fila['email']]);
        if ($existe->fetch()) {
            return;
        }

        $ins = $db->prepare(
            'INSERT INTO usuarios (id_rol, nombre, apellido, email, password_hash, telefono, activo)
             VALUES (:id_rol, :nombre, :apellido, :email, :password_hash, :telefono, 1)'
        );
        $ins->execute([
            'id_rol' => $idRol,
            'nombre' => $fila['nombre'],
            'apellido' => $fila['apellido'],
            'email' => $fila['email'],
            'password_hash' => $hash,
            'telefono' => $fila['telefono'] ?? null,
        ]);
        $id = (int) $db->lastInsertId();

        if ($tipo === 'entrenador') {
            $st = $db->prepare(
                'INSERT INTO entrenadores (id_usuario, especialidad, bio) VALUES (:id, :esp, :bio)'
            );
            $st->execute([
                'id' => $id,
                'esp' => $fila['especialidad'] ?? null,
                'bio' => $fila['bio'] ?? null,
            ]);
        }

        if ($tipo === 'socio') {
            self::asegurarColumnaMembresia($db);
            $st = $db->prepare(
                'INSERT INTO socios (id_usuario, id_entrenador, fecha_nacimiento, notas, fitpoints, membresia_activa, membresia_vence)
                 VALUES (:id, :ent, :fn, NULL, :fp, 1, DATE_ADD(CURDATE(), INTERVAL 1 MONTH))'
            );
            $st->execute([
                'id' => $id,
                'ent' => $idEntrenador,
                'fn' => $fila['fecha'] ?? null,
                'fp' => (int) ($fila['fitpoints'] ?? 0),
            ]);
        }
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
        $stmt = $this->db->query(
            $this->sqlBase() . " ORDER BY CASE r.nombre
                WHEN 'socio' THEN 1
                WHEN 'entrenador' THEN 2
                WHEN 'administrador' THEN 3
                ELSE 4
            END, u.apellido, u.nombre"
        );
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
                self::asegurarColumnaMembresia($this->db);
                $st = $this->db->prepare(
                    'INSERT INTO socios (id_usuario, id_entrenador, fecha_nacimiento, notas, membresia_activa, membresia_vence)
                     VALUES (:id, :ent, :fn, :notas, 1, DATE_ADD(CURDATE(), INTERVAL 1 MONTH))'
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

    public function actualizar(int $id, array $datos): void {
        $this->db->beginTransaction();
        try {
            $sets = [
                'nombre = :nombre',
                'apellido = :apellido',
                'email = :email',
                'telefono = :telefono',
            ];
            $params = [
                'id' => $id,
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'] ?? null,
            ];
            if (!empty($datos['password_hash'])) {
                $sets[] = 'password_hash = :password_hash';
                $params['password_hash'] = $datos['password_hash'];
            }
            $stmt = $this->db->prepare(
                'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id_usuario = :id'
            );
            $stmt->execute($params);

            if (($datos['rol'] ?? '') === 'socio' && array_key_exists('id_entrenador', $datos)) {
                $st = $this->db->prepare(
                    'UPDATE socios SET id_entrenador = :entrenador WHERE id_usuario = :id'
                );
                $st->execute([
                    'entrenador' => $datos['id_entrenador'],
                    'id' => $id,
                ]);
            }

            $this->db->commit();
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
            'membresia_activa' => isset($fila['membresia_activa']) ? (int) $fila['membresia_activa'] === 1 : true,
            'membresia_vence' => $fila['membresia_vence'] ?? null,
            'membresia' => $fila['rol'] === 'socio' ? self::membresiaDesde($fila) : null,
        ];
    }

    public static function membresiaDesde(array $fila): string {
        if ((int) ($fila['activo'] ?? 1) !== 1) {
            return 'inactiva';
        }
        if (array_key_exists('membresia_activa', $fila) && $fila['membresia_activa'] !== null
            && (int) $fila['membresia_activa'] !== 1) {
            return 'inactiva';
        }
        $vence = $fila['membresia_vence'] ?? null;
        if ($vence) {
            try {
                if ((new \DateTime($vence))->setTime(0, 0) < new \DateTime('today')) {
                    return 'inactiva';
                }
            } catch (\Exception $e) {
                // Si la fecha no se puede leer, se usa el flag de membresía.
            }
        }
        if (array_key_exists('membresia_activa', $fila) && $fila['membresia_activa'] !== null) {
            return ((int) $fila['membresia_activa'] === 1) ? 'activa' : 'inactiva';
        }
        $alta = $fila['fecha_alta'] ?? null;
        if (!$alta) {
            return 'activa';
        }
        try {
            $fin = (new \DateTime($alta))->modify('+1 month');
            return $fin < new \DateTime() ? 'inactiva' : 'activa';
        } catch (\Exception $e) {
            return 'activa';
        }
    }

    public function actualizarMembresia(int $idUsuario, bool $activa): bool {
        $fila = $this->porId($idUsuario);
        if (!$fila || ($fila['rol'] ?? '') !== 'socio') {
            return false;
        }
        self::asegurarColumnaMembresia($this->db);
        if ($activa) {
            $st = $this->db->prepare(
                'UPDATE socios
                 SET membresia_activa = 1,
                     membresia_vence = DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
                 WHERE id_usuario = :id'
            );
            $st->execute(['id' => $idUsuario]);
        } else {
            $st = $this->db->prepare(
                'UPDATE socios SET membresia_activa = 0 WHERE id_usuario = :id'
            );
            $st->execute(['id' => $idUsuario]);
        }
        return true;
    }

    public function contarPorRol(string $rol): int {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM usuarios u
             JOIN roles r ON r.id_rol = u.id_rol
             WHERE r.nombre = :rol'
        );
        $stmt->execute(['rol' => $rol]);
        return (int) $stmt->fetchColumn();
    }

    public function borrar(int $idUsuario): void {
        $fila = $this->porId($idUsuario);
        if (!$fila) {
            return;
        }
        $this->db->beginTransaction();
        try {
            $idEntrenador = $fila['id_entrenador'] !== null ? (int) $fila['id_entrenador'] : 0;
            if (($fila['rol'] ?? '') === 'entrenador' && $idEntrenador > 0) {
                $this->db->prepare(
                    'UPDATE rutinas SET id_rutina_origen = NULL
                     WHERE id_rutina_origen IN (
                         SELECT id_rutina FROM (
                             SELECT id_rutina FROM rutinas WHERE id_entrenador = :id
                         ) t
                     )'
                )->execute(['id' => $idEntrenador]);
                $this->db->prepare(
                    'DELETE FROM rutinas WHERE id_entrenador = :id'
                )->execute(['id' => $idEntrenador]);
            }
            $this->db->prepare(
                'DELETE FROM notificaciones WHERE id_usuario_destinatario = :id'
            )->execute(['id' => $idUsuario]);
            $this->db->prepare(
                'DELETE FROM usuarios WHERE id_usuario = :id'
            )->execute(['id' => $idUsuario]);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function sqlBase(): string {
        return 'SELECT u.id_usuario, u.id_rol, r.nombre AS rol, u.nombre, u.apellido,
                       u.email, u.password_hash, u.telefono, u.activo, u.fecha_alta,
                       e.id_entrenador, e.especialidad, e.bio,
                       s.id_socio, s.fecha_nacimiento, s.notas, s.fitpoints, s.membresia_activa, s.membresia_vence,
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
