-- =====================================================
-- PEDZIO - Datos de prueba (seed)
-- Password de todos los usuarios de prueba: "Pedzio2026*"
-- Hash generado con password_hash() PHP (bcrypt)
-- =====================================================

-- SuperAdmin
INSERT INTO usuarios (nombre, email, password_hash, telefono, rol) VALUES
('Admin Pedzio', 'admin@pedzio.test', '$2y$10$examplehashreplacedatruntime', '999000111', 'superadmin');

-- Emprendedores
INSERT INTO usuarios (nombre, email, password_hash, telefono, rol) VALUES
('Rosa Delivery', 'rosa@pedzio.test', '$2y$10$examplehashreplacedatruntime', '988111222', 'emprendedor'),
('Don Lucho Comidas', 'lucho@pedzio.test', '$2y$10$examplehashreplacedatruntime', '977222333', 'emprendedor');

-- Clientes
INSERT INTO usuarios (nombre, email, password_hash, telefono, rol) VALUES
('Ana Cliente', 'ana@pedzio.test', '$2y$10$examplehashreplacedatruntime', '966333444', 'cliente'),
('Luis Cliente', 'luis@pedzio.test', '$2y$10$examplehashreplacedatruntime', '955444555', 'cliente');

-- Emprendimientos (1:1 con emprendedores)
INSERT INTO emprendimientos (usuario_id, nombre_negocio, descripcion, direccion, telefono_contacto) VALUES
((SELECT id FROM usuarios WHERE email = 'rosa@pedzio.test'), 'Sabores de Rosa', 'Comida casera peruana', 'Av. Los Olivos 123, Lima', '988111222'),
((SELECT id FROM usuarios WHERE email = 'lucho@pedzio.test'), 'Don Lucho Parrillas', 'Parrillas y anticuchos', 'Jr. San Martín 456, Lima', '977222333');

-- Categorías
INSERT INTO categorias (emprendimiento_id, nombre) VALUES
((SELECT id FROM emprendimientos WHERE nombre_negocio = 'Sabores de Rosa'), 'Platos de fondo'),
((SELECT id FROM emprendimientos WHERE nombre_negocio = 'Sabores de Rosa'), 'Bebidas'),
((SELECT id FROM emprendimientos WHERE nombre_negocio = 'Don Lucho Parrillas'), 'Parrillas'),
((SELECT id FROM emprendimientos WHERE nombre_negocio = 'Don Lucho Parrillas'), 'Bebidas');

-- Productos
INSERT INTO productos (emprendimiento_id, categoria_id, nombre, descripcion, precio, disponible) VALUES
((SELECT id FROM emprendimientos WHERE nombre_negocio = 'Sabores de Rosa'),
 (SELECT id FROM categorias WHERE nombre = 'Platos de fondo' AND emprendimiento_id = (SELECT id FROM emprendimientos WHERE nombre_negocio = 'Sabores de Rosa')),
 'Lomo saltado', 'Lomo saltado con papas y arroz', 18.50, 1),
((SELECT id FROM emprendimientos WHERE nombre_negocio = 'Sabores de Rosa'),
 (SELECT id FROM categorias WHERE nombre = 'Bebidas' AND emprendimiento_id = (SELECT id FROM emprendimientos WHERE nombre_negocio = 'Sabores de Rosa')),
 'Chicha morada 1/2L', 'Chicha morada casera', 5.00, 1),
((SELECT id FROM emprendimientos WHERE nombre_negocio = 'Don Lucho Parrillas'),
 (SELECT id FROM categorias WHERE nombre = 'Parrillas' AND emprendimiento_id = (SELECT id FROM emprendimientos WHERE nombre_negocio = 'Don Lucho Parrillas')),
 'Anticuchos (6 unid)', 'Anticuchos de corazón a la parrilla', 15.00, 1);

-- Pedido de ejemplo
INSERT INTO pedidos (cliente_id, emprendimiento_id, estado, total, direccion_entrega, notas) VALUES
((SELECT id FROM usuarios WHERE email = 'ana@pedzio.test'),
 (SELECT id FROM emprendimientos WHERE nombre_negocio = 'Sabores de Rosa'),
 'confirmado', 23.50, 'Calle Las Flores 789, Lima', 'Sin ají por favor');

INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES
((SELECT id FROM pedidos ORDER BY id DESC LIMIT 1),
 (SELECT id FROM productos WHERE nombre = 'Lomo saltado'), 1, 18.50, 18.50),
((SELECT id FROM pedidos ORDER BY id DESC LIMIT 1),
 (SELECT id FROM productos WHERE nombre = 'Chicha morada 1/2L'), 1, 5.00, 5.00);

-- Movimiento financiero de ejemplo (ingreso por el pedido de arriba)
INSERT INTO movimientos_financieros (emprendimiento_id, pedido_id, tipo, categoria, monto, descripcion, fecha) VALUES
((SELECT id FROM emprendimientos WHERE nombre_negocio = 'Sabores de Rosa'),
 (SELECT id FROM pedidos ORDER BY id DESC LIMIT 1),
 'ingreso', 'venta', 23.50, 'Venta pedido #1', CURDATE());

-- Gasto manual de ejemplo (no ligado a pedido)
INSERT INTO movimientos_financieros (emprendimiento_id, pedido_id, tipo, categoria, monto, descripcion, fecha) VALUES
((SELECT id FROM emprendimientos WHERE nombre_negocio = 'Sabores de Rosa'),
 NULL, 'gasto', 'insumos', 40.00, 'Compra de carne y verduras', CURDATE());
