<?php

class ControladorCatalogos
{
    private static $campos = array(
        "clientes" => array("nombre", "telefono", "direccion"),
        "proveedores" => array("nombre_proveedor", "telefono", "direccion"),
        "tipos-material" => array("nombre", "descripcion")
    );

    public static function ctrMostrar($recurso)
    {
        return ModeloCatalogos::mdlMostrar($recurso);
    }

    public static function ctrProcesar($recurso)
    {
        if (!isset(self::$campos[$recurso]) || empty($_POST["accionCatalogo"])) {
            return;
        }

        $accion = $_POST["accionCatalogo"];
        $id = filter_var($_POST["idCatalogo"] ?? null, FILTER_VALIDATE_INT);

        try {
            if ($accion === "eliminar") {
                if ($id === false || $id < 1) {
                    throw new InvalidArgumentException("El registro seleccionado no es válido.");
                }
                ModeloCatalogos::mdlBorrar($recurso, $id);
            } elseif ($accion === "crear" || $accion === "editar") {
                $datos = array();
                foreach (self::$campos[$recurso] as $campo) {
                    $datos[$campo] = trim((string) ($_POST[$campo] ?? ""));
                    $limites = array(
                        "nombre" => 150,
                        "nombre_proveedor" => 150,
                        "telefono" => 20,
                        "direccion" => 255,
                        "descripcion" => 65535
                    );
                    $limite = $recurso === "tipos-material" && $campo === "nombre"
                        ? 100
                        : $limites[$campo];
                    if (
                        (($campo === "nombre" || $campo === "nombre_proveedor") && $datos[$campo] === "") ||
                        mb_strlen($datos[$campo]) > $limite
                    ) {
                        throw new InvalidArgumentException("Revisa los campos del formulario; algún valor excede el límite permitido.");
                    }
                }
                if ($accion === "editar" && ($id === false || $id < 1)) {
                    throw new InvalidArgumentException("El registro seleccionado no es válido.");
                }

                if ($accion === "crear") {
                    ModeloCatalogos::mdlIngresar($recurso, $datos);
                } else {
                    ModeloCatalogos::mdlEditar($recurso, $id, $datos);
                }
            } else {
                throw new InvalidArgumentException("La operación solicitada no es válida.");
            }

            self::redirigir($recurso, "success", "Cambios guardados correctamente.");
        } catch (InvalidArgumentException $error) {
            self::redirigir($recurso, "error", $error->getMessage());
        } catch (PDOException $error) {
            self::redirigir($recurso, "error", "No se pudo guardar. Puede haber registros relacionados que impiden esta operación.");
        }
    }

    private static function redirigir($recurso, $tipo, $mensaje)
    {
        $_SESSION["mensaje"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=" . rawurlencode($recurso));
        exit;
    }
}
