-- Stores audit events for bitacora form administration changes.

CREATE TABLE IF NOT EXISTS bitacora_admin_audit (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  idEmpresa INT NOT NULL,
  usuario VARCHAR(120) NOT NULL,
  accion VARCHAR(80) NOT NULL,
  objetivo VARCHAR(160) NULL,
  detalle_json JSON NULL,
  ip VARCHAR(45) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY bitacora_admin_audit_empresa_idx (idEmpresa, creado_en),
  KEY bitacora_admin_audit_usuario_idx (usuario, creado_en),
  KEY bitacora_admin_audit_accion_idx (accion, creado_en),
  CONSTRAINT bitacora_admin_audit_empresa_fk FOREIGN KEY (idEmpresa) REFERENCES razones_sociales(id) ON UPDATE CASCADE
);
