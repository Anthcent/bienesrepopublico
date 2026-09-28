-- =====================================================================
-- Migración 002 — Color por estado físico
-- Permite editar el catálogo de Estados físicos (antes de solo lectura)
-- y asignarle un color a cada estado, reutilizado en el wizard de
-- incorporación (tarjetas del paso "Asignación y estado").
-- =====================================================================

SET NAMES utf8mb4;

ALTER TABLE physical_states
  ADD COLUMN IF NOT EXISTS color VARCHAR(20) NULL AFTER es_negativo;

UPDATE physical_states SET color = '#147d4a' WHERE codigo = 'BUENO' AND (color IS NULL OR color = '');
UPDATE physical_states SET color = '#a15c00' WHERE codigo = 'REGULAR' AND (color IS NULL OR color = '');
UPDATE physical_states SET color = '#c44f00' WHERE codigo = 'DETERIORADO' AND (color IS NULL OR color = '');
UPDATE physical_states SET color = '#b42318' WHERE codigo = 'INSERVIBLE' AND (color IS NULL OR color = '');
UPDATE physical_states SET color = '#50617a' WHERE color IS NULL OR color = '';
