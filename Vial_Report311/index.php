<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Dashboard — VialReport311</title>

  <link rel="stylesheet" href="assets/css/style.css"/>

  <style>
    .dashboard-grid{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
      gap:1rem;
      margin-top:1rem;
    }

    .stat-card{
      background:var(--card);
      border:1px solid var(--border);
      border-radius:16px;
      padding:1.2rem;
      box-shadow:0 10px 25px rgba(0,0,0,.06);
    }

    .stat-card h3{
      font-size:.9rem;
      margin:0;
      color:var(--muted);
      font-weight:600;
    }

    .stat-card .number{
      font-size:2rem;
      font-weight:800;
      margin-top:.5rem;
    }

    .dashboard-section{
      margin-top:2rem;
    }

    .dashboard-section h2{
      margin-bottom:1rem;
    }

    .mini-table{
      width:100%;
      border-collapse:collapse;
    }

    .mini-table th,
    .mini-table td{
      padding:.8rem;
      border-bottom:1px solid var(--border);
      text-align:left;
      font-size:.92rem;
    }

    .dashboard-columns{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:1rem;
      margin-top:1rem;
    }

    @media(max-width:900px){
      .dashboard-columns{
        grid-template-columns:1fr;
      }
    }
  </style>
</head>
<body>

<?php include 'pages/navbar.php'; ?>

<div class="page">

  <div class="page-header">
    <h2>Dashboard <span>VialReport311</span></h2>
  </div>

  <!-- ================================================= -->
  <!-- TARJETAS -->
  <!-- ================================================= -->

  <div class="dashboard-grid">

    <div class="stat-card">
      <h3>Usuarios</h3>
      <div class="number" id="usuarios">0</div>
    </div>

    <div class="stat-card">
      <h3>Reportes</h3>
      <div class="number" id="reportes">0</div>
    </div>

    <div class="stat-card">
      <h3>Tickets</h3>
      <div class="number" id="tickets">0</div>
    </div>

    <div class="stat-card">
      <h3>Proveedores</h3>
      <div class="number" id="proveedores">0</div>
    </div>

    <div class="stat-card">
      <h3>Categorías</h3>
      <div class="number" id="categorias">0</div>
    </div>

    <div class="stat-card">
      <h3>Ubicaciones</h3>
      <div class="number" id="ubicaciones">0</div>
    </div>

  </div>

  <!-- ================================================= -->
  <!-- COLUMNAS -->
  <!-- ================================================= -->

  <div class="dashboard-columns">

    <!-- ESTADOS -->
    <div class="dashboard-section stat-card">

      <h2>📋 Reportes por estado</h2>

      <table class="mini-table">
        <thead>
          <tr>
            <th>Estado</th>
            <th>Total</th>
          </tr>
        </thead>

        <tbody id="tablaEstados">
          <tr>
            <td colspan="2">Cargando...</td>
          </tr>
        </tbody>
      </table>

    </div>

    <!-- PRIORIDADES -->
    <div class="dashboard-section stat-card">

      <h2>🎫 Tickets por prioridad</h2>

      <table class="mini-table">
        <thead>
          <tr>
            <th>Prioridad</th>
            <th>Total</th>
          </tr>
        </thead>

        <tbody id="tablaPrioridades">
          <tr>
            <td colspan="2">Cargando...</td>
          </tr>
        </tbody>
      </table>

    </div>

  </div>

  <!-- ================================================= -->
  <!-- REPORTES MÁS VOTADOS -->
  <!-- ================================================= -->

  <div class="dashboard-section stat-card">

    <h2>👍 Reportes más votados</h2>

    <table class="mini-table">
      <thead>
        <tr>
          <th>Reporte</th>
          <th>Categoría</th>
          <th>Ubicación</th>
          <th>Estado</th>
          <th>Votos</th>
        </tr>
      </thead>

      <tbody id="tablaMasVotados">
        <tr>
          <td colspan="5">Cargando...</td>
        </tr>
      </tbody>
    </table>

  </div>

  <!-- ================================================= -->
  <!-- ÚLTIMOS REPORTES -->
  <!-- ================================================= -->

  <div class="dashboard-section stat-card">

    <h2>🕒 Últimos reportes</h2>

    <table class="mini-table">
      <thead>
        <tr>
          <th>Reporte</th>
          <th>Categoría</th>
          <th>Ubicación</th>
          <th>Estado</th>
          <th>Fecha</th>
        </tr>
      </thead>

      <tbody id="tablaUltimos">
        <tr>
          <td colspan="5">Cargando...</td>
        </tr>
      </tbody>
    </table>

  </div>

  <!-- ================================================= -->
  <!-- TOP PROVEEDORES -->
  <!-- ================================================= -->

  <div class="dashboard-section stat-card">

    <h2>🏢 Top proveedores</h2>

    <table class="mini-table">
      <thead>
        <tr>
          <th>Proveedor</th>
          <th>Tickets</th>
          <th>Soluciones</th>
        </tr>
      </thead>

      <tbody id="tablaProveedores">
        <tr>
          <td colspan="3">Cargando...</td>
        </tr>
      </tbody>
    </table>

  </div>

</div>

<script>
const API_DASHBOARD = 'api/dashboard.php';

function escapeHtml(valor) {
  if (valor === null || valor === undefined || valor === '') return '—';

  return String(valor)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function textoEstado(estado) {
  if (!estado) return '—';
  return String(estado).replace('_', ' ');
}

function formatearFecha(fecha) {
  if (!fecha) return '—';
  return String(fecha).substring(0, 10);
}

async function cargarDashboard() {

  const res = await fetch(API_DASHBOARD);
  const data = await res.json();

  if (!res.ok || data.error) {
    alert(data.error ?? 'Error al cargar dashboard');
    return;
  }

  // =====================================================
  // TOTALES
  // =====================================================

  document.getElementById('usuarios').textContent =
    data.totales.usuarios ?? 0;

  document.getElementById('reportes').textContent =
    data.totales.reportes ?? 0;

  document.getElementById('tickets').textContent =
    data.totales.tickets ?? 0;

  document.getElementById('proveedores').textContent =
    data.totales.proveedores ?? 0;

  document.getElementById('categorias').textContent =
    data.totales.categorias ?? 0;

  document.getElementById('ubicaciones').textContent =
    data.totales.ubicaciones ?? 0;

  // =====================================================
  // ESTADOS
  // =====================================================

  document.getElementById('tablaEstados').innerHTML =
    data.reportesPorEstado.map(e => `
      <tr>
        <td>${escapeHtml(textoEstado(e.estado))}</td>
        <td>${escapeHtml(e.total)}</td>
      </tr>
    `).join('');

  // =====================================================
  // PRIORIDADES
  // =====================================================

  document.getElementById('tablaPrioridades').innerHTML =
    data.ticketsPorPrioridad.map(p => `
      <tr>
        <td>${escapeHtml(p.prioridad)}</td>
        <td>${escapeHtml(p.total)}</td>
      </tr>
    `).join('');

  // =====================================================
  // MÁS VOTADOS
  // =====================================================

  document.getElementById('tablaMasVotados').innerHTML =
    data.reportesMasVotados.map(r => `
      <tr>
        <td>${escapeHtml(r.titulo)}</td>
        <td>${escapeHtml(r.categoria)}</td>
        <td>${escapeHtml(r.ubicacion)}</td>

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
      </tr>
    `).join('');

  // =====================================================
  // ÚLTIMOS
  // =====================================================

  document.getElementById('tablaUltimos').innerHTML =
    data.ultimosReportes.map(r => `
      <tr>
        <td>${escapeHtml(r.titulo)}</td>
        <td>${escapeHtml(r.categoria)}</td>
        <td>${escapeHtml(r.ubicacion)}</td>

        <td>
          <span class="badge badge-${escapeHtml(r.estado)}">
            ${escapeHtml(textoEstado(r.estado))}
          </span>
        </td>

        <td>${escapeHtml(formatearFecha(r.fechaCreacion))}</td>
      </tr>
    `).join('');

  // =====================================================
  // PROVEEDORES
  // =====================================================

  document.getElementById('tablaProveedores').innerHTML =
    data.topProveedores.map(p => `
      <tr>
        <td>${escapeHtml(p.nombreEntidad)}</td>
        <td>${escapeHtml(p.ticketsAsignados ?? 0)}</td>
        <td>${escapeHtml(p.solucionesResueltas ?? 0)}</td>
      </tr>
    `).join('');
}

cargarDashboard();
</script>

</body>
</html>