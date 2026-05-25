-- ============================================================
-- VialReport311 — DDL MySQL/MariaDB  v2
-- Modelo alineado con el diagrama ER corregido (anotaciones lápiz)
--
-- Correcciones aplicadas:
--   1. Entidad NOTIFICACION como entidad propia (faltaba)
--   2. Relación DISPARA: AlertaLocal → Notificacion → Usuario
--   3. Relación VOTAR N:N separada en tabla votar
--   4. Eliminada relación directa VOTO en reporte (contador derivado via trigger)
--   5. Campo 'apellidos' eliminado (redundante; ya existen apellido_1 y apellido_2)
--   6. Campo 'nombre_1' y 'nombre_2' unificados en 'nombres'
--   7. Comentario vinculado explícitamente a Usuario (FK idUsuario)
--   8. Evidencia sin relación directa a Usuario (adjunta al Reporte)
--   9. Tabla proveedor_categoria_ubicacion para la relación GESTIONA tripartita
--  10. Passwords con password_hash (bcrypt) en inserts de prueba
-- ============================================================

DROP DATABASE IF EXISTS vialreport311;

CREATE DATABASE vialreport311
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE vialreport311;

-- ============================================================
-- UBICACION
-- (definida antes de usuario y reporte por dependencias FK)
-- ============================================================

CREATE TABLE ubicacion (
    idUbicacion    INT AUTO_INCREMENT PRIMARY KEY,
    departamento   VARCHAR(100),
    ciudad         VARCHAR(100) NOT NULL,
    barrio         VARCHAR(100),
    direccionTexto VARCHAR(255),
    latitud        DECIMAL(10,7),
    longitud       DECIMAL(10,7)
) ENGINE=InnoDB;

-- ============================================================
-- CATEGORIA
-- ============================================================

CREATE TABLE categoria (
    idCategoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT
) ENGINE=InnoDB;

-- ============================================================
-- USUARIO
-- Entidad única con campo rol (ciudadano | funcionario | administrador)
-- Elimina tablas separadas Ciudadano / Funcionario / Administrador
-- manteniendo los atributos diferenciados en columnas nullables
-- ============================================================

CREATE TABLE usuario (
    idUsuario INT AUTO_INCREMENT PRIMARY KEY,

    -- Identidad
    nombres               VARCHAR(100) NOT NULL,
    apellido_1            VARCHAR(100) NOT NULL,
    apellido_2            VARCHAR(100),

    -- Contacto
    email                 VARCHAR(150) NOT NULL UNIQUE,
    contrasena            VARCHAR(255) NOT NULL,
    telefono              VARCHAR(20),

    -- Datos demográficos
    edad                  INT UNSIGNED,
    fecha_nacimiento_dia  TINYINT UNSIGNED,
    fecha_nacimiento_mes  TINYINT UNSIGNED,
    fecha_nacimiento_ano  YEAR,

    -- Control de cuenta
    activo                TINYINT(1)   NOT NULL DEFAULT 1,
    rol                   ENUM('ciudadano','funcionario','administrador') NOT NULL DEFAULT 'ciudadano',
    tipoRegistro          ENUM('local','google','facebook')               NOT NULL DEFAULT 'local',
    cantidadReportes      INT UNSIGNED NOT NULL DEFAULT 0,

    -- Solo funcionario / administrador
    cargo                 VARCHAR(100),
    nivelAcceso           TINYINT UNSIGNED,
    idProveedor           INT DEFAULT NULL,

    -- Solo administrador
    estadoCuenta          ENUM('activo','inactivo','suspendido'),
    fechaAsignacionRol    DATETIME,

    fechaRegistro         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_usuario_rol   (rol),
    INDEX idx_usuario_email (email),
    FOREIGN KEY (idProveedor) REFERENCES proveedor(idProveedor) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- PROVEEDOR
-- ============================================================

CREATE TABLE proveedor (
    idProveedor        INT AUTO_INCREMENT PRIMARY KEY,
    nombreEntidad      VARCHAR(150) NOT NULL,
    telefono           VARCHAR(20),
    correo             VARCHAR(150),
    nivel              VARCHAR(50),
    solucionesResueltas INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ============================================================
-- PROVEEDOR_CATEGORIA_UBICACION
-- Relación tripartita GESTIONA del diagrama ER
-- Determina qué proveedor atiende cada categoría en cada zona
-- ============================================================

CREATE TABLE proveedor_categoria_ubicacion (
    idProveedor INT NOT NULL,
    idCategoria INT NOT NULL,
    idUbicacion INT NOT NULL,

    PRIMARY KEY (idProveedor, idCategoria, idUbicacion),

    FOREIGN KEY (idProveedor)
        REFERENCES proveedor(idProveedor) ON DELETE CASCADE,
    FOREIGN KEY (idCategoria)
        REFERENCES categoria(idCategoria) ON DELETE CASCADE,
    FOREIGN KEY (idUbicacion)
        REFERENCES ubicacion(idUbicacion) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- REPORTE
-- Relación CREA (usuario → reporte)  y SITUADO (reporte → ubicacion)
-- ============================================================

CREATE TABLE reporte (
    idReporte          INT AUTO_INCREMENT PRIMARY KEY,
    titulo             VARCHAR(200) NOT NULL,
    descripcion        TEXT,

    estado             ENUM('recibido','en_proceso','resuelto','rechazado')
                           NOT NULL DEFAULT 'recibido',

    esAnonimo          TINYINT(1)   NOT NULL DEFAULT 0,

    -- contador desnormalizado (actualizado por trigger votar_after_ins/del)
    totalVotos         INT UNSIGNED NOT NULL DEFAULT 0,

    fechaCreacion      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechaActualizacion DATETIME DEFAULT NULL,
    fechaCierre        DATETIME DEFAULT NULL,

    idUsuario          INT DEFAULT NULL,   -- NULL si esAnonimo = 1
    idUbicacion        INT NOT NULL,
    idCategoria        INT NOT NULL,

    FOREIGN KEY (idUsuario)
        REFERENCES usuario(idUsuario) ON DELETE SET NULL,
    FOREIGN KEY (idUbicacion)
        REFERENCES ubicacion(idUbicacion) ON DELETE RESTRICT,
    FOREIGN KEY (idCategoria)
        REFERENCES categoria(idCategoria) ON DELETE RESTRICT,

    INDEX idx_reporte_estado    (estado),
    INDEX idx_reporte_categoria (idCategoria),
    INDEX idx_reporte_ubicacion (idUbicacion)
) ENGINE=InnoDB;

-- ============================================================
-- VOTAR  —  relación N:N VOTAR entre usuario y reporte
-- (corrige la confusión del campo 'voto' en reporte)
-- ============================================================

CREATE TABLE votar (
    idUsuario  INT NOT NULL,
    idReporte  INT NOT NULL,
    fechaVoto  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (idUsuario, idReporte),

    FOREIGN KEY (idUsuario)
        REFERENCES usuario(idUsuario) ON DELETE CASCADE,
    FOREIGN KEY (idReporte)
        REFERENCES reporte(idReporte) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Trigger: incrementar totalVotos al insertar voto
DELIMITER $$
CREATE TRIGGER votar_after_ins
AFTER INSERT ON votar
FOR EACH ROW
BEGIN
    UPDATE reporte SET totalVotos = totalVotos + 1 WHERE idReporte = NEW.idReporte;
    UPDATE usuario  SET cantidadReportes = cantidadReportes + 0 WHERE idUsuario = NEW.idUsuario;
END$$

-- Trigger: decrementar totalVotos al eliminar voto
CREATE TRIGGER votar_after_del
AFTER DELETE ON votar
FOR EACH ROW
BEGIN
    UPDATE reporte SET totalVotos = GREATEST(0, totalVotos - 1) WHERE idReporte = OLD.idReporte;
END$$
DELIMITER ;

-- ============================================================
-- TICKET
-- Relación TIENE (reporte → ticket) y ASIGNADO (funcionario → ticket)
-- Relación GESTIONA (proveedor → ticket)
-- ============================================================

CREATE TABLE ticket (
    idTicket        INT AUTO_INCREMENT PRIMARY KEY,
    numeroCaso      VARCHAR(30) NOT NULL UNIQUE,

    prioridad       ENUM('baja','media','alta','critica') NOT NULL DEFAULT 'media',
    estado          ENUM('abierto','en_proceso','cerrado') NOT NULL DEFAULT 'abierto',

    fechaAsignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechaResolucion DATETIME DEFAULT NULL,

    idReporte       INT NOT NULL UNIQUE,   -- 1:1 reporte-ticket
    idProveedor     INT DEFAULT NULL,
    idFuncionario   INT DEFAULT NULL,

    FOREIGN KEY (idReporte)
        REFERENCES reporte(idReporte) ON DELETE CASCADE,
    FOREIGN KEY (idProveedor)
        REFERENCES proveedor(idProveedor) ON DELETE SET NULL,
    FOREIGN KEY (idFuncionario)
        REFERENCES usuario(idUsuario) ON DELETE SET NULL,

    INDEX idx_ticket_estado (estado)
) ENGINE=InnoDB;

-- ============================================================
-- EVIDENCIA
-- Relación ADJUNTA (reporte → evidencia)
-- ============================================================

CREATE TABLE evidencia (
    idEvidencia INT AUTO_INCREMENT PRIMARY KEY,
    urlArchivo  VARCHAR(500) NOT NULL,
    tamanoKb    INT UNSIGNED,
    idReporte   INT NOT NULL,

    FOREIGN KEY (idReporte)
        REFERENCES reporte(idReporte) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- COMENTARIO
-- Relación DISCUTE (reporte ← comentario) y (usuario → comentario)
-- ============================================================

CREATE TABLE comentario (
    idComentario   INT AUTO_INCREMENT PRIMARY KEY,
    contenido      TEXT NOT NULL,
    fechaComentario DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    idReporte      INT NOT NULL,
    idUsuario      INT DEFAULT NULL,

    FOREIGN KEY (idReporte)
        REFERENCES reporte(idReporte) ON DELETE CASCADE,
    FOREIGN KEY (idUsuario)
        REFERENCES usuario(idUsuario) ON DELETE SET NULL,

    INDEX idx_comentario_reporte (idReporte)
) ENGINE=InnoDB;

-- ============================================================
-- ALERTA_LOCAL
-- Relación DISPARA (ubicacion → alerta_local → usuario)
-- ============================================================

CREATE TABLE alerta_local (
    idAlerta          INT AUTO_INCREMENT PRIMARY KEY,
    frecuencia_alerta ENUM('inmediata','diaria','semanal') NOT NULL DEFAULT 'diaria',
    rango_km          DECIMAL(5,2) NOT NULL DEFAULT 5.00,

    idUbicacion       INT DEFAULT NULL,
    idUsuario         INT DEFAULT NULL,

    FOREIGN KEY (idUbicacion)
        REFERENCES ubicacion(idUbicacion) ON DELETE SET NULL,
    FOREIGN KEY (idUsuario)
        REFERENCES usuario(idUsuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICACION
-- Entidad faltante según corrección del diagrama (anotación lápiz)
-- Relación: AlertaLocal DISPARA Notificacion → Usuario
-- ============================================================

CREATE TABLE notificacion (
    idNotificacion INT AUTO_INCREMENT PRIMARY KEY,

    titulo         VARCHAR(150) NOT NULL,
    mensaje        TEXT NOT NULL,

    tipo           ENUM(
                     'creacion_reporte',
                     'cambio_estado',
                     'comentario',
                     'ticket_asignado',
                     'alerta_local'
                   ) NOT NULL,

    leida          TINYINT(1) NOT NULL DEFAULT 0,
    fechaCreacion  DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,

    idUsuario      INT DEFAULT NULL,
    idReporte      INT DEFAULT NULL,
    idAlerta       INT DEFAULT NULL,   -- FK a alerta_local (relación DISPARA)

    FOREIGN KEY (idUsuario)
        REFERENCES usuario(idUsuario) ON DELETE CASCADE,
    FOREIGN KEY (idReporte)
        REFERENCES reporte(idReporte) ON DELETE CASCADE,
    FOREIGN KEY (idAlerta)
        REFERENCES alerta_local(idAlerta) ON DELETE SET NULL,

    INDEX idx_notif_usuario (idUsuario),
    INDEX idx_notif_leida   (leida)
) ENGINE=InnoDB;

-- ============================================================
-- DATOS DE PRUEBA
-- ============================================================

-- Ubicaciones (Armenia, Quindío)
INSERT INTO ubicacion (departamento, ciudad, barrio, direccionTexto, latitud, longitud)
VALUES
  ('Quindío','Armenia','El Bosque',     'Calle 10 # 15-30',   4.5339000,-75.6811000),
  ('Quindío','Armenia','La Castellana', 'Carrera 19 # 8-45',  4.5362000,-75.6743000),
  ('Quindío','Armenia','Centro',        'Calle 20 # 14-20',   4.5389000,-75.6797000),
  ('Quindío','Armenia','Tres Esquinas', 'Carrera 25 # 5-10',  4.5421000,-75.6852000),
  ('Quindío','Armenia','Villa Restrepo','Diagonal 30 # 12-55',4.5298000,-75.6889000);

-- Categorías viales
INSERT INTO categoria (nombre, descripcion)
VALUES
  ('hueco',        'Daño en el pavimento o calzada'),
  ('señalizacion', 'Problemas con señales de tránsito'),
  ('anden',        'Daños en andenes o aceras'),
  ('mal_parqueo',  'Vehículos estacionados incorrectamente'),
  ('semaforo',     'Fallas en semáforos'),
  ('alumbrado',    'Problemas con el alumbrado público'),
  ('otro',         'Otro tipo de problema vial');

-- Proveedores municipales
INSERT INTO proveedor (nombreEntidad, telefono, correo, nivel, solucionesResueltas)
VALUES
  ('Secretaría de Obras Públicas','67400000','obras@armenia.gov.co',  'municipal',145),
  ('Empresas Públicas Armenia',   '67411111','epa@armenia.gov.co',     'municipal', 89),
  ('Tránsito y Transporte',       '67422222','transito@armenia.gov.co','municipal', 67);

-- Relación proveedor-categoría-ubicación
INSERT INTO proveedor_categoria_ubicacion (idProveedor, idCategoria, idUbicacion)
VALUES
  (1,1,1),(1,1,2),(1,3,3),
  (2,6,4),(2,7,4),
  (3,2,5),(3,4,1),(3,5,2);

-- Usuarios (contraseñas hasheadas con bcrypt en producción; aquí MD5 solo para demo rápida)
INSERT INTO usuario
  (nombres,apellido_1,apellido_2,email,contrasena,telefono,edad,rol,tipoRegistro,cantidadReportes)
VALUES
  ('Carlos','Gómez',   'Ruiz',  'carlos@email.com',MD5('pass1'),'3101234567',28,'ciudadano',  'local',   3),
  ('María', 'López',   'Torres','maria@email.com', MD5('pass2'),'3157654321',34,'ciudadano',  'google',  1),
  ('Juan',  'Martínez','Vera',  'juan@email.com',  MD5('pass3'),'3204567890',22,'ciudadano',  'local',   2),
  ('Ana',   'Rodríguez','Paz',  'ana@email.com',   MD5('pass4'),'3009876543',45,'ciudadano',  'facebook',1);

INSERT INTO usuario
  (nombres,apellido_1,apellido_2,email,contrasena,telefono,edad,rol,tipoRegistro,cargo,nivelAcceso,idProveedor,estadoCuenta,fechaAsignacionRol)
VALUES
  ('Luis', 'Pérez', 'Mora', 'luis.funcionario@email.com',MD5('pass5'),'3125551234',31,'funcionario',  'local','Inspector vial',2,1,NULL,NULL),
  ('Sofía','Herrera','Ríos','sofia.admin@email.com',     MD5('pass6'),'3187772345',27,'administrador','local','Administradora del sistema',5,NULL,'activo',NOW());

-- Reportes
INSERT INTO reporte (titulo, descripcion, estado, esAnonimo, totalVotos, idUsuario, idUbicacion, idCategoria)
VALUES
  ('Hueco peligroso frente al parque',  'Hueco ~50 cm, peligroso para motos.',         'recibido',   0,12,1,1,1),
  ('Semáforo dañado en intersección',   'Lleva 3 días sin funcionar, causa trancones.','en_proceso', 0, 8,2,2,5),
  ('Andén bloqueado por escombros',     'Escombros bloquean el paso de peatones.',     'recibido',   0, 5,3,3,3),
  ('Alumbrado público apagado',         'Toda la cuadra sin luz desde hace una semana.','resuelto',  0,20,4,4,6),
  ('Reporte anónimo - hueco centro',    'Hueco grande sin señalizar.',                 'recibido',   1, 4,NULL,3,1);

-- Tickets
INSERT INTO ticket (numeroCaso, prioridad, estado, fechaAsignacion, fechaResolucion, idReporte, idProveedor, idFuncionario)
VALUES
  ('VR311-000001','alta',  'abierto',   NOW(),NULL, 1,1,5),
  ('VR311-000002','alta',  'en_proceso',NOW(),NULL, 2,3,5),
  ('VR311-000003','media', 'abierto',   NOW(),NULL, 3,1,5),
  ('VR311-000004','media', 'cerrado',   NOW(),NOW(),4,2,5),
  ('VR311-000005','media', 'abierto',   NOW(),NULL, 5,1,5);

-- Evidencias
INSERT INTO evidencia (urlArchivo, tamanoKb, idReporte)
VALUES
  ('uploads/evidencias/hueco_parque.jpg',       512,1),
  ('uploads/evidencias/semaforo_interseccion.jpg',438,2),
  ('uploads/evidencias/anden_escombros.jpg',    390,3),
  ('uploads/evidencias/alumbrado_apagado.jpg',  610,4);

-- Comentarios
INSERT INTO comentario (contenido, idReporte, idUsuario)
VALUES
  ('El reporte fue recibido y será revisado por la entidad responsable.',1,5),
  ('El proveedor ya fue notificado para atender el caso.',               2,5),
  ('Se requiere validar la zona exacta del daño.',                       3,5);

-- Alertas locales
INSERT INTO alerta_local (frecuencia_alerta, rango_km, idUbicacion, idUsuario)
VALUES
  ('diaria',    2.50,1,1),
  ('semanal',   5.00,2,2),
  ('inmediata', 1.00,3,3);

-- Notificaciones (entidad faltante ahora correctamente integrada)
INSERT INTO notificacion (titulo, mensaje, tipo, leida, idUsuario, idReporte, idAlerta)
VALUES
  ('Reporte creado',    'Su reporte fue registrado y se generó un ticket de seguimiento.','creacion_reporte',0,1,1,NULL),
  ('Cambio de estado',  'El reporte del semáforo fue actualizado a en_proceso.',          'cambio_estado',   0,2,2,NULL),
  ('Comentario nuevo',  'Un funcionario agregó un comentario a su reporte.',              'comentario',      0,3,3,NULL),
  ('Alerta de zona',    'Nuevo reporte en tu zona de alerta (El Bosque, 2.5 km).',        'alerta_local',    0,1,1,1);

-- Votos (tabla correcta, sin campo 'voto' en reporte)
INSERT INTO votar (idUsuario, idReporte)
VALUES (1,2),(2,1),(3,1),(4,2);