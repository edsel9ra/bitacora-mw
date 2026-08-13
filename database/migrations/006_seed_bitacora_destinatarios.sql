-- Seeds bitacora_destinatarios from public/config/bitacora.php recipients.
-- Global recipients use idSede NULL; sede-specific recipients resolve idSede from empresa_sedes.valor_form.

SET NAMES utf8mb4;

CREATE TEMPORARY TABLE tmp_bitacora_destinatarios_seed (
  idEmpresa INT NOT NULL,
  valor_form VARCHAR(80) NULL,
  tipo ENUM('to','cc','bcc') NOT NULL,
  email VARCHAR(120) NOT NULL
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO tmp_bitacora_destinatarios_seed (idEmpresa, valor_form, tipo, email) VALUES
  (1, NULL, 'to', 'coordinador.sistemas@misterwings.com'),
  (1, NULL, 'to', 'subgerente@misterwings.com'),
  (1, NULL, 'to', 'coordinadora.sg-sst@misterwings.com'),
  (1, NULL, 'to', 'supervisora.cocinas@misterwings.com'),
  (1, NULL, 'to', 'operaciones.supervisor@misterwings.com'),
  (1, NULL, 'to', 'director.franquicias@misterwings.com'),
  (1, NULL, 'to', 'gerencia@misterwings.com'),
  (1, NULL, 'to', 'auxiliar.sg-sst@misterwings.com'),
  (1, NULL, 'to', 'coord.inventarios@misterwings.com'),
  (1, NULL, 'to', 'supervisor.cocinas2@misterwings.com'),
  (1, NULL, 'to', 'supervisor.cocinas@misterwings.com'),
  (1, NULL, 'to', 'auxiliar1.sg-sst@misterwings.com'),
  (1, NULL, 'to', 'comercial.gerencia@misterwings.com'),
  (1, NULL, 'to', 'colquingroup@hotmail.com'),
  (1, NULL, 'to', 'presidencia@misterwings.com'),
  (1, NULL, 'to', 'asistente.operativo@misterwings.com'),
  (1, NULL, 'to', 'director.administrativosedes@misterwings.com'),
  (1, 'PANCE', 'to', 'adminpance@misterwings.com'),
  (1, 'PANCE', 'to', 'pance@misterwings.com'),
  (1, 'PANCE', 'to', 'invpance@misterwings.com'),
  (1, 'CIUDAD JARDÍN', 'to', 'adminciudadjardin@misterwings.com'),
  (1, 'CIUDAD JARDÍN', 'to', 'ciudadjardin@misterwings.com'),
  (1, 'CIUDAD JARDÍN', 'to', 'invciudadjardin@misterwings.com'),
  (1, 'JARDÍN PLAZA', 'to', 'adminjardinplaza@misterwings.com'),
  (1, 'JARDÍN PLAZA', 'to', 'jardinplaza@misterwings.com'),
  (1, 'JARDÍN PLAZA', 'to', 'invjardinplaza@misterwings.com'),
  (1, 'BOCHALEMA', 'to', 'adminbochalema@misterwings.com'),
  (1, 'BOCHALEMA', 'to', 'coor.bochalema@misterwings.com'),
  (1, 'BOCHALEMA', 'to', 'bochalema@misterwings.com'),
  (1, 'BOCHALEMA', 'to', 'invbochalema@misterwings.com'),
  (1, 'UNICENTRO', 'to', 'admin.unicentro@misterwings.com'),
  (1, 'UNICENTRO', 'to', 'unicentro@misterwings.com'),
  (1, 'UNICENTRO', 'to', 'invunicentro@misterwings.com'),

  (2, NULL, 'to', 'coordinador.sistemas@misterwings.com'),
  (2, NULL, 'to', 'subgerente@misterwings.com'),
  (2, NULL, 'to', 'coordinadora.sg-sst@misterwings.com'),
  (2, NULL, 'to', 'supervisora.cocinas@misterwings.com'),
  (2, NULL, 'to', 'operaciones.supervisor@misterwings.com'),
  (2, NULL, 'to', 'director.franquicias@misterwings.com'),
  (2, NULL, 'to', 'gerencia@misterwings.com'),
  (2, NULL, 'to', 'auxiliar.sg-sst@misterwings.com'),
  (2, NULL, 'to', 'coord.inventarios@misterwings.com'),
  (2, NULL, 'to', 'supervisor.cocinas2@misterwings.com'),
  (2, NULL, 'to', 'supervisor.cocinas@misterwings.com'),
  (2, NULL, 'to', 'auxiliar1.sg-sst@misterwings.com'),
  (2, NULL, 'to', 'comercial.gerencia@misterwings.com'),
  (2, NULL, 'to', 'colquingroup@hotmail.com'),
  (2, NULL, 'to', 'presidencia@misterwings.com'),
  (2, NULL, 'to', 'asistente.operativo@misterwings.com'),
  (2, NULL, 'to', 'granada@misterwings.com'),
  (2, NULL, 'to', 'admingranada@misterwings.com'),
  (2, NULL, 'to', 'coor.granada@misterwings.com'),
  (2, NULL, 'to', 'invgranada@misterwings.com'),

  (3, NULL, 'to', 'coordinador.sistemas@misterwings.com'),
  (3, NULL, 'to', 'subgerente@misterwings.com'),
  (3, NULL, 'to', 'coordinadora.sg-sst@misterwings.com'),
  (3, NULL, 'to', 'supervisora.cocinas@misterwings.com'),
  (3, NULL, 'to', 'operaciones.supervisor@misterwings.com'),
  (3, NULL, 'to', 'director.franquicias@misterwings.com'),
  (3, NULL, 'to', 'gerencia@misterwings.com'),
  (3, NULL, 'to', 'auxiliar.sg-sst@misterwings.com'),
  (3, NULL, 'to', 'coord.inventarios@misterwings.com'),
  (3, NULL, 'to', 'supervisor.cocinas2@misterwings.com'),
  (3, NULL, 'to', 'supervisor.cocinas@misterwings.com'),
  (3, NULL, 'to', 'auxiliar1.sg-sst@misterwings.com'),
  (3, NULL, 'to', 'comercial.gerencia@misterwings.com'),
  (3, NULL, 'to', 'colquingroup@hotmail.com'),
  (3, NULL, 'to', 'presidencia@misterwings.com'),
  (3, NULL, 'to', 'asistente.operativo@misterwings.com'),
  (3, NULL, 'to', 'adminlaflora@misterwings.com'),
  (3, NULL, 'to', 'lenisalvaro@hotmail.com'),
  (3, NULL, 'to', 'contabilidad.valquin.les@misterwings.com'),
  (3, NULL, 'to', 'esquin@hotmail.com'),
  (3, NULL, 'to', 'aux.contable.les@misterwings.com'),
  (3, NULL, 'to', 'invflora@misterwings.com'),
  (3, 'CHIPICHAPE', 'to', 'Adminchipichape@misterwings.com'),
  (3, 'CHIPICHAPE', 'to', 'chipichape@misterwings.com'),
  (3, 'FLORA', 'to', 'coordinadorflora@misterwings.com'),
  (3, 'FLORA', 'to', 'laflora@misterwings.com'),

  (4, NULL, 'to', 'coordinador.sistemas@misterwings.com'),
  (4, NULL, 'to', 'subgerente@misterwings.com'),
  (4, NULL, 'to', 'coordinadora.sg-sst@misterwings.com'),
  (4, NULL, 'to', 'supervisora.cocinas@misterwings.com'),
  (4, NULL, 'to', 'operaciones.supervisor@misterwings.com'),
  (4, NULL, 'to', 'director.franquicias@misterwings.com'),
  (4, NULL, 'to', 'gerencia@misterwings.com'),
  (4, NULL, 'to', 'auxiliar.sg-sst@misterwings.com'),
  (4, NULL, 'to', 'coord.inventarios@misterwings.com'),
  (4, NULL, 'to', 'supervisor.cocinas2@misterwings.com'),
  (4, NULL, 'to', 'supervisor.cocinas@misterwings.com'),
  (4, NULL, 'to', 'auxiliar1.sg-sst@misterwings.com'),
  (4, NULL, 'to', 'comercial.gerencia@misterwings.com'),
  (4, NULL, 'to', 'colquingroup@hotmail.com'),
  (4, NULL, 'to', 'presidencia@misterwings.com'),
  (4, NULL, 'to', 'asistente.operativo@misterwings.com'),
  (4, NULL, 'to', 'contabilidad.valquin.les@misterwings.com'),
  (4, NULL, 'to', 'esquin@hotmail.com'),
  (4, NULL, 'to', 'lenisalvaro@hotmail.com'),
  (4, NULL, 'to', 'contabilidad-sanfernando@misterwings.com'),
  (4, NULL, 'to', 'aux.contable.valquin@misterwings.com'),
  (4, 'LIMONAR', 'to', 'adminlimonar@misterwings.com'),
  (4, 'LIMONAR', 'to', 'limonar@misterwings.com'),
  (4, 'LIMONAR', 'to', 'invpalmetto@misterwings.com'),
  (4, 'SAN FERNANDO', 'to', 'adminsanfernando@misterwings.com'),
  (4, 'SAN FERNANDO', 'to', 'sanfernando@misterwings.com'),
  (4, 'SAN FERNANDO', 'to', 'inv.sanfernando@misterwings.com'),

  (5, NULL, 'to', 'coordinador.sistemas@misterwings.com'),
  (5, NULL, 'to', 'subgerente@misterwings.com'),
  (5, NULL, 'to', 'coordinadora.sg-sst@misterwings.com'),
  (5, NULL, 'to', 'supervisora.cocinas@misterwings.com'),
  (5, NULL, 'to', 'operaciones.supervisor@misterwings.com'),
  (5, NULL, 'to', 'director.franquicias@misterwings.com'),
  (5, NULL, 'to', 'gerencia@misterwings.com'),
  (5, NULL, 'to', 'mantenimiento@misterwings.com'),
  (5, NULL, 'to', 'auxiliar.sg-sst@misterwings.com'),
  (5, NULL, 'to', 'coord.inventarios@misterwings.com'),
  (5, NULL, 'to', 'comercial@misterwings.com'),
  (5, NULL, 'to', 'supervisor.cocinas2@misterwings.com'),
  (5, NULL, 'to', 'supervisor.cocinas@misterwings.com'),
  (5, NULL, 'to', 'auxiliar1.sg-sst@misterwings.com'),
  (5, NULL, 'to', 'comercial.gerencia@misterwings.com'),
  (5, NULL, 'to', 'colquingroup@hotmail.com'),
  (5, NULL, 'to', 'presidencia@misterwings.com'),
  (5, NULL, 'to', 'asistente.operativo@misterwings.com'),
  (5, NULL, 'to', 'cajallanogrande@misterwings.com'),
  (5, NULL, 'to', 'coordinadorllanogrande@misterwings.com'),
  (5, NULL, 'to', 'contabilidad.valquin.les@misterwings.com'),
  (5, NULL, 'to', 'esquin@hotmail.com'),
  (5, NULL, 'to', 'lenisalvaro@hotmail.com'),
  (5, NULL, 'to', 'contabilidad-sanfernando@misterwings.com'),
  (5, NULL, 'to', 'inv.llanogrande@misterwings.com'),
  (5, NULL, 'to', 'adminllanogrande@misterwings.com'),

  (8, NULL, 'to', 'coordinador.sistemas@misterwings.com'),
  (8, NULL, 'bcc', 'coordinador.sistemas@misterwings.com');

UPDATE bitacora_destinatarios bd
JOIN (
  SELECT DISTINCT idEmpresa, valor_form, tipo, email
  FROM tmp_bitacora_destinatarios_seed
) seed ON seed.idEmpresa = bd.idEmpresa
  AND seed.tipo = bd.tipo
  AND seed.email = bd.email
LEFT JOIN (
  SELECT idEmpresa, valor_form, MIN(idSede) AS idSede
  FROM empresa_sedes
  WHERE activo = 1
  GROUP BY idEmpresa, valor_form
) es ON es.idEmpresa = seed.idEmpresa
  AND es.valor_form = seed.valor_form
SET bd.activo = 1
WHERE (seed.valor_form IS NULL AND bd.idSede IS NULL)
   OR (seed.valor_form IS NOT NULL AND bd.idSede = es.idSede);

INSERT INTO bitacora_destinatarios (idEmpresa, idSede, tipo, email, activo)
SELECT seed.idEmpresa, es.idSede, seed.tipo, seed.email, 1
FROM (
  SELECT DISTINCT idEmpresa, valor_form, tipo, email
  FROM tmp_bitacora_destinatarios_seed
) seed
LEFT JOIN (
  SELECT idEmpresa, valor_form, MIN(idSede) AS idSede
  FROM empresa_sedes
  WHERE activo = 1
  GROUP BY idEmpresa, valor_form
) es ON es.idEmpresa = seed.idEmpresa
  AND es.valor_form = seed.valor_form
WHERE (seed.valor_form IS NULL OR es.idSede IS NOT NULL)
  AND NOT EXISTS (
    SELECT 1
    FROM bitacora_destinatarios bd
    WHERE bd.idEmpresa = seed.idEmpresa
      AND bd.tipo = seed.tipo
      AND bd.email = seed.email
      AND (
        (seed.valor_form IS NULL AND bd.idSede IS NULL)
        OR (seed.valor_form IS NOT NULL AND bd.idSede = es.idSede)
      )
  );

DROP TEMPORARY TABLE tmp_bitacora_destinatarios_seed;
