<?php
// pages/navbar.php
// Navbar principal — rutas absolutas vía BASE_URL
// Con control de sesión y filtrado por rol

require_once __DIR__ . '/../config/session.php';

$current  = basename($_SERVER['PHP_SELF'], '.php');
$logueado = estaLogueado();
$rol      = rolActual();   // ciudadano | funcionario | administrador | ''
$usuario  = usuarioSesion();

// ── Grupos por rol ─────────────────────────────────────────
// URL de inicio según rol
$urlInicio = ($rol === 'administrador') ? URL_HOME : URL_REPORTES;
$slugInicio = ($rol === 'administrador') ? 'index' : 'reportes';
$urlBrand = ($rol === 'administrador') ? URL_HOME : URL_REPORTES;

// Todos ven Inicio y Reportes públicos, pero ciudadano y funcionario con menú simplificado sin redundancia ni duplicidad
$itemsComun = [];
if ($rol === 'ciudadano') {
    $itemsComun = [
        ['url' => URL_REPORTES, 'slug' => 'reportes', 'icon' => 'home', 'label' => 'Inicio'],
        ['url' => URL_ALERTAS, 'slug' => 'alertas', 'icon' => 'bell-ring', 'label' => 'Alertas Locales'],
    ];
} elseif ($rol === 'funcionario') {
    $itemsComun = [
        ['url' => URL_REPORTES, 'slug' => 'reportes', 'icon' => 'home', 'label' => 'Panel de Trabajo']
    ];
} else {
    $itemsComun = [
        ['url' => $urlInicio,    'slug' => $slugInicio, 'icon' => 'home',      'label' => 'Inicio'],
        ['url' => URL_REPORTES,  'slug' => 'reportes',  'icon' => 'flag',      'label' => 'Reportes'],
        ['url' => URL_VOTACIONES, 'slug' => 'votaciones','icon'=> 'thumbs-up', 'label' => 'Votaciones'],
        ['url' => URL_COMENTARIOS,'slug' => 'comentarios','icon'=>'message',   'label' => 'Comentarios'],
    ];
}

$gruposComun = [
    'label' => 'Comunidad',
    'items' => $itemsComun,
];

$gruposOperaciones = [
    'label' => ($rol === 'funcionario') ? 'Gestión de Casos' : 'Operaciones',
    'items' => [
        ['url' => URL_TICKETS,     'slug' => 'tickets',   'icon' => 'ticket',    'label' => ($rol === 'funcionario') ? 'Mis Tickets' : 'Tickets'],
        ['url' => URL_EVIDENCIAS,  'slug' => 'evidencias','icon' => 'paperclip', 'label' => 'Evidencias'],
    ],
];

$gruposAdmin = [
    'label' => 'Administración',
    'items' => [
        ['url' => URL_USUARIOS,    'slug' => 'usuarios',    'icon' => 'users',    'label' => 'Usuarios'],
        ['url' => URL_PROVEEDORES, 'slug' => 'proveedores', 'icon' => 'building', 'label' => 'Proveedores'],
        ['url' => URL_CATEGORIAS,  'slug' => 'categorias',  'icon' => 'tag',      'label' => 'Categorías'],
        ['url' => URL_UBICACIONES, 'slug' => 'ubicaciones', 'icon' => 'map-pin',  'label' => 'Ubicaciones'],
        ['url' => URL_ALERTAS,     'slug' => 'alertas',     'icon' => 'bell-ring','label' => 'Alertas'],
        ['url' => URL_NOTIFICACIONES,'slug'=>'notificaciones','icon'=>'bell',      'label' => 'Notificaciones'],
    ],
];

// Armar lista de grupos visible según rol
$grupos = [$gruposComun];
if (in_array($rol, ['funcionario','administrador'])) {
    $grupos[] = $gruposOperaciones;
}
if ($rol === 'administrador') {
    $grupos[] = $gruposAdmin;
}

// Iconos SVG inline (no dependen de CDN — soluciona el problema de iconos que no cargan)
$svgIcons = [
    'home'       => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
    'users'      => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'flag'       => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>',
    'thumbs-up'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14z"/><path d="M7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>',
    'ticket'     => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>',
    'building'   => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>',
    'paperclip'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>',
    'message'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    'tag'        => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
    'map-pin'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    'bell-ring'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/><line x1="2" y1="2" x2="22" y2="22"/></svg>',
    'bell'       => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
    'menu'       => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
    'x'          => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    'warning'    => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
];
?>

<nav class="navbar" id="mainNav">
  <!-- ── Brand ── -->
  <a class="brand" href="<?= $urlBrand ?>">
    <span class="brand-icon"><?= $svgIcons['warning'] ?></span>
    Vial<span>Report311</span>
  </a>

  <!-- ── Toggle mobile ── -->
  <button class="nav-toggle" id="navToggle" aria-label="Menú">
    <span id="navToggleIcon"><?= $svgIcons['menu'] ?></span>
  </button>

  <!-- ── Links agrupados ── -->
  <div class="nav-body" id="navBody">
    <?php foreach ($grupos as $grupo): ?>
      <div class="nav-group">
        <span class="nav-group-label"><?= htmlspecialchars($grupo['label']) ?></span>
        <div class="nav-group-items">
          <?php foreach ($grupo['items'] as $item):
            $isActive = ($current === $item['slug']);
          ?>
            <a href="<?= $item['url'] ?>"
               class="nav-link <?= $isActive ? 'active' : '' ?>"
               title="<?= htmlspecialchars($item['label']) ?>">
              <?= $svgIcons[$item['icon']] ?>
              <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- ── Usuario / Sesión ── -->
    <div class="nav-group nav-group-session">
      <span class="nav-group-label">Sesión</span>
      <div class="nav-group-items">

        <?php if ($logueado): ?>

          <!-- Badge de rol -->
          <span class="nav-badge-rol badge badge-<?= htmlspecialchars($rol) ?>">
            <?= ucfirst(htmlspecialchars($rol)) ?>
          </span>

          <!-- Nombre usuario -->
          <span class="nav-user" title="<?= htmlspecialchars($usuario['email']) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <?= htmlspecialchars(mb_strimwidth($usuario['nombre'], 0, 18, '…')) ?>
          </span>

          <!-- Logout -->
          <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link nav-logout" title="Cerrar sesión">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span>Salir</span>
          </a>

        <?php else: ?>

          <a href="<?= BASE_URL ?>/auth/login.php" class="nav-link <?= $current==='login'?'active':'' ?>" title="Iniciar sesión">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            <span>Entrar</span>
          </a>

          <a href="<?= BASE_URL ?>/auth/registro.php" class="nav-link nav-reg <?= $current==='registro'?'active':'' ?>" title="Crear cuenta">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            <span>Registro</span>
          </a>

        <?php endif; ?>
      </div>
    </div>

  </div>
</nav>

<style>
  .nav-user {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: .8rem;
    font-weight: 600;
    color: var(--text2);
    white-space: nowrap;
    padding: 6px 8px;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .nav-user svg { flex-shrink: 0; opacity: .7; }
  .nav-badge-rol {
    font-size: .65rem;
    padding: 2px 8px;
    border-radius: 20px;
    font-weight: 700;
    white-space: nowrap;
  }
  .nav-logout { color: var(--danger) !important; }
  .nav-logout:hover { background: rgba(231,76,60,.08) !important; }
  .nav-reg { color: var(--accent) !important; }
  .nav-reg:hover { background: rgba(245,166,35,.08) !important; }
  .nav-group-session { margin-left: auto; border-left: 1px solid var(--border); border-right: none; }
  @media (max-width: 900px) {
    .nav-group-session { margin-left: 0; border-left: none; border-top: 1px solid var(--border); }
  }
</style>

<script>
(function () {
  const toggle = document.getElementById('navToggle');
  const body   = document.getElementById('navBody');
  const icon   = document.getElementById('navToggleIcon');

  const OPEN_ICON  = <?= json_encode($svgIcons['x']) ?>;
  const CLOSE_ICON = <?= json_encode($svgIcons['menu']) ?>;

  toggle.addEventListener('click', () => {
    const open = body.classList.toggle('open');
    icon.innerHTML = open ? OPEN_ICON : CLOSE_ICON;
  });

  // Cerrar al hacer click fuera
  document.addEventListener('click', (e) => {
    if (!e.target.closest('#mainNav')) {
      body.classList.remove('open');
      icon.innerHTML = CLOSE_ICON;
    }
  });
})();
</script>