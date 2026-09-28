-- =====================================================================
-- Datos semilla (PostgreSQL) — convertidos desde database/seed.sql +
-- los INSERT de datos de migrations/001_verification_campaigns.sql.
-- No incluye bienes, préstamos ni movimientos de ejemplo (evitar datos
-- ficticios de negocio, según 00_LEER_PRIMERO/INSTRUCCIONES_IA.md).
-- Idempotente: se puede ejecutar varias veces sin duplicar datos.
-- =====================================================================

INSERT INTO roles (codigo, nombre, descripcion) VALUES
('ADMIN', 'Administrador', 'Acceso total al sistema, incluye usuarios y auditoría.'),
('OPERATIVO', 'Usuario operativo', 'Gestiona inventario, movimientos, préstamos y documentos.'),
('CONSULTA', 'Usuario de consulta', 'Acceso de solo lectura a inventario, préstamos y reportes.')
ON CONFLICT (codigo) DO UPDATE SET nombre = EXCLUDED.nombre;

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
('document.view', 'Ver y generar documentos'),
('verification.view', 'Ver jornadas de verificación patrimonial'),
('verification.manage', 'Crear, capturar y cerrar jornadas de verificación')
ON CONFLICT (codigo) DO UPDATE SET descripcion = EXCLUDED.descripcion;

-- ADMIN: todos los permisos
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.codigo = 'ADMIN'
ON CONFLICT (role_id, permission_id) DO NOTHING;

-- OPERATIVO: todo excepto administrar usuarios y ver auditoría
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.codigo = 'OPERATIVO' AND p.codigo NOT IN ('user.manage', 'audit.view', 'system.configure')
ON CONFLICT (role_id, permission_id) DO NOTHING;

-- CONSULTA: solo vistas y reportes
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.codigo = 'CONSULTA' AND p.codigo IN ('asset.view', 'loan.view', 'report.generate', 'document.view', 'verification.view')
ON CONFLICT (role_id, permission_id) DO NOTHING;

INSERT INTO institutional_identity (id, system_name, organization_name, acronym, brand_mode)
VALUES (1, 'Sistema de Bienes Públicos', 'Bienes Públicos', 'SBP', 'initials')
ON CONFLICT (id) DO NOTHING;

INSERT INTO loan_settings (id, default_loan_days, due_alert_days, require_return_observation)
VALUES (1, 7, 3, 1)
ON CONFLICT (id) DO NOTHING;

INSERT INTO security_settings
    (id, idle_timeout_minutes, absolute_session_hours, password_min_length, max_login_attempts, lockout_minutes, backup_warning_hours)
VALUES (1, 30, 8, 12, 5, 15, 24)
ON CONFLICT (id) DO NOTHING;

-- El usuario administrador inicial NO se crea aquí: el hash de contraseña
-- debe generarse con password_hash() de PHP (bcrypt), no se debe fabricar
-- un hash a mano. Ejecutar después de importar este archivo:
--
--   php database/create_admin.php admin@bienespublicos.local "TuContraseñaSegura"
--
-- Ese script crea (o actualiza) el usuario administrador con un hash válido.

-- Catálogo: estados físicos (colores iguales a los que traía la
-- migración 002 de MySQL)
INSERT INTO physical_states (codigo, nombre, orden, es_negativo, color) VALUES
('BUENO', 'Bueno', 1, 0, '#147d4a'),
('REGULAR', 'Regular', 2, 0, '#a15c00'),
('DETERIORADO', 'Deteriorado', 3, 1, '#c44f00'),
('INSERVIBLE', 'Inservible', 4, 1, '#b42318')
ON CONFLICT (codigo) DO UPDATE SET nombre = EXCLUDED.nombre;

-- Catálogo: tipos de movimiento
INSERT INTO movement_types (codigo, nombre) VALUES
('INCORPORACION', 'Incorporación / registro inicial'),
('REASIGNACION', 'Reasignación'),
('DESINCORPORACION', 'Desincorporación'),
('READMISION', 'Readmisión'),
('PRESTAMO', 'Salida por préstamo'),
('DEVOLUCION', 'Devolución de préstamo'),
('VERIFICACION', 'Actualización por verificación')
ON CONFLICT (codigo) DO UPDATE SET nombre = EXCLUDED.nombre;

-- Catálogo: categorías / tipos de bien (usadas en el wizard de incorporación)
INSERT INTO categories (nombre, tipo_bien, icono) VALUES
('Laptop', 'computo', 'laptop'),
('Computador de escritorio', 'computo', 'desktop'),
('Impresora', 'impresion', 'printer'),
('Mobiliario', 'mobiliario', 'furniture'),
('Otro', 'otro', 'box')
ON CONFLICT (nombre) DO UPDATE SET tipo_bien = EXCLUDED.tipo_bien;
