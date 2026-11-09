<?php

require_once "conexion.php";

class ModeloMateriales
{
    public static function mdlMostrarMateriales($idMaterial = null)
    {
        $sql = "SELECT m.*, tm.nombre AS tipo_material, p.nombre_proveedor
                FROM materiales m
                INNER JOIN tipo_material tm ON tm.idtipo_material = m.idtipo_material
                INNER JOIN proveedor p ON p.idproveedor = m.idproveedor";
        if ($idMaterial !== null) {
            $stmt = Conexion::conectar()->prepare($sql . " WHERE m.idmaterial = :idmaterial");
            $stmt->bindValue(":idmaterial", $idMaterial, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $stmt = Conexion::conectar()->query($sql . " ORDER BY m.nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlIngresarMaterial($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO materiales (idtipo_material, idproveedor, nombre, unidad_medida, stock_actual)
             VALUES (:idtipo_material, :idproveedor, :nombre, :unidad_medida, :stock_actual)"
        );
        self::vincularDatos($stmt, $datos);
        return $stmt->execute() ? "ok" : "error";
    }

    public static function mdlEditarMaterial($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE materiales
             SET idtipo_material = :idtipo_material, idproveedor = :idproveedor,
                 nombre = :nombre, unidad_medida = :unidad_medida, stock_actual = :stock_actual
             WHERE idmaterial = :idmaterial"
        );
        self::vincularDatos($stmt, $datos);
        $stmt->bindValue(":idmaterial", $datos["idmaterial"], PDO::PARAM_INT);
        return $stmt->execute() ? "ok" : "error";
    }

    public static function mdlBorrarMaterial($idMaterial)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM materiales WHERE idmaterial = :idmaterial");
        $stmt->bindValue(":idmaterial", $idMaterial, PDO::PARAM_INT);
        return $stmt->execute() ? "ok" : "error";
    }

    private static function vincularDatos($stmt, $datos)
    {
        $stmt->bindValue(":idtipo_material", $datos["idtipo_material"], PDO::PARAM_INT);
        $stmt->bindValue(":idproveedor", $datos["idproveedor"], PDO::PARAM_INT);
        $stmt->bindValue(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindValue(":unidad_medida", $datos["unidad_medida"], PDO::PARAM_STR);
        $stmt->bindValue(":stock_actual", $datos["stock_actual"]);
    }
}
