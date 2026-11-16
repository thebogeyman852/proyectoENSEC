-- Ejecutar una sola vez en sistemainventario_v3.2, despues de crear un respaldo.
-- Conserva los movimientos existentes: sus nuevas claves quedan en NULL.
ALTER TABLE detalle_material
  ADD COLUMN estado_planificacion ENUM('LEGACY', 'PLANIFICADO') NOT NULL DEFAULT 'LEGACY'
  AFTER cantidad_usada;

ALTER TABLE kardex
  ENGINE = InnoDB,
  ADD COLUMN id_solicitud CHAR(32) NULL AFTER id_kardex,
  ADD UNIQUE KEY uq_kardex_id_solicitud (id_solicitud);
