<?php

require_once "conexion.php";
require_once "auditoria.modelo.php";

class ModeloRecuperacion
{
    public static function mdlSolicitar($nombreUsuario)
    {
        if (!is_string($nombreUsuario) || !preg_match('/^[a-zA-Z0-9]{1,50}$/', $nombreUsuario)) {
            throw new InvalidArgumentException("La solicitud de recuperación no es válida.");
        }
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            $usuario = $conexion->prepare(
                "SELECT idusuario, correo, estado FROM usuario
                 WHERE usuario = :usuario
                 ORDER BY idusuario FOR UPDATE"
            );
            $usuario->execute(array(":usuario" => $nombreUsuario));
            $coincidencias = $usuario->fetchAll(PDO::FETCH_ASSOC);
            if (
                count($coincidencias) !== 1 ||
                (int) $coincidencias[0]["estado"] !== 1 ||
                !filter_var($coincidencias[0]["correo"] ?? "", FILTER_VALIDATE_EMAIL) ||
                !preg_match('/@gmail\.com$/i', (string) $coincidencias[0]["correo"])
            ) {
                $conexion->commit();
                return "ok";
            }
            $cuenta = $coincidencias[0];

            $ultima = $conexion->prepare(
                "SELECT id_solicitud, estado, expira_en, usado_en,
                        (expira_en > CURRENT_TIMESTAMP AND usado_en IS NULL) AS temporal_activa,
                        (solicitado_en >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 5 MINUTE)) AS dentro_limite
                 FROM solicitud_recuperacion
                 WHERE id_usuario = :usuario
                 ORDER BY solicitado_en DESC, id_solicitud DESC
                 LIMIT 1 FOR UPDATE"
            );
            $ultima->execute(array(":usuario" => $cuenta["idusuario"]));
            $registro = $ultima->fetch(PDO::FETCH_ASSOC);
            if ($registro && $registro["estado"] === "pendiente") {
                $conexion->commit();
                return "ok";
            }
            if (
                $registro &&
                $registro["estado"] === "aprobada" &&
                (int) $registro["temporal_activa"] === 1
            ) {
                $conexion->commit();
                return "ok";
            }
            if ($registro && $registro["estado"] === "aprobada") {
                $expirar = $conexion->prepare(
                    "UPDATE solicitud_recuperacion
                     SET estado = 'expirada', hash_temporal = NULL
                     WHERE id_solicitud = :id AND estado = 'aprobada' AND usado_en IS NULL"
                );
                $expirar->execute(array(":id" => $registro["id_solicitud"]));
            }
            if ($registro && (int) $registro["dentro_limite"] === 1) {
                $conexion->commit();
                return "ok";
            }
            $insertar = $conexion->prepare(
                "INSERT INTO solicitud_recuperacion (id_usuario, estado)
                 VALUES (:usuario, 'pendiente')"
            );
            $insertar->execute(array(":usuario" => $cuenta["idusuario"]));
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => (int) $cuenta["idusuario"],
                "accion" => "solicitar_recuperacion",
                "modulo" => "usuarios",
                "tabla" => "solicitud_recuperacion",
                "id_registro" => (int) $conexion->lastInsertId(),
                "anteriores" => null,
                "nuevos" => array("estado" => "pendiente")
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

    public static function mdlListarSolicitudes()
    {
        return Conexion::conectar()->query(
            "SELECT r.id_solicitud, r.estado, r.solicitado_en, r.aprobado_en,
                    r.expira_en, r.usado_en, u.nombre, u.usuario, u.correo,
                    admin.nombre AS nombre_administrador
             FROM solicitud_recuperacion r
             INNER JOIN usuario u ON u.idusuario = r.id_usuario
             LEFT JOIN usuario admin ON admin.idusuario = r.autorizado_por
             ORDER BY r.solicitado_en DESC, r.id_solicitud DESC
             LIMIT 1000"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlTemporalActivo($idUsuario, $hashUsuario)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            $stmt = $conexion->prepare(
                "SELECT r.id_solicitud
                 FROM solicitud_recuperacion r
                 INNER JOIN usuario u ON u.idusuario = r.id_usuario
                 WHERE r.id_usuario = :usuario AND r.estado = 'aprobada'
                   AND r.expira_en > CURRENT_TIMESTAMP AND r.usado_en IS NULL
                   AND BINARY r.hash_temporal = BINARY u.password
                   AND BINARY r.hash_temporal = BINARY :hash
                 ORDER BY r.aprobado_en DESC LIMIT 1 FOR UPDATE"
            );
            $stmt->execute(array(":usuario" => $idUsuario, ":hash" => $hashUsuario));
            $id = $stmt->fetchColumn();
            if ($id !== false) {
                $conexion->commit();
                return (int) $id;
            }

            $expirar = $conexion->prepare(
                "UPDATE solicitud_recuperacion r
                 INNER JOIN usuario u ON u.idusuario = r.id_usuario
                 SET r.estado = 'expirada', r.hash_temporal = NULL
                 WHERE r.id_usuario = :usuario AND r.estado = 'aprobada'
                   AND r.usado_en IS NULL
                   AND (r.expira_en IS NULL OR r.expira_en <= CURRENT_TIMESTAMP
                        OR r.hash_temporal IS NULL OR BINARY r.hash_temporal <> BINARY u.password
                        OR BINARY r.hash_temporal <> BINARY :hash)"
            );
            $expirar->execute(array(":usuario" => $idUsuario, ":hash" => $hashUsuario));
            $conexion->commit();
            return null;
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    public static function mdlAprobar($idSolicitud, $idAdmin, $hashTemporal, $enviarCorreo)
    {
        if (
            ($_SESSION["perfil"] ?? "") !== "Administrador" ||
            (int) ($_SESSION["idusuario"] ?? 0) !== (int) $idAdmin
        ) {
            throw new InvalidArgumentException("Solo el administrador autenticado puede aprobar recuperaciones.");
        }
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            $stmt = $conexion->prepare(
                "SELECT r.id_usuario, r.estado, u.correo, u.estado AS usuario_activo
                 FROM solicitud_recuperacion r
                 INNER JOIN usuario u ON u.idusuario = r.id_usuario
                 WHERE r.id_solicitud = :id FOR UPDATE"
            );
            $stmt->execute(array(":id" => $idSolicitud));
            $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$solicitud || $solicitud["estado"] !== "pendiente" || (int) $solicitud["usuario_activo"] !== 1) {
                throw new InvalidArgumentException("La solicitud ya no está pendiente o el usuario está inactivo.");
            }
            if ((int) $solicitud["id_usuario"] === (int) $idAdmin) {
                throw new InvalidArgumentException("No puedes aprobar tu propia solicitud de recuperación.");
            }
            $actualizarUsuario = $conexion->prepare(
                "UPDATE usuario SET password = :password, debe_cambiar_password = 1
                 WHERE idusuario = :usuario"
            );
            $actualizarUsuario->execute(array(
                ":password" => $hashTemporal,
                ":usuario" => $solicitud["id_usuario"]
            ));
            $actualizarSolicitud = $conexion->prepare(
                "UPDATE solicitud_recuperacion
                 SET estado = 'aprobada', hash_temporal = :hash, aprobado_en = CURRENT_TIMESTAMP,
                     expira_en = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 5 MINUTE), autorizado_por = :admin
                 WHERE id_solicitud = :id AND estado = 'pendiente'"
            );
            $actualizarSolicitud->execute(array(
                ":hash" => $hashTemporal,
                ":admin" => $idAdmin,
                ":id" => $idSolicitud
            ));
            if ($actualizarSolicitud->rowCount() !== 1) {
                throw new RuntimeException("No se pudo aprobar la solicitud de recuperación.");
            }
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $idAdmin,
                "accion" => "aprobar_recuperacion",
                "modulo" => "usuarios",
                "tabla" => "solicitud_recuperacion",
                "id_registro" => $idSolicitud,
                "anteriores" => array("estado" => "pendiente"),
                "nuevos" => array("estado" => "aprobada", "id_usuario" => $solicitud["id_usuario"])
            ));
            $enviarCorreo($solicitud["correo"]);
            $conexion->commit();
            return "ok";
        } catch (Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    public static function mdlRechazar($idSolicitud, $idAdmin)
    {
        if (
            ($_SESSION["perfil"] ?? "") !== "Administrador" ||
            (int) ($_SESSION["idusuario"] ?? 0) !== (int) $idAdmin
        ) {
            throw new InvalidArgumentException("Solo el administrador autenticado puede rechazar recuperaciones.");
        }
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            $stmt = $conexion->prepare(
                "SELECT estado FROM solicitud_recuperacion WHERE id_solicitud = :id FOR UPDATE"
            );
            $stmt->execute(array(":id" => $idSolicitud));
            if ($stmt->fetchColumn() !== "pendiente") {
                throw new InvalidArgumentException("La solicitud ya no está pendiente.");
            }
            $rechazar = $conexion->prepare(
                "UPDATE solicitud_recuperacion SET estado = 'rechazada'
                 WHERE id_solicitud = :id AND estado = 'pendiente'"
            );
            $rechazar->execute(array(":id" => $idSolicitud));
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $idAdmin,
                "accion" => "rechazar_recuperacion",
                "modulo" => "usuarios",
                "tabla" => "solicitud_recuperacion",
                "id_registro" => (int) $idSolicitud,
                "anteriores" => array("estado" => "pendiente"),
                "nuevos" => array("estado" => "rechazada")
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

    public static function mdlCambiarTemporal($idUsuario, $idSolicitud, $passwordNuevo)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            $stmt = $conexion->prepare(
                "SELECT r.id_solicitud, r.hash_temporal, u.password AS password_actual
                 FROM solicitud_recuperacion r
                 INNER JOIN usuario u ON u.idusuario = r.id_usuario
                 WHERE r.id_solicitud = :solicitud AND r.id_usuario = :usuario
                   AND r.estado = 'aprobada' AND r.expira_en > CURRENT_TIMESTAMP
                   AND r.usado_en IS NULL
                   AND BINARY r.hash_temporal = BINARY u.password
                 FOR UPDATE"
            );
            $stmt->execute(array(":solicitud" => $idSolicitud, ":usuario" => $idUsuario));
            $recuperacion = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$recuperacion) {
                $expirar = $conexion->prepare(
                    "UPDATE solicitud_recuperacion r
                     INNER JOIN usuario u ON u.idusuario = r.id_usuario
                     SET r.estado = 'expirada', r.hash_temporal = NULL
                     WHERE r.id_solicitud = :solicitud AND r.id_usuario = :usuario
                       AND r.estado = 'aprobada' AND r.usado_en IS NULL
                       AND (r.expira_en IS NULL OR r.expira_en <= CURRENT_TIMESTAMP
                            OR r.hash_temporal IS NULL OR BINARY r.hash_temporal <> BINARY u.password)"
                );
                $expirar->execute(array(":solicitud" => $idSolicitud, ":usuario" => $idUsuario));
                $conexion->commit();
                throw new InvalidArgumentException("La contraseña temporal venció o ya fue utilizada. Solicita otra recuperación.");
            }
            if (
                !is_string($recuperacion["hash_temporal"]) ||
                !hash_equals($recuperacion["hash_temporal"], $recuperacion["password_actual"])
            ) {
                throw new InvalidArgumentException("La solicitud de cambio de contraseña ya no es válida.");
            }
            if (password_verify($passwordNuevo, $recuperacion["hash_temporal"])) {
                throw new InvalidArgumentException("La nueva contraseña debe ser diferente a la contraseña temporal.");
            }
            $usuario = $conexion->prepare(
                "UPDATE usuario SET password = :password, debe_cambiar_password = 0 WHERE idusuario = :id"
            );
            $usuario->execute(array(
                ":password" => password_hash($passwordNuevo, PASSWORD_DEFAULT),
                ":id" => $idUsuario
            ));
            $solicitud = $conexion->prepare(
                "UPDATE solicitud_recuperacion
                 SET estado = 'usada', usado_en = CURRENT_TIMESTAMP, hash_temporal = NULL
                 WHERE id_solicitud = :id AND estado = 'aprobada'"
            );
            $solicitud->execute(array(":id" => $idSolicitud));
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $idUsuario,
                "accion" => "cambio_password_temporal",
                "modulo" => "usuarios",
                "tabla" => "usuario",
                "id_registro" => $idUsuario,
                "anteriores" => array("debe_cambiar_password" => 1),
                "nuevos" => array("debe_cambiar_password" => 0)
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
