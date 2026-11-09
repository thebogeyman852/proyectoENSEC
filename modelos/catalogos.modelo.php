<?php

require_once "conexion.php";

class ModeloCatalogos
{
    private static $recursos = array(
        "clientes" => array(
            "tabla" => "cliente",
            "id" => "idcliente",
            "campos" => array("nombre", "telefono", "direccion")
        ),
        "proveedores" => array(
            "tabla" => "proveedor",
            "id" => "idproveedor",
            "campos" => array("nombre_proveedor", "telefono", "direccion")
        ),
        "tipos-material" => array(
            "tabla" => "tipo_material",
            "id" => "idtipo_material",
            "campos" => array("nombre", "descripcion")
        )
    );

    public static function mdlMostrar($recurso)
    {
        $configuracion = self::configuracion($recurso);
        $stmt = Conexion::conectar()->query(
            "SELECT * FROM " . $configuracion["tabla"] . " ORDER BY " . $configuracion["id"] . " DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlIngresar($recurso, $datos)
    {
        $configuracion = self::configuracion($recurso);
        $columnas = $configuracion["campos"];
        $marcadores = array_map(static function ($campo) {
            return ":" . $campo;
        }, $columnas);
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO " . $configuracion["tabla"] . " (" . implode(", ", $columnas) . ")
             VALUES (" . implode(", ", $marcadores) . ")"
        );

        foreach ($columnas as $campo) {
            $stmt->bindValue(":" . $campo, $datos[$campo], PDO::PARAM_STR);
        }

        return $stmt->execute() ? "ok" : "error";
    }

    public static function mdlEditar($recurso, $id, $datos)
    {
        $configuracion = self::configuracion($recurso);
        $asignaciones = array_map(static function ($campo) {
            return $campo . " = :" . $campo;
        }, $configuracion["campos"]);
        $stmt = Conexion::conectar()->prepare(
            "UPDATE " . $configuracion["tabla"] . " SET " . implode(", ", $asignaciones) .
            " WHERE " . $configuracion["id"] . " = :id"
        );

        foreach ($configuracion["campos"] as $campo) {
            $stmt->bindValue(":" . $campo, $datos[$campo], PDO::PARAM_STR);
        }
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        return $stmt->execute() ? "ok" : "error";
    }

    public static function mdlBorrar($recurso, $id)
    {
        $configuracion = self::configuracion($recurso);
        $stmt = Conexion::conectar()->prepare(
            "DELETE FROM " . $configuracion["tabla"] . " WHERE " . $configuracion["id"] . " = :id"
        );
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        return $stmt->execute() ? "ok" : "error";
    }

    private static function configuracion($recurso)
    {
        if (!isset(self::$recursos[$recurso])) {
            throw new InvalidArgumentException("Catálogo no válido.");
        }

        return self::$recursos[$recurso];
    }
}
