-- =====================================================================
-- Sistema de Bienes Públicos — Esquema de base de datos (MySQL 8+)
-- Fuente: 01_PLAN_MAESTRO/PLAN_MAESTRO_V4.md
-- Convenciones: snake_case, claves primarias BIGINT UNSIGNED AUTO_INCREMENT,
-- soft-delete mediante estado (no se elimina físicamente nada crítico).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- M03 — Usuarios y roles
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL UNIQUE,
    nombre VARCHAR(80) NOT NULL,
    descripcion VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(80) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    cargo VARCHAR(120) NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_acceso DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_preferences (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    sidebar_collapsed TINYINT(1) NOT NULL DEFAULT 0,
    vista_inventario ENUM('table','cards','compact') NOT NULL DEFAULT 'table',
    densidad ENUM('comoda','compacta') NOT NULL DEFAULT 'comoda',
    columnas_json TEXT NULL,
    busquedas_recientes_json TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_prefs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS institutional_identity (
    id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    system_name VARCHAR(160) NOT NULL,
    organization_name VARCHAR(200) NOT NULL,
    acronym VARCHAR(30) NULL,
    brand_mode ENUM('logo','initials') NOT NULL DEFAULT 'initials',
    tax_id VARCHAR(40) NULL,
    address TEXT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(160) NULL,
    website VARCHAR(255) NULL,
    logo_data_uri LONGTEXT NULL,
    updated_by_user_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_institutional_identity_singleton CHECK (id = 1),
    CONSTRAINT fk_institutional_identity_user FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS saved_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    modulo VARCHAR(40) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    filtros_json TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_saved_views_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- M04 — Catálogos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    piso_zona VARCHAR(80) NULL,
    imagen_url VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS responsibles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    cargo VARCHAR(120) NULL,
    dependencia VARCHAR(150) NULL,
    email VARCHAR(160) NULL,
    telefono VARCHAR(40) NULL,
    ubicacion_habitual_id BIGINT UNSIGNED NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_responsibles_location FOREIGN KEY (ubicacion_habitual_id) REFERENCES locations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    tipo_bien VARCHAR(60) NOT NULL,
    icono VARCHAR(40) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS brands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL UNIQUE,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS models (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    brand_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_models_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS physical_states (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(60) NOT NULL,
    orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
    es_negativo TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS movement_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL UNIQUE,
    nombre VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- M05/M06 — Inventario de bienes
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS assets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_bien VARCHAR(40) NOT NULL UNIQUE,
    serial VARCHAR(80) NULL,
    descripcion VARCHAR(255) NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    brand_id BIGINT UNSIGNED NULL,
    model_id BIGINT UNSIGNED NULL,
    color VARCHAR(60) NULL,
    material VARCHAR(60) NULL,
    imagen_url VARCHAR(255) NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    responsible_id BIGINT UNSIGNED NOT NULL,
    physical_state_id INT UNSIGNED NOT NULL,
    estado_administrativo ENUM('ACTIVO','DESINCORPORADO') NOT NULL DEFAULT 'ACTIVO',
    disponibilidad ENUM('DISPONIBLE','PRESTADO') NOT NULL DEFAULT 'DISPONIBLE',
    informacion_completa TINYINT(1) NOT NULL DEFAULT 0,
    usuario_creador_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_assets_serial (serial),
    CONSTRAINT fk_assets_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT fk_assets_brand FOREIGN KEY (brand_id) REFERENCES brands(id),
    CONSTRAINT fk_assets_model FOREIGN KEY (model_id) REFERENCES models(id),
    CONSTRAINT fk_assets_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_assets_responsible FOREIGN KEY (responsible_id) REFERENCES responsibles(id),
    CONSTRAINT fk_assets_physical_state FOREIGN KEY (physical_state_id) REFERENCES physical_states(id),
    CONSTRAINT fk_assets_creator FOREIGN KEY (usuario_creador_id) REFERENCES users(id),
    INDEX idx_assets_search (descripcion, serial),
    INDEX idx_assets_estado (estado_administrativo, disponibilidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- M07 — Movimientos administrativos (incorporación, reasignación,
-- desincorporación, readmisión). Los préstamos tienen dominio propio (M08).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS asset_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id BIGINT UNSIGNED NOT NULL,
    movement_type_id INT UNSIGNED NOT NULL,
    ubicacion_anterior_id BIGINT UNSIGNED NULL,
    ubicacion_nueva_id BIGINT UNSIGNED NULL,
    responsable_anterior_id BIGINT UNSIGNED NULL,
    responsable_nuevo_id BIGINT UNSIGNED NULL,
    estado_fisico_anterior_id INT UNSIGNED NULL,
    estado_fisico_nuevo_id INT UNSIGNED NULL,
    motivo VARCHAR(255) NULL,
    observaciones TEXT NULL,
    anulado TINYINT(1) NOT NULL DEFAULT 0,
    usuario_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mov_asset FOREIGN KEY (asset_id) REFERENCES assets(id),
    CONSTRAINT fk_mov_type FOREIGN KEY (movement_type_id) REFERENCES movement_types(id),
    CONSTRAINT fk_mov_user FOREIGN KEY (usuario_id) REFERENCES users(id),
    INDEX idx_mov_asset (asset_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- M08 — Préstamos y devoluciones (dominio propio, no es reasignación)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS loans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    responsible_id BIGINT UNSIGNED NULL,
    prestatario_nombre_snapshot VARCHAR(150) NOT NULL,
    prestatario_cargo_snapshot VARCHAR(120) NULL,
    prestatario_dependencia_snapshot VARCHAR(150) NULL,
    fecha_prestamo DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    fecha_cierre DATE NULL,
    motivo VARCHAR(255) NULL,
    observaciones TEXT NULL,
    estado ENUM('ACTIVO','PARCIALMENTE_DEVUELTO','DEVUELTO','VENCIDO','ANULADO') NOT NULL DEFAULT 'ACTIVO',
    usuario_creador_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_loans_responsible FOREIGN KEY (responsible_id) REFERENCES responsibles(id),
    CONSTRAINT fk_loans_creator FOREIGN KEY (usuario_creador_id) REFERENCES users(id),
    INDEX idx_loans_estado (estado, fecha_vencimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS loan_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    asset_id BIGINT UNSIGNED NOT NULL,
    estado_salida_id INT UNSIGNED NOT NULL,
    fecha_devolucion DATE NULL,
    estado_devolucion_id INT UNSIGNED NULL,
    observacion_devolucion TEXT NULL,
    imagen_devolucion_url VARCHAR(255) NULL,
    devuelto_por_usuario_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ld_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    CONSTRAINT fk_ld_asset FOREIGN KEY (asset_id) REFERENCES assets(id),
    CONSTRAINT fk_ld_estado_salida FOREIGN KEY (estado_salida_id) REFERENCES physical_states(id),
    CONSTRAINT fk_ld_estado_devolucion FOREIGN KEY (estado_devolucion_id) REFERENCES physical_states(id),
    CONSTRAINT fk_ld_user FOREIGN KEY (devuelto_por_usuario_id) REFERENCES users(id),
    INDEX idx_ld_asset (asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS loan_extensions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    fecha_anterior DATE NOT NULL,
    fecha_nueva DATE NOT NULL,
    motivo VARCHAR(255) NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_le_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    CONSTRAINT fk_le_user FOREIGN KEY (usuario_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- M09 — Documentos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(60) NOT NULL,
    entidad_tipo VARCHAR(40) NOT NULL,
    entidad_id BIGINT UNSIGNED NOT NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    estado ENUM('VIGENTE','ANULADO') NOT NULL DEFAULT 'VIGENTE',
    contenido_html LONGTEXT NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_user FOREIGN KEY (usuario_id) REFERENCES users(id),
    INDEX idx_documents_entidad (entidad_tipo, entidad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- M11 — Auditoría
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NULL,
    accion VARCHAR(80) NOT NULL,
    entidad_tipo VARCHAR(40) NOT NULL,
    entidad_id BIGINT UNSIGNED NOT NULL,
    resumen_humano VARCHAR(255) NOT NULL,
    valores_anteriores_json TEXT NULL,
    valores_nuevos_json TEXT NULL,
    ip VARCHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (usuario_id) REFERENCES users(id),
    INDEX idx_audit_entidad (entidad_tipo, entidad_id),
    INDEX idx_audit_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- M12 — Notificaciones (persistentes, fuente de verdad = BD)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    usuario_destino_id BIGINT UNSIGNED NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    mensaje VARCHAR(255) NOT NULL,
    nivel ENUM('info','warning','danger','success') NOT NULL DEFAULT 'info',
    entidad_tipo VARCHAR(40) NULL,
    entidad_id BIGINT UNSIGNED NULL,
    clave_unica VARCHAR(150) NOT NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    fecha_lectura DATETIME NULL,
    resuelta TINYINT(1) NOT NULL DEFAULT 0,
    fecha_resolucion DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notifications_clave (clave_unica),
    CONSTRAINT fk_notifications_user FOREIGN KEY (usuario_destino_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_estado (usuario_destino_id, leida, resuelta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
