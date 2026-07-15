$(function () {
  $(".form-bitacora").submit(function () {
    $.ajax({
      url: "../enviar_mg.php",
      type: "POST",
      data: $(".form-bitacora").serialize(),
      success: function (data) {
        $(".mostrar").html(data);
      },
    });
  });
});
