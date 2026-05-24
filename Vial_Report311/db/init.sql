-- ============================================================
-- VialReport311 — DDL MySQL/MariaDB
-- Modelo alineado con el diagrama ER corregido
-- ============================================================

DROP DATABASE IF EXISTS vialreport311;

CREATE DATABASE vialreport311
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE vialreport311;

-- ============================================================
-- USUARIO
-- Entidad general para ciudadano, funcionario y administrador
-- ============================================================

CREATE TABLE usuario (
    idUsuario INT AUTO_INCREMENT PRIMARY KEY,

    nombres VARCHAR(100) NOT NULL,
    apellido_1 VARCHAR(100) NOT NULL,
    apellido_2 VARCHAR(100),

    email VARCHAR(150) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),

    edad INT,
    fecha_nacimiento_dia TINYINT,
    fecha_nacimiento_mes TINYINT,
    fecha_nacimiento_ano YEAR,

    activo TINYINT(1) NOT NULL DEFAULT 1,

    rol ENUM('ciudadano', 'funcionario', 'administrador') NOT NULL DEFAULT 'ciudadano',

    tipoRegistro ENUM('local', 'google', 'facebook') NOT NULL DEFAULT 'local',
    cantidadReportes INT NOT NULL DEFAULT 0,

    -- Datos propios de funcionario
    cargo VARCHAR(100),
    nivelAcceso TINYINT,

    -- Datos propios de administrador
    estadoCuenta ENUM('activo', 'inactivo', 'suspendido'),
    fechaAsignacionRol DATETIME,

    fechaRegistro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- UBICACION
-- ============================================================

CREATE TABLE ubicacion (
    idUbicacion INT AUTO_INCREMENT PRIMARY KEY,

    departamento VARCHAR(100),
    ciudad VARCHAR(100) NOT NULL,
    barrio VARCHAR(100),
    direccionTexto VARCHAR(255),

    latitud DECIMAL(10,7),
    longitud DECIMAL(10,7)
) ENGINE=InnoDB;

-- ============================================================
-- CATEGORIA
-- ============================================================

CREATE TABLE categoria (
    idCategoria INT AUTO_INCREMENT PRIMARY KEY,

    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT
) ENGINE=InnoDB;

-- ============================================================
-- PROVEEDOR
-- Entidad responsable de atender reportes según categoría/zona
-- ============================================================

CREATE TABLE proveedor (
    idProveedor INT AUTO_INCREMENT PRIMARY KEY,

    nombreEntidad VARCHAR(150) NOT NULL,
    telefono VARCHAR(20),
    correo VARCHAR(150),
    nivel VARCHAR(50),

    solucionesResueltas INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ============================================================
-- PROVEEDOR_CATEGORIA_UBICACION
-- Permite saber qué proveedor atiende una categoría en una zona
-- ============================================================

CREATE TABLE proveedor_categoria_ubicacion (
    idProveedor INT NOT NULL,
    idCategoria INT NOT NULL,
    idUbicacion INT NOT NULL,

    PRIMARY KEY (idProveedor, idCategoria, idUbicacion),

    FOREIGN KEY (idProveedor)
        REFERENCES proveedor(idProveedor)
        ON DELETE CASCADE,

    FOREIGN KEY (idCategoria)
        REFERENCES categoria(idCategoria)
        ON DELETE CASCADE,

    FOREIGN KEY (idUbicacion)
        REFERENCES ubicacion(idUbicacion)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- REPORTE
-- ============================================================

CREATE TABLE reporte (
    idReporte INT AUTO_INCREMENT PRIMARY KEY,

    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,

    estado ENUM('recibido', 'en_proceso', 'resuelto', 'rechazado')
        NOT NULL DEFAULT 'recibido',

    esAnonimo TINYINT(1) NOT NULL DEFAULT 0,
    voto INT NOT NULL DEFAULT 0,

    fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechaActualizacion DATETIME DEFAULT NULL,
    fechaCierre DATETIME DEFAULT NULL,

    idUsuario INT DEFAULT NULL,
    idUbicacion INT NOT NULL,
    idCategoria INT NOT NULL,

    FOREIGN KEY (idUsuario)
        REFERENCES usuario(idUsuario)
        ON DELETE SET NULL,

    FOREIGN KEY (idUbicacion)
        REFERENCES ubicacion(idUbicacion)
        ON DELETE RESTRICT,

    FOREIGN KEY (idCategoria)
        REFERENCES categoria(idCategoria)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- TICKET
-- Se genera para hacer seguimiento al reporte
-- ============================================================

CREATE TABLE ticket (
    idTicket INT AUTO_INCREMENT PRIMARY KEY,

    numeroCaso VARCHAR(30) NOT NULL UNIQUE,

    prioridad ENUM('baja', 'media', 'alta', 'critica')
        NOT NULL DEFAULT 'media',

    estado ENUM('abierto', 'en_proceso', 'cerrado')
        NOT NULL DEFAULT 'abierto',

    fechaAsignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechaResolucion DATETIME DEFAULT NULL,

    idReporte INT NOT NULL,
    idProveedor INT DEFAULT NULL,
    idFuncionario INT DEFAULT NULL,

    FOREIGN KEY (idReporte)
        REFERENCES reporte(idReporte)
        ON DELETE CASCADE,

    FOREIGN KEY (idProveedor)
        REFERENCES proveedor(idProveedor)
        ON DELETE SET NULL,

    FOREIGN KEY (idFuncionario)
        REFERENCES usuario(idUsuario)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- EVIDENCIA
-- ============================================================

CREATE TABLE evidencia (
    idEvidencia INT AUTO_INCREMENT PRIMARY KEY,

    urlArchivo VARCHAR(500) NOT NULL,
    tamanoKb INT,
    contenido VARCHAR(100),

    idReporte INT NOT NULL,

    FOREIGN KEY (idReporte)
        REFERENCES reporte(idReporte)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- COMENTARIO
-- ============================================================

CREATE TABLE comentario (
    idComentario INT AUTO_INCREMENT PRIMARY KEY,

    contenido TEXT NOT NULL,
    fechaComentario DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    idReporte INT NOT NULL,
    idUsuario INT DEFAULT NULL,

    FOREIGN KEY (idReporte)
        REFERENCES reporte(idReporte)
        ON DELETE CASCADE,

    FOREIGN KEY (idUsuario)
        REFERENCES usuario(idUsuario)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- ALERTA LOCAL
-- ============================================================

CREATE TABLE alerta_local (
    idAlerta INT AUTO_INCREMENT PRIMARY KEY,

    frecuencia_alerta VARCHAR(50),
    rango_km DECIMAL(5,2),

    idUbicacion INT DEFAULT NULL,
    idUsuario INT DEFAULT NULL,

    FOREIGN KEY (idUbicacion)
        REFERENCES ubicacion(idUbicacion)
        ON DELETE SET NULL,

    FOREIGN KEY (idUsuario)
        REFERENCES usuario(idUsuario)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICACION
-- Entidad faltante según la corrección del diagrama
-- ============================================================

CREATE TABLE notificacion (
    idNotificacion INT AUTO_INCREMENT PRIMARY KEY,

    titulo VARCHAR(150) NOT NULL,
    mensaje TEXT NOT NULL,

    tipo ENUM('creacion_reporte', 'cambio_estado', 'comentario', 'ticket_asignado', 'alerta_local')
        NOT NULL,

    leida TINYINT(1) NOT NULL DEFAULT 0,
    fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    idUsuario INT DEFAULT NULL,
    idReporte INT DEFAULT NULL,

    FOREIGN KEY (idUsuario)
        REFERENCES usuario(idUsuario)
        ON DELETE CASCADE,

    FOREIGN KEY (idReporte)
        REFERENCES reporte(idReporte)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- VOTAR
-- Relación N:N entre usuario y reporte
-- ============================================================

CREATE TABLE votar (
    idUsuario INT NOT NULL,
    idReporte INT NOT NULL,

    fechaVoto DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (idUsuario, idReporte),

    FOREIGN KEY (idUsuario)
        REFERENCES usuario(idUsuario)
        ON DELETE CASCADE,

    FOREIGN KEY (idReporte)
        REFERENCES reporte(idReporte)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DATOS DE PRUEBA
-- ============================================================

INSERT INTO ubicacion
(departamento, ciudad, barrio, direccionTexto, latitud, longitud)
VALUES
('Quindío', 'Armenia', 'El Bosque', 'Calle 10 # 15-30', 4.5339000, -75.6811000),
('Quindío', 'Armenia', 'La Castellana', 'Carrera 19 # 8-45', 4.5362000, -75.6743000),
('Quindío', 'Armenia', 'Centro', 'Calle 20 # 14-20', 4.5389000, -75.6797000),
('Quindío', 'Armenia', 'Tres Esquinas', 'Carrera 25 # 5-10', 4.5421000, -75.6852000),
('Quindío', 'Armenia', 'Villa Restrepo', 'Diagonal 30 # 12-55', 4.5298000, -75.6889000);

INSERT INTO categoria
(nombre, descripcion)
VALUES
('hueco', 'Daño en el pavimento o calzada'),
('señalizacion', 'Problemas con señales de tránsito'),
('anden', 'Daños en andenes o aceras'),
('mal_parqueo', 'Vehículos estacionados incorrectamente'),
('semaforo', 'Fallas en semáforos'),
('alumbrado', 'Problemas con el alumbrado público'),
('otro', 'Otro tipo de problema vial');

INSERT INTO proveedor
(nombreEntidad, telefono, correo, nivel, solucionesResueltas)
VALUES
('Secretaría de Obras Públicas', '67400000', 'obras@armenia.gov.co', 'municipal', 145),
('Empresas Públicas Armenia', '67411111', 'epa@armenia.gov.co', 'municipal', 89),
('Tránsito y Transporte', '67422222', 'transito@armenia.gov.co', 'municipal', 67);

INSERT INTO usuario
(nombres, apellido_1, apellido_2, email, contrasena, telefono, edad, rol, tipoRegistro, cantidadReportes)
VALUES
('Carlos', 'Gómez', 'Ruiz', 'carlos@email.com', MD5('pass1'), '3101234567', 28, 'ciudadano', 'local', 3),
('María', 'López', 'Torres', 'maria@email.com', MD5('pass2'), '3157654321', 34, 'ciudadano', 'google', 1),
('Juan', 'Martínez', 'Vera', 'juan@email.com', MD5('pass3'), '3204567890', 22, 'ciudadano', 'local', 2),
('Ana', 'Rodríguez', 'Paz', 'ana@email.com', MD5('pass4'), '3009876543', 45, 'ciudadano', 'facebook', 1);

INSERT INTO usuario
(nombres, apellido_1, apellido_2, email, contrasena, telefono, edad, rol, tipoRegistro, cargo, nivelAcceso)
VALUES
('Luis', 'Pérez', 'Mora', 'luis.funcionario@email.com', MD5('pass5'), '3125551234', 31, 'funcionario', 'local', 'Inspector vial', 2),
('Sofía', 'Herrera', 'Ríos', 'sofia.admin@email.com', MD5('pass6'), '3187772345', 27, 'administrador', 'local', 'Administradora del sistema', 5);

UPDATE usuario
SET estadoCuenta = 'activo',
    fechaAsignacionRol = NOW()
WHERE rol = 'administrador';

-- Relación proveedor-categoría-ubicación

INSERT INTO proveedor_categoria_ubicacion
(idProveedor, idCategoria, idUbicacion)
VALUES
(1, 1, 1),
(1, 1, 2),
(1, 3, 3),
(2, 6, 4),
(2, 7, 4),
(3, 2, 5),
(3, 4, 1),
(3, 5, 2);

-- Reportes

INSERT INTO reporte
(titulo, descripcion, estado, esAnonimo, voto, idUsuario, idUbicacion, idCategoria)
VALUES
('Hueco peligroso frente al parque', 'Hueco de aproximadamente 50 cm, peligroso para motos.', 'recibido', 0, 12, 1, 1, 1),
('Semáforo dañado en intersección', 'Lleva 3 días sin funcionar y causa trancones.', 'en_proceso', 0, 8, 2, 2, 5),
('Andén bloqueado por escombros', 'Escombros bloquean el paso de peatones.', 'recibido', 0, 5, 3, 3, 3),
('Alumbrado público apagado', 'Toda la cuadra sin luz desde hace una semana.', 'resuelto', 0, 20, 4, 4, 6),
('Reporte anónimo - hueco centro', 'Hueco grande sin señalizar.', 'recibido', 1, 4, NULL, 3, 1);

-- Tickets asociados

INSERT INTO ticket
(numeroCaso, prioridad, estado, fechaAsignacion, fechaResolucion, idReporte, idProveedor, idFuncionario)
VALUES
('VR311-000001', 'alta', 'abierto', NOW(), NULL, 1, 1, 5),
('VR311-000002', 'alta', 'en_proceso', NOW(), NULL, 2, 3, 5),
('VR311-000003', 'media', 'abierto', NOW(), NULL, 3, 1, 5),
('VR311-000004', 'media', 'cerrado', NOW(), NOW(), 4, 2, 5),
('VR311-000005', 'media', 'abierto', NOW(), NULL, 5, 1, 5);

-- Evidencias

INSERT INTO evidencia
(urlArchivo, tamanoKb, contenido, idReporte)
VALUES
('uploads/evidencias/hueco_parque.jpg', 512, 'image/jpeg', 1),
('uploads/evidencias/semaforo_interseccion.jpg', 438, 'image/jpeg', 2),
('uploads/evidencias/anden_escombros.jpg', 390, 'image/jpeg', 3),
('uploads/evidencias/alumbrado_apagado.jpg', 610, 'image/jpeg', 4);

-- Comentarios

INSERT INTO comentario
(contenido, idReporte, idUsuario)
VALUES
('El reporte fue recibido y será revisado por la entidad responsable.', 1, 5),
('El proveedor ya fue notificado para atender el caso.', 2, 5),
('Se requiere validar la zona exacta del daño.', 3, 5);

-- Alertas locales

INSERT INTO alerta_local
(frecuencia_alerta, rango_km, idUbicacion, idUsuario)
VALUES
('diaria', 2.50, 1, 1),
('semanal', 5.00, 2, 2),
('inmediata', 1.00, 3, 3);

-- Notificaciones

INSERT INTO notificacion
(titulo, mensaje, tipo, leida, idUsuario, idReporte)
VALUES
('Reporte creado', 'Su reporte fue registrado correctamente y se generó un ticket de seguimiento.', 'creacion_reporte', 0, 1, 1),
('Cambio de estado', 'El reporte del semáforo fue actualizado a en proceso.', 'cambio_estado', 0, 2, 2),
('Comentario agregado', 'Un funcionario agregó un comentario a su reporte.', 'comentario', 0, 3, 3);

-- Votos

INSERT INTO votar
(idUsuario, idReporte)
VALUES
(1, 2),
(2, 1),
(3, 1),
(4, 2);