
<?php

// todos los controladores lo llamanos aqui
require_once "controladores/plantilla.controlador.php";
require_once "controladores/usuarios.controlador.php";

require_once "controladores/categorias.controlador.php";
require_once "controladores/productos.controlador.php";
require_once "controladores/materiales.controlador.php";
require_once "controladores/herramientas.controlador.php";
require_once "controladores/movimientos.controlador.php";
require_once "controladores/catalogos.controlador.php";
require_once "controladores/pedidos.controlador.php";
require_once "controladores/pagos.controlador.php";
require_once "controladores/inventario.controlador.php";
require_once "controladores/compras.controlador.php";
require_once "controladores/reportes.controlador.php";
require_once "controladores/auditoria.controlador.php";
require_once "controladores/recuperacion.controlador.php";
// todosl os modelos lo lamanadmos aqui

require_once "modelos/usuarios.modelo.php";

require_once "modelos/categorias.modelo.php";
require_once "modelos/productos.modelo.php";
require_once "modelos/materiales.modelo.php";
require_once "modelos/herramientas.modelo.php";
require_once "modelos/movimientos.modelo.php";
require_once "modelos/catalogos.modelo.php";
require_once "modelos/pedidos.modelo.php";
require_once "modelos/pagos.modelo.php";
require_once "modelos/inventario.modelo.php";
require_once "modelos/compras.modelo.php";
require_once "modelos/reportes.modelo.php";
require_once "modelos/auditoria.modelo.php";
require_once "modelos/recuperacion.modelo.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_SESSION["iniciarSesion"]) &&
    $_SESSION["iniciarSesion"] === "ok"
) {
    $ruta = $_GET["ruta"] ?? "";
    if (!empty($_SESSION["requiereCambioPassword"]) && !in_array($ruta, array("cambiar-password", "salir"), true)) {
        header("Location: index.php?ruta=cambiar-password");
        exit;
    }
    if ($ruta === "categorias") {
        ControladorCategorias::ctrProcesarCategoria();
    } elseif (in_array($ruta, array("clientes", "proveedores", "tipos-material"), true)) {
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
    } elseif ($ruta === "productos") {
        ControladorProductos::ctrProcesarProducto();
    } elseif ($ruta === "inventario") {
        ControladorInventario::ctrProcesar();
    } elseif ($ruta === "compras") {
        ControladorCompras::ctrProcesar();
    } elseif ($ruta === "cambiar-password") {
        ControladorRecuperacion::ctrCambiarTemporal();
    }
}

// hacemos una instancia a controlador
$plantilla= new ControladorPlantilla();
$plantilla->crtPlantilla();

?>