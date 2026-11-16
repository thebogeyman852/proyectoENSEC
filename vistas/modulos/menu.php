<?php
$fotoUsuario = trim((string) ($_SESSION["foto"] ?? ""));
$fotoUsuarioDisponible = $fotoUsuario !== "" && is_file($fotoUsuario);
?>
<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    
    <!-- Perfil rápido en el menú lateral (opcional pero limpio) -->
    <li class="nav-item nav-profile">
      <a href="#" class="nav-link">
        <div class="profile-image">
          <?php
          if ($fotoUsuarioDisponible) {
            echo '<img class="img-xs rounded-circle" src="' . htmlspecialchars($fotoUsuario, ENT_QUOTES, "UTF-8") . '" alt="Foto de perfil">';
          }else{
            echo '<i class="mdi mdi-account-circle" style="font-size:32px; color:#fff" aria-hidden="true"></i>';
          }
          ?>
          <div class="dot-indicator bg-success"></div>
        </div>
        <div class="text-wrapper">
          <p class="profile-name"><?php echo $_SESSION["nombre"]; ?></p>
          <p class="designation"><?php echo $_SESSION["perfil"]; ?></p>
        </div>
      </a>
    </li>

    <!-- ENCABEZADO DE SECCIÓN -->
    <li class="nav-item nav-category">Menú Principal</li>

    <!-- OPCIÓN: INICIO (DASHBOARD) -->
    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=inicio">
        <i class="menu-icon mdi mdi-television"></i>
        <span class="menu-title">Inicio</span>
      </a>
    </li>

    <!-- OPCIÓN: USUARIOS -->
    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=usuarios">
        <i class="menu-icon mdi mdi-account-multiple"></i>
        <span class="menu-title">Usuarios</span>
      </a>
    </li>

    <!-- OPCIÓN: CATEGORÍAS -->
    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=categorias">
        <i class="menu-icon mdi mdi-folder-outline"></i>
        <span class="menu-title">Categorías</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=productos">
        <i class="menu-icon mdi mdi-cube"></i>
        <span class="menu-title">Productos</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=materiales">
        <i class="menu-icon mdi mdi-cube-outline"></i>
        <span class="menu-title">Materiales</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=tipos-material">
        <i class="menu-icon mdi mdi-shape-outline"></i>
        <span class="menu-title">Tipos de material</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=proveedores">
        <i class="menu-icon mdi mdi-truck"></i>
        <span class="menu-title">Proveedores</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=clientes">
        <i class="menu-icon mdi mdi-account-box"></i>
        <span class="menu-title">Clientes</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=pedidos">
        <i class="menu-icon mdi mdi-clipboard-list"></i>
        <span class="menu-title">Pedidos</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=pagos">
        <i class="menu-icon mdi mdi-cash"></i>
        <span class="menu-title">Pagos</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=compras">
        <i class="menu-icon mdi mdi-cart"></i>
        <span class="menu-title">Compras</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=reportes">
        <i class="menu-icon mdi mdi-chart-bar"></i>
        <span class="menu-title">Reportes</span>
      </a>
    </li>
    <?php if (($_SESSION["perfil"] ?? "") === "Administrador") { ?>
    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=auditoria">
        <i class="menu-icon mdi mdi-clipboard-text"></i>
        <span class="menu-title">Auditoría</span>
      </a>
    </li>
    <?php } ?>

    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=inventario">
        <i class="menu-icon mdi mdi-swap-horizontal"></i>
        <span class="menu-title">Kardex e inventario</span>
      </a>
    </li>

    <!-- OPCIÓN: HERRAMIENTAS -->
    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=herramientas">
        <i class="menu-icon mdi mdi-tools"></i>
        <span class="menu-title">Herramientas</span>
      </a>
    </li>

  </ul>
</nav>