<?php

class ControladorCompras
{
    public static function ctrListarCompras()
    {
        return ModeloCompras::mdlListarCompras();
    }

    public static function ctrDetalle($idCompra)
    {
        return ModeloCompras::mdlDetalleCompra($idCompra);
    }

    public static function ctrProcesar()
    {
        if (empty($_POST["accionCompra"])) {
            return;
        }
        try {
            $token = $_POST["csrf_token"] ?? null;
            if (!is_string($token) || !hash_equals((string) ($_SESSION["csrf_token"] ?? ""), $token)) {
                throw new InvalidArgumentException("El formulario venció. Recarga la página.");
            }
            if (!in_array($_SESSION["perfil"] ?? "", array("Administrador", "Especial"), true)) {
                throw new InvalidArgumentException("No tienes permiso para registrar compras.");
            }

            if ($_POST["accionCompra"] === "anular") {
                $idCompra = filter_var($_POST["id_compra"] ?? null, FILTER_VALIDATE_INT);
                $motivo = trim((string) ($_POST["motivo_anulacion"] ?? ""));
                if ($idCompra === false || $idCompra < 1 || $motivo === "" || mb_strlen($motivo) > 255) {
                    throw new InvalidArgumentException("Indica una compra válida y el motivo de anulación.");
                }
                ModeloCompras::mdlAnularCompra($idCompra, (int) $_SESSION["idusuario"], $motivo);
                self::redirigir("success", "Compra anulada y movimientos compensatorios registrados.");
            }

            if ($_POST["accionCompra"] !== "crear") {
                throw new InvalidArgumentException("La acción solicitada no es válida.");
            }
            $proveedor = filter_var($_POST["id_proveedor"] ?? null, FILTER_VALIDATE_INT);
            $fecha = self::fecha($_POST["fecha"] ?? "");
            $documentoEntrada = $_POST["nro_documento"] ?? "";
            $observacionEntrada = $_POST["observacion"] ?? "";
            $documento = is_string($documentoEntrada) ? trim($documentoEntrada) : "";
            $observacion = is_string($observacionEntrada) ? trim($observacionEntrada) : "";
            $solicitud = $_POST["id_solicitud"] ?? "";
            if (
                $proveedor === false || $proveedor < 1 || $fecha === false ||
                !is_string($solicitud) || !preg_match('/^[a-f0-9]{32}$/', $solicitud) ||
                mb_strlen($documento) > 50 || mb_strlen($observacion) > 255
            ) {
                throw new InvalidArgumentException("Revisa proveedor, fecha, documento y observación.");
            }

            $tipos = $_POST["tipo_item"] ?? array();
            $items = $_POST["id_item"] ?? array();
            $cantidades = $_POST["cantidad"] ?? array();
            $costos = $_POST["costo_unitario"] ?? array();
            if (!is_array($tipos) || !is_array($items) || !is_array($cantidades) || !is_array($costos)) {
                throw new InvalidArgumentException("El detalle de compra no tiene un formato válido.");
            }
            $filas = count($tipos);
            if ($filas < 1 || $filas > 50 || count($items) !== $filas || count($cantidades) !== $filas || count($costos) !== $filas) {
                throw new InvalidArgumentException("Agrega entre 1 y 50 renglones completos.");
            }
            $detalles = array();
            for ($i = 0; $i < $filas; $i++) {
                $tipo = $tipos[$i] ?? "";
                $id = filter_var($items[$i] ?? null, FILTER_VALIDATE_INT);
                $cantidad = self::decimalCentimos($cantidades[$i] ?? "", false);
                $costo = self::decimalCentimos($costos[$i] ?? "", true);
                if (!in_array($tipo, array("material", "herramienta"), true) || $id === false || $id < 1) {
                    throw new InvalidArgumentException("Selecciona un artículo válido en cada renglón.");
                }
                $detalles[] = array(
                    "tipo_item" => $tipo,
                    "id_item" => $id,
                    "cantidad_centimos" => $cantidad,
                    "costo_centimos" => $costo
                );
            }

            $idCompra = ModeloCompras::mdlRegistrarCompra(array(
                "id_solicitud" => $solicitud,
                "id_proveedor" => $proveedor,
                "fecha" => $fecha,
                "nro_documento" => $documento === "" ? null : $documento,
                "observacion" => $observacion === "" ? null : $observacion,
                "id_usuario" => (int) $_SESSION["idusuario"],
                "detalles" => $detalles
            ));
            self::redirigir("success", "Compra #" . $idCompra . " registrada con sus entradas de inventario.");
        } catch (InvalidArgumentException $error) {
            self::redirigir("error", $error->getMessage());
        } catch (PDOException $error) {
            self::redirigir("error", "No se pudo guardar la compra; la transacción revirtió todos sus cambios.");
        } catch (RuntimeException $error) {
            self::redirigir("error", $error->getMessage());
        }
    }

    private static function fecha($valor)
    {
        if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return false;
        }
        $fecha = DateTime::createFromFormat("!Y-m-d", $valor);
        return $fecha && $fecha->format("Y-m-d") === $valor ? $valor : false;
    }

    private static function decimalCentimos($valor, $permitirCero)
    {
        if (!is_string($valor) && !is_numeric($valor)) {
            throw new InvalidArgumentException("Cantidad y costo deben ser valores numéricos.");
        }
        $valor = trim((string) $valor);
        if (!preg_match('/^\d{1,6}(?:\.\d{1,2})?$/', $valor)) {
            throw new InvalidArgumentException("Cantidad y costo deben tener hasta seis dígitos enteros y dos decimales.");
        }
        $partes = explode(".", $valor, 2);
        $centimos = (int) $partes[0] * 100 + (int) str_pad($partes[1] ?? "0", 2, "0");
        if (!$permitirCero && $centimos <= 0) {
            throw new InvalidArgumentException("La cantidad debe ser mayor que cero.");
        }
        return $centimos;
    }

    private static function redirigir($tipo, $mensaje)
    {
        $_SESSION["mensajeCompra"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=compras");
        exit;
    }
}
