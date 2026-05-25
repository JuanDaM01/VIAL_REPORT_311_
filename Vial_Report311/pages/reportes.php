<?php require_once __DIR__ . '/../config/session.php'; requireRole(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Reportes — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
  <style>
    /* ── Combobox Ubicación ── */
    .combobox-wrap {
      position: relative;
    }
    .combobox-wrap input[type="text"] {
      width: 100%;
      padding: .55rem .75rem;
      padding-right: 2.2rem;
      font-size: .9rem;
      border: 1px solid var(--border);
      border-radius: 8px;
      background: var(--card);
      color: var(--text);
      outline: none;
      transition: border-color .2s, box-shadow .2s;
    }
    .combobox-wrap input[type="text"]:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(245,166,35,.12);
    }
    .combobox-wrap input[type="text"]::placeholder {
      color: var(--muted);
    }
    .combobox-toggle {
      position: absolute;
      right: .5rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--muted);
      cursor: pointer;
      padding: 2px;
      display: flex;
      align-items: center;
      transition: transform .2s, color .15s;
    }
    .combobox-toggle.open {
      transform: translateY(-50%) rotate(180deg);
    }
    .combobox-toggle:hover { color: var(--text2); }
    .combobox-dropdown {
      position: absolute;
      top: calc(100% + 4px);
      left: 0;
      right: 0;
      max-height: 200px;
      overflow-y: auto;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 8px;
      box-shadow: 0 8px 24px rgba(0,0,0,.18);
      z-index: 100;
      display: none;
    }
    .combobox-dropdown.open {
      display: block;
      animation: cbSlide .15s ease;
    }
    @keyframes cbSlide {
      from { opacity: 0; transform: translateY(-6px); }
      to   { opacity: 1; transform: none; }
    }
    .combobox-option {
      padding: .5rem .75rem;
      font-size: .85rem;
      cursor: pointer;
      transition: background .12s;
      color: var(--text);
      border-bottom: 1px solid var(--border);
    }
    .combobox-option:last-child { border-bottom: none; }
    .combobox-option:hover,
    .combobox-option.highlighted {
      background: rgba(245,166,35,.1);
    }
    .combobox-option.selected {
      background: rgba(245,166,35,.15);
      font-weight: 600;
    }
    .combobox-empty {
      padding: .6rem .75rem;
      font-size: .82rem;
      color: var(--muted);
      font-style: italic;
    }
    .combobox-new-hint {
      padding: .5rem .75rem;
      font-size: .8rem;
      color: var(--accent);
      font-weight: 600;
      cursor: pointer;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: .4rem;
      transition: background .12s;
    }
    .combobox-new-hint:hover {
      background: rgba(245,166,35,.08);
    }
    .combobox-badge {
      display: inline-flex;
      align-items: center;
      gap: .3rem;
      margin-top: .3rem;
      font-size: .72rem;
      padding: 2px 8px;
      border-radius: 12px;
      font-weight: 600;
    }
    .combobox-badge.existing {
      background: rgba(39,174,96,.12);
      color: var(--ok);
    }
    .combobox-badge.new-loc {
      background: rgba(245,166,35,.12);
      color: var(--accent);
    }

    /* ── Portal Ciudadano ── */
    body.role-ciudadano #grp-estado,
    body.role-ciudadano #grp-prioridad,
    body.role-ciudadano #grp-usuario,
    body.role-ciudadano #grp-proveedor,
    body.role-ciudadano #grp-funcionario,
    body.role-ciudadano .col-admin {
      display: none !important;
    }
    
    /* Hero Banner Ciudadano */
    .ciudadano-hero {
      display: none;
      background: linear-gradient(135deg, var(--surface2) 0%, var(--surface) 100%);
      border: 1px solid var(--border2);
      border-radius: var(--radius-lg);
      padding: 2.2rem 2.5rem;
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
      justify-content: space-between;
      align-items: center;
      box-shadow: var(--shadow);
    }
    body.role-ciudadano .ciudadano-hero {
      display: flex;
    }
    body.role-ciudadano .page-header {
      display: none !important;
    }
    .ciudadano-hero::before {
      content: '';
      position: absolute;
      width: 300px;
      height: 300px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(245,166,35,.08) 0%, transparent 70%);
      top: -100px;
      right: -50px;
      pointer-events: none;
    }
    .ciudadano-hero h1 {
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: .6rem;
      letter-spacing: -.5px;
      color: var(--text);
    }
    .ciudadano-hero h1 span {
      color: var(--accent);
    }
    .ciudadano-hero p {
      color: var(--text2);
      font-size: 1rem;
      max-width: 600px;
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }
    .ciudadano-hero .btn-lg {
      padding: .7rem 1.6rem;
      font-size: .95rem;
      font-weight: 700;
      box-shadow: 0 4px 15px rgba(245,166,35,.3);
      transition: transform .2s, box-shadow .2s;
    }
    .ciudadano-hero .btn-lg:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(245,166,35,.45);
    }
    .hero-decor {
      font-size: 4.5rem;
      opacity: .85;
      animation: float 4s ease-in-out infinite alternate;
      user-select: none;
    }
    @keyframes float {
      from { transform: translateY(0px) rotate(0deg); }
      to   { transform: translateY(-10px) rotate(5deg); }
    }
    
    /* Pestañas Ciudadano */
    .citizen-tabs {
      display: none;
      gap: .5rem;
      margin-bottom: 1.2rem;
      border-bottom: 1px solid var(--border);
      padding-bottom: .5rem;
    }
    body.role-ciudadano .citizen-tabs {
      display: flex;
    }
    .tab-btn {
      background: transparent;
      color: var(--muted);
      border: none;
      font-size: .9rem;
      font-weight: 600;
      padding: .6rem 1.2rem;
      cursor: pointer;
      position: relative;
      transition: color .2s;
    }
    .tab-btn:hover {
      color: var(--text2);
    }
    .tab-btn.active {
      color: var(--accent);
    }
    .tab-btn.active::after {
      content: '';
      position: absolute;
      bottom: -.5rem;
      left: 0;
      right: 0;
      height: 3px;
      background: var(--accent);
      border-radius: 3px 3px 0 0;
    }
    
    
    /* Botón Votar */
    .btn-vote {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      border: 1px solid var(--border);
      background: var(--surface2);
      color: var(--text2);
      border-radius: 20px;
      padding: 4px 12px;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      user-select: none;
    }
    .btn-vote:hover {
      border-color: var(--accent);
      color: var(--accent);
      background: rgba(245,166,35,0.06);
    }
    .btn-vote.voted {
      border-color: var(--ok);
      color: var(--ok);
      background: rgba(39,174,96,0.1);
    }
    .btn-vote.voted:hover {
      background: rgba(39,174,96,0.18);
    }

    /* Botón/Enlace Comentario en fila */
    .btn-comment {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      color: var(--accent);
      background: none;
      border: none;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      padding: 2px 0;
      transition: opacity 0.15s;
      margin-top: 4px;
      outline: none;
    }
    .btn-comment:hover {
      text-decoration: underline;
      opacity: 0.85;
    }

    /* Modal Comentarios */
    .comments-thread {
      max-height: 280px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      padding: 0.5rem;
      border: 1px solid var(--border);
      border-radius: 8px;
      background: rgba(0,0,0,0.15);
      margin-bottom: 1rem;
    }
    .comment-card {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 0.75rem;
      position: relative;
    }
    .comment-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.35rem;
      font-size: 0.75rem;
    }
    .comment-author {
      font-weight: 700;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .comment-date {
      color: var(--muted);
    }
    .comment-text {
      font-size: 0.84rem;
      color: var(--text2);
      line-height: 1.4;
      white-space: pre-wrap;
    }
    .comment-form {
      display: flex;
      gap: 0.6rem;
      align-items: stretch;
    }
    .comment-form textarea {
      flex: 1;
      height: 42px !important;
      min-height: 42px !important;
      resize: none;
      padding: 8px 12px;
      font-size: 0.85rem;
    }
    .comment-form button {
      padding: 0 1.2rem;
    }
  </style>
</head>
<body class="role-<?= rolActual() ?>">

<?php include 'navbar.php'; ?>

<div class="page">
  <!-- Hero Banner para Ciudadano -->
  <div id="ciudadanoHero" class="ciudadano-hero">
    <div class="hero-content">
      <h1>¡Hola, <span id="citizenName"></span>!</h1>
      <p>Ayuda a mejorar nuestra ciudad reportando daños en la infraestructura vial. Las entidades correspondientes los solucionarán a la brevedad.</p>
      <button class="btn btn-primary btn-lg" onclick="abrirModal()">＋ Reportar Problema Vial</button>
    </div>
    <div class="hero-decor">🚧</div>
  </div>

  <div class="page-header">
    <h2>Gestión de <span>Reportes</span></h2>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nuevo reporte</button>
  </div>

  <!-- Pestañas de Filtrado para Ciudadano -->
  <div class="citizen-tabs" id="citizenTabs">
    <button class="tab-btn active" onclick="filtrarVista('mis')">Mis Reportes</button>
    <button class="tab-btn" onclick="filtrarVista('todos')">Todos los Reportes</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Ticket</th>
          <th>Reporte</th>
          <th>Categoría</th>
          <th>Estado</th>
          <th>Votos</th>
          <th class="col-admin">Prioridad</th>
          <th class="col-admin">Proveedor</th>
          <th class="col-admin">Ciudadano</th>
          <th>Ubicación</th>
          <th>Fecha</th>
          <th>Acciones</th>
        </tr>
      </thead>

      <tbody id="tbody">
        <tr class="empty-row">
          <td colspan="11">Cargando...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal CREATE / UPDATE -->
<div class="modal-overlay" id="modal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTit">Nuevo Reporte</h3>
      <button onclick="cerrarModal()">✕</button>
    </div>

    <div class="modal-body">
      <input type="hidden" id="rid"/>

      <div class="form-group">
        <label>Título *</label>
        <input type="text" id="titulo" placeholder="Describe brevemente el problema vial"/>
      </div>

      <div class="form-group">
        <label>Descripción</label>
        <textarea id="descripcion" rows="3" placeholder="Detalle del problema, punto de referencia o información adicional"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Categoría *</label>
          <select id="idCategoria"></select>
        </div>

        <div class="form-group">
          <label>Ubicación *</label>
          <div class="combobox-wrap" id="ubicacionCombobox">
            <input type="text" id="ubicacionInput" placeholder="Buscar o escribir nueva ubicación..." autocomplete="off"/>
            <input type="hidden" id="idUbicacion" value=""/>
            <button type="button" class="combobox-toggle" id="ubicacionToggle">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="combobox-dropdown" id="ubicacionDropdown"></div>
            <div id="ubicacionBadge"></div>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" id="grp-estado">
          <label>Estado del reporte</label>
          <select id="estado">
            <option value="recibido">Recibido</option>
            <option value="en_proceso">En proceso</option>
            <option value="resuelto">Resuelto</option>
            <option value="rechazado">Rechazado</option>
          </select>
        </div>

        <div class="form-group" id="grp-prioridad">
          <label>Prioridad del ticket</label>
          <select id="prioridad">
            <option value="">Automática</option>
            <option value="baja">Baja</option>
            <option value="media">Media</option>
            <option value="alta">Alta</option>
            <option value="critica">Crítica</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" id="grp-usuario">
          <label>Ciudadano</label>
          <select id="idUsuario"></select>
        </div>

        <div class="form-group" id="grp-proveedor">
          <label>Proveedor responsable</label>
          <select id="idProveedor">
            <option value="">Asignación automática</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" id="grp-funcionario">
          <label>Funcionario asignado</label>
          <select id="idFuncionario">
            <option value="">Asignación automática</option>
          </select>
        </div>

        <div class="form-group">
          <label>URL de evidencia</label>
          <input type="text" id="urlArchivo" placeholder="uploads/evidencias/foto.jpg"/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Tamaño evidencia (KB)</label>
          <input type="number" id="tamanoKb" min="1" placeholder="Ej: 480"/>
        </div>

        <div class="form-group">
          <label>Tipo de contenido</label>
          <select id="contenido">
            <option value="">No especificado</option>
            <option value="image/jpeg">image/jpeg</option>
            <option value="image/png">image/png</option>
            <option value="image/webp">image/webp</option>
            <option value="application/pdf">application/pdf</option>
          </select>
        </div>
      </div>

      <div class="check-row">
        <input type="checkbox" id="esAnonimo" onchange="controlarAnonimo()"/>
        <label for="esAnonimo">Reporte anónimo</label>
      </div>
    </div>

    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="cerrarModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="guardar()">Guardar</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<!-- Modal COMENTARIOS INTERACTIVOS -->
<div class="modal-overlay" id="commentsModal">
  <div class="modal" style="width: 520px;">
    <div class="modal-head">
      <h3 id="commentsModalTit">Comentarios del Reporte</h3>
      <button onclick="cerrarCommentsModal()">✕</button>
    </div>

    <div class="modal-body">
      <input type="hidden" id="commReportId"/>
      
      <!-- Hilo de comentarios -->
      <div class="comments-thread" id="commentsThread">
        <div class="text-muted" style="text-align: center; padding: 2rem;">Cargando comentarios...</div>
      </div>

      <!-- Formulario para agregar comentario -->
      <div class="comment-form">
        <textarea id="newCommentText" rows="2" placeholder="Escribe un comentario sobre este reporte..."></textarea>
        <button class="btn btn-primary" onclick="guardarComentario()">Enviar</button>
      </div>
    </div>
  </div>
</div>

<script>
const USER_ROLE = '<?= rolActual() ?>';
const USER_ID = <?= json_encode($_SESSION['usuario_id'] ?? null) ?>;
const USER_NAME = <?= json_encode($_SESSION['nombre'] ?? '') ?>;

const API_REPORTES = '../api/reportes.php';
const API_CATALOGOS = '../api/catalogos.php?recurso=todo';

let todosLosReportes = [];
let vistaActual = 'mis';
let misVotos = new Set();

async function cargarMisVotos() {
  if (!USER_ID) return;
  try {
    const res = await fetch(`../api/votos.php?idUsuario=${USER_ID}`);
    const data = await res.json();
    if (res.ok && Array.isArray(data)) {
      misVotos = new Set(data.map(v => Number(v.idReporte)));
    }
  } catch (err) {
    console.error("Error al cargar votos del usuario: ", err);
  }
}

let catalogos = {
  categorias: [],
  ubicaciones: [],
  ciudadanos: [],
  funcionarios: [],
  proveedores: []
};

function escapeHtml(valor) {
  if (valor === null || valor === undefined || valor === '') return '—';

  return String(valor)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function formatearFecha(fecha) {
  if (!fecha) return '—';
  return String(fecha).substring(0, 10);
}

function textoEstado(estado) {
  if (!estado) return '—';
  return String(estado).replace('_', ' ');
}

function textoUbicacion(u) {
  const partes = [];

  if (u.barrio) partes.push(u.barrio);
  if (u.direccionTexto) partes.push(u.direccionTexto);
  if (u.ciudad) partes.push(u.ciudad);

  const unicas = [];
  partes.forEach(p => {
    const trimP = p.trim();
    if (trimP && !unicas.includes(trimP)) {
      unicas.push(trimP);
    }
  });

  return unicas.join(' - ');
}

function textoPersona(u) {
  const apellido2 = u.apellido_2 ? ' ' + u.apellido_2 : '';
  return `${u.nombres} ${u.apellido_1}${apellido2}`;
}

async function cargarCatalogos() {
  const res = await fetch(API_CATALOGOS);
  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudieron cargar los catálogos', false);
    return;
  }

  catalogos = data;

  llenarSelectCategorias();
  llenarSelectUbicaciones();
  llenarSelectCiudadanos();
  llenarSelectProveedores();
  llenarSelectFuncionarios();
}

function llenarSelectCategorias() {
  const select = document.getElementById('idCategoria');

  select.innerHTML = '<option value="">Seleccione una categoría</option>' +
    catalogos.categorias.map(c => `
      <option value="${c.idCategoria}">
        ${escapeHtml(c.nombre)}
      </option>
    `).join('');
}

// ── Combobox Ubicación ──────────────────────────────────────
let ubicacionSeleccionada = null; // { idUbicacion, texto } o null (nueva)
let ubicacionTextoNuevo = '';     // texto libre si es nueva
let cbHighlightIdx = -1;

function initComboboxUbicacion() {
  const input    = document.getElementById('ubicacionInput');
  const hidden   = document.getElementById('idUbicacion');
  const dropdown = document.getElementById('ubicacionDropdown');
  const toggle   = document.getElementById('ubicacionToggle');
  const badge    = document.getElementById('ubicacionBadge');

  function getOpciones(filtro) {
    const f = (filtro || '').toLowerCase();
    return catalogos.ubicaciones
      .map(u => ({ id: u.idUbicacion, texto: textoUbicacion(u) }))
      .filter(o => !f || o.texto.toLowerCase().includes(f));
  }

  function renderDropdown(filtro) {
    const opciones = getOpciones(filtro);
    const f = (filtro || '').trim();
    let html = '';

    if (opciones.length === 0 && !f) {
      html = '<div class="combobox-empty">No hay ubicaciones registradas</div>';
    } else {
      opciones.forEach((o, i) => {
        const selClass = (ubicacionSeleccionada && ubicacionSeleccionada.idUbicacion === o.id) ? ' selected' : '';
        const hiClass = (i === cbHighlightIdx) ? ' highlighted' : '';
        html += `<div class="combobox-option${selClass}${hiClass}" data-id="${o.id}" data-idx="${i}">${escapeHtml(o.texto)}</div>`;
      });
    }

    // Si hay texto y no coincide exactamente con ninguna opción, mostrar hint de "crear nueva"
    if (f && !opciones.some(o => o.texto.toLowerCase() === f.toLowerCase())) {
      html += `<div class="combobox-new-hint" id="cbCrearNueva">＋ Crear nueva ubicación: "${escapeHtml(f)}"</div>`;
    }

    dropdown.innerHTML = html;

    // Event listeners para opciones
    dropdown.querySelectorAll('.combobox-option').forEach(el => {
      el.addEventListener('click', () => {
        const id = Number(el.dataset.id);
        const opt = opciones.find(o => o.id === id);
        seleccionar(opt);
      });
    });

    const crearBtn = dropdown.querySelector('#cbCrearNueva');
    if (crearBtn) {
      crearBtn.addEventListener('click', () => {
        usarNueva(f);
      });
    }
  }

  function seleccionar(opt) {
    ubicacionSeleccionada = { idUbicacion: opt.id, texto: opt.texto };
    ubicacionTextoNuevo = '';
    input.value = opt.texto;
    hidden.value = opt.id;
    badge.innerHTML = '<span class="combobox-badge existing">✓ Ubicación existente</span>';
    cerrarDropdown();
  }

  function usarNueva(texto) {
    ubicacionSeleccionada = null;
    ubicacionTextoNuevo = texto;
    input.value = texto;
    hidden.value = '';
    badge.innerHTML = '<span class="combobox-badge new-loc">＋ Se creará nueva ubicación</span>';
    cerrarDropdown();
  }

  function abrirDropdown() {
    cbHighlightIdx = -1;
    renderDropdown(input.value);
    dropdown.classList.add('open');
    toggle.classList.add('open');
  }

  function cerrarDropdown() {
    dropdown.classList.remove('open');
    toggle.classList.remove('open');
    cbHighlightIdx = -1;
  }

  function esAbierto() {
    return dropdown.classList.contains('open');
  }

  // Eventos
  input.addEventListener('focus', abrirDropdown);
  input.addEventListener('input', () => {
    cbHighlightIdx = -1;

    // Si había una ubicación seleccionada y el usuario cambió el texto,
    // se limpia la selección para forzar una nueva búsqueda o creación.
    if (ubicacionSeleccionada && input.value !== ubicacionSeleccionada.texto) {
      ubicacionSeleccionada = null;
      hidden.value = '';
      badge.innerHTML = '';
    }

    renderDropdown(input.value);
    if (!esAbierto()) abrirDropdown();

    // Si el usuario borra todo, limpiar selección
    if (!input.value.trim()) {
      ubicacionSeleccionada = null;
      ubicacionTextoNuevo = '';
      hidden.value = '';
      badge.innerHTML = '';
    }
  });

  toggle.addEventListener('click', (e) => {
    e.stopPropagation();
    if (esAbierto()) cerrarDropdown();
    else abrirDropdown();
  });

  // Cerrar al hacer click fuera
  document.addEventListener('click', (e) => {
    if (!e.target.closest('#ubicacionCombobox')) {
      cerrarDropdown();
      // Si quedó texto sin seleccionar, tratarlo como nueva
      const val = input.value.trim();
      if (val && !ubicacionSeleccionada) {
        usarNueva(val);
      }
    }
  });

  // Navegación con teclado
  input.addEventListener('keydown', (e) => {
    const opciones = dropdown.querySelectorAll('.combobox-option');
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (!esAbierto()) abrirDropdown();
      cbHighlightIdx = Math.min(cbHighlightIdx + 1, opciones.length - 1);
      renderDropdown(input.value);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      cbHighlightIdx = Math.max(cbHighlightIdx - 1, 0);
      renderDropdown(input.value);
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (cbHighlightIdx >= 0 && cbHighlightIdx < opciones.length) {
        opciones[cbHighlightIdx].click();
      } else {
        const val = input.value.trim();
        if (val && !ubicacionSeleccionada) {
          usarNueva(val);
        }
        cerrarDropdown();
      }
    } else if (e.key === 'Escape') {
      cerrarDropdown();
    } else if (e.key === 'Tab') {
      const val = input.value.trim();
      if (val && !ubicacionSeleccionada) {
        usarNueva(val);
      }
      cerrarDropdown();
    }
  });
}

function llenarSelectUbicaciones() {
  // Inicializar combobox (se ejecuta después de cargar catálogos)
  initComboboxUbicacion();
}

function llenarSelectCiudadanos() {
  const select = document.getElementById('idUsuario');

  select.innerHTML = '<option value="">Seleccione un ciudadano</option>' +
    catalogos.ciudadanos.map(u => `
      <option value="${u.idUsuario}">
        ${escapeHtml(textoPersona(u))} - ${escapeHtml(u.email)}
      </option>
    `).join('');
}

function llenarSelectProveedores() {
  const select = document.getElementById('idProveedor');

  select.innerHTML = '<option value="">Asignación automática</option>' +
    catalogos.proveedores.map(p => `
      <option value="${p.idProveedor}">
        ${escapeHtml(p.nombreEntidad)}
      </option>
    `).join('');
}

function llenarSelectFuncionarios() {
  const select = document.getElementById('idFuncionario');

  select.innerHTML = '<option value="">Asignación automática</option>' +
    catalogos.funcionarios.map(f => `
      <option value="${f.idUsuario}">
        ${escapeHtml(textoPersona(f))} ${f.cargo ? '- ' + escapeHtml(f.cargo) : ''}
      </option>
    `).join('');
}

async function cargar() {
  const res = await fetch(API_REPORTES);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!res.ok || data.error) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="11">Error al cargar reportes.</td></tr>';
    toast(data.error ?? 'Error al cargar reportes', false);
    return;
  }

  todosLosReportes = data;
  renderizarTabla();
}

function filtrarVista(tipo) {
  vistaActual = tipo;

  const btns = document.querySelectorAll('#citizenTabs .tab-btn');
  btns.forEach(btn => {
    if (btn.getAttribute('onclick').includes(tipo)) {
      btn.classList.add('active');
    } else {
      btn.classList.remove('active');
    }
  });

  renderizarTabla();
}

function renderizarTabla() {
  const tbody = document.getElementById('tbody');

  let reportesFiltrados = todosLosReportes;

  // Si es ciudadano, filtramos según la pestaña seleccionada
  if (USER_ROLE === 'ciudadano') {
    if (vistaActual === 'mis') {
      reportesFiltrados = todosLosReportes.filter(r => r.idUsuario == USER_ID);
    }
  }

  if (!reportesFiltrados.length) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="11">No se encontraron reportes en esta sección.</td></tr>`;
    return;
  }

  tbody.innerHTML = reportesFiltrados.map(r => {
    const tipoUsuario = r.esAnonimo == 1
      ? '<span class="badge badge-anonimo">Anónimo</span>'
      : escapeHtml(r.nombreUsuario ?? '—');

    // Determinar si el usuario actual puede editar/eliminar este reporte
    let accionesHtml = '';
    const puedeEditar = (USER_ROLE === 'administrador') ||
                        (USER_ROLE === 'funcionario') ||
                        (USER_ROLE === 'ciudadano' && r.idUsuario == USER_ID);

    if (puedeEditar) {
      accionesHtml = `
        <button class="btn-edit" onclick="editar(${r.idReporte})">✏ Editar</button>
        <button class="btn-del" onclick="eliminar(${r.idReporte}, '${escapeHtml(r.titulo)}')">🗑 Eliminar</button>
      `;
    } else {
      accionesHtml = `<span class="badge badge-recibido" style="opacity: 0.65; cursor: not-allowed; padding: 4px 10px; font-size: 0.72rem;">Sólo lectura</span>`;
    }

    // Columna Votos interactiva o estándar
    let votosColHtml = '';
    if (USER_ROLE === 'ciudadano') {
      const yaVoto = misVotos.has(Number(r.idReporte));
      const activeClass = yaVoto ? ' voted' : '';
      const label = yaVoto ? '✓ Votado' : '👍 Votar';
      votosColHtml = `
        <button class="btn-vote${activeClass}" onclick="toggleVoto(${r.idReporte})">
          ${label} <span class="badge badge-en_proceso" style="background: rgba(255,255,255,0.25); color: inherit; padding: 1px 6px;">${r.totalVotos ?? 0}</span>
        </button>
      `;
    } else {
      votosColHtml = `
        <span class="badge badge-en_proceso">
          ${escapeHtml(r.totalVotos ?? 0)}
        </span>
      `;
    }

    return `
      <tr>
        <td>
          <strong>${escapeHtml(r.numeroCaso ?? 'Sin ticket')}</strong>
          <br>
          <small>#${escapeHtml(r.idReporte)}</small>
        </td>

        <td>
          <strong>${escapeHtml(r.titulo)}</strong>
          <br>
          <small>${escapeHtml(r.descripcion ?? '')}</small>
          <br>
          <button class="btn-comment" onclick="abrirCommentsModal(${r.idReporte})">💬 Comentarios</button>
        </td>

        <td>${escapeHtml(r.categoria)}</td>

        <td>
          <span class="badge badge-${escapeHtml(r.estado)}">
            ${escapeHtml(textoEstado(r.estado))}
          </span>
        </td>

        <td>
          ${votosColHtml}
        </td>

        <td class="col-admin">
          <span class="badge badge-${escapeHtml(r.prioridad ?? 'media')}">
            ${escapeHtml(r.prioridad ?? 'media')}
          </span>
        </td>

        <td class="col-admin">${escapeHtml(r.proveedor ?? 'Sin asignar')}</td>
        <td class="col-admin">${tipoUsuario}</td>
        <td>${escapeHtml(r.ubicacion ?? '—')}</td>
        <td>${escapeHtml(formatearFecha(r.fechaCreacion))}</td>

        <td class="td-acc">
          ${accionesHtml}
        </td>
      </tr>
    `;
  }).join('');
}

function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nuevo Reporte';

  document.getElementById('rid').value = '';
  document.getElementById('titulo').value = '';
  document.getElementById('descripcion').value = '';
  document.getElementById('idCategoria').value = '';
  document.getElementById('idUbicacion').value = '';
  document.getElementById('ubicacionInput').value = '';
  document.getElementById('ubicacionBadge').innerHTML = '';
  ubicacionSeleccionada = null;
  ubicacionTextoNuevo = '';

  // Cerrar combobox dropdown
  const cbDropdown = document.getElementById('ubicacionDropdown');
  const cbToggle = document.getElementById('ubicacionToggle');
  if (cbDropdown) cbDropdown.classList.remove('open');
  if (cbToggle) cbToggle.classList.remove('open');

  document.getElementById('estado').value = 'recibido';
  document.getElementById('prioridad').value = '';

  if (USER_ROLE === 'ciudadano') {
    document.getElementById('idUsuario').value = USER_ID;
  } else {
    document.getElementById('idUsuario').value = '';
  }

  document.getElementById('idProveedor').value = '';
  document.getElementById('idFuncionario').value = '';
  document.getElementById('urlArchivo').value = '';
  document.getElementById('tamanoKb').value = '';
  document.getElementById('contenido').value = '';
  document.getElementById('esAnonimo').checked = false;

  controlarAnonimo();

  document.getElementById('modal').classList.add('open');
}

async function editar(id) {
  const res = await fetch(`${API_REPORTES}?id=${id}`);
  const r = await res.json();

  if (!res.ok || r.error) {
    toast(r.error ?? 'No se pudo cargar el reporte', false);
    return;
  }

  document.getElementById('modalTit').textContent = 'Editar Reporte';

  document.getElementById('rid').value = r.idReporte;
  document.getElementById('titulo').value = r.titulo ?? '';
  document.getElementById('descripcion').value = r.descripcion ?? '';
  document.getElementById('idCategoria').value = r.idCategoria ?? '';

  // Cerrar combobox dropdown
  const cbDropdown = document.getElementById('ubicacionDropdown');
  const cbToggle = document.getElementById('ubicacionToggle');
  if (cbDropdown) cbDropdown.classList.remove('open');
  if (cbToggle) cbToggle.classList.remove('open');

  // Restaurar ubicación en el combobox
  document.getElementById('idUbicacion').value = r.idUbicacion ?? '';
  if (r.idUbicacion) {
    const ubMatch = catalogos.ubicaciones.find(u => u.idUbicacion == r.idUbicacion);
    if (ubMatch) {
      const textoUb = textoUbicacion(ubMatch);
      document.getElementById('ubicacionInput').value = textoUb;
      ubicacionSeleccionada = { idUbicacion: r.idUbicacion, texto: textoUb };
      ubicacionTextoNuevo = '';
      document.getElementById('ubicacionBadge').innerHTML = '<span class="combobox-badge existing">✓ Ubicación existente</span>';
    }
  } else {
    document.getElementById('ubicacionInput').value = '';
    ubicacionSeleccionada = null;
    ubicacionTextoNuevo = '';
    document.getElementById('ubicacionBadge').innerHTML = '';
  }

  document.getElementById('estado').value = r.estado ?? 'recibido';
  document.getElementById('prioridad').value = r.prioridad ?? '';

  if (USER_ROLE === 'ciudadano') {
    document.getElementById('idUsuario').value = USER_ID;
  } else {
    document.getElementById('idUsuario').value = r.idUsuario ?? '';
  }

  document.getElementById('idProveedor').value = r.idProveedor ?? '';
  document.getElementById('idFuncionario').value = r.idFuncionario ?? '';
  document.getElementById('urlArchivo').value = '';
  document.getElementById('tamanoKb').value = '';
  document.getElementById('contenido').value = '';

  document.getElementById('esAnonimo').checked = r.esAnonimo == 1;

  controlarAnonimo();

  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

function controlarAnonimo() {
  const esAnonimo = document.getElementById('esAnonimo').checked;
  const selectUsuario = document.getElementById('idUsuario');

  if (esAnonimo) {
    selectUsuario.value = '';
    selectUsuario.disabled = true;
  } else {
    selectUsuario.disabled = false;
    if (USER_ROLE === 'ciudadano') {
      selectUsuario.value = USER_ID;
    }
  }
}

function validarFormulario(body) {
  if (!body.titulo) {
    toast('El título es obligatorio', false);
    return false;
  }

  if (!body.idCategoria) {
    toast('Debe seleccionar una categoría', false);
    return false;
  }

  if (!body.idUbicacion && !ubicacionTextoNuevo) {
    toast('Debe seleccionar o escribir una ubicación', false);
    return false;
  }

  if (body.esAnonimo === 0 && !body.idUsuario) {
    toast('Debe seleccionar un ciudadano o marcar el reporte como anónimo', false);
    return false;
  }

  return true;
}

async function guardar() {
  const id = document.getElementById('rid').value;
  const esAnonimo = document.getElementById('esAnonimo').checked ? 1 : 0;

  // Validar que haya ubicación antes de proceder
  let idUbicacionFinal = document.getElementById('idUbicacion').value
    ? Number(document.getElementById('idUbicacion').value)
    : null;

  if (!idUbicacionFinal && !ubicacionTextoNuevo) {
    toast('Debe seleccionar o escribir una ubicación', false);
    return;
  }

  // Validaciones rápidas antes de crear la ubicación
  if (!document.getElementById('titulo').value.trim()) {
    toast('El título es obligatorio', false);
    return;
  }
  if (!document.getElementById('idCategoria').value) {
    toast('Debe seleccionar una categoría', false);
    return;
  }

  // Resolver ubicación: si es nueva, crearla primero

  if (!idUbicacionFinal && ubicacionTextoNuevo) {
    // Crear nueva ubicación via API
    try {
      const resUb = await fetch('../api/ubicaciones.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ciudad: ubicacionTextoNuevo,
          direccionTexto: ubicacionTextoNuevo
        })
      });
      const dataUb = await resUb.json();

      if (!resUb.ok || dataUb.error) {
        toast(dataUb.error ?? 'No se pudo crear la ubicación', false);
        return;
      }

      idUbicacionFinal = dataUb.idUbicacion;

      // Actualizar catálogos para que la nueva ubicación quede disponible
      catalogos.ubicaciones.push({
        idUbicacion: idUbicacionFinal,
        ciudad: ubicacionTextoNuevo,
        direccionTexto: ubicacionTextoNuevo,
        barrio: null,
        departamento: null
      });

      toast('Nueva ubicación creada automáticamente', true);
    } catch (err) {
      toast('Error al crear la ubicación: ' + err.message, false);
      return;
    }
  }

  const body = {
    titulo: document.getElementById('titulo').value.trim(),
    descripcion: document.getElementById('descripcion').value.trim(),
    estado: document.getElementById('estado').value,
    esAnonimo: esAnonimo,

    idCategoria: document.getElementById('idCategoria').value
      ? Number(document.getElementById('idCategoria').value)
      : null,

    idUbicacion: idUbicacionFinal,

    idUsuario: esAnonimo === 1
      ? null
      : (
          document.getElementById('idUsuario').value
            ? Number(document.getElementById('idUsuario').value)
            : null
        )
  };

  const prioridad = document.getElementById('prioridad').value;
  const idProveedor = document.getElementById('idProveedor').value;
  const idFuncionario = document.getElementById('idFuncionario').value;
  const urlArchivo = document.getElementById('urlArchivo').value.trim();
  const tamanoKb = document.getElementById('tamanoKb').value;
  const contenido = document.getElementById('contenido').value;

  if (prioridad) {
    body.prioridad = prioridad;
  }

  if (idProveedor) {
    body.idProveedor = Number(idProveedor);
  }

  if (idFuncionario) {
    body.idFuncionario = Number(idFuncionario);
  }

  if (urlArchivo) {
    body.urlArchivo = urlArchivo;
    body.tamanoKb = tamanoKb ? Number(tamanoKb) : null;
    body.contenido = contenido || null;
  }

  if (!validarFormulario(body)) {
    return;
  }

  const url = id ? `${API_REPORTES}?id=${id}` : API_REPORTES;
  const method = id ? 'PUT' : 'POST';

  const res = await fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(body)
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo guardar el reporte', false);
    return;
  }

  toast(data.mensaje ?? 'Reporte guardado correctamente', true);

  cerrarModal();
  cargar();
}

async function eliminar(id, titulo) {
  if (!confirm(`¿Eliminar el reporte "${titulo}"?`)) {
    return;
  }

  const res = await fetch(`${API_REPORTES}?id=${id}`, {
    method: 'DELETE'
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo eliminar el reporte', false);
    return;
  }

  toast(data.mensaje ?? 'Reporte eliminado correctamente', true);
  cargar();
}

async function toggleVoto(idReporte) {
  if (!USER_ID) {
    toast('Inicie sesión para votar', false);
    return;
  }

  const idNum = Number(idReporte);
  const yaVoto = misVotos.has(idNum);
  const method = yaVoto ? 'DELETE' : 'POST';
  const url = yaVoto
    ? `../api/votos.php?idUsuario=${USER_ID}&idReporte=${idNum}`
    : `../api/votos.php`;

  const options = { method };
  if (method === 'POST') {
    options.headers = { 'Content-Type': 'application/json' };
    options.body = JSON.stringify({ idUsuario: USER_ID, idReporte: idNum });
  }

  // ── Actualización optimista del botón ───────────────────────
  // Actualizamos el Set y el contador visualmente de inmediato
  // para que la UI no espere el reload completo de cargar().
  const reporteLocal = todosLosReportes.find(x => Number(x.idReporte) === idNum);
  if (reporteLocal) {
    if (yaVoto) {
      reporteLocal.totalVotos = Math.max(0, Number(reporteLocal.totalVotos ?? 0) - 1);
      misVotos.delete(idNum);
    } else {
      reporteLocal.totalVotos = Number(reporteLocal.totalVotos ?? 0) + 1;
      misVotos.add(idNum);
    }
    renderizarTabla();
  }

  try {
    const res = await fetch(url, options);
    const data = await res.json();
    if (!res.ok || data.error) {
      // Revertir cambio optimista si hubo error
      if (reporteLocal) {
        if (yaVoto) {
          reporteLocal.totalVotos = Number(reporteLocal.totalVotos ?? 0) + 1;
          misVotos.add(idNum);
        } else {
          reporteLocal.totalVotos = Math.max(0, Number(reporteLocal.totalVotos ?? 0) - 1);
          misVotos.delete(idNum);
        }
        renderizarTabla();
      }
      toast(data.error ?? 'No se pudo procesar el voto', false);
      return;
    }

    toast(yaVoto ? 'Voto retirado correctamente' : '¡Voto registrado!', true);

    // Recarga en segundo plano para sincronizar con la BD
    await cargar();
  } catch (err) {
    toast('Error al procesar voto: ' + err.message, false);
  }
}

async function abrirCommentsModal(idReporte) {
  const rep = todosLosReportes.find(x => x.idReporte == idReporte);
  const tituloReporte = rep ? rep.titulo : '';
  
  document.getElementById('commReportId').value = idReporte;
  document.getElementById('commentsModalTit').textContent = `Comentarios: "${tituloReporte}"`;
  document.getElementById('newCommentText').value = '';
  document.getElementById('commentsModal').classList.add('open');
  await cargarComentarios(idReporte);
}

function cerrarCommentsModal() {
  document.getElementById('commentsModal').classList.remove('open');
}

async function cargarComentarios(idReporte) {
  const thread = document.getElementById('commentsThread');
  thread.innerHTML = '<div class="text-muted" style="text-align: center; padding: 2rem;">Cargando comentarios...</div>';
  
  try {
    const res = await fetch(`../api/comentarios.php?idReporte=${idReporte}`);
    const data = await res.json();
    
    if (!res.ok || data.error) {
      thread.innerHTML = `<div class="text-danger" style="text-align: center; padding: 2rem;">Error al cargar comentarios: ${data.error ?? ''}</div>`;
      return;
    }
    
    if (data.length === 0) {
      thread.innerHTML = '<div class="text-muted" style="text-align: center; padding: 2rem; font-style: italic;">Sin comentarios. ¡Sé el primero en comentar!</div>';
      return;
    }
    
    thread.innerHTML = data.map(c => {
      const autor = c.usuario ? escapeHtml(c.usuario) : 'Usuario desconocido';
      const fecha = formatearFecha(c.fechaComentario) + ' ' + String(c.fechaComentario).substring(11, 16);
      const rolBadge = c.rol ? `<span class="badge badge-${escapeHtml(c.rol)}" style="font-size: 0.6rem; padding: 1px 6px; margin-left: 6px;">${escapeHtml(c.rol)}</span>` : '';
      
      return `
        <div class="comment-card">
          <div class="comment-meta">
            <span class="comment-author">${autor}${rolBadge}</span>
            <span class="comment-date">${fecha}</span>
          </div>
          <div class="comment-text">${escapeHtml(c.contenido)}</div>
        </div>
      `;
    }).join('');
    
    thread.scrollTop = thread.scrollHeight;
    
  } catch (err) {
    thread.innerHTML = `<div class="text-danger" style="text-align: center; padding: 2rem;">Error al cargar comentarios: ${err.message}</div>`;
  }
}

async function guardarComentario() {
  const idReporte = Number(document.getElementById('commReportId').value);
  const contenido = document.getElementById('newCommentText').value.trim();
  
  if (!contenido) {
    toast('El comentario no puede estar vacío', false);
    return;
  }
  
  try {
    const res = await fetch('../api/comentarios.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        contenido: contenido,
        idReporte: idReporte,
        idUsuario: USER_ID
      })
    });
    
    const data = await res.json();
    if (!res.ok || data.error) {
      toast(data.error ?? 'No se pudo guardar el comentario', false);
      return;
    }
    
    document.getElementById('newCommentText').value = '';
    toast('Comentario agregado correctamente', true);
    await cargarComentarios(idReporte);
  } catch (err) {
    toast('Error al guardar comentario: ' + err.message, false);
  }
}

function toast(msg, ok) {
  const t = document.getElementById('toast');

  t.textContent = msg;
  t.className = ok ? 'show ok' : 'show err';

  setTimeout(() => {
    t.className = '';
  }, 3000);
}

async function iniciar() {
  const nameEl = document.getElementById('citizenName');
  if (nameEl) {
    nameEl.textContent = USER_NAME;
  }
  await cargarMisVotos();
  await cargarCatalogos();
  await cargar();
}

iniciar();
</script>

</body>
</html>