<?php

class ControladorCategorias
{
    public static function ctrCrearCategoria()
    {
        if (!isset($_POST["nuevaCategoria"])) {
            return;
        }

        $nombre = trim((string) $_POST["nuevaCategoria"]);
        if ($nombre === "" || mb_strlen($nombre) > 100) {
            self::mostrarAviso("El nombre es obligatorio y no debe exceder 100 caracteres.");
            return;
        }

        try {
            $resultado = ModeloCategorias::mdlIngresarCategoria("categoria", array(
                "nombre" => $nombre,
                "descripcion" => trim((string) ($_POST["nuevaDescripcion"] ?? ""))
            ));
            self::mostrarAviso($resultado === "ok" ? "Categoría guardada correctamente." : "No se pudo guardar la categoría.");
        } catch (PDOException $error) {
            self::mostrarAviso("No se pudo guardar la categoría.");
        }
    }

    public static function ctrMostrarCategorias($item, $valor)
    {
        return ModeloCategorias::mdlMostrarCategorias("categoria", $item, $valor);
    }

    public static function ctrEditarCategoria()
    {
        if (!isset($_POST["editarCategoria"], $_POST["idCategoria"])) {
            return;
        }

        $nombre = trim((string) $_POST["editarCategoria"]);
        $idCategoria = filter_var($_POST["idCategoria"], FILTER_VALIDATE_INT);
        if ($nombre === "" || mb_strlen($nombre) > 100 || $idCategoria === false || $idCategoria < 1) {
            self::mostrarAviso("Revisa el nombre y la categoría seleccionada.");
            return;
        }

        try {
            $resultado = ModeloCategorias::mdlEditarCategoria("categoria", array(
                "idcategoria" => $idCategoria,
                "nombre" => $nombre,
                "descripcion" => trim((string) ($_POST["editarDescripcion"] ?? ""))
            ));
            self::mostrarAviso($resultado === "ok" ? "Categoría actualizada correctamente." : "No se pudo actualizar la categoría.");
        } catch (PDOException $error) {
            self::mostrarAviso("No se pudo actualizar la categoría.");
        }
    }

    public static function ctrBorrarCategoria()
    {
        if (!isset($_GET["idCategoria"])) {
            return;
        }

        $idCategoria = filter_var($_GET["idCategoria"], FILTER_VALIDATE_INT);
        if ($idCategoria === false || $idCategoria < 1) {
            self::mostrarAviso("La categoría seleccionada no es válida.");
            return;
        }

        try {
            ModeloCategorias::mdlBorrarCategoria("categoria", $idCategoria);
            self::mostrarAviso("Categoría eliminada correctamente.");
        } catch (PDOException $error) {
            self::mostrarAviso("La categoría no se puede borrar porque está relacionada con pedidos.");
        }
    }

    private static function mostrarAviso($mensaje)
    {
        echo '<script>alert(' . json_encode($mensaje, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . '); window.location = "index.php?ruta=categorias";</script>';
    }
}
