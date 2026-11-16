# 13 — Guía para programar sin IA

Esta guía toma como ejemplo el CRUD de categorías que existe en el proyecto. El objetivo es que puedas seguir el recorrido de una solicitud desde el formulario hasta MariaDB y construir una operación equivalente por tu cuenta.

## 1. Entiende el recorrido MVC

1. La vista `vistas/modulos/categorias.php` genera formularios HTML.
2. El navegador envía los campos mediante `POST`.
3. `index.php` carga controladores/modelos y deriva la petición autenticada a `ControladorCategorias::ctrProcesarCategoria()`.
4. El controlador valida token, operación, texto e identificador.
5. `ModeloCategorias` ejecuta SQL preparado con PDO.
6. El controlador guarda un mensaje y redirige; la vista muestra el mensaje.

Prueba a dibujar estas seis etapas con flechas. Si una acción no funciona, localiza primero en qué etapa deja de avanzar.

## 2. HTML: crear un formulario

El alta de categoría tiene la forma:

```php
<form method="post">
  <input type="hidden" name="accionCategoria" value="crear">
  <input type="hidden" name="csrf_token"
         value="<?php echo htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8"); ?>">
  <input type="text" name="nombre" maxlength="100" required>
  <input type="text" name="descripcion">
  <button type="submit">Agregar</button>
</form>
```

- `method="post"` envía datos para cambiar el sistema.
- `name` es la clave con la que PHP leerá cada dato en `$_POST`.
- `required` y `maxlength` ayudan al usuario, pero no sustituyen la validación del servidor.
- `csrf_token` demuestra que la acción viene de un formulario de la sesión iniciada.
- `htmlspecialchars` escapa el token para insertarlo de manera segura en HTML.

## 3. Controlador: validar antes de guardar

El controlador primero comprueba el token:

```php
$token = $_POST["csrf_token"] ?? null;
if (!is_string($token) ||
    !hash_equals((string) ($_SESSION["csrf_token"] ?? ""), $token)) {
    throw new InvalidArgumentException("La sesión del formulario venció.");
}
```

`$_POST["csrf_token"] ?? null` significa “usa el token enviado o `null` si no existe”. `hash_equals` compara cadenas evitando una comparación insegura de tokens.

Después se valida el texto:

```php
$nombre = trim((string) ($_POST["nombre"] ?? ""));
if ($nombre === "" || mb_strlen($nombre) > 100) {
    throw new InvalidArgumentException("El nombre es obligatorio.");
}
```

- `trim` elimina espacios al inicio y final.
- `(string)` normaliza el dato recibido.
- `mb_strlen` mide caracteres, incluso letras acentuadas.
- `throw` interrumpe el flujo y permite mostrar un error controlado.

No confíes en que el navegador haya respetado `required`, porque una solicitud puede enviarse sin usar el formulario visual.

## 4. Modelo: ejecutar SQL con PDO

El alta usa una consulta preparada:

```php
$stmt = Conexion::conectar()->prepare(
    "INSERT INTO categoria (nombre, descripcion)
     VALUES (:nombre, :descripcion)"
);
$stmt->execute(array(
    ":nombre" => $datos["nombre"],
    ":descripcion" => $datos["descripcion"]
));
```

`prepare` separa la estructura SQL de los valores. Los marcadores `:nombre` y `:descripcion` se enlazan mediante `execute`. No unas datos recibidos por el usuario dentro del texto SQL.

La tabla es fija (`categoria`). Los parámetros preparados protegen valores, pero no sirven para nombres de tabla o columna; por eso estos nombres deben estar escritos en el código o elegidos de una lista permitida.

## 5. Editar y eliminar

La edición necesita un identificador:

```sql
UPDATE categoria
SET nombre = :nombre, descripcion = :descripcion
WHERE idcategoria = :idcategoria
```

El `WHERE` es esencial: sin él cambiarías todas las categorías.

El borrado se envía por POST, valida el ID y ejecuta:

```sql
DELETE FROM categoria WHERE idcategoria = :idcategoria
```

MariaDB puede rechazarlo cuando existan productos relacionados. En ese caso el sistema informa que la operación no se pudo completar y conserva la integridad referencial.

## 6. Mensajes y redirección

Después de guardar, el controlador coloca el mensaje en la sesión y redirige:

```php
$_SESSION["mensaje"] = array("tipo" => "success", "texto" => "Cambios guardados.");
header("Location: index.php?ruta=categorias");
exit;
```

La redirección evita que al recargar la página se repita el envío del formulario. `exit` detiene PHP después de enviar la cabecera.

## 7. Cómo escribir un CRUD desde cero

Orden recomendado:

1. Leer en el archivo SQL la tabla, columnas, claves y restricciones.
2. Escribir un `SELECT` pequeño y probarlo en una copia de pruebas de phpMyAdmin.
3. Crear en el modelo el método de listado y probar `fetchAll(PDO::FETCH_ASSOC)`.
4. Crear el formulario HTML con `name` claros.
5. Agregar la ruta POST en `index.php`.
6. Validar token, identificadores, longitudes y tipos en el controlador.
7. Crear consultas preparadas para alta/edición/borrado.
8. Mostrar mensajes y manejar errores de integridad sin enseñar detalles de conexión.
9. Probar los casos válidos, vacíos, inválidos y relacionados.

## 8. Cómo investigar un error

- `Undefined array key`: revisa si la clave existe antes de leerla; usa `??` cuando haya un valor alternativo correcto.
- `SQLSTATE[42S22]`: el nombre de una columna no coincide con la base seleccionada; compara consulta y SQL importado.
- `SQLSTATE[23000]`: puede ser una clave duplicada o una relación que impide borrar; inspecciona el mensaje en el log local y las FK.
- “No errors found” del editor solo confirma análisis estático, no que la base esté disponible ni que la consulta funcione.
- Prueba primero en copia, no con registros reales.

## 9. Ejercicio

Sin copiar el código, añade al formulario de categorías un campo de búsqueda por nombre. Escribe una consulta preparada `SELECT ... WHERE nombre LIKE :busqueda`, valida la longitud y escapa `%` y `_` si quieres que se busquen como caracteres literales. Compara el resultado con una lista sin filtro y comprueba el caso sin coincidencias.

## Referencias

- [CRUD y resultados de pruebas](./15_DIAGNOSTICO_Y_CORRECCION_CRUD.md)
- [Arquitectura MVC](./02_Arquitectura_MVC.md)
- [Conexión PDO](./01_Conexion_PDO.md)
- [Pedidos](./03_Modulo_Pedidos.md)
- [Materiales](./04_Modulo_Materiales.md)
- [Kardex](./05_Kardex_Inventario.md)
- [Guía de compras, herramientas, pagos, usuarios, auditoría y reportes](./16_Guia_Modulos_Inventario_Compras_Seguridad_Reportes.md)

## Para practicar los módulos con transacciones

En inventario y compras, encuentra primero en el modelo el `beginTransaction()`. Anota en una hoja qué consultas bloquean una fila, cuál modifica el stock y cuál inserta el kardex. Luego dibuja qué debe deshacerse si la última consulta falla. Ese ejercicio ayuda a comprobar que un movimiento no pueda guardar solamente la mitad de sus cambios.

En pagos, escribe con tus propias palabras cómo se calcula el saldo: total del pedido menos pagos registrados. Después revisa por qué el pedido se bloquea antes de validar ese saldo. En auditoría, identifica qué datos se escriben antes y después y confirma que no se guardan contraseñas ni hashes.

Antes de probar los ejercicios, usa una copia de XAMPP, respaldo y las migraciones documentadas. La existencia de una función o un resultado correcto de `php -l` no demuestra que la operación ya se haya probado con formularios, relaciones y datos reales.
