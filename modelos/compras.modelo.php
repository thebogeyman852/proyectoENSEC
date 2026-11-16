<?php

require_once "conexion.php";

class ModeloCompras
{
    private static $articulos = array(
        "material" => array("tabla" => "materiales", "id" => "idmaterial", "stock" => "stock_actual"),
        "herramienta" => array("tabla" => "herramienta", "id" => "idherramienta", "stock" => "stock")
    );

    public static function mdlListarCompras()
    {
        return Conexion::conectar()->query(
            "SELECT c.*, p.nombre_proveedor, u.nombre AS usuario
             FROM compra c
             INNER JOIN proveedor p ON p.idproveedor = c.id_proveedor
             LEFT JOIN usuario u ON u.idusuario = c.id_usuario
             ORDER BY c.fecha DESC, c.id_compra DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlDetalleCompra($idCompra)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT d.*, CASE d.tipo_item
                 WHEN 'material' THEN m.nombre
                 WHEN 'herramienta' THEN h.nombre
             END AS articulo
             FROM detalle_compra d
             LEFT JOIN materiales m ON d.tipo_item = 'material' AND m.idmaterial = d.id_item
             LEFT JOIN herramienta h ON d.tipo_item = 'herramienta' AND h.idherramienta = d.id_item
             WHERE d.id_compra = :id
             ORDER BY d.id_detalle"
        );
        $stmt->execute(array(":id" => $idCompra));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlRegistrarCompra($datos)
    {
        self::validarIdempotencia();
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            $existente = $conexion->prepare("SELECT id_compra FROM compra WHERE id_solicitud = :solicitud");
            $existente->execute(array(":solicitud" => $datos["id_solicitud"]));
            if ($existente->fetchColumn()) {
                throw new InvalidArgumentException("Esta compra ya fue registrada; no se duplicó.");
            }

            $proveedor = $conexion->prepare("SELECT idproveedor FROM proveedor WHERE idproveedor = :id FOR UPDATE");
            $proveedor->execute(array(":id" => $datos["id_proveedor"]));
            if (!$proveedor->fetchColumn()) {
                throw new InvalidArgumentException("El proveedor seleccionado no existe.");
            }

            $totalCentimos = 0;
            $stocksActuales = array();
            $detalles = array();
            foreach ($datos["detalles"] as $detalle) {
                $configuracion = self::articulo($detalle["tipo_item"]);
                if ($detalle["tipo_item"] === "herramienta" && $detalle["cantidad_centimos"] % 100 !== 0) {
                    throw new InvalidArgumentException("La cantidad de herramientas debe ser un número entero.");
                }
                $claveItem = $detalle["tipo_item"] . ":" . $detalle["id_item"];
                if (!array_key_exists($claveItem, $stocksActuales)) {
                    $stocksActuales[$claveItem] = self::aCentimos(
                        self::bloquearStockCompra(
                            $conexion,
                            $configuracion,
                            $detalle["id_item"],
                            $datos["id_proveedor"]
                        )
                    );
                }
                $subtotal = self::subtotalCentimos($detalle["cantidad_centimos"], $detalle["costo_centimos"]);
                $totalCentimos += $subtotal;
                if ($totalCentimos > 999999999999) {
                    throw new InvalidArgumentException("El total supera el máximo permitido por la base de datos.");
                }
                $detalle["stock_anterior_centimos"] = $stocksActuales[$claveItem];
                $detalle["stock_resultante_centimos"] = $detalle["stock_anterior_centimos"] + $detalle["cantidad_centimos"];
                self::validarMaximoStock($detalle["tipo_item"], $detalle["stock_resultante_centimos"]);
                $stocksActuales[$claveItem] = $detalle["stock_resultante_centimos"];
                $detalle["subtotal_centimos"] = $subtotal;
                $detalle["configuracion"] = $configuracion;
                $detalles[] = $detalle;
            }
            if (empty($detalles)) {
                throw new InvalidArgumentException("La compra debe contener al menos un artículo.");
            }

            $insertarCompra = $conexion->prepare(
                "INSERT INTO compra
                    (id_solicitud, id_proveedor, fecha, nro_documento, total, observacion, id_usuario)
                 VALUES (:solicitud, :proveedor, :fecha, :documento, :total, :observacion, :usuario)"
            );
            $insertarCompra->execute(array(
                ":solicitud" => $datos["id_solicitud"],
                ":proveedor" => $datos["id_proveedor"],
                ":fecha" => $datos["fecha"],
                ":documento" => $datos["nro_documento"],
                ":total" => self::aDecimal($totalCentimos),
                ":observacion" => $datos["observacion"],
                ":usuario" => $datos["id_usuario"]
            ));
            $idCompra = (int) $conexion->lastInsertId();

            $insertarDetalle = $conexion->prepare(
                "INSERT INTO detalle_compra (id_compra, tipo_item, id_item, cantidad, costo_unitario)
                 VALUES (:compra, :tipo, :item, :cantidad, :costo)"
            );
            $insertarKardex = $conexion->prepare(
                "INSERT INTO kardex
                    (id_solicitud, tipo_item, id_item, tipo_movimiento, cantidad, stock_anterior,
                     stock_resultante, costo_unitario, referencia_tipo, referencia_id, motivo, id_usuario)
                 VALUES
                    (:solicitud, :tipo, :item, 'entrada', :cantidad, :anterior,
                     :resultante, :costo, 'compra', :compra, :motivo, :usuario)"
            );

            foreach ($detalles as $detalle) {
                self::actualizarStock(
                    $conexion,
                    $detalle["configuracion"],
                    $detalle["id_item"],
                    $detalle["stock_resultante_centimos"]
                );
                $insertarDetalle->execute(array(
                    ":compra" => $idCompra,
                    ":tipo" => $detalle["tipo_item"],
                    ":item" => $detalle["id_item"],
                    ":cantidad" => self::aDecimal($detalle["cantidad_centimos"]),
                    ":costo" => self::aDecimal($detalle["costo_centimos"])
                ));
                $insertarKardex->execute(array(
                    ":solicitud" => bin2hex(random_bytes(16)),
                    ":tipo" => $detalle["tipo_item"],
                    ":item" => $detalle["id_item"],
                    ":cantidad" => self::aDecimal($detalle["cantidad_centimos"]),
                    ":anterior" => self::aDecimal($detalle["stock_anterior_centimos"]),
                    ":resultante" => self::aDecimal($detalle["stock_resultante_centimos"]),
                    ":costo" => self::aDecimal($detalle["costo_centimos"]),
                    ":compra" => $idCompra,
                    ":motivo" => "Entrada por compra " . ($datos["nro_documento"] ?: "#" . $idCompra),
                    ":usuario" => $datos["id_usuario"]
                ));
            }
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $datos["id_usuario"],
                "accion" => "crear",
                "modulo" => "compras",
                "tabla" => "compra",
                "id_registro" => $idCompra,
                "anteriores" => null,
                "nuevos" => array(
                    "proveedor" => $datos["id_proveedor"],
                    "total" => self::aDecimal($totalCentimos),
                    "detalles" => count($detalles),
                    "documento" => $datos["nro_documento"]
                )
            ));
            $conexion->commit();
            return $idCompra;
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    public static function mdlAnularCompra($idCompra, $idUsuario, $motivo)
    {
        self::validarIdempotencia();
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            $compra = $conexion->prepare("SELECT estado, nro_documento FROM compra WHERE id_compra = :id FOR UPDATE");
            $compra->execute(array(":id" => $idCompra));
            $registro = $compra->fetch(PDO::FETCH_ASSOC);
            if (!$registro) {
                throw new InvalidArgumentException("La compra seleccionada no existe.");
            }
            if ($registro["estado"] !== "registrada") {
                throw new InvalidArgumentException("La compra ya fue anulada.");
            }

            $detalles = $conexion->prepare("SELECT * FROM detalle_compra WHERE id_compra = :id ORDER BY id_detalle");
            $detalles->execute(array(":id" => $idCompra));
            $filas = $detalles->fetchAll(PDO::FETCH_ASSOC);
            foreach ($filas as $fila) {
                $configuracion = self::articulo($fila["tipo_item"]);
                $stockAnterior = self::aCentimos(self::bloquearStock($conexion, $configuracion, (int) $fila["id_item"]));
                $cantidad = self::aCentimos($fila["cantidad"]);
                if ($stockAnterior < $cantidad) {
                    throw new InvalidArgumentException(
                        "No se puede anular: el artículo de la compra ya se consumió o su stock bajó de la cantidad comprada."
                    );
                }
                $stockNuevo = $stockAnterior - $cantidad;
                self::actualizarStock($conexion, $configuracion, (int) $fila["id_item"], $stockNuevo);
                $kardex = $conexion->prepare(
                    "INSERT INTO kardex
                        (id_solicitud, tipo_item, id_item, tipo_movimiento, cantidad, stock_anterior,
                         stock_resultante, costo_unitario, referencia_tipo, referencia_id, motivo, id_usuario)
                     VALUES
                        (:solicitud, :tipo, :item, 'salida', :cantidad, :anterior,
                         :resultante, :costo, 'compra', :compra, :motivo, :usuario)"
                );
                $kardex->execute(array(
                    ":solicitud" => substr(hash("sha256", "anulacion-compra-" . $idCompra . "-" . $fila["id_detalle"]), 0, 32),
                    ":tipo" => $fila["tipo_item"],
                    ":item" => $fila["id_item"],
                    ":cantidad" => self::aDecimal($cantidad),
                    ":anterior" => self::aDecimal($stockAnterior),
                    ":resultante" => self::aDecimal($stockNuevo),
                    ":costo" => $fila["costo_unitario"],
                    ":compra" => $idCompra,
                    ":motivo" => "Reversión de compra #" . $idCompra . ": " . $motivo,
                    ":usuario" => $idUsuario
                ));
            }
            $actualizar = $conexion->prepare("UPDATE compra SET estado = 'anulada' WHERE id_compra = :id");
            $actualizar->execute(array(":id" => $idCompra));
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $idUsuario,
                "accion" => "anular",
                "modulo" => "compras",
                "tabla" => "compra",
                "id_registro" => $idCompra,
                "anteriores" => array("estado" => "registrada"),
                "nuevos" => array("estado" => "anulada", "motivo" => $motivo)
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

    private static function validarIdempotencia()
    {
        foreach (array("compra" => "id_solicitud", "kardex" => "id_solicitud") as $tabla => $columna) {
            $stmt = Conexion::conectar()->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla AND COLUMN_NAME = :columna"
            );
            $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
            if ((int) $stmt->fetchColumn() !== 1) {
                throw new RuntimeException("Falta aplicar la migración de idempotencia para compras en una copia de pruebas.");
            }
        }
    }

    private static function articulo($tipo)
    {
        if (!isset(self::$articulos[$tipo])) {
            throw new InvalidArgumentException("Las compras solo admiten materiales y herramientas; no se maneja stock habitual de muebles terminados.");
        }
        return self::$articulos[$tipo];
    }

    private static function validarMaximoStock($tipo, $stockCentimos)
    {
        $maximo = $tipo === "material" ? 9999999999 : 214748364700;
        if ($stockCentimos < 0 || $stockCentimos > $maximo) {
            throw new InvalidArgumentException("El stock resultante excede la capacidad de la columna actual.");
        }
    }

    private static function bloquearStock($conexion, $configuracion, $id)
    {
        $stmt = $conexion->prepare(
            "SELECT " . $configuracion["stock"] . " FROM " . $configuracion["tabla"] .
            " WHERE " . $configuracion["id"] . " = :id FOR UPDATE"
        );
        $stmt->execute(array(":id" => $id));
        $valor = $stmt->fetchColumn();
        if ($valor === false) {
            throw new InvalidArgumentException("Uno de los artículos no existe.");
        }
        return $valor;
    }

    private static function bloquearStockCompra($conexion, $configuracion, $id, $idProveedor)
    {
        if ($configuracion["tabla"] !== "materiales") {
            return self::bloquearStock($conexion, $configuracion, $id);
        }

        $stmt = $conexion->prepare(
            "SELECT stock_actual, idproveedor FROM materiales WHERE idmaterial = :id FOR UPDATE"
        );
        $stmt->execute(array(":id" => $id));
        $material = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$material) {
            throw new InvalidArgumentException("Uno de los artículos no existe.");
        }
        if ((int) $material["idproveedor"] !== (int) $idProveedor) {
            throw new InvalidArgumentException("El material no pertenece al proveedor seleccionado.");
        }
        return $material["stock_actual"];
    }

    private static function actualizarStock($conexion, $configuracion, $id, $stock)
    {
        $stmt = $conexion->prepare(
            "UPDATE " . $configuracion["tabla"] . " SET " . $configuracion["stock"] . " = :stock" .
            " WHERE " . $configuracion["id"] . " = :id"
        );
        $stmt->execute(array(":stock" => self::aDecimal($stock), ":id" => $id));
    }

    private static function aCentimos($valor)
    {
        if (!preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', (string) $valor, $partes)) {
            throw new InvalidArgumentException("Cantidad almacenada no válida.");
        }
        return (int) $partes[1] * 100 + (int) str_pad($partes[2] ?? "0", 2, "0");
    }

    private static function subtotalCentimos($cantidad, $costo)
    {
        return intdiv(($cantidad * $costo) + 50, 100);
    }

    private static function aDecimal($centimos)
    {
        $absoluto = abs((int) $centimos);
        return ($centimos < 0 ? "-" : "") . intdiv($absoluto, 100) . "." .
            str_pad((string) ($absoluto % 100), 2, "0", STR_PAD_LEFT);
    }
}
