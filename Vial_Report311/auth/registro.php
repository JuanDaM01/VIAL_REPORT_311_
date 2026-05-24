<?php
// auth/registro.php
// Registro de nuevos ciudadanos con diseño glassmorphism
// Valida inputs, hashea contraseña con bcrypt, inserta en BD

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Si ya está logueado, redirigir
if (estaLogueado()) {
    header('Location: ' . urlPorRol(rolActual()));
    exit;
}

$errors  = [];
$success = '';
$datos   = ['nombres' => '', 'apellido_1' => '', 'apellido_2' => '', 'email' => '', 'telefono' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombres'    => trim($_POST['nombres']    ?? ''),
        'apellido_1' => trim($_POST['apellido_1'] ?? ''),
        'apellido_2' => trim($_POST['apellido_2'] ?? ''),
        'email'      => trim($_POST['email']      ?? ''),
        'telefono'   => trim($_POST['telefono']   ?? ''),
    ];
    $pass1 = $_POST['password']  ?? '';
    $pass2 = $_POST['password2'] ?? '';

    // Validaciones
    if ($datos['nombres']    === '') $errors[] = 'El nombre es obligatorio.';
    if ($datos['apellido_1'] === '') $errors[] = 'El primer apellido es obligatorio.';
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo inválido.';
    if (strlen($pass1) < 6)   $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
    if ($pass1 !== $pass2)    $errors[] = 'Las contraseñas no coinciden.';

    if (empty($errors)) {
        $pdo = getConnection();

        // Email único
        $check = $pdo->prepare('SELECT idUsuario FROM usuario WHERE email = ? LIMIT 1');
        $check->execute([$datos['email']]);
        if ($check->fetch()) {
            $errors[] = 'Ese correo ya está registrado.';
        } else {
            $hash = password_hash($pass1, PASSWORD_BCRYPT);
            $ins  = $pdo->prepare('
                INSERT INTO usuario
                  (nombres, apellido_1, apellido_2, email, contrasena, telefono, rol, tipoRegistro, activo)
                VALUES
                  (?, ?, ?, ?, ?, ?, \'ciudadano\', \'local\', 1)
            ');
            $ins->execute([
                $datos['nombres'],
                $datos['apellido_1'],
                $datos['apellido_2'] ?: null,
                $datos['email'],
                $hash,
                $datos['telefono'] ?: null,
            ]);

            // Redirigir al login con mensaje de éxito
            header('Location: ' . BASE_URL . '/auth/login.php?msg=registro');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Crear cuenta — VialReport311</title>
  <meta name="description" content="Crea tu cuenta en VialReport311 y comienza a reportar problemas viales en tu ciudad."/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css"/>
  <style>
    body { font-family: 'Inter', sans-serif; }

    .auth-bg {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      padding: 1.5rem;
      background: var(--bg);
    }
    .auth-bg::before {
      content: '';
      position: fixed;
      inset: 0;
      background:
        radial-gradient(ellipse 70% 55% at 80% 15%,  rgba(41,128,185,.12)  0%, transparent 55%),
        radial-gradient(ellipse 60% 50% at 15% 85%,  rgba(245,166,35,.1)   0%, transparent 55%),
        radial-gradient(ellipse 50% 40% at 50% 50%,  rgba(39,174,96,.06)   0%, transparent 55%);
      animation: bgPulse 9s ease-in-out infinite alternate;
      pointer-events: none;
    }
    @keyframes bgPulse { from { opacity:.7; } to { opacity:1; } }

    .auth-card {
      position: relative;
      width: 100%;
      max-width: 520px;
      background: rgba(19,21,29,.78);
      backdrop-filter: blur(22px) saturate(1.4);
      -webkit-backdrop-filter: blur(22px) saturate(1.4);
      border: 1px solid rgba(255,255,255,.09);
      border-radius: 24px;
      padding: 2.6rem 2.4rem 2.2rem;
      box-shadow:
        0 8px 40px rgba(0,0,0,.45),
        0 0 0 1px rgba(41,128,185,.06) inset,
        0 1px 0 rgba(255,255,255,.07) inset;
      animation: cardIn .45s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes cardIn {
      from { opacity:0; transform:translateY(24px) scale(.97); }
      to   { opacity:1; transform:none; }
    }

    .auth-logo {
      display: flex; align-items: center; justify-content: center;
      gap: .55rem; margin-bottom: 1.7rem; text-decoration: none;
    }
    .auth-logo-icon {
      width: 42px; height: 42px;
      background: linear-gradient(135deg, var(--accent), #c77b1a);
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 16px rgba(245,166,35,.3);
    }
    .auth-logo-icon svg { width: 22px; height: 22px; color: #000; }
    .auth-logo-text { font-size: 1.35rem; font-weight: 800; color: var(--text); letter-spacing: -.5px; }
    .auth-logo-text span { color: var(--accent); }

    .auth-title { text-align: center; font-size: 1.45rem; font-weight: 800; margin-bottom: .3rem; letter-spacing: -.4px; }
    .auth-subtitle { text-align: center; color: var(--muted); font-size: .87rem; margin-bottom: 1.7rem; }

    .auth-form { display: flex; flex-direction: column; gap: .95rem; }

    .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
    @media (max-width: 480px) { .form-row-2 { grid-template-columns: 1fr; } }

    .field-group { display: flex; flex-direction: column; gap: 4px; }
    .field-group label { font-size: .75rem; font-weight: 600; color: var(--text2); letter-spacing: .25px; }

    .field-wrap { position: relative; }
    .field-wrap .field-icon {
      position: absolute; left: .8rem; top: 50%;
      transform: translateY(-50%); color: var(--muted);
      display: flex; pointer-events: none;
    }
    .field-wrap input {
      width: 100%;
      background: rgba(26,30,42,.65);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 10px;
      color: var(--text);
      font-family: 'Inter', sans-serif;
      font-size: .88rem;
      padding: .6rem .8rem .6rem 2.5rem;
      outline: none;
      transition: border-color .2s, box-shadow .2s;
    }
    .field-wrap input:focus {
      border-color: rgba(245,166,35,.6);
      box-shadow: 0 0 0 3px rgba(245,166,35,.1);
    }
    .field-wrap input::placeholder { color: var(--muted); }
    .field-wrap input.no-icon { padding-left: .8rem; }

    .toggle-pass {
      position: absolute; right: .8rem; top: 50%;
      transform: translateY(-50%);
      background: none; border: none; color: var(--muted);
      cursor: pointer; display: flex; padding: 2px;
      transition: color .15s;
    }
    .toggle-pass:hover { color: var(--text2); }

    /* Barra de fortaleza */
    .strength-bar {
      height: 3px;
      background: var(--surface3);
      border-radius: 3px;
      margin-top: 4px;
      overflow: hidden;
    }
    .strength-fill {
      height: 100%;
      border-radius: 3px;
      transition: width .3s, background .3s;
      width: 0%;
    }
    .strength-label { font-size: .7rem; color: var(--muted); margin-top: 2px; }

    /* Errors list */
    .auth-errors {
      background: var(--danger-bg);
      border: 1px solid rgba(231,76,60,.25);
      border-radius: 10px;
      padding: .75rem 1rem;
      margin-bottom: .5rem;
      animation: fadeIn .25s ease;
    }
    @keyframes fadeIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:none; } }
    .auth-errors ul { list-style: none; display: flex; flex-direction: column; gap: 3px; }
    .auth-errors li { font-size: .83rem; color: var(--danger); display: flex; gap: .4rem; align-items: flex-start; }
    .auth-errors li::before { content: '•'; flex-shrink: 0; }

    .btn-auth {
      width: 100%; padding: .75rem;
      background: linear-gradient(135deg, var(--accent) 0%, #c77b1a 100%);
      color: #000; font-family: 'Inter', sans-serif;
      font-size: .93rem; font-weight: 700;
      border: none; border-radius: 10px; cursor: pointer;
      transition: opacity .2s, transform .15s, box-shadow .2s;
      box-shadow: 0 4px 16px rgba(245,166,35,.28);
      margin-top: .2rem;
    }
    .btn-auth:hover { opacity:.92; transform:translateY(-1px); box-shadow:0 6px 22px rgba(245,166,35,.38); }
    .btn-auth:active { transform:translateY(0); }

    .auth-divider {
      display: flex; align-items: center; gap: .75rem;
      color: var(--muted); font-size: .78rem; margin: .3rem 0;
    }
    .auth-divider::before, .auth-divider::after {
      content: ''; flex: 1; height: 1px;
      background: rgba(255,255,255,.07);
    }

    .auth-footer { text-align: center; font-size: .84rem; color: var(--muted); margin-top: 1.5rem; }
    .auth-footer a { color: var(--accent); text-decoration: none; font-weight: 600; transition: color .15s; }
    .auth-footer a:hover { color: var(--accent2); }

    .terms-note { font-size: .74rem; color: var(--muted); text-align: center; margin-top: .6rem; line-height: 1.5; }
    .terms-note a { color: var(--accent); text-decoration: none; }
  </style>
</head>
<body>
<div class="auth-bg">
  <div class="auth-card">

    <!-- Logo -->
    <a class="auth-logo" href="<?= BASE_URL ?>/auth/login.php">
      <div class="auth-logo-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </div>
      <span class="auth-logo-text">Vial<span>Report311</span></span>
    </a>

    <h1 class="auth-title">Crear cuenta</h1>
    <p class="auth-subtitle">Únete para reportar problemas en tu ciudad</p>

    <!-- Errores -->
    <?php if (!empty($errors)): ?>
      <div class="auth-errors" role="alert">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form class="auth-form" method="POST" id="regForm" novalidate>

      <!-- Nombres -->
      <div class="form-row-2">
        <div class="field-group">
          <label for="nombres">Nombre(s) *</label>
          <div class="field-wrap">
            <span class="field-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <input id="nombres" type="text" name="nombres" autocomplete="given-name"
              placeholder="Carlos" required
              value="<?= htmlspecialchars($datos['nombres']) ?>"/>
          </div>
        </div>

        <div class="field-group">
          <label for="apellido_1">Primer apellido *</label>
          <div class="field-wrap">
            <span class="field-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <input id="apellido_1" type="text" name="apellido_1" autocomplete="family-name"
              placeholder="Gómez" required
              value="<?= htmlspecialchars($datos['apellido_1']) ?>"/>
          </div>
        </div>
      </div>

      <!-- Segundo apellido + Teléfono -->
      <div class="form-row-2">
        <div class="field-group">
          <label for="apellido_2">Segundo apellido</label>
          <div class="field-wrap">
            <span class="field-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <input id="apellido_2" type="text" name="apellido_2"
              placeholder="Ruiz (opcional)"
              value="<?= htmlspecialchars($datos['apellido_2']) ?>"/>
          </div>
        </div>

        <div class="field-group">
          <label for="telefono">Teléfono</label>
          <div class="field-wrap">
            <span class="field-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.1 11.4 19.79 19.79 0 0 1 1 2.18 2 2 0 0 1 2.96 0h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 7.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </span>
            <input id="telefono" type="tel" name="telefono"
              placeholder="310 123 4567 (opcional)"
              value="<?= htmlspecialchars($datos['telefono']) ?>"/>
          </div>
        </div>
      </div>

      <!-- Email -->
      <div class="field-group">
        <label for="email">Correo electrónico *</label>
        <div class="field-wrap">
          <span class="field-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          </span>
          <input id="email" type="email" name="email" autocomplete="email"
            placeholder="tu@correo.com" required
            value="<?= htmlspecialchars($datos['email']) ?>"/>
        </div>
      </div>

      <!-- Password -->
      <div class="field-group">
        <label for="password">Contraseña *</label>
        <div class="field-wrap">
          <span class="field-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
          <input id="password" type="password" name="password"
            autocomplete="new-password" placeholder="Mínimo 6 caracteres" required/>
          <button type="button" class="toggle-pass" id="togglePass1" aria-label="Ver contraseña">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        <span class="strength-label" id="strengthLabel"></span>
      </div>

      <!-- Confirm Password -->
      <div class="field-group">
        <label for="password2">Confirmar contraseña *</label>
        <div class="field-wrap">
          <span class="field-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
          <input id="password2" type="password" name="password2"
            autocomplete="new-password" placeholder="Repite la contraseña" required/>
          <button type="button" class="toggle-pass" id="togglePass2" aria-label="Ver contraseña">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-auth" id="submitBtn">
        Crear cuenta
      </button>

    </form>

    <p class="terms-note">Al registrarte aceptas los <a href="#">Términos de uso</a> y la <a href="#">Política de privacidad</a></p>

    <div class="auth-footer">
      ¿Ya tienes cuenta? <a href="<?= BASE_URL ?>/auth/login.php">Iniciar sesión</a>
    </div>

  </div>
</div>

<script>
// Toggle password visibility
function togglePassword(inputId, btnId) {
  const input = document.getElementById(inputId);
  const btn   = document.getElementById(btnId);
  const EYE_OPEN   = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
  const EYE_CLOSED = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`;
  btn.addEventListener('click', () => {
    const isPass = input.type === 'password';
    input.type   = isPass ? 'text' : 'password';
    btn.querySelector('svg').innerHTML = isPass ? EYE_CLOSED : EYE_OPEN;
  });
}
togglePassword('password',  'togglePass1');
togglePassword('password2', 'togglePass2');

// Indicador de fortaleza de contraseña
const passInput    = document.getElementById('password');
const strengthFill = document.getElementById('strengthFill');
const strengthLbl  = document.getElementById('strengthLabel');

passInput.addEventListener('input', () => {
  const v = passInput.value;
  let score = 0;
  if (v.length >= 6)  score++;
  if (v.length >= 10) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;

  const levels = [
    { pct: '0%',   bg: 'transparent', label: '' },
    { pct: '25%',  bg: '#e74c3c',     label: 'Muy débil' },
    { pct: '50%',  bg: '#e67e22',     label: 'Débil' },
    { pct: '65%',  bg: '#f1c40f',     label: 'Regular' },
    { pct: '85%',  bg: '#27ae60',     label: 'Fuerte' },
    { pct: '100%', bg: '#2ecc71',     label: '¡Muy fuerte!' },
  ];
  const lvl = levels[Math.min(score, 5)];
  strengthFill.style.width      = lvl.pct;
  strengthFill.style.background = lvl.bg;
  strengthLbl.textContent       = lvl.label;
});

// Submit feedback
document.getElementById('regForm').addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  btn.textContent = 'Creando cuenta…';
  btn.disabled = true;
  btn.style.opacity = '.7';
});
</script>
</body>
</html>
