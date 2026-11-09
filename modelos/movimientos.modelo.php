<?php

require_once "conexion.php";

class ModeloMovimientos
{
    public static function mdlMostrarMovimientos()
    {
        $stmt = Conexion::conectar()->query(
            "SELECT i.*, u.nombre AS nombre_usuario, h.nombre AS nombre_herramienta
             FROM inventario i
             INNER JOIN usuario u ON u.idusuario = i.idusuario
             LEFT JOIN herramienta h ON h.idherramienta = i.idherramienta
             ORDER BY i.fecha DESC, i.idinventario DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlIngresarMovimiento($datos)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();

        try {
            $stmt = $conexion->prepare("SELECT stock FROM herramienta WHERE idherramienta = :id FOR UPDATE");
            $stmt->bindValue(":id", $datos["idherramienta"], PDO::PARAM_INT);
            $stmt->execute();
            $herramienta = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$herramienta) {
                throw new InvalidArgumentException("La herramienta seleccionada no existe.");
            }

            $stock = (int) $herramienta["stock"];
            if ($datos["tipo_movimiento"] === "SALIDA" && $datos["cantidad"] > $stock) {
                throw new InvalidArgumentException("La salida supera el stock disponible.");
            }

            $nuevoStock = $datos["tipo_movimiento"] === "ENTRADA"
                ? $stock + $datos["cantidad"]
                : $stock - $datos["cantidad"];
            $actualizar = $conexion->prepare("UPDATE herramienta SET stock = :stock WHERE idherramienta = :id");
            $actualizar->bindValue(":stock", $nuevoStock, PDO::PARAM_INT);
            $actualizar->bindValue(":id", $datos["idherramienta"], PDO::PARAM_INT);
            $actualizar->execute();

            $insertar = $conexion->prepare(
                "INSERT INTO inventario (idusuario, idherramienta, tipo_movimiento, cantidad)
                 VALUES (:idusuario, :idherramienta, :tipo_movimiento, :cantidad)"
            );
            $insertar->bindValue(":idusuario", $datos["idusuario"], PDO::PARAM_INT);
            $insertar->bindValue(":idherramienta", $datos["idherramienta"], PDO::PARAM_INT);
            $insertar->bindValue(":tipo_movimiento", $datos["tipo_movimiento"], PDO::PARAM_STR);
            $insertar->bindValue(":cantidad", $datos["cantidad"], PDO::PARAM_INT);
            $insertar->execute();

            $conexion->commit();
            return "ok";
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }
}
