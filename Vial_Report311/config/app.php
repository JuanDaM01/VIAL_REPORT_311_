<?php
// config/app.php
// Configuración global — BASE_URL calculada desde SCRIPT_NAME

// Detecta el prefijo del proyecto en la URL
// Ej: /Vial_Report311/pages/usuarios.php → BASE_URL = /Vial_Report311
// Ej: /Vial_Report311/index.php          → BASE_URL = /Vial_Report311

$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$parts  = explode('/', trim($script, '/'));

// El primer segmento es siempre el nombre del proyecto (Vial_Report311)
$base = '/' . ($parts[0] ?? '');

define('BASE_URL', $base);

// ─── Rutas ────────────────────────────────────────────────
define('URL_HOME',           BASE_URL . '/index.php');
define('URL_USUARIOS',       BASE_URL . '/pages/usuarios.php');
define('URL_REPORTES',       BASE_URL . '/pages/reportes.php');
define('URL_TICKETS',        BASE_URL . '/pages/tickets.php');
define('URL_PROVEEDORES',    BASE_URL . '/pages/proveedores.php');
define('URL_CATEGORIAS',     BASE_URL . '/pages/categorias.php');
define('URL_UBICACIONES',    BASE_URL . '/pages/ubicaciones.php');
define('URL_COMENTARIOS',    BASE_URL . '/pages/comentarios.php');
define('URL_EVIDENCIAS',     BASE_URL . '/pages/evidencias.php');
define('URL_ALERTAS',        BASE_URL . '/pages/alertas.php');
define('URL_VOTACIONES',     BASE_URL . '/pages/votaciones.php');
define('URL_NOTIFICACIONES', BASE_URL . '/pages/notificaciones.php');
define('URL_CSS',            BASE_URL . '/assets/css/style.css');
define('URL_API',            BASE_URL . '/api');