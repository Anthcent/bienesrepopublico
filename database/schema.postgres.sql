-- =====================================================================
-- Sistema de Bienes Públicos — Esquema de base de datos (PostgreSQL 14+)
-- Convertido desde database/schema.sql (MySQL) + migrations/001..003.
-- Es el esquema completo y único para un despliegue nuevo (no se aplican
-- las migraciones MySQL por separado: sus tablas/columnas ya están aquí).
--
-- Convenciones: snake_case, claves primarias BIGSERIAL/SERIAL,
-- soft-delete mediante estado (no se elimina físicamente nada crítico).
-- Los antiguos TINYINT(1) de MySQL se mapean a SMALLINT (0/1) en vez de
-- BOOLEAN para que el código PHP existente (comparaciones "= 1"/"= 0" y
-- valores que PDO trae como cadenas) siga funcionando sin cambios.
-- Los ENUM de MySQL se mapean a VARCHAR + CHECK con los mismos valores.
-- =====================================================================

-- Función reutilizada por los triggers "updated_at" de las tablas que la
-- necesitan (equivalente a ON UPDATE CURRENT_TIMESTAMP de MySQL).
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS trigger AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ---------------------------------------------------------------------
-- M03 — Usuarios y roles
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL UNIQUE,
    nombre VARCHAR(80) NOT NULL,
    descripcion VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS permissions (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(80) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    role_id INT NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    cargo VARCHAR(120) NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activo SMALLINT NOT NULL DEFAULT 1,
    ultimo_acceso TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
);
DROP TRIGGER IF EXISTS trg_users_updated_at ON users;
CREATE TRIGGER trg_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGSERIAL PRIMARY KEY,
    scope VARCHAR(12) NOT NULL CHECK (scope IN ('account', 'ip')),
    identifier_hash CHAR(64) NOT NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_login_attempts_lookup
    ON login_attempts (scope, identifier_hash, attempted_at);

CREATE TABLE IF NOT EXISTS user_preferences (
    user_id BIGINT PRIMARY KEY,
    sidebar_collapsed SMALLINT NOT NULL DEFAULT 0,
    vista_inventario VARCHAR(10) NOT NULL DEFAULT 'table' CHECK (vista_inventario IN ('table','cards','compact')),
    densidad VARCHAR(10) NOT NULL DEFAULT 'comoda' CHECK (densidad IN ('comoda','compacta')),
    columnas_json TEXT NULL,
    busquedas_recientes_json TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prefs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
DROP TRIGGER IF EXISTS trg_user_preferences_updated_at ON user_preferences;
CREATE TRIGGER trg_user_preferences_updated_at BEFORE UPDATE ON user_preferences
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE IF NOT EXISTS institutional_identity (
    id SMALLINT PRIMARY KEY DEFAULT 1 CHECK (id = 1),
    system_name VARCHAR(160) NOT NULL,
    organization_name VARCHAR(200) NOT NULL,
    acronym VARCHAR(30) NULL,
    brand_mode VARCHAR(12) NOT NULL DEFAULT 'initials' CHECK (brand_mode IN ('logo', 'initials')),
    tax_id VARCHAR(40) NULL,
    address TEXT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(160) NULL,
    website VARCHAR(255) NULL,
    logo_data_uri TEXT NULL,
    updated_by_user_id BIGINT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_institutional_identity_user FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
);
ALTER TABLE institutional_identity
    ADD COLUMN IF NOT EXISTS brand_mode VARCHAR(12) NOT NULL DEFAULT 'initials'
    CHECK (brand_mode IN ('logo', 'initials'));
UPDATE institutional_identity
SET brand_mode = 'initials'
WHERE logo_data_uri IS NULL AND brand_mode = 'logo';
DROP TRIGGER IF EXISTS trg_institutional_identity_updated_at ON institutional_identity;
CREATE TRIGGER trg_institutional_identity_updated_at BEFORE UPDATE ON institutional_identity
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE IF NOT EXISTS loan_settings (
    id SMALLINT PRIMARY KEY DEFAULT 1 CHECK (id = 1),
    default_loan_days SMALLINT NOT NULL DEFAULT 7 CHECK (default_loan_days BETWEEN 1 AND 365),
    due_alert_days SMALLINT NOT NULL DEFAULT 3 CHECK (due_alert_days BETWEEN 0 AND 90),
    require_return_observation SMALLINT NOT NULL DEFAULT 1 CHECK (require_return_observation IN (0, 1)),
    updated_by_user_id BIGINT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_loan_settings_user FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
);
DROP TRIGGER IF EXISTS trg_loan_settings_updated_at ON loan_settings;
CREATE TRIGGER trg_loan_settings_updated_at BEFORE UPDATE ON loan_settings
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE IF NOT EXISTS security_settings (
    id SMALLINT PRIMARY KEY DEFAULT 1 CHECK (id = 1),
    idle_timeout_minutes SMALLINT NOT NULL DEFAULT 30 CHECK (idle_timeout_minutes BETWEEN 5 AND 120),
    absolute_session_hours SMALLINT NOT NULL DEFAULT 8 CHECK (absolute_session_hours BETWEEN 1 AND 24),
    password_min_length SMALLINT NOT NULL DEFAULT 12 CHECK (password_min_length BETWEEN 8 AND 64),
    max_login_attempts SMALLINT NOT NULL DEFAULT 5 CHECK (max_login_attempts BETWEEN 3 AND 10),
    lockout_minutes SMALLINT NOT NULL DEFAULT 15 CHECK (lockout_minutes BETWEEN 5 AND 120),
    backup_warning_hours SMALLINT NOT NULL DEFAULT 24 CHECK (backup_warning_hours BETWEEN 1 AND 168),
    updated_by_user_id BIGINT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_security_settings_user FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
);
DROP TRIGGER IF EXISTS trg_security_settings_updated_at ON security_settings;
CREATE TRIGGER trg_security_settings_updated_at BEFORE UPDATE ON security_settings
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE IF NOT EXISTS saved_views (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    modulo VARCHAR(40) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    filtros_json TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_saved_views_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- M04 — Catálogos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS locations (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    piso_zona VARCHAR(80) NULL,
    imagen_url VARCHAR(255) NULL,
    activo SMALLINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS responsibles (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    cedula VARCHAR(20) NULL,
    cargo VARCHAR(120) NULL,
    dependencia VARCHAR(150) NULL,
    email VARCHAR(160) NULL,
    telefono VARCHAR(40) NULL,
    ubicacion_habitual_id BIGINT NULL,
    activo SMALLINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_responsibles_location FOREIGN KEY (ubicacion_habitual_id) REFERENCES locations(id)
);

CREATE TABLE IF NOT EXISTS categories (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL UNIQUE,
    tipo_bien VARCHAR(60) NOT NULL,
    icono VARCHAR(40) NULL,
    activo SMALLINT NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS brands (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL UNIQUE,
    activo SMALLINT NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS models (
    id BIGSERIAL PRIMARY KEY,
    brand_id BIGINT NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    activo SMALLINT NOT NULL DEFAULT 1,
    CONSTRAINT fk_models_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS physical_states (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(60) NOT NULL,
    orden SMALLINT NOT NULL DEFAULT 0,
    es_negativo SMALLINT NOT NULL DEFAULT 0,
    color VARCHAR(20) NULL
);

CREATE TABLE IF NOT EXISTS movement_types (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL UNIQUE,
    nombre VARCHAR(80) NOT NULL
);

-- ---------------------------------------------------------------------
-- M05/M06 — Inventario de bienes
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS assets (
    id BIGSERIAL PRIMARY KEY,
    numero_bien VARCHAR(40) NOT NULL UNIQUE,
    serial VARCHAR(80) NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    category_id BIGINT NULL,
    brand_id BIGINT NULL,
    model_id BIGINT NULL,
    color VARCHAR(60) NULL,
    material VARCHAR(60) NULL,
    imagen_url VARCHAR(255) NULL,
    location_id BIGINT NOT NULL,
    responsible_id BIGINT NOT NULL,
    physical_state_id INT NOT NULL,
    estado_administrativo VARCHAR(20) NOT NULL DEFAULT 'ACTIVO' CHECK (estado_administrativo IN ('ACTIVO','DESINCORPORADO')),
    disponibilidad VARCHAR(20) NOT NULL DEFAULT 'DISPONIBLE' CHECK (disponibilidad IN ('DISPONIBLE','PRESTADO')),
    informacion_completa SMALLINT NOT NULL DEFAULT 0,
    usuario_creador_id BIGINT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assets_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT fk_assets_brand FOREIGN KEY (brand_id) REFERENCES brands(id),
    CONSTRAINT fk_assets_model FOREIGN KEY (model_id) REFERENCES models(id),
    CONSTRAINT fk_assets_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_assets_responsible FOREIGN KEY (responsible_id) REFERENCES responsibles(id),
    CONSTRAINT fk_assets_physical_state FOREIGN KEY (physical_state_id) REFERENCES physical_states(id),
    CONSTRAINT fk_assets_creator FOREIGN KEY (usuario_creador_id) REFERENCES users(id)
);
DROP TRIGGER IF EXISTS trg_assets_updated_at ON assets;
CREATE TRIGGER trg_assets_updated_at BEFORE UPDATE ON assets
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE INDEX IF NOT EXISTS idx_assets_search ON assets (descripcion, serial);
CREATE INDEX IF NOT EXISTS idx_assets_estado ON assets (estado_administrativo, disponibilidad);

-- ---------------------------------------------------------------------
-- M07 — Movimientos administrativos (incorporación, reasignación,
-- desincorporación, readmisión). Los préstamos tienen dominio propio (M08).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS asset_movements (
    id BIGSERIAL PRIMARY KEY,
    asset_id BIGINT NOT NULL,
    movement_type_id INT NOT NULL,
    ubicacion_anterior_id BIGINT NULL,
    ubicacion_nueva_id BIGINT NULL,
    responsable_anterior_id BIGINT NULL,
    responsable_nuevo_id BIGINT NULL,
    estado_fisico_anterior_id INT NULL,
    estado_fisico_nuevo_id INT NULL,
    motivo VARCHAR(255) NULL,
    observaciones TEXT NULL,
    anulado SMALLINT NOT NULL DEFAULT 0,
    usuario_id BIGINT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mov_asset FOREIGN KEY (asset_id) REFERENCES assets(id),
    CONSTRAINT fk_mov_type FOREIGN KEY (movement_type_id) REFERENCES movement_types(id),
    CONSTRAINT fk_mov_user FOREIGN KEY (usuario_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_mov_asset ON asset_movements (asset_id, created_at);

-- ---------------------------------------------------------------------
-- M08 — Préstamos y devoluciones (dominio propio, no es reasignación)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS loans (
    id BIGSERIAL PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    responsible_id BIGINT NULL,
    prestatario_nombre_snapshot VARCHAR(150) NOT NULL,
    prestatario_cargo_snapshot VARCHAR(120) NULL,
    prestatario_dependencia_snapshot VARCHAR(150) NULL,
    fecha_prestamo DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    fecha_cierre DATE NULL,
    motivo VARCHAR(255) NULL,
    observaciones TEXT NULL,
    estado VARCHAR(25) NOT NULL DEFAULT 'ACTIVO' CHECK (estado IN ('ACTIVO','PARCIALMENTE_DEVUELTO','DEVUELTO','VENCIDO','ANULADO')),
    usuario_creador_id BIGINT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_loans_responsible FOREIGN KEY (responsible_id) REFERENCES responsibles(id),
    CONSTRAINT fk_loans_creator FOREIGN KEY (usuario_creador_id) REFERENCES users(id)
);
DROP TRIGGER IF EXISTS trg_loans_updated_at ON loans;
CREATE TRIGGER trg_loans_updated_at BEFORE UPDATE ON loans
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE INDEX IF NOT EXISTS idx_loans_estado ON loans (estado, fecha_vencimiento);

CREATE TABLE IF NOT EXISTS loan_details (
    id BIGSERIAL PRIMARY KEY,
    loan_id BIGINT NOT NULL,
    asset_id BIGINT NOT NULL,
    estado_salida_id INT NOT NULL,
    fecha_devolucion DATE NULL,
    estado_devolucion_id INT NULL,
    observacion_devolucion TEXT NULL,
    imagen_devolucion_url VARCHAR(255) NULL,
    devuelto_por_usuario_id BIGINT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ld_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    CONSTRAINT fk_ld_asset FOREIGN KEY (asset_id) REFERENCES assets(id),
    CONSTRAINT fk_ld_estado_salida FOREIGN KEY (estado_salida_id) REFERENCES physical_states(id),
    CONSTRAINT fk_ld_estado_devolucion FOREIGN KEY (estado_devolucion_id) REFERENCES physical_states(id),
    CONSTRAINT fk_ld_user FOREIGN KEY (devuelto_por_usuario_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_ld_asset ON loan_details (asset_id);

CREATE TABLE IF NOT EXISTS loan_extensions (
    id BIGSERIAL PRIMARY KEY,
    loan_id BIGINT NOT NULL,
    fecha_anterior DATE NOT NULL,
    fecha_nueva DATE NOT NULL,
    motivo VARCHAR(255) NULL,
    usuario_id BIGINT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_le_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    CONSTRAINT fk_le_user FOREIGN KEY (usuario_id) REFERENCES users(id)
);

-- ---------------------------------------------------------------------
-- M09 — Documentos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS documents (
    id BIGSERIAL PRIMARY KEY,
    tipo VARCHAR(60) NOT NULL,
    entidad_tipo VARCHAR(40) NOT NULL,
    entidad_id BIGINT NOT NULL,
    version INT NOT NULL DEFAULT 1,
    estado VARCHAR(15) NOT NULL DEFAULT 'VIGENTE' CHECK (estado IN ('VIGENTE','ANULADO')),
    contenido_html TEXT NOT NULL,
    usuario_id BIGINT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_user FOREIGN KEY (usuario_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_documents_entidad ON documents (entidad_tipo, entidad_id);

-- ---------------------------------------------------------------------
-- M11 — Auditoría
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT NULL,
    accion VARCHAR(80) NOT NULL,
    entidad_tipo VARCHAR(40) NOT NULL,
    entidad_id BIGINT NOT NULL,
    resumen_humano VARCHAR(255) NOT NULL,
    valores_anteriores_json TEXT NULL,
    valores_nuevos_json TEXT NULL,
    ip VARCHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (usuario_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_audit_entidad ON audit_log (entidad_tipo, entidad_id);
CREATE INDEX IF NOT EXISTS idx_audit_fecha ON audit_log (created_at);

CREATE TABLE IF NOT EXISTS backup_runs (
    id BIGSERIAL PRIMARY KEY,
    status VARCHAR(12) NOT NULL CHECK (status IN ('success', 'failed')),
    storage_label VARCHAR(120) NULL,
    size_bytes BIGINT NULL CHECK (size_bytes IS NULL OR size_bytes >= 0),
    checksum_sha256 CHAR(64) NULL,
    error_message VARCHAR(500) NULL,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    restore_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_backup_runs_completed ON backup_runs (completed_at DESC);

-- ---------------------------------------------------------------------
-- M12 — Notificaciones (persistentes, fuente de verdad = BD)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id BIGSERIAL PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    usuario_destino_id BIGINT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    mensaje VARCHAR(255) NOT NULL,
    nivel VARCHAR(10) NOT NULL DEFAULT 'info' CHECK (nivel IN ('info','warning','danger','success')),
    entidad_tipo VARCHAR(40) NULL,
    entidad_id BIGINT NULL,
    clave_unica VARCHAR(150) NOT NULL UNIQUE,
    leida SMALLINT NOT NULL DEFAULT 0,
    fecha_lectura TIMESTAMP NULL,
    resuelta SMALLINT NOT NULL DEFAULT 0,
    fecha_resolucion TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (usuario_destino_id) REFERENCES users(id) ON DELETE CASCADE
);
DROP TRIGGER IF EXISTS trg_notifications_updated_at ON notifications;
CREATE TRIGGER trg_notifications_updated_at BEFORE UPDATE ON notifications
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE INDEX IF NOT EXISTS idx_notifications_estado ON notifications (usuario_destino_id, leida, resuelta);

-- ---------------------------------------------------------------------
-- Migración 001 — Jornada de Verificación Patrimonial (ya integrada aquí)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS verification_campaigns (
    id BIGSERIAL PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    titulo VARCHAR(180) NOT NULL,
    descripcion VARCHAR(255) NULL,
    fecha_programada DATE NULL,
    fecha_cierre TIMESTAMP NULL,
    ubicacion_id BIGINT NULL,
    responsible_id BIGINT NULL,
    campos_json TEXT NOT NULL,
    creado_por_usuario_id BIGINT NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'BORRADOR' CHECK (estado IN ('BORRADOR','GENERADA','EN_VERIFICACION','EN_CAPTURA','COMPLETADA','CANCELADA')),
    cantidad_bienes INT NOT NULL DEFAULT 0,
    observaciones TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vc_location FOREIGN KEY (ubicacion_id) REFERENCES locations(id),
    CONSTRAINT fk_vc_responsible FOREIGN KEY (responsible_id) REFERENCES responsibles(id),
    CONSTRAINT fk_vc_creator FOREIGN KEY (creado_por_usuario_id) REFERENCES users(id)
);
DROP TRIGGER IF EXISTS trg_verification_campaigns_updated_at ON verification_campaigns;
CREATE TRIGGER trg_verification_campaigns_updated_at BEFORE UPDATE ON verification_campaigns
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE INDEX IF NOT EXISTS idx_vc_estado ON verification_campaigns (estado);

CREATE TABLE IF NOT EXISTS verification_campaign_items (
    id BIGSERIAL PRIMARY KEY,
    campaign_id BIGINT NOT NULL,
    asset_id BIGINT NOT NULL,

    numero_bien_snapshot VARCHAR(40) NOT NULL,
    descripcion_snapshot VARCHAR(255) NOT NULL,
    serial_snapshot VARCHAR(80) NULL,
    location_id_snapshot BIGINT NOT NULL,
    location_nombre_snapshot VARCHAR(150) NOT NULL,
    responsible_id_snapshot BIGINT NOT NULL,
    responsible_nombre_snapshot VARCHAR(150) NOT NULL,
    physical_state_id_snapshot INT NOT NULL,
    physical_state_nombre_snapshot VARCHAR(60) NOT NULL,

    verificado SMALLINT NOT NULL DEFAULT 0,
    sin_cambios SMALLINT NOT NULL DEFAULT 0,

    ubicacion_correcta SMALLINT NULL,
    responsable_correcto SMALLINT NULL,
    serial_correcto SMALLINT NULL,
    estado_correcto SMALLINT NULL,

    ubicacion_observada_id BIGINT NULL,
    responsable_observado_id BIGINT NULL,
    estado_fisico_observado_id INT NULL,
    serial_observado VARCHAR(80) NULL,

    requiere_fotografia SMALLINT NOT NULL DEFAULT 0,
    observacion_manual TEXT NULL,
    observacion_captura TEXT NULL,

    capturado_por_usuario_id BIGINT NULL,
    fecha_captura TIMESTAMP NULL,
    estado_item VARCHAR(30) NOT NULL DEFAULT 'PENDIENTE' CHECK (estado_item IN ('PENDIENTE','VERIFICADO_SIN_CAMBIOS','VERIFICADO_CON_CAMBIOS','NO_ENCONTRADO','REQUIERE_REVISION')),

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_vci_campaign FOREIGN KEY (campaign_id) REFERENCES verification_campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_vci_asset FOREIGN KEY (asset_id) REFERENCES assets(id),
    CONSTRAINT fk_vci_location_snapshot FOREIGN KEY (location_id_snapshot) REFERENCES locations(id),
    CONSTRAINT fk_vci_responsible_snapshot FOREIGN KEY (responsible_id_snapshot) REFERENCES responsibles(id),
    CONSTRAINT fk_vci_state_snapshot FOREIGN KEY (physical_state_id_snapshot) REFERENCES physical_states(id),
    CONSTRAINT fk_vci_location_obs FOREIGN KEY (ubicacion_observada_id) REFERENCES locations(id),
    CONSTRAINT fk_vci_responsible_obs FOREIGN KEY (responsable_observado_id) REFERENCES responsibles(id),
    CONSTRAINT fk_vci_state_obs FOREIGN KEY (estado_fisico_observado_id) REFERENCES physical_states(id),
    CONSTRAINT fk_vci_user FOREIGN KEY (capturado_por_usuario_id) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_vci_campaign ON verification_campaign_items (campaign_id, estado_item);
CREATE INDEX IF NOT EXISTS idx_vci_asset ON verification_campaign_items (asset_id);

-- Sesiones de aplicación persistentes para despliegues con múltiples instancias.
CREATE TABLE IF NOT EXISTS app_sessions (
    id VARCHAR(128) PRIMARY KEY,
    payload TEXT NOT NULL DEFAULT '',
    expires_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_app_sessions_expires_at ON app_sessions (expires_at);
