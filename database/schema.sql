-- database/schema.sql
-- Esquema completo para una instalación nueva de Entrada Consciente (MySQL 8 / TiDB).
-- Coincide con la base de datos actual. Para ajustes opcionales sobre una BD existente: php database/migrate.php

CREATE TABLE IF NOT EXISTS usuarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    usuario     VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    rol         ENUM('admin','docente') NOT NULL DEFAULT 'docente',
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estudiantes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    documento       VARCHAR(50)  NULL UNIQUE,
    nombres         VARCHAR(150) NULL,
    grado           VARCHAR(20)  NULL,
    celular_padre   VARCHAR(30)  NULL,   -- columna antigua, se sigue leyendo como respaldo
    correo_padre    VARCHAR(100) NULL,   -- columna antigua, se sigue leyendo como respaldo
    celular_padres  VARCHAR(20)  NULL,
    correo_padres   VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inasistencias (
    id_inasistencia INT AUTO_INCREMENT PRIMARY KEY,
    estudiante      VARCHAR(150) NULL,
    grado           VARCHAR(20)  NULL,
    materia         VARCHAR(100) NULL,
    docente         VARCHAR(150) NULL,
    estado          ENUM('presente','ausente','evadido') NULL,
    observacion     TEXT NULL,
    fecha_registro  DATETIME NULL,
    INDEX idx_toma (grado, materia, docente, fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario inicial: admin / admin123  (cámbiela al ingresar, desde "Mi perfil")
INSERT IGNORE INTO usuarios (nombre, usuario, password, rol)
VALUES ('Administrador del Sistema', 'admin', '$2y$10$Ufd8KUupcr2DeB9WjKX7HO8rZFO7I82V4g7TUXMyqzlKZOoFCA6gG', 'admin');
