<?php

require_once "conexion.php";

class ModeloInventario
{
    private static $articulos = array(
        "material" => array("tabla" => "materiales", "id" => "idmaterial", "stock" => "stock_actual"),
        "herramienta" => array("tabla" => "herramienta", "id" => "idherramienta", "stock" => "stock")
    );

    public static function mdlListarArticulos($tipo)
    {
        $configuracion = self::articulo($tipo);
        $conexion = Conexion::conectar();
        $sql = $tipo === "material"
            ? "SELECT idmaterial AS id, nombre, stock_actual AS stock, unidad_medida AS unidad,
                      idproveedor AS id_proveedor
               FROM materiales ORDER BY nombre"
            : "SELECT idherramienta AS id, nombre, stock, 'unidad' AS unidad
               FROM herramienta ORDER BY nombre";
        return $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlListarMovimientos($filtros = array())
    {
        if (
            !empty($filtros["tipo_item"]) &&
            !in_array($filtros["tipo_item"], array("material", "herramienta", "producto"), true)
        ) {
            $filtros["tipo_item"] = "";
        }
        if (
            !empty($filtros["tipo_movimiento"]) &&
            !in_array($filtros["tipo_movimiento"], array("entrada", "salida"), true)
        ) {
            $filtros["tipo_movimiento"] = "";
        }
        foreach (array("desde", "hasta") as $fecha) {
            if (!empty($filtros[$fecha]) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filtros[$fecha])) {
                $filtros[$fecha] = "";
            }
        }
        if (!empty($filtros["id_usuario"]) && filter_var($filtros["id_usuario"], FILTER_VALIDATE_INT) === false) {
            $filtros["id_usuario"] = "";
        }
        if (!empty($filtros["id_item"]) && filter_var($filtros["id_item"], FILTER_VALIDATE_INT) === false) {
            $filtros["id_item"] = "";
        }
        $sql = "SELECT k.id_kardex, k.fecha, k.tipo_item, k.id_item, k.tipo_movimiento,
                       k.cantidad, k.stock_anterior, k.stock_resultante, k.referencia_tipo,
                       k.referencia_id, k.motivo, k.id_usuario, u.nombre AS usuario,
                       CASE k.tipo_item
                           WHEN 'material' THEN m.nombre
                           WHEN 'herramienta' THEN h.nombre
                           WHEN 'producto' THEN p.descripcion
                       END AS articulo
                FROM kardex k
                LEFT JOIN usuario u ON u.idusuario = k.id_usuario
                LEFT JOIN materiales m ON k.tipo_item = 'material' AND m.idmaterial = k.id_item
                LEFT JOIN herramienta h ON k.tipo_item = 'herramienta' AND h.idherramienta = k.id_item
                LEFT JOIN productos p ON k.tipo_item = 'producto' AND p.idproducto = k.id_item
                WHERE 1 = 1";
        $parametros = array();
        foreach (array("tipo_item", "tipo_movimiento", "id_usuario") as $campo) {
            if (!empty($filtros[$campo])) {
                $sql .= " AND k.$campo = :$campo";
                $parametros[":" . $campo] = $filtros[$campo];
            }
        }
        if (!empty($filtros["id_item"])) {
            $sql .= " AND k.id_item = :id_item";
            $parametros[":id_item"] = (int) $filtros["id_item"];
        }
        if (!empty($filtros["desde"])) {
            $sql .= " AND k.fecha >= :desde";
            $parametros[":desde"] = $filtros["desde"] . " 00:00:00";
        }
        if (!empty($filtros["hasta"])) {
            $sql .= " AND k.fecha < DATE_ADD(:hasta, INTERVAL 1 DAY)";
            $parametros[":hasta"] = $filtros["hasta"] . " 00:00:00";
        }
        $sql .= " ORDER BY k.fecha DESC, k.id_kardex DESC LIMIT 1000";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlUsuarios()
    {
        return Conexion::conectar()->query("SELECT idusuario, nombre FROM usuario WHERE estado = 1 ORDER BY nombre")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlRegistrarMovimiento($datos)
    {
        $configuracion = self::articulo($datos["tipo_item"]);
        self::validarIdSolicitudDisponible();
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            self::validarSolicitudNueva($conexion, $datos["id_solicitud"]);
            if ($datos["tipo_movimiento"] === "salida" && $datos["referencia_tipo"] === "pedido") {
                $idPedido = filter_var($datos["referencia_id"] ?? null, FILTER_VALIDATE_INT);
                if ($idPedido === false || $idPedido < 1) {
                    throw new InvalidArgumentException("Indica un pedido válido para registrar esta salida.");
                }
                $pedido = $conexion->prepare("SELECT estado FROM pedido WHERE idpedido = :id FOR UPDATE");
                $pedido->execute(array(":id" => $idPedido));
                $pedidoEncontrado = $pedido->fetch(PDO::FETCH_ASSOC);
                if (!$pedidoEncontrado) {
                    throw new InvalidArgumentException("El pedido asociado a la salida no existe.");
                }
                if ($pedidoEncontrado["estado"] === "CANCELADO") {
                    throw new InvalidArgumentException("No se pueden registrar salidas para un pedido cancelado.");
                }
            }
            $stock = self::bloquearStock($conexion, $configuracion, $datos["id_item"]);
            $cantidadCentimos = self::aCentimos($datos["cantidad"]);
            $stockCentimos = self::aCentimos($stock);
            if ($cantidadCentimos <= 0) {
                throw new InvalidArgumentException("La cantidad debe ser mayor que cero.");
            }
            if ($datos["tipo_item"] === "herramienta" && $cantidadCentimos % 100 !== 0) {
                throw new InvalidArgumentException("Las herramientas se controlan en unidades enteras.");
            }
            if ($datos["tipo_movimiento"] === "salida" && $cantidadCentimos > $stockCentimos) {
                throw new InvalidArgumentException("La salida supera las existencias disponibles.");
            }
            $resultado = $datos["tipo_movimiento"] === "entrada"
                ? $stockCentimos + $cantidadCentimos
                : $stockCentimos - $cantidadCentimos;
            self::validarMaximoStock($datos["tipo_item"], $resultado);
            self::actualizarStock($conexion, $configuracion, $datos["id_item"], $resultado);
            self::insertarKardex($conexion, array(
                "id_solicitud" => $datos["id_solicitud"],
                "tipo_item" => $datos["tipo_item"],
                "id_item" => $datos["id_item"],
                "tipo_movimiento" => $datos["tipo_movimiento"],
                "cantidad" => $cantidadCentimos,
                "stock_anterior" => $stockCentimos,
                "stock_resultante" => $resultado,
                "referencia_tipo" => $datos["referencia_tipo"],
                "referencia_id" => $datos["referencia_id"],
                "motivo" => $datos["motivo"],
                "id_usuario" => $datos["id_usuario"]
            ));
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $datos["id_usuario"],
                "accion" => $datos["tipo_movimiento"],
                "modulo" => "inventario",
                "tabla" => $configuracion["tabla"],
                "id_registro" => $datos["id_item"],
                "anteriores" => array("stock" => self::aDecimal($stockCentimos)),
                "nuevos" => array(
                    "stock" => self::aDecimal($resultado),
                    "tipo_movimiento" => $datos["tipo_movimiento"],
                    "cantidad" => self::aDecimal($cantidadCentimos),
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

    public static function mdlAjustarInventario($datos)
    {
        $configuracion = self::articulo($datos["tipo_item"]);
        self::validarSolicitudDisponibleAjuste();
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            self::validarSolicitudNueva($conexion, $datos["id_solicitud"]);
            $stockAnterior = self::aCentimos(self::bloquearStock($conexion, $configuracion, $datos["id_item"]));
            $stockFisico = self::aCentimos($datos["stock_fisico"]);
            if ($datos["tipo_item"] === "herramienta" && $stockFisico % 100 !== 0) {
                throw new InvalidArgumentException("Las herramientas se controlan en unidades enteras.");
            }
            self::validarMaximoStock($datos["tipo_item"], $stockFisico);
            $diferencia = $stockFisico - $stockAnterior;
            if ($diferencia === 0) {
                throw new InvalidArgumentException("El stock físico coincide con el sistema; no hay ajuste que registrar.");
            }
            $insertarAjuste = $conexion->prepare(
                "INSERT INTO ajuste_inventario
                    (id_solicitud, tipo_item, id_item, stock_sistema, stock_fisico, diferencia, motivo, id_usuario)
                 VALUES (:id_solicitud, :tipo_item, :id_item, :stock_sistema, :stock_fisico, :diferencia, :motivo, :id_usuario)"
            );
            $insertarAjuste->execute(array(
                ":id_solicitud" => $datos["id_solicitud"],
                ":tipo_item" => $datos["tipo_item"],
                ":id_item" => $datos["id_item"],
                ":stock_sistema" => self::aDecimal($stockAnterior),
                ":stock_fisico" => self::aDecimal($stockFisico),
                ":diferencia" => self::aDecimal($diferencia),
                ":motivo" => $datos["motivo"],
                ":id_usuario" => $datos["id_usuario"]
            ));
            $idAjuste = (int) $conexion->lastInsertId();
            self::actualizarStock($conexion, $configuracion, $datos["id_item"], $stockFisico);
            self::insertarKardex($conexion, array(
                "id_solicitud" => $datos["id_solicitud"],
                "tipo_item" => $datos["tipo_item"],
                "id_item" => $datos["id_item"],
                "tipo_movimiento" => $diferencia > 0 ? "entrada" : "salida",
                "cantidad" => abs($diferencia),
                "stock_anterior" => $stockAnterior,
                "stock_resultante" => $stockFisico,
                "referencia_tipo" => "ajuste",
                "referencia_id" => $idAjuste,
                "motivo" => $datos["motivo"],
                "id_usuario" => $datos["id_usuario"]
            ));
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $datos["id_usuario"],
                "accion" => "ajuste",
                "modulo" => "inventario",
                "tabla" => "ajuste_inventario",
                "id_registro" => $idAjuste,
                "anteriores" => array("stock" => self::aDecimal($stockAnterior)),
                "nuevos" => array(
                    "stock_fisico" => self::aDecimal($stockFisico),
                    "diferencia" => self::aDecimal($diferencia),
                    "tipo_item" => $datos["tipo_item"],
                    "id_item" => $datos["id_item"],
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

    private static function insertarKardex($conexion, $datos)
    {
        $stmt = $conexion->prepare(
            "INSERT INTO kardex
                (id_solicitud, tipo_item, id_item, tipo_movimiento, cantidad, stock_anterior,
                 stock_resultante, referencia_tipo, referencia_id, motivo, id_usuario)
             VALUES
                (:id_solicitud, :tipo_item, :id_item, :tipo_movimiento, :cantidad, :stock_anterior,
                 :stock_resultante, :referencia_tipo, :referencia_id, :motivo, :id_usuario)"
        );
        $stmt->execute(array(
            ":id_solicitud" => $datos["id_solicitud"],
            ":tipo_item" => $datos["tipo_item"],
            ":id_item" => $datos["id_item"],
            ":tipo_movimiento" => $datos["tipo_movimiento"],
            ":cantidad" => self::aDecimal($datos["cantidad"]),
            ":stock_anterior" => self::aDecimal($datos["stock_anterior"]),
            ":stock_resultante" => self::aDecimal($datos["stock_resultante"]),
            ":referencia_tipo" => $datos["referencia_tipo"],
            ":referencia_id" => $datos["referencia_id"],
            ":motivo" => $datos["motivo"],
            ":id_usuario" => $datos["id_usuario"]
        ));
    }

    private static function bloquearStock($conexion, $configuracion, $idItem)
    {
        $stmt = $conexion->prepare(
            "SELECT " . $configuracion["stock"] . " FROM " . $configuracion["tabla"] .
            " WHERE " . $configuracion["id"] . " = :id FOR UPDATE"
        );
        $stmt->execute(array(":id" => $idItem));
        $stock = $stmt->fetchColumn();
        if ($stock === false) {
            throw new InvalidArgumentException("El artículo seleccionado no existe.");
        }
        return $stock;
    }

    private static function actualizarStock($conexion, $configuracion, $idItem, $nuevoStock)
    {
        $stmt = $conexion->prepare(
            "UPDATE " . $configuracion["tabla"] . " SET " . $configuracion["stock"] . " = :stock" .
            " WHERE " . $configuracion["id"] . " = :id"
        );
        $stmt->execute(array(
            ":stock" => self::aDecimal($nuevoStock),
            ":id" => $idItem
        ));
    }

    private static function validarSolicitudNueva($conexion, $idSolicitud)
    {
        $stmt = $conexion->prepare("SELECT id_kardex FROM kardex WHERE id_solicitud = :solicitud");
        $stmt->execute(array(":solicitud" => $idSolicitud));
        if ($stmt->fetchColumn()) {
            throw new InvalidArgumentException("Esta operación ya fue registrada; no se repitieron sus cambios.");
        }
    }

    private static function validarIdSolicitudDisponible()
    {
        if (!self::columnaExiste("kardex", "id_solicitud")) {
            throw new RuntimeException("Aplica la migración kardex-consumos-idempotentes en una copia de pruebas antes de habilitar movimientos.");
        }
    }

    private static function validarSolicitudDisponibleAjuste()
    {
        self::validarIdSolicitudDisponible();
        if (!self::columnaExiste("ajuste_inventario", "id_solicitud")) {
            throw new RuntimeException("Aplica la migración de idempotencia de ajustes en una copia de pruebas antes de habilitar ajustes.");
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

    private static function articulo($tipo)
    {
        if (!isset(self::$articulos[$tipo])) {
            throw new InvalidArgumentException("Este tipo de artículo no admite movimientos de inventario.");
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

    private static function aCentimos($valor)
    {
        if (!preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', (string) $valor, $partes)) {
            throw new InvalidArgumentException("El stock debe ser un valor no negativo con hasta dos decimales.");
        }
        return ((int) $partes[1] * 100) + (int) str_pad($partes[2] ?? "0", 2, "0");
    }

    private static function aDecimal($centimos)
    {
        $signo = $centimos < 0 ? "-" : "";
        $absoluto = abs((int) $centimos);
        return $signo . intdiv($absoluto, 100) . "." . str_pad((string) ($absoluto % 100), 2, "0", STR_PAD_LEFT);
    }
}
