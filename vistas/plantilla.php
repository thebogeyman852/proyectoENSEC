<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Star Admin | Dashboard</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="vistas/assets/vendors/iconfonts/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vistas/assets/vendors/iconfonts/ionicons/dist/css/ionicons.css">
  <link rel="stylesheet" href="vistas/assets/vendors/iconfonts/flag-icon-css/css/flag-icon.min.css">
  <link rel="stylesheet" href="vistas/assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="vistas/assets/vendors/css/vendor.bundle.addons.css">
  <!-- inject:css -->
  <link rel="stylesheet" href="vistas/assets/css/shared/style.css">
  <!-- endinject -->
  <!-- Layout styles -->
  <link rel="stylesheet" href="vistas/assets/css/demo_1/style.css">
  <!-- End Layout styles -->
  <link rel="shortcut icon" href="vistas/assets/images/favicon.ico" />

</head>

<body>
<div class="container-scroller">
<?php
if(isset($_SESSION["iniciarSesion"]) && $_SESSION["iniciarSesion"] == "ok") {

    // 1. Navbar superior fija
    include "modulos/cabecera.php";
    
    // 2. Contenedor principal de página (¡Clave para Star Admin!)
    echo '<div class="container-fluid page-body-wrapper">';
    
    // Sidebar (Menú lateral)
    include "modulos/menu.php";
    
    // Enrutamiento del contenido central
   if (isset($_GET["ruta"])) {
    $rutas = array(
        "inicio", "usuarios", "categorias", "materiales", "herramientas",
        "movimientos", "clientes", "proveedores", "tipos-material",
        "pedidos", "pagos", "salir"
    );

    if (in_array($_GET["ruta"], $rutas, true)) {
        if (in_array($_GET["ruta"], array("clientes", "proveedores", "tipos-material"), true)) {
            include "vistas/modulos/catalogo.php";
        } else {
            include "vistas/modulos/" . $_GET["ruta"] . ".php";
        }
    } else {
        include "vistas/modulos/404.php";
    }
} else {
    include "vistas/modulos/inicio.php";
}
    echo '</div>'; // Cierre de page-body-wrapper
    
} else {
    include "modulos/login.php";
}
?>
</div> <!-- Cierre de container-scroller -->

<!-- plugins:js -->
<script src="vistas/assets/vendors/js/vendor.bundle.base.js"></script>
<script src="vistas/assets/vendors/js/vendor.bundle.addons.js"></script>
<!-- inject:js -->
<script src="vistas/assets/js/shared/off-canvas.js"></script>
<script src="vistas/assets/js/shared/misc.js"></script>
<!-- Custom js for this page-->
<script src="vistas/assets/js/demo_1/dashboard.js"></script>
<script src="vistas/assets/js/shared/jquery.cookie.js" type="text/javascript"></script>
<script src="vistas/js/plantilla.js"></script>

</body>
</html>
