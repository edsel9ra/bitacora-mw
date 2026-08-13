-- Queues bitacora emails for asynchronous delivery by a CLI worker.

ALTER TABLE bitacora_envios
  MODIFY estado ENUM('procesando','pendiente','completado','parcial','fallido') NOT NULL DEFAULT 'procesando';

CREATE TABLE IF NOT EXISTS bitacora_email_queue (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  idEnvio BIGINT NULL,
  idEmpresa INT NOT NULL,
  sede VARCHAR(120) NOT NULL,
  usuario VARCHAR(120) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body_html MEDIUMTEXT NOT NULL,
  recipients_json JSON NOT NULL,
  attachments_json JSON NULL,
  estado ENUM('pendiente','procesando','enviado','fallido') NOT NULL DEFAULT 'pendiente',
  attempts INT NOT NULL DEFAULT 0,
  max_attempts INT NOT NULL DEFAULT 3,
  available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  locked_by VARCHAR(120) NULL,
  locked_at DATETIME NULL,
  sent_at DATETIME NULL,
  last_error TEXT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY bitacora_email_queue_estado_idx (estado, available_at, attempts),
  KEY bitacora_email_queue_envio_idx (idEnvio),
  KEY bitacora_email_queue_empresa_idx (idEmpresa, creado_en),
  CONSTRAINT bitacora_email_queue_envio_fk FOREIGN KEY (idEnvio) REFERENCES bitacora_envios(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT bitacora_email_queue_empresa_fk FOREIGN KEY (idEmpresa) REFERENCES razones_sociales(id) ON UPDATE CASCADE
);
