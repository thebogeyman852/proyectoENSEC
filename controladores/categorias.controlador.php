<?php

class ControladorCategorias
{
    public static function ctrProcesarCategoria()
    {
        if (empty($_POST["accionCategoria"])) {
            return;
        }

        try {
            $token = $_POST["csrf_token"] ?? null;
            if (
                !is_string($token) ||
                !hash_equals((string) ($_SESSION["csrf_token"] ?? ""), $token)
            ) {
                throw new InvalidArgumentException("La sesión del formulario venció. Recarga la página e inténtalo otra vez.");
            }

            $accion = $_POST["accionCategoria"];
            if ($accion === "eliminar") {
                $id = filter_var($_POST["idcategoria"] ?? null, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1) {
                    throw new InvalidArgumentException("La categoría seleccionada no es válida.");
                }
                ModeloCategorias::mdlBorrarCategoria("categoria", $id);
            } elseif ($accion === "crear" || $accion === "editar") {
                $nombre = trim((string) ($_POST["nombre"] ?? ""));
                $descripcion = trim((string) ($_POST["descripcion"] ?? ""));
                if ($nombre === "" || mb_strlen($nombre) > 100 || mb_strlen($descripcion) > 65535) {
                    throw new InvalidArgumentException("El nombre es obligatorio (máximo 100 caracteres).");
                }

                if ($accion === "crear") {
                    ModeloCategorias::mdlIngresarCategoria("categoria", array(
                        "nombre" => $nombre,
                        "descripcion" => $descripcion
                    ));
                } else {
                    $id = filter_var($_POST["idcategoria"] ?? null, FILTER_VALIDATE_INT);
                    if ($id === false || $id < 1) {
                        throw new InvalidArgumentException("La categoría seleccionada no es válida.");
                    }
                    ModeloCategorias::mdlEditarCategoria("categoria", array(
                        "idcategoria" => $id,
                        "nombre" => $nombre,
                        "descripcion" => $descripcion
                    ));
                }
            } else {
                throw new InvalidArgumentException("La operación solicitada no es válida.");
            }

            self::redirigirCategoria("success", "Cambios guardados correctamente.");
        } catch (InvalidArgumentException $error) {
            self::redirigirCategoria("error", $error->getMessage());
        } catch (PDOException $error) {
            self::redirigirCategoria(
                "error",
                "No se pudo completar la operación. La categoría podría estar relacionada con productos."
            );
        }
    }

    public static function ctrMostrarCategorias($item, $valor)
    {
        return ModeloCategorias::mdlMostrarCategorias("categoria", $item, $valor);
    }

    private static function redirigirCategoria($tipo, $mensaje)
    {
        $_SESSION["mensaje"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=categorias");
        exit;
    }
}
