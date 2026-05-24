<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Reportes — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2>Gestión de <span>Reportes</span></h2>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nuevo reporte</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Título</th>
          <th>Categoría</th>
          <th>Estado</th>
          <th>Votos</th>
          <th>Tipo</th>
          <th>Ciudadano</th>
          <th>Ubicación</th>
          <th>Fecha</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr class="empty-row"><td colspan="10">Cargando...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal CREATE / UPDATE -->
<div class="modal-overlay" id="modal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTit">Nuevo Reporte</h3>
      <button onclick="cerrarModal()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="rid"/>
      <div class="form-group">
        <label>Título *</label>
        <input type="text" id="titulo" placeholder="Describe brevemente el problema"/>
      </div>
      <div class="form-group">
        <label>Descripción</label>
        <textarea id="descripcion" rows="3" placeholder="Detalla el problema, ubicación exacta, etc."></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Categoría *</label>
          <select id="categoria">
            <option value="hueco">🕳️ Hueco</option>
            <option value="señalizacion">🚧 Señalización</option>
            <option value="anden">🚶 Andén</option>
            <option value="mal_parqueo">🚗 Mal parqueo</option>
            <option value="semaforo">🚦 Semáforo</option>
            <option value="alumbrado">💡 Alumbrado</option>
            <option value="otro">📌 Otro</option>
          </select>
        </div>
        <div class="form-group">
          <label>Estado</label>
          <select id="estado">
            <option value="recibido">Recibido</option>
            <option value="en_proceso">En proceso</option>
            <option value="resuelto">Resuelto</option>
            <option value="rechazado">Rechazado</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>ID Ciudadano</label>
          <input type="number" id="idUsuario" placeholder="Ej: 1" min="1"/>
        </div>
        <div class="form-group">
          <label>ID Ubicación</label>
          <input type="number" id="idUbicacion" placeholder="Ej: 1" min="1"/>
        </div>
      </div>
      <div class="check-row">
        <input type="checkbox" id="esAnonimo"/>
        <label for="esAnonimo">Reporte anónimo (no vincula ciudadano)</label>
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
const API = '../api/reportes.php';

const CATEGORIAS = {
  hueco:        '🕳️ Hueco',
  señalizacion: '🚧 Señalización',
  anden:        '🚶 Andén',
  mal_parqueo:  '🚗 Mal parqueo',
  semaforo:     '🚦 Semáforo',
  alumbrado:    '💡 Alumbrado',
  otro:         '📌 Otro'
};

// ── Cargar tabla ──────────────────────────────────────────
async function cargar() {
  const res  = await fetch(API);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="10">No hay reportes registrados.</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(r => {
    const cat   = CATEGORIAS[r.categoria] ?? r.categoria;
    const fecha = (r.fechaCreacion ?? '').substring(0, 10);
    const tipo  = r.esAnonimo == 1
      ? '<span class="badge badge-anonimo">Anónimo</span>'
      : '<span class="badge badge-local">Registrado</span>';

    return `
    <tr>
      <td>#${r.idReporte}</td>
      <td><strong>${r.titulo}</strong></td>
      <td>${cat}</td>
      <td><span class="badge badge-${r.estado}">${r.estado.replace('_',' ')}</span></td>
      <td>👍 ${r.voto}</td>
      <td>${tipo}</td>
      <td>${r.nombreUsuario ?? '—'}</td>
      <td>${r.ubicacion ?? '—'}</td>
      <td>${fecha}</td>
      <td class="td-acc">
        <button class="btn-edit" onclick="editar(${r.idReporte})">✏ Editar</button>
        <button class="btn-del"  onclick="eliminar(${r.idReporte}, \`${r.titulo.replace(/`/g,"'")}\`)">🗑 Eliminar</button>
      </td>
    </tr>`;
  }).join('');
}

// ── Abrir modal vacío (crear) ─────────────────────────────
function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nuevo Reporte';
  document.getElementById('rid').value         = '';
  document.getElementById('titulo').value      = '';
  document.getElementById('descripcion').value = '';
  document.getElementById('categoria').value   = 'hueco';
  document.getElementById('estado').value      = 'recibido';
  document.getElementById('idUsuario').value   = '';
  document.getElementById('idUbicacion').value = '';
  document.getElementById('esAnonimo').checked = false;
  document.getElementById('modal').classList.add('open');
}

// ── Cargar datos y abrir modal (editar) ───────────────────
async function editar(id) {
  const res = await fetch(`${API}?id=${id}`);
  const r   = await res.json();

  document.getElementById('modalTit').textContent = 'Editar Reporte';
  document.getElementById('rid').value         = r.idReporte;
  document.getElementById('titulo').value      = r.titulo;
  document.getElementById('descripcion').value = r.descripcion ?? '';
  document.getElementById('categoria').value   = r.categoria;
  document.getElementById('estado').value      = r.estado;
  document.getElementById('idUsuario').value   = r.idUsuario ?? '';
  document.getElementById('idUbicacion').value = r.idUbicacion ?? '';
  document.getElementById('esAnonimo').checked = r.esAnonimo == 1;
  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

// ── Guardar (POST = crear / PUT = editar) ─────────────────
async function guardar() {
  const id = document.getElementById('rid').value;

  const body = {
    titulo:      document.getElementById('titulo').value.trim(),
    descripcion: document.getElementById('descripcion').value.trim(),
    categoria:   document.getElementById('categoria').value,
    estado:      document.getElementById('estado').value,
    idUsuario:   document.getElementById('idUsuario').value   || null,
    idUbicacion: document.getElementById('idUbicacion').value || null,
    esAnonimo:   document.getElementById('esAnonimo').checked ? 1 : 0,
  };

  const url    = id ? `${API}?id=${id}` : API;
  const method = id ? 'PUT' : 'POST';

  const res  = await fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) });
  const data = await res.json();

  toast(data.mensaje ?? data.error, res.ok && data.mensaje);
  if (res.ok && data.mensaje) { cerrarModal(); cargar(); }
}

// ── Eliminar ──────────────────────────────────────────────
async function eliminar(id, titulo) {
  if (!confirm(`¿Eliminar el reporte "${titulo}"?`)) return;
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
