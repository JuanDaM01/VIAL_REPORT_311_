<?php require_once __DIR__ . '/../config/session.php'; requireRole(['administrador']); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Ubicaciones — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2>Gestión de <span>Ubicaciones</span></h2>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nueva ubicación</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Departamento</th>
          <th>Ciudad</th>
          <th>Barrio</th>
          <th>Dirección</th>
          <th>Coordenadas</th>
          <th>Reportes</th>
          <th>Proveedores</th>
          <th>Alertas</th>
          <th>Acciones</th>
        </tr>
      </thead>

      <tbody id="tbody">
        <tr class="empty-row">
          <td colspan="10">Cargando...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal CREATE / UPDATE -->
<div class="modal-overlay" id="modal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTit">Nueva Ubicación</h3>
      <button onclick="cerrarModal()">✕</button>
    </div>

    <div class="modal-body">
      <input type="hidden" id="uid"/>

      <div class="form-row">
        <div class="form-group">
          <label>Departamento</label>
          <input type="text" id="departamento" placeholder="Ej: Quindío"/>
        </div>

        <div class="form-group">
          <label>Ciudad *</label>
          <input type="text" id="ciudad" placeholder="Ej: Armenia"/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Barrio</label>
          <input type="text" id="barrio" placeholder="Ej: El Bosque"/>
        </div>

        <div class="form-group">
          <label>Dirección</label>
          <input type="text" id="direccionTexto" placeholder="Ej: Calle 10 # 15-30"/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Latitud</label>
          <input type="number" step="0.0000001" id="latitud" placeholder="Ej: 4.5339000"/>
        </div>

        <div class="form-group">
          <label>Longitud</label>
          <input type="number" step="0.0000001" id="longitud" placeholder="Ej: -75.6811000"/>
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
const API_UBICACIONES = '../api/ubicaciones.php';

function escapeHtml(valor) {
  if (valor === null || valor === undefined || valor === '') return '—';

  return String(valor)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function textoCoordenadas(u) {
  if (!u.latitud && !u.longitud) {
    return '—';
  }

  return `${u.latitud ?? '—'}, ${u.longitud ?? '—'}`;
}

async function cargar() {
  const res = await fetch(API_UBICACIONES);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!res.ok || data.error) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="10">Error al cargar ubicaciones.</td></tr>';
    toast(data.error ?? 'Error al cargar ubicaciones', false);
    return;
  }

  if (!data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="10">No hay ubicaciones registradas.</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(u => `
    <tr>
      <td>#${u.idUbicacion}</td>
      <td>${escapeHtml(u.departamento)}</td>
      <td><strong>${escapeHtml(u.ciudad)}</strong></td>
      <td>${escapeHtml(u.barrio)}</td>
      <td>${escapeHtml(u.direccionTexto)}</td>
      <td>${escapeHtml(textoCoordenadas(u))}</td>

      <td>
        <span class="badge badge-en_proceso">
          ${escapeHtml(u.totalReportes ?? 0)}
        </span>
      </td>

      <td>
        <span class="badge badge-resuelto">
          ${escapeHtml(u.totalProveedores ?? 0)}
        </span>
      </td>

      <td>
        <span class="badge badge-recibido">
          ${escapeHtml(u.totalAlertas ?? 0)}
        </span>
      </td>

      <td class="td-acc">
        <button class="btn-edit" onclick="editar(${u.idUbicacion})">✏ Editar</button>
        <button class="btn-del" onclick="eliminar(${u.idUbicacion}, '${escapeHtml(u.ciudad)}')">🗑 Eliminar</button>
      </td>
    </tr>
  `).join('');
}

function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nueva Ubicación';

  document.getElementById('uid').value = '';
  document.getElementById('departamento').value = '';
  document.getElementById('ciudad').value = '';
  document.getElementById('barrio').value = '';
  document.getElementById('direccionTexto').value = '';
  document.getElementById('latitud').value = '';
  document.getElementById('longitud').value = '';

  document.getElementById('modal').classList.add('open');
}

async function editar(id) {
  const res = await fetch(`${API_UBICACIONES}?id=${id}`);
  const u = await res.json();

  if (!res.ok || u.error) {
    toast(u.error ?? 'No se pudo cargar la ubicación', false);
    return;
  }

  document.getElementById('modalTit').textContent = 'Editar Ubicación';

  document.getElementById('uid').value = u.idUbicacion;
  document.getElementById('departamento').value = u.departamento ?? '';
  document.getElementById('ciudad').value = u.ciudad ?? '';
  document.getElementById('barrio').value = u.barrio ?? '';
  document.getElementById('direccionTexto').value = u.direccionTexto ?? '';
  document.getElementById('latitud').value = u.latitud ?? '';
  document.getElementById('longitud').value = u.longitud ?? '';

  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

function construirBody() {
  return {
    departamento: document.getElementById('departamento').value.trim() || null,
    ciudad: document.getElementById('ciudad').value.trim(),
    barrio: document.getElementById('barrio').value.trim() || null,
    direccionTexto: document.getElementById('direccionTexto').value.trim() || null,
    latitud: document.getElementById('latitud').value || null,
    longitud: document.getElementById('longitud').value || null
  };
}

function validarFormulario(body) {
  if (!body.ciudad) {
    toast('La ciudad es obligatoria', false);
    return false;
  }

  return true;
}

async function guardar() {
  const id = document.getElementById('uid').value;
  const body = construirBody();

  if (!validarFormulario(body)) {
    return;
  }

  const url = id ? `${API_UBICACIONES}?id=${id}` : API_UBICACIONES;
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
    toast(data.error ?? 'No se pudo guardar la ubicación', false);
    return;
  }

  toast(data.mensaje ?? 'Ubicación guardada correctamente', true);

  cerrarModal();
  cargar();
}

async function eliminar(id, ciudad) {
  if (!confirm(`¿Eliminar la ubicación de "${ciudad}"?`)) {
    return;
  }

  const res = await fetch(`${API_UBICACIONES}?id=${id}`, {
    method: 'DELETE'
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo eliminar la ubicación', false);
    return;
  }

  toast(data.mensaje ?? 'Ubicación eliminada correctamente', true);
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

cargar();
</script>

</body>
</html>