<?php
require_once "conexion.php";
require_once "auditoria.modelo.php";

class ModeloUsuarios
{
    static public function mdlMostrarUsuarios($tabla, $item, $valor)
    {
        if ($tabla !== "usuario" || ($item !== null && !in_array($item, array("idusuario", "usuario", "perfil", "estado"), true))) {
            throw new InvalidArgumentException("Búsqueda de usuario no válida.");
        }

        if ($item != null) {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM usuario WHERE $item = :valor");
            $stmt->bindValue(":valor", $valor);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = Conexion::conectar()->query("SELECT * FROM usuario ORDER BY idusuario DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // registro de usuario
    static public function mdlIngresarUsuario($tabla, $datos)
    {
        if ($tabla !== "usuario") {
            throw new InvalidArgumentException("Tabla de usuario no válida.");
        }
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
        $stmt = $conexion->prepare(
            "INSERT INTO usuario (nombre, usuario, password, perfil, foto, correo, debe_cambiar_password)
             VALUES (:nombre, :usuario, :password, :perfil, :foto, :correo, 0)"
        );
        $stmt->execute(array(
            ":nombre" => $datos["nombre"],
            ":usuario" => $datos["usuario"],
            ":password" => $datos["password"],
            ":perfil" => $datos["perfil"],
            ":foto" => $datos["foto"],
            ":correo" => $datos["correo"]
        ));
        ModeloAuditoria::mdlRegistrar($conexion, array(
            "id_usuario" => $_SESSION["idusuario"] ?? null,
            "accion" => "crear",
            "modulo" => "usuarios",
            "tabla" => "usuario",
            "id_registro" => (int) $conexion->lastInsertId(),
            "anteriores" => null,
            "nuevos" => array(
                "nombre" => $datos["nombre"],
                "usuario" => $datos["usuario"],
                "perfil" => $datos["perfil"],
                "correo" => $datos["correo"]
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

    static public function mdlActualizarUsuario($datos)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
        $anterior = $conexion->prepare(
            "SELECT idusuario, nombre, usuario, perfil, correo, estado FROM usuario WHERE idusuario = :id FOR UPDATE"
        );
        $anterior->execute(array(":id" => $datos["idusuario"]));
        $datosAnteriores = $anterior->fetch(PDO::FETCH_ASSOC);
        if (!$datosAnteriores) {
            throw new InvalidArgumentException("El usuario seleccionado no existe.");
        }
        $sql = "UPDATE usuario SET nombre = :nombre, usuario = :usuario, perfil = :perfil, correo = :correo";
        if ($datos["password"] !== "") {
            $sql .= ", password = :password";
        }
        $sql .= " WHERE idusuario = :idusuario";
        $stmt = $conexion->prepare($sql);
        $parametros = array(
            ":nombre" => $datos["nombre"],
            ":usuario" => $datos["usuario"],
            ":perfil" => $datos["perfil"],
            ":correo" => $datos["correo"],
            ":idusuario" => $datos["idusuario"]
        );
        if ($datos["password"] !== "") {
            $parametros[":password"] = $datos["password"];
        }
        $stmt->execute($parametros);
        $nuevos = array(
            "nombre" => $datos["nombre"],
            "usuario" => $datos["usuario"],
            "perfil" => $datos["perfil"],
            "correo" => $datos["correo"],
            "password_changed" => $datos["password"] !== ""
        );
        ModeloAuditoria::mdlRegistrar($conexion, array(
            "id_usuario" => $_SESSION["idusuario"] ?? null,
            "accion" => "editar",
            "modulo" => "usuarios",
            "tabla" => "usuario",
            "id_registro" => (int) $datos["idusuario"],
            "anteriores" => $datosAnteriores,
            "nuevos" => $nuevos
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

    static public function mdlActualizarEstado($idUsuario, $estado)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();
        try {
            $consulta = $conexion->prepare(
                "SELECT idusuario, nombre, usuario, perfil, correo, estado FROM usuario WHERE idusuario = :id FOR UPDATE"
            );
            $consulta->execute(array(":id" => $idUsuario));
            $anterior = $consulta->fetch(PDO::FETCH_ASSOC);
            if (!$anterior) {
                throw new InvalidArgumentException("El usuario seleccionado no existe.");
            }
            if ((int) $estado === 0 && $anterior["perfil"] === "Administrador") {
                $administradores = $conexion->prepare(
                    "SELECT COUNT(*) FROM usuario WHERE perfil = 'Administrador' AND estado = 1 AND idusuario <> :id"
                );
                $administradores->execute(array(":id" => $idUsuario));
                if ((int) $administradores->fetchColumn() === 0) {
                    throw new InvalidArgumentException("No se puede desactivar al último administrador activo.");
                }
            }
            $stmt = $conexion->prepare("UPDATE usuario SET estado = :estado WHERE idusuario = :idusuario");
            $stmt->execute(array(":estado" => $estado, ":idusuario" => $idUsuario));
            ModeloAuditoria::mdlRegistrar($conexion, array(
                "id_usuario" => $_SESSION["idusuario"] ?? null,
                "accion" => (int) $estado === 1 ? "activar" : "desactivar",
                "modulo" => "usuarios",
                "tabla" => "usuario",
                "id_registro" => (int) $idUsuario,
                "anteriores" => $anterior,
                "nuevos" => array("estado" => (int) $estado)
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

    static public function mdlActualizarUltimoLogin($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE usuario SET ultimo_login = CURRENT_TIMESTAMP WHERE idusuario = :idusuario");
        $stmt->execute(array(":idusuario" => $idUsuario));
    }

    static public function mdlActualizarPassword($idUsuario, $password)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE usuario SET password = :password WHERE idusuario = :idusuario");
        $stmt->execute(array(":password" => $password, ":idusuario" => $idUsuario));
    }

    static public function mdlBorrarUsuario($idUsuario)
    {
        return self::mdlActualizarEstado($idUsuario, 0);
    }

    static public function mdlContarUsuarios()
    {
        return (int) Conexion::conectar()->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
    }

    static public function mdlCrearPrimerUsuario($datos)
    {
        $conexion = Conexion::conectar();
        $conexion->beginTransaction();

        try {
            $cantidad = (int) $conexion->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
            if ($cantidad !== 0) {
                throw new InvalidArgumentException("La configuración inicial ya fue completada.");
            }

            $stmt = $conexion->prepare(
                "INSERT INTO usuario (nombre, usuario, password, perfil, foto, correo, debe_cambiar_password)
                 VALUES (:nombre, :usuario, :password, 'Administrador', '', :correo, 0)"
            );
            $stmt->execute(array(
                ":nombre" => $datos["nombre"],
                ":usuario" => $datos["usuario"],
                ":password" => $datos["password"],
                ":correo" => $datos["correo"]
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