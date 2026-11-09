 <nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    
    <!-- Perfil rápido en el menú lateral (opcional pero limpio) -->
    <li class="nav-item nav-profile">
      <a href="#" class="nav-link">
        <div class="profile-image">
          <?php
          if($_SESSION["foto"] != ""){
            echo '<img class="img-xs rounded-circle" src="'.$_SESSION["foto"].'" alt="profile image">';
          }else{
            echo '<img class="img-xs rounded-circle" src="vistas/img/usuarios/default/anonymous.png" alt="profile image">';
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

    <!-- OPCIÓN: REGISTRO DE MOVIMIENTOS -->
    <li class="nav-item">
      <a class="nav-link" href="index.php?ruta=movimientos">
        <i class="menu-icon mdi mdi-swap-horizontal"></i>
        <span class="menu-title">Inventario</span>
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