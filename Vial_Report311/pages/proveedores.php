<?php require_once __DIR__ . '/../config/session.php'; requireRole(['administrador']); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Proveedores — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2>Gestión de <span>Proveedores</span></h2>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nuevo proveedor</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Entidad</th>
          <th>Contacto</th>
          <th>Nivel</th>
          <th>Soluciones</th>
          <th>Cobertura</th>
          <th>Tickets</th>
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

<!-- Modal CREATE / UPDATE -->
<div class="modal-overlay" id="modal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTit">Nuevo Proveedor</h3>
      <button onclick="cerrarModal()">✕</button>
    </div>

    <div class="modal-body">
      <input type="hidden" id="pid"/>

      <div class="form-group">
        <label>Nombre de la entidad *</label>
        <input type="text" id="nombreEntidad" placeholder="Ej: Secretaría de Obras Públicas"/>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Teléfono</label>
          <input type="text" id="telefono" placeholder="67400000"/>
        </div>

        <div class="form-group">
          <label>Correo</label>
          <input type="email" id="correo" placeholder="contacto@entidad.gov.co"/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Nivel</label>
          <input type="text" id="nivel" placeholder="municipal, departamental, privado"/>
        </div>

        <div class="form-group">
          <label>Soluciones resueltas</label>
          <input type="number" id="solucionesResueltas" min="0" value="0"/>
        </div>
      </div>

      <hr style="border:0;border-top:1px solid var(--border);margin:1rem 0;"/>

      <div class="page-header" style="padding:0;margin-bottom:.6rem;">
        <h3 style="font-size:1rem;">Cobertura por categoría y ubicación</h3>
        <button class="btn btn-ghost" type="button" onclick="agregarAsignacion()">+ Agregar cobertura</button>
      </div>

      <div id="asignaciones"></div>
    </div>

    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="cerrarModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="guardar()">Guardar</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const API_PROVEEDORES = '../api/proveedores.php';
const API_CATALOGOS = '../api/catalogos.php?recurso=todo';

let catalogos = {
  categorias: [],
  ubicaciones: []
};

let asignaciones = [];

function escapeHtml(valor) {
  if (valor === null || valor === undefined || valor === '') return '—';

  return String(valor)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function textoUbicacion(u) {
  const partes = [];

  if (u.barrio) partes.push(u.barrio);
  if (u.direccionTexto) partes.push(u.direccionTexto);
  if (u.ciudad) partes.push(u.ciudad);

  return partes.join(' - ');
}

async function cargarCatalogos() {
  const res = await fetch(API_CATALOGOS);
  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudieron cargar los catálogos', false);
    return;
  }

  catalogos.categorias = data.categorias;
  catalogos.ubicaciones = data.ubicaciones;
}

async function cargar() {
  const res = await fetch(API_PROVEEDORES);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!res.ok || data.error) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="8">Error al cargar proveedores.</td></tr>';
    toast(data.error ?? 'Error al cargar proveedores', false);
    return;
  }

  if (!data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="8">No hay proveedores registrados.</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(p => `
    <tr>
      <td>#${p.idProveedor}</td>

      <td>
        <strong>${escapeHtml(p.nombreEntidad)}</strong>
      </td>

      <td>
        <small>Tel: ${escapeHtml(p.telefono)}</small>
        <br>
        <small>Email: ${escapeHtml(p.correo)}</small>
      </td>

      <td>${escapeHtml(p.nivel)}</td>

      <td>
        <span class="badge badge-resuelto">
          ${escapeHtml(p.solucionesResueltas ?? 0)}
        </span>
      </td>

      <td>
        <small>${escapeHtml(p.totalCategorias ?? 0)} categoría(s)</small>
        <br>
        <small>${escapeHtml(p.totalUbicaciones ?? 0)} ubicación(es)</small>
      </td>

      <td>
        <span class="badge badge-en_proceso">
          ${escapeHtml(p.totalTickets ?? 0)}
        </span>
      </td>

      <td class="td-acc">
        <button class="btn-edit" onclick="editar(${p.idProveedor})">✏ Editar</button>
        <button class="btn-del" onclick="eliminar(${p.idProveedor}, '${escapeHtml(p.nombreEntidad)}')">🗑 Eliminar</button>
      </td>
    </tr>
  `).join('');
}

function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nuevo Proveedor';

  document.getElementById('pid').value = '';
  document.getElementById('nombreEntidad').value = '';
  document.getElementById('telefono').value = '';
  document.getElementById('correo').value = '';
  document.getElementById('nivel').value = '';
  document.getElementById('solucionesResueltas').value = 0;

  asignaciones = [];
  renderizarAsignaciones();

  document.getElementById('modal').classList.add('open');
}

async function editar(id) {
  const res = await fetch(`${API_PROVEEDORES}?id=${id}`);
  const p = await res.json();

  if (!res.ok || p.error) {
    toast(p.error ?? 'No se pudo cargar el proveedor', false);
    return;
  }

  document.getElementById('modalTit').textContent = 'Editar Proveedor';

  document.getElementById('pid').value = p.idProveedor;
  document.getElementById('nombreEntidad').value = p.nombreEntidad ?? '';
  document.getElementById('telefono').value = p.telefono ?? '';
  document.getElementById('correo').value = p.correo ?? '';
  document.getElementById('nivel').value = p.nivel ?? '';
  document.getElementById('solucionesResueltas').value = p.solucionesResueltas ?? 0;

  asignaciones = (p.asignaciones ?? []).map(a => ({
    idCategoria: Number(a.idCategoria),
    idUbicacion: Number(a.idUbicacion)
  }));

  renderizarAsignaciones();

  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

function agregarAsignacion() {
  asignaciones.push({
    idCategoria: '',
    idUbicacion: ''
  });

  renderizarAsignaciones();
}

function eliminarAsignacion(index) {
  asignaciones.splice(index, 1);
  renderizarAsignaciones();
}

function cambiarAsignacion(index, campo, valor) {
  asignaciones[index][campo] = valor ? Number(valor) : '';
}

function opcionesCategorias(seleccionado) {
  return '<option value="">Seleccione categoría</option>' +
    catalogos.categorias.map(c => `
      <option value="${c.idCategoria}" ${c.idCategoria == seleccionado ? 'selected' : ''}>
        ${escapeHtml(c.nombre)}
      </option>
    `).join('');
}

function opcionesUbicaciones(seleccionado) {
  return '<option value="">Seleccione ubicación</option>' +
    catalogos.ubicaciones.map(u => `
      <option value="${u.idUbicacion}" ${u.idUbicacion == seleccionado ? 'selected' : ''}>
        ${escapeHtml(textoUbicacion(u))}
      </option>
    `).join('');
}

function renderizarAsignaciones() {
  const contenedor = document.getElementById('asignaciones');

  if (!asignaciones.length) {
    contenedor.innerHTML = `
      <div class="empty-row" style="padding:1rem;text-align:center;">
        No hay cobertura configurada para este proveedor.
      </div>
    `;
    return;
  }

  contenedor.innerHTML = asignaciones.map((a, index) => `
    <div class="form-row" style="align-items:end;margin-bottom:.7rem;">
      <div class="form-group">
        <label>Categoría</label>
        <select onchange="cambiarAsignacion(${index}, 'idCategoria', this.value)">
          ${opcionesCategorias(a.idCategoria)}
        </select>
      </div>

      <div class="form-group">
        <label>Ubicación</label>
        <select onchange="cambiarAsignacion(${index}, 'idUbicacion', this.value)">
          ${opcionesUbicaciones(a.idUbicacion)}
        </select>
      </div>

      <button class="btn btn-ghost" type="button" onclick="eliminarAsignacion(${index})">
        Quitar
      </button>
    </div>
  `).join('');
}

function construirBody() {
  return {
    nombreEntidad: document.getElementById('nombreEntidad').value.trim(),
    telefono: document.getElementById('telefono').value.trim() || null,
    correo: document.getElementById('correo').value.trim() || null,
    nivel: document.getElementById('nivel').value.trim() || null,
    solucionesResueltas: document.getElementById('solucionesResueltas').value
      ? Number(document.getElementById('solucionesResueltas').value)
      : 0,

    asignaciones: asignaciones
      .filter(a => a.idCategoria && a.idUbicacion)
      .map(a => ({
        idCategoria: Number(a.idCategoria),
        idUbicacion: Number(a.idUbicacion)
      }))
  };
}

function validarFormulario(body) {
  if (!body.nombreEntidad) {
    toast('El nombre de la entidad es obligatorio', false);
    return false;
  }

  return true;
}

async function guardar() {
  const id = document.getElementById('pid').value;
  const body = construirBody();

  if (!validarFormulario(body)) {
    return;
  }

  const url = id ? `${API_PROVEEDORES}?id=${id}` : API_PROVEEDORES;
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
    toast(data.error ?? 'No se pudo guardar el proveedor', false);
    return;
  }

  toast(data.mensaje ?? 'Proveedor guardado correctamente', true);

  cerrarModal();
  cargar();
}

async function eliminar(id, nombre) {
  if (!confirm(`¿Eliminar el proveedor "${nombre}"?`)) {
    return;
  }

  const res = await fetch(`${API_PROVEEDORES}?id=${id}`, {
    method: 'DELETE'
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo eliminar el proveedor', false);
    return;
  }

  toast(data.mensaje ?? 'Proveedor eliminado correctamente', true);
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