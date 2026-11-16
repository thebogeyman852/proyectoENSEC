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
        $motivoEntrada = $_POST["motivo"] ?? "";
        $motivo = is_string($motivoEntrada) ? trim($motivoEntrada) : "";
        $solicitud = $_POST["id_solicitud"] ?? "";
        if (
            $idHerramienta === false || $idHerramienta < 1 ||
            $cantidad === false || $cantidad < 1 ||
            !in_array($tipo, array("ENTRADA", "SALIDA"), true) ||
            $motivo === "" || mb_strlen($motivo) > 255 ||
            !is_string($solicitud) || !preg_match('/^[a-f0-9]{32}$/', $solicitud) ||
            empty($_SESSION["idusuario"])
        ) {
            self::redirigir("error", "Revisa los datos del movimiento.");
        }

        try {
            $token = $_POST["csrf_token"] ?? null;
            if (
                !is_string($token) ||
                !hash_equals((string) ($_SESSION["csrf_token"] ?? ""), $token)
            ) {
                throw new InvalidArgumentException("La sesión del formulario venció. Recarga la página e inténtalo otra vez.");
            }
            ModeloMovimientos::mdlIngresarMovimiento(array(
                "idusuario" => (int) $_SESSION["idusuario"],
                "idherramienta" => $idHerramienta,
                "tipo_movimiento" => $tipo,
                "cantidad" => $cantidad,
                "motivo" => $motivo,
                "id_solicitud" => $solicitud
            ));
            self::redirigir("success", "Movimiento registrado y stock actualizado.");
        } catch (InvalidArgumentException $error) {
            self::redirigir("error", $error->getMessage());
        } catch (PDOException $error) {
            self::redirigir("error", "No se pudo registrar el movimiento.");
        } catch (RuntimeException $error) {
            self::redirigir("error", $error->getMessage());
        }
    }

    private static function redirigir($tipo, $mensaje)
    {
        $_SESSION["mensaje"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=movimientos");
        exit;
    }
}
