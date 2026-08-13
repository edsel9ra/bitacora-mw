-- Stores one encrypted draft per user, company and form type.

CREATE TABLE IF NOT EXISTS bitacora_borradores (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  token CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  idUsuario INT NOT NULL,
  idEmpresa INT NOT NULL,
  tipo_formulario ENUM('operational','supervision') NOT NULL,
  schema_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  ciphertext MEDIUMBLOB NOT NULL,
  iv VARBINARY(12) NOT NULL,
  tag VARBINARY(16) NOT NULL,
  key_version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  version INT UNSIGNED NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  UNIQUE KEY bitacora_borradores_token_unique (token),
  UNIQUE KEY bitacora_borradores_owner_unique (idUsuario, idEmpresa, tipo_formulario),
  KEY bitacora_borradores_expires_idx (expires_at),
  CONSTRAINT bitacora_borradores_usuario_fk FOREIGN KEY (idUsuario) REFERENCES usuarios_login(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT bitacora_borradores_empresa_fk FOREIGN KEY (idEmpresa) REFERENCES razones_sociales(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
