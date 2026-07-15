-- Seeds config_json with the current PHP-backed schema reference.
-- Add future dynamic fields under `dynamic_fields` per company.

INSERT IGNORE INTO bitacora_empresa_config (idEmpresa, tipo_formulario, config_json) VALUES
  (1, 'operational', JSON_OBJECT('schema', 'operational_current', 'dynamic_fields', JSON_ARRAY())),
  (2, 'operational', JSON_OBJECT('schema', 'operational_current', 'dynamic_fields', JSON_ARRAY())),
  (3, 'operational', JSON_OBJECT('schema', 'operational_current', 'dynamic_fields', JSON_ARRAY())),
  (4, 'operational', JSON_OBJECT('schema', 'operational_current', 'dynamic_fields', JSON_ARRAY())),
  (5, 'operational', JSON_OBJECT('schema', 'operational_current', 'dynamic_fields', JSON_ARRAY())),
  (6, 'supervision', JSON_OBJECT('schema', 'supervision_current', 'dynamic_fields', JSON_ARRAY())),
  (7, 'operational', JSON_OBJECT('schema', 'operational_current', 'dynamic_fields', JSON_ARRAY())),
  (8, 'operational', JSON_OBJECT('schema', 'operational_current', 'dynamic_fields', JSON_ARRAY()));
