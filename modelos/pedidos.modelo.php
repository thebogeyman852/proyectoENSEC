<?php

require_once "conexion.php";
require_once "auditoria.modelo.php";

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
            $pedidos[$indice]["materiales_planificados"] = array();
            $pedidos[$indice]["materiales_historicos"] = array();
            $pedidos[$indice]["consumos_materiales"] = array();
            $pedidos[$indice]["total_pagado"] = 0.0;
            $indicePedidos[(int) $pedido["idpedido"]] = $indice;
        }

        $detalles = $conexion->query(
            "SELECT d.*, pr.descripcion AS producto, pr.codigo AS codigo_producto
             FROM detalle_pedido d
             INNER JOIN productos pr ON pr.idproducto = d.idproducto
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
                $grupo = ($detalleMaterial["estado_planificacion"] ?? "LEGACY") === "PLANIFICADO"
                    ? "materiales_planificados"
                    : "materiales_historicos";
                $pedidos[$indicePedidos[$idPedido]][$grupo][] = $detalleMaterial;
            }
        }

        $consumos = $conexion->query(
            "SELECT k.id_kardex, k.referencia_id AS idpedido, k.id_item AS idmaterial,
                    k.cantidad, k.stock_anterior, k.stock_resultante, k.fecha, k.motivo,
                    COALESCE(m.nombre, CONCAT('Material #', k.id_item)) AS material,
                    m.unidad_medida, u.nombre AS usuario
             FROM kardex k
             LEFT JOIN materiales m ON m.idmaterial = k.id_item
             LEFT JOIN usuario u ON u.idusuario = k.id_usuario
             WHERE k.tipo_item = 'material'
               AND k.tipo_movimiento = 'salida'
               AND k.referencia_tipo = 'pedido'
             ORDER BY k.fecha DESC, k.id_kardex DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($consumos as $consumo) {
            $idPedido = (int) $consumo["idpedido"];
            if (isset($indicePedidos[$idPedido])) {
                $pedidos[$indicePedidos[$idPedido]]["consumos_materiales"][] = $consumo;
            }
        }

        $estadoPago = $conexion->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pagos' AND COLUMN_NAME = 'estado'"
        )->fetchColumn();
        $pagosSql = (int) $estadoPago === 1
            ? "SELECT idpedido, SUM(CASE WHEN estado = 'registrado' THEN monto ELSE 0 END) AS total_pagado FROM pagos GROUP BY idpedido"
            : "SELECT idpedido, SUM(monto) AS total_pagado FROM pagos GROUP BY idpedido";
        $pagos = $conexion->query($pagosSql)
            ->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($indicePedidos as $idPedido => $indice) {
            $pedidos[$indice]["total_pagado"] = (float) ($pagos[$idPedido] ?? 0);
        }

        return $pedidos;
    }

    public static function mdlMigracionPlanificacionAplicada()
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND (
                    (TABLE_NAME = 'detalle_material' AND COLUMN_NAME = 'estado_planificacion')
                    OR (TABLE_NAME = 'kardex' AND COLUMN_NAME = 'id_solicitud')
               )"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn() === 2;
    }

    public static function mdlCrearPedido($datos)
    {
        if ($datos["materiales"] && !self::mdlMigracionPlanificacionAplicada()) {
            throw new InvalidArgumentException(
                "Para guardar materiales planificados, aplica primero la migración del kardex en la base sistemainventario_v3.2."
            );
        }

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
            self::registrarMaterialesPlanificados($conexion, $idPedido, $datos["materiales"]);

            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $datos["idusuario"],
                "accion" => "crear",
                "modulo" => "pedidos",
                "tabla" => "pedido",
                "id_registro" => $idPedido,
                "anteriores" => null,
                "nuevos" => array(
                    "idcliente" => $datos["idcliente"],
                    "total" => number_format($total, 2, ".", ""),
                    "detalles" => $datos["detalles"],
                    "materiales_planificados" => $datos["materiales"]
                )
            ));
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
        if (!in_array($estado, array("PENDIENTE", "EN_PROCESO", "COMPLETADO", "CANCELADO"), true)) {
            throw new InvalidArgumentException("El estado del pedido no es válido.");
        }

        $conexion = Conexion::conectar();
        $conexion->beginTransaction();

        try {
            $pedido = $conexion->prepare("SELECT estado FROM pedido WHERE idpedido = :id FOR UPDATE");
            $pedido->execute(array(":id" => $idPedido));
            $actual = $pedido->fetch(PDO::FETCH_ASSOC);
            if (!$actual) {
                throw new InvalidArgumentException("El pedido seleccionado no existe.");
            }

            $devoluciones = $estado === "CANCELADO"
                ? self::mdlDevolverConsumosPedido($conexion, (int) $idPedido)
                : 0;

            if ($actual["estado"] !== $estado) {
                $actualizar = $conexion->prepare("UPDATE pedido SET estado = :estado WHERE idpedido = :id");
                $actualizar->execute(array(":estado" => $estado, ":id" => $idPedido));
            } elseif ($estado === "CANCELADO" && $devoluciones === 0) {
                $conexion->commit();
                return "ok";
            }

            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $_SESSION["idusuario"] ?? null,
                "accion" => "cambiar_estado",
                "modulo" => "pedidos",
                "tabla" => "pedido",
                "id_registro" => (int) $idPedido,
                "anteriores" => $actual,
                "nuevos" => array(
                    "estado" => $estado,
                    "devoluciones_kardex" => $devoluciones
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

    private static function mdlDevolverConsumosPedido($conexion, $idPedido)
    {
        $consumosStmt = $conexion->prepare(
            "SELECT id_kardex, id_item, cantidad
             FROM kardex
             WHERE tipo_item = 'material'
               AND tipo_movimiento = 'salida'
               AND referencia_tipo = 'pedido'
               AND referencia_id = :idpedido
             ORDER BY id_kardex
             FOR UPDATE"
        );
        $consumosStmt->execute(array(":idpedido" => $idPedido));
        $consumos = $consumosStmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$consumos) {
            return 0;
        }

        $idSolicitudDisponible = $conexion->prepare(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'kardex'
               AND COLUMN_NAME = 'id_solicitud'"
        );
        $idSolicitudDisponible->execute();
        if ((int) $idSolicitudDisponible->fetchColumn() !== 1) {
            throw new InvalidArgumentException(
                "No se pueden devolver consumos sin la clave de idempotencia del kardex. Aplica la migración documentada en una copia de pruebas."
            );
        }

        $idUsuario = filter_var($_SESSION["idusuario"] ?? null, FILTER_VALIDATE_INT);
        if ($idUsuario === false || $idUsuario < 1) {
            throw new InvalidArgumentException("No se pudo identificar al usuario que cancela el pedido.");
        }

        $buscarDevolucion = $conexion->prepare(
            "SELECT tipo_item, id_item, tipo_movimiento, cantidad, referencia_tipo, referencia_id, motivo
             FROM kardex
             WHERE id_solicitud = :solicitud
             FOR UPDATE"
        );
        $buscarStock = $conexion->prepare(
            "SELECT stock_actual FROM materiales WHERE idmaterial = :id FOR UPDATE"
        );
        $actualizarStock = $conexion->prepare(
            "UPDATE materiales SET stock_actual = :stock WHERE idmaterial = :id"
        );
        $insertarDevolucion = $conexion->prepare(
            "INSERT INTO kardex
                (id_solicitud, tipo_item, id_item, tipo_movimiento, cantidad,
                 stock_anterior, stock_resultante, referencia_tipo, referencia_id,
                 motivo, id_usuario)
             VALUES
                (:solicitud, 'material', :idmaterial, 'entrada', :cantidad,
                 :stock_anterior, :stock_resultante, 'pedido', :idpedido, :motivo, :idusuario)"
        );
        $stocks = array();
        $devoluciones = 0;
        foreach ($consumos as $consumo) {
            $idKardexOrigen = (int) $consumo["id_kardex"];
            $idMaterial = (int) $consumo["id_item"];
            $cantidad = self::aCentimos($consumo["cantidad"]);
            if ($cantidad <= 0) {
                throw new InvalidArgumentException("Un consumo del pedido tiene una cantidad no válida.");
            }

            $idSolicitud = substr(
                hash("sha256", "devolucion-cancelacion-pedido-" . $idPedido . "-" . $idKardexOrigen),
                0,
                32
            );
            $motivo = "Devolución por cancelación del pedido #" . $idPedido .
                " (consumo kardex #" . $idKardexOrigen . ")";
            $buscarDevolucion->execute(array(":solicitud" => $idSolicitud));
            $devolucionExistente = $buscarDevolucion->fetch(PDO::FETCH_ASSOC);
            if ($devolucionExistente) {
                if (
                    $devolucionExistente["tipo_item"] !== "material" ||
                    (int) $devolucionExistente["id_item"] !== $idMaterial ||
                    $devolucionExistente["tipo_movimiento"] !== "entrada" ||
                    self::aCentimos($devolucionExistente["cantidad"]) !== $cantidad ||
                    $devolucionExistente["referencia_tipo"] !== "pedido" ||
                    (int) $devolucionExistente["referencia_id"] !== $idPedido ||
                    $devolucionExistente["motivo"] !== $motivo
                ) {
                    throw new InvalidArgumentException(
                        "Existe un movimiento con la clave de devolución del consumo que no coincide; no se modificó el pedido."
                    );
                }
                continue;
            }

            if (!array_key_exists($idMaterial, $stocks)) {
                $buscarStock->execute(array(":id" => $idMaterial));
                $stock = $buscarStock->fetchColumn();
                if ($stock === false) {
                    throw new InvalidArgumentException(
                        "No se puede cancelar: falta el material de un consumo registrado."
                    );
                }
                $stocks[$idMaterial] = self::aCentimos($stock);
            }

            $stockAnterior = $stocks[$idMaterial];
            $stockResultante = $stockAnterior + $cantidad;
            if ($stockResultante > 9999999999) {
                throw new InvalidArgumentException(
                    "La devolución excede la capacidad de stock del material; no se canceló el pedido."
                );
            }
            $actualizarStock->execute(array(
                ":stock" => number_format($stockResultante / 100, 2, ".", ""),
                ":id" => $idMaterial
            ));
            $insertarDevolucion->execute(array(
                ":solicitud" => $idSolicitud,
                ":idmaterial" => $idMaterial,
                ":cantidad" => number_format($cantidad / 100, 2, ".", ""),
                ":stock_anterior" => number_format($stockAnterior / 100, 2, ".", ""),
                ":stock_resultante" => number_format($stockResultante / 100, 2, ".", ""),
                ":idpedido" => $idPedido,
                ":motivo" => $motivo,
                ":idusuario" => $idUsuario
            ));
            $stocks[$idMaterial] = $stockResultante;
            $devoluciones++;
        }

        return $devoluciones;
    }

    public static function mdlEditarPedido($idPedido, $datos)
    {
        $planificacionMigrada = self::mdlMigracionPlanificacionAplicada();
        if (!$planificacionMigrada && $datos["materiales"]) {
            throw new InvalidArgumentException(
                "Para cambiar materiales planificados, aplica primero la migración del kardex en la base sistemainventario_v3.2."
            );
        }

        $conexion = Conexion::conectar();
        $conexion->beginTransaction();

        try {
            $actual = $conexion->prepare("SELECT idpedido, estado FROM pedido WHERE idpedido = :id FOR UPDATE");
            $actual->execute(array(":id" => $idPedido));
            $pedidoActual = $actual->fetch(PDO::FETCH_ASSOC);
            if (!$pedidoActual) {
                throw new InvalidArgumentException("El pedido seleccionado no existe.");
            }

            $total = self::calcularTotal($datos["detalles"]);
            $estadoPago = $conexion->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'pagos'
                   AND COLUMN_NAME = 'estado'"
            );
            $estadoPago->execute();
            $sqlPagos = (int) $estadoPago->fetchColumn() === 1
                ? "SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE idpedido = :id AND estado = 'registrado'"
                : "SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE idpedido = :id";
            $pagos = $conexion->prepare($sqlPagos);
            $pagos->execute(array(":id" => $idPedido));
            if ($total < (float) $pagos->fetchColumn()) {
                throw new InvalidArgumentException("El nuevo total no puede ser menor que los pagos registrados.");
            }

            $detallesAnteriores = $conexion->prepare("SELECT * FROM detalle_pedido WHERE idpedido = :id");
            $detallesAnteriores->execute(array(":id" => $idPedido));
            $datosAnteriores = array(
                "pedido" => $pedidoActual,
                "detalles" => $detallesAnteriores->fetchAll(PDO::FETCH_ASSOC)
            );
            $materialesAnteriores = $conexion->prepare("SELECT * FROM detalle_material WHERE idpedido = :id");
            $materialesAnteriores->execute(array(":id" => $idPedido));
            $datosAnteriores["materiales"] = $materialesAnteriores->fetchAll(PDO::FETCH_ASSOC);

            if ($planificacionMigrada) {
                $conexion->prepare(
                    "DELETE FROM detalle_material
                     WHERE idpedido = :id AND estado_planificacion = 'PLANIFICADO'"
                )->execute(array(":id" => $idPedido));
            }
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
            if ($planificacionMigrada) {
                self::registrarMaterialesPlanificados($conexion, $idPedido, $datos["materiales"]);
            }
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $_SESSION["idusuario"] ?? null,
                "accion" => "editar",
                "modulo" => "pedidos",
                "tabla" => "pedido",
                "id_registro" => (int) $idPedido,
                "anteriores" => $datosAnteriores,
                "nuevos" => array(
                    "idcliente" => $datos["idcliente"],
                    "total" => number_format($total, 2, ".", ""),
                    "detalles" => $datos["detalles"],
                    "materiales_planificados" => $datos["materiales"]
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

    public static function mdlBorrarPedido($idPedido)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();

        try {
            $pedido = $conexion->prepare("SELECT * FROM pedido WHERE idpedido = :id FOR UPDATE");
            $pedido->execute(array(":id" => $idPedido));
            $pedidoActual = $pedido->fetch(PDO::FETCH_ASSOC);
            if (!$pedidoActual) {
                throw new InvalidArgumentException("El pedido seleccionado no existe.");
            }

            $dependencias = $conexion->prepare(
                "SELECT
                    (SELECT COUNT(*) FROM pagos WHERE idpedido = :idpagos) +
                    (SELECT COUNT(*) FROM detalle_pedido WHERE idpedido = :iddetalles) +
                    (SELECT COUNT(*) FROM detalle_material WHERE idpedido = :idmateriales) +
                    (SELECT COUNT(*) FROM kardex
                     WHERE referencia_tipo = 'pedido' AND referencia_id = :idkardex)"
            );
            $dependencias->execute(array(
                ":idpagos" => $idPedido,
                ":iddetalles" => $idPedido,
                ":idmateriales" => $idPedido,
                ":idkardex" => $idPedido
            ));
            if ((int) $dependencias->fetchColumn() > 0) {
                throw new InvalidArgumentException(
                    "No se puede eliminar el pedido porque tiene pagos o registros de materiales/inventario asociados."
                );
            }

            $borrar = $conexion->prepare("DELETE FROM pedido WHERE idpedido = :id");
            $borrar->bindValue(":id", $idPedido, PDO::PARAM_INT);
            $borrar->execute();
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $_SESSION["idusuario"] ?? null,
                "accion" => "eliminar",
                "modulo" => "pedidos",
                "tabla" => "pedido",
                "id_registro" => (int) $idPedido,
                "anteriores" => $pedidoActual,
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
            "INSERT INTO detalle_pedido (idpedido, idproducto, cantidad, precio_unitario)
             VALUES (:idpedido, :idproducto, :cantidad, :precio_unitario)"
        );
        foreach ($detalles as $detalle) {
            $stmt->execute(array(
                ":idpedido" => $idPedido,
                ":idproducto" => $detalle["idproducto"],
                ":cantidad" => $detalle["cantidad"],
                ":precio_unitario" => number_format($detalle["precio_unitario"], 2, ".", "")
            ));
        }
    }

    private static function registrarMaterialesPlanificados($conexion, $idPedido, $materiales)
    {
        $stmt = $conexion->prepare(
            "INSERT INTO detalle_material
                (idpedido, idmaterial, cantidad_usada, estado_planificacion)
             VALUES (:idpedido, :idmaterial, :cantidad, 'PLANIFICADO')"
        );
        foreach ($materiales as $detalle) {
            $stmt->execute(array(
                ":idpedido" => $idPedido,
                ":idmaterial" => $detalle["idmaterial"],
                ":cantidad" => number_format($detalle["cantidad_usada"], 2, ".", "")
            ));
        }
    }

    public static function mdlRegistrarConsumoMaterial($datos)
    {
        if (!self::mdlMigracionPlanificacionAplicada()) {
            throw new InvalidArgumentException(
                "El kardex no está preparado en esta base. Aplica la migración documentada en Documentacion/Migraciones."
            );
        }

        $idPedido = filter_var($datos["idpedido"] ?? null, FILTER_VALIDATE_INT);
        $idMaterial = filter_var($datos["idmaterial"] ?? null, FILTER_VALIDATE_INT);
        $idUsuario = filter_var($datos["idusuario"] ?? null, FILTER_VALIDATE_INT);
        $solicitudEntrada = $datos["id_solicitud"] ?? null;
        $cantidadEntrada = $datos["cantidad"] ?? null;
        $motivoEntrada = $datos["motivo"] ?? null;
        $idSolicitud = is_string($solicitudEntrada) ? $solicitudEntrada : "";
        $cantidadTexto = is_string($cantidadEntrada) ? $cantidadEntrada : "";
        $motivo = is_string($motivoEntrada) ? trim($motivoEntrada) : "";

        if (
            $idPedido === false || $idPedido < 1 ||
            $idMaterial === false || $idMaterial < 1 ||
            $idUsuario === false || $idUsuario < 1 ||
            !preg_match('/^[a-f0-9]{32}$/', $idSolicitud) ||
            !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $cantidadTexto) ||
            (float) $cantidadTexto <= 0 ||
            $motivo === "" || mb_strlen($motivo) > 255
        ) {
            throw new InvalidArgumentException("Los datos del consumo no son válidos.");
        }
        $datos["idpedido"] = $idPedido;
        $datos["idmaterial"] = $idMaterial;
        $datos["idusuario"] = $idUsuario;
        $datos["id_solicitud"] = $idSolicitud;
        $datos["cantidad"] = $cantidadTexto;
        $datos["motivo"] = $motivo;

        $conexion = Conexion::conectar();
        $conexion->beginTransaction();

        try {
            $repetido = $conexion->prepare(
                "SELECT id_kardex FROM kardex WHERE id_solicitud = :solicitud FOR UPDATE"
            );
            $repetido->execute(array(":solicitud" => $datos["id_solicitud"]));
            if ($repetido->fetchColumn()) {
                throw new InvalidArgumentException("Este consumo ya fue registrado.");
            }

            $pedido = $conexion->prepare("SELECT estado FROM pedido WHERE idpedido = :id FOR UPDATE");
            $pedido->execute(array(":id" => $datos["idpedido"]));
            $pedidoEncontrado = $pedido->fetch(PDO::FETCH_ASSOC);
            if (!$pedidoEncontrado) {
                throw new InvalidArgumentException("El pedido seleccionado no existe.");
            }
            if ($pedidoEncontrado["estado"] === "CANCELADO") {
                throw new InvalidArgumentException("No se pueden registrar consumos en un pedido cancelado.");
            }

            $materialStmt = $conexion->prepare(
                "SELECT stock_actual FROM materiales WHERE idmaterial = :id FOR UPDATE"
            );
            $materialStmt->execute(array(":id" => $datos["idmaterial"]));
            $material = $materialStmt->fetch(PDO::FETCH_ASSOC);
            if (!$material) {
                throw new InvalidArgumentException("El material seleccionado no existe.");
            }

            $stockAnterior = self::aCentimos($material["stock_actual"]);
            $cantidad = self::aCentimos($datos["cantidad"]);
            if ($cantidad < 1 || $stockAnterior < $cantidad) {
                throw new InvalidArgumentException("El material no tiene stock suficiente.");
            }

            $stockResultante = $stockAnterior - $cantidad;
            $actualizar = $conexion->prepare(
                "UPDATE materiales SET stock_actual = :stock WHERE idmaterial = :id"
            );
            $actualizar->execute(array(
                ":stock" => number_format($stockResultante / 100, 2, ".", ""),
                ":id" => $datos["idmaterial"]
            ));

            $insertar = $conexion->prepare(
                "INSERT INTO kardex
                    (id_solicitud, tipo_item, id_item, tipo_movimiento, cantidad,
                     stock_anterior, stock_resultante, referencia_tipo, referencia_id,
                     motivo, id_usuario)
                 VALUES
                    (:solicitud, 'material', :idmaterial, 'salida', :cantidad,
                     :stock_anterior, :stock_resultante, 'pedido', :idpedido, :motivo, :idusuario)"
            );
            $insertar->execute(array(
                ":solicitud" => $datos["id_solicitud"],
                ":idmaterial" => $datos["idmaterial"],
                ":cantidad" => number_format($cantidad / 100, 2, ".", ""),
                ":stock_anterior" => number_format($stockAnterior / 100, 2, ".", ""),
                ":stock_resultante" => number_format($stockResultante / 100, 2, ".", ""),
                ":idpedido" => $datos["idpedido"],
                ":motivo" => trim($datos["motivo"]),
                ":idusuario" => $datos["idusuario"]
            ));

            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $datos["idusuario"],
                "accion" => "consumir_material",
                "modulo" => "pedidos",
                "tabla" => "kardex",
                "id_registro" => (int) $conexion->lastInsertId(),
                "anteriores" => array("stock" => number_format($stockAnterior / 100, 2, ".", "")),
                "nuevos" => array(
                    "idpedido" => $datos["idpedido"],
                    "idmaterial" => $datos["idmaterial"],
                    "cantidad" => number_format($cantidad / 100, 2, ".", ""),
                    "stock_resultante" => number_format($stockResultante / 100, 2, ".", ""),
                    "motivo" => $datos["motivo"]
                )
            ));
            $conexion->commit();
            return "ok";
        } catch (PDOException $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            if ($error->getCode() === "23000" && (int) ($error->errorInfo[1] ?? 0) === 1062) {
                throw new InvalidArgumentException("Este consumo ya fue registrado.", 0, $error);
            }
            throw $error;
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    private static function aCentimos($valor)
    {
        $texto = (string) $valor;
        if (!preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $texto, $partes)) {
            throw new InvalidArgumentException("El stock o la cantidad tienen un formato no válido.");
        }
        return ((int) $partes[1] * 100) + (int) str_pad($partes[2] ?? "", 2, "0");
    }
}
