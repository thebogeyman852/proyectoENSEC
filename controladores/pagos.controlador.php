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
            $token = $_POST["csrf_token"] ?? null;
            $tokenSesion = $_SESSION["csrf_token"] ?? null;
            $idUsuario = filter_var($_SESSION["idusuario"] ?? null, FILTER_VALIDATE_INT);
            if (
                !is_string($token) ||
                !is_string($tokenSesion) ||
                $tokenSesion === "" ||
                !hash_equals($tokenSesion, $token) ||
                $idUsuario === false ||
                $idUsuario < 1
            ) {
                throw new InvalidArgumentException("La sesión del formulario venció. Recarga la página e inténtalo otra vez.");
            }
            if ($accion === "anular") {
                if ($idPago === false || $idPago < 1) {
                    throw new InvalidArgumentException("El pago seleccionado no es válido.");
                }
                $motivoEntrada = $_POST["motivo_anulacion"] ?? "";
                $motivo = is_string($motivoEntrada) ? trim($motivoEntrada) : "";
                if ($motivo === "" || mb_strlen($motivo) > 255) {
                    throw new InvalidArgumentException("Escribe el motivo de anulación (máximo 255 caracteres).");
                }
                ModeloPagos::mdlAnularPago($idPago, $idUsuario, $motivo);
            } elseif ($accion === "crear" || $accion === "editar") {
                $idPedido = filter_var($_POST["idpedido"] ?? null, FILTER_VALIDATE_INT);
                $montoEntrada = $_POST["monto"] ?? "";
                $metodoEntrada = $_POST["metodo_pago"] ?? "";
                $solicitudEntrada = $_POST["id_solicitud"] ?? "";
                $monto = is_string($montoEntrada) ? trim($montoEntrada) : "";
                $metodo = is_string($metodoEntrada) ? trim($metodoEntrada) : "";
                $solicitud = is_string($solicitudEntrada) ? $solicitudEntrada : "";
                if (
                    ($accion === "crear" && ($idPedido === false || $idPedido < 1)) ||
                    ($accion === "editar" && ($idPago === false || $idPago < 1)) ||
                    !preg_match('/^\d{1,9}(?:\.\d{1,2})?$/', $monto) || (float) $monto <= 0 ||
                    $metodo === "" || mb_strlen($metodo) > 50 ||
                    ($accion === "crear" && !preg_match('/^[a-f0-9]{32}$/', $solicitud))
                ) {
                    throw new InvalidArgumentException("Revisa el monto y el método de pago.");
                }
                if ($accion === "crear") {
                    ModeloPagos::mdlIngresarPago(array(
                        "idpedido" => $idPedido,
                        "monto" => $monto,
                        "metodo_pago" => $metodo,
                        "id_usuario" => $idUsuario,
                        "id_solicitud" => $solicitud
                    ));
                } else {
                    ModeloPagos::mdlEditarPago($idPago, array(
                        "monto" => $monto,
                        "metodo_pago" => $metodo,
                        "id_usuario" => $idUsuario
                    ));
                }
            } else {
                throw new InvalidArgumentException("La operación solicitada no es válida.");
            }

            self::redirigir(
                "success",
                $accion === "anular"
                    ? "Pago anulado; se conservó el historial y el saldo fue recalculado."
                    : "Pago guardado correctamente."
            );
        } catch (InvalidArgumentException $error) {
            self::redirigir("error", $error->getMessage());
        } catch (PDOException $error) {
            self::redirigir("error", "No se pudo guardar el pago.");
        } catch (RuntimeException $error) {
            self::redirigir("error", $error->getMessage());
        }
    }

    private static function redirigir($tipo, $mensaje)
    {
        $_SESSION["mensaje"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=pagos");
        exit;
    }
}
