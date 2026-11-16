-- Ejecutar solo una vez en la copia de pruebas de la base, despues de respaldarla.
-- Los pagos existentes conservan NULL y no se reescriben.

ALTER TABLE pagos
  ADD COLUMN id_solicitud CHAR(32) NULL AFTER idpago,
  ADD UNIQUE KEY uq_pagos_id_solicitud (id_solicitud);
