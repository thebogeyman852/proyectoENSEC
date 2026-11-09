<?php

class ControladorPagos
{
    public static function ctrMostrarPagos()
    {
        return ModeloPagos::mdlMostrarPagos();
    }

    public static function ctrMostrarPedidosConSaldo()
    {
        return ModeloPagos::mdlMostrarPedidosConSaldo();
    }

    public static function ctrProcesarPago()
    {
        if (empty($_POST["accionPago"])) {
            return;
        }

        $accion = $_POST["accionPago"];
        $idPago = filter_var($_POST["idpago"] ?? null, FILTER_VALIDATE_INT);

        try {
            if ($accion === "eliminar") {
                if ($idPago === false || $idPago < 1) {
                    throw new InvalidArgumentException("El pago seleccionado no es válido.");
                }
                ModeloPagos::mdlBorrarPago($idPago);
            } elseif ($accion === "crear" || $accion === "editar") {
                $idPedido = filter_var($_POST["idpedido"] ?? null, FILTER_VALIDATE_INT);
                $monto = trim((string) ($_POST["monto"] ?? ""));
                $metodo = trim((string) ($_POST["metodo_pago"] ?? ""));
                if (
                    ($accion === "crear" && ($idPedido === false || $idPedido < 1)) ||
                    ($accion === "editar" && ($idPago === false || $idPago < 1)) ||
                    !preg_match('/^\d{1,9}(?:\.\d{1,2})?$/', $monto) || (float) $monto <= 0 ||
                    $metodo === "" || mb_strlen($metodo) > 50
                ) {
                    throw new InvalidArgumentException("Revisa el monto y el método de pago.");
                }
                if ($accion === "crear") {
                    ModeloPagos::mdlIngresarPago(array(
                        "idpedido" => $idPedido,
                        "monto" => (float) $monto,
                        "metodo_pago" => $metodo
                    ));
                } else {
                    ModeloPagos::mdlEditarPago($idPago, array(
                        "monto" => (float) $monto,
                        "metodo_pago" => $metodo
                    ));
                }
            } else {
                throw new InvalidArgumentException("La operación solicitada no es válida.");
            }

            self::redirigir("success", "Pago guardado correctamente.");
        } catch (InvalidArgumentException $error) {
            self::redirigir("error", $error->getMessage());
        } catch (PDOException $error) {
            self::redirigir("error", "No se pudo guardar el pago.");
        }
    }

    private static function redirigir($tipo, $mensaje)
    {
        $_SESSION["mensaje"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=pagos");
        exit;
    }
}
