<?php

require_once "conexion.php";

class ModeloHerramientas
{
    public static function mdlMostrarHerramientas($idHerramienta = null)
    {
        if ($idHerramienta !== null) {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM herramienta WHERE idherramienta = :id");
            $stmt->bindValue(":id", $idHerramienta, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $stmt = Conexion::conectar()->query("SELECT * FROM herramienta ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlIngresarHerramienta($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO herramienta (nombre, estado, stock) VALUES (:nombre, :estado, :stock)"
        );
        self::vincularDatos($stmt, $datos);
        return $stmt->execute() ? "ok" : "error";
    }

    public static function mdlEditarHerramienta($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE herramienta SET nombre = :nombre, estado = :estado, stock = :stock
             WHERE idherramienta = :idherramienta"
        );
        self::vincularDatos($stmt, $datos);
        $stmt->bindValue(":idherramienta", $datos["idherramienta"], PDO::PARAM_INT);
        return $stmt->execute() ? "ok" : "error";
    }

    public static function mdlBorrarHerramienta($idHerramienta)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM herramienta WHERE idherramienta = :id");
        $stmt->bindValue(":id", $idHerramienta, PDO::PARAM_INT);
        return $stmt->execute() ? "ok" : "error";
    }

    private static function vincularDatos($stmt, $datos)
    {
        $stmt->bindValue(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindValue(":estado", $datos["estado"], PDO::PARAM_STR);
        $stmt->bindValue(":stock", $datos["stock"], PDO::PARAM_INT);
    }
}
