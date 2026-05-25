<?php
require_once __DIR__ . '/../config/session.php';
requireRole();
$rol      = rolActual();
$usuario  = usuarioSesion();
$esCiudadano = ($rol === 'ciudadano');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= $esCiudadano ? 'Mis Notificaciones' : 'Gestión de Notificaciones' ?> — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
  <style>

    /* ══════════════════════════════════════════════════
       VISTA CIUDADANO — NOTIFICACIONES
       Diseño tipo bandeja de entrada personal
    ══════════════════════════════════════════════════ */

    /* ── Indicador de no leídas en header ── */
    .notif-header-count {
      display: inline-flex;
      align-items: center;
      gap: .45rem;
      background: var(--accent);
      color: #000;
      font-size: .72rem;
      font-weight: 800;
      padding: 3px 10px;
      border-radius: 20px;
      vertical-align: middle;
      letter-spacing: .02em;
    }
    .notif-header-count.zero {
      background: var(--surface3);
      color: var(--muted);
    }

    /* ── Tabs filtro ciudadano ── */
    .notif-tabs {
      display: flex;
      gap: .4rem;
      margin-bottom: 1.5rem;
      border-bottom: 1px solid var(--border);
      padding-bottom: 0;
    }
    .notif-tab {
      background: none;
      border: none;
      border-bottom: 2px solid transparent;
      color: var(--muted);
      font-size: .82rem;
      font-weight: 600;
      padding: .55rem 1rem;
      cursor: pointer;
      transition: color .15s, border-color .15s;
      margin-bottom: -1px;
      white-space: nowrap;
      position: relative;
    }
    .notif-tab:hover { color: var(--text2); }
    .notif-tab.active {
      color: var(--accent);
      border-bottom-color: var(--accent);
    }
    .tab-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: var(--accent);
      color: #000;
      font-size: .6rem;
      font-weight: 800;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      margin-left: 4px;
      vertical-align: middle;
    }
    .tab-badge.hidden { display: none; }

    /* ── Acciones masivas ── */
    .notif-actions {
      display: flex;
      justify-content: flex-end;
      gap: .5rem;
      margin-bottom: 1rem;
    }

    /* ── Lista de notificaciones (bandeja) ── */
    .notif-list {
      display: flex;
      flex-direction: column;
      gap: .5rem;
    }

    .notif-card {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1rem 1.1rem;
      cursor: pointer;
      transition: background .14s, border-color .14s, transform .1s;
      position: relative;
    }
    .notif-card:hover {
      background: var(--surface2);
      border-color: var(--border2);
      transform: translateX(2px);
    }
    .notif-card.unread {
      border-left: 3px solid var(--accent);
      background: linear-gradient(90deg, rgba(245,166,35,.05) 0%, var(--surface) 60%);
    }
    .notif-card.unread:hover {
      background: linear-gradient(90deg, rgba(245,166,35,.09) 0%, var(--surface2) 60%);
    }

    /* Icono tipo */
    .notif-icon {
      flex-shrink: 0;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .notif-icon.tipo-cambio_estado   { background: rgba(41,128,185,.15);  color: #2980b9; }
    .notif-icon.tipo-comentario      { background: rgba(39,174,96,.15);   color: #27ae60; }
    .notif-icon.tipo-alerta_local    { background: rgba(245,166,35,.15);  color: #f5a623; }
    .notif-icon.tipo-creacion_reporte{ background: rgba(155,89,182,.15);  color: #9b59b6; }
    .notif-icon.tipo-ticket_asignado { background: rgba(231,76,60,.15);   color: #e74c3c; }
    .notif-icon.tipo-voto            { background: rgba(26,188,156,.15);  color: #1abc9c; }

    /* Contenido */
    .notif-content { flex: 1; min-width: 0; }

    .notif-meta {
      display: flex;
      align-items: center;
      gap: .5rem;
      flex-wrap: wrap;
      margin-bottom: .25rem;
    }

    .notif-titulo {
      font-size: .9rem;
      font-weight: 600;
      color: var(--text);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .notif-card.unread .notif-titulo { color: #fff; font-weight: 700; }

    .notif-tipo-tag {
      font-size: .62rem;
      font-weight: 700;
      padding: 1px 7px;
      border-radius: 20px;
      white-space: nowrap;
      text-transform: uppercase;
      letter-spacing: .04em;
    }
    .notif-tipo-tag.cambio_estado    { background: rgba(41,128,185,.18);  color: #5ba4d4; }
    .notif-tipo-tag.comentario       { background: rgba(39,174,96,.18);   color: #52d68a; }
    .notif-tipo-tag.alerta_local     { background: rgba(245,166,35,.18);  color: #f5a623; }
    .notif-tipo-tag.creacion_reporte { background: rgba(155,89,182,.18);  color: #c39bd3; }
    .notif-tipo-tag.ticket_asignado  { background: rgba(231,76,60,.18);   color: #f08080; }
    .notif-tipo-tag.voto             { background: rgba(26,188,156,.18);  color: #1abc9c; }

    .notif-mensaje {
      font-size: .82rem;
      color: var(--text2);
      margin-bottom: .35rem;
      line-height: 1.45;
      /* Truncar a 2 líneas */
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .notif-footer {
      display: flex;
      align-items: center;
      gap: .75rem;
      flex-wrap: wrap;
    }

    .notif-fecha {
      font-size: .72rem;
      color: var(--muted);
    }

    .notif-reporte-link {
      font-size: .72rem;
      color: var(--accent);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 3px;
    }
    .notif-reporte-link:hover { text-decoration: underline; }

    /* Punto no leído */
    .notif-dot {
      flex-shrink: 0;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--accent);
      margin-top: 5px;
      box-shadow: 0 0 6px rgba(245,166,35,.5);
    }
    .notif-card.read .notif-dot { opacity: 0; }

    /* Botón marcar leída */
    .btn-mark-read {
      flex-shrink: 0;
      background: none;
      border: 1px solid var(--border2);
      border-radius: 6px;
      color: var(--muted);
      font-size: .7rem;
      padding: 3px 8px;
      cursor: pointer;
      transition: color .13s, border-color .13s, background .13s;
      white-space: nowrap;
    }
    .btn-mark-read:hover {
      color: var(--accent);
      border-color: var(--accent);
      background: rgba(245,166,35,.08);
    }

    /* ── Estado vacío ── */
    .notif-empty {
      text-align: center;
      padding: 4rem 2rem;
      color: var(--muted);
    }
    .notif-empty svg { opacity: .3; margin-bottom: 1rem; }
    .notif-empty h3 { font-size: 1rem; font-weight: 600; margin-bottom: .4rem; color: var(--text2); }
    .notif-empty p  { font-size: .82rem; line-height: 1.5; }

    /* ── Skeleton loader ── */
    .notif-skeleton {
      display: flex;
      flex-direction: column;
      gap: .5rem;
    }
    .sk-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1rem 1.1rem;
      display: flex;
      align-items: flex-start;
      gap: 1rem;
    }
    .sk-circle {
      width: 40px; height: 40px;
      border-radius: 50%;
      background: var(--surface3);
      flex-shrink: 0;
      animation: sk-pulse 1.4s ease-in-out infinite;
    }
    .sk-lines { flex: 1; display: flex; flex-direction: column; gap: 8px; }
    .sk-line {
      height: 10px;
      border-radius: 5px;
      background: var(--surface3);
      animation: sk-pulse 1.4s ease-in-out infinite;
    }
    .sk-line.w80 { width: 80%; }
    .sk-line.w60 { width: 60%; }
    .sk-line.w40 { width: 40%; }
    @keyframes sk-pulse {
      0%,100% { opacity: .45; }
      50%      { opacity: .9;  }
    }

    /* ══════════════════════════════════════════════════
       PANEL ADMIN (tabla clásica) — oculto para ciudadano
    ══════════════════════════════════════════════════ */
    .admin-panel { display: none; }
    body.role-administrador .admin-panel  { display: block; }
    body.role-administrador .citizen-panel { display: none; }
    body.role-funcionario .admin-panel  { display: block; }
    body.role-funcionario .citizen-panel { display: none; }

    /* Badge admin */
    .badge-cambio_estado    { background: var(--info-bg);   color: #5ba4d4; }
    .badge-comentario       { background: var(--ok-bg);     color: #52d68a; }
    .badge-alerta_local     { background: var(--warn-bg);   color: #f5a623; }
    .badge-creacion_reporte { background: rgba(155,89,182,.15); color: #c39bd3; }
    .badge-ticket_asignado  { background: var(--danger-bg); color: #f08080; }
    .badge-voto             { background: rgba(26,188,156,.15); color: #1abc9c; }

    /* Responsive notif-card */
    @media (max-width: 600px) {
      .notif-card { gap: .65rem; padding: .8rem .9rem; }
      .notif-icon { width: 34px; height: 34px; }
      .notif-actions { flex-direction: column; }
    }
  </style>
</head>
<body class="role-<?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?>">

<?php include 'navbar.php'; ?>

<div class="page">

  <!-- ════════════════════════════════════════════════════
       VISTA CIUDADANO
  ═════════════════════════════════════════════════════ -->
  <div class="citizen-panel">

    <div class="page-header" style="margin-bottom:.75rem;">
      <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
        <h2>Mis <span>Notificaciones</span></h2>
        <span class="notif-header-count zero" id="countBadge">0 nuevas</span>
      </div>
      <button class="btn btn-ghost" onclick="marcarTodasLeidas()" id="btnMarcarTodas" style="display:none;">
        ✓ Marcar todas como leídas
      </button>
    </div>

    <p style="color:var(--text2);font-size:.84rem;margin-bottom:1.25rem;max-width:580px;line-height:1.5;">
      Aquí encontrarás actualizaciones sobre tus reportes, alertas locales activas y comentarios de la comunidad.
    </p>

    <!-- Tabs de filtro -->
    <div class="notif-tabs">
      <button class="notif-tab active" onclick="setTab('todas',   this)">Todas</button>
      <button class="notif-tab"        onclick="setTab('nuevas',  this)">
        No leídas <span class="tab-badge hidden" id="tabBadgeNuevas">0</span>
      </button>
      <button class="notif-tab"        onclick="setTab('cambio_estado',    this)">
        <span style="display:flex;align-items:center;gap:4px;">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><polyline points="12 6 12 12 16 14"/></svg>
          Estado
        </span>
      </button>
      <button class="notif-tab" onclick="setTab('comentario', this)">
        <span style="display:flex;align-items:center;gap:4px;">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          Comentarios
        </span>
      </button>
      <button class="notif-tab" onclick="setTab('alerta_local', this)">
        <span style="display:flex;align-items:center;gap:4px;">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          Alertas
        </span>
      </button>
    </div>

    <!-- Skeleton mientras carga -->
    <div class="notif-skeleton" id="skeletonLoader">
      <?php for($i=0;$i<4;$i++): ?>
      <div class="sk-card">
        <div class="sk-circle"></div>
        <div class="sk-lines">
          <div class="sk-line w80"></div>
          <div class="sk-line w60"></div>
          <div class="sk-line w40"></div>
        </div>
      </div>
      <?php endfor; ?>
    </div>

    <!-- Lista real -->
    <div class="notif-list" id="notifList" style="display:none;"></div>

    <!-- Estado vacío -->
    <div class="notif-empty" id="notifEmpty" style="display:none;">
      <svg width="56" height="56" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      <h3 id="emptyTitle">No tienes notificaciones</h3>
      <p id="emptyMsg">Cuando ocurra algo relacionado con tus reportes o alertas locales, te avisaremos aquí.</p>
    </div>

  </div><!-- /citizen-panel -->


  <!-- ════════════════════════════════════════════════════
       VISTA ADMIN / FUNCIONARIO
  ═════════════════════════════════════════════════════ -->
  <div class="admin-panel">

    <div class="page-header">
      <h2>Gestión de <span>Notificaciones</span></h2>
      <button class="btn btn-primary" onclick="abrirModal()">+ Nueva notificación</button>
    </div>

    <div class="filters">
      <button class="btn btn-ghost" onclick="filtrarLectura('')">Todas</button>
      <button class="btn btn-ghost" onclick="filtrarLectura('0')">No leídas</button>
      <button class="btn btn-ghost" onclick="filtrarLectura('1')">Leídas</button>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Título</th><th>Mensaje</th><th>Tipo</th>
            <th>Estado</th><th>Usuario</th><th>Reporte</th><th>Fecha</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tbody">
          <tr class="empty-row"><td colspan="9">Cargando...</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Modal admin -->
    <div class="modal-overlay" id="modal">
      <div class="modal">
        <div class="modal-head">
          <h3 id="modalTit">Nueva Notificación</h3>
          <button onclick="cerrarModal()">✕</button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="nid"/>
          <div class="form-group">
            <label>Título *</label>
            <input type="text" id="titulo" placeholder="Ej: Cambio de estado"/>
          </div>
          <div class="form-group">
            <label>Mensaje *</label>
            <textarea id="mensaje" rows="3" placeholder="Escriba el mensaje que recibirá el usuario"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Tipo *</label>
              <select id="tipo">
                <option value="creacion_reporte">Creación de reporte</option>
                <option value="cambio_estado">Cambio de estado</option>
                <option value="comentario">Comentario</option>
                <option value="ticket_asignado">Ticket asignado</option>
                <option value="alerta_local">Alerta local</option>
              </select>
            </div>
            <div class="form-group">
              <label>Estado de lectura</label>
              <select id="leida">
                <option value="0">No leída</option>
                <option value="1">Leída</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Usuario destinatario *</label>
              <select id="idUsuario"></select>
            </div>
            <div class="form-group">
              <label>Reporte relacionado</label>
              <select id="idReporte"></select>
            </div>
          </div>
        </div>
        <div class="modal-foot">
          <button class="btn btn-ghost" onclick="cerrarModal()">Cancelar</button>
          <button class="btn btn-primary" onclick="guardar()">Guardar</button>
        </div>
      </div>
    </div>

  </div><!-- /admin-panel -->

</div><!-- /page -->

<div id="toast"></div>

<script>
const API   = '../api/notificaciones.php';
const API_U = '../api/usuarios.php';
const API_R = '../api/reportes.php';
const ROL   = <?= json_encode($rol) ?>;
const UID   = <?= json_encode($usuario['id']) ?>;

/* ── Helpers ── */
function escHtml(v) {
  if (v === null || v === undefined || v === '') return '—';
  return String(v).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');
}
function fmtFecha(f) {
  if (!f) return '';
  const d = new Date(f);
  const ahora = new Date();
  const diff  = ahora - d;
  const min   = Math.floor(diff / 60000);
  const hrs   = Math.floor(diff / 3600000);
  const dias  = Math.floor(diff / 86400000);
  if (min < 1)   return 'Ahora mismo';
  if (min < 60)  return `Hace ${min} min`;
  if (hrs < 24)  return `Hace ${hrs} h`;
  if (dias < 7)  return `Hace ${dias} día${dias>1?'s':''}`;
  return d.toLocaleDateString('es-CO', { day:'2-digit', month:'short', year:'numeric' });
}
function fmtFechaCorta(f) {
  if (!f) return '—';
  return String(f).substring(0,19);
}
function textoTipo(t) {
  return {
    creacion_reporte:'Nuevo reporte', cambio_estado:'Cambio de estado',
    comentario:'Comentario', ticket_asignado:'Ticket asignado',
    alerta_local:'Alerta local', voto:'Votación'
  }[t] ?? t;
}
function iconoTipo(t) {
  const icons = {
    cambio_estado:    `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><polyline points="12 6 12 12 16 14"/></svg>`,
    comentario:       `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`,
    alerta_local:     `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>`,
    creacion_reporte: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>`,
    ticket_asignado:  `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2z"/><path d="M13 5v2M13 17v2M13 11v2"/></svg>`,
    voto:             `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14z"/><path d="M7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>`,
  };
  return icons[t] ?? icons['creacion_reporte'];
}

/* ══════════════════════════════════════════════════
   LÓGICA CIUDADANO
══════════════════════════════════════════════════ */
let tabActual   = 'todas';
let todasNotifs = [];

async function cargarCiudadano() {
  if (ROL !== 'ciudadano') return;

  document.getElementById('skeletonLoader').style.display = 'flex';
  document.getElementById('notifList').style.display      = 'none';
  document.getElementById('notifEmpty').style.display     = 'none';

  try {
    const res  = await fetch(`${API}?idUsuario=${UID}`);
    todasNotifs = res.ok ? await res.json() : [];
    if (!Array.isArray(todasNotifs)) todasNotifs = [];
  } catch (e) { todasNotifs = []; }

  actualizarContadores();
  renderizarCiudadano();
}

function actualizarContadores() {
  const noLeidas = todasNotifs.filter(n => n.leida == 0).length;

  /* Badge header */
  const hb = document.getElementById('countBadge');
  hb.textContent = noLeidas > 0 ? `${noLeidas} nueva${noLeidas>1?'s':''}` : 'Al día ✓';
  hb.className   = 'notif-header-count' + (noLeidas === 0 ? ' zero' : '');

  /* Badge tab "No leídas" */
  const tb = document.getElementById('tabBadgeNuevas');
  if (noLeidas > 0) { tb.textContent = noLeidas; tb.classList.remove('hidden'); }
  else              { tb.classList.add('hidden'); }

  /* Botón marcar todas */
  document.getElementById('btnMarcarTodas').style.display = noLeidas > 0 ? '' : 'none';

  /* Badge campana en navbar */
  actualizarBadgeNavbar(noLeidas);
}

function actualizarBadgeNavbar(count) {
  let badge = document.getElementById('navNotifBadge');
  if (!badge) return;
  if (count > 0) {
    badge.textContent = count > 9 ? '9+' : count;
    badge.style.display = 'flex';
  } else {
    badge.style.display = 'none';
  }
}

function filtrarNotifs() {
  if (tabActual === 'todas')   return todasNotifs;
  if (tabActual === 'nuevas')  return todasNotifs.filter(n => n.leida == 0);
  return todasNotifs.filter(n => n.tipo === tabActual);
}

function renderizarCiudadano() {
  document.getElementById('skeletonLoader').style.display = 'none';
  const lista  = document.getElementById('notifList');
  const empty  = document.getElementById('notifEmpty');
  const filtradas = filtrarNotifs();

  if (!filtradas.length) {
    lista.style.display  = 'none';
    empty.style.display  = 'block';
    const msgs = {
      todas:         { t:'No tienes notificaciones aún',         m:'Cuando ocurra algo relacionado con tus reportes o alertas, te avisaremos aquí.' },
      nuevas:        { t:'¡Todo al día!',                        m:'No tienes notificaciones sin leer.' },
      cambio_estado: { t:'Sin actualizaciones de estado',        m:'Cuando uno de tus reportes cambie de estado, aparecerá aquí.' },
      comentario:    { t:'Sin comentarios nuevos',               m:'Cuando alguien comente en tus reportes, lo verás aquí.' },
      alerta_local:  { t:'Sin alertas locales',                  m:'Si ocurre un reporte dentro del rango de alguna de tus alertas, te notificaremos.' },
    };
    const cfg = msgs[tabActual] || msgs.todas;
    document.getElementById('emptyTitle').textContent = cfg.t;
    document.getElementById('emptyMsg').textContent   = cfg.m;
    return;
  }

  empty.style.display = 'none';
  lista.style.display = 'flex';
  lista.innerHTML = filtradas.map(n => {
    const leida    = n.leida == 1;
    const cardCls  = leida ? 'notif-card read' : 'notif-card unread';
    const btnLabel = leida ? '↩ Marcar no leída' : '✓ Leída';
    const btnTitle = leida ? 'Marcar como no leída' : 'Marcar como leída';
    const nuevoLeida = leida ? 0 : 1;
    const reporteHtml = n.idReporte
      ? `<a class="notif-reporte-link" href="../pages/reportes.php" title="Ver reporte">
           <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
             <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
             <line x1="4" y1="22" x2="4" y2="15"/>
           </svg>
           Reporte #${escHtml(n.idReporte)}
         </a>`
      : '';

    return `
    <div class="${cardCls}" id="nc-${n.idNotificacion}" onclick="abrirNotif(${n.idNotificacion})">
      <div class="notif-icon tipo-${escHtml(n.tipo)}">${iconoTipo(n.tipo)}</div>
      <div class="notif-content">
        <div class="notif-meta">
          <span class="notif-titulo">${escHtml(n.titulo)}</span>
          <span class="notif-tipo-tag ${escHtml(n.tipo)}">${escHtml(textoTipo(n.tipo))}</span>
        </div>
        <div class="notif-mensaje">${escHtml(n.mensaje)}</div>
        <div class="notif-footer">
          <span class="notif-fecha">${fmtFecha(n.fechaCreacion)}</span>
          ${reporteHtml}
        </div>
      </div>
      <div class="notif-dot"></div>
      <button class="btn-mark-read"
              title="${btnTitle}"
              onclick="event.stopPropagation(); cambiarLecturaCiudadano(${n.idNotificacion}, ${nuevoLeida})">
        ${btnLabel}
      </button>
    </div>`;
  }).join('');
}

function setTab(tab, el) {
  tabActual = tab;
  document.querySelectorAll('.notif-tab').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  renderizarCiudadano();
}

async function abrirNotif(id) {
  /* Si no está leída, marcarla al abrir */
  const n = todasNotifs.find(x => x.idNotificacion == id);
  if (n && n.leida == 0) await cambiarLecturaCiudadano(id, 1, true);
}

async function cambiarLecturaCiudadano(id, leida, silencioso = false) {
  const res = await fetch(`${API}?id=${id}`, {
    method:'PUT',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ soloLectura:true, leida })
  });
  if (!res.ok) { if (!silencioso) toast('Error al actualizar', false); return; }

  /* Actualizar estado local sin re-fetch */
  const idx = todasNotifs.findIndex(x => x.idNotificacion == id);
  if (idx !== -1) todasNotifs[idx].leida = leida;

  actualizarContadores();
  renderizarCiudadano();
  if (!silencioso) toast(leida ? 'Marcada como leída' : 'Marcada como no leída', true);
}

async function marcarTodasLeidas() {
  const noLeidas = todasNotifs.filter(n => n.leida == 0);
  await Promise.all(noLeidas.map(n =>
    fetch(`${API}?id=${n.idNotificacion}`, {
      method:'PUT',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ soloLectura:true, leida:1 })
    })
  ));
  todasNotifs.forEach(n => n.leida = 1);
  actualizarContadores();
  renderizarCiudadano();
  toast('Todas marcadas como leídas', true);
}

/* ══════════════════════════════════════════════════
   LÓGICA ADMIN
══════════════════════════════════════════════════ */
let filtroLeida = '';
let usuarios = [], reportes = [];

async function cargarCatalogos() {
  const [ru, rr] = await Promise.all([fetch(API_U), fetch(API_R)]);
  usuarios = await ru.json();
  reportes = await rr.json();
  const su = document.getElementById('idUsuario');
  const sr = document.getElementById('idReporte');
  if (!su || !sr) return;
  su.innerHTML = '<option value="">Seleccione usuario</option>' +
    usuarios.map(u => `<option value="${u.idUsuario}">${escHtml(u.nombres+' '+u.apellido_1)} - ${escHtml(u.rol)}</option>`).join('');
  sr.innerHTML = '<option value="">Sin reporte relacionado</option>' +
    reportes.map(r => `<option value="${r.idReporte}">#${r.idReporte} - ${escHtml(r.titulo)}</option>`).join('');
}

async function cargar() {
  let url = API + (filtroLeida !== '' ? `?leida=${filtroLeida}` : '');
  const res = await fetch(url);
  const data = await res.json();
  const tbody = document.getElementById('tbody');
  if (!tbody) return;
  if (!res.ok || data.error) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="9">Error al cargar notificaciones.</td></tr>`;
    toast(data.error ?? 'Error', false); return;
  }
  if (!data.length) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="9">No hay notificaciones.</td></tr>`; return;
  }
  tbody.innerHTML = data.map(n => `
    <tr>
      <td>#${n.idNotificacion}</td>
      <td><strong>${escHtml(n.titulo)}</strong></td>
      <td>${escHtml(n.mensaje)}</td>
      <td><span class="badge badge-${escHtml(n.tipo)}">${escHtml(textoTipo(n.tipo))}</span></td>
      <td><span class="badge badge-${n.leida==1?'activo':'pendiente'}">${n.leida==1?'Leída':'No leída'}</span></td>
      <td>${escHtml(n.usuario)}</td>
      <td>${escHtml(n.reporte)}</td>
      <td>${escHtml(fmtFechaCorta(n.fechaCreacion))}</td>
      <td class="td-acc">
        <button class="btn-edit" onclick="editarAdmin(${n.idNotificacion})">✏ Editar</button>
        <button class="btn-edit" onclick="cambiarLecturaAdmin(${n.idNotificacion},${n.leida==1?0:1})">${n.leida==1?'↩ No leída':'✓ Leída'}</button>
        <button class="btn-del"  onclick="eliminar(${n.idNotificacion},'${escHtml(n.titulo)}')">🗑 Eliminar</button>
      </td>
    </tr>`).join('');
}

function filtrarLectura(v) { filtroLeida = v; cargar(); }

function abrirModal() {
  ['nid','titulo','mensaje'].forEach(id => { const el=document.getElementById(id); if(el) el.value=''; });
  const t=document.getElementById('tipo'); if(t) t.value='creacion_reporte';
  const l=document.getElementById('leida'); if(l) l.value='0';
  const u=document.getElementById('idUsuario'); if(u) u.value='';
  const r=document.getElementById('idReporte'); if(r) r.value='';
  const mt=document.getElementById('modalTit'); if(mt) mt.textContent='Nueva Notificación';
  document.getElementById('modal')?.classList.add('open');
}

async function editarAdmin(id) {
  const res = await fetch(`${API}?id=${id}`);
  const n = await res.json();
  if (!res.ok || n.error) { toast(n.error ?? 'No se pudo cargar', false); return; }
  document.getElementById('modalTit').textContent = 'Editar Notificación';
  document.getElementById('nid').value     = n.idNotificacion;
  document.getElementById('titulo').value  = n.titulo ?? '';
  document.getElementById('mensaje').value = n.mensaje ?? '';
  document.getElementById('tipo').value    = n.tipo ?? 'creacion_reporte';
  document.getElementById('leida').value   = n.leida==1?'1':'0';
  document.getElementById('idUsuario').value = n.idUsuario ?? '';
  document.getElementById('idReporte').value = n.idReporte ?? '';
  document.getElementById('modal')?.classList.add('open');
}

function cerrarModal() { document.getElementById('modal')?.classList.remove('open'); }

async function guardar() {
  const id      = document.getElementById('nid').value;
  const idRep   = document.getElementById('idReporte').value;
  const body    = {
    titulo:    document.getElementById('titulo').value.trim(),
    mensaje:   document.getElementById('mensaje').value.trim(),
    tipo:      document.getElementById('tipo').value,
    leida:     Number(document.getElementById('leida').value),
    idUsuario: document.getElementById('idUsuario').value ? Number(document.getElementById('idUsuario').value) : null,
    idReporte: idRep ? Number(idRep) : null
  };
  if (!body.titulo)    { toast('El título es obligatorio', false); return; }
  if (!body.mensaje)   { toast('El mensaje es obligatorio', false); return; }
  if (!body.idUsuario) { toast('Seleccione el usuario destinatario', false); return; }

  const res = await fetch(id ? `${API}?id=${id}` : API, {
    method: id ? 'PUT' : 'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(body)
  });
  const data = await res.json();
  if (!res.ok || data.error) { toast(data.error ?? 'Error al guardar', false); return; }
  toast(data.mensaje ?? 'Guardado', true);
  cerrarModal(); cargar();
}

async function cambiarLecturaAdmin(id, leida) {
  const res = await fetch(`${API}?id=${id}`, {
    method:'PUT', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ soloLectura:true, leida })
  });
  const data = await res.json();
  if (!res.ok || data.error) { toast(data.error ?? 'Error', false); return; }
  toast(data.mensaje ?? 'Actualizado', true); cargar();
}

async function eliminar(id, titulo) {
  if (!confirm(`¿Eliminar la notificación "${titulo}"?`)) return;
  const res = await fetch(`${API}?id=${id}`, { method:'DELETE' });
  const data = await res.json();
  if (!res.ok || data.error) { toast(data.error ?? 'Error', false); return; }
  toast(data.mensaje ?? 'Eliminada', true); cargar();
}

/* ── Toast global ── */
function toast(msg, ok) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = ok ? 'show ok' : 'show err';
  setTimeout(() => t.className = '', 3200);
}

/* ── Init ── */
if (ROL === 'ciudadano') {
  cargarCiudadano();
} else {
  cargarCatalogos().then(cargar);
}
</script>

</body>
</html>