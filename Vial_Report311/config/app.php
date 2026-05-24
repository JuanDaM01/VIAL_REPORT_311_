<?php
// config/app.php
// Configuración global de la aplicación
// Ajusta BASE_URL si tu proyecto no está en la raíz del servidor

// Detecta automáticamente la URL base desde el documento raíz del proyecto
// Ejemplo: si tienes http://localhost/Vial_Report311/ → BASE_URL = '/Vial_Report311'
// Si está en la raíz → BASE_URL = ''

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Sube hasta encontrar la carpeta raíz del proyecto (donde está index.php)
// El proyecto vive en /Vial_Report311/ (la subcarpeta del repositorio)
$projectRoot = rtrim(dirname(str_replace('\\', '/', __DIR__ . '/../')), '/');

// Calculamos el prefijo relativo al document root de Apache
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

if (!empty($docRoot) && str_starts_with($projectRoot, $docRoot)) {
    define('BASE_URL', substr($projectRoot, strlen($docRoot)));
} else {
    // Fallback: derivar de SCRIPT_NAME
    $parts = explode('/', ltrim($scriptName, '/'));
    // Si el proyecto está en /Vial_Report311/pages/foo.php → ['Vial_Report311','pages','foo.php']
    // BASE = /Vial_Report311
    if (count($parts) >= 2) {
        define('BASE_URL', '/' . $parts[0]);
    } else {
        define('BASE_URL', '');
    }
}

// ─── Rutas de secciones ────────────────────────────────────
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