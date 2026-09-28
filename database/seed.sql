-- =====================================================================
-- Datos semilla: catálogos base y usuario administrador inicial.
-- No incluye bienes, préstamos ni movimientos de ejemplo (evitar datos
-- ficticios de negocio, según 00_LEER_PRIMERO/INSTRUCCIONES_IA.md).
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO roles (codigo, nombre, descripcion) VALUES
('ADMIN', 'Administrador', 'Acceso total al sistema, incluye usuarios y auditoría.'),
('OPERATIVO', 'Usuario operativo', 'Gestiona inventario, movimientos, préstamos y documentos.'),
('CONSULTA', 'Usuario de consulta', 'Acceso de solo lectura a inventario, préstamos y reportes.')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO permissions (codigo, descripcion) VALUES
('asset.view', 'Ver inventario de bienes'),
('asset.create', 'Incorporar bienes'),
('asset.edit', 'Editar bienes'),
('asset.reassign', 'Reasignar bienes'),
('asset.decommission', 'Desincorporar bienes'),
('asset.readmit', 'Readmitir bienes'),
('loan.view', 'Ver préstamos'),
('loan.create', 'Registrar préstamos'),
('loan.return', 'Registrar devoluciones'),
('loan.extend', 'Extender préstamos'),
('loan.cancel', 'Anular préstamos'),
('catalog.manage', 'Administrar catálogos'),
('user.manage', 'Administrar usuarios'),
('audit.view', 'Ver auditoría'),
('system.configure', 'Configurar el sistema'),
('report.generate', 'Generar reportes'),
('document.view', 'Ver y generar documentos')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- ADMIN: todos los permisos
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.codigo = 'ADMIN'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- OPERATIVO: todo excepto administrar usuarios y ver auditoría
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.codigo = 'OPERATIVO' AND p.codigo NOT IN ('user.manage', 'audit.view', 'system.configure')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- CONSULTA: solo vistas y reportes
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.codigo = 'CONSULTA' AND p.codigo IN ('asset.view', 'loan.view', 'report.generate', 'document.view')
ON DUPLICATE KEY UPDATE role_id = role_id;

INSERT INTO institutional_identity (id, system_name, organization_name, acronym, brand_mode)
VALUES (1, 'Sistema de Bienes Públicos', 'Bienes Públicos', 'SBP', 'initials')
ON DUPLICATE KEY UPDATE id = id;

-- El usuario administrador inicial NO se crea aquí: el hash de contraseña
-- debe generarse con password_hash() de PHP (bcrypt), no se debe fabricar
-- un hash a mano. Ejecutar después de importar este archivo:
--
--   php database/create_admin.php admin@bienespublicos.local "TuContraseñaSegura"
--
-- Ese script crea (o actualiza) el usuario administrador con un hash válido.

-- Catálogo: estados físicos
INSERT INTO physical_states (codigo, nombre, orden, es_negativo) VALUES
('BUENO', 'Bueno', 1, 0),
('REGULAR', 'Regular', 2, 0),
('DETERIORADO', 'Deteriorado', 3, 1),
('INSERVIBLE', 'Inservible', 4, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Catálogo: tipos de movimiento
INSERT INTO movement_types (codigo, nombre) VALUES
('INCORPORACION', 'Incorporación / registro inicial'),
('REASIGNACION', 'Reasignación'),
('DESINCORPORACION', 'Desincorporación'),
('READMISION', 'Readmisión'),
('PRESTAMO', 'Salida por préstamo'),
('DEVOLUCION', 'Devolución de préstamo')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Catálogo: categorías / tipos de bien (usadas en el wizard de incorporación)
INSERT INTO categories (nombre, tipo_bien, icono) VALUES
('Laptop', 'computo', 'laptop'),
('Computador de escritorio', 'computo', 'desktop'),
('Impresora', 'impresion', 'printer'),
('Mobiliario', 'mobiliario', 'furniture'),
('Otro', 'otro', 'box')
ON DUPLICATE KEY UPDATE tipo_bien = VALUES(tipo_bien);
