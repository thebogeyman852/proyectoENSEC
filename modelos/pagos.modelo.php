<?php

require_once "conexion.php";
require_once "auditoria.modelo.php";

class ModeloPagos
{
    public static function mdlMostrarPagos()
    {
        $estadoDisponible = self::columnaExiste("pagos", "estado");
        $estado = $estadoDisponible
            ? "pa.estado AS estado_pago, pa.motivo_anulacion"
            : "'registrado' AS estado_pago, NULL AS motivo_anulacion";
        $stmt = Conexion::conectar()->query(
            "SELECT pa.*, $estado, pe.estado AS estado_pedido, pe.total AS total_pedido, c.nombre AS cliente
             FROM pagos pa
             INNER JOIN pedido pe ON pe.idpedido = pa.idpedido
             INNER JOIN cliente c ON c.idcliente = pe.idcliente
             ORDER BY pa.fecha_pago DESC, pa.idpago DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function mdlMostrarPedidosConSaldo()
    {
        $condicionEstado = self::columnaExiste("pagos", "estado")
            ? " AND pa.estado = 'registrado'"
            : "";
        $stmt = Conexion::conectar()->query(
            "SELECT pe.idpedido, pe.total, pe.estado, pe.fecha_pedido, c.nombre AS cliente,
                    COALESCE(SUM(CASE WHEN 1 = 1 $condicionEstado THEN pa.monto ELSE 0 END), 0) AS total_pagado,
                    pe.total - COALESCE(SUM(CASE WHEN 1 = 1 $condicionEstado THEN pa.monto ELSE 0 END), 0) AS saldo
             FROM pedido pe
             INNER JOIN cliente c ON c.idcliente = pe.idcliente
             LEFT JOIN pagos pa ON pa.idpedido = pe.idpedido
             WHERE pe.estado <> 'CANCELADO'
             GROUP BY pe.idpedido, pe.total, pe.estado, pe.fecha_pedido, c.nombre
             HAVING saldo > 0
             ORDER BY pe.fecha_pedido DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlIngresarPago($datos)
    {
        self::validarIdempotencia();
        if (
            !isset($datos["id_solicitud"]) ||
            !is_string($datos["id_solicitud"]) ||
            !preg_match('/^[a-f0-9]{32}$/', $datos["id_solicitud"])
        ) {
            throw new InvalidArgumentException("La solicitud de pago no es válida.");
        }

        $conexion = Conexion::conectar();
        $conexion->beginTransaction();

        try {
            $pedido = self::bloquearPedido($conexion, $datos["idpedido"]);
            $estadoDisponible = self::columnaExiste("pagos", "estado");
            $buscarSolicitud = $conexion->prepare(
                "SELECT idpedido, monto, metodo_pago" . ($estadoDisponible ? ", estado" : "") .
                " FROM pagos WHERE id_solicitud = :solicitud"
            );
            $buscarSolicitud->execute(array(":solicitud" => $datos["id_solicitud"]));
            $pagoExistente = $buscarSolicitud->fetch(PDO::FETCH_ASSOC);
            if ($pagoExistente) {
                if (isset($pagoExistente["estado"]) && $pagoExistente["estado"] !== "registrado") {
                    throw new InvalidArgumentException("El pago de esta solicitud fue anulado y no puede repetirse.");
                }
                if (
                    (int) $pagoExistente["idpedido"] !== (int) $datos["idpedido"] ||
                    self::aCentimos($pagoExistente["monto"]) !== self::aCentimos($datos["monto"]) ||
                    $pagoExistente["metodo_pago"] !== $datos["metodo_pago"]
                ) {
                    throw new InvalidArgumentException(
                        "La solicitud de pago ya fue utilizada con otros datos; no se duplicó."
                    );
                }
                $conexion->commit();
                return "ok";
            }

            self::validarSaldo($conexion, $datos["idpedido"], $datos["monto"], null, $pedido);
            $stmt = $conexion->prepare(
                "INSERT INTO pagos (id_solicitud, idpedido, monto, metodo_pago)
                 VALUES (:solicitud, :idpedido, :monto, :metodo_pago)"
            );
            $parametros = self::parametros($datos);
            $parametros[":solicitud"] = $datos["id_solicitud"];
            $stmt->execute($parametros);
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $datos["id_usuario"] ?? null,
                "accion" => "crear",
                "modulo" => "pagos",
                "tabla" => "pagos",
                "id_registro" => (int) $conexion->lastInsertId(),
                "anteriores" => null,
                "nuevos" => array(
                    "idpedido" => $datos["idpedido"],
                    "monto" => $datos["monto"],
                    "metodo_pago" => $datos["metodo_pago"]
                )
            ));

            $conexion->commit();
            return "ok";
        } catch (PDOException $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            if (isset($error->errorInfo[1]) && (int) $error->errorInfo[1] === 1062) {
                throw new InvalidArgumentException("Esta solicitud de pago ya fue registrada; no se duplicó.", 0, $error);
            }
            throw $error;
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
            $sqlPago = "SELECT idpedido, monto, metodo_pago" . (self::columnaExiste("pagos", "estado") ? ", estado" : "") .
                " FROM pagos WHERE idpago = :id FOR UPDATE";
            $stmt = $conexion->prepare($sqlPago);
            $stmt->execute(array(":id" => $idPago));
            $pago = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$pago) {
                throw new InvalidArgumentException("El pago seleccionado no existe.");
            }
            if (isset($pago["estado"]) && $pago["estado"] !== "registrado") {
                throw new InvalidArgumentException("No se puede editar un pago anulado.");
            }

            $pedido = self::bloquearPedido($conexion, (int) $pago["idpedido"], true);
            if (
                $pedido["estado"] === "CANCELADO" &&
                self::aCentimos($datos["monto"]) > self::aCentimos($pago["monto"])
            ) {
                throw new InvalidArgumentException("No se puede aumentar un pago de un pedido cancelado.");
            }
            self::validarSaldo($conexion, (int) $pago["idpedido"], $datos["monto"], $idPago, $pedido);
            $actualizar = $conexion->prepare(
                "UPDATE pagos SET monto = :monto, metodo_pago = :metodo_pago WHERE idpago = :id"
            );
            $actualizar->execute(array(
                ":monto" => self::aDecimal(self::aCentimos($datos["monto"])),
                ":metodo_pago" => $datos["metodo_pago"],
                ":id" => $idPago
            ));
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $datos["id_usuario"] ?? null,
                "accion" => "editar",
                "modulo" => "pagos",
                "tabla" => "pagos",
                "id_registro" => $idPago,
                "anteriores" => array("monto" => $pago["monto"], "metodo_pago" => $pago["metodo_pago"]),
                "nuevos" => array("monto" => $datos["monto"], "metodo_pago" => $datos["metodo_pago"])
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

    public static function mdlAnularPago($idPago, $idUsuario, $motivo)
    {
        if (!self::columnaExiste("pagos", "estado")) {
            throw new RuntimeException("Aplica la migración de anulación de pagos en una copia de pruebas antes de habilitar esta operación.");
        }
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            $pago = $conexion->prepare("SELECT idpedido, monto, metodo_pago, estado FROM pagos WHERE idpago = :id FOR UPDATE");
            $pago->execute(array(":id" => $idPago));
            $pagoActual = $pago->fetch(PDO::FETCH_ASSOC);
            if (!$pagoActual) {
                throw new InvalidArgumentException("El pago seleccionado no existe.");
            }
            if ($pagoActual["estado"] !== "registrado") {
                throw new InvalidArgumentException("El pago ya fue anulado.");
            }
            $anular = $conexion->prepare(
                "UPDATE pagos
                 SET estado = 'anulado', anulado_en = CURRENT_TIMESTAMP,
                     anulado_por = :usuario, motivo_anulacion = :motivo
                 WHERE idpago = :id AND estado = 'registrado'"
            );
            $anular->execute(array(
                ":usuario" => $idUsuario,
                ":motivo" => $motivo,
                ":id" => $idPago
            ));
            if ($anular->rowCount() !== 1) {
                throw new RuntimeException("No se confirmó la anulación del pago.");
            }
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $idUsuario,
                "accion" => "anular",
                "modulo" => "pagos",
                "tabla" => "pagos",
                "id_registro" => (int) $idPago,
                "anteriores" => $pagoActual,
                "nuevos" => array("estado" => "anulado", "motivo" => $motivo)
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

    private static function validarSaldo($conexion, $idPedido, $monto, $idPagoExcluir, $pedido = null)
    {
        $registro = $pedido === null ? self::bloquearPedido($conexion, $idPedido) : $pedido;

        $sql = "SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE idpedido = :idpedido";
        if (self::columnaExiste("pagos", "estado")) {
            $sql .= " AND estado = 'registrado'";
        }
        if ($idPagoExcluir !== null) {
            $sql .= " AND idpago <> :idpago";
        }
        $pagado = $conexion->prepare($sql);
        $parametros = array(":idpedido" => $idPedido);
        if ($idPagoExcluir !== null) {
            $parametros[":idpago"] = $idPagoExcluir;
        }
        $pagado->execute($parametros);
        $saldoCentimos = self::aCentimos($registro["total"]) - self::aCentimos($pagado->fetchColumn());
        $montoCentimos = self::aCentimos($monto);
        if ($montoCentimos > $saldoCentimos) {
            throw new InvalidArgumentException("El monto supera el saldo pendiente del pedido.");
        }
        if ($montoCentimos <= 0) {
            throw new InvalidArgumentException("El monto debe ser mayor que cero.");
        }
    }

    private static function bloquearPedido($conexion, $idPedido, $permitirCancelado = false)
    {
        $pedido = $conexion->prepare("SELECT total, estado FROM pedido WHERE idpedido = :id FOR UPDATE");
        $pedido->execute(array(":id" => $idPedido));
        $registro = $pedido->fetch(PDO::FETCH_ASSOC);
        if (!$registro) {
            throw new InvalidArgumentException("El pedido seleccionado no existe.");
        }
        if ($registro["estado"] === "CANCELADO" && !$permitirCancelado) {
            throw new InvalidArgumentException("No se pueden registrar pagos para un pedido cancelado.");
        }
        return $registro;
    }

    private static function parametros($datos)
    {
        return array(
            ":idpedido" => $datos["idpedido"],
            ":monto" => self::aDecimal(self::aCentimos($datos["monto"])),
            ":metodo_pago" => $datos["metodo_pago"]
        );
    }

    private static function validarIdempotencia()
    {
        $conexion = Conexion::conectar();
        $columna = $conexion->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'pagos'
               AND COLUMN_NAME = 'id_solicitud'"
        );
        $columna->execute();
        $indice = $conexion->prepare(
            "SELECT COUNT(*)
             FROM information_schema.STATISTICS s
             WHERE s.TABLE_SCHEMA = DATABASE()
               AND s.TABLE_NAME = 'pagos'
               AND s.COLUMN_NAME = 'id_solicitud'
               AND s.NON_UNIQUE = 0
               AND s.SEQ_IN_INDEX = 1
               AND (
                    SELECT COUNT(*)
                    FROM information_schema.STATISTICS i
                    WHERE i.TABLE_SCHEMA = s.TABLE_SCHEMA
                      AND i.TABLE_NAME = s.TABLE_NAME
                      AND i.INDEX_NAME = s.INDEX_NAME
               ) = 1"
        );
        $indice->execute();
        if ((int) $columna->fetchColumn() !== 1 || (int) $indice->fetchColumn() !== 1) {
            throw new RuntimeException(
                "Aplica la migración de idempotencia de pagos en una copia de pruebas antes de registrar pagos."
            );
        }
    }

    private static function aCentimos($monto)
    {
        if (
            (!is_string($monto) && !is_int($monto)) ||
            !preg_match('/^\d{1,9}(?:\.\d{1,2})?$/', (string) $monto)
        ) {
            throw new InvalidArgumentException("El monto no tiene un formato válido.");
        }
        $partes = explode(".", (string) $monto, 2);
        $centimos = (int) $partes[0] * 100 +
            (int) str_pad(isset($partes[1]) ? $partes[1] : "", 2, "0");
        if ($centimos > 99999999999) {
            throw new InvalidArgumentException("El monto supera el límite permitido.");
        }
        return $centimos;
    }

    private static function aDecimal($centimos)
    {
        return intdiv($centimos, 100) . "." . str_pad((string) ($centimos % 100), 2, "0", STR_PAD_LEFT);
    }

    private static function columnaExiste($tabla, $columna)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla AND COLUMN_NAME = :columna"
        );
        $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
        return (int) $stmt->fetchColumn() === 1;
    }
}
