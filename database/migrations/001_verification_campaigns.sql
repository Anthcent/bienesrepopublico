-- =====================================================================
-- Migración 001 — Jornada de Verificación Patrimonial (Plan Maestro V5 §17-30)
-- Tablas nuevas: verification_campaigns, verification_campaign_items
-- Además: permisos nuevos (verification.view/manage) y tipo de movimiento
-- VERIFICACION (para correcciones de estado físico/serial que no implican
-- cambio de ubicación/responsable, las cuales sí reutilizan REASIGNACION
-- vía AssetMovementService::reassign()).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS verification_campaigns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    titulo VARCHAR(180) NOT NULL,
    descripcion VARCHAR(255) NULL,
    fecha_programada DATE NULL,
    fecha_cierre DATETIME NULL,
    ubicacion_id BIGINT UNSIGNED NULL,
    responsible_id BIGINT UNSIGNED NULL,
    campos_json TEXT NOT NULL,
    creado_por_usuario_id BIGINT UNSIGNED NOT NULL,
    estado ENUM('BORRADOR','GENERADA','EN_VERIFICACION','EN_CAPTURA','COMPLETADA','CANCELADA') NOT NULL DEFAULT 'BORRADOR',
    cantidad_bienes INT UNSIGNED NOT NULL DEFAULT 0,
    observaciones TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_vc_location FOREIGN KEY (ubicacion_id) REFERENCES locations(id),
    CONSTRAINT fk_vc_responsible FOREIGN KEY (responsible_id) REFERENCES responsibles(id),
    CONSTRAINT fk_vc_creator FOREIGN KEY (creado_por_usuario_id) REFERENCES users(id),
    INDEX idx_vc_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS verification_campaign_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    asset_id BIGINT UNSIGNED NOT NULL,

    numero_bien_snapshot VARCHAR(40) NOT NULL,
    descripcion_snapshot VARCHAR(255) NOT NULL,
    serial_snapshot VARCHAR(80) NULL,
    location_id_snapshot BIGINT UNSIGNED NOT NULL,
    location_nombre_snapshot VARCHAR(150) NOT NULL,
    responsible_id_snapshot BIGINT UNSIGNED NOT NULL,
    responsible_nombre_snapshot VARCHAR(150) NOT NULL,
    physical_state_id_snapshot INT UNSIGNED NOT NULL,
    physical_state_nombre_snapshot VARCHAR(60) NOT NULL,

    verificado TINYINT(1) NOT NULL DEFAULT 0,
    sin_cambios TINYINT(1) NOT NULL DEFAULT 0,

    ubicacion_correcta TINYINT(1) NULL,
    responsable_correcto TINYINT(1) NULL,
    serial_correcto TINYINT(1) NULL,
    estado_correcto TINYINT(1) NULL,

    ubicacion_observada_id BIGINT UNSIGNED NULL,
    responsable_observado_id BIGINT UNSIGNED NULL,
    estado_fisico_observado_id INT UNSIGNED NULL,
    serial_observado VARCHAR(80) NULL,

    requiere_fotografia TINYINT(1) NOT NULL DEFAULT 0,
    observacion_manual TEXT NULL,
    observacion_captura TEXT NULL,

    capturado_por_usuario_id BIGINT UNSIGNED NULL,
    fecha_captura DATETIME NULL,
    estado_item ENUM('PENDIENTE','VERIFICADO_SIN_CAMBIOS','VERIFICADO_CON_CAMBIOS','NO_ENCONTRADO','REQUIERE_REVISION') NOT NULL DEFAULT 'PENDIENTE',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_vci_campaign FOREIGN KEY (campaign_id) REFERENCES verification_campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_vci_asset FOREIGN KEY (asset_id) REFERENCES assets(id),
    CONSTRAINT fk_vci_location_snapshot FOREIGN KEY (location_id_snapshot) REFERENCES locations(id),
    CONSTRAINT fk_vci_responsible_snapshot FOREIGN KEY (responsible_id_snapshot) REFERENCES responsibles(id),
    CONSTRAINT fk_vci_state_snapshot FOREIGN KEY (physical_state_id_snapshot) REFERENCES physical_states(id),
    CONSTRAINT fk_vci_location_obs FOREIGN KEY (ubicacion_observada_id) REFERENCES locations(id),
    CONSTRAINT fk_vci_responsible_obs FOREIGN KEY (responsable_observado_id) REFERENCES responsibles(id),
    CONSTRAINT fk_vci_state_obs FOREIGN KEY (estado_fisico_observado_id) REFERENCES physical_states(id),
    CONSTRAINT fk_vci_user FOREIGN KEY (capturado_por_usuario_id) REFERENCES users(id),
    INDEX idx_vci_campaign (campaign_id, estado_item),
    INDEX idx_vci_asset (asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO movement_types (codigo, nombre)
SELECT 'VERIFICACION', 'Actualización por verificación'
WHERE NOT EXISTS (SELECT 1 FROM movement_types WHERE codigo = 'VERIFICACION');

INSERT INTO permissions (codigo, descripcion)
SELECT 'verification.view', 'Ver jornadas de verificación patrimonial'
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE codigo = 'verification.view');

INSERT INTO permissions (codigo, descripcion)
SELECT 'verification.manage', 'Crear, capturar y cerrar jornadas de verificación'
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE codigo = 'verification.manage');

-- ADMIN y OPERATIVO: ver + gestionar. CONSULTA: solo ver (mismo criterio
-- que asset.view/loan.view/report.generate/document.view ya asignados).
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.codigo IN ('ADMIN','OPERATIVO') AND p.codigo IN ('verification.view','verification.manage')
  AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.codigo = 'CONSULTA' AND p.codigo = 'verification.view'
  AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);

SET FOREIGN_KEY_CHECKS = 1;
