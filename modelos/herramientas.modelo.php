<?php

require_once "conexion.php";
require_once "auditoria.modelo.php";

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
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
        $stmt = $conexion->prepare(
            "INSERT INTO herramienta (codigo, nombre, estado, stock) VALUES (:codigo, :nombre, :estado, 0)"
        );
        self::vincularDatos($stmt, $datos);
        $stmt->execute();
        ModeloAuditoria::mdlRegistrar($conexion, array(
            "id_usuario" => $_SESSION["idusuario"] ?? null,
            "accion" => "crear",
            "modulo" => "herramientas",
            "tabla" => "herramienta",
            "id_registro" => (int) $conexion->lastInsertId(),
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

    public static function mdlEditarHerramienta($datos)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
        $anterior = $conexion->prepare("SELECT * FROM herramienta WHERE idherramienta = :id FOR UPDATE");
        $anterior->execute(array(":id" => $datos["idherramienta"]));
        $datosAnteriores = $anterior->fetch(PDO::FETCH_ASSOC);
        if (!$datosAnteriores) {
            throw new InvalidArgumentException("La herramienta seleccionada no existe.");
        }
        $stmt = $conexion->prepare(
            "UPDATE herramienta SET codigo = :codigo, nombre = :nombre, estado = :estado
             WHERE idherramienta = :idherramienta"
        );
        self::vincularDatos($stmt, $datos);
        $stmt->bindValue(":idherramienta", $datos["idherramienta"], PDO::PARAM_INT);
        $stmt->execute();
        ModeloAuditoria::mdlRegistrar($conexion, array(
            "id_usuario" => $_SESSION["idusuario"] ?? null,
            "accion" => "editar",
            "modulo" => "herramientas",
            "tabla" => "herramienta",
            "id_registro" => (int) $datos["idherramienta"],
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

    public static function mdlBorrarHerramienta($idHerramienta)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            $herramienta = $conexion->prepare("SELECT * FROM herramienta WHERE idherramienta = :id FOR UPDATE");
            $herramienta->bindValue(":id", $idHerramienta, PDO::PARAM_INT);
            $herramienta->execute();
            $datosAnteriores = $herramienta->fetch(PDO::FETCH_ASSOC);
            if (!$datosAnteriores) {
                throw new InvalidArgumentException("La herramienta seleccionada no existe.");
            }

            $inventario = $conexion->prepare("SELECT COUNT(*) FROM inventario WHERE idherramienta = :id");
            $inventario->bindValue(":id", $idHerramienta, PDO::PARAM_INT);
            $inventario->execute();
            $kardex = $conexion->prepare(
                "SELECT COUNT(*) FROM kardex WHERE tipo_item = 'herramienta' AND id_item = :id"
            );
            $kardex->bindValue(":id", $idHerramienta, PDO::PARAM_INT);
            $kardex->execute();
            if ((int) $inventario->fetchColumn() > 0 || (int) $kardex->fetchColumn() > 0) {
                throw new InvalidArgumentException("No se puede eliminar una herramienta con historial de movimientos.");
            }

            $stmt = $conexion->prepare("DELETE FROM herramienta WHERE idherramienta = :id");
            $stmt->bindValue(":id", $idHerramienta, PDO::PARAM_INT);
            $stmt->execute();
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $_SESSION["idusuario"] ?? null,
                "accion" => "eliminar",
                "modulo" => "herramientas",
                "tabla" => "herramienta",
                "id_registro" => (int) $idHerramienta,
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

    private static function vincularDatos($stmt, $datos)
    {
        $stmt->bindValue(":codigo", $datos["codigo"], PDO::PARAM_STR);
        $stmt->bindValue(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindValue(":estado", $datos["estado"], PDO::PARAM_STR);
    }
}
