<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Categorías — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2>Gestión de <span>Categorías</span></h2>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nueva categoría</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Reportes asociados</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr class="empty-row"><td colspan="5">Cargando...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal CREATE / UPDATE -->
<div class="modal-overlay" id="modal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTit">Nueva Categoría</h3>
      <button onclick="cerrarModal()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="cid"/>

      <div class="form-row">
        <div class="form-group" style="flex:2">
          <label>Nombre * <small style="color:var(--muted)">(sin espacios, en minúsculas)</small></label>
          <input type="text" id="nombre" placeholder="Ej: hueco, semaforo, anden"/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="flex:1">
          <label>Descripción</label>
          <textarea id="descripcion" rows="3"
            placeholder="Describe brevemente el tipo de problema vial..."
            style="resize:vertical;background:var(--surface2);color:var(--text);border:1px solid var(--border);
                   border-radius:8px;padding:.6rem .9rem;font-size:.88rem;width:100%;"></textarea>
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
const API = '../api/categorias.php';

// Íconos para categorías conocidas
const catIco = {
  hueco:        '🕳️',
  señalizacion: '🚧',
  anden:        '🚶',
  mal_parqueo:  '🚗',
  semaforo:     '🚦',
  alumbrado:    '💡',
  otro:         '📌',
};

// ── Cargar tabla ──────────────────────────────────────────
async function cargar() {
  const res  = await fetch(API);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="5">No hay categorías registradas.</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(c => {
    const ico = catIco[c.nombre] ?? '📂';
    return `
      <tr>
        <td>#${c.idCategoria}</td>
        <td><strong>${ico} ${c.nombre}</strong></td>
        <td>${c.descripcion ?? '<span style="color:var(--muted)">Sin descripción</span>'}</td>
        <td>
          <span class="badge badge-en_proceso" style="font-size:.8rem">${c.totalReportes} reporte${c.totalReportes != 1 ? 's' : ''}</span>
        </td>
        <td class="td-acc">
          <button class="btn-edit" onclick="editar(${c.idCategoria})">✏ Editar</button>
          <button class="btn-del"  onclick="eliminar(${c.idCategoria}, '${c.nombre}')">🗑 Eliminar</button>
        </td>
      </tr>
    `;
  }).join('');
}

// ── Abrir modal vacío (crear) ─────────────────────────────
function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nueva Categoría';
  document.getElementById('cid').value         = '';
  document.getElementById('nombre').value      = '';
  document.getElementById('descripcion').value = '';
  document.getElementById('modal').classList.add('open');
}

// ── Cargar datos y abrir modal (editar) ───────────────────
async function editar(id) {
  const res = await fetch(`${API}?id=${id}`);
  const c   = await res.json();

  document.getElementById('modalTit').textContent  = 'Editar Categoría';
  document.getElementById('cid').value         = c.idCategoria;
  document.getElementById('nombre').value      = c.nombre;
  document.getElementById('descripcion').value = c.descripcion ?? '';
  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

// ── Guardar (POST = crear / PUT = editar) ─────────────────
async function guardar() {
  const id = document.getElementById('cid').value;

  const body = {
    nombre:      document.getElementById('nombre').value.trim(),
    descripcion: document.getElementById('descripcion').value.trim(),
  };

  if (!body.nombre) { toast('El nombre es obligatorio', false); return; }

  const url    = id ? `${API}?id=${id}` : API;
  const method = id ? 'PUT' : 'POST';

  const res  = await fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) });
  const data = await res.json();

  toast(data.mensaje ?? data.error, res.ok && data.mensaje);
  if (res.ok && data.mensaje) { cerrarModal(); cargar(); }
}

// ── Eliminar ──────────────────────────────────────────────
async function eliminar(id, nombre) {
  if (!confirm(`¿Eliminar la categoría "${nombre}"?\n\nLos reportes asociados perderán su vínculo.`)) return;
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
