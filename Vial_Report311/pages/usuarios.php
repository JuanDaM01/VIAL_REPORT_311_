<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Ciudadanos — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2>Gestión de <span>Ciudadanos</span></h2>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nuevo ciudadano</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre completo</th>
          <th>Email</th>
          <th>Teléfono</th>
          <th>Edad</th>
          <th>Registro</th>
          <th>Estado</th>
          <th>Reportes</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr class="empty-row"><td colspan="9">Cargando...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal CREATE / UPDATE -->
<div class="modal-overlay" id="modal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTit">Nuevo Ciudadano</h3>
      <button onclick="cerrarModal()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="uid"/>
      <div class="form-row">
        <div class="form-group">
          <label>Nombres *</label>
          <input type="text" id="nombres" placeholder="Carlos"/>
        </div>
        <div class="form-group">
          <label>Primer apellido *</label>
          <input type="text" id="apellido_1" placeholder="Gómez"/>
        </div>
        <div class="form-group">
          <label>Segundo apellido</label>
          <input type="text" id="apellido_2" placeholder="Ruiz"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Email *</label>
          <input type="email" id="email" placeholder="correo@ejemplo.com"/>
        </div>
        <div class="form-group">
          <label>Contraseña *</label>
          <input type="password" id="contrasena" placeholder="••••••••"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Teléfono</label>
          <input type="text" id="telefono" placeholder="310 000 0000"/>
        </div>
        <div class="form-group">
          <label>Edad</label>
          <input type="number" id="edad" placeholder="25" min="1" max="120"/>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Tipo de registro</label>
          <select id="tipoRegistro">
            <option value="local">Local</option>
            <option value="google">Google</option>
            <option value="facebook">Facebook</option>
          </select>
        </div>
        <div class="form-group">
          <label>Cargo (si es funcionario)</label>
          <input type="text" id="cargo" placeholder="Inspector vial"/>
        </div>
      </div>
      <div class="check-row">
        <input type="checkbox" id="activo" checked/>
        <label for="activo">Usuario activo</label>
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
const API = '../api/usuarios.php';

// ── Cargar tabla ──────────────────────────────────────────
async function cargar() {
  const res  = await fetch(API);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="9">No hay ciudadanos registrados.</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(u => `
    <tr>
      <td>#${u.idUsuario}</td>
      <td><strong>${u.nombres} ${u.apellido_1}</strong></td>
      <td>${u.email}</td>
      <td>${u.telefono ?? '—'}</td>
      <td>${u.edad ?? '—'}</td>
      <td><span class="badge badge-${u.tipoRegistro}">${u.tipoRegistro}</span></td>
      <td><span class="badge badge-${u.activo == 1 ? 'activo' : 'inactivo'}">${u.activo == 1 ? 'Activo' : 'Inactivo'}</span></td>
      <td>${u.cantidadReportes}</td>
      <td class="td-acc">
        <button class="btn-edit" onclick="editar(${u.idUsuario})">✏ Editar</button>
        <button class="btn-del"  onclick="eliminar(${u.idUsuario}, '${u.nombres} ${u.apellido_1}')">🗑 Eliminar</button>
      </td>
    </tr>
  `).join('');
}

// ── Abrir modal vacío (crear) ─────────────────────────────
function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nuevo Ciudadano';
  ['uid','nombres','apellido_1','apellido_2','email','contrasena','telefono','edad','cargo']
    .forEach(id => document.getElementById(id).value = '');
  document.getElementById('tipoRegistro').value = 'local';
  document.getElementById('activo').checked = true;
  document.getElementById('modal').classList.add('open');
}

// ── Cargar datos y abrir modal (editar) ───────────────────
async function editar(id) {
  const res = await fetch(`${API}?id=${id}`);
  const u   = await res.json();

  document.getElementById('modalTit').textContent = 'Editar Ciudadano';
  document.getElementById('uid').value         = u.idUsuario;
  document.getElementById('nombres').value     = u.nombres;
  document.getElementById('apellido_1').value  = u.apellido_1;
  document.getElementById('apellido_2').value  = u.apellido_2 ?? '';
  document.getElementById('email').value       = u.email;
  document.getElementById('contrasena').value  = '';   // no se muestra la contraseña hasheada
  document.getElementById('telefono').value    = u.telefono ?? '';
  document.getElementById('edad').value        = u.edad ?? '';
  document.getElementById('tipoRegistro').value= u.tipoRegistro;
  document.getElementById('cargo').value       = u.cargo ?? '';
  document.getElementById('activo').checked    = u.activo == 1;
  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

// ── Guardar (POST = crear / PUT = editar) ─────────────────
async function guardar() {
  const id = document.getElementById('uid').value;

  const body = {
    nombres:      document.getElementById('nombres').value.trim(),
    apellido_1:   document.getElementById('apellido_1').value.trim(),
    apellido_2:   document.getElementById('apellido_2').value.trim(),
    email:        document.getElementById('email').value.trim(),
    contrasena:   document.getElementById('contrasena').value.trim(),
    telefono:     document.getElementById('telefono').value.trim(),
    edad:         document.getElementById('edad').value || null,
    tipoRegistro: document.getElementById('tipoRegistro').value,
    cargo:        document.getElementById('cargo').value.trim(),
    activo:       document.getElementById('activo').checked ? 1 : 0,
  };

  const url    = id ? `${API}?id=${id}` : API;
  const method = id ? 'PUT' : 'POST';

  const res  = await fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) });
  const data = await res.json();

  toast(data.mensaje ?? data.error, res.ok && data.mensaje);
  if (res.ok && data.mensaje) { cerrarModal(); cargar(); }
}

// ── Eliminar ──────────────────────────────────────────────
async function eliminar(id, nombre) {
  if (!confirm(`¿Eliminar al ciudadano "${nombre}"?`)) return;
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
