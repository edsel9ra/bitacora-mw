$(function () {
  var $btn = $("#btnLogin");

  function showError(message) {
    $("#loginError").text(message).prop("hidden", false);
  }

  function hideError() {
    $("#loginError").prop("hidden", true);
  }

  // Mostrar / ocultar contraseña
  $("#togglePassword").on("click", function () {
    var $pw = $("#password");
    var show = $pw.attr("type") === "password";
    $pw.attr("type", show ? "text" : "password");
    $(this).toggleClass("is-active", show);
    $(this).attr("aria-label", show ? "Ocultar contraseña" : "Mostrar contraseña");
    $(this).attr("aria-pressed", show ? "true" : "false");
    $(this).attr("title", show ? "Ocultar contraseña" : "Mostrar contraseña");
    $pw.focus();
  });

  // Limpiar el error mientras el usuario escribe
  $("#usuario, #password").on("input", function () {
    hideError();
  });

  $("#formLogin").submit(function (e) {
    e.preventDefault();
    hideError();

    var usuario = $.trim($("#usuario").val());
    var password = $("#password").val();
    var csrfToken = $.trim($('input[name="csrf_token"]').val());

    if (usuario.length === 0 || password.length === 0) {
      showError("No puedes dejar campos vacíos.");
      $("#usuario").focus();
      return false;
    }

    var defaultText = $btn.data("default-text") || "Ingresar";
    $btn.prop("disabled", true).text("Ingresando...");

    $.ajax({
      url: "bd/login.php",
      type: "POST",
      dataType: "json",
      data: { usuario: usuario, password: password, csrf_token: csrfToken },
      success: function (data) {
        if (data === null || data.ok === false) {
          $btn.prop("disabled", false).text(defaultText);
          showError((data && data.message) || "Usuario y/o contraseña inválidos.");
          return;
        }

        $btn.text("¡Listo!");
        setTimeout(function () {
          window.location.href = "/vistas/pag_inicio.php";
        }, 400);
      },
      error: function (xhr) {
        $btn.prop("disabled", false).text(defaultText);
        var message =
          (xhr.responseJSON && xhr.responseJSON.message) ||
          "No fue posible iniciar sesión. Intenta nuevamente o contacta soporte.";
        showError(message);
      },
    });
  });
});
