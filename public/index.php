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
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="password-wrap">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control custom-input"
                            placeholder="Ingresa tu contraseña"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" id="togglePassword" class="password-toggle" aria-label="Mostrar contraseña" aria-pressed="false" title="Mostrar contraseña">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" name="submit" id="btnLogin" class="btn-login" data-default-text="Ingresar">
                    Ingresar
                </button>

            </form>

            <div id="footer" class="login-footer">
                <span>Powered by Edson Ramos for Mister Wings®</span>
            </div>

        </div>
    </main>

    <!-- Scripts -->
    <script src="resources/jquery/jquery-3.6.0.min.js"></script>
    <script src="resources/sweetalert/sweetalert2.all.min.js"></script>
    <script src="code.js"></script>

</body>
</html>
