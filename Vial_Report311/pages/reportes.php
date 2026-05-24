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
          <th>Ticket</th>
          <th>Reporte</th>
          <th>Categoría</th>
          <th>Estado</th>
          <th>Votos</th>
          <th>Prioridad</th>
          <th>Proveedor</th>
          <th>Ciudadano</th>
          <th>Ubicación</th>
          <th>Fecha</th>
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
      <h3 id="modalTit">Nuevo Reporte</h3>
      <button onclick="cerrarModal()">✕</button>
    </div>

    <div class="modal-body">
      <input type="hidden" id="rid"/>

      <div class="form-group">
        <label>Título *</label>
        <input type="text" id="titulo" placeholder="Describe brevemente el problema vial"/>
      </div>

      <div class="form-group">
        <label>Descripción</label>
        <textarea id="descripcion" rows="3" placeholder="Detalle del problema, punto de referencia o información adicional"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Categoría *</label>
          <select id="idCategoria"></select>
        </div>

        <div class="form-group">
          <label>Ubicación *</label>
          <select id="idUbicacion"></select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Estado del reporte</label>
          <select id="estado">
            <option value="recibido">Recibido</option>
            <option value="en_proceso">En proceso</option>
            <option value="resuelto">Resuelto</option>
            <option value="rechazado">Rechazado</option>
          </select>
        </div>

        <div class="form-group">
          <label>Prioridad del ticket</label>
          <select id="prioridad">
            <option value="">Automática</option>
            <option value="baja">Baja</option>
            <option value="media">Media</option>
            <option value="alta">Alta</option>
            <option value="critica">Crítica</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Ciudadano</label>
          <select id="idUsuario"></select>
        </div>

        <div class="form-group">
          <label>Proveedor responsable</label>
          <select id="idProveedor">
            <option value="">Asignación automática</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Funcionario asignado</label>
          <select id="idFuncionario">
            <option value="">Asignación automática</option>
          </select>
        </div>

        <div class="form-group">
          <label>URL de evidencia</label>
          <input type="text" id="urlArchivo" placeholder="uploads/evidencias/foto.jpg"/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Tamaño evidencia (KB)</label>
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

      <div class="check-row">
        <input type="checkbox" id="esAnonimo" onchange="controlarAnonimo()"/>
        <label for="esAnonimo">Reporte anónimo</label>
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
const API_REPORTES = '../api/reportes.php';
const API_CATALOGOS = '../api/catalogos.php?recurso=todo';

let catalogos = {
  categorias: [],
  ubicaciones: [],
  ciudadanos: [],
  funcionarios: [],
  proveedores: []
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
  return String(fecha).substring(0, 10);
}

function textoEstado(estado) {
  if (!estado) return '—';
  return String(estado).replace('_', ' ');
}

function textoUbicacion(u) {
  const partes = [];

  if (u.barrio) partes.push(u.barrio);
  if (u.direccionTexto) partes.push(u.direccionTexto);
  if (u.ciudad) partes.push(u.ciudad);

  return partes.join(' - ');
}

function textoPersona(u) {
  const apellido2 = u.apellido_2 ? ' ' + u.apellido_2 : '';
  return `${u.nombres} ${u.apellido_1}${apellido2}`;
}

async function cargarCatalogos() {
  const res = await fetch(API_CATALOGOS);
  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudieron cargar los catálogos', false);
    return;
  }

  catalogos = data;

  llenarSelectCategorias();
  llenarSelectUbicaciones();
  llenarSelectCiudadanos();
  llenarSelectProveedores();
  llenarSelectFuncionarios();
}

function llenarSelectCategorias() {
  const select = document.getElementById('idCategoria');

  select.innerHTML = '<option value="">Seleccione una categoría</option>' +
    catalogos.categorias.map(c => `
      <option value="${c.idCategoria}">
        ${escapeHtml(c.nombre)}
      </option>
    `).join('');
}

function llenarSelectUbicaciones() {
  const select = document.getElementById('idUbicacion');

  select.innerHTML = '<option value="">Seleccione una ubicación</option>' +
    catalogos.ubicaciones.map(u => `
      <option value="${u.idUbicacion}">
        ${escapeHtml(textoUbicacion(u))}
      </option>
    `).join('');
}

function llenarSelectCiudadanos() {
  const select = document.getElementById('idUsuario');

  select.innerHTML = '<option value="">Seleccione un ciudadano</option>' +
    catalogos.ciudadanos.map(u => `
      <option value="${u.idUsuario}">
        ${escapeHtml(textoPersona(u))} - ${escapeHtml(u.email)}
      </option>
    `).join('');
}

function llenarSelectProveedores() {
  const select = document.getElementById('idProveedor');

  select.innerHTML = '<option value="">Asignación automática</option>' +
    catalogos.proveedores.map(p => `
      <option value="${p.idProveedor}">
        ${escapeHtml(p.nombreEntidad)}
      </option>
    `).join('');
}

function llenarSelectFuncionarios() {
  const select = document.getElementById('idFuncionario');

  select.innerHTML = '<option value="">Asignación automática</option>' +
    catalogos.funcionarios.map(f => `
      <option value="${f.idUsuario}">
        ${escapeHtml(textoPersona(f))} ${f.cargo ? '- ' + escapeHtml(f.cargo) : ''}
      </option>
    `).join('');
}

async function cargar() {
  const res = await fetch(API_REPORTES);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!res.ok || data.error) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="11">Error al cargar reportes.</td></tr>';
    toast(data.error ?? 'Error al cargar reportes', false);
    return;
  }

  if (!data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="11">No hay reportes registrados.</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(r => {
    const tipoUsuario = r.esAnonimo == 1
      ? '<span class="badge badge-anonimo">Anónimo</span>'
      : escapeHtml(r.nombreUsuario ?? '—');

    return `
      <tr>
        <td>
          <strong>${escapeHtml(r.numeroCaso ?? 'Sin ticket')}</strong>
          <br>
          <small>#${escapeHtml(r.idReporte)}</small>
        </td>

        <td>
          <strong>${escapeHtml(r.titulo)}</strong>
          <br>
          <small>${escapeHtml(r.descripcion ?? '')}</small>
        </td>

        <td>${escapeHtml(r.categoria)}</td>

        <td>
          <span class="badge badge-${escapeHtml(r.estado)}">
            ${escapeHtml(textoEstado(r.estado))}
          </span>
        </td>

        <td>
          <span class="badge badge-en_proceso">
            ${escapeHtml(r.voto ?? 0)}
          </span>
        </td>

        <td>
          <span class="badge badge-${escapeHtml(r.prioridad ?? 'media')}">
            ${escapeHtml(r.prioridad ?? 'media')}
          </span>
        </td>

        <td>${escapeHtml(r.proveedor ?? 'Sin asignar')}</td>
        <td>${tipoUsuario}</td>
        <td>${escapeHtml(r.ubicacion ?? '—')}</td>
        <td>${escapeHtml(formatearFecha(r.fechaCreacion))}</td>

        <td class="td-acc">
          <button class="btn-edit" onclick="editar(${r.idReporte})">✏ Editar</button>
          <button class="btn-del" onclick="eliminar(${r.idReporte}, '${escapeHtml(r.titulo)}')">🗑 Eliminar</button>
        </td>
      </tr>
    `;
  }).join('');
}

function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nuevo Reporte';

  document.getElementById('rid').value = '';
  document.getElementById('titulo').value = '';
  document.getElementById('descripcion').value = '';
  document.getElementById('idCategoria').value = '';
  document.getElementById('idUbicacion').value = '';
  document.getElementById('estado').value = 'recibido';
  document.getElementById('prioridad').value = '';
  document.getElementById('idUsuario').value = '';
  document.getElementById('idProveedor').value = '';
  document.getElementById('idFuncionario').value = '';
  document.getElementById('urlArchivo').value = '';
  document.getElementById('tamanoKb').value = '';
  document.getElementById('contenido').value = '';
  document.getElementById('esAnonimo').checked = false;

  controlarAnonimo();

  document.getElementById('modal').classList.add('open');
}

async function editar(id) {
  const res = await fetch(`${API_REPORTES}?id=${id}`);
  const r = await res.json();

  if (!res.ok || r.error) {
    toast(r.error ?? 'No se pudo cargar el reporte', false);
    return;
  }

  document.getElementById('modalTit').textContent = 'Editar Reporte';

  document.getElementById('rid').value = r.idReporte;
  document.getElementById('titulo').value = r.titulo ?? '';
  document.getElementById('descripcion').value = r.descripcion ?? '';
  document.getElementById('idCategoria').value = r.idCategoria ?? '';
  document.getElementById('idUbicacion').value = r.idUbicacion ?? '';
  document.getElementById('estado').value = r.estado ?? 'recibido';
  document.getElementById('prioridad').value = r.prioridad ?? '';
  document.getElementById('idUsuario').value = r.idUsuario ?? '';
  document.getElementById('idProveedor').value = r.idProveedor ?? '';
  document.getElementById('idFuncionario').value = r.idFuncionario ?? '';
  document.getElementById('urlArchivo').value = '';
  document.getElementById('tamanoKb').value = '';
  document.getElementById('contenido').value = '';

  document.getElementById('esAnonimo').checked = r.esAnonimo == 1;

  controlarAnonimo();

  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

function controlarAnonimo() {
  const esAnonimo = document.getElementById('esAnonimo').checked;
  const selectUsuario = document.getElementById('idUsuario');

  if (esAnonimo) {
    selectUsuario.value = '';
    selectUsuario.disabled = true;
  } else {
    selectUsuario.disabled = false;
  }
}

function validarFormulario(body) {
  if (!body.titulo) {
    toast('El título es obligatorio', false);
    return false;
  }

  if (!body.idCategoria) {
    toast('Debe seleccionar una categoría', false);
    return false;
  }

  if (!body.idUbicacion) {
    toast('Debe seleccionar una ubicación', false);
    return false;
  }

  if (body.esAnonimo === 0 && !body.idUsuario) {
    toast('Debe seleccionar un ciudadano o marcar el reporte como anónimo', false);
    return false;
  }

  return true;
}

async function guardar() {
  const id = document.getElementById('rid').value;
  const esAnonimo = document.getElementById('esAnonimo').checked ? 1 : 0;

  const body = {
    titulo: document.getElementById('titulo').value.trim(),
    descripcion: document.getElementById('descripcion').value.trim(),
    estado: document.getElementById('estado').value,
    esAnonimo: esAnonimo,

    idCategoria: document.getElementById('idCategoria').value
      ? Number(document.getElementById('idCategoria').value)
      : null,

    idUbicacion: document.getElementById('idUbicacion').value
      ? Number(document.getElementById('idUbicacion').value)
      : null,

    idUsuario: esAnonimo === 1
      ? null
      : (
          document.getElementById('idUsuario').value
            ? Number(document.getElementById('idUsuario').value)
            : null
        )
  };

  const prioridad = document.getElementById('prioridad').value;
  const idProveedor = document.getElementById('idProveedor').value;
  const idFuncionario = document.getElementById('idFuncionario').value;
  const urlArchivo = document.getElementById('urlArchivo').value.trim();
  const tamanoKb = document.getElementById('tamanoKb').value;
  const contenido = document.getElementById('contenido').value;

  if (prioridad) {
    body.prioridad = prioridad;
  }

  if (idProveedor) {
    body.idProveedor = Number(idProveedor);
  }

  if (idFuncionario) {
    body.idFuncionario = Number(idFuncionario);
  }

  if (urlArchivo) {
    body.urlArchivo = urlArchivo;
    body.tamanoKb = tamanoKb ? Number(tamanoKb) : null;
    body.contenido = contenido || null;
  }

  if (!validarFormulario(body)) {
    return;
  }

  const url = id ? `${API_REPORTES}?id=${id}` : API_REPORTES;
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
    toast(data.error ?? 'No se pudo guardar el reporte', false);
    return;
  }

  toast(data.mensaje ?? 'Reporte guardado correctamente', true);

  cerrarModal();
  cargar();
}

async function eliminar(id, titulo) {
  if (!confirm(`¿Eliminar el reporte "${titulo}"?`)) {
    return;
  }

  const res = await fetch(`${API_REPORTES}?id=${id}`, {
    method: 'DELETE'
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo eliminar el reporte', false);
    return;
  }

  toast(data.mensaje ?? 'Reporte eliminado correctamente', true);
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