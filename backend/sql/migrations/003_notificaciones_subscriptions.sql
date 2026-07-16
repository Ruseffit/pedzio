-- NOTA: para instalaciones NUEVAS ya no hace falta correr este archivo:
-- su contenido quedó incorporado directamente en sql/schema.sql.
-- Este script solo sirve para actualizar una base de datos de Pedzio
-- ya existente que se creó ANTES de que schema.sql incluyera estos cambios.

-- =====================================================
-- PEDZIO - Tabla de suscripciones Web Push (VAPID)
-- Motor: MySQL 8.0+ / MariaDB 10.4+
-- Aditiva: no modifica ni borra nada existente.
-- =====================================================

SET NAMES utf8mb4;

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
