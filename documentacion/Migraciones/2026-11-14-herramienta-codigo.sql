-- Apply once to innomade_pruebas after creating a fresh backup.
-- Existing tools keep NULL; codes are required for new and edited records.
USE innomade_pruebas;

ALTER TABLE herramienta
  ADD COLUMN codigo VARCHAR(50) NULL DEFAULT NULL AFTER idherramienta,
  ADD UNIQUE KEY uq_herramienta_codigo (codigo);
