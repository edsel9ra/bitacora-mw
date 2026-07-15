<?php
require_once __DIR__ . '/../config/security.php';

app_destroy_session();
header('Location: ../index.php');
exit;
