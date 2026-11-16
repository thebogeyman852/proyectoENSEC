<?php

require_once "conexion.php";

class ModeloAuditoria
{
    public static function mdlRegistrar($conexion, $datos)
    {
        $stmt = $conexion->prepare(
            "INSERT INTO auditoria
                (id_usuario, accion, modulo, tabla_afectada, id_registro, datos_anteriores, datos_nuevos)
             VALUES
                (:usuario, :accion, :modulo, :tabla, :registro, :anteriores, :nuevos)"
        );
        $stmt->execute(array(
            ":usuario" => $datos["id_usuario"],
            ":accion" => $datos["accion"],
            ":modulo" => $datos["modulo"],
            ":tabla" => $datos["tabla"],
            ":registro" => $datos["id_registro"] === null ? null : (string) $datos["id_registro"],
            ":anteriores" => self::json($datos["anteriores"] ?? null),
            ":nuevos" => self::json($datos["nuevos"] ?? null)
        ));
    }

    public static function mdlListar($filtros = array())
    {
        $sql = "SELECT a.*, u.nombre AS usuario
                FROM auditoria a
                LEFT JOIN usuario u ON u.idusuario = a.id_usuario
                WHERE 1 = 1";
        $parametros = array();
        if (!empty($filtros["modulo"]) && preg_match('/^[a-zA-Z0-9_-]{1,80}$/', $filtros["modulo"])) {
            $sql .= " AND a.modulo = :modulo";
            $parametros[":modulo"] = $filtros["modulo"];
        }
        if (!empty($filtros["id_usuario"]) && filter_var($filtros["id_usuario"], FILTER_VALIDATE_INT) !== false) {
            $sql .= " AND a.id_usuario = :usuario";
            $parametros[":usuario"] = (int) $filtros["id_usuario"];
        }
        foreach (array("desde", "hasta") as $campo) {
            if (!empty($filtros[$campo]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filtros[$campo])) {
                if ($campo === "desde") {
                    $sql .= " AND a.fecha >= :desde";
                    $parametros[":desde"] = $filtros[$campo] . " 00:00:00";
                } else {
                    $sql .= " AND a.fecha < DATE_ADD(:hasta, INTERVAL 1 DAY)";
                    $parametros[":hasta"] = $filtros[$campo] . " 00:00:00";
                }
            }
        }
        $stmt = Conexion::conectar()->prepare($sql . " ORDER BY a.fecha DESC, a.id_auditoria DESC LIMIT 1000");
        $stmt->execute($parametros);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function json($valor)
    {
        return $valor === null ? null : json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
