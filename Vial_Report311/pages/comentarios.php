<?php require_once __DIR__ . '/../config/session.php'; requireRole(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Comentarios — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2>Gestión de <span>Comentarios</span></h2>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nuevo comentario</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Reporte</th>
          <th>Comentario</th>
          <th>Usuario</th>
          <th>Rol</th>
          <th>Fecha</th>
          <th>Acciones</th>
        </tr>
      </thead>

      <tbody id="tbody">
        <tr class="empty-row">
          <td colspan="7">Cargando...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="modal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTit">Nuevo Comentario</h3>
      <button onclick="cerrarModal()">✕</button>
    </div>

    <div class="modal-body">
      <input type="hidden" id="cid"/>

      <div class="form-group">
        <label>Reporte asociado *</label>
        <select id="idReporte"></select>
      </div>

      <div class="form-group">
        <label>Usuario que comenta</label>
        <select id="idUsuario"></select>
      </div>

      <div class="form-group">
        <label>Comentario *</label>
        <textarea id="contenido" rows="4" placeholder="Escriba el comentario del seguimiento"></textarea>
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
const API_COMENTARIOS = '../api/comentarios.php';
const API_REPORTES = '../api/reportes.php';
const API_USUARIOS = '../api/usuarios.php';

let reportes = [];
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

function formatearFecha(fecha) {
  if (!fecha) return '—';
  return String(fecha).substring(0, 19);
}

function textoUsuario(u) {
  const apellido2 = u.apellido_2 ? ' ' + u.apellido_2 : '';
  return `${u.nombres} ${u.apellido_1}${apellido2}`;
}

async function cargarCatalogos() {
  const [resReportes, resUsuarios] = await Promise.all([
    fetch(API_REPORTES),
    fetch(API_USUARIOS)
  ]);

  reportes = await resReportes.json();
  usuarios = await resUsuarios.json();

  llenarReportes();
  llenarUsuarios();
}

function llenarReportes(seleccionado = '') {
  const select = document.getElementById('idReporte');

  select.innerHTML = '<option value="">Seleccione un reporte</option>' +
    reportes.map(r => `
      <option value="${r.idReporte}" ${r.idReporte == seleccionado ? 'selected' : ''}>
        #${r.idReporte} - ${escapeHtml(r.titulo)}
      </option>
    `).join('');
}

function llenarUsuarios(seleccionado = '') {
  const select = document.getElementById('idUsuario');

  select.innerHTML = '<option value="">Sin usuario / comentario del sistema</option>' +
    usuarios.map(u => `
      <option value="${u.idUsuario}" ${u.idUsuario == seleccionado ? 'selected' : ''}>
        ${escapeHtml(textoUsuario(u))} - ${escapeHtml(u.rol)}
      </option>
    `).join('');
}

async function cargar() {
  const res = await fetch(API_COMENTARIOS);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!res.ok || data.error) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="7">Error al cargar comentarios.</td></tr>';
    toast(data.error ?? 'Error al cargar comentarios', false);
    return;
  }

  if (!data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="7">No hay comentarios registrados.</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(c => `
    <tr>
      <td>#${c.idComentario}</td>

      <td>
        <strong>#${c.idReporte}</strong>
        <br>
        <small>${escapeHtml(c.reporte)}</small>
      </td>

      <td>${escapeHtml(c.contenido)}</td>
      <td>${escapeHtml(c.usuario ?? 'Sistema')}</td>

      <td>
        <span class="badge badge-${escapeHtml(c.rol ?? 'sistema')}">
          ${escapeHtml(c.rol ?? 'sistema')}
        </span>
      </td>

      <td>${escapeHtml(formatearFecha(c.fechaComentario))}</td>

      <td class="td-acc">
        <button class="btn-edit" onclick="editar(${c.idComentario})">✏ Editar</button>
        <button class="btn-del" onclick="eliminar(${c.idComentario})">🗑 Eliminar</button>
      </td>
    </tr>
  `).join('');
}

function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nuevo Comentario';

  document.getElementById('cid').value = '';
  document.getElementById('idReporte').value = '';
  document.getElementById('idUsuario').value = '';
  document.getElementById('contenido').value = '';

  document.getElementById('modal').classList.add('open');
}

async function editar(id) {
  const res = await fetch(`${API_COMENTARIOS}?id=${id}`);
  const c = await res.json();

  if (!res.ok || c.error) {
    toast(c.error ?? 'No se pudo cargar el comentario', false);
    return;
  }

  document.getElementById('modalTit').textContent = 'Editar Comentario';

  document.getElementById('cid').value = c.idComentario;
  llenarReportes(c.idReporte);
  llenarUsuarios(c.idUsuario);
  document.getElementById('contenido').value = c.contenido ?? '';

  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

function construirBody() {
  return {
    idReporte: document.getElementById('idReporte').value
      ? Number(document.getElementById('idReporte').value)
      : null,

    idUsuario: document.getElementById('idUsuario').value
      ? Number(document.getElementById('idUsuario').value)
      : null,

    contenido: document.getElementById('contenido').value.trim()
  };
}

function validarFormulario(body) {
  if (!body.idReporte) {
    toast('Debe seleccionar el reporte asociado', false);
    return false;
  }

  if (!body.contenido) {
    toast('El comentario es obligatorio', false);
    return false;
  }

  return true;
}

async function guardar() {
  const id = document.getElementById('cid').value;
  const body = construirBody();

  if (!validarFormulario(body)) {
    return;
  }

  const url = id ? `${API_COMENTARIOS}?id=${id}` : API_COMENTARIOS;
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
    toast(data.error ?? 'No se pudo guardar el comentario', false);
    return;
  }

  toast(data.mensaje ?? 'Comentario guardado correctamente', true);

  cerrarModal();
  cargar();
}

async function eliminar(id) {
  if (!confirm(`¿Eliminar el comentario #${id}?`)) {
    return;
  }

  const res = await fetch(`${API_COMENTARIOS}?id=${id}`, {
    method: 'DELETE'
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo eliminar el comentario', false);
    return;
  }

  toast(data.mensaje ?? 'Comentario eliminado correctamente', true);
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