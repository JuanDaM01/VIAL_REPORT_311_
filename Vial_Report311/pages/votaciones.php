<?php require_once __DIR__ . '/../config/session.php'; requireRole(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Votaciones — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2>Gestión de <span>Votaciones</span></h2>
    <button class="btn btn-primary" onclick="abrirModal()">+ Registrar voto</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Usuario</th>
          <th>Reporte</th>
          <th>Categoría</th>
          <th>Ubicación</th>
          <th>Estado reporte</th>
          <th>Total votos</th>
          <th>Fecha voto</th>
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

<!-- Modal CREATE -->
<div class="modal-overlay" id="modal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTit">Registrar Voto</h3>
      <button onclick="cerrarModal()">✕</button>
    </div>

    <div class="modal-body">
      <div class="form-group">
        <label>Usuario que vota *</label>
        <select id="idUsuario"></select>
      </div>

      <div class="form-group">
        <label>Reporte votado *</label>
        <select id="idReporte"></select>
      </div>
    </div>

    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="cerrarModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="guardar()">Guardar voto</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const API_VOTOS = '../api/votos.php';
const API_USUARIOS = '../api/usuarios.php';
const API_REPORTES = '../api/reportes.php';

let usuarios = [];
let reportes = [];

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
  return String(fecha).substring(0, 19);
}

function textoUsuario(u) {
  const apellido2 = u.apellido_2 ? ' ' + u.apellido_2 : '';
  return `${u.nombres} ${u.apellido_1}${apellido2}`;
}

function textoEstado(estado) {
  if (!estado) return '—';
  return String(estado).replace('_', ' ');
}

async function cargarCatalogos() {
  const [resUsuarios, resReportes] = await Promise.all([
    fetch(API_USUARIOS),
    fetch(API_REPORTES)
  ]);

  usuarios = await resUsuarios.json();
  reportes = await resReportes.json();

  llenarUsuarios();
  llenarReportes();
}

function llenarUsuarios() {
  const select = document.getElementById('idUsuario');

  select.innerHTML = '<option value="">Seleccione un usuario</option>' +
    usuarios.map(u => `
      <option value="${u.idUsuario}">
        ${escapeHtml(textoUsuario(u))} - ${escapeHtml(u.email)}
      </option>
    `).join('');
}

function llenarReportes() {
  const select = document.getElementById('idReporte');

  select.innerHTML = '<option value="">Seleccione un reporte</option>' +
    reportes.map(r => `
      <option value="${r.idReporte}">
        #${r.idReporte} - ${escapeHtml(r.titulo)} (${escapeHtml(r.categoria)})
      </option>
    `).join('');
}

async function cargar() {
  const res = await fetch(API_VOTOS);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!res.ok || data.error) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="8">Error al cargar votaciones.</td></tr>';
    toast(data.error ?? 'Error al cargar votaciones', false);
    return;
  }

  if (!data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="8">No hay votos registrados.</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(v => `
    <tr>
      <td>
        <strong>${escapeHtml(v.usuario)}</strong>
        <br>
        <small>${escapeHtml(v.email)}</small>
      </td>

      <td>
        <strong>#${v.idReporte}</strong>
        <br>
        <small>${escapeHtml(v.reporte)}</small>
      </td>

      <td>${escapeHtml(v.categoria)}</td>
      <td>${escapeHtml(v.ubicacion)}</td>

      <td>
        <span class="badge badge-${escapeHtml(v.estadoReporte)}">
          ${escapeHtml(textoEstado(v.estadoReporte))}
        </span>
      </td>

      <td>
        <span class="badge badge-en_proceso">
          ${escapeHtml(v.totalVotosReporte ?? 0)}
        </span>
      </td>

      <td>${escapeHtml(formatearFecha(v.fechaVoto))}</td>

      <td class="td-acc">
        <button class="btn-del" onclick="eliminar(${v.idUsuario}, ${v.idReporte})">
          🗑 Eliminar voto
        </button>
      </td>
    </tr>
  `).join('');
}

function abrirModal() {
  document.getElementById('idUsuario').value = '';
  document.getElementById('idReporte').value = '';

  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

function construirBody() {
  return {
    idUsuario: document.getElementById('idUsuario').value
      ? Number(document.getElementById('idUsuario').value)
      : null,

    idReporte: document.getElementById('idReporte').value
      ? Number(document.getElementById('idReporte').value)
      : null
  };
}

function validarFormulario(body) {
  if (!body.idUsuario) {
    toast('Debe seleccionar el usuario que vota', false);
    return false;
  }

  if (!body.idReporte) {
    toast('Debe seleccionar el reporte votado', false);
    return false;
  }

  return true;
}

async function guardar() {
  const body = construirBody();

  if (!validarFormulario(body)) {
    return;
  }

  const res = await fetch(API_VOTOS, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(body)
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo registrar el voto', false);
    return;
  }

  toast(data.mensaje ?? 'Voto registrado correctamente', true);

  cerrarModal();
  await cargarCatalogos();
  await cargar();
}

async function eliminar(idUsuario, idReporte) {
  if (!confirm('¿Eliminar este voto?')) {
    return;
  }

  const res = await fetch(`${API_VOTOS}?idUsuario=${idUsuario}&idReporte=${idReporte}`, {
    method: 'DELETE'
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo eliminar el voto', false);
    return;
  }

  toast(data.mensaje ?? 'Voto eliminado correctamente', true);

  await cargarCatalogos();
  await cargar();
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