<?php
// config/session.php
// Middleware de sesión — control de acceso por rol
// Uso: require_once __DIR__ . '/../config/session.php';
//      requireRole(['admin','funcionario']);  // bloquea si no tiene ese rol

require_once __DIR__ . '/app.php';

// ── Arrancar sesión (solo si no hay ya una) ──────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,          // dura hasta cerrar el navegador
        'path'     => '/',
        'secure'   => false,      // true en producción con HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ── Helpers ──────────────────────────────────────────────────

/** ¿Hay sesión activa? */
function estaLogueado(): bool {
    return !empty($_SESSION['usuario_id']);
}

/** Rol del usuario actual (ciudadano | funcionario | administrador | '') */
function rolActual(): string {
    return $_SESSION['rol'] ?? '';
}

/** Datos completos del usuario en sesión */
function usuarioSesion(): array {
    return [
        'id'          => $_SESSION['usuario_id']  ?? null,
        'nombre'      => $_SESSION['nombre']      ?? '',
        'email'       => $_SESSION['email']       ?? '',
        'rol'         => $_SESSION['rol']         ?? '',
        'idProveedor' => $_SESSION['idProveedor'] ?? null,
    ];
}

/**
 * Exige que el usuario esté logueado y tenga uno de los roles permitidos.
 * Si no cumple, redirige al login con un mensaje.
 *
 * @param string[] $rolesPermitidos  Ej: ['administrador','funcionario']
 *                                   Vacío [] = solo exige login
 */
function requireRole(array $rolesPermitidos = []): void {
    if (!estaLogueado()) {
        header('Location: ' . BASE_URL . '/auth/login.php?msg=sesion');
        exit;
    }
    if (!empty($rolesPermitidos) && !in_array(rolActual(), $rolesPermitidos, true)) {
        header('Location: ' . BASE_URL . '/auth/login.php?msg=acceso');
        exit;
    }
}

/**
 * Inicia sesión guardando los datos del usuario en $_SESSION.
 */
function iniciarSesion(array $usuario): void {
    session_regenerate_id(true);   // previene session fixation
    $_SESSION['usuario_id']  = $usuario['idUsuario'];
    $_SESSION['nombre']      = trim($usuario['nombres'] . ' ' . $usuario['apellido_1']);
    $_SESSION['email']       = $usuario['email'];
    $_SESSION['rol']         = $usuario['rol'];
    $_SESSION['idProveedor'] = $usuario['idProveedor'] ?? null;
}

/**
 * Destruye la sesión completamente.
 */
function cerrarSesion(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Devuelve la URL de inicio según el rol (para redirigir tras login).
 */
function urlPorRol(string $rol): string {
    return match($rol) {
        'administrador' => BASE_URL . '/index.php',
        'funcionario'   => BASE_URL . '/pages/reportes.php',
        default         => BASE_URL . '/pages/reportes.php',
    };
}
