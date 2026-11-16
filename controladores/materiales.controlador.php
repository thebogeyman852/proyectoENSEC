<?php

class ControladorMateriales
{
    public static function ctrMostrarMateriales($idMaterial = null)
    {
        return ModeloMateriales::mdlMostrarMateriales($idMaterial);
    }

    public static function ctrProcesarMaterial()
    {
        if (empty($_POST["accionMaterial"])) {
            return;
        }

        $accion = $_POST["accionMaterial"];
        $idMaterial = filter_var($_POST["idMaterial"] ?? null, FILTER_VALIDATE_INT);

        try {
            $token = $_POST["csrf_token"] ?? null;
            if (
                !is_string($token) ||
                !hash_equals((string) ($_SESSION["csrf_token"] ?? ""), $token)
            ) {
                throw new InvalidArgumentException("La sesión del formulario venció. Recarga la página e inténtalo otra vez.");
            }
            if ($accion === "eliminar") {
                if ($idMaterial === false || $idMaterial < 1) {
                    throw new InvalidArgumentException("El material seleccionado no es válido.");
                }
                ModeloMateriales::mdlBorrarMaterial($idMaterial);
            } elseif ($accion === "crear" || $accion === "editar") {
                $datos = self::obtenerDatosValidos($_POST);
                if ($datos === false || ($accion === "editar" && ($idMaterial === false || $idMaterial < 1))) {
                    throw new InvalidArgumentException("Revisa los datos del material.");
                }

                if ($accion === "crear") {
                    ModeloMateriales::mdlIngresarMaterial($datos);
                } else {
                    $datos["idmaterial"] = $idMaterial;
                    ModeloMateriales::mdlEditarMaterial($datos);
                }
            } else {
                throw new InvalidArgumentException("La operación solicitada no es válida.");
            }

            self::redirigir("success", "Material guardado correctamente.");
        } catch (InvalidArgumentException $error) {
            self::redirigir("error", $error->getMessage());
        } catch (PDOException $error) {
            self::redirigir("error", "No se pudo guardar. Comprueba las relaciones y que el material no tenga consumos registrados.");
        }
    }

    private static function obtenerDatosValidos($entrada)
    {
        $idTipo = filter_var($entrada["idtipo_material"] ?? null, FILTER_VALIDATE_INT);
        $idProveedor = filter_var($entrada["idproveedor"] ?? null, FILTER_VALIDATE_INT);
        $nombre = trim((string) ($entrada["nombre"] ?? ""));
        $unidad = trim((string) ($entrada["unidad_medida"] ?? ""));

        if (
            $idTipo === false || $idTipo < 1 || $idProveedor === false || $idProveedor < 1 ||
            $nombre === "" || mb_strlen($nombre) > 150 ||
            $unidad === "" || mb_strlen($unidad) > 50
        ) {
            return false;
        }

        return array(
            "idtipo_material" => $idTipo,
            "idproveedor" => $idProveedor,
            "nombre" => $nombre,
            "unidad_medida" => $unidad
        );
    }

    private static function redirigir($tipo, $mensaje)
    {
        $_SESSION["mensaje"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=materiales");
        exit;
    }
}
