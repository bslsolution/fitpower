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

DROP TABLE IF EXISTS series;
DROP TABLE IF EXISTS rutina_ejercicios;
DROP TABLE IF EXISTS rutinas;
DROP TABLE IF EXISTS ejercicios;
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
    fecha_nacimiento DATE NULL,
    notas VARCHAR(255) NULL,
    CONSTRAINT fk_socios_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
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
    (3, 3, 'Santiago', 'Socio', 'socio@gym.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '099000003', 1);

INSERT INTO entrenadores (id_entrenador, id_usuario, especialidad, bio) VALUES
    (1, 2, 'Fuerza e hipertrofia', 'Personal trainer del gimnasio');

INSERT INTO socios (id_socio, id_usuario, fecha_nacimiento, notas) VALUES
    (1, 3, '2000-05-12', 'Socio de prueba');

INSERT INTO ejercicios (id_ejercicio, id_entrenador_creador, nombre, grupo_muscular, descripcion) VALUES
    (1, 1, 'Sentadilla', 'Pierna', 'Barra en espalda, cadera atrás, rodillas alineadas.'),
    (2, 1, 'Press banca', 'Pecho', 'Escápulas juntas, bajada controlada al pecho.'),
    (3, 1, 'Remo con barra', 'Espalda', 'Torso estable, llevar la barra al abdomen.'),
    (4, 1, 'Plancha', 'Core', 'Cuerpo en línea, sin hundir la lumbar.');

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
