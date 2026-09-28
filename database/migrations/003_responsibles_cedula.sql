-- =====================================================================
-- Migración 003 — Cédula en Responsables + Préstamos exigen prestatario
-- registrado
-- Antes, "Registrar préstamo" pedía el nombre del prestatario como texto
-- libre (sin validar contra ningún catálogo). Ahora se selecciona (o se
-- da de alta rápida) desde el catálogo Responsables, que pasa a hacer
-- doble función: custodio de bienes Y prestatario elegible. Se le agrega
-- cédula porque un préstamo real necesita poder identificar a la persona
-- de forma inequívoca (el nombre solo no alcanza).
-- =====================================================================

SET NAMES utf8mb4;

ALTER TABLE responsibles
  ADD COLUMN IF NOT EXISTS cedula VARCHAR(20) NULL AFTER nombre;
