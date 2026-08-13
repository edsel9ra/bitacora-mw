-- Stores bitacora send attempts and their final processing state.

CREATE TABLE IF NOT EXISTS bitacora_envios (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  idEmpresa INT NOT NULL,
  sede VARCHAR(120) NOT NULL,
  fecha_bitacora DATE NULL,
  usuario VARCHAR(120) NOT NULL,
  responsable VARCHAR(160) NULL,
  tipo_formulario VARCHAR(40) NOT NULL DEFAULT 'operational',
  estado ENUM('procesando','completado','parcial','fallido') NOT NULL DEFAULT 'procesando',
  correo_enviado TINYINT(1) NOT NULL DEFAULT 0,
  pdf_generado TINYINT(1) NOT NULL DEFAULT 0,
  correos_seccion_enviados INT NOT NULL DEFAULT 0,
  pdf_id BIGINT NULL,
  error_mensaje TEXT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY bitacora_envios_empresa_fecha_idx (idEmpresa, fecha_bitacora, creado_en),
  KEY bitacora_envios_usuario_idx (usuario, creado_en),
  KEY bitacora_envios_estado_idx (estado, creado_en),
  CONSTRAINT bitacora_envios_empresa_fk FOREIGN KEY (idEmpresa) REFERENCES razones_sociales(id) ON UPDATE CASCADE,
  CONSTRAINT bitacora_envios_pdf_fk FOREIGN KEY (pdf_id) REFERENCES bitacora_pdfs(id) ON DELETE SET NULL ON UPDATE CASCADE
);
