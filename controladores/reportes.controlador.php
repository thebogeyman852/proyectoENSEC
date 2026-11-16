<?php

class ControladorReportes
{
    public static function ctrMostrar()
    {
        $desde = self::fecha($_GET["desde"] ?? "");
        $hasta = self::fecha($_GET["hasta"] ?? "");
        if ($desde !== null && $hasta !== null && $desde > $hasta) {
            throw new InvalidArgumentException("La fecha inicial no puede ser posterior a la fecha final.");
        }
        return array(
            "existencias" => ModeloReportes::mdlExistencias(),
            "movimientos" => ModeloReportes::mdlMovimientos($desde, $hasta),
            "consumos" => ModeloReportes::mdlConsumosPedido($desde, $hasta),
            "compras" => ModeloReportes::mdlComprasProveedor($desde, $hasta),
            "herramientas" => ModeloReportes::mdlHerramientas(),
            "pedidos" => ModeloReportes::mdlPedidos($desde, $hasta),
            "pagos" => ModeloReportes::mdlPagos($desde, $hasta),
            "desde" => $desde,
            "hasta" => $hasta,
            "error" => null
        );
    }

    private static function fecha($valor)
    {
        if ($valor === "") {
            return null;
        }
        if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            throw new InvalidArgumentException("Usa fechas válidas en formato AAAA-MM-DD.");
        }
        $fecha = DateTime::createFromFormat("!Y-m-d", $valor);
        if (!$fecha || $fecha->format("Y-m-d") !== $valor) {
            throw new InvalidArgumentException("La fecha seleccionada no existe.");
        }
        return $valor;
    }
}
