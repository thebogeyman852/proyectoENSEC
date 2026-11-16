<?php

class ControladorAuditoria
{
    public static function ctrListar()
    {
        if (($_SESSION["perfil"] ?? "") !== "Administrador") {
            throw new InvalidArgumentException("Solo el administrador puede consultar el historial de auditoría.");
        }
        return ModeloAuditoria::mdlListar(array(
            "modulo" => $_GET["modulo"] ?? "",
            "id_usuario" => $_GET["id_usuario"] ?? "",
            "desde" => $_GET["desde"] ?? "",
            "hasta" => $_GET["hasta"] ?? ""
        ));
    }
}
