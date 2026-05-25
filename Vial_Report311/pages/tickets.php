<?php
require_once __DIR__ . '/../config/session.php';
requireRole(['funcionario', 'administrador']);
$rol = rolActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Tickets — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
  <style>
    body.role-funcionario #grp-funcionario-ticket,
    body.role-funcionario #grp-reporte-ticket,
    body.role-funcionario #grp-numero-ticket {
      display: none !important;
    }
  </style>
</head>
<body class="role-<?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?>">

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2><?= ($rol === 'funcionario') ? 'Mis <span>Tickets</span>' : 'Gestión de <span>Tickets</span>' ?></h2>
    <?php if ($rol !== 'funcionario'): ?>
      <button class="btn btn-primary" onclick="abrirModal()">+ Nuevo ticket</button>
    <?php endif; ?>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Caso</th>
          <th>Reporte</th>
          <th>Categoría</th>
          <th>Prioridad</th>
          <th>Estado ticket</th>
          <th>Proveedor</th>
          <th>Funcionario</th>
          <th>Ciudadano</th>
          <th>Ubicación</th>
          <th>Fechas</th>
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
      <h3 id="modalTit">Nuevo Ticket</h3>
      <button onclick="cerrarModal()">✕</button>
    </div>

    <div class="modal-body">
      <input type="hidden" id="tid"/>

      <div class="form-row">
        <div class="form-group" style="flex:2" id="grp-reporte-ticket">
          <label>Reporte asociado *</label>
          <select id="idReporte"></select>
        </div>

        <div class="form-group" id="grp-numero-ticket">
          <label>Número de caso</label>
          <input type="text" id="numeroCaso" placeholder="Automático"/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Prioridad *</label>
          <select id="prioridad">
            <option value="baja">Baja</option>
            <option value="media">Media</option>
            <option value="alta">Alta</option>
            <option value="critica">Crítica</option>
          </select>
        </div>

        <div class="form-group">
          <label>Estado *</label>
          <select id="estado">
            <option value="abierto">Abierto</option>
            <option value="en_proceso">En proceso</option>
            <option value="cerrado">Cerrado</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Proveedor responsable</label>
          <select id="idProveedor"></select>
        </div>

        <div class="form-group" id="grp-funcionario-ticket">
          <label>Funcionario asignado</label>
          <select id="idFuncionario"></select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Fecha de resolución</label>
          <input type="datetime-local" id="fechaResolucion"/>
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
const USER_ROLE = '<?= rolActual() ?>';
const USER_ID = <?= json_encode($_SESSION['usuario_id'] ?? null) ?>;

const API_TICKETS = '../api/tickets.php';
const API_REPORTES = '../api/reportes.php';
const API_CATALOGOS = '../api/catalogos.php?recurso=todo';

let ticketEnEdicion = null;

let catalogos = {
  reportes: [],
  proveedores: [],
  funcionarios: []
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
  return String(fecha).substring(0, 19);
}

function textoEstado(valor) {
  if (!valor) return '—';
  return String(valor).replace('_', ' ');
}

function badgePrioridad(prioridad) {
  const mapa = {
    baja: 'badge-resuelto',
    media: 'badge-en_proceso',
    alta: 'badge-warn2',
    critica: 'badge-rechazado'
  };

  return mapa[prioridad] ?? 'badge-recibido';
}

function badgeEstado(estado) {
  const mapa = {
    abierto: 'badge-recibido',
    en_proceso: 'badge-en_proceso',
    cerrado: 'badge-resuelto'
  };

  return mapa[estado] ?? 'badge-recibido';
}

function textoPersona(u) {
  const apellido2 = u.apellido_2 ? ' ' + u.apellido_2 : '';
  return `${u.nombres} ${u.apellido_1}${apellido2}`;
}

async function cargarCatalogos() {
  const [resCatalogos, resReportes] = await Promise.all([
    fetch(API_CATALOGOS),
    fetch(API_REPORTES)
  ]);

  const dataCatalogos = await resCatalogos.json();
  const dataReportes = await resReportes.json();

  if (!resCatalogos.ok || dataCatalogos.error) {
    toast(dataCatalogos.error ?? 'No se pudieron cargar los catálogos', false);
    return;
  }

  if (!resReportes.ok || dataReportes.error) {
    toast(dataReportes.error ?? 'No se pudieron cargar los reportes', false);
    return;
  }

  catalogos.proveedores = dataCatalogos.proveedores;
  catalogos.funcionarios = dataCatalogos.funcionarios;
  catalogos.reportes = dataReportes;

  llenarReportes();
  llenarProveedores();
  llenarFuncionarios();
}

function llenarReportes(seleccionado = '') {
  const select = document.getElementById('idReporte');

  select.innerHTML = '<option value="">Seleccione un reporte</option>' +
    catalogos.reportes.map(r => `
      <option value="${r.idReporte}" ${r.idReporte == seleccionado ? 'selected' : ''}>
        #${r.idReporte} - ${escapeHtml(r.titulo)}
      </option>
    `).join('');
}

function llenarProveedores(seleccionado = '') {
  const select = document.getElementById('idProveedor');

  select.innerHTML = '<option value="">Asignación automática</option>' +
    catalogos.proveedores.map(p => `
      <option value="${p.idProveedor}" ${p.idProveedor == seleccionado ? 'selected' : ''}>
        ${escapeHtml(p.nombreEntidad)}
      </option>
    `).join('');
}

function llenarFuncionarios(seleccionado = '') {
  const select = document.getElementById('idFuncionario');

  select.innerHTML = '<option value="">Asignación automática</option>' +
    catalogos.funcionarios.map(f => `
      <option value="${f.idUsuario}" ${f.idUsuario == seleccionado ? 'selected' : ''}>
        ${escapeHtml(textoPersona(f))} ${f.cargo ? '- ' + escapeHtml(f.cargo) : ''}
      </option>
    `).join('');
}

async function cargar() {
  const res = await fetch(API_TICKETS);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!res.ok || data.error) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="11">Error al cargar tickets.</td></tr>';
    toast(data.error ?? 'Error al cargar tickets', false);
    return;
  }

  let ticketsFiltrados = data;
  if (USER_ROLE === 'funcionario') {
    ticketsFiltrados = data.filter(t => t.idFuncionario == USER_ID);
  }

  if (!ticketsFiltrados.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="11">No se encontraron tickets.</td></tr>';
    return;
  }

  tbody.innerHTML = ticketsFiltrados.map(t => {
    const ciudadano = t.esAnonimo == 1
      ? '<span class="badge badge-anonimo">Anónimo</span>'
      : escapeHtml(t.ciudadano ?? '—');

    return `
      <tr>
        <td>
          <strong>${escapeHtml(t.numeroCaso)}</strong>
          <br>
          <small>#${t.idTicket}</small>
        </td>

        <td>
          <strong>#${t.idReporte}</strong>
          <br>
          ${escapeHtml(t.tituloReporte)}
          <br>
          <small>Reporte: ${escapeHtml(textoEstado(t.estadoReporte))}</small>
        </td>

        <td>${escapeHtml(t.categoria)}</td>

        <td>
          <span class="badge ${badgePrioridad(t.prioridad)}">
            ${escapeHtml(t.prioridad)}
          </span>
        </td>

        <td>
          <span class="badge ${badgeEstado(t.estado)}">
            ${escapeHtml(textoEstado(t.estado))}
          </span>
        </td>

        <td>${escapeHtml(t.proveedor ?? 'Sin asignar')}</td>
        <td>${escapeHtml(t.funcionario ?? 'Sin asignar')}</td>
        <td>${ciudadano}</td>
        <td>${escapeHtml(t.ubicacion)}</td>

        <td>
          <small>Asignación: ${escapeHtml(formatearFecha(t.fechaAsignacion))}</small>
          <br>
          <small>Resolución: ${escapeHtml(formatearFecha(t.fechaResolucion))}</small>
        </td>

        <td class="td-acc">
          <button class="btn-edit" onclick="editar(${t.idTicket})">${USER_ROLE === 'funcionario' ? '🛠 Gestionar' : '✏ Editar'}</button>
          ${USER_ROLE !== 'funcionario' ? `<button class="btn-del" onclick="eliminar(${t.idTicket}, '${escapeHtml(t.numeroCaso)}')">🗑 Eliminar</button>` : ''}
        </td>
      </tr>
    `;
  }).join('');
}

async function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nuevo Ticket';

  document.getElementById('tid').value = '';
  document.getElementById('numeroCaso').value = '';
  document.getElementById('prioridad').value = 'media';
  document.getElementById('estado').value = 'abierto';
  document.getElementById('fechaResolucion').value = '';

  llenarReportes();
  llenarProveedores();
  llenarFuncionarios();

  document.getElementById('modal').classList.add('open');
}

async function editar(id) {
  const res = await fetch(`${API_TICKETS}?id=${id}`);
  const t = await res.json();

  if (!res.ok || t.error) {
    toast(t.error ?? 'No se pudo cargar el ticket', false);
    return;
  }

  ticketEnEdicion = t;

  document.getElementById('modalTit').textContent = USER_ROLE === 'funcionario' ? '🛠 Gestionar Ticket' : 'Editar Ticket';

  document.getElementById('tid').value = t.idTicket;
  document.getElementById('numeroCaso').value = t.numeroCaso ?? '';
  document.getElementById('prioridad').value = t.prioridad ?? 'media';
  document.getElementById('estado').value = t.estado ?? 'abierto';
  document.getElementById('fechaResolucion').value = t.fechaResolucion
    ? String(t.fechaResolucion).replace(' ', 'T').substring(0, 16)
    : '';

  llenarReportes(t.idReporte);
  llenarProveedores(t.idProveedor);
  llenarFuncionarios(t.idFuncionario);

  if (USER_ROLE === 'funcionario') {
    document.getElementById('idReporte').disabled = true;
    document.getElementById('numeroCaso').disabled = true;
    document.getElementById('idFuncionario').disabled = true;
  } else {
    document.getElementById('idReporte').disabled = false;
    document.getElementById('numeroCaso').disabled = false;
    document.getElementById('idFuncionario').disabled = false;
  }

  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  ticketEnEdicion = null;
  document.getElementById('modal').classList.remove('open');
}

function construirBody() {
  if (USER_ROLE === 'funcionario' && ticketEnEdicion) {
    return {
      idReporte: Number(ticketEnEdicion.idReporte),
      numeroCaso: ticketEnEdicion.numeroCaso,
      prioridad: document.getElementById('prioridad').value,
      estado: document.getElementById('estado').value,
      idProveedor: document.getElementById('idProveedor').value
        ? Number(document.getElementById('idProveedor').value)
        : null,
      idFuncionario: ticketEnEdicion.idFuncionario
        ? Number(ticketEnEdicion.idFuncionario)
        : null,
      fechaResolucion: document.getElementById('fechaResolucion').value || null
    };
  }

  return {
    idReporte: document.getElementById('idReporte').value
      ? Number(document.getElementById('idReporte').value)
      : null,

    numeroCaso: document.getElementById('numeroCaso').value.trim() || null,

    prioridad: document.getElementById('prioridad').value,
    estado: document.getElementById('estado').value,

    idProveedor: document.getElementById('idProveedor').value
      ? Number(document.getElementById('idProveedor').value)
      : null,

    idFuncionario: document.getElementById('idFuncionario').value
      ? Number(document.getElementById('idFuncionario').value)
      : null,

    fechaResolucion: document.getElementById('fechaResolucion').value || null
  };
}

function validarFormulario(body) {
  if (!body.idReporte) {
    toast('Debe seleccionar un reporte asociado', false);
    return false;
  }

  if (!body.prioridad) {
    toast('Debe seleccionar la prioridad', false);
    return false;
  }

  if (!body.estado) {
    toast('Debe seleccionar el estado del ticket', false);
    return false;
  }

  return true;
}

async function guardar() {
  const id = document.getElementById('tid').value;

  if (USER_ROLE === 'funcionario' && !id) {
    toast('Los funcionarios no pueden crear tickets', false);
    return;
  }

  const body = construirBody();

  if (!validarFormulario(body)) {
    return;
  }

  const url = id ? `${API_TICKETS}?id=${id}` : API_TICKETS;
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
    toast(data.error ?? 'No se pudo guardar el ticket', false);
    return;
  }

  toast(data.mensaje ?? 'Ticket guardado correctamente', true);

  cerrarModal();
  await cargarCatalogos();
  await cargar();
}

async function eliminar(id, numeroCaso) {
  if (!confirm(`¿Eliminar el ticket "${numeroCaso}"?`)) {
    return;
  }

  const res = await fetch(`${API_TICKETS}?id=${id}`, {
    method: 'DELETE'
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo eliminar el ticket', false);
    return;
  }

  toast(data.mensaje ?? 'Ticket eliminado correctamente', true);
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