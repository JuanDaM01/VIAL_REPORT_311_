<?php require_once __DIR__ . '/../config/session.php'; requireRole(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Notificaciones — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page">
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
          <th>ID</th>
          <th>Título</th>
          <th>Mensaje</th>
          <th>Tipo</th>
          <th>Estado</th>
          <th>Usuario</th>
          <th>Reporte</th>
          <th>Fecha</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr class="empty-row">
          <td colspan="9">Cargando...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal CREATE / UPDATE -->
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

<div id="toast"></div>

<script>
const API_NOTIFICACIONES = '../api/notificaciones.php';
const API_USUARIOS = '../api/usuarios.php';
const API_REPORTES = '../api/reportes.php';

let filtroLeida = '';
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

function textoTipo(tipo) {
  const tipos = {
    creacion_reporte: 'Creación de reporte',
    cambio_estado: 'Cambio de estado',
    comentario: 'Comentario',
    ticket_asignado: 'Ticket asignado',
    alerta_local: 'Alerta local'
  };

  return tipos[tipo] ?? tipo;
}

function textoUsuario(u) {
  const apellido2 = u.apellido_2 ? ' ' + u.apellido_2 : '';
  return `${u.nombres} ${u.apellido_1}${apellido2}`;
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
        ${escapeHtml(textoUsuario(u))} - ${escapeHtml(u.rol)}
      </option>
    `).join('');
}

function llenarReportes() {
  const select = document.getElementById('idReporte');

  select.innerHTML = '<option value="">Sin reporte relacionado</option>' +
    reportes.map(r => `
      <option value="${r.idReporte}">
        #${r.idReporte} - ${escapeHtml(r.titulo)}
      </option>
    `).join('');
}

async function cargar() {
  let url = API_NOTIFICACIONES;

  if (filtroLeida !== '') {
    url += `?leida=${filtroLeida}`;
  }

  const res = await fetch(url);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!res.ok || data.error) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="9">Error al cargar notificaciones.</td></tr>';
    toast(data.error ?? 'Error al cargar notificaciones', false);
    return;
  }

  if (!data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="9">No hay notificaciones registradas.</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(n => `
    <tr>
      <td>#${n.idNotificacion}</td>

      <td>
        <strong>${escapeHtml(n.titulo)}</strong>
      </td>

      <td>${escapeHtml(n.mensaje)}</td>

      <td>
        <span class="badge badge-${escapeHtml(n.tipo)}">
          ${escapeHtml(textoTipo(n.tipo))}
        </span>
      </td>

      <td>
        <span class="badge badge-${n.leida == 1 ? 'activo' : 'pendiente'}">
          ${n.leida == 1 ? 'Leída' : 'No leída'}
        </span>
      </td>

      <td>${escapeHtml(n.usuario)}</td>
      <td>${escapeHtml(n.reporte)}</td>
      <td>${escapeHtml(formatearFecha(n.fechaCreacion))}</td>

      <td class="td-acc">
        <button class="btn-edit" onclick="editar(${n.idNotificacion})">✏ Editar</button>
        <button class="btn-edit" onclick="cambiarLectura(${n.idNotificacion}, ${n.leida == 1 ? 0 : 1})">
          ${n.leida == 1 ? '↩ No leída' : '✓ Leída'}
        </button>
        <button class="btn-del" onclick="eliminar(${n.idNotificacion}, '${escapeHtml(n.titulo)}')">🗑 Eliminar</button>
      </td>
    </tr>
  `).join('');
}

function filtrarLectura(valor) {
  filtroLeida = valor;
  cargar();
}

function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nueva Notificación';

  document.getElementById('nid').value = '';
  document.getElementById('titulo').value = '';
  document.getElementById('mensaje').value = '';
  document.getElementById('tipo').value = 'creacion_reporte';
  document.getElementById('leida').value = '0';
  document.getElementById('idUsuario').value = '';
  document.getElementById('idReporte').value = '';

  document.getElementById('modal').classList.add('open');
}

async function editar(id) {
  const res = await fetch(`${API_NOTIFICACIONES}?id=${id}`);
  const n = await res.json();

  if (!res.ok || n.error) {
    toast(n.error ?? 'No se pudo cargar la notificación', false);
    return;
  }

  document.getElementById('modalTit').textContent = 'Editar Notificación';

  document.getElementById('nid').value = n.idNotificacion;
  document.getElementById('titulo').value = n.titulo ?? '';
  document.getElementById('mensaje').value = n.mensaje ?? '';
  document.getElementById('tipo').value = n.tipo ?? 'creacion_reporte';
  document.getElementById('leida').value = n.leida == 1 ? '1' : '0';
  document.getElementById('idUsuario').value = n.idUsuario ?? '';
  document.getElementById('idReporte').value = n.idReporte ?? '';

  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

function construirBody() {
  const idReporte = document.getElementById('idReporte').value;

  return {
    titulo: document.getElementById('titulo').value.trim(),
    mensaje: document.getElementById('mensaje').value.trim(),
    tipo: document.getElementById('tipo').value,
    leida: Number(document.getElementById('leida').value),
    idUsuario: document.getElementById('idUsuario').value
      ? Number(document.getElementById('idUsuario').value)
      : null,
    idReporte: idReporte ? Number(idReporte) : null
  };
}

function validarFormulario(body) {
  if (!body.titulo) {
    toast('El título es obligatorio', false);
    return false;
  }

  if (!body.mensaje) {
    toast('El mensaje es obligatorio', false);
    return false;
  }

  if (!body.tipo) {
    toast('El tipo es obligatorio', false);
    return false;
  }

  if (!body.idUsuario) {
    toast('Debe seleccionar el usuario destinatario', false);
    return false;
  }

  return true;
}

async function guardar() {
  const id = document.getElementById('nid').value;
  const body = construirBody();

  if (!validarFormulario(body)) {
    return;
  }

  const url = id ? `${API_NOTIFICACIONES}?id=${id}` : API_NOTIFICACIONES;
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
    toast(data.error ?? 'No se pudo guardar la notificación', false);
    return;
  }

  toast(data.mensaje ?? 'Notificación guardada correctamente', true);

  cerrarModal();
  cargar();
}

async function cambiarLectura(id, leida) {
  const res = await fetch(`${API_NOTIFICACIONES}?id=${id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      soloLectura: true,
      leida: leida
    })
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo actualizar la notificación', false);
    return;
  }

  toast(data.mensaje ?? 'Estado actualizado', true);
  cargar();
}

async function eliminar(id, titulo) {
  if (!confirm(`¿Eliminar la notificación "${titulo}"?`)) {
    return;
  }

  const res = await fetch(`${API_NOTIFICACIONES}?id=${id}`, {
    method: 'DELETE'
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo eliminar la notificación', false);
    return;
  }

  toast(data.mensaje ?? 'Notificación eliminada correctamente', true);
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