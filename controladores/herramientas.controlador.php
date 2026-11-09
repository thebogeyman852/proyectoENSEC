<?php

class ControladorHerramientas
{
    public static function ctrMostrarHerramientas($idHerramienta = null)
    {
        return ModeloHerramientas::mdlMostrarHerramientas($idHerramienta);
    }

    public static function ctrProcesarHerramienta()
    {
        if (empty($_POST["accionHerramienta"])) {
            return;
        }

        $accion = $_POST["accionHerramienta"];
        $idHerramienta = filter_var($_POST["idherramienta"] ?? null, FILTER_VALIDATE_INT);

        try {
            if ($accion === "eliminar") {
                if ($idHerramienta === false || $idHerramienta < 1) {
                    throw new InvalidArgumentException("La herramienta seleccionada no es válida.");
                }
                ModeloHerramientas::mdlBorrarHerramienta($idHerramienta);
            } elseif ($accion === "crear" || $accion === "editar") {
                $datos = self::obtenerDatosValidos($_POST);
                if ($datos === false || ($accion === "editar" && ($idHerramienta === false || $idHerramienta < 1))) {
                    throw new InvalidArgumentException("Revisa los datos de la herramienta.");
                }

                if ($accion === "crear") {
                    ModeloHerramientas::mdlIngresarHerramienta($datos);
                } else {
                    $datos["idherramienta"] = $idHerramienta;
                    ModeloHerramientas::mdlEditarHerramienta($datos);
                }
            } else {
                throw new InvalidArgumentException("La operación solicitada no es válida.");
            }

            self::redirigir("success", "Herramienta guardada correctamente.");
        } catch (InvalidArgumentException $error) {
            self::redirigir("error", $error->getMessage());
        } catch (PDOException $error) {
            self::redirigir("error", "No se pudo guardar la herramienta.");
        }
    }

    private static function obtenerDatosValidos($entrada)
    {
        $nombre = trim((string) ($entrada["nombre"] ?? ""));
        $estado = trim((string) ($entrada["estado"] ?? ""));
        $stock = filter_var($entrada["stock"] ?? null, FILTER_VALIDATE_INT);

        if (
            $nombre === "" || mb_strlen($nombre) > 150 ||
            $estado === "" || mb_strlen($estado) > 50 ||
            $stock === false || $stock < 0
        ) {
            return false;
        }

        return array("nombre" => $nombre, "estado" => $estado, "stock" => $stock);
    }

    private static function redirigir($tipo, $mensaje)
    {
        $_SESSION["mensaje"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=herramientas");
        exit;
    }
}
