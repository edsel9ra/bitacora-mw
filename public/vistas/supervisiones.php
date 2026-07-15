<?php
require_once __DIR__ . '/../config/security.php';
app_require_login(6);
?>
<!doctype html>
<html lang="es">

<head>
  <title>Supervisiones - Mister Wings</title>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap CSS v5.2.0-beta1 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">

</head>

<body style="background: url(fondo.jpg) center center fixed;">
  <div class="container">
    <div class="col-lg-12">
      <div class="card border-0 shadow my-3">
        <div class="card-body p-5">
          <div class="col-md-3">
            <a class="btn btn-danger" href="../bd/logout.php">Cerrar sesión</a>
          </div>
          <h1 class="text-center"><b>Reporte de supervisión</b></h1>
          <hr class="my-4">
            <form class="form-bitacora" enctype="multipart/form-data" autocomplete="off">
              <?php echo app_csrf_input(); ?>
          <div class="row">
              <div class="col-md-3">
                <div class="form-floating mb-3">
                  <input type="date" class="form-control" id="fechasup" name="fechasup" autofocus>
                  <label for="fechasup" style="color:#000000; border:#000000"><b>Fecha de acompañamiento <span style="color:#FF0000">*</span></b></label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-floating">
                  <select class="form-select" id="horasup" aria-label="horasup" name="horasup">
                    <option value="" selected>Seleccionar...</option>
                    <option value="11:00 AM - 3:00 PM">11:00 AM - 3:00 PM</option>
                    <option value="3:00 PM - 6:00 PM">3:00 PM - 6:00 PM</option>
                    <option value="6:00 PM - 10:00 PM">6:00 PM - 10:00 PM</option>
                    <option value="Todo el turno">Todo el turno</option>
                  </select>
                  <label for="horasup" style="color:#000000"><b>Horario de supervisión <span style="color:#FF0000">*</span></b></label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-floating">
                  <select class="form-select" id="sede" aria-label="sede" name="sede">
                    <option value="" selected>Seleccionar...</option>
                    <option value="Pance">Mister Wings Pance</option>
                    <option value="Ciudad Jardín">Mister Wings Ciudad Jardín</option>
                    <option value="Jardín Plaza">Mister Wings Jardín Plaza</option>
                    <option value="Unicentro">Mister Wings Unicentro</option>
                    <option value="Limonar">Mister Wings Limonar</option>
                    <option value="San Fernando">Mister Wings San Fernando</option>
                    <option value="Granada">Mister Wings Granada</option>
                    <option value="Chipichape">Mister Wings Chipichape</option>
                    <option value="Flora">Mister Wings Flora</option>
                    <option value="Llanogrande">Mister Wings Llanogrande</option>
                    <option value="Bochalema">Mister Wings Bochalema</option>
                  </select>
                  <label for="sede" style="color:#000000"><b>Sede <span style="color:#FF0000">*</span></b></label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-floating">
                  <select class="form-select" id="area" aria-label="area" name="area">
                    <option value="" selected>Seleccionar...</option>
                    <option value="Salon">Salon</option>
                    <option value="Cocina">Cocina</option>
                    <option value="Bar">Bar</option>
                  </select>
                  <label for="area" style="color:#000000"><b>Área <span style="color:#FF0000">*</span></b></label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-floating mb-4">
                  <select class="form-select" id="responsableb" aria-label="responsableb" name="responsableb">
                    <option value="" selected>Seleccionar...</option>
                    <option value="Angela Mesa - Supervisora de Cocina y Bar">Angela Mesa - Supervisora de Cocina y Bar</option>
                    <option value="Brian Ortiz - Coordinador de Operaciones">Brian Ortiz - Coordinador de Operaciones</option>
                    <option value="Gabriel Perez - Supervisor Comercial">Gabriel Perez - Supervisor Comercial</option>
                    <option value="Maria Conchita - Supervisora de Cocina y Bar">Maria Conchita - Supervisora de Cocina y Bar</option>
                    <option value="Nicol Muñoz - Supervisora de Operaciones">Nicol Muñoz - Supervisora de Operaciones</option>
                  </select>
                  <label for="area" style="color:#000000"><b>Responsable de
                      supervisión <span style="color:#FF0000">*</span></b></label>                      
                </div>
              </div>
              <div class="col-md-12"><br><br>
                <label for="hallazgos"><b>• Hallazgos encontrados con sus evidencias <span style="color:#FF0000">*</span></label><br><br>
                <textarea id="hallazgos" class="form-control" rows="6" name="hallazgos" required></textarea>
                <div class="col-md-12"><br>
                  <label for="ryc"><b>• Retroalimentación y qué colaboradores <span style="color:#FF0000">*</span></label><br><br>
                  <textarea id="ryc" class="form-control" rows="6" name="ryc" required></textarea>
                </div>
                <div class="col-md-12"><br>
                  <label for="tappv"><b>• Tareas asignadas para próximas visitas <span style="color:#FF0000">*</span></label><br><br>
                  <textarea id="tappv" class="form-control" rows="6" name="tappv" required></textarea>
                </div>
                <div class="col-md-12"><br>
                  <label for="pasc"><b>• Plan de acción dejado a la sede o recomendaciones <span style="color:#FF0000">*</span></label><br><br>
                  <textarea id="pasc" class="form-control" rows="6" name="pasc" required></textarea>
                </div>
                <div class="col-md-12"><br>
                  <label for="pasc"><b>• Otras Actividades del supervisor <span style="color:#FF0000">*</span></label><br><br>
                  <textarea id="actsup" class="form-control" rows="6" name="actsup" required></textarea>
                </div>
                <br><br>
              </div><br>
              <div class="d-grid gap-2 col-6 mx-auto">
                  <button type="button" id="generar-pdf" name="generar-pdf" class="btn btn-danger btn-lg btn-block">Generar PDF</button>
                  <button id="boton" type="submit" class="btn btn-lg" style="background: #e30613; color:#ffffff;">Enviar reporte</button>
          </div>
            </form>
             <div class="mostrar"></div>
        </div>
      </div>
    </div>
  </div>
  </div>
  </div>
  <!-- Bootstrap JavaScript Libraries and JQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.js" integrity="sha512-n/4gHW3atM3QqRcbCn6ewmpxcLAHGaDjpEBu4xZd47N0W2oQ+6q7oc3PXstrJYXcbNU1OHdQ1T7pAP+gi5Yu8g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.5/dist/umd/popper.min.js" integrity="sha384-Xe+8cL9oJa6tN/veChSP7q+mnSPaj5Bcu9mPX5F5xIGE0DVittaqT5lorf0EI7Vk" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.min.js" integrity="sha384-kjU+l4N0Yf4ZOJErLsIcvOU2qSb74wXpOhqTvwVx3OElZRweTnQ6d31fXEoRD1Jy" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
  <script src="../code.js"></script>
  <script src="../resources/popper/popper.min.js"></script>
  <script src="../resources/sweetalert/sweetalert2.all.min.js"></script>
  <script>
    $(".form-bitacora").submit(function (event) {
        event.preventDefault();
        var $botonEnvio = $(this).find('button[type="submit"]');
        $botonEnvio.prop('disabled', true);
        $.ajax({
            url: "../scripts/send_sup.php",
            type: "POST",
            data: $(".form-bitacora").serialize(),
            success: function (data) {
                $(".mostrar").html(data);
                $('.form-bitacora')[0].reset();
            },
            complete: function () {
                $botonEnvio.prop('disabled', false);
            }
        });
    });
    
    $("#generar-pdf").click(function () {
        generarPDF();
    });
    
    function generarPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
    
        const etiquetas = {
            fechasup: "Fecha de Acompañamiento",
            horasup: "Horario de supervisión",
            sede: "Sede",
            area: "Area Supervisada",
            responsableb: "Supervisor y Cargo",
            hallazgos: "Hallazgos Encontrados",
            ryc: "Retroalimentación a Colaboradores",
            tappv: "Tareas Asignadas",
            pasc: "Planes de Acción y Recomendaciones",
            actsup: "Otras actividades realizadas"
        };
    
        const logoBase64 = "data:image/jpeg;base64,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";
    
        // Encabezado estilizado
        doc.addImage(logoBase64, "JPEG", 10, 10, 30, 30); // Posición y tamaño del logo
        doc.setFontSize(24);
        doc.setFont("helvetica", "bold");
        doc.text("Reporte de Supervisión", 50, 25); // Ajuste del título
        doc.setLineWidth(0.5);
        //doc.line(10, 40, 200, 40); // Línea divisoria debajo del título
    
        const formData = $(".form-bitacora").find("input, textarea, select").serializeArray();
        if (formData.length === 0) {
            alert("No hay datos para generar el PDF.");
            return;
        }
    
        let y = 50;
        const pageHeight = 290;
        const margin = 10;
        const lineSpacing = 10;
        doc.setFontSize(12);
    
        // Generar contenido del formulario
        formData.forEach(item => {
            const etiqueta = etiquetas[item.name] || `Campo desconocido (${item.name})`;
            const contenido = item.value || "No proporcionado";
            const textoCompleto = `${etiqueta}: ${contenido}`;
            
            const lineasEtiqueta = doc.splitTextToSize(etiqueta, 190);
            const lineasContenido = doc.splitTextToSize(contenido, 190);
            
            lineasEtiqueta.forEach((lineaEtiqueta, index) => {
                if (y + lineSpacing > pageHeight){
                    doc.addPage();
                    y = 10;
                }
                
                doc.setFont("helvetica", "bolditalic");
                doc.text(lineaEtiqueta, margin, y);
                
                if (index === lineasEtiqueta.length - 1) {
                    // Continuar en la misma línea para contenido si no se desbordó
                    y += lineSpacing;
                }
            });
            
            lineasContenido.forEach(lineasContenido => {
                if (y + lineSpacing > pageHeight) {
                    doc.addPage();
                    y = 10;
                }
                doc.setFont("helvetica", "normal");
                doc.text(lineasContenido, margin, y);
                y += lineSpacing;
            });
        });
    
        // Pie de página
        const totalPages = doc.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            doc.setPage(i);
            doc.setFontSize(8);
            doc.text(`Página ${i} de ${totalPages}`, 10, 290);
        }
    
        const sede = $("#sede").val().toLowerCase() || "sin_sede";
        const area = $("#area").val().toLowerCase() || "sin_area";
        const fecha = $("#fechasup").val() || "sin_fecha";
        const nombre_archivo = `reporte_${sede}_${area}_${fecha}.pdf`;
    
        doc.save(nombre_archivo);
    }
  </script>
</body>

</html>
