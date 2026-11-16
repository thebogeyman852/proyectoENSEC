<?php

class ControladorPedidos
{
    public static function ctrMostrarPedidos()
    {
        return ModeloPedidos::mdlMostrarPedidos();
    }

    public static function ctrProcesarPedido()
    {
        if (empty($_POST["accionPedido"])) {
            return;
        }

        try {
            $accion = $_POST["accionPedido"];
            $csrfEntrada = $_POST["csrf_token"] ?? null;
            $csrfSesion = $_SESSION["csrf_token"] ?? null;
            if (
                empty($_SESSION["idusuario"]) ||
                !is_string($accion) ||
                !is_string($csrfEntrada) ||
                !is_string($csrfSesion) ||
                $csrfSesion === "" ||
                !hash_equals($csrfSesion, $csrfEntrada)
            ) {
                throw new InvalidArgumentException("El formulario venció o la sesión no es válida. Recarga la página.");
            }
            $idPedido = filter_var($_POST["idpedido"] ?? null, FILTER_VALIDATE_INT);

            if ($accion === "consumir_material") {
                $idMaterial = filter_var($_POST["idmaterial"] ?? null, FILTER_VALIDATE_INT);
                $cantidadEntrada = $_POST["cantidad"] ?? "";
                $motivoEntrada = $_POST["motivo"] ?? "";
                $solicitudEntrada = $_POST["id_solicitud"] ?? "";
                $cantidad = is_string($cantidadEntrada) ? trim($cantidadEntrada) : "";
                $motivo = is_string($motivoEntrada) ? trim($motivoEntrada) : "";
                $idSolicitud = is_string($solicitudEntrada) ? $solicitudEntrada : "";
                if (
                    $idPedido === false || $idPedido < 1 ||
                    $idMaterial === false || $idMaterial < 1 ||
                    !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $cantidad) ||
                    (float) $cantidad <= 0 ||
                    $motivo === "" || mb_strlen($motivo) > 255 ||
                    !preg_match('/^[a-f0-9]{32}$/', $idSolicitud)
                ) {
                    throw new InvalidArgumentException("Revisa el material, la cantidad y el motivo.");
                }

                ModeloPedidos::mdlRegistrarConsumoMaterial(array(
                    "idpedido" => $idPedido,
                    "idmaterial" => $idMaterial,
                    "idusuario" => (int) $_SESSION["idusuario"],
                    "cantidad" => $cantidad,
                    "motivo" => $motivo,
                    "id_solicitud" => $idSolicitud
                ));
            } elseif ($accion === "crear" || $accion === "editar") {
                $idCliente = filter_var($_POST["idcliente"] ?? null, FILTER_VALIDATE_INT);
                if (
                    $idCliente === false || $idCliente < 1 ||
                    ($accion === "editar" && ($idPedido === false || $idPedido < 1))
                ) {
                    throw new InvalidArgumentException("Selecciona un cliente válido.");
                }

                $datos = array(
                    "idcliente" => $idCliente,
                    "detalles" => self::obtenerDetalles($_POST),
                    "materiales" => self::obtenerMateriales($_POST)
                );
                if ($accion === "crear") {
                    $datos["idusuario"] = (int) $_SESSION["idusuario"];
                    ModeloPedidos::mdlCrearPedido($datos);
                } else {
                    ModeloPedidos::mdlEditarPedido($idPedido, $datos);
                }
            } elseif ($accion === "estado") {
                $estado = $_POST["estado"] ?? "";
                $estadosPermitidos = array("PENDIENTE", "EN_PROCESO", "COMPLETADO", "CANCELADO");
                if ($idPedido === false || $idPedido < 1 || !in_array($estado, $estadosPermitidos, true)) {
                    throw new InvalidArgumentException("El estado o pedido seleccionado no es válido.");
                }
                ModeloPedidos::mdlCambiarEstado($idPedido, $estado);
            } elseif ($accion === "eliminar") {
                if ($idPedido === false || $idPedido < 1) {
                    throw new InvalidArgumentException("El pedido seleccionado no es válido.");
                }
                ModeloPedidos::mdlBorrarPedido($idPedido);
            } else {
                throw new InvalidArgumentException("La operación solicitada no es válida.");
            }

            self::redirigir(
                "success",
                $accion === "consumir_material"
                    ? "Consumo registrado; el stock y el kardex fueron actualizados."
                    : ($accion === "estado" && $estado === "CANCELADO"
                        ? "Pedido cancelado; los consumos reales fueron devueltos al inventario."
                    : "Pedido guardado correctamente."
                    )
            );
        } catch (InvalidArgumentException $error) {
            self::redirigir("error", $error->getMessage());
        } catch (PDOException $error) {
            self::redirigir("error", "No se pudo completar la operación. Verifica la base de datos y vuelve a intentarlo.");
        }
    }

    private static function obtenerDetalles($entrada)
    {
        $productos = $entrada["idproducto"] ?? array();
        $cantidades = $entrada["cantidad"] ?? array();
        $precios = $entrada["precio_unitario"] ?? array();
        if (!is_array($productos) || !is_array($cantidades) || !is_array($precios)) {
            throw new InvalidArgumentException("Los productos del pedido no son válidos.");
        }

        $detalles = array();
        foreach ($productos as $indice => $productoEntrada) {
            $producto = filter_var($productoEntrada, FILTER_VALIDATE_INT);
            $cantidad = filter_var($cantidades[$indice] ?? null, FILTER_VALIDATE_INT);
            $precio = trim((string) ($precios[$indice] ?? ""));
            if (($productoEntrada === "" || $productoEntrada === null) && $cantidad === false && $precio === "") {
                continue;
            }
            if (
                $producto === false || $producto < 1 || $cantidad === false || $cantidad < 1 || $cantidad > 2147483647 ||
                !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $precio) || (float) $precio <= 0
            ) {
                throw new InvalidArgumentException("Completa correctamente cada producto, cantidad y precio.");
            }

            $detalles[] = array(
                "idproducto" => $producto,
                "cantidad" => $cantidad,
                "precio_unitario" => (float) $precio
            );
        }

        if (!$detalles) {
            throw new InvalidArgumentException("Agrega al menos un producto al pedido.");
        }
        return $detalles;
    }

    private static function obtenerMateriales($entrada)
    {
        $ids = $entrada["idmaterial"] ?? array();
        $cantidades = $entrada["cantidad_usada"] ?? array();
        if (!is_array($ids) || !is_array($cantidades)) {
            throw new InvalidArgumentException("Los materiales usados no son válidos.");
        }

        $materiales = array();
        foreach ($ids as $indice => $idEntrada) {
            $cantidad = trim((string) ($cantidades[$indice] ?? ""));
            if (($idEntrada === "" || $idEntrada === null) && $cantidad === "") {
                continue;
            }

            $id = filter_var($idEntrada, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1 || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $cantidad) || (float) $cantidad <= 0) {
                throw new InvalidArgumentException("Completa correctamente cada material y cantidad usada.");
            }
            $materiales[] = array("idmaterial" => $id, "cantidad_usada" => (float) $cantidad);
        }
        return $materiales;
    }

    private static function redirigir($tipo, $mensaje)
    {
        $_SESSION["mensaje"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=pedidos");
        exit;
    }
}
