
<?php

// todos los controladores lo llamanos aqui
require_once "controladores/plantilla.controlador.php";
require_once "controladores/usuarios.controlador.php";
require_once "controladores/productos.controlador.php";


// todosl os modelos lo lamanadmos aqui

require_once "modelos/usuarios.modelo.php";
require_once "modelos/producto.modelo.php";




// hacemos una instancia a controlador
$plantilla= new ControladorPlantilla();
$plantilla->crtPlantilla();

?>