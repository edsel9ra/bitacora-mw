<?php
require_once __DIR__ . '/config/security.php';
app_start_session();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mister Wings - Login Bitácora</title>

    <link rel="shortcut icon" href="resources/img/ALITAS.png">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="resources/css/bootstrap.min.css">

    <!-- SweetAlert -->
    <link rel="stylesheet" href="resources/sweetalert/sweetalert2.min.css">

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="resources/css/estilos.css">
</head>

<body>

    <main class="login-page">
        <div class="login-card">

            <div class="login-logo">
                <img src="logo.jpg" alt="Logo Mister Wings">
            </div>

            <div class="login-header">
                <h1>Bitácora Mister Wings</h1>
                <p>Ingresa tus credenciales para continuar</p>
            </div>

            <form id="formLogin" method="post" autocomplete="off">
                <?php echo app_csrf_input(); ?>

                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input 
                        type="text" 
                        name="usuario" 
                        id="usuario" 
                        class="form-control custom-input" 
                        placeholder="Ingresa tu usuario"
                        autocomplete="off"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-control custom-input" 
                        placeholder="Ingresa tu contraseña"
                        required
                    >
                </div>

                <button type="submit" name="submit" class="btn-login">
                    Ingresar
                </button>

            </form>

            <div id="footer" class="login-footer">
                <span>Developed by Andrés Mesa for Mister Wings®</span>
            </div>

        </div>
    </main>

    <!-- Scripts -->
    <script src="resources/jquery/jquery-3.6.0.min.js"></script>
    <script src="resources/popper/popper.min.js"></script>
    <script src="resources/js/bootstrap.min.js"></script>
    <script src="resources/sweetalert/sweetalert2.all.min.js"></script>
    <script src="code.js"></script>

</body>
</html>
