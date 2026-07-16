-- =====================================================
-- PEDZIO - Esquema de Base de Datos
-- Motor: MySQL 8.0+ / MariaDB 10.4+
-- Charset: utf8mb4
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    foto_url VARCHAR(255) DEFAULT NULL,
    rol ENUM('cliente', 'emprendedor', 'superadmin') NOT NULL DEFAULT 'cliente',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuarios_rol (rol),
    INDEX idx_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS emprendimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL UNIQUE,
    nombre_negocio VARCHAR(150) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    logo_path VARCHAR(255) DEFAULT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    telefono_contacto VARCHAR(20) DEFAULT NULL,
    horario_apertura TIME NOT NULL DEFAULT '08:00:00',
    horario_cierre TIME NOT NULL DEFAULT '20:00:00',
    pedido_minimo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    radio_entrega_km DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    acepta_pedidos TINYINT(1) NOT NULL DEFAULT 1,
    notif_push_activo TINYINT(1) NOT NULL DEFAULT 1,
    notif_email_activo TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_emprendimientos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    emprendimiento_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categorias_emprendimiento
        FOREIGN KEY (emprendimiento_id) REFERENCES emprendimientos(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_categorias_emprendimiento (emprendimiento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS productos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    emprendimiento_id INT UNSIGNED NOT NULL,
    categoria_id INT UNSIGNED DEFAULT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    precio DECIMAL(10,2) NOT NULL,
    imagen_path VARCHAR(255) DEFAULT NULL,
    disponible TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_productos_emprendimiento
        FOREIGN KEY (emprendimiento_id) REFERENCES emprendimientos(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_productos_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_productos_emprendimiento (emprendimiento_id),
    INDEX idx_productos_disponible (disponible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    emprendimiento_id INT UNSIGNED NOT NULL,
    estado ENUM('pendiente', 'confirmado', 'en_preparacion', 'en_camino', 'entregado', 'cancelado')
        NOT NULL DEFAULT 'pendiente',
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    direccion_entrega VARCHAR(255) NOT NULL,
    notas TEXT DEFAULT NULL,
    comprobante_pago VARCHAR(255) DEFAULT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedidos_cliente
        FOREIGN KEY (cliente_id) REFERENCES usuarios(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pedidos_emprendimiento
        FOREIGN KEY (emprendimiento_id) REFERENCES emprendimientos(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_pedidos_cliente (cliente_id),
    INDEX idx_pedidos_emprendimiento (emprendimiento_id),
    INDEX idx_pedidos_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS detalle_pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    producto_id INT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_detalle_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detalle_producto
        FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_detalle_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS movimientos_financieros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    emprendimiento_id INT UNSIGNED NOT NULL,
    pedido_id INT UNSIGNED DEFAULT NULL,
    tipo ENUM('ingreso', 'gasto') NOT NULL,
    categoria VARCHAR(80) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    fecha DATE NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimientos_emprendimiento
        FOREIGN KEY (emprendimiento_id) REFERENCES emprendimientos(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_movimientos_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_movimientos_emprendimiento (emprendimiento_id),
    INDEX idx_movimientos_tipo_fecha (tipo, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notificaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tipo VARCHAR(40) NOT NULL DEFAULT 'general',
    titulo VARCHAR(150) NOT NULL,
    mensaje VARCHAR(255) DEFAULT NULL,
    url VARCHAR(255) DEFAULT NULL,
    leido TINYINT(1) NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificaciones_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_notificaciones_usuario_leido (usuario_id, leido),
    INDEX idx_notificaciones_creado (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notificaciones_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    endpoint VARCHAR(500) NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_subs_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_notif_subs_endpoint (endpoint(255)),
    INDEX idx_notif_subs_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
