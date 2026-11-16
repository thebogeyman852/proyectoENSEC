<?php

class ControladorProductos
{
    public static function ctrMostrarProductos()
    {
        return ModeloProductos::mdlMostrarProductos();
    }

    public static function ctrProcesarProducto()
    {
        if (empty($_POST["accionProducto"])) {
            return;
        }

        $imagenSubida = null;
        try {
            $token = $_POST["csrf_token"] ?? null;
            if (
                !is_string($token) ||
                !hash_equals((string) ($_SESSION["csrf_token"] ?? ""), $token)
            ) {
                throw new InvalidArgumentException("La sesión del formulario venció. Recarga la página e inténtalo otra vez.");
            }
            $accion = $_POST["accionProducto"];
            if ($accion === "eliminar") {
                $idProducto = filter_var($_POST["idproducto"] ?? null, FILTER_VALIDATE_INT);
                if ($idProducto === false || $idProducto < 1) {
                    throw new InvalidArgumentException("El producto seleccionado no es válido.");
                }
                $productoActual = ModeloProductos::mdlObtenerProducto($idProducto);
                if (!$productoActual) {
                    throw new InvalidArgumentException("El producto seleccionado no existe.");
                }
                ModeloProductos::mdlEliminarProducto($idProducto);
                $imagenAnterior = $productoActual["imagen"];
                $mensaje = "Producto eliminado correctamente.";
            } elseif ($accion === "crear" || $accion === "editar") {
                $datos = self::validarProducto($_POST);
                if ($accion === "crear") {
                    $imagenSubida = self::guardarImagen($_FILES["imagenProducto"] ?? null);
                    $datos["imagen"] = $imagenSubida;
                    ModeloProductos::mdlCrearProducto($datos);
                    $imagenAnterior = null;
                    $mensaje = "Producto guardado correctamente.";
                } else {
                    $idProducto = filter_var($_POST["idproducto"] ?? null, FILTER_VALIDATE_INT);
                    if ($idProducto === false || $idProducto < 1) {
                        throw new InvalidArgumentException("El producto seleccionado no es válido.");
                    }
                    $productoActual = ModeloProductos::mdlObtenerProducto($idProducto);
                    if (!$productoActual) {
                        throw new InvalidArgumentException("El producto seleccionado no existe.");
                    }
                    $imagenAnterior = $productoActual["imagen"];
                    $imagenSubida = self::guardarImagen($_FILES["imagenProducto"] ?? null);
                    $datos["imagen"] = $imagenSubida !== null
                        ? $imagenSubida
                        : (!empty($_POST["eliminarImagen"]) ? null : $imagenAnterior);
                    ModeloProductos::mdlEditarProducto($idProducto, $datos);
                    $mensaje = "Producto guardado correctamente.";
                }
            } else {
                throw new InvalidArgumentException("La operación solicitada no es válida.");
            }

            if ($imagenSubida !== null && $imagenAnterior !== $imagenSubida) {
                $imagenAnteriorEliminada = self::eliminarArchivoImagen($imagenAnterior);
            } elseif ($accion === "eliminar" || ($accion === "editar" && !empty($_POST["eliminarImagen"]))) {
                $imagenAnteriorEliminada = self::eliminarArchivoImagen($imagenAnterior);
            } else {
                $imagenAnteriorEliminada = true;
            }
            if (!$imagenAnteriorEliminada) {
                $mensaje .= " No se pudo borrar el archivo de imagen anterior.";
            }
            self::redirigir("success", $mensaje);
        } catch (InvalidArgumentException $error) {
            self::eliminarArchivoImagen($imagenSubida);
            self::redirigir("error", $error->getMessage());
        } catch (PDOException $error) {
            self::eliminarArchivoImagen($imagenSubida);
            self::redirigir(
                "error",
                "No se pudo guardar el producto. Comprueba el código, la categoría y que no tenga pedidos asociados."
            );
        }
    }

    private static function validarProducto($entrada)
    {
        $idCategoria = filter_var($entrada["idcategoria"] ?? null, FILTER_VALIDATE_INT);
        $codigo = trim((string) ($entrada["codigo"] ?? ""));
        $descripcion = trim((string) ($entrada["descripcion"] ?? ""));
        $precio = trim((string) ($entrada["precio_venta"] ?? ""));
        if (
            $idCategoria === false || $idCategoria < 1 ||
            $codigo === "" || mb_strlen($codigo) > 50 ||
            $descripcion === "" || mb_strlen($descripcion) > 255 ||
            !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $precio) || (float) $precio <= 0
        ) {
            throw new InvalidArgumentException("Completa correctamente los datos obligatorios del producto.");
        }

        if (array_key_exists("imagen", $entrada)) {
            throw new InvalidArgumentException("La imagen se debe seleccionar desde el dispositivo.");
        }

        $datos = array(
            "idcategoria" => $idCategoria,
            "codigo" => $codigo,
            "descripcion" => $descripcion,
            "precio_venta" => number_format((float) $precio, 2, ".", "")
        );

        foreach (array("ancho", "alto", "profundidad", "largo", "espesor", "peso") as $dimension) {
            $valor = trim((string) ($entrada[$dimension] ?? ""));
            if ($valor === "") {
                $datos[$dimension] = null;
                continue;
            }
            if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $valor)) {
                throw new InvalidArgumentException("Las dimensiones deben ser números positivos con hasta dos decimales.");
            }
            $datos[$dimension] = number_format((float) $valor, 2, ".", "");
        }

        return $datos;
    }

    private static function guardarImagen($archivo)
    {
        if ($archivo === null || !is_array($archivo)) {
            return null;
        }
        if (!isset($archivo["error"]) || !is_int($archivo["error"])) {
            throw new InvalidArgumentException("El archivo de imagen no es válido.");
        }
        if ($archivo["error"] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($archivo["error"] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException("No se pudo recibir la imagen. Intenta seleccionarla nuevamente.");
        }
        if (empty($archivo["tmp_name"]) || !is_string($archivo["tmp_name"]) || !is_uploaded_file($archivo["tmp_name"])) {
            throw new InvalidArgumentException("El archivo de imagen recibido no es válido.");
        }
        $tamanoArchivo = filesize($archivo["tmp_name"]);
        if ($tamanoArchivo === false || $tamanoArchivo > 2 * 1024 * 1024) {
            throw new InvalidArgumentException("La imagen debe pesar 2 MB o menos.");
        }

        $informacionImagen = @getimagesize($archivo["tmp_name"]);
        if (!$informacionImagen || empty($informacionImagen["mime"])) {
            throw new InvalidArgumentException("El archivo seleccionado no es una imagen válida.");
        }
        $extensionesPermitidas = array(
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/gif" => "gif",
            "image/webp" => "webp"
        );
        if (!isset($extensionesPermitidas[$informacionImagen["mime"]])) {
            throw new InvalidArgumentException("Formato no permitido. Usa una imagen PNG, JPG, GIF o WEBP.");
        }

        $directorio = dirname(__DIR__) . DIRECTORY_SEPARATOR . "vistas" . DIRECTORY_SEPARATOR . "img"
            . DIRECTORY_SEPARATOR . "productos";
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            throw new InvalidArgumentException("No se pudo preparar la carpeta para guardar imágenes.");
        }

        $nombreArchivo = bin2hex(random_bytes(16)) . "." . $extensionesPermitidas[$informacionImagen["mime"]];
        $destino = $directorio . DIRECTORY_SEPARATOR . $nombreArchivo;
        if (!move_uploaded_file($archivo["tmp_name"], $destino)) {
            throw new InvalidArgumentException("No se pudo guardar la imagen seleccionada.");
        }

        return "vistas/img/productos/" . $nombreArchivo;
    }

    private static function eliminarArchivoImagen($ruta)
    {
        if (!is_string($ruta) || !preg_match('/^vistas\/img\/productos\/[a-f0-9]{32}\.(jpg|png|gif|webp)$/', $ruta)) {
            return true;
        }

        $archivo = dirname(__DIR__) . DIRECTORY_SEPARATOR
            . str_replace("/", DIRECTORY_SEPARATOR, $ruta);
        return !is_file($archivo) || unlink($archivo);
    }

    private static function redirigir($tipo, $mensaje)
    {
        $_SESSION["mensajeProducto"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=productos");
        exit;
    }
}
