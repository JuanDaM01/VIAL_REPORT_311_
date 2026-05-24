-- ============================================================
--  VialReport311 — DDL MySQL/MariaDB
--  Basado en el diagrama ER del proyecto
--  Ejecutar: mysql -u root -p < db/init.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS vialreport311
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE vialreport311;

-- ------------------------------------------------------------
--  UBICACION
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ubicacion (
    idUbicacion     INT AUTO_INCREMENT PRIMARY KEY,
    departamento    VARCHAR(100),
    ciudad          VARCHAR(100) NOT NULL,
    barrio          VARCHAR(100),
    direccionTexto  VARCHAR(255),
    latitud         DECIMAL(10,7),
    longitud        DECIMAL(10,7)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  USUARIO
--  (Incluye ciudadano y funcionario como roles del mismo usuario)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuario (
    idUsuario       INT AUTO_INCREMENT PRIMARY KEY,
    nombres         VARCHAR(100) NOT NULL,
    apellido_1      VARCHAR(100) NOT NULL,
    apellido_2      VARCHAR(100),
    email           VARCHAR(150) NOT NULL UNIQUE,
    contrasena      VARCHAR(255) NOT NULL,
    telefono        VARCHAR(20),
    edad            INT,
    fecha_nacimiento_dia  TINYINT,
    fecha_nacimiento_mes  TINYINT,
    fecha_nacimiento_ano  YEAR,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    tipoRegistro    ENUM('local','google','facebook') NOT NULL DEFAULT 'local',
    -- Campos de Funcionario (NULL si es ciudadano)
    cargo           VARCHAR(100),
    nivelAcceso     TINYINT      DEFAULT NULL,
    -- Campos de Administrador (NULL si no aplica)
    estadoCuenta    ENUM('activo','inactivo','suspendido') DEFAULT NULL,
    fechaAsignacionRol DATETIME  DEFAULT NULL,
    cantidadReportes INT         NOT NULL DEFAULT 0,
    fecha_registro  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  PROVEEDOR
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS proveedor (
    idProveedor         INT AUTO_INCREMENT PRIMARY KEY,
    nombreEntidad       VARCHAR(150) NOT NULL,
    telefono            VARCHAR(20),
    correo              VARCHAR(150),
    nivel               VARCHAR(50),
    solucionesResueltas INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  CATEGORIA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categoria (
    idCategoria  INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL UNIQUE,
    descripcion  TEXT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  REPORTE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reporte (
    idReporte           INT AUTO_INCREMENT PRIMARY KEY,
    titulo              VARCHAR(200) NOT NULL,
    descripcion         TEXT,
    estado              ENUM('recibido','en_proceso','resuelto','rechazado')
                        NOT NULL DEFAULT 'recibido',
    esAnonimo           TINYINT(1) NOT NULL DEFAULT 0,
    voto                INT        NOT NULL DEFAULT 0,
    categoria           VARCHAR(50) NOT NULL,
    fechaCreacion       DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechaActualizacion  DATETIME   DEFAULT NULL,
    fechaCierre         DATETIME   DEFAULT NULL,
    idUsuario           INT        DEFAULT NULL,
    idUbicacion         INT        DEFAULT NULL,
    FOREIGN KEY (idUsuario)   REFERENCES usuario(idUsuario)   ON DELETE SET NULL,
    FOREIGN KEY (idUbicacion) REFERENCES ubicacion(idUbicacion) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  TICKET
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ticket (
    idTicket        INT AUTO_INCREMENT PRIMARY KEY,
    prioridad       ENUM('baja','media','alta','critica') NOT NULL DEFAULT 'media',
    estado          ENUM('abierto','en_proceso','cerrado') NOT NULL DEFAULT 'abierto',
    fechaAsignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fechaResolucion DATETIME DEFAULT NULL,
    idReporte       INT NOT NULL,
    FOREIGN KEY (idReporte) REFERENCES reporte(idReporte) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  EVIDENCIA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evidencia (
    idEvidencia INT AUTO_INCREMENT PRIMARY KEY,
    urlArchivo  VARCHAR(500) NOT NULL,
    tamanoKb    INT,
    contenido   VARCHAR(50),
    idReporte   INT NOT NULL,
    FOREIGN KEY (idReporte) REFERENCES reporte(idReporte) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  COMENTARIO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS comentario (
    idComentario    INT AUTO_INCREMENT PRIMARY KEY,
    contenido       TEXT NOT NULL,
    fechaComentario DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    idReporte       INT NOT NULL,
    idUsuario       INT DEFAULT NULL,
    FOREIGN KEY (idReporte) REFERENCES reporte(idReporte) ON DELETE CASCADE,
    FOREIGN KEY (idUsuario) REFERENCES usuario(idUsuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  ALERTA LOCAL
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS alerta_local (
    idAlerta        INT AUTO_INCREMENT PRIMARY KEY,
    frecuencia_alerta VARCHAR(50),
    rango_km        DECIMAL(5,2),
    idUbicacion     INT DEFAULT NULL,
    idUsuario       INT DEFAULT NULL,
    FOREIGN KEY (idUbicacion) REFERENCES ubicacion(idUbicacion) ON DELETE SET NULL,
    FOREIGN KEY (idUsuario)   REFERENCES usuario(idUsuario)   ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  VOTAR (tabla relacional N:N usuario-reporte)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS votar (
    idUsuario INT NOT NULL,
    idReporte INT NOT NULL,
    PRIMARY KEY (idUsuario, idReporte),
    FOREIGN KEY (idUsuario) REFERENCES usuario(idUsuario) ON DELETE CASCADE,
    FOREIGN KEY (idReporte) REFERENCES reporte(idReporte) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--  DATOS DE PRUEBA
-- ============================================================

INSERT INTO ubicacion (departamento, ciudad, barrio, direccionTexto, latitud, longitud) VALUES
('Quindío', 'Armenia', 'El Bosque',     'Calle 10 # 15-30', 4.5339, -75.6811),
('Quindío', 'Armenia', 'La Castellana', 'Carrera 19 # 8-45',4.5362, -75.6743),
('Quindío', 'Armenia', 'Centro',        'Calle 20 # 14-20', 4.5389, -75.6797),
('Quindío', 'Armenia', 'Tres Esquinas', 'Carrera 25 # 5-10',4.5421, -75.6852),
('Quindío', 'Armenia', 'Villa Restrepo','Diagonal 30 # 12-55',4.5298,-75.6889);

INSERT INTO usuario (nombres, apellido_1, apellido_2, email, contrasena, telefono, edad, tipoRegistro, cantidadReportes) VALUES
('Carlos',    'Gómez',     'Ruiz',    'carlos@email.com',    MD5('pass1'), '3101234567', 28, 'local',    3),
('María',     'López',     'Torres',  'maria@email.com',     MD5('pass2'), '3157654321', 34, 'google',   1),
('Juan',      'Martínez',  'Vera',    'juan@email.com',      MD5('pass3'), '3204567890', 22, 'local',    2),
('Ana',       'Rodríguez', 'Paz',     'ana@email.com',       MD5('pass4'), '3009876543', 45, 'facebook', 1),
('Luis',      'Pérez',     'Mora',    'luis@email.com',      MD5('pass5'), '3125551234', 31, 'local',    1),
('Sofía',     'Herrera',   'Ríos',    'sofia@email.com',     MD5('pass6'), '3187772345', 27, 'google',   2),
('Diego',     'Castro',    'Fuentes', 'diego@email.com',     MD5('pass7'), '3013334456', 39, 'local',    1),
('Valentina', 'Moreno',    'Silva',   'valentina@email.com', MD5('pass8'), '3228889901', 25, 'local',    1);

INSERT INTO categoria (nombre, descripcion) VALUES
('hueco',        'Daño en el pavimento o calzada'),
('señalizacion', 'Problemas con señales de tránsito'),
('anden',        'Daños en andenes o aceras'),
('mal_parqueo',  'Vehículos estacionados incorrectamente'),
('semaforo',     'Fallas en semáforos'),
('alumbrado',    'Problemas con el alumbrado público'),
('otro',         'Otro tipo de problema vial');

INSERT INTO proveedor (nombreEntidad, telefono, correo, nivel, solucionesResueltas) VALUES
('Secretaría de Obras Públicas', '67400000', 'obras@armenia.gov.co',  'municipal', 145),
('Empresas Públicas Armenia',    '67411111', 'epa@armenia.gov.co',    'municipal',  89),
('Tránsito y Transporte',        '67422222', 'transito@armenia.gov.co','municipal', 67);

INSERT INTO reporte (titulo, descripcion, estado, esAnonimo, voto, categoria, idUsuario, idUbicacion) VALUES
('Hueco peligroso frente al parque',   'Hueco de ~50cm, peligroso para motos.',         'recibido',   0, 12, 'hueco',        1, 1),
('Semáforo dañado en intersección',    'Lleva 3 días sin funcionar, causa trancones.',   'en_proceso', 0,  8, 'semaforo',     2, 2),
('Andén bloqueado por escombros',      'Escombros bloquean el paso de peatones.',        'recibido',   0,  5, 'anden',        3, 3),
('Alumbrado público apagado',          'Toda la cuadra sin luz desde hace una semana.', 'resuelto',   0, 20, 'alumbrado',    4, 4),
('Señal de stop caída',                'La señal está tirada en el suelo.',              'en_proceso', 0,  7, 'señalizacion', 5, 5),
('Vehículo obstruye vía principal',    'Camión en zona prohibida bloquea carril.',       'rechazado',  0,  3, 'mal_parqueo',  6, 1),
('Hueco profundo en vía principal',    'Hueco de más de 1m de diámetro.',                'recibido',   0, 25, 'hueco',        7, 2),
('Poste de luz inclinado',             'Poste con riesgo de caída sobre la acera.',      'en_proceso', 0, 15, 'alumbrado',    8, 3),
('Reporte anónimo - hueco centro',     'Hueco grande sin señalizar.',                    'recibido',   1,  4, 'hueco',     NULL, 3),
('Alcantarilla sin tapa',              'Peligro para peatones en la noche.',             'recibido',   0, 18, 'otro',         2, 4);
