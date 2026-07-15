function enviaCorreo(){
    $('#formLogin').submit(function(e){                         
        e.preventDefault(); 
    var fecha = $.trim($("#inputDate").val());
    var sede = $.trim($("#inputSede").val());
    var responsable = $.trim($("#inputResponsable").val());
    var cargo = $.trim($("#inputCargo").val());
    var servicioalcliente = $.trim($("#sacb").val());
    var gestionhumana = $.trim($("#ghb").val());
    var sst = $.trim($("#sstb").val());
    var sistemas = $.trim($("#tib").val());
    var mantenimiento = $.trim($("#man").val());
    var calidad = $.trim($("#cba").val());
    var inventarios = $.trim($("#invb").val());
    var ndomirappi = $.trim($("#dpb").val());
    var caja = $.trim($("#ncaja").val());
    var rappi = $.trim($("#inputRappi").val());
    var domi = $.trim($("#inputDomi").val());
    var ticketpromedio = $.trim($("#tpb").val());

    if(fecha.length == "" || sede.length == "" || responsable.length == "" || cargo.length == "" || fecha.length == "" || servicioalcliente.length == "" || gestionhumana.length == "" || sst.length == "" || sistemas.length == "" || mantenimiento.length == "" || calidad.length == "" || inventarios.length == "" || ndomirappi.length == "" || caja.length == "" || rappi.length == "" || domi.length == "" || ticketpromedio.length == ""){
        Swal.fire({
            icon: 'warning',
            title: 'Advertencia',
            text: '¡No puedes dejar ningun campo vacio!',
          });
          return false;	            
        }else{    
            $.ajax({
                    url: '../enviar_mg.php',
                    type: 'POST',
                    data:{fecha:fecha,sede:sede,responsable:responsable,cargo:cargo,sac:servicioalcliente,gh:gestionhumana,sst:sst,ti:sistemas,mantenimiento:mantenimiento,calidad:calidad,inv:inventarios,ndp:ndomirappi,caja:caja,rappi:rappi,domi:domi,tp:ticketpromedio}
                   }
               ).done(function(resp){                   
                   if(resp>0){
                    Swal.fire('Mensaje de confirmación','La bitácora se ha enviado correctamente','success');
                    $("#inputDate").val("");
                    $("#inputSede").val();
                    $("#inputResponsable").val();
                    $("#inputCargo").val();
                    $("#sacb").val();
                    $("#ghb").val();
                    $("#sstb").val();
                    $("#tib").val();
                    $("#man").val();
                    $("#cba").val();
                    $("#invb").val();
                    $("#dpb").val();
                    $("#ncaja").val();
                    $("#inputRappi").val();
                    $("#inputDomi").val();
                    $("#tpb").val();
                    $("#inputDate").focus();    
               }else{
                Swal.fire('Mensaje de error','No se pudo envia la bitácora','error');
                console.log(resp);
            }   
        })

    }
}
