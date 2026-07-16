-- NOTA: para instalaciones NUEVAS ya no hace falta correr este archivo:
-- su contenido quedó incorporado directamente en sql/schema.sql.
-- Este script solo sirve para actualizar una base de datos de Pedzio
-- ya existente que se creó ANTES de que schema.sql incluyera estos cambios.

-- =====================================================
-- PEDZIO - Migración 002
-- Configuración de emprendimiento + Notificaciones
-- Motor: MySQL 8.0+ / MariaDB 10.4+
-- 100% ADITIVA: no modifica ni borra nada existente.
-- Ejecutar una sola vez sobre la BD ya creada con schema.sql
-- =====================================================

SET NAMES utf8mb4;

-- ── 1. Nuevos campos de configuración en emprendimientos ────────────────────
-- (horario, pedido mínimo, radio de entrega, aceptar pedidos, preferencias
--  de notificación). Se usa un procedimiento con IF para que la migración
--  se pueda re-ejecutar sin error si alguna columna ya existe.

SET @db := DATABASE();

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE emprendimientos ADD COLUMN horario_apertura TIME NOT NULL DEFAULT ''08:00:00'' AFTER telefono_contacto',
    'SELECT ''horario_apertura ya existe'''
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'emprendimientos' AND COLUMN_NAME = 'horario_apertura'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE emprendimientos ADD COLUMN horario_cierre TIME NOT NULL DEFAULT ''20:00:00'' AFTER horario_apertura',
    'SELECT ''horario_cierre ya existe'''
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'emprendimientos' AND COLUMN_NAME = 'horario_cierre'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE emprendimientos ADD COLUMN pedido_minimo DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER horario_cierre',
    'SELECT ''pedido_minimo ya existe'''
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'emprendimientos' AND COLUMN_NAME = 'pedido_minimo'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE emprendimientos ADD COLUMN radio_entrega_km DECIMAL(5,2) NOT NULL DEFAULT 5.00 AFTER pedido_minimo',
    'SELECT ''radio_entrega_km ya existe'''
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'emprendimientos' AND COLUMN_NAME = 'radio_entrega_km'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE emprendimientos ADD COLUMN acepta_pedidos TINYINT(1) NOT NULL DEFAULT 1 AFTER radio_entrega_km',
    'SELECT ''acepta_pedidos ya existe'''
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'emprendimientos' AND COLUMN_NAME = 'acepta_pedidos'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE emprendimientos ADD COLUMN notif_push_activo TINYINT(1) NOT NULL DEFAULT 1 AFTER acepta_pedidos',
    'SELECT ''notif_push_activo ya existe'''
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'emprendimientos' AND COLUMN_NAME = 'notif_push_activo'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE emprendimientos ADD COLUMN notif_email_activo TINYINT(1) NOT NULL DEFAULT 1 AFTER notif_push_activo',
    'SELECT ''notif_email_activo ya existe'''
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'emprendimientos' AND COLUMN_NAME = 'notif_email_activo'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── 2. Tabla de notificaciones (centro de notificaciones in-app) ───────────

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

-- NOTA: la tabla de suscripciones push NO se crea aquí. Se usa directamente
-- `notificaciones_subscriptions` (migración 003), que es el sistema de
-- Web Push real con VAPID. Así evitamos mantener dos tablas para lo mismo.
