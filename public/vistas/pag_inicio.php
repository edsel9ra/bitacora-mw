<?php
require_once __DIR__ . '/../config/security.php';

app_start_session();

if (empty($_SESSION['s_usuario'])) {
    header('Location: ../index.php');
    exit;
}

$empresaId = (int) ($_SESSION['s_idEmpresa'] ?? 0);
if ($empresaId > 0) {
    header('Location: bitacora.php');
    exit;
}
?>
<!doctype html>
<html lang="es">

<head>
    <link rel="shortcut icon" href="#" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="../resources/sweetalert/sweetalert2.min.css">
    <title>Bitácora Mister Wings</title>
</head>
<body>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
        <div class="jumbotron">
          <h1 class="display-4 text-center">¡Bienvenido!</h1>
          <h2 class="text-center">Usuario: <span class="badge badge-success"><?php echo app_h($_SESSION['s_nombre'] ?? ''); ?></span></h2>
          <p class="lead text-center">No hay una vista asignada para esta empresa.</p>
          <hr class="my-4">
          <a class="btn btn-danger btn-lg" href="../bd/logout.php" role="button">Cerrar Sesión</a>
        </div>
        </div>
    </div>
</div>

<script src="../resources/jquery/jquery-3.6.0.min.js"></script>
<script src="../resources/popper/popper.min.js"></script>
<script src="../resources/js/bootstrap.min.js"></script>
<script src="../resources/sweetalert/sweetalert2.all.min.js"></script>
</body>
</html>
