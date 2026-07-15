<?php
require_once __DIR__ . '/../config/security.php';
app_require_login(5);
?>
<!doctype html>
<html lang="es">

<head>
    <!--  meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Bitácora Mister Wings</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="../resources/sweetalert/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="../resources/img/LOGO ALITAS-09.png" alt="Logo">

</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="jumbotron" style="background:#f0f2f5">
                    <a class="btn btn-danger btn-block-sm" href="../bd/logout.php" role="button">Cerrar Sesión</a>
                    <h1 class="display-4 text-center"><b>Bitácora Mister Wings</b></h1>
                    <h2 class="text-center">Usuario: <span class="badge badge-primary">
                            <?php echo app_h($_SESSION["s_nombre"] ?? ''); ?>
                        </span></h2>
                    <!--<p class="lead text-center">Esta es la página de inicio, luego de un LOGIN correcto.</p>-->
                    <hr class="my-4">
                    <br>
                    <form method="post" class="form-bitacora" autocomplete="off">
                        <?php echo app_csrf_input(); ?>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="inputDate"><b>Fecha de bitácora<span style="color:#FF0000"
                                            ;>*</span></b></label>
                                <input type="date" class="form-control" name="fechab" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="idSede"><b>Sede<span style="color:#FF0000" ;>*</span></b></label>
                                <select id="idSede" class="form-control" name="sede" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="LLANOGRANDE" selected>LLANOGRANDE</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="inputResponsable"><b>Responsable<span style="color:#FF0000"
                                            ;>*</span></b></label>
                                <input type="text" class="form-control" style="text-transform:uppercase;" value=""
                                    onkeyup="javascript:this.value=this.value.toUpperCase();" name="responsable" placeholder="Ingrese su nombre completo"
                                    required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="idCargo"><b>Cargo<span style="color:#FF0000" ;>*</span></b></label>
                                <select id="idCargo" class="form-control" name="cargo" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="Coordinador/a">Coordinador/a</option>
                                    <option value="Cajero/a">Cajero/a</option>
                                </select>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="exampleFormControlTextarea1"><span style="color:#FF0000" ;>• </span><b>ÁREA
                                        DE OPERACIONES<span style="color:#FF0000" ;>*</span></b></label>
                                </br>
                            </div>
                            <div class="container d-flex justify-content-center mt-2 mb-2">
                                <div class="form-group col-md-7">
                                  <label for="bar"><b><span style="color:#FF0000" ;>•</span> Servicio al Cliente: Ingrese
                                      novedades relacionadas con clientes o quejas de clientes que se presenten en
                                      la sede.<span style="color:#FF0000" ;>*</span></b></label>
                                  <textarea class="form-control" rows="3" name="sac" required></textarea>
                                </div>
                                <div class="form-group col-md-5">
                                  <label for="bar"><b><span style="color:#FF0000" ;>•</span> Devoluciones de Producto (Retorno a
                                      Cocina)<span style="color:#FF0000" ;>*</span></b></label>
                                  <textarea class="form-control" rows="4" name="devo" required></textarea>
                                </div>
                            </div>
                            <div class="container d-flex justify-content-center">
                                <div class="form-group col-md-6">
                                    <label for="supervisores"><b><span style="color:#FF0000" ;>•</span> Ingrese los supervisores que hayan visitado la sede en el día y las actividades realizadas. En caso de que no hayan visitado, seleccionar "No aplica visita"<span
                                                style="color:#FF0000" ;>*</span></b></label>
                                    <select id="supervisores" name="supervisores[]" multiple="multiple" style="width: 100%;" class="form-control" rows="3" required>
                                        <option value="Brian Alberto Ortiz">Brian Alberto Ortiz - Coordinador de Operaciones</option>
                                        <option value="Angela Yuliana Mesa">Angela Yuliana Mesa - Entrenadora de Cocina y Bar</option>
                                        <option value="Julia Maria Carabali">Julia Maria Carabali - Entrenadora de Cocina y Bar</option>
                                        <option value="Nicol Muñoz">Nicol Muñoz - Supervisora de Operaciones</option>
                                        <option value="No hay visita por parte de los supervisores">No aplica visita</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6" style="display: none;" id="contenedor_sup">
                                        <textarea class="form-control" rows="5" id="act_sup" name="act_sup"></textarea>
                                        <div class="form-group col-md-5">
                                                <label for="supervisores"><b><span style="color:#FF0000" ;>•</span> Ingrese la hora de ingreso de la
                                                                entrenadora/supervisora/coordinador<span style="color:#FF0000" ;>*</span></b></label>
                                                <input class="form-control" type="time" id="hora_entrada" name="hora_entrada" />
                                                <label for="supervisores"><b><span style="color:#FF0000" ;>•</span> Ingrese la hora de salida de la
                                                                entrenadora/supervisora/coordinador<span style="color:#FF0000" ;>*</span></b></label>
                                                <input class="form-control" type="time" id="hora_salida" name="hora_salida" />
                                        </div>
                                </div>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="exampleFormControlTextarea1"><span style="color:#FF0000" ;>• </span><b>AFLUENCIA DE
                                    COMENSALES<span style="color:#FF0000" ;>*</span></b></label>
                                </br>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="comens"><b>Medio Día<span style="color:#FF0000" ;>*</span></b></label>
                                <select id="comens" class="form-control" name="comens" required>
                                    <option value="">Seleccionar</option>
                                    <option value="BAJA">AFLUENCIA BAJA</option>
                                    <option value="MODERADA">AFLUENCIA MODERADA</option>
                                    <option value="ALTA">AFLUENCIA ALTA</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="comens1"><b>Tarde<span style="color:#FF0000" ;>*</span></b></label>
                                <select id="comens1" class="form-control" name="comens1" required>
                                    <option value="">Seleccionar</option>
                                    <option value="BAJA">AFLUENCIA BAJA</option>
                                    <option value="MODERADA">AFLUENCIA MODERADA</option>
                                    <option value="ALTA">AFLUENCIA ALTA</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="comens2"><b>Noche<span style="color:#FF0000" ;>*</span></b></label>
                                <select id="comens2" class="form-control" name="comens2" required>
                                    <option value="">Seleccionar</option>
                                    <option value="BAJA">AFLUENCIA BAJA</option>
                                    <option value="MODERADA">AFLUENCIA MODERADA</option>
                                    <option value="ALTA">AFLUENCIA ALTA</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="mesas"><b><span style="color:#FF0000" ;>• </span> Observaciones Jefe de
                                        Mesas<span style="color:#FF0000" ;>*</span></b></label>
                                <!--  <small class="form-text" style="font-weight:bold;">• Ingrese todas las novedades reportadas por el jefe de salon.</small> -->
                                <textarea class="form-control" rows="3" name="mesas" required></textarea>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="bar"><b><span style="color:#FF0000" ;>•</span> Observaciones Jefe de
                                        Bar<span style="color:#FF0000" ;>*</span></b></label>
                                <!-- <small class="form-text" style="font-weight:bold;">• Ingrese todas las novedades reportadas por el jefe de bar.</small> -->
                                </br>
                                <textarea class="form-control" rows="3" name="bar" required></textarea>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="cocina"><b><span style="color:#FF0000" ;>•</span> Observaciones Jefe de
                                        Cocina<span style="color:#FF0000" ;>*</span></b></label>
                                <!-- <small class="form-text" style="font-weight:bold;">• Ingrese todas las novedades reportadas por el jefe de cocina.</small> -->
                                </br>
                                <textarea class="form-control" rows="3" name="cocina" required></textarea>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="coc"><span style="color:#FF0000" ;>• </span><b>ÁREA DE MERCADEO<span
                                            style="color:#FF0000" ;>*</span></b></label>
                                </br>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="bar"><b><span style="color:#FF0000" ;>•</span> Venta de Cocteleria: Ingrese
                                        todas las ventas de mojitos, margaritas, etc.<span style="color:#FF0000"
                                            ;>*</span></b></label>
                                </br>
                                <textarea class="form-control" rows="3" name="coc" required></textarea>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="bar"><b><span style="color:#FF0000" ;>•</span> Ventas de Productos Foco:
                                        Ingrese todas las ventas de productos Foco<span style="color:#FF0000"
                                            ;>*</span></b></label>
                                </br>
                                <textarea class="form-control" rows="3" name="mer" required></textarea>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="bar"><b><span style="color:#FF0000" ;>•</span> Venta de Productos Nuevos:
                                        Ingrese todas las ventas de productos nuevos.<span style="color:#FF0000"
                                            ;>*</span></b></label>
                                </br>
                                <textarea class="form-control" rows="3" name="mer1" required></textarea>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="bar"><b><span style="color:#FF0000" ;>•</span> Campañas del Mes: (Día de la
                                        madre, Halloween, día de la mujer, etc.)<span style="color:#FF0000"
                                            ;>*</span></b></label>
                                </br>
                                <textarea class="form-control" rows="3" name="mer2" required></textarea>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="bar"><b><span style="color:#FF0000" ;>•</span> Registro de Solicitudes HelpDesk: Ingrese las solicitudes registradas en la plataforma HeplDesk para el área de <strong>Mercadeo</strong>
                                <span style="color:#FF0000";>*</span></b></label>
                                </br>
                                <textarea class="form-control" rows="3" name="mer3" required></textarea>
                            </div>
                            <div class="col-md-12">
                              <fieldset class="mb-2">
                                <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se realizó alguna reserva en donde hay entre 10 o
                                    más personas?<span style="color:#FF0000">*</span></b>
                                </legend>
                                <div class="form-check form-check-inline">
                                  <input class="form-check-input" type="radio" id="visitaSi" name="reservas_15" value="Si" required>
                                  <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                </div>
                                <div class="form-check form-check-inline">
                                  <input class="form-check-input" type="radio" id="visitaNo" name="reservas_15" value="No" required>
                                  <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                </div>
                              </fieldset>
                              <!-- Contenedor del textarea -->
                              <div id="mer4Group" class="mt-2" style="display:none;">
                                <label for="mer4" class="form-label"><strong>Ingrese las reservas realizadas durante el día, con la información y
                                    contacto de los clientes:</strong></label>
                                <textarea class="form-control" rows="4" id="mer4" name="mer4"></textarea>
                              </div>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="exampleFormControlTextarea1"><span style="color:#FF0000" ;>• </span><b>ÁREA
                                        DE GESTIÓN HUMANA<span style="color:#FF0000" ;>*</span></b></label>
                                <small class="form-text" style="font-weight:bold;">• Ingrese todas las novedades
                                    relacionadas con el personal de la sede.</small>
                                <textarea class="form-control" rows="3" name="gh"></textarea>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="exampleFormControlTextarea1"><span style="color:#FF0000" ;>• </span><b>REUNIÓN DE 
                                    CALIDAD 3:00 P.M<span style="color:#FF0000" ;>*</span></b></label>
                                <small class="form-text" style="font-weight:bold;">• Ingrese todas las novedades
                                    relacionadas con la reunión díaria.</small>
                                <textarea class="form-control" rows="3" name="reu" required></textarea>
                            </div>
                            <div class="mt-3 mb-3">
                                <label for="exampleFormControlTextarea1"><span style="color:#FF0000" ;>• </span><b>ÁREA
                                        DE SEGURIDAD Y SALUD EN EL TRABAJO<span style="color:#FF0000";>*</span></b></label>
                                <div class="row mt-2 mb-2">
                                  <div class="col-md-6">
                                    <fieldset class="mb-2">
                                      <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se presento algún incidente o
                                          accidente durante la jornada laboral?
                                          Los accidentes pueden ser tanto laborales como accidentes de tránsito.<span
                                            style="color:#FF0000">*</span></b>
                                      </legend>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaSi" name="accidentes_sst" value="Si" required>
                                        <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                      </div>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaNo" name="accidentes_sst" value="No" required>
                                        <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                      </div>
                                    </fieldset>
                                    <!-- Contenedor del textarea -->
                                    <div id="sst1Group" class="mt-2" style="display:none;">
                                      <label for="sst1" class="form-label"><strong>Ingrese los incidentes, accidentes laborales y
                                          accidentes de tránsito que se presentaron:</strong></label>
                                      <textarea class="form-control" rows="3" id="sst1" name="sst1"></textarea>
                                    </div>
                                  </div>
                                  <div class="col-md-6">
                                    <fieldset class="mb-2">
                                      <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Algún colaborador tiene
                                          incapacidad igual o mayor a 15 días?<span style="color:#FF0000">*</span></b>
                                      </legend>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaSi" name="incapacidades_sst" value="Si" required>
                                        <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                      </div>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaNo" name="incapacidades_sst" value="No" required>
                                        <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                      </div>
                                    </fieldset>
                                    <!-- Contenedor del textarea -->
                                    <div id="sst2Group" class="mt-2" style="display:none;">
                                      <label for="sst2" class="form-label"><strong>Ingrese las incapacidades iguales o mayores a 15
                                          días:</strong></label>
                                      <textarea class="form-control" rows="5" id="sst2" name="sst2"></textarea>
                                    </div>
                                  </div>
                                </div>
                                <div class="row mt-2 mb-2">
                                  <div class="col-md-6">
                                    <fieldset class="mb-2">
                                      <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se presento alguna novedad en el
                                          ambiente laboral? <span style="color:#FF0000">*</span></b>
                                      </legend>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaSi" name="ambiente_laboral" value="Si" required>
                                        <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                      </div>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaNo" name="ambiente_laboral" value="No" required>
                                        <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                      </div>
                                    </fieldset>
                                    <!-- Contenedor del textarea -->
                                    <div id="sst3Group" class="mt-2" style="display:none;">
                                      <label for="sst3" class="form-label"><strong>Ingrese los hallazgos por ambiente
                                          laboral:</strong></label>
                                      <textarea class="form-control" rows="3" id="sst3" name="sst3"></textarea>
                                    </div>
                                  </div>
                                  <div class="col-md-6">
                                    <fieldset class="mb-2">
                                      <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se ha presentado novedades con los
                                          extintores y la señalización?<span style="color:#FF0000">*</span></b>
                                      </legend>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaSi" name="senal_sst" value="Si" required>
                                        <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                      </div>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaNo" name="senal_sst" value="No" required>
                                        <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                      </div>
                                    </fieldset>
                                    <!-- Contenedor del textarea -->
                                    <div id="sst4Group" class="mt-2" style="display:none;">
                                      <label for="sst4" class="form-label"><strong>Ingrese los reportes de los extintores y avisos de
                                          señalización:</strong></label>
                                      <textarea class="form-control" rows="3" id="sst4" name="sst4"></textarea>
                                    </div>
                                  </div>
                                </div>
                                <div class="mt-3 mb-3">
                                  <div class="container d-flex justify-content-center">
                                    <div class="form-group col-md-6">
                                      <label for="equipo_sst"><b><span style="color:#FF0000" ;>•</span> Ingrese si hubo visita por parte
                                          del equipo de
                                          SST a la sede y las actividades realizadas. En caso de que no hayan visitado, seleccionar "No
                                          aplica visita".<span style="color:#FF0000" ;>*</span></b></label>
                                      <select id="equipo_sst" name="equipo_sst[]" multiple="multiple" style="width: 100%;"
                                        class="form-control" rows="3" required>
                                        <option value="Pamela Valencia">Pamela Valencia - Coordinadora de SST</option>
                                        <option value="Johanna Findo">Johanna Findo - Auxiliar SST</option>
                                        <option value="Edward Zambrano">Edward Zambrano - Auxiliar SST</option>
                                        <option value="No hay visita por parte del área">No aplica visita</option>
                                      </select>
                                    </div>
                                    <div class="form-group col-md-6" style="display: none;" id="contenedor_sst">
                                      <textarea class="form-control" rows="7" id="sst5" name="sst5"></textarea>
                                    </div>
                                  </div>
                                </div>
                                <div class="row mt-2 mb-2">
                                  <div class="col-md-4">
                                    <fieldset class="mb-2">
                                      <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se realizó entrega de elementos de
                                          protección personal (EPP)? <span style="color:#FF0000">*</span></b>
                                      </legend>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaSi" name="entrega_epp" value="Si" required>
                                        <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                      </div>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaNo" name="entrega_epp" value="No" required>
                                        <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                      </div>
                                    </fieldset>
                                    <!-- Contenedor del textarea -->
                                    <div id="sst6Group" class="mt-2" style="display:none;">
                                      <label for="sst6" class="form-label"><strong>Ingrese las entregas realizadas de elementos de
                                          protección personal (EPP), especificar si fue por parte del personal del área o del
                                          proveedor:</strong></label>
                                      <textarea class="form-control" rows="3" id="sst6" name="sst6"></textarea>
                                    </div>
                                  </div>
                                  <div class="col-md-4">
                                    <fieldset class="mb-2">
                                      <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se presentaron otras novedades
                                          relacionadas con el área?<span style="color:#FF0000">*</span></b>
                                      </legend>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaSi" name="novedades_sst" value="Si" required>
                                        <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                      </div>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaNo" name="novedades_sst" value="No" required>
                                        <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                      </div>
                                    </fieldset>
                                    <!-- Contenedor del textarea -->
                                    <div id="sst7Group" class="mt-2" style="display:none;">
                                      <label for="sst7" class="form-label"><strong>Ingrese otras novedades como mantenimiento de alto
                                          riesgo, situaciones de salud, condiciones o actos inseguros, etc:</strong></label>
                                      <textarea class="form-control" rows="4" id="sst7" name="sst7"></textarea>
                                    </div>
                                  </div>
                                  <div class="col-md-4">
                                    <fieldset class="mb-2">
                                      <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se registraron solicitudes o casos
                                          en HelpDesk para el área de SST?<span style="color:#FF0000">*</span></b>
                                      </legend>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaSi" name="casos_sst" value="Si" required>
                                        <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                      </div>
                                      <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="visitaNo" name="casos_sst" value="No" required>
                                        <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                      </div>
                                    </fieldset>
                                    <!-- Contenedor del textarea -->
                                    <div id="sst8Group" class="mt-2" style="display:none;">
                                      <label for="sst8" class="form-label"><strong>Ingrese las solicitudes o los casos registrados y/o
                                          pendientes para el área:</strong></label>
                                      <textarea class="form-control" rows="5" id="sst8" name="sst8"></textarea>
                                    </div>
                                  </div>
                                </div>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="exampleFormControlTextarea1"><span style="color:#FF0000" ;>• </span><b>ÁREA
                                        DE SISTEMAS - TI<span style="color:#FF0000" ;>*</span></b></label>
                                <small class="form-text" style="font-weight:bold;"></small>
                            </div>
                            <div class="row mt-2 mb-2">
                              <div class="col-md-6">
                                <fieldset class="mb-2">
                                  <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se presento alguna novedad con los equipos
                                      de computo, impresoras y/o en la infraestructura de red (Internet, WiFi)?<span style="color:#FF0000">*</span></b>
                                  </legend>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="visitaSi" name="equipos_ti" value="Si" required>
                                    <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                  </div>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="visitaNo" name="equipos_ti" value="No" required>
                                    <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                  </div>
                                </fieldset>
                                <!-- Contenedor del textarea -->
                                <div id="tiGroup" class="mt-2" style="display:none;">
                                  <label for="ti" class="form-label"><strong>Describa las novedades presentadas en los equipos:</strong></label>
                                  <textarea class="form-control" rows="3" id="ti" name="ti"></textarea>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <fieldset class="mb-2">
                                  <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se presentaron novedades con la facturación,
                                      como facturas electrónicas sin CUFE?<span style="color:#FF0000">*</span></b>
                                  </legend>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="visitaSi" name="facturas_ti" value="Si" required>
                                    <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                  </div>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="visitaNo" name="facturas_ti" value="No" required>
                                    <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                  </div>
                                </fieldset>
                                <!-- Contenedor del textarea -->
                                <div id="ti1Group" class="mt-2" style="display:none;">
                                  <label for="ti1" class="form-label"><strong>Ingrese las facturas con las novedades presentadas:</strong></label>
                                  <textarea class="form-control" rows="3" id="ti1" name="ti1"></textarea>
                                </div>
                              </div>
                            </div>
                            <div class="row mt-2 mb-2">
                              <div class="col-md-6">
                                <fieldset class="mb-2">
                                  <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo alguna novedad relacionada al área
                                      como visitas a la sede por parte del equipo de TI?<span style="color:#FF0000">*</span></b>
                                  </legend>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="visitaSi" name="novedades_ti" value="Si" required>
                                    <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                  </div>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="visitaNo" name="novedades_ti" value="No" required>
                                    <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                  </div>
                                </fieldset>
                                <!-- Contenedor del textarea -->
                                <div id="ti2Group" class="mt-2" style="display:none;">
                                  <label for="ti2" class="form-label"><strong>Describa las novedades presentadas:</strong></label>
                                  <textarea class="form-control" rows="3" id="ti2" name="ti2"></textarea>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <fieldset class="mb-2">
                                  <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se registraron solicitudes o casos
                                      en HelpDesk para el área de <strong>Sistemas - TI</strong>?<span style="color:#FF0000">*</span></b>
                                  </legend>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="visitaSi" name="casos_ti" value="Si" required>
                                    <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                  </div>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="visitaNo" name="casos_ti" value="No" required>
                                    <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                  </div>
                                </fieldset>
                                <!-- Contenedor del textarea -->
                                <div id="ti3Group" class="mt-2" style="display:none;">
                                  <label for="ti3" class="form-label"><strong>Ingrese las solicitudes o los casos registrados y/o pendientes para el
                                      área:</strong></label>
                                  <textarea class="form-control" rows="3" id="ti3" name="ti3"></textarea>
                                </div>
                              </div>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="exampleFormControlTextarea1"><span style="color:#FF0000" ;>• </span><b>ÁREA
                                        DE INFRAESTRUCTURA Y MANTENIMIENTO<span style="color:#FF0000" ;>*</span></b></label>
                                <small class="form-text" style="font-weight:bold;"></small>
                            </div>
                            <div class="row mt-2 mb-2">
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo novedades con los equipos de
                                        Cocina? <span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="equipos_cocina" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="equipos_cocina" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="mantGroup" class="mt-2" style="display:none;">
                                    <label for="mant" class="form-label"><strong>Ingrese las novedades con los equipos de
                                        Cocina:</strong></label>
                                    <textarea class="form-control" rows="3" id="mant" name="mant"></textarea>
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo novedades con los equipos de
                                        Bar?<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="equipos_bar" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="equipos_bar" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="mant1Group" class="mt-2" style="display:none;">
                                    <label for="mant1" class="form-label"><strong>Ingrese las novedades con los equipos de
                                        Bar:</strong></label>
                                    <textarea class="form-control" rows="4" id="mant1" name="mant1"></textarea>
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo novedades con los equipos de
                                        Salón?<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="equipos_salon" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="equipos_salon" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="mant2Group" class="mt-2" style="display:none;">
                                    <label for="mant2" class="form-label"><strong>Ingrese las novedades con los equipos de
                                        Salón:</strong></label>
                                    <textarea class="form-control" rows="3" id="mant2" name="mant2"></textarea>
                                  </div>
                                </div>
                              </div>
                              <div class="row mt-2 mb-2">
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo novedades en la sede a nivel
                                        locativo?<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="locativos" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="locativos" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="mant3Group" class="mt-2" style="display:none;">
                                    <label for="mant3" class="form-label"><strong>Ingrese las novedades en los espacios
                                        locativos:</strong></label>
                                    <textarea class="form-control" rows="14" id="mant3" name="mant3"></textarea>
                                  </div>
                                </div>
                                <!-- Campo nuevo planta eléctrica -->
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo novedades con la planta eléctrica? (Si
                                        aplica)<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="planta_elect" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="planta_elect" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor de los campos -->
                                  <div id="plantaGroup" class="mt-2" style="display:none;">
                                    <label for="mant5" class="form-label"><strong>Ingrese la hora de encendido:</strong></label>
                                    <input type="time" class="form-control" id="mant5" name="mant5">
                                    <label for="mant6" class="form-label"><strong>Ingrese la hora de apagado:</strong></label>
                                    <input type="time" class="form-control" id="mant6" name="mant6">
                                    <label for="mant_duracion" class="form-label"><strong>Tiempo transcurrido (minutos):</strong></label>
                                    <input type="number" class="form-control" id="mant7" name="mant7" readonly>
                                    <label for="mant8" class="form-label"><strong>Ingrese las novedades presentadas en la planta
                                        eléctrica:</strong></label>
                                    <textarea class="form-control" rows="5" id="mant8" name="mant8"></textarea>
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hay alguna actividad pendiente del
                                        área, ya sea del equipo de Mantenimiento o un contratista?<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="pendientes" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="pendientes" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="mant4Group" class="mt-2" style="display:none;">
                                    <label for="mant4" class="form-label"><strong>Ingrese reportes pendientes por resolver. Incluye
                                        solicitudes realizadas en la plataforma de HelpDesk al área de Mantenimiento:</strong></label>
                                    <textarea class="form-control" rows="11" id="mant4" name="mant4"></textarea>
                                  </div>
                                </div>
                            </div>
                            <div class="mt-3 mb-3">
                              <label for="exampleFormControlTextarea1"><span style="color:#FF0000" ;>• </span><b>ÁREA
                                  DE MEJORAMIENTO Y ESTANDARIZACIÓN (CALIDAD Y AMBIENTAL)<span style="color:#FF0000" ;>*</span></b></label>
                              <!-- Select al estilo de los supervisores de Operaciones -->
                              <div class="container d-flex justify-content-center">
                                <div class="form-group col-md-6">
                                  <label for="equipo_bpm"><b><span style="color:#FF0000" ;>•</span> Ingrese si hubo visita por parte del equipo de
                                      Mejoramiento a la sede y las actividades realizadas. En caso de que no hayan visitado, seleccionar "No aplica
                                      visita"<span style="color:#FF0000" ;>*</span></b></label>
                                  <select id="equipo_bpm" name="equipo_bpm[]" multiple="multiple" style="width: 100%;" class="form-control" rows="3"
                                    required>
                                    <option value="Fabián Salazar">Fabián Salazar - Coordinador de Mejoramiento</option>
                                    <option value="Carlos Peña">Carlos Peña - Supervisor de Calidad y Ambiental</option>
                                    <!-- Se debe cambiar el nombre del aprendiz cada 6 meses, se deja 1 -->
                                    <option value="Alejandro Noguera">Alejandro Noguera - Aprendiz</option>
                                    <option value="No hay visita por parte del área">No aplica visita</option>
                                  </select>
                                </div>
                                <div class="form-group col-md-6" style="display: none;" id="contenedor_bpm">
                                  <textarea class="form-control" rows="5" id="bpm" name="bpm"></textarea>
                                </div>
                              </div>
                              <!-- Condicionales con ingreso de información -->
                              <div class="row mt-2 mb-2">
                                <div class="col-md-6">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo visita por parte de la Secretaría de
                                        Salud?<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="visita_ss" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="visita_ss" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="bpm1Group" class="mt-2" style="display:none;">
                                    <label for="bpm1" class="form-label"><strong>Describa las actividades de la visita:</strong></label>
                                    <textarea class="form-control" rows="3" id="bpm1" name="bpm1"></textarea>
                                  </div>
                                </div>
                                <div class="col-md-6">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo visita por parte del DAGMA?<span
                                          style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="visita_dagma" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="visita_dagma" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="bpm2Group" class="mt-2" style="display:none;">
                                    <label for="bpm2" class="form-label"><strong>Describa las actividades de la visita:</strong></label>
                                    <textarea class="form-control" rows="3" id="bpm2" name="bpm2"></textarea>
                                  </div>
                                </div>
                              </div>
                              <div class="row mt-2 mb-2">
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo visita por parte de West - Klaxen?<span
                                          style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="visita_west" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="visita_west" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="bpm3Group" class="mt-2" style="display:none;">
                                    <label for="bpm3" class="form-label"><strong>Describa las actividades de la visita:</strong></label>
                                    <textarea class="form-control" rows="3" id="bpm3" name="bpm3"></textarea>
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo entrega de ACU al proveedor
                                      autorizado?<span style="color:#FF0000">*</span></b>
                                  </legend>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="bpm4" value="Si" required>
                                    <label class="form-check-label"><b>SI</b></label>
                                  </div>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="bpm4" value="No" required>
                                    <label class="form-check-label"><b>NO</b></label>
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo entrega de residuos aprovechables al
                                      proveedor autorizado?<span style="color:#FF0000">*</span></b>
                                  </legend>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="bpm5" value="Si" required>
                                    <label class="form-check-label"><b>SI</b></label>
                                  </div>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="bpm5" value="No" required>
                                    <label class="form-check-label"><b>NO</b></label>
                                  </div>
                                </div>
                              </div>
                              <div class="row mt-2 mb-2">
                                <div class="col-md-4">
                                  <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo entrega de residuos orgánicos al proveedor
                                      autorizado?<span style="color:#FF0000">*</span></b>
                                  </legend>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="bpm6" value="Si" required>
                                    <label class="form-check-label"><b>SI</b></label>
                                  </div>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="bpm6" value="No" required>
                                    <label class="form-check-label"><b>NO</b></label>
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se realizó control de plagas?<span
                                        style="color:#FF0000">*</span></b>
                                  </legend>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="bpm7" value="Si" required>
                                    <label class="form-check-label"><b>SI</b></label>
                                  </div>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="bpm7" value="No" required>
                                    <label class="form-check-label"><b>NO</b></label>
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo novedades con los instrumentos de
                                        medición (grameras y termómetros)?<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="novedad_grameras" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="novedad_grameras" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="bpm8Group" class="mt-2" style="display:none;">
                                    <label for="bpm8" class="form-label"><strong>Describa las novedades presentadas:</strong></label>
                                    <textarea class="form-control" rows="3" id="bpm8" name="bpm8"></textarea>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="exampleFormControlTextarea1"><span style="color:#FF0000" ;>• </span><b>ÁREA DE BAR<span style="color:#FF0000" ;>*</span></b></label>
                                <small class="form-text" style="font-weight:bold;"></small>
                            </div>
                            <div class="container d-flex justify-content-center">
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo producción de bolsas de
                                        hielo?<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="hielo_produ" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="hielo_produ" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="hieloGroup" class="mt-2" style="display:none;">
                                    <label for="hielo" class="form-label"><strong>Ingrese la producción de bolsas en el
                                        día:</strong></label>
                                    <input type="number" min="0" max="100" class="form-control" name="hielo" id="hielo">
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se compró hielo a proveedor
                                        Kolbitos?<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="hielo_kolbitos" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="hielo_kolbitos" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="hielo1Group" class="mt-2" style="display:none;">
                                    <label for="hielo1" class="form-label"><strong>Ingrese la cantidad de bolsas
                                        compradas:</strong></label>
                                    <input type="number" min="0" max="100" class="form-control" name="hielo1" id="hielo1">
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se consumieron bolsas de hielo
                                        durante la operación del día?<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="hielo_consumo" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="hielo_consumo" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="hielo2Group" class="mt-2" style="display:none;">
                                    <label for="hielo2" class="form-label"><strong>Ingrese el consumo de bolsas en el
                                        día:</strong></label>
                                    <input type="number" min="0" max="100" class="form-control" name="hielo2" id="hielo2">
                                  </div>
                                </div>
                            </div>
                            <div class="container d-flex justify-content-center">
                
                                <div class="form-group col-md-4">
                                  <label for="bar"><b><span style="color:#FF0000;">•</span> Inventario Final de bolsas de hielo en el
                                      día<span style="color:#FF0000;">*</span></b></label>
                                  <input type="number" min="0" max="100" class="form-control" name="hielo3" required></textarea>
                                </div>
                
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se enviaron bolsas de hielo a otras
                                        sedes?<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="hielo_enviado" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="hielo_enviado" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="hielo4Group" class="mt-2" style="display:none;">
                                    <label for="hielo4" class="form-label"><strong>Ingrese el número de bolsas que se enviaron y a que
                                        sedes:</strong></label>
                                    <textarea class="form-control" rows="3" id="hielo4" name="hielo4"></textarea>
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se solicitó bolsas de hielo a otras
                                        sedes?<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="hielo_recibido" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="hielo_recibido" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="hielo5Group" class="mt-2" style="display:none;">
                                    <label for="hielo5" class="form-label"><strong>Ingrese el número de bolsas que se recibieron y de
                                        que sedes:</strong></label>
                                    <textarea class="form-control" rows="3" id="hielo5" name="hielo5"></textarea>
                                  </div>
                                </div>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="exampleFormControlTextarea1"><span style="color:#FF0000" ;>• </span><b>ÁREA
                                        DE DESPENSA<span style="color:#FF0000" ;>*</span></b></label>
                                <small class="form-text" style="font-weight:bold;">• Ingrese las novedades relacionadas
                                    con materias primas de Despensa.</small>
                                <textarea class="form-control" rows="3" name="desp" required></textarea>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="exampleFormControlTextarea1"><span style="color:#FF0000" ;>• </span><b> NOVEDADES DOMICILIOS / RAPPI<span style="color:#FF0000" ;>*</span></b></label>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="bar"><b><span style="color:#FF0000" ;>•</span> Ingrese las novedades con Rappi.<span style="color:#FF0000" ;>*</span></b></label>
                                <textarea class="form-control" rows="3" name="dorp" required></textarea>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="bar"><b><span style="color:#FF0000" ;>•</span> Ingrese las novedades con domicilios propios.<span
                                            style="color:#FF0000" ;>*</span></b></label>
                                <textarea class="form-control" rows="3" name="dorp1" required></textarea>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="exampleFormControlTextarea1"><span style="color:#FF0000" ;>• </span><b>ÁREA
                                        DE TESORERIA<span style="color:#FF0000" ;>*</span></b></label>
                                <small class="form-text" style="font-weight:bold;"></small>
                            </div>
                            <div class="row mt-2 mb-2">
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo facturas anuladas en mesas
                                        durante el día? <span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="facturas_mesas" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="facturas_mesas" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="fa_mesasGroup" class="mt-2" style="display:none;">
                                    <label for="fa_mesas" class="form-label"><strong>Ingrese las facturas anuladas en
                                        mesas:</strong></label>
                                    <textarea class="form-control" rows="3" id="fa_mesas" name="fa_mesas"></textarea>
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo facturas anuladas de Domicilios
                                        durante el día?<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="facturas_domic" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="facturas_domic" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="fa_domGroup" class="mt-2" style="display:none;">
                                    <label for="mant1" class="form-label"><strong>Ingrese las facturas anuladas en
                                        domicilios:</strong></label>
                                    <textarea class="form-control" rows="3" id="fa_dom" name="fa_dom"></textarea>
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <fieldset class="mb-2">
                                    <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo facturas anuladas de Rappi
                                        durante el día?<span style="color:#FF0000">*</span></b>
                                    </legend>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaSi" name="facturas_rappi" value="Si" required>
                                      <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="visitaNo" name="facturas_rappi" value="No" required>
                                      <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                    </div>
                                  </fieldset>
                                  <!-- Contenedor del textarea -->
                                  <div id="fa_rappiGroup" class="mt-2" style="display:none;">
                                    <label for="fa_rappi" class="form-label"><strong>Ingrese las facturas anuladas de
                                        Rappi:</strong></label>
                                    <textarea class="form-control" rows="3" id="fa_rappi" name="fa_rappi"></textarea>
                                  </div>
                                </div>
                              </div>
                            <div class="row mt-2 mb-2">
                              <div class="col-md-6">
                                <fieldset class="mb-2">
                                  <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Hubo redenciones de bonos Coomeva
                                      durante el día?<span style="color:#FF0000">*</span></b>
                                  </legend>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="visitaSi" name="bonos_coomeva" value="Si" required>
                                    <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                  </div>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="visitaNo" name="bonos_coomeva" value="No" required>
                                    <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                  </div>
                                </fieldset>
                                <!-- Contenedor del textarea -->
                                <div id="tesor1Group" class="mt-2" style="display:none;">
                                  <label for="tesor1" class="form-label"><strong>Ingrese los bonos de Coomeva
                                      canjeados:</strong></label>
                                  <textarea class="form-control" rows="4" id="tesor1" name="tesor1"></textarea>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <fieldset class="mb-2">
                                  <legend class="h6 mb-2"><b><span style="color:#FF0000">•</span>¿Se realizaron pagos por medio de EasyPedido?<span
                                        style="color:#FF0000">*</span></b>
                                  </legend>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="visitaSi" name="easypedido" value="Si" required>
                                    <label class="form-check-label" for="visitaSi"><b>SI</b></label>
                                  </div>
                                  <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="visitaNo" name="easypedido" value="No" required>
                                    <label class="form-check-label" for="visitaNo"><b>NO</b></label>
                                  </div>
                                </fieldset>
                                <!-- Contenedor del textarea -->
                                <div id="tesor2Group" class="mt-2" style="display:none;">
                                  <label for="mer4" class="form-label"><strong>Ingrese los pedidos con su correspondiente valor:</strong></label>
                                  <textarea class="form-control" rows="4" id="tesor2" name="tesor2"></textarea>
                                </div>
                              </div>
                            </div>
                            <div class="row mt-2 mb-2">
                              <div class="form-group col-md-12">
                                <label for="bar"><b><span style="color:#FF0000;">•</span> Faltantes de caja y otras novedades. No se
                                    puede omitir información importante.<span style="color:#FF0000;">*</span></b></label>
                                <textarea class="form-control" rows="5" name="tesor" required></textarea>
                              </div>
                            </div>
                            <div class="container d-flex">
                                <div class="form-group col-md-3">
                                    <label for="inputRappi"><b>RAPPI<span style="color:#FF0000" ;>*</span></b></label>
                                    <input type="number" min="0" max="100" class="form-control" name="rappi" required>
                                    <small class="form-text" style="font-weight:bold;">Nº ordenes</small>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="inputDomi"><b>Domicilios<span style="color:#FF0000"
                                                ;>*</span></b></label>
                                    <input type="number" min="0" max="100" class="form-control" name="domi" required>
                                    <small class="form-text" style="font-weight:bold;">Nº domicilios</small>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="inputRappi"><b>Número de DomiExpress<span style="color:#FF0000"
                                                ;>*</span></b></label>
                                    <input type="number" min="0" max="100" class="form-control" name="domiexpress" required>
                                    <small class="form-text" style="font-weight:bold;">Número de domiciliarios de
                                        DomiExpress</small>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="inputDomi"><b>Horas DomiExpress<span style="color:#FF0000"
                                                ;>*</span></b></label>
                                    <input type="number" min="0" max="100" class="form-control" name="hdomi" required>
                                    <small class="form-text" style="font-weight:bold;">Número de horas en sede del
                                        domiciliario</small>
                                </div>
                            </div>
                            <div class="container d-flex justify-content-center">
                                <div class="form-group col-lg-5">
                                    <label for="inputPD"><b>Cumplimiento Porcentual Presupuesto Diario<span
                                                style="color:#FF0000;">*</span></b></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">% </span>
                                        </div>
                                        <input type="number" class="form-control" id="inputPD" name="pd" required>
                                    </div>
                                </div>
                                <div class="form-group col-lg-5">
                                    <label for="inputTP"><b>Ticket Promedio<span style="color:#FF0000"
                                                ;>*</span></b></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="tpb">$</span>
                                        </div>
                                        <input type="number" class="form-control" id="inputTP" name="tp" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="container d-flex justify-content-center col-12">
                            <div class="form-group col-lg-4"><button type="button" id="guardar_local_storage" class="btn btn-secondary btn-lg btn-block">💾 Guardar Temporal</button></div>
                            <div class="form-group col-lg-4"><button type="button" id="cargar_local_storage" class="btn btn-success btn-lg btn-block">📂 Cargar Información Temporal</button></div>
                            <div class="form-group col-lg-4"><button type="submit" name="enviar_bitacora" id="enviar_bitacora" class="btn btn-primary btn-lg btn-block">📨 Enviar Bitácora</button></div>
                        </div>
                    </form>
                    <div class="mostrar"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="../resources/jquery/jquery-3.6.0.min.js"></script>
    <script src="../resources/js/bootstrap.min.js"></script>
    <script src="../resources/popper/popper.min.js"></script>
    <script src="../resources/sweetalert/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../code.js"></script>
    <script src="../localstorage_bitacora.js"></script>
    <script>
        $(document).ready(function () {
            $('#supervisores').select2({
                placeholder: "Seleccione uno o más supervisores que realizaron visita",
                tags: true,
                tokenSeparators: [',', ';', '.'],
                closeOnSelect: false
            });
        
            $('#supervisores').on('change', function () {
                var selectedOptions = $(this).val();
                var exception = 'No hay visita por parte de los supervisores';
                if (selectedOptions && selectedOptions.length > 0) {
                    if (selectedOptions.includes(exception)) {
                        $('#contenedor_sup').hide();
                        $('#supervisores option').not('[value="' + exception + '"]').prop('disabled', true);
                    } else {
                        $('#contenedor_sup').show();
                        $('#supervisores option[value="' + exception + '"]').prop('disabled', true);
                    }
                } else {
                    $('#contenedor_sup').hide();
                    $('#supervisores option').prop('disabled', false);
                    $('#supervisores option[value="' + exception + '"]').prop('disabled', false);
                }
            });
        
            //Agregar para el campo de Mejoramiento
            $('#equipo_bpm').select2({
                placeholder: "Seleccione uno o más supervisores que realizaron visita",
                tags: true,
                tokenSeparators: [',', ';', '.'],
                closeOnSelect: false
            });
        
            $('#equipo_bpm').on('change', function () {
                var selectedOptions = $(this).val();
                var exception = 'No hay visita por parte del área';
                if (selectedOptions && selectedOptions.length > 0) {
                    if (selectedOptions.includes(exception)) {
                        $('#contenedor_bpm').hide();
                        $('#equipo_bpm option').not('[value="' + exception + '"]').prop('disabled', true);
                    } else {
                        $('#contenedor_bpm').show();
                        $('#equipo_bpm option[value="' + exception + '"]').prop('disabled', true);
                    }
                } else {
                    $('#contenedor_bpm').hide();
                    $('#equipo_bpm option').prop('disabled', false);
                    $('#equipo_bpm option[value="' + exception + '"]').prop('disabled', false);
                }
            });
        
            // Agregar listado para SST
              $('#equipo_sst').select2({
                placeholder: "Seleccione el personal que realizó la visita",
                tags: true,
                tokenSeparators: [',', ';', '.'],
                closeOnSelect: false
              });
        
              $('#equipo_sst').on('change', function () {
                var selectedOptions = $(this).val();
                var exception = 'No hay visita por parte del área';
                if (selectedOptions && selectedOptions.length > 0) {
                  if (selectedOptions.includes(exception)) {
                    $('#contenedor_sst').hide();
                    $('#equipo_sst option').not('[value="' + exception + '"]').prop('disabled', true);
                  } else {
                    $('#contenedor_sst').show();
                    $('#equipo_sst option[value="' + exception + '"]').prop('disabled', true);
                  }
                } else {
                  $('#contenedor_sst').hide();
                  $('#equipo_sst option').prop('disabled', false);
                  $('#equipo_sst option[value="' + exception + '"]').prop('disabled', false);
                }
              });
        
              // ===== Campos Mejoramiento Condicionales + Texto (bpm1, bpm2, bpm3, bpm8) =====
              // Config: radio name, textarea selector, contenedor, y texto por defecto cuando es "No"
              // ======================
              // Configuración unificada
              // ======================
              const toggles = [
                { radioName: 'equipos_ti', inputSel: '#ti', groupSel: '#tiGroup', defaultText: 'Sin novedades con los equipos.' },
                { radioName: 'facturas_ti', inputSel: '#ti1', groupSel: '#ti1Group', defaultText: 'Las facturas electrónicas se integran con código CUFE.' },
                { radioName: 'novedades_ti', inputSel: '#ti2', groupSel: '#ti2Group', defaultText: 'Sin novedades.' },
                { radioName: 'casos_ti', inputSel: '#ti3', groupSel: '#ti3Group', defaultText: 'No se reportan casos o solicitudes.' },
        
                { radioName: 'visita_ss', inputSel: '#bpm1', groupSel: '#bpm1Group', defaultText: 'Sin novedad.' },
                { radioName: 'visita_dagma', inputSel: '#bpm2', groupSel: '#bpm2Group', defaultText: 'Sin novedad.' },
                { radioName: 'visita_west', inputSel: '#bpm3', groupSel: '#bpm3Group', defaultText: 'Sin novedad.' },
                { radioName: 'novedad_grameras', inputSel: '#bpm8', groupSel: '#bpm8Group', defaultText: 'Sin novedad.' },
        
                { radioName: 'accidentes_sst', inputSel: '#sst1', groupSel: '#sst1Group', defaultText: 'Sin novedades.' },
                { radioName: 'incapacidades_sst', inputSel: '#sst2', groupSel: '#sst2Group', defaultText: 'Sin novedades.' },
                { radioName: 'ambiente_laboral', inputSel: '#sst3', groupSel: '#sst3Group', defaultText: 'Sin novedades.' },
                { radioName: 'senal_sst', inputSel: '#sst4', groupSel: '#sst4Group', defaultText: 'Sin novedades.' },
                { radioName: 'entrega_epp', inputSel: '#sst6', groupSel: '#sst6Group', defaultText: 'Sin novedades.' },
                { radioName: 'novedades_sst', inputSel: '#sst7', groupSel: '#sst7Group', defaultText: 'Sin novedades.' },
                { radioName: 'casos_sst', inputSel: '#sst8', groupSel: '#sst8Group', defaultText: 'Sin novedades.' },
        
                { radioName: 'equipos_cocina', inputSel: '#mant', groupSel: '#mantGroup', defaultText: 'Sin novedades.' },
                { radioName: 'equipos_bar', inputSel: '#mant1', groupSel: '#mant1Group', defaultText: 'Sin novedades.' },
                { radioName: 'equipos_salon', inputSel: '#mant2', groupSel: '#mant2Group', defaultText: 'Sin novedades.' },
                { radioName: 'locativos', inputSel: '#mant3', groupSel: '#mant3Group', defaultText: 'Sin novedades.' },
                { radioName: 'pendientes', inputSel: '#mant4', groupSel: '#mant4Group', defaultText: 'Sin novedades.' },
        
                // Si #hielo es numérico en tu HTML, añade defaultNumber
                { radioName: 'hielo_produ', inputSel: '#hielo', groupSel: '#hieloGroup', defaultText: 'Sin novedades.', defaultNumber: 0 },
                { radioName: 'hielo_kolbitos', inputSel: '#hielo1', groupSel: '#hielo1Group', defaultText: 'Sin novedades.', defaultNumber: 0 },
                { radioName: 'hielo_consumo', inputSel: '#hielo2', groupSel: '#hielo2Group', defaultText: 'Sin novedades.', defaultNumber: 0 },
                { radioName: 'hielo_enviado', inputSel: '#hielo4', groupSel: '#hielo4Group', defaultText: 'Sin novedades.' },
                { radioName: 'hielo_recibido', inputSel: '#hielo5', groupSel: '#hielo5Group', defaultText: 'Sin novedades.' },
        
                { radioName: 'facturas_mesas', inputSel: '#fa_mesas', groupSel: '#fa_mesasGroup', defaultText: 'No se anularon facturas.' },
                { radioName: 'facturas_domic', inputSel: '#fa_dom', groupSel: '#fa_domGroup', defaultText: 'No se anularon facturas.' },
                { radioName: 'facturas_rappi', inputSel: '#fa_rappi', groupSel: '#fa_rappiGroup', defaultText: 'No se anularon facturas.' },
                { radioName: 'bonos_coomeva', inputSel: '#tesor1', groupSel: '#tesor1Group', defaultText: 'No se canjearon bonos Coomeva.' },
                
                { radioName: 'reservas_15', inputSel: '#mer4', groupSel: '#mer4Group', defaultText: 'No se realizaron reservas.' },
                { radioName: 'easypedido', inputSel: '#tesor2', groupSel: '#tesor2Group', defaultText: 'No se realizaron pedidos por EasyPedido.' },
                
                { radioName: 'planta_elect', inputSel: '#mant5', groupSel: '#plantaGroup', defaultTime: '00:00' },
                { radioName: 'planta_elect', inputSel: '#mant6', groupSel: '#plantaGroup', defaultTime: '00:00' },
                { radioName: 'planta_elect', inputSel: '#mant7', groupSel: '#plantaGroup', defaultNumber: 0 },
                { radioName: 'planta_elect', inputSel: '#mant8', groupSel: '#plantaGroup', defaultText: 'No se presentaron novedades relacionadas a la planta eléctrica.' }
              ];
              
              function calcularTiempo() {

                    const inicio = document.getElementById('mant5').value;
                    const fin = document.getElementById('mant6').value;
                    
                    // Si falta alguno de los dos, no calculamos
                    if (!inicio || !fin) return;
                    
                    // Parsear hh:mm
                    const [h1, m1] = inicio.split(':').map(Number);
                    const [h2, m2] = fin.split(':').map(Number);
                    
                    let minutosInicio = h1 * 60 + m1;
                    let minutosFin = h2 * 60 + m2;
                    
                    // Diferencia en minutos (soporta cruce de medianoche)
                    let diff = minutosFin - minutosInicio;
                    if (diff < 0) { diff +=24 * 60; // suma 24 horas si apagado es al día siguiente 
                      }
                      document.getElementById('mant7').value=diff;
                }
                
                // Recalcular cada vez que cambien las horas
                document.getElementById('mant5').addEventListener('change', calcularTiempo);
                document.getElementById('mant6').addEventListener('change', calcularTiempo);
        
              // ======================
              // UI: mostrar/ocultar y required (no toca valores)
              // ======================
              function applyToggleState(value, $group, $input) {
                if (value === 'Si') {
                  $group.show();
                  $input.prop('required', true);
                } else if (value === 'No') {
                  $group.hide();
                  $input.prop('required', false);
                } else {
                  $group.hide();
                  $input.prop('required', false);
                }
              }
        
              // ======================
              // Enlazar eventos e inicializar
              // ======================
              function bindToggle(t) {
                const $group = $(t.groupSel);
                const $input = $(t.inputSel);
        
                $(`input[name="${t.radioName}"]`).on('change', function () {
                  applyToggleState(this.value, $group, $input);
                });
        
                const checked = $(`input[name="${t.radioName}"]:checked`).val();
                applyToggleState(checked, $group, $input);
              }
        
              // Inicializa todos
              toggles.forEach(bindToggle);
        
              // ===== Radios simples (sin input asociado): bpm4, bpm5, bpm6, bpm7 =====
              const simpleRadios = ['bpm4', 'bpm5', 'bpm6', 'bpm7'];
        
              // ======================
              // Submit del formulario
              // ======================
              $(".form-bitacora").submit(function (event) {
                event.preventDefault();
        
                let faltantes = false;
        
                toggles.forEach(t => {
                  const val = $(`input[name="${t.radioName}"]:checked`).val();
                  const $inp = $(t.inputSel);
                  const isNumber = $inp.is('input[type=number]');
                  const raw = $inp.val(); // string
                  const trimmed = $.trim(raw);
        
                  if (isNumber) {
                    // Caso input number
                    const hasValue = trimmed !== '';
                    const num = hasValue ? Number(trimmed) : NaN;
        
                    if (val === 'No' && !hasValue) {
                      // Completar con valor por defecto numérico
                      $inp.val(t.defaultNumber ?? 0);
                    }
        
                    if (val === 'Si') {
                      // Requiere un número válido (permite 0 si tu caso lo necesita)
                      if (!hasValue || Number.isNaN(num)) {
                        if (!faltantes) $inp.focus();
                        faltantes = true;
                      }
                    }
                  } else {
                    // Caso textarea (u otros no numéricos)
                    if (val === 'No' && trimmed === '') {
                      $inp.val(t.defaultText ?? 'Sin novedades.');
                    }
                    if (val === 'Si' && trimmed === '') {
                      if (!faltantes) $inp.focus();
                      faltantes = true;
                    }
                  }
                });
        
                if (faltantes) return; // cancela envío hasta que completen
        
                const $botonEnvio = $(this).find('button[type="submit"]');
                $botonEnvio.prop('disabled', true);
                $.ajax({
                    url: "../scripts/send_lebor.php",
                    type: "POST",
                    data: $(".form-bitacora").serialize(),
                    success: function (data) {
                        $(".mostrar").html(data);
                        localStorage.removeItem('bitacora');
                        $('.form-bitacora')[0].reset();
                        $('#contenedor_sup').hide();
                        $('#supervisores option').prop('disabled', false);
                        $('#supervisores').select2({
                            placeholder: "Seleccione uno o más supervisores que realizaron visita",
                            tags: true,
                            tokenSeparators: [',', ';', '.'],
                            closeOnSelect: false
                        });
                        $('#contenedor_bpm').hide();
                        $('#equipo_bpm option').prop('disabled', false);
                        $('#equipo_bpm').select2({
                            placeholder: "Seleccione uno o más supervisores que realizaron visita",
                            tags: true,
                            tokenSeparators: [',', ';', '.'],
                            closeOnSelect: false
                        });
        
                        $('#contenedor_sst').hide();
                        $('#equipo_sst option').prop('disabled', false);
                        $('#equipo_sst').select2({
                          placeholder: "Seleccione el personal que realizó la visita",
                          tags: true,
                          tokenSeparators: [',', ';', '.'],
                          closeOnSelect: false
                        });
            
                        // Limpiar radios y ocultar grupos
                        toggles.forEach(t => {
                          $(`input[name="${t.radioName}"]`).prop('checked', false);
                          applyToggleState(undefined, $(t.groupSel), $(t.inputSel));
                          $(t.inputSel).val(''); // limpia textarea o input number
                        });
        
                        simpleRadios.forEach(n => {
                            $(`input[name="${n}"]`).prop('checked', false);
                        });
                    },
        
                    complete: function () {
                        $botonEnvio.prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>

</html>
