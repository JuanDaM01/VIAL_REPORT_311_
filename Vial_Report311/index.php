<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>VialReport311</title>
  <link rel="stylesheet" href="assets/css/style.css"/>
</head>
<body>

<?php
$current = 'index';
include 'pages/navbar.php';
?>

<div class="hero">
  <h1>Gestión de <span>Infraestructura Vial</span></h1>
  <p>Centraliza, organiza y da trazabilidad a los reportes ciudadanos de tu municipio en tiempo real.</p>

  <div style="display:flex;gap:1rem;margin-top:.5rem;flex-wrap:wrap;justify-content:center;">
    <a href="pages/reportes.php"   class="btn btn-primary">Ver Reportes</a>
    <a href="pages/usuarios.php"   class="btn btn-ghost">Ciudadanos</a>
    <a href="pages/tickets.php"    class="btn btn-ghost">🎫 Tickets</a>
    <a href="pages/categorias.php" class="btn btn-ghost">🏷 Categorías</a>
  </div>

  <div class="hero-cards">
    <div class="hero-card"><span class="ico">🕳️</span><span class="lbl">Huecos</span></div>
    <div class="hero-card"><span class="ico">🚦</span><span class="lbl">Semáforos</span></div>
    <div class="hero-card"><span class="ico">💡</span><span class="lbl">Alumbrado</span></div>
    <div class="hero-card"><span class="ico">🚧</span><span class="lbl">Señalización</span></div>
    <div class="hero-card"><span class="ico">🚶</span><span class="lbl">Andenes</span></div>
  </div>
</div>

</body>
</html>
