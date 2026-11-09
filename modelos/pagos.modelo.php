<?php

require_once "conexion.php";

class ModeloPagos
{
    public static function mdlMostrarPagos()
    {
        $stmt = Conexion::conectar()->query(
            "SELECT pa.*, pe.estado AS estado_pedido, pe.total AS total_pedido, c.nombre AS cliente
             FROM pagos pa
             INNER JOIN pedido pe ON pe.idpedido = pa.idpedido
             INNER JOIN cliente c ON c.idcliente = pe.idcliente
             ORDER BY pa.fecha_pago DESC, pa.idpago DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlMostrarPedidosConSaldo()
    {
        $stmt = Conexion::conectar()->query(
            "SELECT pe.idpedido, pe.total, pe.estado, pe.fecha_pedido, c.nombre AS cliente,
                    pe.total - COALESCE(SUM(pa.monto), 0) AS saldo
             FROM pedido pe
             INNER JOIN cliente c ON c.idcliente = pe.idcliente
             LEFT JOIN pagos pa ON pa.idpedido = pe.idpedido
             GROUP BY pe.idpedido, pe.total, pe.estado, pe.fecha_pedido, c.nombre
             HAVING saldo > 0
             ORDER BY pe.fecha_pedido DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlIngresarPago($datos)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();

        try {
            self::validarSaldo($conexion, $datos["idpedido"], $datos["monto"], null);
            $stmt = $conexion->prepare(
                "INSERT INTO pagos (idpedido, monto, metodo_pago) VALUES (:idpedido, :monto, :metodo_pago)"
            );
            $stmt->execute(self::parametros($datos));

            $conexion->commit();
            return "ok";
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    public static function mdlEditarPago($idPago, $datos)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();

        try {
            $stmt = $conexion->prepare("SELECT idpedido FROM pagos WHERE idpago = :id");
            $stmt->execute(array(":id" => $idPago));
            $idPedido = $stmt->fetchColumn();
            if (!$idPedido) {
                throw new InvalidArgumentException("El pago seleccionado no existe.");
            }

            self::validarSaldo($conexion, (int) $idPedido, $datos["monto"], $idPago);
            $actualizar = $conexion->prepare(
                "UPDATE pagos SET monto = :monto, metodo_pago = :metodo_pago WHERE idpago = :id"
            );
            $actualizar->execute(array(
                ":monto" => number_format($datos["monto"], 2, ".", ""),
                ":metodo_pago" => $datos["metodo_pago"],
                ":id" => $idPago
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

    public static function mdlBorrarPago($idPago)
    {
        $stmt = Conexion::conectar()->prepare("DELETE FROM pagos WHERE idpago = :id");
        $stmt->bindValue(":id", $idPago, PDO::PARAM_INT);
        return $stmt->execute() ? "ok" : "error";
    }

    private static function validarSaldo($conexion, $idPedido, $monto, $idPagoExcluir)
    {
        $pedido = $conexion->prepare("SELECT total FROM pedido WHERE idpedido = :id FOR UPDATE");
        $pedido->execute(array(":id" => $idPedido));
        $registro = $pedido->fetch(PDO::FETCH_ASSOC);
        if (!$registro) {
            throw new InvalidArgumentException("El pedido seleccionado no existe.");
        }

        $sql = "SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE idpedido = :idpedido";
        if ($idPagoExcluir !== null) {
            $sql .= " AND idpago <> :idpago";
        }
        $pagado = $conexion->prepare($sql);
        $parametros = array(":idpedido" => $idPedido);
        if ($idPagoExcluir !== null) {
            $parametros[":idpago"] = $idPagoExcluir;
        }
        $pagado->execute($parametros);
        $saldo = (float) $registro["total"] - (float) $pagado->fetchColumn();
        if ($monto > $saldo) {
            throw new InvalidArgumentException("El monto supera el saldo pendiente del pedido.");
        }
    }

    private static function parametros($datos)
    {
        return array(
            ":idpedido" => $datos["idpedido"],
            ":monto" => number_format($datos["monto"], 2, ".", ""),
            ":metodo_pago" => $datos["metodo_pago"]
        );
    }
}
