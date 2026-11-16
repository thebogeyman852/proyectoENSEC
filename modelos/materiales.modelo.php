<?php

require_once "conexion.php";
require_once "auditoria.modelo.php";

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
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
        $stmt = $conexion->prepare(
            "INSERT INTO materiales (idtipo_material, idproveedor, nombre, unidad_medida)
             VALUES (:idtipo_material, :idproveedor, :nombre, :unidad_medida)"
        );
        self::vincularDatos($stmt, $datos);
        $stmt->execute();
        ModeloAuditoria::mdlRegistrar($conexion, array(
            "id_usuario" => $_SESSION["idusuario"] ?? null,
            "accion" => "crear",
            "modulo" => "materiales",
            "tabla" => "materiales",
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

    public static function mdlEditarMaterial($datos)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
        $anterior = $conexion->prepare("SELECT * FROM materiales WHERE idmaterial = :id FOR UPDATE");
        $anterior->execute(array(":id" => $datos["idmaterial"]));
        $datosAnteriores = $anterior->fetch(PDO::FETCH_ASSOC);
        if (!$datosAnteriores) {
            throw new InvalidArgumentException("El material seleccionado no existe.");
        }
        $stmt = $conexion->prepare(
            "UPDATE materiales
             SET idtipo_material = :idtipo_material, idproveedor = :idproveedor,
                 nombre = :nombre, unidad_medida = :unidad_medida
             WHERE idmaterial = :idmaterial"
        );
        self::vincularDatos($stmt, $datos);
        $stmt->bindValue(":idmaterial", $datos["idmaterial"], PDO::PARAM_INT);
        $stmt->execute();
        ModeloAuditoria::mdlRegistrar($conexion, array(
            "id_usuario" => $_SESSION["idusuario"] ?? null,
            "accion" => "editar",
            "modulo" => "materiales",
            "tabla" => "materiales",
            "id_registro" => (int) $datos["idmaterial"],
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

    public static function mdlBorrarMaterial($idMaterial)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();

        try {
            $material = $conexion->prepare("SELECT * FROM materiales WHERE idmaterial = :id FOR UPDATE");
            $material->execute(array(":id" => $idMaterial));
            $datosAnteriores = $material->fetch(PDO::FETCH_ASSOC);
            if (!$datosAnteriores) {
                throw new InvalidArgumentException("El material seleccionado no existe.");
            }

            $movimientos = $conexion->prepare(
                "SELECT COUNT(*) FROM kardex WHERE tipo_item = 'material' AND id_item = :id"
            );
            $movimientos->execute(array(":id" => $idMaterial));
            if ((int) $movimientos->fetchColumn() > 0) {
                throw new InvalidArgumentException(
                    "No se puede eliminar un material que tiene movimientos en el kardex."
                );
            }

            $borrar = $conexion->prepare("DELETE FROM materiales WHERE idmaterial = :id");
            $borrar->execute(array(":id" => $idMaterial));
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $_SESSION["idusuario"] ?? null,
                "accion" => "eliminar",
                "modulo" => "materiales",
                "tabla" => "materiales",
                "id_registro" => (int) $idMaterial,
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
        $stmt->bindValue(":idtipo_material", $datos["idtipo_material"], PDO::PARAM_INT);
        $stmt->bindValue(":idproveedor", $datos["idproveedor"], PDO::PARAM_INT);
        $stmt->bindValue(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindValue(":unidad_medida", $datos["unidad_medida"], PDO::PARAM_STR);
    }
}
