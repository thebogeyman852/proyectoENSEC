<?php

require_once "conexion.php";
require_once "auditoria.modelo.php";

class ModeloCategorias {

    static public function mdlIngresarCategoria($tabla, $datos) {
        self::validarTabla($tabla);
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
        $stmt = $conexion->prepare(
            "INSERT INTO categoria (nombre, descripcion) VALUES (:nombre, :descripcion)"
        );
        $stmt->execute(array(
            ":nombre" => $datos["nombre"],
            ":descripcion" => $datos["descripcion"]
        ));
        $id = (int) $conexion->lastInsertId();
        ModeloAuditoria::mdlRegistrar($conexion, array(
            "id_usuario" => $_SESSION["idusuario"] ?? null,
            "accion" => "crear",
            "modulo" => "categorias",
            "tabla" => "categoria",
            "id_registro" => $id,
            "anteriores" => null,
            "nuevos" => $datos
        ));
        $conexion->commit();
        return "ok";
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    static public function mdlMostrarCategorias($tabla, $item, $valor) {
        self::validarTabla($tabla);
        if ($item !== null) {
            if (!in_array($item, array("idcategoria", "nombre"), true)) {
                throw new InvalidArgumentException("Campo de búsqueda no válido.");
            }
            $stmt = Conexion::conectar()->prepare("SELECT * FROM categoria WHERE $item = :valor");
            $stmt->bindValue(":valor", $valor);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $stmt = Conexion::conectar()->query("SELECT * FROM categoria ORDER BY idcategoria DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlEditarCategoria($tabla, $datos) {
        self::validarTabla($tabla);
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
        $anterior = $conexion->prepare("SELECT nombre, descripcion FROM categoria WHERE idcategoria = :id FOR UPDATE");
        $anterior->execute(array(":id" => $datos["idcategoria"]));
        $datosAnteriores = $anterior->fetch(PDO::FETCH_ASSOC);
        if (!$datosAnteriores) {
            throw new InvalidArgumentException("La categoría seleccionada no existe.");
        }
        $stmt = $conexion->prepare(
            "UPDATE categoria SET nombre = :nombre, descripcion = :descripcion
             WHERE idcategoria = :idcategoria"
        );
        $stmt->execute(array(
            ":nombre" => $datos["nombre"],
            ":descripcion" => $datos["descripcion"],
            ":idcategoria" => $datos["idcategoria"]
        ));
        ModeloAuditoria::mdlRegistrar($conexion, array(
            "id_usuario" => $_SESSION["idusuario"] ?? null,
            "accion" => "editar",
            "modulo" => "categorias",
            "tabla" => "categoria",
            "id_registro" => $datos["idcategoria"],
            "anteriores" => $datosAnteriores,
            "nuevos" => array("nombre" => $datos["nombre"], "descripcion" => $datos["descripcion"])
        ));
        $conexion->commit();
        return "ok";
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    static public function mdlBorrarCategoria($tabla, $datos) {
        self::validarTabla($tabla);
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            $anterior = $conexion->prepare("SELECT nombre, descripcion FROM categoria WHERE idcategoria = :id FOR UPDATE");
            $anterior->execute(array(":id" => $datos));
            $registro = $anterior->fetch(PDO::FETCH_ASSOC);
            if (!$registro) {
                throw new InvalidArgumentException("La categoría seleccionada no existe.");
            }
            $stmt = $conexion->prepare("DELETE FROM categoria WHERE idcategoria = :idcategoria");
            $stmt->bindValue(":idcategoria", $datos, PDO::PARAM_INT);
            $stmt->execute();
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $_SESSION["idusuario"] ?? null,
                "accion" => "eliminar",
                "modulo" => "categorias",
                "tabla" => "categoria",
                "id_registro" => $datos,
                "anteriores" => $registro,
                "nuevos" => null
            ));
            $conexion->commit();
            return "ok";
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    private static function validarTabla($tabla)
    {
        if ($tabla !== "categoria") {
            throw new InvalidArgumentException("Tabla de categorías no válida.");
        }
    }
}