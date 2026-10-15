-- ========================================================
-- FITPOWER — DDL + datos de prueba
-- Charset utf8mb4. Docker ya crea la base (MYSQL_DATABASE).
-- Contraseña de usuarios seed: password
-- Hash: bcrypt de la palabra "password"
-- Canonical: documentos/base-de-datos/schema.sql
-- Copia de trabajo Docker: api/config/init.sql
-- ========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS entrenamiento_series;
DROP TABLE IF EXISTS entrenamientos;
DROP TABLE IF EXISTS notificaciones;
DROP TABLE IF EXISTS canjes_fitpoints;
DROP TABLE IF EXISTS recompensas_fitpoints;
DROP TABLE IF EXISTS progreso_pesos;
DROP TABLE IF EXISTS series;
DROP TABLE IF EXISTS rutina_ejercicios;
DROP TABLE IF EXISTS rutinas;
DROP TABLE IF EXISTS ejercicios;
DROP TABLE IF EXISTS grupos_musculares;
DROP TABLE IF EXISTS socios;
DROP TABLE IF EXISTS entrenadores;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- CATÁLOGO DE ROLES
-- --------------------------------------------------------
CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- CUENTAS (solo el administrador da de alta en la app)
-- --------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    apellido VARCHAR(80) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    telefono VARCHAR(30) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultima_visita_dashboard DATETIME NULL,
    CONSTRAINT fk_usuarios_rol
        FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE entrenadores (
    id_entrenador INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    especialidad VARCHAR(80) NULL,
    bio VARCHAR(255) NULL,
    CONSTRAINT fk_entrenadores_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE socios (
    id_socio INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    id_entrenador INT NULL,
    fecha_nacimiento DATE NULL,
    notas VARCHAR(255) NULL,
    fitpoints INT NOT NULL DEFAULT 0,
    membresia_activa TINYINT(1) NOT NULL DEFAULT 1,
    membresia_vence DATE NULL,
    CONSTRAINT fk_socios_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE,
    CONSTRAINT fk_socios_entrenador
        FOREIGN KEY (id_entrenador) REFERENCES entrenadores(id_entrenador)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- CATÁLOGO DE GRUPOS MUSCULARES (el admin los edita)
-- --------------------------------------------------------
CREATE TABLE grupos_musculares (
    id_grupo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- CATÁLOGO DE EJERCICIOS
-- --------------------------------------------------------
CREATE TABLE ejercicios (
    id_ejercicio INT AUTO_INCREMENT PRIMARY KEY,
    id_entrenador_creador INT NULL,
    nombre VARCHAR(100) NOT NULL,
    grupo_muscular VARCHAR(50) NULL,
    descripcion TEXT NULL,
    imagen VARCHAR(255) NULL,
    CONSTRAINT fk_ejercicios_entrenador
        FOREIGN KEY (id_entrenador_creador) REFERENCES entrenadores(id_entrenador)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- RUTINAS (id_socio NULL = plantilla; NOT NULL = asignada)
-- --------------------------------------------------------
CREATE TABLE rutinas (
    id_rutina INT AUTO_INCREMENT PRIMARY KEY,
    id_entrenador INT NOT NULL,
    id_socio INT NULL,
    id_rutina_origen INT NULL,
    nombre VARCHAR(100) NOT NULL,
    objetivo VARCHAR(150) NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'borrador',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rutinas_entrenador
        FOREIGN KEY (id_entrenador) REFERENCES entrenadores(id_entrenador),
    CONSTRAINT fk_rutinas_socio
        FOREIGN KEY (id_socio) REFERENCES socios(id_socio)
        ON DELETE SET NULL,
    CONSTRAINT fk_rutinas_origen
        FOREIGN KEY (id_rutina_origen) REFERENCES rutinas(id_rutina)
        ON DELETE SET NULL,
    CONSTRAINT chk_rutinas_estado
        CHECK (estado IN ('borrador', 'activa', 'finalizada'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ejercicio DENTRO de una rutina (orden = posición para armar/mover)
CREATE TABLE rutina_ejercicios (
    id_rutina_ejercicio INT AUTO_INCREMENT PRIMARY KEY,
    id_rutina INT NOT NULL,
    id_ejercicio INT NOT NULL,
    orden INT NOT NULL DEFAULT 1,
    notas VARCHAR(255) NULL,
    CONSTRAINT fk_re_rutina
        FOREIGN KEY (id_rutina) REFERENCES rutinas(id_rutina)
        ON DELETE CASCADE,
    CONSTRAINT fk_re_ejercicio
        FOREIGN KEY (id_ejercicio) REFERENCES ejercicios(id_ejercicio),
    INDEX idx_re_rutina_orden (id_rutina, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE series (
    id_serie INT AUTO_INCREMENT PRIMARY KEY,
    id_rutina_ejercicio INT NOT NULL,
    orden INT NOT NULL DEFAULT 1,
    repeticiones INT NOT NULL,
    peso_kg DECIMAL(6,2) NULL,
    descanso_segundos INT NULL,
    CONSTRAINT fk_series_re
        FOREIGN KEY (id_rutina_ejercicio) REFERENCES rutina_ejercicios(id_rutina_ejercicio)
        ON DELETE CASCADE,
    INDEX idx_series_re_orden (id_rutina_ejercicio, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Entrenamientos que el socio guarda con Terminar (historial de progreso)
CREATE TABLE entrenamientos (
    id_entrenamiento INT AUTO_INCREMENT PRIMARY KEY,
    id_socio INT NOT NULL,
    id_rutina INT NULL,
    nombre VARCHAR(100) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fitpoints_otorgados TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_ent_socio
        FOREIGN KEY (id_socio) REFERENCES socios(id_socio)
        ON DELETE CASCADE,
    CONSTRAINT fk_ent_rutina
        FOREIGN KEY (id_rutina) REFERENCES rutinas(id_rutina)
        ON DELETE SET NULL,
    INDEX idx_ent_socio_fecha (id_socio, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE entrenamiento_series (
    id_entrenamiento_serie INT AUTO_INCREMENT PRIMARY KEY,
    id_entrenamiento INT NOT NULL,
    id_serie_origen INT NULL,
    id_ejercicio INT NOT NULL,
    nombre_ejercicio VARCHAR(100) NOT NULL,
    grupo_muscular VARCHAR(50) NULL,
    serie_orden INT NOT NULL,
    repeticiones INT NOT NULL,
    peso_kg DECIMAL(6,2) NULL,
    descanso_segundos INT NULL,
    CONSTRAINT fk_ents_entrenamiento
        FOREIGN KEY (id_entrenamiento) REFERENCES entrenamientos(id_entrenamiento)
        ON DELETE CASCADE,
    CONSTRAINT fk_ents_ejercicio
        FOREIGN KEY (id_ejercicio) REFERENCES ejercicios(id_ejercicio),
    UNIQUE KEY uk_ents_entrenamiento_serie (id_entrenamiento, id_serie_origen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- RECOMPENSAS CANJEABLES CON FITPOINTS (las arma el administrador)
-- --------------------------------------------------------
CREATE TABLE recompensas_fitpoints (
    id_recompensa INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    costo_puntos INT NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) NULL,
    fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE canjes_fitpoints (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    destinatario_rol VARCHAR(20) NOT NULL,
    id_usuario_destinatario INT NULL,
    tipo VARCHAR(40) NOT NULL,
    datos JSON NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_rol_fecha (destinatario_rol, fecha),
    INDEX idx_notif_usuario (id_usuario_destinatario, leida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- DATOS DE PRUEBA
-- Hash bcrypt de "password":
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ========================================================

INSERT INTO roles (id_rol, nombre) VALUES
    (1, 'administrador'),
    (2, 'entrenador'),
    (3, 'socio');

INSERT INTO usuarios (id_usuario, id_rol, nombre, apellido, email, password_hash, telefono, activo) VALUES
    (1, 1, 'Ada', 'Admin', 'admin@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000001', 1),
    (2, 2, 'Elena', 'Entrenadora', 'entrenador@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000002', 1),
    (3, 3, 'Santiago', 'Socio', 'socio@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000003', 1),
    (4, 1, 'Bruno', 'Silva', 'admin2@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000004', 1),
    (5, 1, 'Carla', 'Gómez', 'admin3@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000005', 1),
    (6, 2, 'Martín', 'Perez', 'martin.perez@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000006', 1),
    (7, 2, 'Camila', 'Torres', 'camila.torres@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000007', 1),
    (8, 2, 'Lucas', 'Ferrari', 'lucas.ferrari@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000008', 1),
    (9, 2, 'Sofía', 'Ramírez', 'sofia.ramirez@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000009', 1),
    (10, 2, 'Diego', 'Álvarez', 'diego.alvarez@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000010', 1),
    (11, 3, 'Valentina', 'Rossi', 'valentina.rossi@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000011', 1),
    (12, 3, 'Mateo', 'López', 'mateo.lopez@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000012', 1),
    (13, 3, 'Emilia', 'Suárez', 'emilia.suarez@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000013', 1),
    (14, 3, 'Joaquín', 'Castro', 'joaquin.castro@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000014', 1),
    (15, 3, 'Martina', 'Pereira', 'martina.pereira@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000015', 1),
    (16, 3, 'Thiago', 'Rodríguez', 'thiago.rodriguez@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000016', 1),
    (17, 3, 'Lucía', 'Fernández', 'lucia.fernandez@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000017', 1),
    (18, 3, 'Facundo', 'Méndez', 'facundo.mendez@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000018', 1),
    (19, 3, 'Camila', 'Acosta', 'camila.acosta@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000019', 1),
    (20, 3, 'Nicolás', 'Vega', 'nicolas.vega@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000020', 1),
    (21, 3, 'Florencia', 'Díaz', 'florencia.diaz@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000021', 1),
    (22, 3, 'Agustín', 'Silva', 'agustin.silva@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000022', 1),
    (23, 3, 'Julieta', 'Romero', 'julieta.romero@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000023', 1),
    (24, 3, 'Tomás', 'Herrera', 'tomas.herrera@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000024', 1),
    (25, 3, 'Mía', 'González', 'mia.gonzalez@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000025', 1),
    (26, 3, 'Lautaro', 'Núñez', 'lautaro.nunez@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000026', 1),
    (27, 3, 'Antonella', 'Ruiz', 'antonella.ruiz@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000027', 1),
    (28, 3, 'Renata', 'Olivera', 'renata.olivera@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000028', 1),
    (29, 3, 'Paula', 'Benítez', 'paula.benitez@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000029', 1),
    (30, 3, 'Ignacio', 'Pintos', 'ignacio.pintos@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000030', 1);

INSERT INTO entrenadores (id_entrenador, id_usuario, especialidad, bio) VALUES
    (1, 2, 'Fuerza e hipertrofia', 'Personal trainer del gimnasio'),
    (2, 6, 'Musculación', 'Entrenador de fuerza e hipertrofia'),
    (3, 7, 'Entrenamiento funcional', 'Funcional, HIT y movilidad'),
    (4, 8, 'Preparación física', 'Resistencia, cardio y cross training'),
    (5, 9, 'Movilidad y técnica', 'Técnica, movilidad y control de carga'),
    (6, 10, 'Acondicionamiento', 'Acondicionamiento general y fuerza');

INSERT INTO socios (id_socio, id_usuario, id_entrenador, fecha_nacimiento, notas, fitpoints) VALUES
    (1, 3, 1, '2000-05-12', 'Socio de prueba', 50),
    (2, 11, 1, '1998-03-21', NULL, 20),
    (3, 12, 1, '1996-11-02', NULL, 10),
    (4, 13, 1, '2001-07-18', NULL, 40),
    (5, 14, 1, '1999-01-09', NULL, 0),
    (6, 15, 2, '1997-08-14', NULL, 30),
    (7, 16, 2, '2002-04-27', NULL, 0),
    (8, 17, 2, '1995-12-05', NULL, 60),
    (9, 18, 3, '1994-06-30', NULL, 15),
    (10, 19, 3, '2003-09-11', NULL, 25),
    (11, 20, 3, '1998-02-16', NULL, 5),
    (12, 21, 4, '2000-10-08', NULL, 35),
    (13, 22, 4, '1993-05-22', NULL, 0),
    (14, 23, 4, '1999-07-03', NULL, 45),
    (15, 24, 5, '2001-01-25', NULL, 10),
    (16, 25, 5, '1996-08-19', NULL, 20),
    (17, 26, 5, '1992-03-07', NULL, 70),
    (18, 27, 6, '2004-11-13', NULL, 0),
    (19, 28, 6, '1997-12-28', NULL, 15),
    (20, 29, 6, '1995-04-01', NULL, 55),
    (21, 30, 6, '1998-09-06', NULL, 5);

-- Socio de prueba con membresía inactiva
UPDATE socios s
JOIN usuarios u ON u.id_usuario = s.id_usuario
SET s.membresia_activa = 0
WHERE u.email = 'socio@gym.local';

UPDATE socios
SET membresia_vence = DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
WHERE membresia_activa = 1;

UPDATE socios
SET membresia_vence = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
WHERE membresia_activa = 0;

-- Socio de prueba inactivo (+7 días sin entrar al dashboard)
UPDATE usuarios
SET ultima_visita_dashboard = DATE_SUB(NOW(), INTERVAL 8 DAY)
WHERE email = 'joaquin.castro@gym.local';

INSERT INTO grupos_musculares (id_grupo, nombre) VALUES
    (1, 'Pierna'),
    (2, 'Pecho'),
    (3, 'Espalda'),
    (4, 'Core'),
    (5, 'Hombros'),
    (6, 'Brazos');

INSERT INTO ejercicios (id_ejercicio, id_entrenador_creador, nombre, grupo_muscular, descripcion, imagen) VALUES
    (1, 1, 'Sentadilla', 'Pierna', 'Barra en espalda, cadera atrás, rodillas alineadas.', '/front/dist/imagenes/ejercicios/sentadilla.gif'),
    (2, 1, 'Press banca', 'Pecho', 'Escápulas juntas, bajada controlada al pecho.', '/front/dist/imagenes/ejercicios/press-banca.gif'),
    (3, 1, 'Remo con barra', 'Espalda', 'Torso estable, llevar la barra al abdomen.', '/front/dist/imagenes/ejercicios/remo.gif'),
    (4, 1, 'Plancha', 'Core', 'Cuerpo en línea, sin hundir la lumbar.', '/front/dist/imagenes/ejercicios/plancha.gif');

-- Plantilla (sin socio)
INSERT INTO rutinas (id_rutina, id_entrenador, id_socio, id_rutina_origen, nombre, objetivo, estado) VALUES
    (1, 1, NULL, NULL, 'Full body inicial', 'Adaptación y técnica', 'activa');

INSERT INTO rutina_ejercicios (id_rutina_ejercicio, id_rutina, id_ejercicio, orden, notas) VALUES
    (1, 1, 1, 1, 'Profundidad cómoda'),
    (2, 1, 2, 2, NULL),
    (3, 1, 3, 3, NULL),
    (4, 1, 4, 4, '30 a 45 segundos si no hay reps');

INSERT INTO series (id_rutina_ejercicio, orden, repeticiones, peso_kg, descanso_segundos) VALUES
    (1, 1, 10, 40.00, 90),
    (1, 2, 10, 40.00, 90),
    (1, 3, 8, 45.00, 90),
    (2, 1, 8, 30.00, 90),
    (2, 2, 8, 30.00, 90),
    (2, 3, 8, 32.50, 90),
    (3, 1, 10, 30.00, 75),
    (3, 2, 10, 30.00, 75),
    (4, 1, 40, NULL, 45),
    (4, 2, 40, NULL, 45);

-- Copia asignada al socio (así se puede modificar sin tocar la plantilla)
INSERT INTO rutinas (id_rutina, id_entrenador, id_socio, id_rutina_origen, nombre, objetivo, estado) VALUES
    (2, 1, 1, 1, 'Full body inicial', 'Adaptación y técnica', 'activa');

INSERT INTO rutina_ejercicios (id_rutina_ejercicio, id_rutina, id_ejercicio, orden, notas) VALUES
    (5, 2, 1, 1, 'Profundidad cómoda'),
    (6, 2, 2, 2, NULL),
    (7, 2, 3, 3, NULL),
    (8, 2, 4, 4, '30 a 45 segundos si no hay reps');

INSERT INTO series (id_rutina_ejercicio, orden, repeticiones, peso_kg, descanso_segundos) VALUES
    (5, 1, 10, 40.00, 90),
    (5, 2, 10, 40.00, 90),
    (5, 3, 8, 45.00, 90),
    (6, 1, 8, 30.00, 90),
    (6, 2, 8, 30.00, 90),
    (6, 3, 8, 32.50, 90),
    (7, 1, 10, 30.00, 75),
    (7, 2, 10, 30.00, 75),
    (8, 1, 40, NULL, 45),
    (8, 2, 40, NULL, 45);

INSERT INTO recompensas_fitpoints (id_recompensa, nombre, costo_puntos, imagen, descripcion) VALUES
    (1, 'Camiseta', 100, '/front/dist/imagenes/recompensas/camiseta.png', 'Una camiseta personalizada de nuestro gimnasio.'),
    (2, 'Botella', 50, '/front/dist/imagenes/recompensas/botella.png', 'Botella para hidratarte en el entrenamiento.'),
    (3, 'Gorra', 120, '/front/dist/imagenes/recompensas/gorra.png', 'Gorra FitPower para el gym o la calle.'),
    (4, 'Mochila', 300, '/front/dist/imagenes/recompensas/mochila.png', 'Mochila para llevar tu ropa y accesorios.'),
    (5, 'Llavero', 50, '/front/dist/imagenes/recompensas/llavero.png', 'Llavero de merchandising FitPower.'),
    (6, 'Descuento', 500, '/front/dist/imagenes/recompensas/descuento.png', 'Descuento para tu próxima mensualidad.');
