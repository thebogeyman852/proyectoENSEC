<?php

require_once "conexion.php";

class ModeloPedidos
{
    public static function mdlMostrarPedidos()
    {
        $conexion = Conexion::conectar();
        $stmt = $conexion->query(
            "SELECT p.*, c.nombre AS cliente, u.nombre AS usuario
             FROM pedido p
             INNER JOIN cliente c ON c.idcliente = p.idcliente
             INNER JOIN usuario u ON u.idusuario = p.idusuario
             ORDER BY p.fecha_pedido DESC, p.idpedido DESC"
        );
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $indicePedidos = array();

        foreach ($pedidos as $indice => $pedido) {
            $pedidos[$indice]["detalles"] = array();
            $pedidos[$indice]["materiales"] = array();
            $pedidos[$indice]["total_pagado"] = 0.0;
            $indicePedidos[(int) $pedido["idpedido"]] = $indice;
        }

        $detalles = $conexion->query(
            "SELECT d.*, c.nombre AS categoria
             FROM detalle_pedido d
             INNER JOIN categoria c ON c.idcategoria = d.idcategoria
             ORDER BY d.iddetalle_pedido"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($detalles as $detalle) {
            $idPedido = (int) $detalle["idpedido"];
            if (isset($indicePedidos[$idPedido])) {
                $pedidos[$indicePedidos[$idPedido]]["detalles"][] = $detalle;
            }
        }

        $materiales = $conexion->query(
            "SELECT d.*, m.nombre AS material, m.unidad_medida
             FROM detalle_material d
             INNER JOIN materiales m ON m.idmaterial = d.idmaterial
             ORDER BY d.iddetalle_material"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($materiales as $detalleMaterial) {
            $idPedido = (int) $detalleMaterial["idpedido"];
            if (isset($indicePedidos[$idPedido])) {
                $pedidos[$indicePedidos[$idPedido]]["materiales"][] = $detalleMaterial;
            }
        }

        $pagos = $conexion->query("SELECT idpedido, SUM(monto) AS total_pagado FROM pagos GROUP BY idpedido")
            ->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($indicePedidos as $idPedido => $indice) {
            $pedidos[$indice]["total_pagado"] = (float) ($pagos[$idPedido] ?? 0);
        }

        return $pedidos;
    }

    public static function mdlCrearPedido($datos)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();

        try {
            $total = self::calcularTotal($datos["detalles"]);
            $pedido = $conexion->prepare(
                "INSERT INTO pedido (idusuario, idcliente, total, estado)
                 VALUES (:idusuario, :idcliente, :total, 'PENDIENTE')"
            );
            $pedido->execute(array(
                ":idusuario" => $datos["idusuario"],
                ":idcliente" => $datos["idcliente"],
                ":total" => number_format($total, 2, ".", "")
            ));
            $idPedido = (int) $conexion->lastInsertId();

            self::registrarDetallesPedido($conexion, $idPedido, $datos["detalles"]);
            self::registrarConsumoMateriales($conexion, $idPedido, $datos["materiales"]);

            $conexion->commit();
            return $idPedido;
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    public static function mdlCambiarEstado($idPedido, $estado)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE pedido SET estado = :estado WHERE idpedido = :id");
        $stmt->bindValue(":estado", $estado, PDO::PARAM_STR);
        $stmt->bindValue(":id", $idPedido, PDO::PARAM_INT);
        return $stmt->execute() ? "ok" : "error";
    }

    public static function mdlEditarPedido($idPedido, $datos)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();

        try {
            $actual = $conexion->prepare("SELECT idpedido FROM pedido WHERE idpedido = :id FOR UPDATE");
            $actual->execute(array(":id" => $idPedido));
            if (!$actual->fetch(PDO::FETCH_ASSOC)) {
                throw new InvalidArgumentException("El pedido seleccionado no existe.");
            }

            $total = self::calcularTotal($datos["detalles"]);
            $pagos = $conexion->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE idpedido = :id");
            $pagos->execute(array(":id" => $idPedido));
            if ($total < (float) $pagos->fetchColumn()) {
                throw new InvalidArgumentException("El nuevo total no puede ser menor que los pagos registrados.");
            }

            $consumos = $conexion->prepare(
                "SELECT idmaterial, cantidad_usada FROM detalle_material WHERE idpedido = :id FOR UPDATE"
            );
            $consumos->execute(array(":id" => $idPedido));
            $restaurar = $conexion->prepare(
                "UPDATE materiales SET stock_actual = stock_actual + :cantidad WHERE idmaterial = :id"
            );
            foreach ($consumos->fetchAll(PDO::FETCH_ASSOC) as $consumo) {
                $restaurar->execute(array(
                    ":cantidad" => $consumo["cantidad_usada"],
                    ":id" => $consumo["idmaterial"]
                ));
            }

            $conexion->prepare("DELETE FROM detalle_material WHERE idpedido = :id")
                ->execute(array(":id" => $idPedido));
            $conexion->prepare("DELETE FROM detalle_pedido WHERE idpedido = :id")
                ->execute(array(":id" => $idPedido));

            $actualizar = $conexion->prepare(
                "UPDATE pedido SET idcliente = :idcliente, total = :total WHERE idpedido = :id"
            );
            $actualizar->execute(array(
                ":idcliente" => $datos["idcliente"],
                ":total" => number_format($total, 2, ".", ""),
                ":id" => $idPedido
            ));

            self::registrarDetallesPedido($conexion, $idPedido, $datos["detalles"]);
            self::registrarConsumoMateriales($conexion, $idPedido, $datos["materiales"]);
            $conexion->commit();
            return "ok";
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    public static function mdlBorrarPedido($idPedido)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();

        try {
            $pedido = $conexion->prepare("SELECT idpedido FROM pedido WHERE idpedido = :id FOR UPDATE");
            $pedido->execute(array(":id" => $idPedido));
            if (!$pedido->fetch(PDO::FETCH_ASSOC)) {
                throw new InvalidArgumentException("El pedido seleccionado no existe.");
            }

            $stmt = $conexion->prepare(
                "SELECT idmaterial, cantidad_usada FROM detalle_material WHERE idpedido = :id"
            );
            $stmt->execute(array(":id" => $idPedido));
            $restaurar = $conexion->prepare(
                "UPDATE materiales SET stock_actual = stock_actual + :cantidad WHERE idmaterial = :id"
            );
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $material) {
                $restaurar->execute(array(
                    ":cantidad" => $material["cantidad_usada"],
                    ":id" => $material["idmaterial"]
                ));
            }

            $borrar = $conexion->prepare("DELETE FROM pedido WHERE idpedido = :id");
            $borrar->bindValue(":id", $idPedido, PDO::PARAM_INT);
            $borrar->execute();
            $conexion->commit();
            return "ok";
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    private static function calcularTotal($detalles)
    {
        $total = 0;
        foreach ($detalles as $detalle) {
            $total += $detalle["cantidad"] * $detalle["precio_unitario"];
        }
        if ($total <= 0 || $total > 999999999.99) {
            throw new InvalidArgumentException("El total del pedido no es válido.");
        }
        return $total;
    }

    private static function registrarDetallesPedido($conexion, $idPedido, $detalles)
    {
        $stmt = $conexion->prepare(
            "INSERT INTO detalle_pedido (idpedido, idcategoria, cantidad, precio_unitario)
             VALUES (:idpedido, :idcategoria, :cantidad, :precio_unitario)"
        );
        foreach ($detalles as $detalle) {
            $stmt->execute(array(
                ":idpedido" => $idPedido,
                ":idcategoria" => $detalle["idcategoria"],
                ":cantidad" => $detalle["cantidad"],
                ":precio_unitario" => number_format($detalle["precio_unitario"], 2, ".", "")
            ));
        }
    }

    private static function registrarConsumoMateriales($conexion, $idPedido, $materiales)
    {
        $consultar = $conexion->prepare(
            "SELECT stock_actual FROM materiales WHERE idmaterial = :id FOR UPDATE"
        );
        $actualizar = $conexion->prepare(
            "UPDATE materiales SET stock_actual = stock_actual - :cantidad WHERE idmaterial = :id"
        );
        $insertar = $conexion->prepare(
            "INSERT INTO detalle_material (idpedido, idmaterial, cantidad_usada)
             VALUES (:idpedido, :idmaterial, :cantidad)"
        );

        foreach ($materiales as $detalle) {
            $consultar->execute(array(":id" => $detalle["idmaterial"]));
            $material = $consultar->fetch(PDO::FETCH_ASSOC);
            if (!$material || (float) $material["stock_actual"] < $detalle["cantidad_usada"]) {
                throw new InvalidArgumentException("El material no existe o no tiene stock suficiente.");
            }

            $cantidad = number_format($detalle["cantidad_usada"], 2, ".", "");
            $actualizar->execute(array(":cantidad" => $cantidad, ":id" => $detalle["idmaterial"]));
            $insertar->execute(array(
                ":idpedido" => $idPedido,
                ":idmaterial" => $detalle["idmaterial"],
                ":cantidad" => $cantidad
            ));
        }
    }
}
