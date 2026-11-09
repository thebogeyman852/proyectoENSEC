<nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
  <!-- LOGOTIPO / MARCA -->
  <div class="text-center navbar-brand-wrapper d-flex align-items-top justify-content-center">
    <a class="navbar-brand brand-logo" href="index.php?ruta=inicio">
      <span class="font-weight-bold text-primary" style="font-size: 24px; letter-spacing: 1px;">INNOVA</span>
    </a>
    <a class="navbar-brand brand-logo-mini" href="index.php?ruta=inicio">
      <span class="font-weight-bold text-primary" style="font-size: 20px;">IN</span>
    </a>
  </div>
  
  <div class="navbar-menu-wrapper d-flex align-items-center">
    
    <!-- Se eliminó el texto de Help y la barra de búsqueda innecesaria -->
    <ul class="navbar-nav">
      <li class="nav-item font-weight-semibold d-none d-lg-block text-muted">
        Sistema de Control de Inventario
      </li>
    </ul>
    
    <!-- ZONA DERECHA: PERFIL DE USUARIO -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown d-none d-xl-inline-block user-dropdown">
        <a class="nav-link dropdown-toggle" id="UserDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
          <?php
          if(isset($_SESSION["foto"]) && $_SESSION["foto"] != ""){
            echo '<img class="img-xs rounded-circle" src="'.$_SESSION["foto"].'" alt="Profile image">';
          } else {
            echo '<img class="img-xs rounded-circle" src="vistas/img/usuarios/default/anonymous.png" alt="Profile image">';
          }
          ?>
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
          
          <!-- Encabezado del Dropdown con datos reales de la sesión -->
          <div class="dropdown-header text-center">
            <?php
            if(isset($_SESSION["foto"]) && $_SESSION["foto"] != ""){
              echo '<img class="img-md rounded-circle mb-2" src="'.$_SESSION["foto"].'" alt="Profile image">';
            } else {
              echo '<img class="img-md rounded-circle mb-2" src="vistas/img/usuarios/default/anonymous.png" alt="Profile image">';
            }
            ?>
            <p class="mb-1 mt-2 font-weight-semibold"><?php echo isset($_SESSION["nombre"]) ? $_SESSION["nombre"] : "Usuario"; ?></p>
            <p class="font-weight-light text-muted mb-0"><?php echo isset($_SESSION["perfil"]) ? $_SESSION["perfil"] : ""; ?></p>
          </div>
          
          <!-- Botón de Cerrar Sesión Integrado -->
          <div class="dropdown-divider"></div>
          <div class="p-2 text-center">
            <a href="index.php?ruta=salir" class="btn btn-primary btn-flat btn-block">Cerrar Sesión</a>
          </div>
          
        </div>
      </li>
    </ul>
    
    <!-- Botón responsive para móviles -->
    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
      <span class="mdi mdi-menu"></span>
    </button>
  </div>
</nav>