<?php
require_once __DIR__ . '/../config/security.php';

app_require_post_login(null, false);
app_destroy_session();
header('Location: ../index.php');
exit;
