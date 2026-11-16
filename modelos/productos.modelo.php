<?php

require_once "conexion.php";
require_once "auditoria.modelo.php";

class ModeloProductos
{
    public static function mdlMostrarProductos()
    {
        $stmt = Conexion::conectar()->query(
            "SELECT p.*, c.nombre AS categoria
             FROM productos p
             INNER JOIN categoria c ON c.idcategoria = p.idcategoria
             ORDER BY p.descripcion, p.idproducto"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlObtenerProducto($idProducto)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT * FROM productos WHERE idproducto = :idproducto"
        );
        $stmt->execute(array(":idproducto" => $idProducto));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function mdlCrearProducto($datos)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
        $stmt = $conexion->prepare(
            "INSERT INTO productos
                (idcategoria, codigo, descripcion, precio_venta, imagen, ancho, alto, profundidad, largo, espesor, peso)
             VALUES
                (:idcategoria, :codigo, :descripcion, :precio_venta, :imagen, :ancho, :alto, :profundidad, :largo, :espesor, :peso)"
        );
        $stmt->execute(self::parametros($datos));
        $idProducto = (int) $conexion->lastInsertId();
        ModeloAuditoria::mdlRegistrar($conexion, array(
            "id_usuario" => $_SESSION["idusuario"] ?? null,
            "accion" => "crear",
            "modulo" => "productos",
            "tabla" => "productos",
            "id_registro" => $idProducto,
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

    public static function mdlEditarProducto($idProducto, $datos)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
        $anterior = $conexion->prepare("SELECT * FROM productos WHERE idproducto = :id FOR UPDATE");
        $anterior->execute(array(":id" => $idProducto));
        $datosAnteriores = $anterior->fetch(PDO::FETCH_ASSOC);
        if (!$datosAnteriores) {
            throw new InvalidArgumentException("El producto seleccionado no existe.");
        }
        $parametros = self::parametros($datos);
        $parametros[":idproducto"] = $idProducto;
        $stmt = $conexion->prepare(
            "UPDATE productos SET
                idcategoria = :idcategoria,
                codigo = :codigo,
                descripcion = :descripcion,
                precio_venta = :precio_venta,
                imagen = :imagen,
                ancho = :ancho,
                alto = :alto,
                profundidad = :profundidad,
                largo = :largo,
                espesor = :espesor,
                peso = :peso
             WHERE idproducto = :idproducto"
        );
        $stmt->execute($parametros);
        ModeloAuditoria::mdlRegistrar($conexion, array(
            "id_usuario" => $_SESSION["idusuario"] ?? null,
            "accion" => "editar",
            "modulo" => "productos",
            "tabla" => "productos",
            "id_registro" => $idProducto,
            "anteriores" => $datosAnteriores,
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

    public static function mdlEliminarProducto($idProducto)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            $producto = $conexion->prepare("SELECT * FROM productos WHERE idproducto = :idproducto FOR UPDATE");
            $producto->execute(array(":idproducto" => $idProducto));
            $datosAnteriores = $producto->fetch(PDO::FETCH_ASSOC);
            if (!$datosAnteriores) {
                throw new InvalidArgumentException("El producto seleccionado no existe.");
            }
            $movimientos = $conexion->prepare(
                "SELECT COUNT(*) FROM kardex WHERE tipo_item = 'producto' AND id_item = :idproducto"
            );
            $movimientos->execute(array(":idproducto" => $idProducto));
            if ((int) $movimientos->fetchColumn() > 0) {
                throw new InvalidArgumentException("No se puede eliminar un producto con movimientos en el kardex.");
            }
            $stmt = $conexion->prepare("DELETE FROM productos WHERE idproducto = :idproducto");
            $stmt->execute(array(":idproducto" => $idProducto));
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $_SESSION["idusuario"] ?? null,
                "accion" => "eliminar",
                "modulo" => "productos",
                "tabla" => "productos",
                "id_registro" => $idProducto,
                "anteriores" => $datosAnteriores,
                "nuevos" => null
            ));
            $conexion->commit();
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
        return "ok";
    }

    private static function parametros($datos)
    {
        $parametros = array();
        foreach ($datos as $campo => $valor) {
            $parametros[":" . $campo] = $valor;
        }
        return $parametros;
    }
}
