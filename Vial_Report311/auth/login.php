<?php
// auth/login.php
// Página de login con diseño glassmorphism
// POST → valida credenciales → inicia sesión → redirige por rol

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Si ya está logueado, redirigir
if (estaLogueado()) {
    header('Location: ' . urlPorRol(rolActual()));
    exit;
}

$error   = '';
$success = '';

// ── Mensaje GET ──────────────────────────────────────────────
$msg = $_GET['msg'] ?? '';
if ($msg === 'sesion')  $error   = '⚠️ Debes iniciar sesión para acceder a esa página.';
if ($msg === 'acceso')  $error   = '🚫 No tienes permisos para acceder a esa sección.';
if ($msg === 'logout')  $success = '✅ Sesión cerrada correctamente.';
if ($msg === 'registro') $success = '✅ Cuenta creada. Ya puedes iniciar sesión.';

// ── Procesar POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Completa todos los campos.';
    } else {
        $pdo  = getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM usuario WHERE email = ? AND activo = 1 LIMIT 1'
        );
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            $error = 'Correo o contraseña incorrectos.';
        } else {
            // Soportar bcrypt (nuevos usuarios) Y MD5 (datos demo)
            $md5match    = ($usuario['contrasena'] === md5($password));
            $bcryptMatch = password_verify($password, $usuario['contrasena']);

            if ($md5match || $bcryptMatch) {
                // Migrar MD5 → bcrypt automáticamente
                if ($md5match) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $pdo->prepare('UPDATE usuario SET contrasena = ? WHERE idUsuario = ?')
                        ->execute([$hash, $usuario['idUsuario']]);
                }

                iniciarSesion($usuario);
                header('Location: ' . urlPorRol($usuario['rol']));
                exit;
            } else {
                $error = 'Correo o contraseña incorrectos.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Iniciar sesión — VialReport311</title>
  <meta name="description" content="Accede a VialReport311 para reportar y gestionar problemas viales en tu ciudad."/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css"/>
  <style>
    /* ── Auth layout ── */
    body { font-family: 'Inter', sans-serif; overflow: hidden; }

    .auth-bg {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      padding: 1.5rem;
      background: var(--bg);
    }

    /* Fondo animado con gradientes */
    .auth-bg::before {
      content: '';
      position: fixed;
      inset: 0;
      background:
        radial-gradient(ellipse 80% 60% at 20% 10%,  rgba(245,166,35,.12) 0%, transparent 55%),
        radial-gradient(ellipse 60% 50% at 80% 80%,  rgba(41,128,185,.1)  0%, transparent 55%),
        radial-gradient(ellipse 50% 40% at 55% 45%,  rgba(142,68,173,.07) 0%, transparent 55%);
      animation: bgPulse 8s ease-in-out infinite alternate;
      pointer-events: none;
    }
    @keyframes bgPulse {
      from { opacity: .7; }
      to   { opacity: 1; }
    }

    /* Partículas decorativas */
    .auth-bg::after {
      content: '';
      position: fixed;
      width: 350px; height: 350px;
      border-radius: 50%;
      border: 1px solid rgba(245,166,35,.08);
      top: -80px; right: -80px;
      animation: spin 25s linear infinite;
      pointer-events: none;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Card glassmorphism ── */
    .auth-card {
      position: relative;
      width: 100%;
      max-width: 430px;
      background: rgba(19,21,29,.78);
      backdrop-filter: blur(22px) saturate(1.4);
      -webkit-backdrop-filter: blur(22px) saturate(1.4);
      border: 1px solid rgba(255,255,255,.09);
      border-radius: 24px;
      padding: 2.8rem 2.4rem 2.4rem;
      box-shadow:
        0 8px 40px rgba(0,0,0,.45),
        0 0 0 1px rgba(245,166,35,.06) inset,
        0 1px 0 rgba(255,255,255,.07) inset;
      animation: cardIn .45s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes cardIn {
      from { opacity:0; transform:translateY(24px) scale(.97); }
      to   { opacity:1; transform:none; }
    }

    /* ── Logo ── */
    .auth-logo {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .55rem;
      margin-bottom: 2rem;
      text-decoration: none;
    }
    .auth-logo-icon {
      width: 46px; height: 46px;
      background: linear-gradient(135deg, var(--accent), #c77b1a);
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 18px rgba(245,166,35,.35);
    }
    .auth-logo-icon svg { width: 24px; height: 24px; color: #000; }
    .auth-logo-text {
      font-size: 1.45rem;
      font-weight: 800;
      color: var(--text);
      letter-spacing: -.5px;
    }
    .auth-logo-text span { color: var(--accent); }

    /* ── Heading ── */
    .auth-title {
      text-align: center;
      font-size: 1.55rem;
      font-weight: 800;
      margin-bottom: .35rem;
      letter-spacing: -.4px;
    }
    .auth-subtitle {
      text-align: center;
      color: var(--muted);
      font-size: .88rem;
      margin-bottom: 1.9rem;
    }

    /* ── Alerts ── */
    .auth-alert {
      border-radius: 10px;
      padding: .7rem 1rem;
      font-size: .85rem;
      font-weight: 500;
      margin-bottom: 1.2rem;
      display: flex;
      align-items: center;
      gap: .5rem;
      animation: fadeIn .25s ease;
    }
    @keyframes fadeIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:none; } }
    .auth-alert-err { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(231,76,60,.25); }
    .auth-alert-ok  { background: var(--ok-bg);     color: var(--ok);     border: 1px solid rgba(39,174,96,.25); }

    /* ── Form ── */
    .auth-form { display: flex; flex-direction: column; gap: 1.1rem; }

    .field-group { display: flex; flex-direction: column; gap: 5px; }
    .field-group label {
      font-size: .77rem;
      font-weight: 600;
      color: var(--text2);
      letter-spacing: .25px;
    }

    .field-wrap {
      position: relative;
    }
    .field-wrap .field-icon {
      position: absolute;
      left: .85rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      display: flex;
      pointer-events: none;
    }
    .field-wrap input {
      width: 100%;
      background: rgba(26,30,42,.65);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 10px;
      color: var(--text);
      font-family: 'Inter', sans-serif;
      font-size: .9rem;
      padding: .65rem .85rem .65rem 2.6rem;
      outline: none;
      transition: border-color .2s, box-shadow .2s;
    }
    .field-wrap input:focus {
      border-color: rgba(245,166,35,.6);
      box-shadow: 0 0 0 3px rgba(245,166,35,.1);
    }
    .field-wrap input::placeholder { color: var(--muted); }

    /* Toggle password */
    .toggle-pass {
      position: absolute;
      right: .85rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--muted);
      cursor: pointer;
      display: flex;
      padding: 2px;
      transition: color .15s;
    }
    .toggle-pass:hover { color: var(--text2); }

    /* ── Submit button ── */
    .btn-auth {
      width: 100%;
      padding: .78rem;
      background: linear-gradient(135deg, var(--accent) 0%, #c77b1a 100%);
      color: #000;
      font-family: 'Inter', sans-serif;
      font-size: .95rem;
      font-weight: 700;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: opacity .2s, transform .15s, box-shadow .2s;
      box-shadow: 0 4px 18px rgba(245,166,35,.3);
      letter-spacing: .2px;
      margin-top: .3rem;
    }
    .btn-auth:hover {
      opacity: .92;
      transform: translateY(-1px);
      box-shadow: 0 6px 24px rgba(245,166,35,.4);
    }
    .btn-auth:active { transform: translateY(0); }

    /* ── Divider ── */
    .auth-divider {
      display: flex;
      align-items: center;
      gap: .75rem;
      color: var(--muted);
      font-size: .78rem;
      margin: .4rem 0;
    }
    .auth-divider::before,
    .auth-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: rgba(255,255,255,.07);
    }

    /* ── Footer link ── */
    .auth-footer {
      text-align: center;
      font-size: .84rem;
      color: var(--muted);
      margin-top: 1.6rem;
    }
    .auth-footer a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 600;
      transition: color .15s;
    }
    .auth-footer a:hover { color: var(--accent2); }

    /* ── Demo hint ── */
    .demo-hint {
      background: rgba(245,166,35,.06);
      border: 1px solid rgba(245,166,35,.12);
      border-radius: 10px;
      padding: .75rem 1rem;
      margin-top: 1.3rem;
    }
    .demo-hint p {
      font-size: .76rem;
      color: var(--muted);
      margin: 0;
      line-height: 1.6;
    }
    .demo-hint strong { color: var(--text2); }
    .demo-hint .demo-title {
      font-size: .7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .8px;
      color: var(--accent);
      margin-bottom: .35rem;
    }
  </style>
</head>
<body>
<div class="auth-bg">
  <div class="auth-card">

    <!-- Logo -->
    <a class="auth-logo" href="<?= BASE_URL ?>/index.php">
      <div class="auth-logo-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </div>
      <span class="auth-logo-text">Vial<span>Report311</span></span>
    </a>

    <h1 class="auth-title">Bienvenido de vuelta</h1>
    <p class="auth-subtitle">Inicia sesión para continuar</p>

    <!-- Alertas -->
    <?php if ($error): ?>
      <div class="auth-alert auth-alert-err" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="auth-alert auth-alert-ok" role="alert"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Formulario -->
    <form class="auth-form" method="POST" id="loginForm" action="" novalidate>

      <div class="field-group">
        <label for="email">Correo electrónico</label>
        <div class="field-wrap">
          <span class="field-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          </span>
          <input
            id="email"
            type="email"
            name="email"
            autocomplete="email"
            placeholder="tu@correo.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
          />
        </div>
      </div>

      <div class="field-group">
        <label for="password">Contraseña</label>
        <div class="field-wrap">
          <span class="field-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
          <input
            id="password"
            type="password"
            name="password"
            autocomplete="current-password"
            placeholder="••••••••"
            required
          />
          <button type="button" class="toggle-pass" id="togglePass" aria-label="Ver contraseña">
            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-auth" id="submitBtn">
        Iniciar sesión
      </button>

    </form>

    <div class="auth-divider">o</div>

    <div class="auth-footer">
      ¿No tienes cuenta?
      <a href="<?= BASE_URL ?>/auth/registro.php">Regístrate gratis</a>
    </div>

    <!-- Demo credentials -->
    <div class="demo-hint">
      <div class="demo-title">🔑 Credenciales de demo</div>
      <p>
        <strong>Admin:</strong> sofia.admin@email.com / pass6<br/>
        <strong>Funcionario:</strong> luis.funcionario@email.com / pass5<br/>
        <strong>Ciudadano:</strong> carlos@email.com / pass1
      </p>
    </div>

  </div>
</div>

<script>
// Toggle ver contraseña
const toggleBtn = document.getElementById('togglePass');
const passInput = document.getElementById('password');
const eyeIcon   = document.getElementById('eyeIcon');

const EYE_OPEN   = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
const EYE_CLOSED = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`;

toggleBtn.addEventListener('click', () => {
  const isPass = passInput.type === 'password';
  passInput.type = isPass ? 'text' : 'password';
  eyeIcon.innerHTML = isPass ? EYE_CLOSED : EYE_OPEN;
});

// Feedback visual al enviar
document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  btn.textContent = 'Verificando…';
  btn.disabled = true;
  btn.style.opacity = '.7';
});
</script>
</body>
</html>
