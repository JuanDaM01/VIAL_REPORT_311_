<?php require_once __DIR__ . '/../config/session.php'; requireRole(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Evidencias — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2>Gestión de <span>Evidencias</span></h2>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nueva evidencia</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Reporte</th>
          <th>Archivo</th>
          <th>Tipo</th>
          <th>Tamaño</th>
          <th>Vista</th>
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
      <h3 id="modalTit">Nueva Evidencia</h3>
      <button onclick="cerrarModal()">✕</button>
    </div>

    <div class="modal-body">
      <input type="hidden" id="eid"/>

      <div class="form-group">
        <label>Reporte asociado *</label>
        <select id="idReporte"></select>
      </div>

      <div class="form-group">
        <label>URL o ruta del archivo *</label>
        <input type="text" id="urlArchivo" placeholder="uploads/evidencias/foto.jpg"/>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Tamaño en KB</label>
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
    </div>

    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="cerrarModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="guardar()">Guardar</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const API_EVIDENCIAS = '../api/evidencias.php';
const API_REPORTES = '../api/reportes.php';

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

async function cargarReportes() {
  const res = await fetch(API_REPORTES);
  reportes = await res.json();

  llenarReportes();
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

function esImagen(contenido, url) {
  if (contenido && contenido.startsWith('image/')) {
    return true;
  }

  return /\.(jpg|jpeg|png|webp|gif)$/i.test(url);
}

async function cargar() {
  const res = await fetch(API_EVIDENCIAS);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!res.ok || data.error) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="7">Error al cargar evidencias.</td></tr>';
    toast(data.error ?? 'Error al cargar evidencias', false);
    return;
  }

  if (!data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="7">No hay evidencias registradas.</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(e => {
    const vista = esImagen(e.contenido, e.urlArchivo)
      ? `<img src="../${escapeHtml(e.urlArchivo)}" alt="Evidencia" style="width:72px;height:52px;object-fit:cover;border-radius:8px;border:1px solid var(--border);"/>`
      : `<a href="../${escapeHtml(e.urlArchivo)}" target="_blank">Abrir archivo</a>`;

    return `
      <tr>
        <td>#${e.idEvidencia}</td>

        <td>
          <strong>#${e.idReporte}</strong>
          <br>
          <small>${escapeHtml(e.reporte)}</small>
        </td>

        <td>
          <small>${escapeHtml(e.urlArchivo)}</small>
        </td>

        <td>
          <span class="badge badge-en_proceso">
            ${escapeHtml(e.contenido ?? 'archivo')}
          </span>
        </td>

        <td>${escapeHtml(e.tamanoKb ? e.tamanoKb + ' KB' : '—')}</td>

        <td>${vista}</td>

        <td class="td-acc">
          <button class="btn-edit" onclick="editar(${e.idEvidencia})">✏ Editar</button>
          <button class="btn-del" onclick="eliminar(${e.idEvidencia})">🗑 Eliminar</button>
        </td>
      </tr>
    `;
  }).join('');
}

function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nueva Evidencia';

  document.getElementById('eid').value = '';
  document.getElementById('idReporte').value = '';
  document.getElementById('urlArchivo').value = '';
  document.getElementById('tamanoKb').value = '';
  document.getElementById('contenido').value = '';

  document.getElementById('modal').classList.add('open');
}

async function editar(id) {
  const res = await fetch(`${API_EVIDENCIAS}?id=${id}`);
  const e = await res.json();

  if (!res.ok || e.error) {
    toast(e.error ?? 'No se pudo cargar la evidencia', false);
    return;
  }

  document.getElementById('modalTit').textContent = 'Editar Evidencia';

  document.getElementById('eid').value = e.idEvidencia;
  llenarReportes(e.idReporte);
  document.getElementById('urlArchivo').value = e.urlArchivo ?? '';
  document.getElementById('tamanoKb').value = e.tamanoKb ?? '';
  document.getElementById('contenido').value = e.contenido ?? '';

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

    urlArchivo: document.getElementById('urlArchivo').value.trim(),

    tamanoKb: document.getElementById('tamanoKb').value
      ? Number(document.getElementById('tamanoKb').value)
      : null,

    contenido: document.getElementById('contenido').value || null
  };
}

function validarFormulario(body) {
  if (!body.idReporte) {
    toast('Debe seleccionar el reporte asociado', false);
    return false;
  }

  if (!body.urlArchivo) {
    toast('Debe indicar la URL o ruta del archivo', false);
    return false;
  }

  return true;
}

async function guardar() {
  const id = document.getElementById('eid').value;
  const body = construirBody();

  if (!validarFormulario(body)) {
    return;
  }

  const url = id ? `${API_EVIDENCIAS}?id=${id}` : API_EVIDENCIAS;
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
    toast(data.error ?? 'No se pudo guardar la evidencia', false);
    return;
  }

  toast(data.mensaje ?? 'Evidencia guardada correctamente', true);

  cerrarModal();
  cargar();
}

async function eliminar(id) {
  if (!confirm(`¿Eliminar la evidencia #${id}?`)) {
    return;
  }

  const res = await fetch(`${API_EVIDENCIAS}?id=${id}`, {
    method: 'DELETE'
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo eliminar la evidencia', false);
    return;
  }

  toast(data.mensaje ?? 'Evidencia eliminada correctamente', true);
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
  await cargarReportes();
  await cargar();
}

iniciar();
</script>

</body>
</html>