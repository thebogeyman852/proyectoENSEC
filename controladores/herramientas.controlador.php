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
            $token = $_POST["csrf_token"] ?? null;
            if (
                !is_string($token) ||
                !hash_equals((string) ($_SESSION["csrf_token"] ?? ""), $token)
            ) {
                throw new InvalidArgumentException("La sesión del formulario venció. Recarga la página e inténtalo otra vez.");
            }
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
            if ($error->getCode() === "23000" && (int) ($error->errorInfo[1] ?? 0) === 1062) {
                self::redirigir("error", "El código de herramienta ya está registrado.");
            }
            self::redirigir("error", "No se pudo guardar la herramienta.");
        }
    }

    private static function obtenerDatosValidos($entrada)
    {
        $codigoEntrada = $entrada["codigo"] ?? null;
        $codigo = is_string($codigoEntrada) ? trim($codigoEntrada) : "";
        $nombre = trim((string) ($entrada["nombre"] ?? ""));
        $estado = trim((string) ($entrada["estado"] ?? ""));

        if ($codigo === "") {
            throw new InvalidArgumentException("El código de herramienta es obligatorio.");
        }
        if (mb_strlen($codigo) > 50) {
            throw new InvalidArgumentException("El código no puede superar 50 caracteres.");
        }
        if (
            $nombre === "" || mb_strlen($nombre) > 150 ||
            $estado === "" || mb_strlen($estado) > 50
        ) {
            return false;
        }

        return array("codigo" => $codigo, "nombre" => $nombre, "estado" => $estado);
    }

    private static function redirigir($tipo, $mensaje)
    {
        $_SESSION["mensaje"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=herramientas");
        exit;
    }
}
