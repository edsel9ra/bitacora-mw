-- Foundational schema required before the unified bitacora migrations.

CREATE TABLE IF NOT EXISTS razones_sociales (
  id INT NOT NULL PRIMARY KEY,
  empresa VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sedes (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  sede VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios_login (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL,
  usuario VARCHAR(25) NOT NULL,
  email VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  idEmpresa INT NOT NULL,
  rol ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
  fecha_creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  idSede INT NOT NULL,
  UNIQUE KEY usuarios_login_usuario_unique (usuario),
  UNIQUE KEY usuarios_login_email_unique (email),
  KEY usuarios_login_empresa_idx (idEmpresa),
  KEY usuarios_login_sede_idx (idSede),
  CONSTRAINT usuarios_login_empresa_fk FOREIGN KEY (idEmpresa) REFERENCES razones_sociales(id) ON UPDATE CASCADE,
  CONSTRAINT usuarios_login_sede_fk FOREIGN KEY (idSede) REFERENCES sedes(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO razones_sociales (id, empresa) VALUES
  (1, 'MES GROUP'),
  (2, 'MES SOLUCIONES HCQC'),
  (3, 'LES GROUP'),
  (4, 'INVERSIONES VALQUIN'),
  (5, 'LEBOR'),
  (6, 'MES GROUP SAS -ADMIN'),
  (7, 'MES GROUP - TRILOGIA'),
  (8, 'MES DEV');

INSERT IGNORE INTO sedes (id, sede) VALUES
  (1, 'Mister Wings Ciudad Jardin'),
  (2, 'Mister Wings Pance'),
  (3, 'Mister Wings Limonar'),
  (4, 'Mister Wings Too Jardin Plaza'),
  (5, 'Mister Wings San Fernando'),
  (6, 'Mister Wings Too Chipichape'),
  (7, 'Mister Wings Granada'),
  (8, 'Mister Wings Flora'),
  (9, 'Mister Wings Unicentro'),
  (10, 'Mister Wings Llanogrande'),
  (11, 'Oficina Administrativa'),
  (12, 'Mister Wings Bochalema');
