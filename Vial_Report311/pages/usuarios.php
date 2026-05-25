<?php require_once __DIR__ . '/../config/session.php'; requireRole(['administrador']); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Usuarios — VialReport311</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="page-header">
    <h2>Gestión de <span>Usuarios</span></h2>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nuevo usuario</button>
  </div>

  <div class="filters">
    <button class="btn btn-ghost" onclick="filtrarRol('')">Todos</button>
    <button class="btn btn-ghost" onclick="filtrarRol('ciudadano')">Ciudadanos</button>
    <button class="btn btn-ghost" onclick="filtrarRol('funcionario')">Funcionarios</button>
    <button class="btn btn-ghost" onclick="filtrarRol('administrador')">Administradores</button>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre completo</th>
          <th>Email</th>
          <th>Teléfono</th>
          <th>Rol</th>
          <th>Registro</th>
          <th>Estado</th>
          <th>Reportes</th>
          <th>Cargo (funcionario)</th>
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
      <h3 id="modalTit">Nuevo Usuario</h3>
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
          <label>Contraseña</label>
          <input type="password" id="contrasena" placeholder="Solo escribir si se desea cambiar"/>
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
          <label>Día nacimiento</label>
          <input type="number" id="fecha_nacimiento_dia" min="1" max="31" placeholder="15"/>
        </div>

        <div class="form-group">
          <label>Mes nacimiento</label>
          <input type="number" id="fecha_nacimiento_mes" min="1" max="12" placeholder="6"/>
        </div>

        <div class="form-group">
          <label>Año nacimiento</label>
          <input type="number" id="fecha_nacimiento_ano" min="1900" max="2100" placeholder="2000"/>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Rol *</label>
          <select id="rol" onchange="controlarRol()">
            <option value="ciudadano">Ciudadano</option>
            <option value="funcionario">Funcionario</option>
            <option value="administrador">Administrador</option>
          </select>
        </div>

        <div class="form-group">
          <label>Tipo de registro</label>
          <select id="tipoRegistro">
            <option value="local">Local</option>
            <option value="google">Google</option>
            <option value="facebook">Facebook</option>
          </select>
        </div>

        <div class="form-group">
          <label>Estado</label>
          <select id="activo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>

      <div id="bloqueFuncionario" class="form-row">
        <div class="form-group">
          <label>Cargo</label>
          <input type="text" id="cargo" placeholder="Inspector vial"/>
        </div>

        <div class="form-group">
          <label>Nivel de acceso</label>
          <input type="number" id="nivelAcceso" min="1" max="5" placeholder="1 a 5"/>
        </div>
      </div>

      <div id="bloqueAdministrador" class="form-row">
        <div class="form-group">
          <label>Estado de cuenta</label>
          <select id="estadoCuenta">
            <option value="">No aplica</option>
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
            <option value="suspendido">Suspendido</option>
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
const API = '../api/usuarios.php';
let rolActual = '';

function escapeHtml(valor) {
  if (valor === null || valor === undefined || valor === '') return '—';

  return String(valor)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function textoNombre(u) {
  const apellido2 = u.apellido_2 ? ' ' + u.apellido_2 : '';
  return `${u.nombres} ${u.apellido_1}${apellido2}`;
}

function textoCargo(u) {
  if (u.rol !== 'funcionario') {
    return '—';
  }

  const cargo = u.cargo ? u.cargo : 'Sin cargo';
  const nivel = u.nivelAcceso ? `Nivel ${u.nivelAcceso}` : 'Sin nivel';

  return `${cargo} / ${nivel}`;
}

async function cargar() {
  const url = rolActual ? `${API}?rol=${rolActual}` : API;
  const res = await fetch(url);
  const data = await res.json();
  const tbody = document.getElementById('tbody');

  if (!res.ok || data.error) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="10">Error al cargar usuarios.</td></tr>';
    toast(data.error ?? 'Error al cargar usuarios', false);
    return;
  }

  if (!data.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="10">No hay usuarios registrados.</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(u => `
    <tr>
      <td>#${u.idUsuario}</td>

      <td>
        <strong>${escapeHtml(textoNombre(u))}</strong>
        <br>
        <small>${escapeHtml(u.edad ? u.edad + ' años' : '')}</small>
      </td>

      <td>${escapeHtml(u.email)}</td>
      <td>${escapeHtml(u.telefono)}</td>

      <td>
        <span class="badge badge-${escapeHtml(u.rol)}">
          ${escapeHtml(u.rol)}
        </span>
      </td>

      <td>
        <span class="badge badge-${escapeHtml(u.tipoRegistro)}">
          ${escapeHtml(u.tipoRegistro)}
        </span>
      </td>

      <td>
        <span class="badge badge-${u.activo == 1 ? 'activo' : 'inactivo'}">
          ${u.activo == 1 ? 'Activo' : 'Inactivo'}
        </span>
      </td>

      <td>${u.cantidadReportes ?? 0}</td>

      <td>${escapeHtml(textoCargo(u))}</td>

      <td class="td-acc">
        <button class="btn-edit" onclick="editar(${u.idUsuario})">✏ Editar</button>
        <button class="btn-del" onclick="eliminar(${u.idUsuario}, '${escapeHtml(textoNombre(u))}')">🗑 Eliminar</button>
      </td>
    </tr>
  `).join('');
}

function filtrarRol(rol) {
  rolActual = rol;
  cargar();
}

function abrirModal() {
  document.getElementById('modalTit').textContent = 'Nuevo Usuario';

  limpiarFormulario();

  document.getElementById('rol').value = 'ciudadano';
  document.getElementById('tipoRegistro').value = 'local';
  document.getElementById('activo').value = '1';

  controlarRol();

  document.getElementById('modal').classList.add('open');
}

function limpiarFormulario() {
  [
    'uid',
    'nombres',
    'apellido_1',
    'apellido_2',
    'email',
    'contrasena',
    'telefono',
    'edad',
    'fecha_nacimiento_dia',
    'fecha_nacimiento_mes',
    'fecha_nacimiento_ano',
    'cargo',
    'nivelAcceso'
  ].forEach(id => {
    document.getElementById(id).value = '';
  });

  document.getElementById('estadoCuenta').value = '';
}

async function editar(id) {
  const res = await fetch(`${API}?id=${id}`);
  const u = await res.json();

  if (!res.ok || u.error) {
    toast(u.error ?? 'No se pudo cargar el usuario', false);
    return;
  }

  document.getElementById('modalTit').textContent = 'Editar Usuario';

  document.getElementById('uid').value = u.idUsuario;
  document.getElementById('nombres').value = u.nombres ?? '';
  document.getElementById('apellido_1').value = u.apellido_1 ?? '';
  document.getElementById('apellido_2').value = u.apellido_2 ?? '';
  document.getElementById('email').value = u.email ?? '';
  document.getElementById('contrasena').value = '';
  document.getElementById('telefono').value = u.telefono ?? '';
  document.getElementById('edad').value = u.edad ?? '';
  document.getElementById('fecha_nacimiento_dia').value = u.fecha_nacimiento_dia ?? '';
  document.getElementById('fecha_nacimiento_mes').value = u.fecha_nacimiento_mes ?? '';
  document.getElementById('fecha_nacimiento_ano').value = u.fecha_nacimiento_ano ?? '';
  document.getElementById('rol').value = u.rol ?? 'ciudadano';
  document.getElementById('tipoRegistro').value = u.tipoRegistro ?? 'local';
  document.getElementById('activo').value = u.activo == 1 ? '1' : '0';
  document.getElementById('cargo').value = u.cargo ?? '';
  document.getElementById('nivelAcceso').value = u.nivelAcceso ?? '';
  document.getElementById('estadoCuenta').value = u.estadoCuenta ?? '';

  controlarRol();

  document.getElementById('modal').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modal').classList.remove('open');
}

function controlarRol() {
  const rol = document.getElementById('rol').value;

  const bloqueFuncionario = document.getElementById('bloqueFuncionario');
  const bloqueAdministrador = document.getElementById('bloqueAdministrador');

  if (rol === 'ciudadano') {
    bloqueFuncionario.style.display = 'none';
    bloqueAdministrador.style.display = 'none';
    document.getElementById('cargo').value = '';
    document.getElementById('nivelAcceso').value = '';
    document.getElementById('estadoCuenta').value = '';
    return;
  }

  if (rol === 'funcionario') {
    bloqueFuncionario.style.display = 'flex';
    bloqueAdministrador.style.display = 'none';
    document.getElementById('estadoCuenta').value = '';
    return;
  }

  if (rol === 'administrador') {
    bloqueFuncionario.style.display = 'none';
    bloqueAdministrador.style.display = 'flex';
    document.getElementById('cargo').value = '';
    document.getElementById('nivelAcceso').value = '';

    if (!document.getElementById('estadoCuenta').value) {
      document.getElementById('estadoCuenta').value = 'activo';
    }
  }
}

function construirBody() {
  const id = document.getElementById('uid').value;
  const contrasena = document.getElementById('contrasena').value.trim();

  const body = {
    nombres: document.getElementById('nombres').value.trim(),
    apellido_1: document.getElementById('apellido_1').value.trim(),
    apellido_2: document.getElementById('apellido_2').value.trim() || null,
    email: document.getElementById('email').value.trim(),
    telefono: document.getElementById('telefono').value.trim() || null,
    edad: document.getElementById('edad').value
      ? Number(document.getElementById('edad').value)
      : null,

    fecha_nacimiento_dia: document.getElementById('fecha_nacimiento_dia').value
      ? Number(document.getElementById('fecha_nacimiento_dia').value)
      : null,

    fecha_nacimiento_mes: document.getElementById('fecha_nacimiento_mes').value
      ? Number(document.getElementById('fecha_nacimiento_mes').value)
      : null,

    fecha_nacimiento_ano: document.getElementById('fecha_nacimiento_ano').value
      ? Number(document.getElementById('fecha_nacimiento_ano').value)
      : null,

    rol: document.getElementById('rol').value,
    tipoRegistro: document.getElementById('tipoRegistro').value,
    activo: Number(document.getElementById('activo').value),

    estadoCuenta: document.getElementById('estadoCuenta').value || null
  };

  if (body.rol === 'funcionario') {
    body.cargo = document.getElementById('cargo').value.trim() || null;
    body.nivelAcceso = document.getElementById('nivelAcceso').value
      ? Number(document.getElementById('nivelAcceso').value)
      : null;
  }

  if (!id || contrasena) {
    body.contrasena = contrasena;
  }

  return body;
}

function validarFormulario(body) {
  if (!body.nombres) {
    toast('El nombre es obligatorio', false);
    return false;
  }

  if (!body.apellido_1) {
    toast('El primer apellido es obligatorio', false);
    return false;
  }

  if (!body.email) {
    toast('El email es obligatorio', false);
    return false;
  }

  if (!document.getElementById('uid').value && !body.contrasena) {
    toast('La contraseña es obligatoria para crear un usuario', false);
    return false;
  }

  if (body.rol === 'funcionario' && !body.cargo) {
    toast('El cargo es obligatorio para funcionarios', false);
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

  const url = id ? `${API}?id=${id}` : API;
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
    toast(data.error ?? 'No se pudo guardar el usuario', false);
    return;
  }

  toast(data.mensaje ?? 'Usuario guardado correctamente', true);

  cerrarModal();
  cargar();
}

async function eliminar(id, nombre) {
  if (!confirm(`¿Eliminar al usuario "${nombre}"?`)) {
    return;
  }

  const res = await fetch(`${API}?id=${id}`, {
    method: 'DELETE'
  });

  const data = await res.json();

  if (!res.ok || data.error) {
    toast(data.error ?? 'No se pudo eliminar el usuario', false);
    return;
  }

  toast(data.mensaje ?? 'Usuario eliminado correctamente', true);
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