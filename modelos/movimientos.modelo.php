<?php

require_once "conexion.php";

class ModeloMovimientos
{
    public static function mdlMostrarMovimientos()
    {
        $stmt = Conexion::conectar()->query(
            "SELECT historial.*
             FROM (
                 SELECT i.fecha,
                        i.tipo_movimiento COLLATE utf8mb4_unicode_ci AS tipo_movimiento,
                        i.cantidad,
                        u.nombre COLLATE utf8mb4_unicode_ci AS nombre_usuario,
                        h.nombre COLLATE utf8mb4_unicode_ci AS nombre_herramienta,
                        CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS motivo,
                        i.idinventario AS id_movimiento
                 FROM inventario i
                 INNER JOIN usuario u ON u.idusuario = i.idusuario
                 LEFT JOIN herramienta h ON h.idherramienta = i.idherramienta
                 UNION ALL
                 SELECT k.fecha,
                        UPPER(k.tipo_movimiento) COLLATE utf8mb4_unicode_ci AS tipo_movimiento,
                        k.cantidad,
                        u.nombre COLLATE utf8mb4_unicode_ci AS nombre_usuario,
                        h.nombre COLLATE utf8mb4_unicode_ci AS nombre_herramienta,
                        k.motivo COLLATE utf8mb4_unicode_ci AS motivo,
                        k.id_kardex AS id_movimiento
                 FROM kardex k
                 LEFT JOIN usuario u ON u.idusuario = k.id_usuario
                 LEFT JOIN herramienta h ON h.idherramienta = k.id_item
                 WHERE k.tipo_item = 'herramienta'
             ) historial
             ORDER BY historial.fecha DESC, historial.id_movimiento DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlIngresarMovimiento($datos)
    {
        $conexion = Conexion::conectar();
        $columna = $conexion->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kardex' AND COLUMN_NAME = 'id_solicitud'"
        );
        $columna->execute();
        if ((int) $columna->fetchColumn() !== 1) {
            throw new RuntimeException("Aplica la migración de idempotencia de kardex en una copia de pruebas antes de registrar movimientos.");
        }
        $conexion->beginTransaction();

        try {
            $repetido = $conexion->prepare("SELECT id_kardex FROM kardex WHERE id_solicitud = :solicitud FOR UPDATE");
            $repetido->execute(array(":solicitud" => $datos["id_solicitud"]));
            if ($repetido->fetchColumn()) {
                throw new InvalidArgumentException("Este movimiento ya fue registrado.");
            }
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
            if ($nuevoStock < 0 || $nuevoStock > 2147483647) {
                throw new InvalidArgumentException("El stock resultante excede la capacidad de la columna actual.");
            }
            $actualizar = $conexion->prepare("UPDATE herramienta SET stock = :stock WHERE idherramienta = :id");
            $actualizar->bindValue(":stock", $nuevoStock, PDO::PARAM_INT);
            $actualizar->bindValue(":id", $datos["idherramienta"], PDO::PARAM_INT);
            $actualizar->execute();

            $kardex = $conexion->prepare(
                "INSERT INTO kardex
                    (id_solicitud, tipo_item, id_item, tipo_movimiento, cantidad, stock_anterior,
                     stock_resultante, referencia_tipo, motivo, id_usuario)
                 VALUES
                    (:solicitud, 'herramienta', :id_item, :tipo_movimiento, :cantidad, :stock_anterior,
                     :stock_resultante, 'manual', :motivo, :id_usuario)"
            );
            $kardex->execute(array(
                ":solicitud" => $datos["id_solicitud"],
                ":id_item" => $datos["idherramienta"],
                ":tipo_movimiento" => strtolower($datos["tipo_movimiento"]),
                ":cantidad" => number_format($datos["cantidad"], 2, ".", ""),
                ":stock_anterior" => number_format($stock, 2, ".", ""),
                ":stock_resultante" => number_format($nuevoStock, 2, ".", ""),
                ":motivo" => $datos["motivo"],
                ":id_usuario" => $datos["idusuario"]
            ));
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $datos["idusuario"],
                "accion" => $datos["tipo_movimiento"],
                "modulo" => "herramientas",
                "tabla" => "herramienta",
                "id_registro" => $datos["idherramienta"],
                "anteriores" => array("stock" => $stock),
                "nuevos" => array(
                    "stock" => $nuevoStock,
                    "tipo_movimiento" => $datos["tipo_movimiento"],
                    "cantidad" => $datos["cantidad"],
                    "motivo" => $datos["motivo"]
                )
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
}
