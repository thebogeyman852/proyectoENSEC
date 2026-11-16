<?php

class ControladorInventario
{
    public static function ctrListarMovimientos($filtros = array())
    {
        return ModeloInventario::mdlListarMovimientos($filtros);
    }

    public static function ctrListarArticulos($tipo)
    {
        return ModeloInventario::mdlListarArticulos($tipo);
    }

    public static function ctrUsuarios()
    {
        return ModeloInventario::mdlUsuarios();
    }

    public static function ctrProcesar()
    {
        if (empty($_POST["accionInventario"])) {
            return;
        }
        try {
            $token = $_POST["csrf_token"] ?? null;
            if (!is_string($token) || !hash_equals((string) ($_SESSION["csrf_token"] ?? ""), $token)) {
                throw new InvalidArgumentException("El formulario venció. Recarga la página.");
            }
            if (!in_array($_SESSION["perfil"] ?? "", array("Administrador", "Especial"), true)) {
                throw new InvalidArgumentException("No tienes permiso para modificar inventario.");
            }
            $tipoItem = $_POST["tipo_item"] ?? "";
            $idItem = filter_var($_POST["id_item"] ?? null, FILTER_VALIDATE_INT);
            $tipoMovimiento = $_POST["tipo_movimiento"] ?? "";
            $motivoEntrada = $_POST["motivo"] ?? "";
            $motivo = is_string($motivoEntrada) ? trim($motivoEntrada) : "";
            if (
                !in_array($tipoItem, array("material", "herramienta"), true) ||
                $idItem === false || $idItem < 1 ||
                $motivo === "" || mb_strlen($motivo) > 255
            ) {
                throw new InvalidArgumentException("Revisa el artículo, el identificador y el motivo.");
            }
            $solicitud = self::idSolicitud($_POST["id_solicitud"] ?? "");
            $base = array(
                "tipo_item" => $tipoItem,
                "id_item" => $idItem,
                "id_usuario" => (int) $_SESSION["idusuario"],
                "id_solicitud" => $solicitud,
                "motivo" => $motivo
            );

            if ($_POST["accionInventario"] === "movimiento") {
                if (!in_array($tipoMovimiento, array("entrada", "salida"), true)) {
                    throw new InvalidArgumentException("Selecciona entrada o salida.");
                }
                $cantidad = self::decimal($_POST["cantidad"] ?? "");
                ModeloInventario::mdlRegistrarMovimiento($base + array(
                    "tipo_movimiento" => $tipoMovimiento,
                    "cantidad" => $cantidad,
                    "referencia_tipo" => "manual",
                    "referencia_id" => null
                ));
            } elseif ($_POST["accionInventario"] === "ajuste") {
                if (($_SESSION["perfil"] ?? "") !== "Administrador") {
                    throw new InvalidArgumentException("Solo el administrador puede registrar ajustes.");
                }
                $stockFisico = self::decimal($_POST["stock_fisico"] ?? "", true);
                ModeloInventario::mdlAjustarInventario($base + array("stock_fisico" => $stockFisico));
            } else {
                throw new InvalidArgumentException("La acción solicitada no es válida.");
            }
            self::redirigir("success", "Movimiento guardado en inventario y kardex.");
        } catch (InvalidArgumentException $error) {
            self::redirigir("error", $error->getMessage());
        } catch (PDOException $error) {
            self::redirigir("error", "No se pudo registrar el movimiento. No se actualizaron las existencias.");
        } catch (RuntimeException $error) {
            self::redirigir("error", $error->getMessage());
        }
    }

    private static function decimal($valor, $permitirCero = false)
    {
        if (!is_string($valor) && !is_numeric($valor)) {
            throw new InvalidArgumentException("Ingresa una cantidad numérica válida.");
        }
        $valor = trim((string) $valor);
        if (
            !preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $valor) ||
            (!$permitirCero && (float) $valor <= 0)
        ) {
            throw new InvalidArgumentException("Ingresa una cantidad válida con hasta dos decimales.");
        }
        return $valor;
    }

    private static function idSolicitud($valor)
    {
        if (!is_string($valor) || !preg_match('/^[a-f0-9]{32}$/', $valor)) {
            throw new InvalidArgumentException("La solicitud del formulario no es válida. Recarga la página.");
        }
        return $valor;
    }

    private static function redirigir($tipo, $mensaje)
    {
        $_SESSION["mensajeInventario"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=inventario");
        exit;
    }
}
