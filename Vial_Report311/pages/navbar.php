<?php
// pages/navbar.php — incluir en cada página
$current = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="navbar">
  <a class="brand" href="../index.php">⚠ VialReport<span>311</span></a>

  <div class="nav-links">
    <a href="../index.php" class="<?= $current === 'index' ? 'active' : '' ?>">Inicio</a>

    <a href="usuarios.php" class="<?= $current === 'usuarios' ? 'active' : '' ?>">
      👤 Usuarios
    </a>

    <a href="reportes.php" class="<?= $current === 'reportes' ? 'active' : '' ?>">
      📋 Reportes
    </a>

    <a href="tickets.php" class="<?= $current === 'tickets' ? 'active' : '' ?>">
      🎫 Tickets
    </a>

    <a href="categorias.php" class="<?= $current === 'categorias' ? 'active' : '' ?>">
      🏷 Categorías
    </a>

    <a href="notificaciones.php" class="<?= $current === 'notificaciones' ? 'active' : '' ?>">
      🔔 Notificaciones
    </a>
  </div>
</nav>