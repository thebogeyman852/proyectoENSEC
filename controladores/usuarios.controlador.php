<?php

class ControladorUsuarios
{
    public function crtIngresoUsuario()
    {
        if (isset($_POST["ingUsuario"], $_POST["ingPassword"])) {
            if (
                preg_match('/^[a-zA-Z0-9]+$/', $_POST["ingUsuario"]) &&
                preg_match('/^[a-zA-Z0-9]+$/', $_POST["ingPassword"])
            ) {
                $tabla = "usuario";
                $item = "usuario";
                $valor = $_POST["ingUsuario"];
                $respuesta = ModeloUsuarios::MdlMostrarUsuarios($tabla, $item, $valor);

                $passwordAlmacenada = $respuesta ? $respuesta["password"] : "";
                $informacionPassword = $passwordAlmacenada !== "" ? password_get_info($passwordAlmacenada) : array("algo" => null);
                $passwordValida = $informacionPassword["algo"] !== null
                    ? password_verify($_POST["ingPassword"], $passwordAlmacenada)
                    : hash_equals($passwordAlmacenada, $_POST["ingPassword"]);
                if (
                    $respuesta &&
                    (int) $respuesta["estado"] === 1 &&
                    $respuesta["usuario"] === $_POST["ingUsuario"] &&
                    $passwordValida
                ) {
                    if ($informacionPassword["algo"] === null || password_needs_rehash($passwordAlmacenada, PASSWORD_DEFAULT)) {
                        ModeloUsuarios::mdlActualizarPassword(
                            (int) $respuesta["idusuario"],
                            password_hash($_POST["ingPassword"], PASSWORD_DEFAULT)
                        );
                    }
                    ModeloUsuarios::mdlActualizarUltimoLogin((int) $respuesta["idusuario"]);
                    $_SESSION["idusuario"] = (int) $respuesta["idusuario"];
                    $_SESSION["usuario"] = $respuesta["usuario"];
                    $_SESSION["nombre"] = $respuesta["nombre"];
                    $_SESSION["perfil"] = $respuesta["perfil"];
                    $_SESSION["foto"] = $respuesta["foto"];
                    $_SESSION["iniciarSesion"] = "ok";
                    echo '<script>
                        window.location = "index.php?ruta=inicio";
                    </script>';
                } else {
                    echo '<br><div class="alert alert-danger">Error al ingresar, vuelve a intentarlo</div>';
                }
            }
        }
    }

    static public function ctrHayUsuarios()
    {
        return ModeloUsuarios::mdlContarUsuarios() > 0;
    }

    static public function ctrCrearPrimerUsuario()
    {
        if (!isset($_POST["crearPrimerUsuario"])) {
            return;
        }

        $nombre = trim((string) ($_POST["primerNombre"] ?? ""));
        $usuario = trim((string) ($_POST["primerUsuario"] ?? ""));
        $password = (string) ($_POST["primerPassword"] ?? "");
        if (
            $nombre === "" || mb_strlen($nombre) > 150 ||
            !preg_match('/^[a-zA-Z0-9]+$/', $usuario) || mb_strlen($usuario) > 50 ||
            !preg_match('/^[a-zA-Z0-9]{8,72}$/', $password)
        ) {
            self::mostrarMensajeAcceso("Ingresa un nombre, usuario y contraseña válidos (mínimo 8 caracteres).");
            return;
        }

        try {
            ModeloUsuarios::mdlCrearPrimerUsuario(array(
                "nombre" => $nombre,
                "usuario" => $usuario,
                "password" => password_hash($password, PASSWORD_DEFAULT)
            ));
            self::mostrarMensajeAcceso("Administrador creado. Ya puedes iniciar sesión.");
        } catch (InvalidArgumentException $error) {
            self::mostrarMensajeAcceso($error->getMessage());
        } catch (PDOException $error) {
            self::mostrarMensajeAcceso("No se pudo completar la configuración inicial.");
        }
    }

    private static function mostrarMensajeAcceso($mensaje)
    {
        echo '<script>alert(' . json_encode($mensaje, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . '); window.location = "index.php";</script>';
    }

   static public function ctrCrearUsuario() {
        if (isset($_POST["nuevoNombre"], $_POST["nuevoUsuario"], $_POST["nuevoPassword"], $_POST["nuevoPerfil"])) {
            if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["nuevoNombre"]) &&
                preg_match('/^[a-zA-Z0-9]+$/', $_POST["nuevoUsuario"]) &&
                preg_match('/^[a-zA-Z0-9]+$/', $_POST["nuevoPassword"]) &&
                mb_strlen($_POST["nuevoNombre"]) <= 150 &&
                mb_strlen($_POST["nuevoUsuario"]) <= 50 &&
                mb_strlen($_POST["nuevoPassword"]) <= 72 &&
                in_array($_POST["nuevoPerfil"], array("Administrador", "Especial", "Vendedor"), true)){

                // ==========================================
                // VALIDAR IMAGEN / FOTO
                // ==========================================
                $ruta = "vistas/img/usuarios/default/anonymous.png";

                if (isset($_FILES["nuevaFoto"]["tmp_name"]) && !empty($_FILES["nuevaFoto"]["tmp_name"])) {

                    // Capturar dimensiones de la imagen
                    list($ancho, $alto) = getimagesize($_FILES["nuevaFoto"]["tmp_name"]);

                    $nuevoAncho = 500;
                    $nuevoAlto = 500;

                    // Crear directorio si no existe
                    $directorio = "vistas/img/usuarios/".$_POST["nuevoUsuario"];
                    if (!file_exists($directorio)) {
                        mkdir($directorio, 0755, true);
                    }

                    // De acuerdo al tipo de imagen aplicamos las funciones por defecto
                    if ($_FILES["nuevaFoto"]["type"] == "image/jpeg") {
                        $aleatorio = mt_rand(100,999);
                        $ruta = "vistas/img/usuarios/".$_POST["nuevoUsuario"]."/".$aleatorio.".jpg";
                        $origen = imagecreatefromjpeg($_FILES["nuevaFoto"]["tmp_name"]);
                        $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
                        imagejpeg($destino, $ruta);
                    }

                    if ($_FILES["nuevaFoto"]["type"] == "image/png") {
                        $aleatorio = mt_rand(100,999);
                        $ruta = "vistas/img/usuarios/".$_POST["nuevoUsuario"]."/".$aleatorio.".png";
                        $origen = imagecreatefrompng($_FILES["nuevaFoto"]["tmp_name"]);
                        $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
                        imagepng($destino, $ruta);
                    }
                }

                $tabla = "usuario";
                $password = password_hash($_POST["nuevoPassword"], PASSWORD_DEFAULT);

                $datos = array("nombre" => $_POST["nuevoNombre"],
                                "usuario" => $_POST["nuevoUsuario"],
                                "password" => $password,
                                "perfil" => $_POST["nuevoPerfil"],
                                "foto" => $ruta);

                try {
                    $respuesta = ModeloUsuarios::mdlIngresarUsuario($tabla, $datos);
                    $mensaje = $respuesta === "ok"
                        ? "El usuario ha sido guardado correctamente."
                        : "No se pudo guardar el usuario.";
                    echo '<script>alert(' . json_encode($mensaje, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . '); window.location = "index.php?ruta=usuarios";</script>';
                } catch (PDOException $error) {
                    echo '<script>alert("No se pudo guardar el usuario."); window.location = "index.php?ruta=usuarios";</script>';
                }
            } else {
                echo '<script>alert("Revisa los datos del usuario."); window.location = "index.php?ruta=usuarios";</script>';
            }
        }
    }
    static public function ctrMostrarUsuarios($item, $valor){
        $tabla = "usuario";
        $respuesta = ModeloUsuarios::mdlMostrarUsuarios($tabla, $item, $valor);
        return $respuesta;
    }

    static public function ctrProcesarUsuario()
    {
        if (empty($_POST["accionUsuario"])) {
            return;
        }

        $accion = $_POST["accionUsuario"];
        $idUsuario = filter_var($_POST["idusuario"] ?? null, FILTER_VALIDATE_INT);

        try {
            if ($accion === "estado") {
                $estado = filter_var($_POST["estado"] ?? null, FILTER_VALIDATE_INT);
                if ($idUsuario === false || $idUsuario < 1 || !in_array($estado, array(0, 1), true)) {
                    throw new InvalidArgumentException("El usuario o estado seleccionado no es válido.");
                }
                if ($idUsuario === (int) ($_SESSION["idusuario"] ?? 0) && $estado === 0) {
                    throw new InvalidArgumentException("No puedes desactivar tu propia sesión.");
                }
                ModeloUsuarios::mdlActualizarEstado($idUsuario, $estado);
            } elseif ($accion === "eliminar") {
                if ($idUsuario === false || $idUsuario < 1) {
                    throw new InvalidArgumentException("El usuario seleccionado no es válido.");
                }
                if ($idUsuario === (int) ($_SESSION["idusuario"] ?? 0)) {
                    throw new InvalidArgumentException("No puedes eliminar tu propia cuenta.");
                }
                ModeloUsuarios::mdlBorrarUsuario($idUsuario);
            } elseif ($accion === "editar") {
                $nombre = trim((string) ($_POST["nombre"] ?? ""));
                $usuario = trim((string) ($_POST["usuario"] ?? ""));
                $perfil = trim((string) ($_POST["perfil"] ?? ""));
                $password = trim((string) ($_POST["password"] ?? ""));
                if (
                    $idUsuario === false || $idUsuario < 1 ||
                    $nombre === "" || mb_strlen($nombre) > 150 ||
                    $usuario === "" || mb_strlen($usuario) > 50 ||
                    !preg_match('/^[a-zA-Z0-9]+$/', $usuario) ||
                    !in_array($perfil, array("Administrador", "Especial", "Vendedor"), true) ||
                    ($password !== "" && !preg_match('/^[a-zA-Z0-9]{1,72}$/', $password))
                ) {
                    throw new InvalidArgumentException("Revisa los datos del usuario.");
                }
                ModeloUsuarios::mdlActualizarUsuario(array(
                    "idusuario" => $idUsuario,
                    "nombre" => $nombre,
                    "usuario" => $usuario,
                    "perfil" => $perfil,
                    "password" => $password === "" ? "" : password_hash($password, PASSWORD_DEFAULT)
                ));
            } else {
                throw new InvalidArgumentException("La operación solicitada no es válida.");
            }

            self::redirigirUsuario("success", "Usuario actualizado correctamente.");
        } catch (InvalidArgumentException $error) {
            self::redirigirUsuario("error", $error->getMessage());
        } catch (PDOException $error) {
            self::redirigirUsuario("error", "No se pudo guardar el usuario. Puede haber registros asociados a su cuenta.");
        }
    }

    private static function redirigirUsuario($tipo, $mensaje)
    {
        $_SESSION["mensaje"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=usuarios");
        exit;
    }
}