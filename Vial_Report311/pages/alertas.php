<?php
require_once __DIR__ . '/../config/session.php';
requireRole();
$rol = rolActual();
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
  </style>
</head>
<body class="role-<?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?>">

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2><?= ($rol === 'ciudadano') ? 'Mis <span>Alertas Locales</span>' : 'Gestión de <span>Alertas Locales</span>' ?></h2>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nueva alerta</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th class="col-admin-alerta">Usuario</th>
          <th class="col-admin-alerta">Rol</th>
          <th>Ubicación</th>
          <th>Frecuencia</th>
          <th>Rango</th>
          <th>Coordenadas</th>
          <th>Acciones</th>
        </tr>
      </thead>

      <tbody id="tbody">
        <tr class="empty-row">
          <td colspan="8">Cargando...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal CREATE / UPDATE -->
<div class="modal-overlay" id="modal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTit">Nueva Alerta Local</h3>
      <button onclick="cerrarModal()">✕</button>
    </div>

    <div class="modal-body">
      <input type="hidden" id="aid"/>

      <div class="form-row">
        <div class="form-group" id="grp-usuario-alerta">
          <label>Usuario *</label>
          <select id="idUsuario"></select>
        </div>

        <div class="form-group">
          <label>Ubicación *</label>
          <select id="idUbicacion"></select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Frecuencia de alerta *</label>
          <select id="frecuencia_alerta">
            <option value="">Seleccione una frecuencia</option>
            <option value="inmediata">Inmediata</option>
            <option value="diaria">Diaria</option>
            <option value="semanal">Semanal</option>
            <option value="mensual">Mensual</option>
          </select>
        </div>

        <div class="form-group">
          <label>Rango en kilómetros *</label>
          <input type="number" step="0.01" min="0.1" id="rango_km" placeholder="Ej: 2.5"/>
        </div>
      </div>
    </div>

    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="cerrarModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="guardar()">Guardar</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const USER_ROLE = '<?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?>';
const USER_ID = <?= json_encode($_SESSION['usuario_id'] ?? null) ?>;

const API_ALERTAS = '../api/alertas.php';
const API_USUARIOS = '../api/usuarios.php';
const API_UBICACIONES = '../api/ubicaciones.php';

let usuarios = [];
let ubicaciones = [];

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
  const apellido2 = u.apellido_2 ? ' ' + u.apellido_2 : '';
  return `${u.nombres} ${u.apellido_1}${apellido2}`;
}

function textoUbicacion(u) {
  const partes = [];

  if (u.barrio) partes.push(u.barrio);
  if (u.direccionTexto) partes.push(u.direccionTexto);
  if (u.ciudad) partes.push(u.ciudad);

  return partes.join(' - ');
}

function textoUbicacionDesdeAlerta(a) {
  const partes = [];

  if (a.barrio) partes.push(a.barrio);
  if (a.direccionTexto) partes.push(a.direccionTexto);
  if (a.ciudad) partes.push(a.ciudad);

  return partes.join(' - ');
}

function textoCoordenadas(a) {
  if (!a.latitud && !a.longitud) {
    return '—';
  }

  return `${a.latitud ?? '—'}, ${a.longitud ?? '—'}`;
}

async function cargarCatalogos() {
  const peticiones = [fetch(API_UBICACIONES)];

  if (USER_ROLE !== 'ciudadano') {
    peticiones.unshift(fetch(API_USUARIOS));
  }

  const resultados = await Promise.all(peticiones);

  if (USER_ROLE !== 'ciudadano') {
    usuarios = await resultados[0].json();
    ubicaciones = await resultados[1].json();
    llenarUsuarios();
  } else {
    ubicaciones = await resultados[0].json();
  }

  llenarUbicaciones();
}

function llenarUsuarios(seleccionado = '') {
  const select = document.getElementById('idUsuario');

  select.innerHTML = '<option value="">Seleccione un usuario</option>' +
    usuarios.map(u => `
      <option value="${u.idUsuario}" ${u.idUsuario == seleccionado ? 'selected' : ''}>
        ${escapeHtml(textoUsuario(u))} - ${escapeHtml(u.rol)}
      </option>
    `).join('');
}

function llenarUbicaciones(seleccionado = '') {
  const select = document.getElementById('idUbicacion');

  select.innerHTML = '<option value="">Seleccione una ubicación</option>' +
    ubicaciones.map(u => `
      <option value="${u.idUbicacion}" ${u.idUbicacion == seleccionado ? 'selected' : ''}>
        ${escapeHtml(textoUbicacion(u))}
      </option>
    `).join('');
}

async function cargar() {
  const url = USER_ROLE === 'ciudadano' && USER_ID
    ? `${API_ALERTAS}?idUsuario=${USER_ID}`
    : API_ALERTAS;

  const res = await fetch(url);
  const data = await res.json();
  const tbody = document.getElementById('tbody');
  const colspan = USER_ROLE === 'ciudadano' ? 6 : 8;

  if (!res.ok || data.error) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="${colspan}">Error al cargar alertas.</td></tr>`;
    toast(data.error ?? 'Error al cargar alertas', false);
    return;
  }

  if (!data.length) {
    tbody.innerHTML = `<tr class="empty-row"><td colspan="${colspan}">No hay alertas locales registradas.</td></tr>`;
    return;
  }

  tbody.innerHTML = data.map(a => `
    <tr>
      <td>#${a.idAlerta}</td>

      ${USER_ROLE !== 'ciudadano' ? `
      <td class="col-admin-alerta">
        <strong>${escapeHtml(a.usuario)}</strong>
        <br>
        <small>${escapeHtml(a.email)}</small>
      </td>

      <td class="col-admin-alerta">
        <span class="badge badge-${escapeHtml(a.rol ?? 'usuario')}">
          ${escapeHtml(a.rol)}
        </span>
      </td>
      ` : ''}

      <td>${escapeHtml(textoUbicacionDesdeAlerta(a))}</td>

      <td>
        <span class="badge badge-en_proceso">
          ${escapeHtml(a.frecuencia_alerta)}
        </span>
      </td>

      <td>${escapeHtml(a.rango_km)} km</td>

      <td>${escapeHtml(textoCoordenadas(a))}</td>

      <td class="td-acc">
        <button class="btn-edit" onclick="editar(${a.idAlerta})">✏ Editar</button>
        <button class="btn-del" onclick="eliminar(${a.idAlerta})">🗑 Eliminar</button>
      </td>
    </tr>
  `).join('');
}

function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nueva Alerta Local';

  document.getElementById('aid').value = '';
  document.getElementById('idUsuario').value = USER_ROLE === 'ciudadano' ? String(USER_ID) : '';
  document.getElementById('idUbicacion').value = '';
  document.getElementById('frecuencia_alerta').value = '';
  document.getElementById('rango_km').value = '';

  document.getElementById('modal').classList.add('open');
}

async function editar(id) {
  const res = await fetch(`${API_ALERTAS}?id=${id}`);
  const a = await res.json();

  if (!res.ok || a.error) {
    toast(a.error ?? 'No se pudo cargar la alerta local', false);
    return;
  }

  document.getElementById('modalTit').textContent = 'Editar Alerta Local';

  document.getElementById('aid').value = a.idAlerta;

  if (USER_ROLE === 'ciudadano') {
    document.getElementById('idUsuario').value = String(USER_ID);
  } else {
    llenarUsuarios(a.idUsuario);
  }

  llenarUbicaciones(a.idUbicacion);
  document.getElementById('frecuencia_alerta').value = a.frecuencia_alerta ?? '';
  document.getElementById('rango_km').value = a.rango_km ?? '';

  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

function construirBody() {
  const idUsuario = USER_ROLE === 'ciudadano'
    ? USER_ID
    : (document.getElementById('idUsuario').value
        ? Number(document.getElementById('idUsuario').value)
        : null);

  return {
    idUsuario,

    idUbicacion: document.getElementById('idUbicacion').value
      ? Number(document.getElementById('idUbicacion').value)
      : null,

    frecuencia_alerta: document.getElementById('frecuencia_alerta').value,

    rango_km: document.getElementById('rango_km').value
      ? Number(document.getElementById('rango_km').value)
      : null
  };
}

function validarFormulario(body) {
  if (!body.idUsuario) {
    toast('Debe seleccionar el usuario asociado', false);
    return false;
  }

  if (!body.idUbicacion) {
    toast('Debe seleccionar la ubicación asociada', false);
    return false;
  }

  if (!body.frecuencia_alerta) {
    toast('Debe seleccionar la frecuencia de la alerta', false);
    return false;
  }

  if (!body.rango_km || body.rango_km <= 0) {
    toast('Debe indicar un rango válido en kilómetros', false);
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

  const url = id ? `${API_ALERTAS}?id=${id}` : API_ALERTAS;
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
    toast(data.error ?? 'No se pudo guardar la alerta local', false);
    return;
  }

  toast(data.mensaje ?? 'Alerta local guardada correctamente', true);

  cerrarModal();
  cargar();
}

async function eliminar(id) {
  if (!confirm(`¿Eliminar la alerta local #${id}?`)) {
    return;
  }

  const res = await fetch(`${API_ALERTAS}?id=${id}`, {
    method: 'DELETE'
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo eliminar la alerta local', false);
    return;
  }

  toast(data.mensaje ?? 'Alerta local eliminada correctamente', true);
  cargar();
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
  await cargarCatalogos();
  await cargar();
}

iniciar();
</script>

</body>
</html>