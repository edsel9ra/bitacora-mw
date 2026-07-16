-- Supports the unified bitacora view without removing the current PHP config fallback.
--
-- Estructura de tabla para la tabla `razones_sociales`
--

CREATE TABLE `razones_sociales` (
  `id` int(11) NOT NULL,
  `empresa` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `razones_sociales`
--

INSERT INTO `razones_sociales` (`id`, `empresa`) VALUES
(1, 'MES GROUP'),
(2, 'MES SOLUCIONES HCQC'),
(3, 'LES GROUP'),
(4, 'INVERSIONES VALQUIN'),
(5, 'LEBOR'),
(6, 'MES GROUP SAS -ADMIN'),
(7, 'MES GROUP - TRILOGIA'),
(8, 'MES DEV');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes`
--

CREATE TABLE `sedes` (
  `id` int(11) NOT NULL,
  `sede` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Volcado de datos para la tabla `sedes`
--

INSERT INTO `sedes` (`id`, `sede`) VALUES
(1, 'Mister Wings Ciudad Jardin'),
(2, 'Mister Wings Pance'),
(3, 'Mister Wings Limonar'),
(4, 'Mister Wings Too Jardín Plaza'),
(5, 'Mister Wings San Fernando'),
(6, 'Mister Wings Too Chipichape'),
(7, 'Mister Wings Granada'),
(8, 'Mister Wings Flora'),
(9, 'Mister Wings Unicentro'),
(10, 'Mister Wings Llanogrande'),
(11, 'Oficina Administrativa'),
(12, 'Mister Wings Bochalema');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_login`
--

CREATE TABLE `usuarios_login` (
  `id` int(20) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `usuario` varchar(25) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `idEmpresa` int(11) NOT NULL,
  `fecha_creado` datetime NOT NULL,
  `idSede` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios_login`
--

INSERT INTO `usuarios_login` (`id`, `nombre`, `usuario`, `email`, `password`, `idEmpresa`, `fecha_creado`, `idSede`) VALUES
(1, 'Develop Account', 'dev', 'coordinador.sistemas@misterwings.com', '367189c4a0c25fd18499b83776783828', 8, '2020-12-02 09:15:18', 11),
(2, 'Mister Wings Ciudad Jardín', 'mwcj', 'ciudadjardin@misterwings.com', '9cf328c6356a90c757517c070f8b2278', 1, '2021-03-09 14:12:00', 1),
(3, 'Mister Wings Pance', 'mwpance', 'pance@misterwings.com', 'd1db3ba54a6871de364ba29fbf20261a', 1, '2021-03-09 19:59:20', 2),
(4, 'Mister Wings Jardín Plaza', 'mwjp', 'jardinplaza@misterwings.com', 'f26755ab392ab18913bb9b37bdcb6d94', 1, '2021-03-09 19:59:30', 4),
(5, 'Mister Wings Granada', 'mwgranada', 'granada@misterwings.com', '62c3ac51a2d768a464f8bf875f98b359', 2, '2021-03-09 19:59:35', 7),
(6, 'Mister Wings Too Chipichape', 'mwchipichape', 'chipichape@misterwings.com', '9c869fafb3711a9f6fa5c20d7f81b220', 3, '2021-03-09 19:59:39', 6),
(7, 'Mister Wings Flora', 'mwflora', 'flora@misterwings.com', 'e3b8ac44228a9bd572ccb64017893997', 3, '2021-03-09 19:59:43', 8),
(8, 'Mister Wings Limonar', 'mwlimonar', 'palmetto@misterwings.com', 'b9a03e64ccf727b7e9ad7ff91d880613', 4, '2021-03-09 19:59:48', 3),
(9, 'Mister Wings San Fernando', 'mwsf', 'sanfernando@misterwings.com', '6b7fc412dd0912e28e59733bc5b0fb0e', 4, '2021-03-09 19:59:52', 5),
(10, 'Mister Wings Unicentro', 'mwunicentro', 'unicentro@misterwings.com', '392773afbc37139f5721f2a3fd676203', 7, '2022-01-22 02:12:29', 9),
(11, 'Mister Wings Llanogrande', 'mwllanogrande', 'coordinadorllanogrande@misterwin', '38acc7c16be115c48cce5a95d038e94e', 5, '2022-01-22 02:13:15', 10),
(12, 'Supervisores Sedes', 'supervisor', 'servicioalcliente@misterwings.co', '838028e51411b67609c2b89adddd7c18', 6, '2022-10-31 11:34:00', 11),
(13, 'Mister Wings Bochalema', 'mwbochalema', 'bochalema@misterwings.com', 'd7325310c86c46a16198853e9020e46a', 1, '2024-09-04 16:48:21', 12);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `razones_sociales`
--
ALTER TABLE `razones_sociales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sedes`
--
ALTER TABLE `sedes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios_login`
--
ALTER TABLE `usuarios_login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idEmpresa` (`idEmpresa`),
  ADD KEY `idSede` (`idSede`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `sedes`
--
ALTER TABLE `sedes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `usuarios_login`
--
ALTER TABLE `usuarios_login`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `usuarios_login`
--
ALTER TABLE `usuarios_login`
  ADD CONSTRAINT `usuarios_login_ibfk_1` FOREIGN KEY (`idEmpresa`) REFERENCES `razones_sociales` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `usuarios_login_ibfk_2` FOREIGN KEY (`idSede`) REFERENCES `sedes` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;
COMMIT;

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
