<?php

class ControladorRecuperacion
{
    public static function ctrProcesarPublico()
    {
        $accion = $_POST["accionRecuperacion"] ?? "";
        if ($accion === "") {
            return;
        }
        try {
            self::validarCsrf();
            if ($accion !== "solicitar") {
                throw new InvalidArgumentException("La acción solicitada no es válida.");
            }
            $entradaUsuario = $_POST["usuarioRecuperacion"] ?? null;
            $nombreUsuario = is_string($entradaUsuario) ? trim($entradaUsuario) : "";
            if (!preg_match('/^[a-zA-Z0-9]{1,50}$/', $nombreUsuario)) {
                $_SESSION["mensajeRecuperacion"] =
                    "Si la cuenta está activa y tiene un Gmail registrado, recibirá instrucciones de recuperación.";
                header("Location: index.php");
                exit;
            }
            ModeloRecuperacion::mdlSolicitar($nombreUsuario);
            $_SESSION["mensajeRecuperacion"] =
                "Si la cuenta está activa y tiene un Gmail registrado, recibirá instrucciones de recuperación.";
            header("Location: index.php");
            exit;
        } catch (InvalidArgumentException $error) {
            $_SESSION["mensajeRecuperacion"] = $error->getMessage();
            header("Location: index.php");
            exit;
        } catch (PDOException $error) {
            $_SESSION["mensajeRecuperacion"] = "La recuperación aún no está configurada en la base de pruebas.";
            header("Location: index.php");
            exit;
        }
    }

    public static function ctrListarSolicitudes()
    {
        return ModeloRecuperacion::mdlListarSolicitudes();
    }

    public static function ctrAprobar($idSolicitud, $idAdmin)
    {
        if (($_SESSION["perfil"] ?? "") !== "Administrador") {
            throw new InvalidArgumentException("Solo el administrador puede aprobar recuperaciones.");
        }
        $temporal = self::crearTemporal();
        $hash = password_hash($temporal, PASSWORD_DEFAULT);
        try {
            ModeloRecuperacion::mdlAprobar($idSolicitud, $idAdmin, $hash, static function ($correo) use ($temporal) {
                self::enviarCorreo($correo, $temporal);
            });
        } catch (RuntimeException $error) {
            throw $error;
        } catch (Throwable $error) {
            throw new RuntimeException("No se pudo completar la aprobación o el envío del correo. La operación fue revertida.", 0, $error);
        }
    }

    public static function ctrCambiarTemporal()
    {
        if (empty($_SESSION["requiereCambioPassword"])) {
            return;
        }
        try {
            self::validarCsrf();
            $password = (string) ($_POST["nuevaPassword"] ?? "");
            $confirmar = (string) ($_POST["confirmarPassword"] ?? "");
            if (
                strlen($password) < 8 || strlen($password) > 72 ||
                !preg_match('/^[a-zA-Z0-9]+$/', $password) ||
                !hash_equals($password, $confirmar)
            ) {
                throw new InvalidArgumentException("La nueva contraseña debe tener 8-72 letras o números y coincidir con la confirmación.");
            }
            ModeloRecuperacion::mdlCambiarTemporal(
                (int) $_SESSION["idusuario"],
                (int) $_SESSION["idSolicitudRecuperacion"],
                $password
            );
            unset($_SESSION["requiereCambioPassword"], $_SESSION["idSolicitudRecuperacion"]);
            self::redirigirCambio("success", "Contraseña actualizada. Ya puedes usar el sistema.");
        } catch (InvalidArgumentException $error) {
            self::redirigirCambio("error", $error->getMessage());
        } catch (PDOException $error) {
            self::redirigirCambio("error", "No se pudo actualizar la contraseña.");
        }
    }

    public static function ctrForzarCambioLogin($usuario)
    {
        if ((int) ($usuario["debe_cambiar_password"] ?? 0) !== 1) {
            return null;
        }
        return ModeloRecuperacion::mdlTemporalActivo(
            (int) $usuario["idusuario"],
            (string) ($usuario["password"] ?? "")
        );
    }

    private static function crearTemporal()
    {
        $mayusculas = "ABCDEFGHJKLMNPQRSTUVWXYZ";
        $minusculas = "abcdefghijkmnopqrstuvwxyz";
        $digitos = "23456789";
        $caracteres = array(
            $mayusculas[random_int(0, strlen($mayusculas) - 1)],
            $minusculas[random_int(0, strlen($minusculas) - 1)],
            $digitos[random_int(0, strlen($digitos) - 1)]
        );
        $todos = $mayusculas . $minusculas . $digitos;
        while (count($caracteres) < 10) {
            $caracteres[] = $todos[random_int(0, strlen($todos) - 1)];
        }
        for ($i = count($caracteres) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            $temporal = $caracteres[$i];
            $caracteres[$i] = $caracteres[$j];
            $caracteres[$j] = $temporal;
        }
        return implode("", $caracteres);
    }

    private static function enviarCorreo($destinatario, $temporal)
    {
        $autoload = dirname(__DIR__) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";
        if (!is_file($autoload)) {
            throw new RuntimeException("Falta PHPMailer. Ejecuta composer install en la carpeta del proyecto.");
        }
        require_once $autoload;
        if (!class_exists("\\PHPMailer\\PHPMailer\\PHPMailer")) {
            throw new RuntimeException("PHPMailer no está disponible después de cargar vendor/autoload.php.");
        }
        $host = getenv("INNOMADE_SMTP_HOST") ?: "smtp.gmail.com";
        $puerto = getenv("INNOMADE_SMTP_PORT") ?: "587";
        $usuario = getenv("INNOMADE_SMTP_USER");
        $clave = getenv("INNOMADE_SMTP_PASSWORD");
        $remitente = getenv("INNOMADE_SMTP_FROM") ?: $usuario;
        if (!$usuario || !$clave || !$remitente) {
            throw new RuntimeException("Configura las variables locales INNOMADE_SMTP_USER, INNOMADE_SMTP_PASSWORD e INNOMADE_SMTP_FROM.");
        }

        $correo = new \PHPMailer\PHPMailer\PHPMailer(true);
        $correo->isSMTP();
        $correo->SMTPDebug = 0;
        $correo->Host = $host;
        $correo->SMTPAuth = true;
        $correo->Username = $usuario;
        $correo->Password = $clave;
        $correo->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $correo->Port = (int) $puerto;
        $correo->Timeout = 15;
        $correo->CharSet = "UTF-8";
        $correo->setFrom($remitente, getenv("INNOMADE_SMTP_FROM_NAME") ?: "INNOMADE");
        $correo->addAddress($destinatario);
        $correo->isHTML(false);
        $correo->Subject = "Contraseña temporal de INNOMADE";
        $correo->Body = "Tu contraseña temporal es: " . $temporal .
            "\nVence en 5 minutos y solo puede utilizarse una vez. Al ingresar, deberás cambiarla.";
        $correo->send();
    }

    private static function validarCsrf()
    {
        $token = $_POST["csrf_token"] ?? null;
        if (!is_string($token) || !hash_equals((string) ($_SESSION["csrf_token"] ?? ""), $token)) {
            throw new InvalidArgumentException("El formulario venció. Recarga la página.");
        }
    }

    private static function redirigirCambio($tipo, $mensaje)
    {
        $_SESSION["mensajeCambioPassword"] = array("tipo" => $tipo, "texto" => $mensaje);
        header("Location: index.php?ruta=cambiar-password");
        exit;
    }
}
