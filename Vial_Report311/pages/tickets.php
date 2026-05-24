<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Tickets — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2>Gestión de <span>Tickets</span></h2>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nuevo ticket</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Reporte</th>
          <th>Categoría</th>
          <th>Prioridad</th>
          <th>Estado</th>
          <th>Fecha asignación</th>
          <th>Fecha resolución</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr class="empty-row"><td colspan="8">Cargando...</td></tr>
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
        <div class="form-group" style="flex:2">
          <label>Reporte asociado *</label>
          <select id="idReporte">
            <option value="">— Seleccionar reporte —</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Prioridad *</label>
          <select id="prioridad">
            <option value="baja">🟢 Baja</option>
            <option value="media" selected>🟡 Media</option>
            <option value="alta">🟠 Alta</option>
            <option value="critica">🔴 Crítica</option>
          </select>
        </div>
        <div class="form-group">
          <label>Estado *</label>
          <select id="estado">
            <option value="abierto" selected>Abierto</option>
            <option value="en_proceso">En proceso</option>
            <option value="cerrado">Cerrado</option>
          </select>
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
const API         = '../api/tickets.php';
const API_REPORTES = '../api/reportes.php';

// ── Mapas de color para badges ────────────────────────────
const prioBadge = {
  baja:    'badge-resuelto',
  media:   'badge-en_proceso',
  alta:    'badge-warn2',
  critica: 'badge-rechazado',
};
const estadoBadge = {
  abierto:    'badge-recibido',
  en_proceso: 'badge-en_proceso',
  cerrado:    'badge-resuelto',
};
const prioLabel = { baja:'🟢 Baja', media:'🟡 Media', alta:'🟠 Alta', critica:'🔴 Crítica' };

// ── Cargar tabla ──────────────────────────────────────────
async function cargar() {
  const res  = await fetch(API);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="8">No hay tickets registrados.</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(t => `
    <tr>
      <td>#${t.idTicket}</td>
      <td><strong>#${t.idReporte}</strong> ${t.tituloReporte ?? '—'}</td>
      <td>${t.categoria ?? '—'}</td>
      <td><span class="badge ${prioBadge[t.prioridad] ?? 'badge-recibido'}">${prioLabel[t.prioridad] ?? t.prioridad}</span></td>
      <td><span class="badge ${estadoBadge[t.estado] ?? 'badge-recibido'}">${t.estado}</span></td>
      <td>${formatFecha(t.fechaAsignacion)}</td>
      <td>${t.fechaResolucion ? formatFecha(t.fechaResolucion) : '—'}</td>
      <td class="td-acc">
        <button class="btn-edit" onclick="editar(${t.idTicket})">✏ Editar</button>
        <button class="btn-del"  onclick="eliminar(${t.idTicket})">🗑 Eliminar</button>
      </td>
    </tr>
  `).join('');
}

function formatFecha(f) {
  if (!f) return '—';
  return new Date(f).toLocaleString('es-CO', { dateStyle:'short', timeStyle:'short' });
}

// ── Cargar select de reportes ─────────────────────────────
async function cargarReportes(seleccionado) {
  const res  = await fetch(API_REPORTES);
  const data = await res.json();
  const sel  = document.getElementById('idReporte');
  sel.innerHTML = '<option value="">— Seleccionar reporte —</option>' +
    data.map(r => `<option value="${r.idReporte}" ${r.idReporte == seleccionado ? 'selected' : ''}>
      #${r.idReporte} — ${r.titulo}
    </option>`).join('');
}

// ── Abrir modal vacío (crear) ─────────────────────────────
async function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nuevo Ticket';
  document.getElementById('tid').value = '';
  document.getElementById('prioridad').value = 'media';
  document.getElementById('estado').value = 'abierto';
  document.getElementById('fechaResolucion').value = '';
  await cargarReportes(null);
  document.getElementById('modal').classList.add('open');
}

// ── Cargar datos y abrir modal (editar) ───────────────────
async function editar(id) {
  const res = await fetch(`${API}?id=${id}`);
  const t   = await res.json();

  document.getElementById('modalTit').textContent = 'Editar Ticket';
  document.getElementById('tid').value      = t.idTicket;
  document.getElementById('prioridad').value = t.prioridad;
  document.getElementById('estado').value   = t.estado;
  document.getElementById('fechaResolucion').value =
    t.fechaResolucion ? t.fechaResolucion.replace(' ', 'T').substring(0,16) : '';

  await cargarReportes(t.idReporte);
  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

// ── Guardar (POST = crear / PUT = editar) ─────────────────
async function guardar() {
  const id = document.getElementById('tid').value;

  const body = {
    idReporte:       document.getElementById('idReporte').value,
    prioridad:       document.getElementById('prioridad').value,
    estado:          document.getElementById('estado').value,
    fechaResolucion: document.getElementById('fechaResolucion').value || null,
  };

  if (!body.idReporte) { toast('Debes seleccionar un reporte', false); return; }

  const url    = id ? `${API}?id=${id}` : API;
  const method = id ? 'PUT' : 'POST';

  const res  = await fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) });
  const data = await res.json();

  toast(data.mensaje ?? data.error, res.ok && data.mensaje);
  if (res.ok && data.mensaje) { cerrarModal(); cargar(); }
}

// ── Eliminar ──────────────────────────────────────────────
async function eliminar(id) {
  if (!confirm(`¿Eliminar el ticket #${id}?`)) return;
  const res  = await fetch(`${API}?id=${id}`, { method: 'DELETE' });
  const data = await res.json();
  toast(data.mensaje ?? data.error, res.ok && data.mensaje);
  if (res.ok && data.mensaje) cargar();
}

// ── Toast ─────────────────────────────────────────────────
function toast(msg, ok) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = ok ? 'show ok' : 'show err';
  setTimeout(() => t.className = '', 3000);
}

cargar();
</script>
</body>
</html>
