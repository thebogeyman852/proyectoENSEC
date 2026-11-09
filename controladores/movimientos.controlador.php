<?php

class ControladorMovimientos
{
    public static function ctrMostrarMovimientos()
    {
        return ModeloMovimientos::mdlMostrarMovimientos();
    }

    public static function ctrProcesarMovimiento()
    {
        if (!isset($_POST["tipoMovimiento"])) {
            return;
        }

        $idHerramienta = filter_var($_POST["idHerramienta"] ?? null, FILTER_VALIDATE_INT);
        $cantidad = filter_var($_POST["cantidadMovimiento"] ?? null, FILTER_VALIDATE_INT);
        $tipo = $_POST["tipoMovimiento"];
        if (
            $idHerramienta === false || $idHerramienta < 1 ||
            $cantidad === false || $cantidad < 1 ||
            !in_array($tipo, array("ENTRADA", "SALIDA"), true) ||
            empty($_SESSION["idusuario"])
        ) {
            self::redirigir("error", "Revisa los datos del movimiento.");
        }

        try {
            ModeloMovimientos::mdlIngresarMovimiento(array(
                "idusuario" => (int) $_SESSION["idusuario"],
                "idherramienta" => $idHerramienta,
                "tipo_movimiento" => $tipo,
                "cantidad" => $cantidad
            ));
            self::redirigir("success", "Movimiento registrado y stock actualizado.");
        } catch (InvalidArgumentException $error) {
            self::redirigir("error", $error->getMessage());
        } catch (PDOException $error) {
            self::redirigir("error", "No se pudo registrar el movimiento.");
        }
    }

    private static function redirigir($tipo, $mensaje)
    {
        $_SESSION["mensaje"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=movimientos");
        exit;
    }
}
