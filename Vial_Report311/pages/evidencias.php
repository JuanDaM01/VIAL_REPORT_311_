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
  <title>Evidencias — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body class="role-<?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?>">

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2><?= ($rol === 'funcionario') ? 'Evidencias de <span>Mis Casos</span>' : 'Gestión de <span>Evidencias</span>' ?></h2>
    <?php if ($rol !== 'funcionario'): ?>
      <button class="btn btn-primary" onclick="abrirModal()">+ Nueva evidencia</button>
    <?php endif; ?>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Reporte</th>
          <th>Archivo</th>
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
        <div class="form-group" style="width: 100%;">
          <label>Tamaño en KB</label>
          <input type="number" id="tamanoKb" min="1" placeholder="Ej: 480"/>
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

function esImagen(url) {
  return /\.(jpg|jpeg|png|webp|gif)$/i.test(url);
}

async function cargar() {
  const res = await fetch(API_EVIDENCIAS);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  let evidenciasFiltradas = data;
  if (USER_ROLE === 'funcionario') {
    const misReportesIds = new Set(reportes.filter(r => r.idFuncionario == USER_ID).map(r => Number(r.idReporte)));
    evidenciasFiltradas = data.filter(e => misReportesIds.has(Number(e.idReporte)));
  }

  if (!evidenciasFiltradas.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="6">No hay evidencias registradas.</td></tr>';
    return;
  }

  tbody.innerHTML = evidenciasFiltradas.map(e => {
    const vista = esImagen(e.urlArchivo)
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

        <td>${escapeHtml(e.tamanoKb ? e.tamanoKb + ' KB' : '—')}</td>

        <td>${vista}</td>

        <td class="td-acc">
          <button class="btn-edit" onclick="editar(${e.idEvidencia})">${USER_ROLE === 'funcionario' ? '🔍 Ver Detalle' : '✏ Editar'}</button>
          ${USER_ROLE !== 'funcionario' ? `<button class="btn-del" onclick="eliminar(${e.idEvidencia})">🗑 Eliminar</button>` : ''}
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

  document.getElementById('modal').classList.add('open');
}

async function editar(id) {
  const res = await fetch(`${API_EVIDENCIAS}?id=${id}`);
  const e = await res.json();

  if (!res.ok || e.error) {
    toast(e.error ?? 'No se pudo cargar la evidencia', false);
    return;
  }

  document.getElementById('modalTit').textContent = USER_ROLE === 'funcionario' ? 'Detalle de Evidencia' : 'Editar Evidencia';

  document.getElementById('eid').value = e.idEvidencia;
  llenarReportes(e.idReporte);
  document.getElementById('urlArchivo').value = e.urlArchivo ?? '';
  document.getElementById('tamanoKb').value = e.tamanoKb ?? '';

  if (USER_ROLE === 'funcionario') {
    document.getElementById('idReporte').disabled = true;
    document.getElementById('urlArchivo').disabled = true;
    document.getElementById('tamanoKb').disabled = true;
    const saveBtn = document.querySelector('#modal .modal-foot .btn-primary');
    if (saveBtn) saveBtn.style.display = 'none';
  } else {
    document.getElementById('idReporte').disabled = false;
    document.getElementById('urlArchivo').disabled = false;
    document.getElementById('tamanoKb').disabled = false;
    const saveBtn = document.querySelector('#modal .modal-foot .btn-primary');
    if (saveBtn) saveBtn.style.display = 'inline-block';
  }

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
      : null
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