<?php

class ControladorArticulos {

    // ===============================================
    // MOSTRAR ARTÍCULOS
    // ===============================================
    static public function ctrMostrarArticulos($item, $valor) {
        $tabla = "articulo";
        $respuesta = ModeloArticulos::mdlMostrarArticulos($tabla, $item, $valor);
        return $respuesta;
    }

    // ===============================================
    // CREAR ARTÍCULO
    // ===============================================
    static public function ctrCrearArticulo() {
        if (isset($_POST["nuevoNombre"])) {
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nuevoNombre"])) {

                // Validar y procesar la imagen o archivo (modelo 3D)
                $ruta = "";
                if (isset($_FILES["nuevaImagen"]["tmp_name"]) && !empty($_FILES["nuevaImagen"]["tmp_name"])) {

                    // Crear directorio si no existe
                    $directorio = "vistas/img/productos";
                    if (!file_exists($directorio)) {
                        mkdir($directorio, 0755, true);
                    }

                    // Capturar tipo de archivo y generar nombre único
                    $aleatorio = mt_rand(100, 999);
                    $ruta = "vistas/img/productos/" . $aleatorio . "_" . $_FILES["nuevaImagen"]["name"];
                    
                    // Mover archivo al servidor
                    move_uploaded_file($_FILES["nuevaImagen"]["tmp_name"], $ruta);
                }

                $tabla = "articulo";
                $datos = array(
            "idcategoria" => $_POST["nuevaCategoria"],
            "codigo" => $_POST["nuevoCodigo"],
            "nombre" => $_POST["nuevoNombre"],
            "stock" => $_POST["nuevoStock"],
            "precio_venta" => $_POST["nuevoPrecioVenta"],
            "material" => $_POST["nuevoMaterial"],
            "tamano" => $_POST["nuevoTamano"],
            "proporcion" => $_POST["nuevaProporcion"],
            "profundidad" => $_POST["nuevaProfundidad"],
            "piezas" => $_POST["nuevasPiezas"],
            "acabado" => $_POST["nuevoAcabado"],
            "descripcion" => $_POST["nuevaDescripcion"],
            "imagen" => $ruta // O la ruta de la imagen procesada
        );

        $respuesta = ModeloArticulos::mdlIngresarArticulo("articulo", $datos);

        if($respuesta == "ok"){
            echo '<script>
                swal({
                    type: "success",
                    title: "¡El artículo ha sido guardado correctamente!",
                    showConfirmButton: true,
                    confirmButtonText: "Cerrar"
                }).then(function(result){
                    if (result.value) {
                        window.location = "articulos";
                    }
                });
            </script>';
        }
        }
        }
    }  
      static public function ctrEditarArticulo(){
        if(isset($_POST["editarCodigo"])){

            $ruta = $_POST["imagenActual"];

            // Si viene una nueva imagen local, puedes procesarla aquí o mantener la actual
            if(isset($_FILES["editarImagen"]["tmp_name"]) && !empty($_FILES["editarImagen"]["tmp_name"])){
                // Lógica de subida de imagen si lo requieres, o se queda con $ruta
            }

            $datos = array(
                "idarticulo" => $_POST["idArticulo"],
                "idcategoria" => $_POST["editarCategoria"],
                "codigo" => $_POST["editarCodigo"],
                "nombre" => $_POST["editarNombre"],
                "stock" => $_POST["editarStock"],
                "precio_venta" => $_POST["editarPrecioVenta"],
                "material" => $_POST["editarMaterial"],
                "tamano" => $_POST["editarTamano"],
                "proporcion" => $_POST["editarProporcion"],
                "profundidad" => $_POST["editarProfundidad"],
                "piezas" => $_POST["editarPiezas"],
                "acabado" => $_POST["editarAcabado"],
                "descripcion" => $_POST["editarDescripcion"],
                "imagen" => $ruta
            );

            $respuesta = ModeloArticulos::mdlActualizarArticulo("articulo", $datos);

            if($respuesta == "ok"){
                echo '<script>
                    swal({
                        type: "success",
                        title: "¡El artículo ha sido modificado correctamente!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then(function(result){
                        if (result.value) {
                            window.location = "articulos";
                        }
                    });
                </script>';
            }
        }
    }

    /* ===============================================
    BORRAR ARTÍCULO
    =============================================== */
    static public function ctrBorrarArticulo(){
        if(isset($_GET["idArticulo"])){
            $tabla = "articulo";
            $datos = $_GET["idArticulo"];

            $respuesta = ModeloArticulos::mdlBorrarArticulo($tabla, $datos);

            if($respuesta == "ok"){
                echo '<script>
                    swal({
                        type: "success",
                        title: "¡El artículo ha sido borrado correctamente!",
                        showConfirmButton: true,
                        confirmButtonText: "Cerrar"
                    }).then(function(result){
                        if (result.value) {
                            window.location = "articulos";
                        }
                    });
                </script>';
            }
        }
    }
}