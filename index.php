
<?php

// todos los controladores lo llamanos aqui
require_once "controladores/plantilla.controlador.php";
require_once "controladores/usuarios.controlador.php";

require_once "controladores/categorias.controlador.php";
require_once "controladores/materiales.controlador.php";
require_once "controladores/herramientas.controlador.php";
require_once "controladores/movimientos.controlador.php";
require_once "controladores/catalogos.controlador.php";
require_once "controladores/pedidos.controlador.php";
require_once "controladores/pagos.controlador.php";
// todosl os modelos lo lamanadmos aqui

require_once "modelos/usuarios.modelo.php";

require_once "modelos/categorias.modelo.php";
require_once "modelos/materiales.modelo.php";
require_once "modelos/herramientas.modelo.php";
require_once "modelos/movimientos.modelo.php";
require_once "modelos/catalogos.modelo.php";
require_once "modelos/pedidos.modelo.php";
require_once "modelos/pagos.modelo.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_SESSION["iniciarSesion"]) &&
    $_SESSION["iniciarSesion"] === "ok"
) {
    $ruta = $_GET["ruta"] ?? "";
    if (in_array($ruta, array("clientes", "proveedores", "tipos-material"), true)) {
        ControladorCatalogos::ctrProcesar($ruta);
    } elseif ($ruta === "materiales") {
        ControladorMateriales::ctrProcesarMaterial();
    } elseif ($ruta === "herramientas") {
        ControladorHerramientas::ctrProcesarHerramienta();
    } elseif ($ruta === "movimientos") {
        ControladorMovimientos::ctrProcesarMovimiento();
    } elseif ($ruta === "pedidos") {
        ControladorPedidos::ctrProcesarPedido();
    } elseif ($ruta === "pagos") {
        ControladorPagos::ctrProcesarPago();
    } elseif ($ruta === "usuarios") {
        ControladorUsuarios::ctrProcesarUsuario();
    }
}

// hacemos una instancia a controlador
$plantilla= new ControladorPlantilla();
$plantilla->crtPlantilla();

?>