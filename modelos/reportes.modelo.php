<?php

require_once "conexion.php";
require_once "inventario.modelo.php";

class ModeloReportes
{
    public static function mdlExistencias()
    {
        return Conexion::conectar()->query(
            "SELECT 'material' AS tipo, idmaterial AS id, nombre, unidad_medida AS unidad,
                    stock_actual AS stock
             FROM materiales
             UNION ALL
             SELECT 'herramienta', idherramienta, nombre, 'unidad', stock
             FROM herramienta
             UNION ALL
             SELECT 'producto', idproducto, descripcion, 'mueble (stock legado)', stock
             FROM productos
             ORDER BY tipo, nombre"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlMovimientos($desde, $hasta)
    {
        $filtros = array();
        if ($desde !== null) {
            $filtros["desde"] = $desde;
        }
        if ($hasta !== null) {
            $filtros["hasta"] = $hasta;
        }
        return ModeloInventario::mdlListarMovimientos($filtros);
    }

    public static function mdlConsumosPedido($desde, $hasta)
    {
        $sql = "SELECT k.fecha, k.referencia_id AS idpedido, c.nombre AS cliente,
                       m.nombre AS material, m.unidad_medida, k.cantidad, k.stock_anterior,
                       k.stock_resultante, k.id_usuario, u.nombre AS usuario, k.motivo
                FROM kardex k
                LEFT JOIN pedido p ON p.idpedido = k.referencia_id
                LEFT JOIN cliente c ON c.idcliente = p.idcliente
                LEFT JOIN materiales m ON k.tipo_item = 'material' AND m.idmaterial = k.id_item
                LEFT JOIN usuario u ON u.idusuario = k.id_usuario
                WHERE k.tipo_item = 'material' AND k.tipo_movimiento = 'salida'
                  AND k.referencia_tipo = 'pedido'";
        $parametros = array();
        self::agregarFechas($sql, $parametros, $desde, $hasta, "k.fecha");
        $stmt = Conexion::conectar()->prepare($sql . " ORDER BY k.fecha DESC, k.id_kardex DESC LIMIT 1000");
        $stmt->execute($parametros);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlComprasProveedor($desde, $hasta)
    {
        $sql = "SELECT p.idproveedor, p.nombre_proveedor, COUNT(c.id_compra) AS cantidad_compras,
                       COALESCE(SUM(CASE WHEN c.estado = 'registrada' THEN c.total ELSE 0 END), 0) AS total_vigente,
                       COALESCE(SUM(CASE WHEN c.estado = 'anulada' THEN c.total ELSE 0 END), 0) AS total_anulado
                FROM proveedor p
                LEFT JOIN compra c ON c.id_proveedor = p.idproveedor";
        $parametros = array();
        if ($desde !== null) {
            $sql .= " AND c.fecha >= :desde";
            $parametros[":desde"] = $desde;
        }
        if ($hasta !== null) {
            $sql .= " AND c.fecha <= :hasta";
            $parametros[":hasta"] = $hasta;
        }
        $stmt = Conexion::conectar()->prepare(
            $sql . " GROUP BY p.idproveedor, p.nombre_proveedor ORDER BY p.nombre_proveedor"
        );
        $stmt->execute($parametros);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlHerramientas()
    {
        return Conexion::conectar()->query(
            "SELECT h.idherramienta, h.nombre, h.estado, h.stock,
                    COALESCE(SUM(CASE WHEN k.tipo_movimiento = 'entrada' THEN k.cantidad ELSE 0 END), 0) AS entradas,
                    COALESCE(SUM(CASE WHEN k.tipo_movimiento = 'salida' THEN k.cantidad ELSE 0 END), 0) AS salidas
             FROM herramienta h
             LEFT JOIN kardex k ON k.tipo_item = 'herramienta' AND k.id_item = h.idherramienta
             GROUP BY h.idherramienta, h.nombre, h.estado, h.stock
             ORDER BY h.nombre"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlPedidos($desde, $hasta)
    {
        $pagosActivos = self::columnaExiste("pagos", "estado")
            ? "SUM(CASE WHEN pa.estado = 'registrado' THEN pa.monto ELSE 0 END)"
            : "SUM(pa.monto)";
        $sql = "SELECT p.idpedido, p.fecha_pedido, p.estado, p.total, c.nombre AS cliente,
                       u.nombre AS usuario, COALESCE($pagosActivos, 0) AS total_pagado,
                       p.total - COALESCE($pagosActivos, 0) AS saldo
                FROM pedido p
                INNER JOIN cliente c ON c.idcliente = p.idcliente
                INNER JOIN usuario u ON u.idusuario = p.idusuario
                LEFT JOIN pagos pa ON pa.idpedido = p.idpedido
                WHERE 1 = 1";
        $parametros = array();
        self::agregarFechas($sql, $parametros, $desde, $hasta, "p.fecha_pedido");
        $sql .= " GROUP BY p.idpedido, p.fecha_pedido, p.estado, p.total, c.nombre, u.nombre
                  ORDER BY p.fecha_pedido DESC LIMIT 1000";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlPagos($desde, $hasta)
    {
        $estado = self::columnaExiste("pagos", "estado")
            ? "pa.estado AS estado_pago, pa.motivo_anulacion"
            : "'registrado' AS estado_pago, NULL AS motivo_anulacion";
        $sql = "SELECT pa.idpago, pa.idpedido, pa.fecha_pago, pa.monto, pa.metodo_pago,
                       p.total AS total_pedido, c.nombre AS cliente, $estado
                FROM pagos pa
                INNER JOIN pedido p ON p.idpedido = pa.idpedido
                INNER JOIN cliente c ON c.idcliente = p.idcliente
                WHERE 1 = 1";
        $parametros = array();
        self::agregarFechas($sql, $parametros, $desde, $hasta, "pa.fecha_pago");
        $stmt = Conexion::conectar()->prepare($sql . " ORDER BY pa.fecha_pago DESC LIMIT 1000");
        $stmt->execute($parametros);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function agregarFechas(&$sql, &$parametros, $desde, $hasta, $columna)
    {
        if ($desde !== null) {
            $sql .= " AND $columna >= :desde";
            $parametros[":desde"] = $desde . (strlen($desde) === 10 ? " 00:00:00" : "");
        }
        if ($hasta !== null) {
            $sql .= " AND $columna < DATE_ADD(:hasta, INTERVAL 1 DAY)";
            $parametros[":hasta"] = $hasta . (strlen($hasta) === 10 ? " 00:00:00" : "");
        }
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
