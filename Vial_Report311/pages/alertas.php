<?php
require_once __DIR__ . '/../config/session.php';
requireRole();
$rol = rolActual();
$esCiudadano = ($rol === 'ciudadano');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Alertas Locales — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
  <style>
    body.role-ciudadano .col-admin-alerta,
    body.role-ciudadano #grp-usuario-alerta {
      display: none !important;
    }
    .page-intro {
      color: var(--text2);
      font-size: .88rem;
      margin: -.5rem 0 1.25rem;
      max-width: 640px;
      line-height: 1.55;
    }
    .modal-section-title {
      font-size: .8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--accent);
      margin: 0 0 .75rem;
    }
    .modal-section + .modal-section {
      margin-top: 1.1rem;
      padding-top: 1.1rem;
      border-top: 1px solid var(--border);
    }
  </style>
</head>
<body class="role-<?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?>">

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2><?= $esCiudadano ? 'Mis <span>Alertas Locales</span>' : 'Gestión de <span>Alertas Locales</span>' ?></h2>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nueva alerta</button>
  </div>

  <?php if ($esCiudadano): ?>
  <p class="page-intro">
    Recibe avisos cuando haya reportes viales cerca de una zona que tú eliges.
    Indica ciudad y el radio en kilómetros.
  </p>
  <?php endif; ?>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th class="col-admin-alerta">Usuario</th>
          <th>Zona</th>
          <th>Frecuencia</th>
          <th>Rango</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr class="empty-row"><td colspan="9">Cargando...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="modal">
  <div class="modal" style="max-width:520px;">
    <div class="modal-head">
      <h3 id="modalTit">Nueva alerta</h3>
      <button type="button" onclick="cerrarModal()" aria-label="Cerrar">✕</button>
    </div>

    <div class="modal-body">
      <input type="hidden" id="aid"/>
      <input type="hidden" id="idUsuario" value="<?= $esCiudadano ? (int) ($_SESSION['usuario_id'] ?? 0) : '' ?>"/>

      <p class="modal-section-title">Zona a vigilar</p>

      <div class="form-row">
        <div class="form-group">
          <label>Departamento</label>
          <input type="text" id="departamento" placeholder="Quindío"/>
        </div>
        <div class="form-group">
          <label>Ciudad *</label>
          <input type="text" id="ciudad" placeholder="Armenia" required/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Barrio</label>
          <input type="text" id="barrio" placeholder="El Bosque"/>
        </div>
      </div>

      <p class="modal-section-title">Cómo quieres que te avisen</p>

      <div id="grp-usuario-alerta" class="form-group">
        <label>Usuario *</label>
        <select id="idUsuarioSelect"></select>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Frecuencia *</label>
          <select id="frecuencia_alerta" required>
            <option value="">Seleccione</option>
            <option value="inmediata">Inmediata</option>
            <option value="diaria">Diaria</option>
            <option value="semanal">Semanal</option>
          </select>
        </div>
        <div class="form-group">
          <label>Radio (km) *</label>
          <input type="number" step="0.01" min="0.1" max="50" id="rango_km" placeholder="2.5" required/>
        </div>
      </div>
    </div>

    <div class="modal-foot">
      <button type="button" class="btn btn-ghost" onclick="cerrarModal()">Cancelar</button>
      <button type="button" class="btn btn-primary" onclick="guardar()">Guardar</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const USER_ROLE = '<?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?>';
const USER_ID = <?= json_encode($_SESSION['usuario_id'] ?? null) ?>;

const API_ALERTAS = '../api/alertas.php';
const API_USUARIOS = '../api/usuarios.php';

let usuarios = [];

function escapeHtml(valor) {
  if (valor === null || valor === undefined || valor === '') return '—';
  return String(valor)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function textoUsuario(u) {
  const ap2 = u.apellido_2 ? ' ' + u.apellido_2 : '';
  return `${u.nombres} ${u.apellido_1}${ap2}`;
}

function textoZona(a) {
  const partes = [a.barrio, a.ciudad, a.departamento].filter(Boolean);
  return partes.length ? partes.join(' · ') : '—';
}

function limpiarZona() {
  ['departamento', 'ciudad', 'barrio'].forEach((id) => {
    document.getElementById(id).value = '';
  });
}

function llenarZona(a) {
  document.getElementById('departamento').value = a.departamento ?? '';
  document.getElementById('ciudad').value = a.ciudad ?? '';
  document.getElementById('barrio').value = a.barrio ?? '';
}

async function cargarCatalogos() {
  if (USER_ROLE !== 'ciudadano') {
    const res = await fetch(API_USUARIOS);
    usuarios = await res.json();
    llenarUsuarios();
  }
}

function llenarUsuarios(seleccionado = '') {
  const select = document.getElementById('idUsuarioSelect');
  if (!select) return;

  select.innerHTML = '<option value="">Seleccione un usuario</option>' +
    usuarios.map((u) => `
      <option value="${u.idUsuario}" ${u.idUsuario == seleccionado ? 'selected' : ''}>
        ${escapeHtml(textoUsuario(u))}
      </option>
    `).join('');
}

function colspanTabla() {
  return USER_ROLE === 'ciudadano' ? 5 : 6;
}

async function cargar() {
  const url = USER_ROLE === 'ciudadano' && USER_ID
    ? `${API_ALERTAS}?idUsuario=${USER_ID}`
    : API_ALERTAS;

  const res = await fetch(url);
  const data = await res.json();
  const tbody = document.getElementById('tbody');
  const colspan = colspanTabla();

  if (!res.ok || data.error) {
    const detalle = data.detalle ? ` (${data.detalle})` : '';
    tbody.innerHTML = `<tr class="empty-row"><td colspan="${colspan}">No se pudieron cargar las alertas.${escapeHtml(detalle)}</td></tr>`;
    toast(data.error ?? 'Error al cargar', false);
    return;
  }

  if (!Array.isArray(data) || !data.length) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="${colspan}">Aún no tienes alertas. Pulsa «+ Nueva alerta».</td></tr>`;
    return;
  }

  tbody.innerHTML = data.map((a) => `
    <tr>
      <td>#${a.idAlerta}</td>
      ${USER_ROLE !== 'ciudadano' ? `
      <td class="col-admin-alerta"><strong>${escapeHtml(a.usuario)}</strong></td>` : ''}
      <td>${escapeHtml(textoZona(a))}</td>
      <td><span class="badge badge-en_proceso">${escapeHtml(a.frecuencia_alerta)}</span></td>
      <td>${escapeHtml(a.rango_km)} km</td>
      <td class="td-acc">
        <button type="button" class="btn-edit" onclick="editar(${a.idAlerta})">Editar</button>
        <button type="button" class="btn-del" onclick="eliminar(${a.idAlerta})">Eliminar</button>
      </td>
    </tr>
  `).join('');
}

function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nueva alerta';
  document.getElementById('aid').value = '';
  limpiarZona();
  document.getElementById('frecuencia_alerta').value = '';
  document.getElementById('rango_km').value = '';

  if (USER_ROLE !== 'ciudadano') {
    document.getElementById('idUsuarioSelect').value = '';
  }

  document.getElementById('modal').classList.add('open');
}

async function editar(id) {
  const res = await fetch(`${API_ALERTAS}?id=${id}`);
  const a = await res.json();

  if (!res.ok || a.error) {
    toast(a.error ?? 'No se pudo abrir la alerta', false);
    return;
  }

  document.getElementById('modalTit').textContent = 'Editar alerta';
  document.getElementById('aid').value = a.idAlerta;
  llenarZona(a);
  document.getElementById('frecuencia_alerta').value = a.frecuencia_alerta ?? '';
  document.getElementById('rango_km').value = a.rango_km ?? '';

  if (USER_ROLE !== 'ciudadano') {
    llenarUsuarios(a.idUsuario);
    document.getElementById('idUsuarioSelect').value = a.idUsuario ?? '';
  }

  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

function obtenerZona() {
  return {
    departamento: document.getElementById('departamento').value.trim() || null,
    ciudad: document.getElementById('ciudad').value.trim(),
    barrio: document.getElementById('barrio').value.trim() || null
  };
}

function construirBody() {
  const idUsuario = USER_ROLE === 'ciudadano'
    ? USER_ID
    : (document.getElementById('idUsuarioSelect').value
        ? Number(document.getElementById('idUsuarioSelect').value)
        : null);

  const zona = obtenerZona();

  return {
    idUsuario,
    departamento: zona.departamento,
    ciudad: zona.ciudad,
    barrio: zona.barrio,
    frecuencia_alerta: document.getElementById('frecuencia_alerta').value,
    rango_km: document.getElementById('rango_km').value
      ? Number(document.getElementById('rango_km').value)
      : null
  };
}

function validarFormulario(body) {
  if (!body.idUsuario) {
    toast('Usuario no válido', false);
    return false;
  }
  if (!body.ciudad) {
    toast('Indica la ciudad de la zona', false);
    return false;
  }
  if (!body.frecuencia_alerta) {
    toast('Selecciona la frecuencia', false);
    return false;
  }
  if (!body.rango_km || body.rango_km <= 0) {
    toast('Indica un radio válido en km', false);
    return false;
  }
  return true;
}

async function guardar() {
  const id = document.getElementById('aid').value;
  const body = construirBody();

  if (!validarFormulario(body)) {
    return;
  }

  const res = await fetch(id ? `${API_ALERTAS}?id=${id}` : API_ALERTAS, {
    method: id ? 'PUT' : 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body)
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo guardar', false);
    return;
  }

  toast(data.mensaje ?? 'Alerta guardada', true);
  cerrarModal();
  cargar();
}

async function eliminar(id) {
  if (!confirm('¿Eliminar esta alerta?')) {
    return;
  }

  const res = await fetch(`${API_ALERTAS}?id=${id}`, { method: 'DELETE' });
  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo eliminar', false);
    return;
  }

  toast('Alerta eliminada', true);
  cargar();
}

function toast(msg, ok) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = ok ? 'show ok' : 'show err';
  setTimeout(() => { t.className = ''; }, 3000);
}

async function iniciar() {
  await cargarCatalogos();
  await cargar();
}

iniciar();
</script>

</body>
</html>
