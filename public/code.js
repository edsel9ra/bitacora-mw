$("#formLogin").submit(function (e) {
  e.preventDefault();

  var usuario = $.trim($("#usuario").val());
  var password = $.trim($("#password").val());
  var csrfToken = $.trim($('input[name="csrf_token"]').val());

  if (usuario.length === 0 || password.length === 0) {
    Swal.fire({
      icon: "warning",
      title: "No puedes dejar campos vacios",
    });
    return false;
  }

  $.ajax({
    url: "bd/login.php",
    type: "POST",
    dataType: "json",
    data: { usuario: usuario, password: password, csrf_token: csrfToken },
    success: function (data) {
      if (data === null) {
        Swal.fire({
          icon: "error",
          title: "Usuario y/o contraseña invalido",
        });
        return;
      }

      Swal.fire({
        icon: "success",
        title: "¡Conexión exitosa!",
        showConfirmButton: true,
        allowEscapeKey: false,
        allowOutsideClick: false,
      }).then((result) => {
        if (result.value) {
          window.location.href = "/vistas/pag_inicio.php";
        }
      });
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "No fue posible iniciar sesión",
        text: "Intenta nuevamente o contacta soporte.",
      });
    },
  });
});
