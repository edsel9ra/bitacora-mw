-- Allows assigning one or more bitacora sections to specific recipients.

CREATE TABLE IF NOT EXISTS bitacora_seccion_destinatarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  idEmpresa INT NOT NULL,
  idSede INT NULL,
  section_key VARCHAR(80) NOT NULL,
  tipo ENUM('to','cc','bcc') NOT NULL DEFAULT 'to',
  email VARCHAR(120) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY bitacora_seccion_dest_unique (idEmpresa, idSede, section_key, tipo, email),
  KEY bitacora_seccion_dest_empresa_sede_idx (idEmpresa, idSede),
  KEY bitacora_seccion_dest_section_idx (section_key),
  CONSTRAINT bitacora_seccion_dest_empresa_fk FOREIGN KEY (idEmpresa) REFERENCES razones_sociales(id) ON UPDATE CASCADE,
  CONSTRAINT bitacora_seccion_dest_sede_fk FOREIGN KEY (idSede) REFERENCES sedes(id) ON UPDATE CASCADE
);
