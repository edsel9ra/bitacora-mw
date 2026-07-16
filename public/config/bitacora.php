<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

function app_bitacora_common_recipients(): array
{
    return [
        'coordinador.sistemas@misterwings.com',
        'soporte@misterwings.com',
        'subgerente@misterwings.com',
        'coordinadora.sg-sst@misterwings.com',
        'jefegestionhumana@misterwings.com',
        'ambiental@misterwings.com',
        'gestionhumana@misterwings.com',
        'supervisora.cocinas@misterwings.com',
        'operaciones.supervisor@misterwings.com',
        'coordinador.operaciones@misterwings.com',
        'mejoramiento@misterwings.com',
        'mercadeo@misterwings.com',
        'gerencia@misterwings.com',
        'mantenimiento@misterwings.com',
        'auxiliar.sg-sst@misterwings.com',
        'coord.inventarios@misterwings.com',
        'visual@misterwings.com',
        'comercial@misterwings.com',
        'supervisor.comercial@misterwings.com',
        'supervisor.cocinas2@misterwings.com',
        'capacitacionmw@misterwings.com',
        'auxiliar1.sg-sst@misterwings.com',
        'auxiliar.sistemas@misterwings.com',
        'comercial.gerencia@misterwings.com',
        'colquingroup@hotmail.com',
        'aux.gestionhumana@misterwings.com',
        'coordinador.procesos@misterwings.com',
        'apr.mejoramiento@misterwings.com',
        'asistente.operativo@misterwings.com',
        'grafica@misterwings.com',
    ];
}

function app_bitacora_configs(): array
{
    $common = app_bitacora_common_recipients();

    return [
        1 => [
            'slug' => 'mes_group',
            'type' => 'operational',
            'title' => 'Bitacora Mister Wings',
            'sedes' => ['PANCE', 'CIUDAD JARDÍN', 'JARDÍN PLAZA', 'BOCHALEMA'],
            'extras_by_sede' => ['PANCE' => ['chetano']],
            'recipients' => [
                'global' => array_merge($common, [
                    'tesoreria@misterwings.com',
                    'contabilidad@misterwings.com',
                    'aux.tesoreria@misterwings.com',
                    'aux.contable2@misterwings.com',
                    'director.administrativosedes@misterwings.com',
                ]),
                'by_sede' => [
                    'PANCE' => ['adminpance@misterwings.com', 'pance@misterwings.com'],
                    'CIUDAD JARDÍN' => ['adminciudadjardin@misterwings.com', 'ciudadjardin@misterwings.com'],
                    'JARDÍN PLAZA' => ['adminjardinplaza@misterwings.com', 'jardinplaza@misterwings.com'],
                    'BOCHALEMA' => ['adminbochalema@misterwings.com', 'coor.bochalema@misterwings.com', 'bochalema@misterwings.com'],
                ],
            ],
        ],
        2 => [
            'slug' => 'mes_soluciones_hcqc',
            'type' => 'operational',
            'title' => 'Bitacora Mister Wings',
            'sedes' => ['GRANADA'],
            'default_sede' => 'GRANADA',
            'recipients' => [
                'global' => array_merge($common, [
                    'granada@misterwings.com',
                    'admingranada@misterwings.com',
                    'coor.granada@misterwings.com',
                    'tesoreria@misterwings.com',
                    'contabilidad@misterwings.com',
                    'aux.tesoreria@misterwings.com',
                    'aux.contable2@misterwings.com',
                ]),
                'by_sede' => [],
            ],
        ],
        3 => [
            'slug' => 'les_group',
            'type' => 'operational',
            'title' => 'Bitacora Mister Wings',
            'sedes' => ['CHIPICHAPE', 'FLORA'],
            'recipients' => [
                'global' => array_merge($common, [
                    'adminlaflora@misterwings.com',
                    'lenisalvaro@hotmail.com',
                    'contabilidad.valquin.les@misterwings.com',
                    'esquin@hotmail.com',
                ]),
                'by_sede' => [
                    'CHIPICHAPE' => ['Adminchipichape@misterwings.com', 'chipichape@misterwings.com'],
                    'FLORA' => ['coordinadorflora@misterwings.com', 'laflora@misterwings.com'],
                ],
            ],
        ],
        4 => [
            'slug' => 'inversiones_valquin',
            'type' => 'operational',
            'title' => 'Bitacora Mister Wings',
            'sedes' => ['LIMONAR', 'SAN FERNANDO'],
            'recipients' => [
                'global' => array_merge($common, [
                    'contabilidad.valquin.les@misterwings.com',
                    'esquin@hotmail.com',
                    'lenisalvaro@hotmail.com',
                    'contabilidad-sanfernando@misterwings.com',
                ]),
                'by_sede' => [
                    'LIMONAR' => ['adminlimonar@misterwings.com', 'limonar@misterwings.com'],
                    'SAN FERNANDO' => ['adminsanfernando@misterwings.com', 'sanfernando@misterwings.com'],
                ],
            ],
        ],
        5 => [
            'slug' => 'lebor_sas',
            'type' => 'operational',
            'title' => 'Bitacora Mister Wings',
            'sedes' => ['LLANOGRANDE'],
            'default_sede' => 'LLANOGRANDE',
            'extras' => ['reunion_calidad'],
            'recipients' => [
                'global' => array_merge($common, [
                    'cajallanogrande@misterwings.com',
                    'coordinadorllanogrande@misterwings.com',
                    'contabilidad.valquin.les@misterwings.com',
                    'esquin@hotmail.com',
                    'lenisalvaro@hotmail.com',
                    'contabilidad-sanfernando@misterwings.com',
                ]),
                'by_sede' => [],
            ],
        ],
        6 => [
            'slug' => 'supervisiones',
            'type' => 'supervision',
            'title' => 'Reporte de supervision',
            'sedes' => ['Pance', 'Ciudad Jardín', 'Jardín Plaza', 'Unicentro', 'Limonar', 'San Fernando', 'Granada', 'Chipichape', 'Flora', 'Llanogrande', 'Bochalema'],
            'recipients' => [
                'global' => ['gerente.administrativo@misterwings.com', 'gerencia@misterwings.com', 'gerente.franquicias@misterwings.com'],
                'cc' => ['supervisor.cocinas2@misterwings.com', 'supervisor.comercial@misterwings.com', 'supervisor.cocinas@misterwings.com', 'operaciones.supervisor@misterwings.com', 'coordinador.operaciones@misterwings.com', 'soporte@misterwings.com'],
                'by_sede' => [
                    'Pance' => ['adminpance@misterwings.com'],
                    'Ciudad Jardín' => ['adminciudadjardin@misterwings.com'],
                    'Jardín Plaza' => ['adminjardinplaza@misterwings.com'],
                    'Unicentro' => ['admin.unicentro@misterwings.com'],
                    'Limonar' => ['esquin@hotmail.com', 'lenisalvaro@hotmail.com', 'adminlimonar@misterwings.com'],
                    'San Fernando' => ['esquin@hotmail.com', 'lenisalvaro@hotmail.com', 'adminsanfernando@misterwings.com'],
                    'Granada' => ['admingranada@misterwings.com', 'coor.granada@misterwings.com'],
                    'Chipichape' => ['esquin@hotmail.com', 'adminlaflora@misterwings.com', 'adminchipichape@misterwings.com'],
                    'Flora' => ['esquin@hotmail.com', 'adminlaflora@misterwings.com', 'coordinadorflora@misterwings.com'],
                    'Llanogrande' => ['esquin@hotmail.com', 'lenisalvaro@hotmail.com', 'coordinadorllanogrande@misterwings.com'],
                    'Bochalema' => ['adminbochalema@misterwings.com', 'coor.bochalema@misterwings.com'],
                ],
            ],
        ],
        7 => [
            'slug' => 'mes_trilogia',
            'type' => 'operational',
            'title' => 'Bitacora Mister Wings',
            'sedes' => ['UNICENTRO - TRILOGIA'],
            'default_sede' => 'UNICENTRO - TRILOGIA',
            'extras' => ['chetano', 'torito'],
            'recipients' => [
                'global' => array_merge($common, [
                    'unicentro@misterwings.com',
                    'admin.unicentro@misterwings.com',
                    'tesoreria@misterwings.com',
                    'contabilidad@misterwings.com',
                    'aux.tesoreria@misterwings.com',
                    'aux.contable2@misterwings.com',
                    'aux.admin.bochalema@misterwings.com',
                    'director.administrativo@misterwings.com',
                    'director.administrativosedes@misterwings.com',
                ]),
                'by_sede' => [],
            ],
        ],
        8 => [
            'slug' => 'mes_test',
            'type' => 'operational',
            'title' => 'Bitacora Mister Wings',
            'sedes' => ['PANCE', 'CIUDAD JARDÍN', 'JARDÍN PLAZA', 'BOCHALEMA'],
            'extras_by_sede' => ['PANCE' => ['chetano']],
            'recipients' => [
                'global' => ['coordinador.sistemas@misterwings.com'],
                'bcc' => ['coordinador.sistemas@misterwings.com'],
                'by_sede' => [],
            ],
        ],
    ];
}

function app_bitacora_config(int $empresaId): ?array
{
    $configs = app_bitacora_configs();
    return $configs[$empresaId] ?? null;
}

function app_bitacora_extra_enabled(array $config, string $extra, string $sede = ''): bool
{
    $sede = trim($sede);
    $extras = $config['extras'] ?? [];
    if (in_array($extra, $extras, true)) {
        return true;
    }

    $extrasBySede = $config['extras_by_sede'] ?? [];
    if ($sede !== '' && isset($extrasBySede[$sede]) && in_array($extra, $extrasBySede[$sede], true)) {
        return true;
    }

    return false;
}

function app_bitacora_add_recipients(PHPMailer $mail, int $empresaId, string $sede): void
{
    $config = app_bitacora_config($empresaId);
    if ($config === null) {
        throw new RuntimeException('Empresa sin configuración de bitácora.');
    }

    $recipients = $config['recipients'] ?? [];
    $sedeRecipients = $recipients['by_sede'][$sede] ?? [];

    foreach (array_unique(array_merge($sedeRecipients, $recipients['global'] ?? [])) as $email) {
        $mail->addAddress($email);
    }

    foreach (array_unique($recipients['cc'] ?? []) as $email) {
        $mail->addCC($email);
    }

    foreach (array_unique($recipients['bcc'] ?? []) as $email) {
        $mail->addBCC($email);
    }
}

function app_bitacora_db_config_json(int $empresaId): array
{
    static $cache = [];
    if (array_key_exists($empresaId, $cache)) {
        return $cache[$empresaId];
    }

    try {
        require_once __DIR__ . '/../bd/conexion.php';
        $pdo = Conexion::Conectar();
        $stmt = $pdo->prepare('SELECT config_json FROM bitacora_empresa_config WHERE idEmpresa = :idEmpresa LIMIT 1');
        $stmt->execute(['idEmpresa' => $empresaId]);
        $json = $stmt->fetchColumn();

        if (!is_string($json) || trim($json) === '') {
            return $cache[$empresaId] = [];
        }

        $decoded = json_decode($json, true);
        return $cache[$empresaId] = is_array($decoded) ? $decoded : [];
    } catch (Throwable $e) {
        error_log('No fue posible leer config_json de bitácora: ' . $e->getMessage());
        return $cache[$empresaId] = [];
    }
}

function app_bitacora_field(string $type, string $name, string $label, array $extra = []): array
{
    return array_merge([
        'type' => $type,
        'name' => $name,
        'label' => $label,
        'required' => true,
    ], $extra);
}

function app_bitacora_yes_no_field(string $name, string $label, string $groupId, string $detailName, string $detailLabel, string $detailType = 'textarea'): array
{
    return app_bitacora_field('yes_no', $name, $label, [
        'group_id' => $groupId,
        'detail_name' => $detailName,
        'detail_label' => $detailLabel,
        'detail_type' => $detailType,
    ]);
}

function app_bitacora_default_form_sections(array $companyConfig, int $empresaId): array
{
    $supervisores = [
        'Brian Alberto Ortiz' => 'Brian Alberto Ortiz - Coordinador de Operaciones',
        'Angela Yuliana Mesa' => 'Angela Yuliana Mesa - Entrenadora de Cocina y Bar',
        ($empresaId === 8 ? 'Maria Conchita Parra' : 'Julia Maria Carabali') => ($empresaId === 8 ? 'Maria Conchita Parra - Entrenadora de Cocina y Bar' : 'Julia Maria Carabali - Entrenadora de Cocina y Bar'),
        'Nicol Muñoz' => 'Nicol Muñoz - Supervisora de Operaciones',
        'No hay visita por parte de los supervisores' => 'No aplica visita',
    ];
    $sst = [
        'Pamela Valencia' => 'Pamela Valencia - Coordinadora de SST',
        'Johanna Findo' => 'Johanna Findo - Auxiliar SST',
        'No hay visita por parte del área' => 'No aplica visita',
    ];
    $bpm = [
        'Fabián Salazar' => 'Fabián Salazar - Coordinador de Mejoramiento',
        'Alejandro Noguera' => 'Alejandro Noguera - Supervisor de Calidad y Ambiental',
        'Juan Diego Franco' => 'Juan Diego Franco - Aprendiz',
        'No hay visita por parte del área' => 'No aplica visita',
    ];
    $afluencia = ['BAJA' => 'AFLUENCIA BAJA', 'MODERADA' => 'AFLUENCIA MODERADA', 'ALTA' => 'AFLUENCIA ALTA'];

    $sections = [
        ['key' => 'base', 'title' => 'Datos básicos', 'fields' => [
            app_bitacora_field('date', 'fechab', 'Fecha de bitácora', ['col' => 'col-md-6']),
            app_bitacora_field('select', 'sede', 'Sede', ['id' => 'idSede', 'options' => $companyConfig['sedes'] ?? [], 'selected' => $companyConfig['default_sede'] ?? null, 'col' => 'col-md-6']),
            app_bitacora_field('text', 'responsable', 'Responsable', ['col' => 'col-md-6']),
            app_bitacora_field('select', 'cargo', 'Cargo', ['options' => ['Coordinador/a', 'Cajero/a'], 'col' => 'col-md-6']),
        ]],
        ['key' => 'operaciones', 'title' => 'Operaciones', 'fields' => [
            app_bitacora_field('textarea', 'sac', 'Servicio al Cliente: Ingrese novedades relacionadas con clientes o quejas de clientes que se presenten en la sede'),
            app_bitacora_field('textarea', 'devo', 'Devoluciones de Producto (Retorno a Cocina)'),
            app_bitacora_field('multiselect', 'supervisores', 'Ingrese los supervisores que hayan visitado la sede en el día, las actividades realizadas, la hora de ingreso y la hora de salida. En caso de que no hayan visitado, seleccionar "No aplica visita"', ['id' => 'supervisores', 'options' => $supervisores, 'required' => false]),
            app_bitacora_field('supervisor_detail', 'act_sup', 'Detalle visita supervisores', ['required' => false]),
        ]],
        ['key' => 'afluencia', 'title' => 'Afluencia de comensales y observaciones', 'fields' => [
            app_bitacora_field('select', 'comens', 'Afluencia medio día', ['options' => $afluencia]),
            app_bitacora_field('select', 'comens1', 'Afluencia tarde', ['options' => $afluencia]),
            app_bitacora_field('select', 'comens2', 'Afluencia noche', ['options' => $afluencia]),
            app_bitacora_field('textarea', 'mesas', 'Observaciones jefe de mesas'),
            app_bitacora_field('textarea', 'bar', 'Observaciones jefe de bar'),
            app_bitacora_field('textarea', 'cocina', 'Observaciones jefe de cocina'),
        ]],
        ['key' => 'mercadeo', 'title' => 'Mercadeo', 'fields' => [
            app_bitacora_field('textarea', 'coc', 'Venta de coctelería'),
            app_bitacora_field('textarea', 'mer', 'Venta de productos foco'),
            app_bitacora_field('textarea', 'mer1', 'Ventas de productos nuevos'),
            app_bitacora_field('textarea', 'mer2', 'Campañas del mes'),
            app_bitacora_field('textarea', 'mer3', 'Casos HelpDesk para Mercadeo'),
            app_bitacora_yes_no_field('reservas_15', '¿Hubo reservas de 10 o más personas?', 'mer4Group', 'mer4', 'Detalle reservas'),
        ]],
        ['key' => 'gestion_humana', 'title' => 'Gestión Humana', 'fields' => [
            app_bitacora_field('textarea', 'gh', 'Ingrese todas las novedades relacionadas con el personal de la sede.'),
        ]],
        ['key' => 'sst', 'title' => 'SST', 'fields' => [
            app_bitacora_yes_no_field('accidentes_sst', '¿Hubo accidentes o incidentes durante la jornada laboral?', 'sst1Group', 'sst1', 'Detalle'),
            app_bitacora_yes_no_field('incapacidades_sst', '¿Hubo incapacidades >= 15 días?', 'sst2Group', 'sst2', 'Detalle'),
            app_bitacora_yes_no_field('ambiente_laboral', '¿Hubo novedades de ambiente laboral?', 'sst3Group', 'sst3', 'Detalle'),
            app_bitacora_yes_no_field('senal_sst', '¿Hubo novedades de extintores/señalización?', 'sst4Group', 'sst4', 'Detalle'),
            app_bitacora_yes_no_field('entrega_epp', '¿Hubo entrega de elementos de protección personal (EPP)?', 'sst6Group', 'sst6', 'Detalle'),
            app_bitacora_yes_no_field('novedades_sst', '¿Hubo otras novedades relacionadas a SST?', 'sst7Group', 'sst7', 'Detalle'),
            app_bitacora_yes_no_field('casos_sst', '¿Hubo casos HelpDesk para SST?', 'sst8Group', 'sst8', 'Detalle'),
            app_bitacora_field('multiselect', 'equipo_sst', 'Visita de equipo SST', ['id' => 'equipo_sst', 'options' => $sst, 'required' => false]),
            app_bitacora_field('conditional_textarea', 'sst5', 'Actividades de acompañamiento SST', ['container_id' => 'contenedor_sst', 'required' => false]),
        ]],
        ['key' => 'ti', 'title' => 'Sistemas - TI', 'fields' => [
            app_bitacora_yes_no_field('equipos_ti', '¿Novedades con equipos TI o infraestructura de red?', 'tiGroup', 'ti', 'Detalle'),
            app_bitacora_yes_no_field('facturas_ti', '¿Novedades facturación electrónica? Por ejemplo: facturas electrónicas sin CUFE', 'ti1Group', 'ti1', 'Detalle'),
            app_bitacora_yes_no_field('novedades_ti', '¿Otras novedades en TI?', 'ti2Group', 'ti2', 'Detalle'),
            app_bitacora_yes_no_field('casos_ti', '¿Casos HelpDesk para TI?', 'ti3Group', 'ti3', 'Detalle'),
        ]],
        ['key' => 'mantenimiento', 'title' => 'Mantenimiento', 'fields' => [
            app_bitacora_yes_no_field('equipos_cocina', '¿Novedades con equipos de cocina?', 'mantGroup', 'mant', 'Detalle'),
            app_bitacora_yes_no_field('equipos_bar', '¿Novedades con equipos de bar?', 'mant1Group', 'mant1', 'Detalle'),
            app_bitacora_yes_no_field('equipos_salon', '¿Novedades con equipos de salón?', 'mant2Group', 'mant2', 'Detalle'),
            app_bitacora_yes_no_field('locativos', '¿Novedades locativas?', 'mant3Group', 'mant3', 'Detalle'),
            app_bitacora_yes_no_field('pendientes', '¿Pendientes de mantenimiento?', 'mant4Group', 'mant4', 'Detalle'),
            app_bitacora_field('plant', 'planta_elect', '¿Se usó planta eléctrica?'),
        ]],
        ['key' => 'mejoramiento', 'title' => 'Mejoramiento y Estandarización (Calidad y Ambiental)', 'fields' => [
            app_bitacora_field('multiselect', 'equipo_bpm', 'Visita de equipo Mejoramiento', ['id' => 'equipo_bpm', 'options' => $bpm, 'required' => false]),
            app_bitacora_field('conditional_textarea', 'bpm', 'Actividades durante la visita', ['container_id' => 'contenedor_bpm', 'required' => false]),
            app_bitacora_yes_no_field('visita_ss', '¿Visita de la Secretaría de Salud Municipal/Departamental?', 'bpm1Group', 'bpm1', 'Detalle'),
            app_bitacora_yes_no_field('visita_dagma', '¿Visita del DAGMA?', 'bpm2Group', 'bpm2', 'Detalle'),
            app_bitacora_yes_no_field('visita_west', '¿Visita del proveedor West?', 'bpm3Group', 'bpm3', 'Detalle'),
            app_bitacora_field('simple_radio', 'bpm4', '¿Hubo entrega de ACU al proveedor autorizado?'),
            app_bitacora_field('simple_radio', 'bpm5', '¿Hubo entrega de residuos aprovechables al proveedor autorizado?'),
            app_bitacora_field('simple_radio', 'bpm6', '¿Hubo entrega de residuos orgánicos al proveedor autorizado?'),
            app_bitacora_field('simple_radio', 'bpm7', '¿Se realizó control de plagas?'),
            app_bitacora_yes_no_field('novedad_grameras', '¿Hubo novedades con los instrumentos de medición (grameras y termómetros)?', 'bpm8Group', 'bpm8', 'Detalle'),
        ]],
        ['key' => 'bar', 'title' => 'Bar', 'fields' => [
            app_bitacora_yes_no_field('hielo_produ', '¿Producción de bolsas de hielo?', 'hieloGroup', 'hielo', 'Cantidad', 'number'),
            app_bitacora_yes_no_field('hielo_kolbitos', '¿Compra de hielo a Kolbitos?', 'hielo1Group', 'hielo1', 'Cantidad', 'number'),
            app_bitacora_yes_no_field('hielo_consumo', '¿Consumo de bolsas de hielo?', 'hielo2Group', 'hielo2', 'Cantidad', 'number'),
            app_bitacora_field('number', 'hielo3', 'Inventario final de hielo en el día'),
            app_bitacora_yes_no_field('hielo_enviado', '¿Hielo enviado a otra sede?', 'hielo4Group', 'hielo4', 'Detalle'),
            app_bitacora_yes_no_field('hielo_recibido', '¿Hielo recibido de otra sede?', 'hielo5Group', 'hielo5', 'Detalle'),
        ]],
        ['key' => 'despensa', 'title' => 'Despensa', 'fields' => [
            app_bitacora_field('textarea', 'desp', 'Ingrese las novedades relacionadas con materias primas de Despensa'),
        ]],
        ['key' => 'domicilios', 'title' => 'Domicilios', 'fields' => [
            app_bitacora_field('textarea', 'dorp', 'Novedades con Rappi'),
            app_bitacora_field('textarea', 'dorp1', 'Novedades con Domicilios propios'),
        ]],
        ['key' => 'tesoreria', 'title' => 'Tesorería', 'fields' => [
            app_bitacora_yes_no_field('facturas_mesas', '¿Facturas anuladas en mesas?', 'fa_mesasGroup', 'fa_mesas', 'Detalle'),
            app_bitacora_yes_no_field('facturas_domic', '¿Facturas anuladas en domicilios?', 'fa_domGroup', 'fa_dom', 'Detalle'),
            app_bitacora_yes_no_field('facturas_rappi', '¿Facturas anuladas en Rappi?', 'fa_rappiGroup', 'fa_rappi', 'Detalle'),
            app_bitacora_yes_no_field('bonos_coomeva', '¿Hubo redenciones de bonos Coomeva durante el día?', 'tesor1Group', 'tesor1', 'Detalle'),
            app_bitacora_yes_no_field('easypedido', '¿Se realizaron pagos por medio de EasyPedido durante el día?', 'tesor2Group', 'tesor2', 'Detalle'),
            app_bitacora_field('textarea', 'tesor', 'Faltantes de caja y otras novedades. No se puede omitir información importante.'),
            app_bitacora_field('number', 'rappi', 'Número órdenes Rappi'),
            app_bitacora_field('number', 'domi', 'Número domicilios'),
            app_bitacora_field('number', 'domiexpress', 'DomiExpress: domiciliarios'),
            app_bitacora_field('number', 'hdomi', 'DomiExpress: horas'),
            app_bitacora_field('number', 'pd', 'Cumplimiento presupuesto diario (%)'),
            app_bitacora_field('number', 'tp', 'Ticket promedio'),
        ]],
    ];

    if (app_bitacora_extra_enabled($companyConfig, 'reunion_calidad')) {
        $sections[] = ['key' => 'reunion_calidad', 'title' => 'Reunión de calidad', 'fields' => [
            app_bitacora_field('textarea', 'reu', 'Reunión de calidad 3:00 P.M.'),
        ]];
    }

    if (app_bitacora_extra_enabled($companyConfig, 'chetano') || !empty($companyConfig['extras_by_sede'])) {
        $sedes = app_bitacora_extra_enabled($companyConfig, 'chetano') ? [] : ['PANCE'];
        $sections[] = ['key' => 'chetano', 'title' => 'Chetano', 'sedes' => $sedes, 'fields' => [
            app_bitacora_field('textarea', 'nov_chetano', 'Novedades Chetano', ['sedes' => $sedes]),
            app_bitacora_field('textarea', 'ventas_chetano', 'Venta de productos Chetano', ['sedes' => $sedes]),
            app_bitacora_field('textarea', 'dom_chetano', 'Venta domicilios Chetano', ['sedes' => $sedes]),
            app_bitacora_field('textarea', 'mp_chetano', 'Materias primas Chetano', ['sedes' => $sedes]),
        ]];
    }

    if (app_bitacora_extra_enabled($companyConfig, 'torito')) {
        $sections[] = ['key' => 'torito', 'title' => 'Torito', 'fields' => [
            app_bitacora_field('textarea', 'nov_torito', 'Novedades Torito'),
            app_bitacora_field('textarea', 'ventas_torito', 'Venta de productos Torito'),
        ]];
    }

    return $sections;
}

function app_bitacora_normalize_dynamic_field(array $field): ?array
{
    $allowedTypes = ['text', 'textarea', 'number', 'date', 'time', 'select', 'yes_no'];
    $name = (string) ($field['name'] ?? '');
    $type = (string) ($field['type'] ?? 'text');

    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name) || !in_array($type, $allowedTypes, true)) {
        return null;
    }

    $field['name'] = $name;
    $field['type'] = $type;
    $field['label'] = trim((string) ($field['label'] ?? $name));
    $field['required'] = (bool) ($field['required'] ?? false);
    $field['dynamic'] = true;

    if ($type === 'select') {
        $field['options'] = array_values(array_filter((array) ($field['options'] ?? []), static fn($v) => trim((string) $v) !== ''));
    }

    if ($type === 'yes_no') {
        $detailName = (string) ($field['detail_name'] ?? ($name . '_detalle'));
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $detailName)) {
            return null;
        }
        $field['detail_name'] = $detailName;
        $field['group_id'] = $field['group_id'] ?? ($detailName . 'Group');
        $field['detail_label'] = $field['detail_label'] ?? 'Detalle';
        $field['detail_type'] = in_array(($field['detail_type'] ?? 'textarea'), ['textarea', 'number'], true) ? $field['detail_type'] : 'textarea';
    }

    return $field;
}

function app_bitacora_apply_config_json(array $sections, array $json): array
{
    if (!empty($json['replace_default_fields']) && !empty($json['sections']) && is_array($json['sections'])) {
        return $json['sections'];
    }

    foreach ((array) ($json['dynamic_fields'] ?? []) as $field) {
        if (!is_array($field)) {
            continue;
        }
        $field = app_bitacora_normalize_dynamic_field($field);
        if ($field === null) {
            continue;
        }

        $target = trim((string) ($field['section'] ?? 'Campos adicionales'));
        $found = false;
        foreach ($sections as &$section) {
            if (strcasecmp((string) ($section['title'] ?? ''), $target) === 0 || strcasecmp((string) ($section['key'] ?? ''), $target) === 0) {
                $section['fields'][] = $field;
                $found = true;
                break;
            }
        }
        unset($section);

        if (!$found) {
            $sections[] = ['key' => strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $target)), 'title' => $target, 'fields' => [$field]];
        }
    }

    foreach ($sections as &$section) {
        usort($section['fields'], static fn($a, $b) => (int) ($a['order'] ?? 0) <=> (int) ($b['order'] ?? 0));
    }
    unset($section);

    return $sections;
}

function app_bitacora_form_sections(int $empresaId, array $companyConfig): array
{
    $sections = app_bitacora_default_form_sections($companyConfig, $empresaId);
    return app_bitacora_apply_config_json($sections, app_bitacora_db_config_json($empresaId));
}

function app_bitacora_field_visible_for_sede(array $field, string $sede): bool
{
    $sedes = $field['sedes'] ?? [];
    if ($sedes === [] || $sedes === null) {
        return true;
    }

    return in_array($sede, (array) $sedes, true);
}

function app_bitacora_collect_field_names(array $sections, string $sede = ''): array
{
    $names = [];
    foreach ($sections as $section) {
        foreach ((array) ($section['fields'] ?? []) as $field) {
            if (!app_bitacora_field_visible_for_sede($field, $sede)) {
                continue;
            }
            $type = (string) ($field['type'] ?? 'text');
            $name = (string) ($field['name'] ?? '');
            if ($name !== '') {
                $names[] = $name;
            }
            if ($type === 'yes_no' && !empty($field['detail_name'])) {
                $names[] = (string) $field['detail_name'];
            }
            if ($type === 'supervisor_detail') {
                array_push($names, 'hora_entrada', 'hora_salida', 'act_sup');
            }
            if ($type === 'plant') {
                array_push($names, 'mant5', 'mant6', 'mant7', 'mant8');
            }
        }
    }

    return array_values(array_unique($names));
}

function app_bitacora_dynamic_render_fields(array $sections, string $sede = ''): array
{
    $fields = [];
    foreach ($sections as $section) {
        foreach ((array) ($section['fields'] ?? []) as $field) {
            if (empty($field['dynamic']) || !app_bitacora_field_visible_for_sede($field, $sede)) {
                continue;
            }
            $field['section_title'] = $section['title'] ?? 'Campos adicionales';
            $fields[] = $field;
        }
    }
    return $fields;
}
