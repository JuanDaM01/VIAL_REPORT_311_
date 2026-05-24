<?php
// auth/logout.php
// Cierra la sesión y redirige al login

require_once __DIR__ . '/../config/session.php';

cerrarSesion();

header('Location: ' . BASE_URL . '/auth/login.php?msg=logout');
exit;
