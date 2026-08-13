-- Stores generated PDF metadata and download tokens.

CREATE TABLE IF NOT EXISTS bitacora_pdfs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  token CHAR(64) NOT NULL,
  idEmpresa INT NOT NULL,
  sede VARCHAR(120) NOT NULL,
  fecha_bitacora DATE NULL,
  responsable VARCHAR(160) NULL,
  created_by VARCHAR(120) NOT NULL,
  relative_path VARCHAR(500) NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY bitacora_pdfs_token_unique (token),
  KEY bitacora_pdfs_empresa_created_idx (idEmpresa, created_by, creado_en),
  CONSTRAINT bitacora_pdfs_empresa_fk FOREIGN KEY (idEmpresa) REFERENCES razones_sociales(id) ON UPDATE CASCADE
);
