-- Supports the unified bitacora view without removing the current PHP config fallback.

ALTER TABLE usuarios_login MODIFY password VARCHAR(255) NOT NULL;

CREATE TABLE IF NOT EXISTS empresa_sedes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  idEmpresa INT NOT NULL,
  idSede INT NOT NULL,
  valor_form VARCHAR(80) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 0,
  UNIQUE KEY empresa_sede_valor_unique (idEmpresa, idSede, valor_form),
  CONSTRAINT empresa_sedes_empresa_fk FOREIGN KEY (idEmpresa) REFERENCES razones_sociales(id) ON UPDATE CASCADE,
  CONSTRAINT empresa_sedes_sede_fk FOREIGN KEY (idSede) REFERENCES sedes(id) ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS bitacora_destinatarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  idEmpresa INT NOT NULL,
  idSede INT NULL,
  tipo ENUM('to','cc','bcc') NOT NULL DEFAULT 'to',
  email VARCHAR(120) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY bitacora_destinatario_unique (idEmpresa, idSede, tipo, email),
  CONSTRAINT bitacora_destinatarios_empresa_fk FOREIGN KEY (idEmpresa) REFERENCES razones_sociales(id) ON UPDATE CASCADE,
  CONSTRAINT bitacora_destinatarios_sede_fk FOREIGN KEY (idSede) REFERENCES sedes(id) ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS bitacora_empresa_config (
  idEmpresa INT PRIMARY KEY,
  tipo_formulario VARCHAR(40) NOT NULL,
  config_json JSON NOT NULL,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT bitacora_empresa_config_empresa_fk FOREIGN KEY (idEmpresa) REFERENCES razones_sociales(id) ON UPDATE CASCADE
);

INSERT IGNORE INTO empresa_sedes (idEmpresa, idSede, valor_form, orden) VALUES
  (1, 2, 'PANCE', 10),
  (1, 1, 'CIUDAD JARDÍN', 20),
  (1, 4, 'JARDÍN PLAZA', 30),
  (1, 12, 'BOCHALEMA', 40),
  (2, 7, 'GRANADA', 10),
  (3, 6, 'CHIPICHAPE', 10),
  (3, 8, 'FLORA', 20),
  (4, 3, 'LIMONAR', 10),
  (4, 5, 'SAN FERNANDO', 20),
  (5, 10, 'LLANOGRANDE', 10),
  (6, 2, 'Pance', 10),
  (6, 1, 'Ciudad Jardín', 20),
  (6, 4, 'Jardín Plaza', 30),
  (6, 9, 'Unicentro', 40),
  (6, 3, 'Limonar', 50),
  (6, 5, 'San Fernando', 60),
  (6, 7, 'Granada', 70),
  (6, 6, 'Chipichape', 80),
  (6, 8, 'Flora', 90),
  (6, 10, 'Llanogrande', 100),
  (6, 12, 'Bochalema', 110),
  (7, 9, 'UNICENTRO - TRILOGIA', 10),
  (8, 2, 'PANCE', 10),
  (8, 1, 'CIUDAD JARDÍN', 20),
  (8, 4, 'JARDÍN PLAZA', 30),
  (8, 12, 'BOCHALEMA', 40);
