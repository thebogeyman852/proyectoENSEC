# 01 — Conexión PDO

## Archivo y función

`modelos/conexion.php` centraliza la conexión a MariaDB mediante PDO. Actualmente apunta a `localhost`, base `sistemainventario_v3.2`, usuario `root` y contraseña vacía, una configuración local de XAMPP.

```php
$link = new PDO(
    "mysql:host=localhost;dbname=sistemainventario_v3.2;charset=utf8mb4",
    "root",
    ""
);
$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

El DSN indica el controlador `mysql`, el servidor, la base y la codificación. `PDO::ERRMODE_EXCEPTION` hace que los errores SQL se expresen como excepciones, que los controladores y modelos pueden manejar.

## Reproducir la conexión desde cero

1. Comprueba en `php.ini` que esté habilitada la extensión `pdo_mysql`.
2. Crea una clase `Conexion`.
3. Agrega un método estático `conectar()` para que los modelos lo llamen sin crear una instancia.
4. Construye el DSN con `host`, nombre real de base y `charset=utf8mb4`.
5. Configura el modo de excepciones y devuelve el objeto PDO.

La conexión solicita literalmente la base `sistemainventario_v3.2`. Asegúrate de que esa base exista y de que el esquema del archivo `sistemainventario_v3.2.sql` se haya importado allí. Un archivo SQL no crea necesariamente su base de destino: verifica el `USE` o la base seleccionada en phpMyAdmin antes de importar.

## Probar

La comprobación de sintaxis (`php -l modelos/conexion.php`) no prueba la conexión. Para confirmar acceso, inicia MySQL/MariaDB en XAMPP, revisa que `sistemainventario_v3` exista y abre la aplicación. Un error típico de conexión indica base inexistente, servicio detenido, usuario/clave incorrectos o `pdo_mysql` deshabilitado.

No publiques una instalación con estas credenciales locales. La configuración de producción debe usar un usuario limitado y secretos fuera del repositorio.
