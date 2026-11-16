<?php

require_once "conexion.php";
require_once "auditoria.modelo.php";

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
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
        $columnas = $configuracion["campos"];
        $marcadores = array_map(static function ($campo) {
            return ":" . $campo;
        }, $columnas);
        $stmt = $conexion->prepare(
            "INSERT INTO " . $configuracion["tabla"] . " (" . implode(", ", $columnas) . ")
             VALUES (" . implode(", ", $marcadores) . ")"
        );

        foreach ($columnas as $campo) {
            $stmt->bindValue(":" . $campo, $datos[$campo], PDO::PARAM_STR);
        }

        $stmt->execute();
        $id = (int) $conexion->lastInsertId();
        ModeloAuditoria::mdlRegistrar($conexion, array(
            "id_usuario" => $_SESSION["idusuario"] ?? null,
            "accion" => "crear",
            "modulo" => $recurso,
            "tabla" => $configuracion["tabla"],
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

    public static function mdlEditar($recurso, $id, $datos)
    {
        $configuracion = self::configuracion($recurso);
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
        $anterior = $conexion->prepare(
            "SELECT " . implode(", ", $configuracion["campos"]) . " FROM " .
            $configuracion["tabla"] . " WHERE " . $configuracion["id"] . " = :id FOR UPDATE"
        );
        $anterior->execute(array(":id" => $id));
        $datosAnteriores = $anterior->fetch(PDO::FETCH_ASSOC);
        if (!$datosAnteriores) {
            throw new InvalidArgumentException("El registro seleccionado no existe.");
        }
        $asignaciones = array_map(static function ($campo) {
            return $campo . " = :" . $campo;
        }, $configuracion["campos"]);
        $stmt = $conexion->prepare(
            "UPDATE " . $configuracion["tabla"] . " SET " . implode(", ", $asignaciones) .
            " WHERE " . $configuracion["id"] . " = :id"
        );

        foreach ($configuracion["campos"] as $campo) {
            $stmt->bindValue(":" . $campo, $datos[$campo], PDO::PARAM_STR);
        }
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        $stmt->execute();
        ModeloAuditoria::mdlRegistrar($conexion, array(
            "id_usuario" => $_SESSION["idusuario"] ?? null,
            "accion" => "editar",
            "modulo" => $recurso,
            "tabla" => $configuracion["tabla"],
            "id_registro" => $id,
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

    public static function mdlBorrar($recurso, $id)
    {
        $configuracion = self::configuracion($recurso);
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
        $anterior = $conexion->prepare(
            "SELECT " . implode(", ", $configuracion["campos"]) . " FROM " .
            $configuracion["tabla"] . " WHERE " . $configuracion["id"] . " = :id FOR UPDATE"
        );
        $anterior->execute(array(":id" => $id));
        $datosAnteriores = $anterior->fetch(PDO::FETCH_ASSOC);
        if (!$datosAnteriores) {
            throw new InvalidArgumentException("El registro seleccionado no existe.");
        }
        if ($recurso === "proveedores") {
            $compras = $conexion->prepare("SELECT COUNT(*) FROM compra WHERE id_proveedor = :id");
            $compras->execute(array(":id" => $id));
            if ((int) $compras->fetchColumn() > 0) {
                throw new InvalidArgumentException("No se puede eliminar un proveedor que tiene compras asociadas.");
            }
        }
        $stmt = $conexion->prepare(
            "DELETE FROM " . $configuracion["tabla"] . " WHERE " . $configuracion["id"] . " = :id"
        );
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        ModeloAuditoria::mdlRegistrar($conexion, array(
            "id_usuario" => $_SESSION["idusuario"] ?? null,
            "accion" => "eliminar",
            "modulo" => $recurso,
            "tabla" => $configuracion["tabla"],
            "id_registro" => $id,
            "anteriores" => $datosAnteriores,
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

    private static function configuracion($recurso)
    {
        if (!isset(self::$recursos[$recurso])) {
            throw new InvalidArgumentException("Catálogo no válido.");
        }

        return self::$recursos[$recurso];
    }
}
