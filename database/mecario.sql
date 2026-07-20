-- ============================================================
-- MECARIO - BASE DE DATOS COMPLETA DESDE CERO
-- Sistema de inventario y venta de autopartes
-- Importar este único archivo desde phpMyAdmin.
-- No requiere que la base de datos exista previamente.
--
-- Credenciales iniciales:
--   Administrador: admin / root2514
--   Operador:      operador / root2514
--
-- El usuario Operador puede alimentar inventario y utilizar los módulos
-- operativos, pero NO posee permisos del módulo Usuarios.
-- Las cuentas Cliente se crean exclusivamente desde el registro público.
-- ============================================================

CREATE DATABASE IF NOT EXISTS mecario
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE mecario;
SET NAMES utf8mb4;
SET time_zone = '-05:00';

-- ============================================================
-- 1. SEGURIDAD, USUARIOS, ROLES Y PERMISOS
-- ============================================================

CREATE TABLE IF NOT EXISTS roles (
    id_rol INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_rol_activo CHECK (activo IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permisos (
    id_permiso INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(100) NOT NULL UNIQUE,
    modulo VARCHAR(80) NOT NULL,
    accion VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_permiso_activo CHECK (activo IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rol_permiso (
    id_rol INT UNSIGNED NOT NULL,
    id_permiso INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_rol,id_permiso),
    CONSTRAINT fk_rol_permiso_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (id_permiso) REFERENCES permisos(id_permiso)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado TINYINT(1) NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_usuario_intentos CHECK (intentos_fallidos <= 3),
    CONSTRAINT chk_usuario_activo CHECK (activo IN (0,1)),
    CONSTRAINT chk_usuario_bloqueado CHECK (bloqueado IN (0,1)),
    INDEX idx_usuario_login (usuario, activo, bloqueado)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuario_rol (
    id_usuario INT UNSIGNED NOT NULL,
    id_rol INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_usuario,id_rol),
    CONSTRAINT fk_usuario_rol_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_usuario_rol_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Registro de todos los intentos de autenticación: usuario, IP, resultado y fecha.
CREATE TABLE IF NOT EXISTS login_logs (
    id_login_log BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NULL,
    usuario_ingresado VARCHAR(50) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    estado ENUM('exitoso','fallido','bloqueado','inactivo') NOT NULL,
    mensaje VARCHAR(255) NULL,
    fecha_intento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_login_log_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_login_usuario_fecha (usuario_ingresado,fecha_intento),
    INDEX idx_login_ip_fecha (ip,fecha_intento)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS anomalias (
    id_anomalia BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NULL,
    modulo VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    ip VARCHAR(45) NULL,
    nivel ENUM('informativa','advertencia','alta','critica') NOT NULL DEFAULT 'advertencia',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_anomalia_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_anomalia_fecha (fecha_registro),
    INDEX idx_anomalia_nivel (nivel)
) ENGINE=InnoDB;

-- Ciclo de vida de llaves RSA por usuario interno.
-- La llave privada NO se almacena en MySQL: se conserva cifrada fuera de /public.
CREATE TABLE IF NOT EXISTS claves_usuario (
    id_clave BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NOT NULL,
    clave_publica_pem MEDIUMTEXT NOT NULL,
    ruta_clave_privada VARCHAR(500) NOT NULL,
    huella_sha256 CHAR(64) NOT NULL,
    algoritmo VARCHAR(50) NOT NULL DEFAULT 'RSA-2048/SHA-256',
    activa TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_revocacion DATETIME NULL,
    motivo_revocacion VARCHAR(255) NULL,
    CONSTRAINT fk_clave_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_clave_activa CHECK (activa IN (0,1)),
    UNIQUE KEY uq_clave_huella (huella_sha256),
    INDEX idx_clave_usuario_activa (id_usuario,activa)
) ENGINE=InnoDB;

-- Evidencia de no repudio técnico: hash + firma RSA + llave utilizada + IP + fecha.
CREATE TABLE IF NOT EXISTS auditoria_firmada (
    id_auditoria BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NOT NULL,
    id_clave BIGINT UNSIGNED NOT NULL,
    modulo VARCHAR(100) NOT NULL,
    accion VARCHAR(100) NOT NULL,
    entidad VARCHAR(100) NOT NULL,
    entidad_id VARCHAR(100) NULL,
    datos_firmados_json LONGTEXT NOT NULL,
    hash_sha256 CHAR(64) NOT NULL,
    firma_base64 LONGTEXT NOT NULL,
    ip VARCHAR(45) NOT NULL,
    fecha_evento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_auditoria_clave FOREIGN KEY (id_clave) REFERENCES claves_usuario(id_clave)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_auditoria_usuario_fecha (id_usuario,fecha_evento),
    INDEX idx_auditoria_modulo_fecha (modulo,fecha_evento)
) ENGINE=InnoDB;

-- ============================================================
-- 2. CATÁLOGOS E INVENTARIO
-- ============================================================

CREATE TABLE IF NOT EXISTS autos (
    id_auto INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(80) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    anio SMALLINT UNSIGNED NOT NULL,
    descripcion VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_auto_marca_modelo_anio UNIQUE (marca,modelo,anio),
    CONSTRAINT chk_auto_activo CHECK (activo IN (0,1)),
    INDEX idx_auto_busqueda (marca,modelo,anio)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS partes (
    id_parte INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_parte VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_parte_activo CHECK (activo IN (0,1)),
    INDEX idx_parte_nombre (nombre_parte)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS secciones (
    id_seccion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    nombre_seccion VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_seccion_activo CHECK (activo IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventario_partes (
    id_inventario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_auto INT UNSIGNED NOT NULL,
    id_parte INT UNSIGNED NOT NULL,
    id_seccion INT UNSIGNED NOT NULL,
    creado_por INT UNSIGNED NOT NULL,
    codigo_inventario VARCHAR(50) NOT NULL UNIQUE,
    descripcion_corta VARCHAR(255) NOT NULL,
    observaciones TEXT NULL,
    condicion_pieza ENUM('Excelente','Buena','Regular','Para reparar') NOT NULL DEFAULT 'Buena',
    precio DECIMAL(12,2) NOT NULL,
    cantidad INT UNSIGNED NOT NULL DEFAULT 0,
    thumbnail VARCHAR(255) NULL,
    imagen_grande VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventario_auto FOREIGN KEY (id_auto) REFERENCES autos(id_auto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_inventario_parte FOREIGN KEY (id_parte) REFERENCES partes(id_parte)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_inventario_seccion FOREIGN KEY (id_seccion) REFERENCES secciones(id_seccion)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_inventario_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_inventario_precio CHECK (precio >= 0),
    CONSTRAINT chk_inventario_activo CHECK (activo IN (0,1)),
    INDEX idx_inventario_publico (activo,cantidad),
    INDEX idx_inventario_filtros (id_parte,id_auto,id_seccion),
    INDEX idx_inventario_descripcion (descripcion_corta)
) ENGINE=InnoDB;

-- ============================================================
-- 3. VENTAS, FACTURAS Y COMENTARIOS
-- ============================================================

CREATE TABLE IF NOT EXISTS ventas (
    id_venta BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    itbms DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    observacion VARCHAR(255) NULL,
    origen ENUM('interno','cliente') NOT NULL DEFAULT 'interno',
    metodo_pago ENUM('Efectivo','Yappy','Visa','Mastercard') NOT NULL DEFAULT 'Efectivo',
    estado_pago ENUM('no_aplica','confirmado') NOT NULL DEFAULT 'no_aplica',
    referencia_pago VARCHAR(80) NULL,
    metodo_entrega ENUM('retiro','delivery') NOT NULL DEFAULT 'retiro',
    direccion_entrega VARCHAR(255) NULL,
    telefono_entrega VARCHAR(30) NULL,
    costo_entrega DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado ENUM('completada','anulada') NOT NULL DEFAULT 'completada',
    fecha_venta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_venta_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_venta_subtotal CHECK (subtotal >= 0),
    CONSTRAINT chk_venta_itbms CHECK (itbms >= 0),
    CONSTRAINT chk_venta_total CHECK (total >= 0),
    INDEX idx_venta_fecha (fecha_venta),
    INDEX idx_venta_mes_estado (estado,fecha_venta),
    INDEX idx_venta_metodo (metodo_pago)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS venta_detalles (
    id_detalle BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_venta BIGINT UNSIGNED NOT NULL,
    id_inventario INT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    precio_unitario DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) GENERATED ALWAYS AS (cantidad * precio_unitario) STORED,
    CONSTRAINT fk_detalle_venta FOREIGN KEY (id_venta) REFERENCES ventas(id_venta)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detalle_inventario FOREIGN KEY (id_inventario) REFERENCES inventario_partes(id_inventario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_detalle_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_detalle_precio CHECK (precio_unitario >= 0),
    INDEX idx_detalle_venta (id_venta),
    INDEX idx_detalle_inventario (id_inventario)
) ENGINE=InnoDB;

-- Metadatos de factura. El PDF se genera bajo demanda con TCPDF/OpenSSL.
CREATE TABLE IF NOT EXISTS facturas (
    id_factura BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_venta BIGINT UNSIGNED NOT NULL UNIQUE,
    numero_factura VARCHAR(50) NOT NULL UNIQUE,
    subtotal DECIMAL(12,2) NOT NULL,
    itbms DECIMAL(12,2) NOT NULL,
    costo_entrega DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,
    ruta_pdf VARCHAR(500) NULL,
    hash_pdf_sha256 CHAR(64) NULL,
    huella_certificado_sha256 CHAR(64) NULL,
    estado_firma ENUM('pendiente','firmada','error') NOT NULL DEFAULT 'pendiente',
    fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_firma DATETIME NULL,
    CONSTRAINT fk_factura_venta FOREIGN KEY (id_venta) REFERENCES ventas(id_venta)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_factura_estado (estado_firma),
    INDEX idx_factura_fecha (fecha_emision)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS comentarios (
    id_comentario BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_inventario INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NULL,
    nombre_visitante VARCHAR(100) NOT NULL,
    correo_visitante VARCHAR(150) NULL,
    comentario TEXT NOT NULL,
    ip VARCHAR(45) NULL,
    publicado TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    moderado_por INT UNSIGNED NULL,
    fecha_comentario DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_moderacion DATETIME NULL,
    CONSTRAINT fk_comentario_inventario FOREIGN KEY (id_inventario) REFERENCES inventario_partes(id_inventario)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_comentario_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_comentario_moderador FOREIGN KEY (moderado_por) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_comentario_publicado CHECK (publicado IN (0,1)),
    CONSTRAINT chk_comentario_activo CHECK (activo IN (0,1)),
    INDEX idx_comentario_publico (id_inventario,publicado,activo)
) ENGINE=InnoDB;

-- ============================================================
-- 4. DATOS MAESTROS: ROLES Y PERMISOS
-- ============================================================

INSERT IGNORE INTO roles (nombre_rol,descripcion) VALUES
('Administrador','Acceso total al sistema y administración de seguridad.'),
('Operador','Usuario operativo que alimenta inventario y utiliza módulos operativos sin acceso al módulo Usuarios.'),
('Inventario','Gestión de autos, partes, secciones e inventario.'),
('Vendedor','Consulta de inventario y registro de ventas.'),
('Moderador','Moderación de comentarios públicos.'),
('Consulta','Acceso de solo lectura.'),
('Cliente','Cuenta pública creada desde el registro para comprar y comentar.');

INSERT IGNORE INTO permisos (codigo,modulo,accion,descripcion) VALUES
('usuarios.ver','Usuarios','ver','Consultar usuarios internos.'),
('usuarios.crear','Usuarios','crear','Crear usuarios internos.'),
('usuarios.editar','Usuarios','editar','Modificar usuarios internos.'),
('usuarios.estado','Usuarios','estado','Activar, desactivar o desbloquear usuarios.'),
('roles.ver','Roles','ver','Consultar roles y permisos.'),
('roles.gestionar','Roles','gestionar','Crear y modificar roles y permisos.'),
('autos.ver','Autos','ver','Consultar autos.'),
('autos.gestionar','Autos','gestionar','Gestionar autos.'),
('partes.ver','Partes','ver','Consultar tipos de partes.'),
('partes.gestionar','Partes','gestionar','Gestionar tipos de partes.'),
('secciones.ver','Secciones','ver','Consultar secciones o categorías.'),
('secciones.gestionar','Secciones','gestionar','Gestionar secciones o categorías.'),
('inventario.ver','Inventario','ver','Consultar y buscar inventario.'),
('inventario.gestionar','Inventario','gestionar','Crear y modificar inventario.'),
('inventario.exportar','Inventario','exportar','Exportar inventario actual filtrado a Excel.'),
('ventas.ver','Ventas','ver','Consultar ventas y estadísticas.'),
('ventas.crear','Ventas','crear','Registrar ventas internas.'),
('ventas.exportar','Ventas','exportar','Exportar reportes de ventas a Excel.'),
('comentarios.moderar','Comentarios','moderar','Aprobar, ocultar o deshabilitar comentarios.'),
('seguridad.ver','Seguridad','ver','Consultar intentos de login y anomalías.'),
('auditoria.ver','Auditoría','ver','Consultar trazabilidad con firmas RSA.'),
('auditoria.gestionar','Auditoría','gestionar','Rotar llaves RSA de usuarios internos.'),
('catalogo.ver','Catálogo público','ver','Consultar catálogo público.'),
('comentarios.crear','Comentarios','crear','Crear comentarios como cliente autenticado.'),
('compras.crear','Compras','crear','Comprar piezas mediante carrito.'),
('compras.ver','Compras','ver','Consultar historial personal y facturas.');

-- Administrador: todos los permisos presentes y futuros al importar este script.
INSERT IGNORE INTO rol_permiso (id_rol,id_permiso)
SELECT r.id_rol,p.id_permiso FROM roles r CROSS JOIN permisos p
WHERE r.nombre_rol='Administrador';

-- Operador: todo el flujo operativo excepto Usuarios, gestión de Roles y rotación de llaves.
INSERT IGNORE INTO rol_permiso (id_rol,id_permiso)
SELECT r.id_rol,p.id_permiso FROM roles r
INNER JOIN permisos p ON p.codigo IN (
    'roles.ver','autos.ver','autos.gestionar','partes.ver','partes.gestionar',
    'secciones.ver','secciones.gestionar','inventario.ver','inventario.gestionar',
    'inventario.exportar','ventas.ver','ventas.crear','ventas.exportar',
    'comentarios.moderar','seguridad.ver','auditoria.ver','catalogo.ver'
)
WHERE r.nombre_rol='Operador';

INSERT IGNORE INTO rol_permiso (id_rol,id_permiso)
SELECT r.id_rol,p.id_permiso FROM roles r
INNER JOIN permisos p ON p.codigo IN (
    'autos.ver','autos.gestionar','partes.ver','partes.gestionar','secciones.ver',
    'secciones.gestionar','inventario.ver','inventario.gestionar','inventario.exportar'
)
WHERE r.nombre_rol='Inventario';

INSERT IGNORE INTO rol_permiso (id_rol,id_permiso)
SELECT r.id_rol,p.id_permiso FROM roles r
INNER JOIN permisos p ON p.codigo IN ('inventario.ver','ventas.ver','ventas.crear','ventas.exportar')
WHERE r.nombre_rol='Vendedor';

INSERT IGNORE INTO rol_permiso (id_rol,id_permiso)
SELECT r.id_rol,p.id_permiso FROM roles r
INNER JOIN permisos p ON p.codigo='comentarios.moderar'
WHERE r.nombre_rol='Moderador';

INSERT IGNORE INTO rol_permiso (id_rol,id_permiso)
SELECT r.id_rol,p.id_permiso FROM roles r
INNER JOIN permisos p ON p.codigo IN ('autos.ver','partes.ver','secciones.ver','inventario.ver','ventas.ver')
WHERE r.nombre_rol='Consulta';

INSERT IGNORE INTO rol_permiso (id_rol,id_permiso)
SELECT r.id_rol,p.id_permiso FROM roles r
INNER JOIN permisos p ON p.codigo IN ('catalogo.ver','comentarios.crear','compras.crear','compras.ver')
WHERE r.nombre_rol='Cliente';

-- ============================================================
-- 5. USUARIOS INICIALES
-- Contraseña de ambos: root2514 (hash bcrypt).
-- ============================================================

INSERT INTO usuarios (nombre,apellido,usuario,correo,password_hash,intentos_fallidos,bloqueado,bloqueado_hasta,activo)
VALUES
('Administrador','General','admin','admin@mecario.local','$2y$12$srq0r5dCE6eQDxt.XFxZOOpBfboA1QWDLA6mc4ekPhN.MXTeGACQm',0,0,NULL,1),
('Operador','Sistema','operador','operador@mecario.local','$2y$12$srq0r5dCE6eQDxt.XFxZOOpBfboA1QWDLA6mc4ekPhN.MXTeGACQm',0,0,NULL,1)
ON DUPLICATE KEY UPDATE
    password_hash=VALUES(password_hash), intentos_fallidos=0, bloqueado=0, bloqueado_hasta=NULL, activo=1;

INSERT IGNORE INTO usuario_rol (id_usuario,id_rol)
SELECT u.id_usuario,r.id_rol FROM usuarios u INNER JOIN roles r ON r.nombre_rol='Administrador'
WHERE u.usuario='admin';

INSERT IGNORE INTO usuario_rol (id_usuario,id_rol)
SELECT u.id_usuario,r.id_rol FROM usuarios u INNER JOIN roles r ON r.nombre_rol='Operador'
WHERE u.usuario='operador';

-- Secciones iniciales.
INSERT IGNORE INTO secciones
    (codigo, nombre_seccion, descripcion)
VALUES
('A', 'Carrocería', 'Puertas, capós, defensas y piezas exteriores.'),
('B', 'Vidrios y espejos', 'Vidrios, retrovisores y accesorios.'),
('C', 'Motores', 'Motores y componentes mecánicos principales.'),
('D', 'Eléctrico', 'Faros, alternadores, sensores y piezas eléctricas.'),
('E', 'Ruedas y suspensión', 'Llantas, rines y suspensión.');

-- Tipos de partes iniciales.
INSERT IGNORE INTO partes (nombre_parte, descripcion) VALUES
('Puerta', 'Puertas delanteras o traseras.'),
('Motor', 'Motor completo o componentes principales.'),
('Retrovisor', 'Retrovisor lateral.'),
('Vidrio', 'Vidrio delantero, lateral o trasero.'),
('Capó', 'Capó delantero.'),
('Defensa', 'Defensa delantera o trasera.'),
('Faro', 'Faro delantero o trasero.'),
('Radiador', 'Radiador del vehículo.'),
('Caja de cambios', 'Transmisión manual o automática.'),
('Llanta', 'Llanta o neumático del vehículo.');

-- ============================================================
-- Datos de demostración para probar catálogo público, inventario y ventas.
-- Estos INSERT usan IGNORE para que el script pueda importarse varias veces.
-- ============================================================

INSERT IGNORE INTO autos (marca, modelo, anio, descripcion) VALUES
('Toyota', 'Corolla', 2015, 'Sedán compacto usado como referencia para piezas de carrocería.'),
('Honda', 'Civic', 2017, 'Sedán de alta rotación en inventario.'),
('Hyundai', 'Accent', 2018, 'Vehículo común para piezas eléctricas y exteriores.'),
('Nissan', 'Sentra', 2016, 'Auto de origen para piezas mecánicas y de carrocería.'),
('Kia', 'Rio', 2019, 'Hatchback usado para piezas de carrocería y sistema eléctrico.'),
('Mazda', '3', 2014, 'Sedán utilizado como fuente de piezas mecánicas y exteriores.'),
('Ford', 'Escape', 2016, 'SUV con piezas de suspensión, vidrios y carrocería.'),
('Chevrolet', 'Spark', 2020, 'Vehículo compacto para piezas eléctricas y exteriores.'),
('Suzuki', 'Swift', 2018, 'Hatchback con piezas de alta rotación.'),
('Toyota', 'Hilux', 2017, 'Pickup para piezas de motor, carrocería y suspensión.');

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-PUE-001', 'Puerta delantera izquierda en buen estado', 'Color gris, pequeños detalles de uso.', 'Buena', 120.00, 3, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Toyota' AND a.modelo='Corolla' AND a.anio=2015
  AND p.nombre_parte='Puerta'
  AND s.codigo='A'
  AND u.usuario='operador';

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-MOT-002', 'Motor completo revisado', 'Motor probado, requiere instalación por técnico.', 'Buena', 950.00, 1, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Honda' AND a.modelo='Civic' AND a.anio=2017
  AND p.nombre_parte='Motor'
  AND s.codigo='C'
  AND u.usuario='operador';

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-RET-003', 'Retrovisor lateral derecho', 'Carcasa negra, espejo intacto.', 'Excelente', 45.00, 5, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Hyundai' AND a.modelo='Accent' AND a.anio=2018
  AND p.nombre_parte='Retrovisor'
  AND s.codigo='B'
  AND u.usuario='operador';


-- Inventario ampliado de demostración.
INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-VID-004', 'Vidrio lateral delantero', 'Vidrio sin grietas, listo para instalación.', 'Excelente', 65.00, 4, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Nissan' AND a.modelo='Sentra' AND a.anio=2016 AND p.nombre_parte='Vidrio' AND s.codigo='B' AND u.usuario='operador';

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-CAP-005', 'Capó delantero original', 'Pintura roja con detalles leves de uso.', 'Buena', 180.00, 2, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Kia' AND a.modelo='Rio' AND a.anio=2019 AND p.nombre_parte='Capó' AND s.codigo='A' AND u.usuario='operador';

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-DEF-006', 'Defensa trasera completa', 'Incluye soportes principales.', 'Buena', 135.00, 2, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Mazda' AND a.modelo='3' AND a.anio=2014 AND p.nombre_parte='Defensa' AND s.codigo='A' AND u.usuario='operador';

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-FAR-007', 'Faro delantero derecho', 'Mica transparente y conectores completos.', 'Excelente', 90.00, 3, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Ford' AND a.modelo='Escape' AND a.anio=2016 AND p.nombre_parte='Faro' AND s.codigo='D' AND u.usuario='operador';

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-RAD-008', 'Radiador en buen estado', 'Probado sin fugas visibles.', 'Buena', 110.00, 2, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Chevrolet' AND a.modelo='Spark' AND a.anio=2020 AND p.nombre_parte='Radiador' AND s.codigo='C' AND u.usuario='operador';

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-CAJ-009', 'Caja de cambios automática', 'Unidad usada, requiere revisión preventiva antes de instalar.', 'Regular', 620.00, 1, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Suzuki' AND a.modelo='Swift' AND a.anio=2018 AND p.nombre_parte='Caja de cambios' AND s.codigo='C' AND u.usuario='operador';

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-LLA-010', 'Llanta 17 pulgadas', 'Neumático con vida útil aproximada del 70%.', 'Buena', 55.00, 6, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Toyota' AND a.modelo='Hilux' AND a.anio=2017 AND p.nombre_parte='Llanta' AND s.codigo='E' AND u.usuario='operador';

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-PUE-011', 'Puerta trasera derecha', 'Incluye manigueta y mecanismo interno.', 'Buena', 145.00, 2, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Honda' AND a.modelo='Civic' AND a.anio=2017 AND p.nombre_parte='Puerta' AND s.codigo='A' AND u.usuario='operador';

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-RET-012', 'Retrovisor lateral izquierdo', 'Ajuste manual, carcasa blanca.', 'Buena', 38.00, 4, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Chevrolet' AND a.modelo='Spark' AND a.anio=2020 AND p.nombre_parte='Retrovisor' AND s.codigo='B' AND u.usuario='operador';

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-FAR-013', 'Faro trasero izquierdo', 'Completo con base y conectores.', 'Excelente', 72.00, 3, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Hyundai' AND a.modelo='Accent' AND a.anio=2018 AND p.nombre_parte='Faro' AND s.codigo='D' AND u.usuario='operador';

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-MOT-014', 'Motor para reparación', 'Unidad incompleta ideal para repuestos internos.', 'Para reparar', 320.00, 1, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Toyota' AND a.modelo='Hilux' AND a.anio=2017 AND p.nombre_parte='Motor' AND s.codigo='C' AND u.usuario='operador';

INSERT IGNORE INTO inventario_partes
    (id_auto, id_parte, id_seccion, creado_por, codigo_inventario, descripcion_corta, observaciones, condicion_pieza, precio, cantidad, thumbnail, imagen_grande, activo)
SELECT a.id_auto, p.id_parte, s.id_seccion, u.id_usuario,
       'MEC-VID-015', 'Vidrio trasero', 'Desmontado profesionalmente, sin fisuras.', 'Excelente', 85.00, 2, NULL, NULL, 1
FROM autos a, partes p, secciones s, usuarios u
WHERE a.marca='Ford' AND a.modelo='Escape' AND a.anio=2016 AND p.nombre_parte='Vidrio' AND s.codigo='B' AND u.usuario='operador';

-- ============================================================
-- FIN DEL SCRIPT ÚNICO MECARIO
-- Las llaves RSA de admin/operador se generan automáticamente al primer login.
-- Las facturas se crean en BD al confirmar una venta y el PDF firmado se
-- genera al descargarla desde el sistema.
-- ============================================================
