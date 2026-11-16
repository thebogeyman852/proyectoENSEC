-- Ejecutar solo en una copia de pruebas de sistemainventario_v3.2,
-- despues de respaldar y aplicar 2026-11-11-kardex-consumos-idempotentes.sql.
-- No elimina ni modifica registros existentes.

ALTER TABLE ajuste_inventario
  ADD COLUMN id_solicitud CHAR(32) NULL AFTER id_ajuste,
  ADD UNIQUE KEY uq_ajuste_id_solicitud (id_solicitud);

ALTER TABLE compra
  ADD COLUMN id_solicitud CHAR(32) NULL AFTER id_compra,
  ADD UNIQUE KEY uq_compra_id_solicitud (id_solicitud);

ALTER TABLE pagos
  ADD COLUMN estado ENUM('registrado', 'anulado') NOT NULL DEFAULT 'registrado',
  ADD COLUMN anulado_en DATETIME DEFAULT NULL,
  ADD COLUMN anulado_por INT(11) DEFAULT NULL,
  ADD COLUMN motivo_anulacion VARCHAR(255) DEFAULT NULL,
  ADD KEY idx_pagos_estado (estado),
  ADD CONSTRAINT fk_pagos_anulado_por FOREIGN KEY (anulado_por)
    REFERENCES usuario (idusuario) ON UPDATE CASCADE;

CREATE TABLE auditoria (
  id_auditoria INT(11) NOT NULL AUTO_INCREMENT,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_usuario INT(11) DEFAULT NULL,
  accion VARCHAR(50) NOT NULL,
  modulo VARCHAR(80) NOT NULL,
  tabla_afectada VARCHAR(80) NOT NULL,
  id_registro VARCHAR(80) DEFAULT NULL,
  datos_anteriores LONGTEXT DEFAULT NULL,
  datos_nuevos LONGTEXT DEFAULT NULL,
  PRIMARY KEY (id_auditoria),
  KEY idx_auditoria_fecha (fecha),
  KEY idx_auditoria_modulo (modulo, fecha),
  KEY idx_auditoria_usuario (id_usuario, fecha),
  CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitud_recuperacion (
  id_solicitud INT(11) NOT NULL AUTO_INCREMENT,
  id_usuario INT(11) NOT NULL,
  estado ENUM('pendiente', 'aprobada', 'rechazada', 'expirada', 'usada') NOT NULL DEFAULT 'pendiente',
  hash_temporal VARCHAR(255) DEFAULT NULL,
  solicitado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aprobado_en DATETIME DEFAULT NULL,
  expira_en DATETIME DEFAULT NULL,
  autorizado_por INT(11) DEFAULT NULL,
  usado_en DATETIME DEFAULT NULL,
  PRIMARY KEY (id_solicitud),
  KEY idx_recuperacion_estado (estado, solicitado_en),
  KEY idx_recuperacion_usuario (id_usuario, estado),
  CONSTRAINT fk_recuperacion_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuario (idusuario) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_recuperacion_admin FOREIGN KEY (autorizado_por)
    REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE usuario
  ADD COLUMN correo VARCHAR(254) DEFAULT NULL,
  ADD UNIQUE KEY uq_usuario_correo (correo),
  ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0;

CREATE TRIGGER auditoria_impedir_actualizacion
BEFORE UPDATE ON auditoria
FOR EACH ROW
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El historial de auditoria es inmutable';

CREATE TRIGGER auditoria_impedir_eliminacion
BEFORE DELETE ON auditoria
FOR EACH ROW
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El historial de auditoria es inmutable';

-- Configura SMTP/PHPMailer solo desde secretos locales fuera del repositorio.
-- No agregues credenciales de Gmail a este archivo ni al código fuente.
