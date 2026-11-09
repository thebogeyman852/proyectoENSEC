<?php
require_once "conexion.php";

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
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO usuario (nombre, usuario, password, perfil, foto)
             VALUES (:nombre, :usuario, :password, :perfil, :foto)"
        );
        $stmt->execute(array(
            ":nombre" => $datos["nombre"],
            ":usuario" => $datos["usuario"],
            ":password" => $datos["password"],
            ":perfil" => $datos["perfil"],
            ":foto" => $datos["foto"]
        ));
        return "ok";
    }

    static public function mdlActualizarUsuario($datos)
    {
        $sql = "UPDATE usuario SET nombre = :nombre, usuario = :usuario, perfil = :perfil";
        if ($datos["password"] !== "") {
            $sql .= ", password = :password";
        }
        $sql .= " WHERE idusuario = :idusuario";
        $stmt = Conexion::conectar()->prepare($sql);
        $parametros = array(
            ":nombre" => $datos["nombre"],
            ":usuario" => $datos["usuario"],
            ":perfil" => $datos["perfil"],
            ":idusuario" => $datos["idusuario"]
        );
        if ($datos["password"] !== "") {
            $parametros[":password"] = $datos["password"];
        }
        $stmt->execute($parametros);
        return "ok";
    }

    static public function mdlActualizarEstado($idUsuario, $estado)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE usuario SET estado = :estado WHERE idusuario = :idusuario");
        $stmt->execute(array(":estado" => $estado, ":idusuario" => $idUsuario));
        return "ok";
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
        $stmt = Conexion::conectar()->prepare("DELETE FROM usuario WHERE idusuario = :idusuario");
        $stmt->bindValue(":idusuario", $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return "ok";
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
                "INSERT INTO usuario (nombre, usuario, password, perfil, foto)
                 VALUES (:nombre, :usuario, :password, 'Administrador', '')"
            );
            $stmt->execute(array(
                ":nombre" => $datos["nombre"],
                ":usuario" => $datos["usuario"],
                ":password" => $datos["password"]
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