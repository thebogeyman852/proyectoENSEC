# 04 — Módulo de materiales

## Propósito

Mantiene la ficha de cada material: tipo, proveedor, nombre y unidad de medida. El stock visible se consulta desde `materiales.stock_actual`, pero no se edita directamente desde el formulario de mantenimiento.

## Archivos y tablas

- `vistas/modulos/materiales.php`: captura datos de ficha y muestra existencias.
- `controladores/materiales.controlador.php`: valida nombre, tipo, proveedor y unidad.
- `modelos/materiales.modelo.php`: consulta `materiales`, `tipo_material` y `proveedor`.
- `materiales`: columnas usadas `idmaterial`, `idtipo_material`, `idproveedor`, `nombre`, `unidad_medida`, `stock_actual`.

Crear una ficha deja el stock en cero mediante el valor por defecto de MariaDB. Editarla cambia solamente sus atributos descriptivos. Las cantidades se modificarán con las operaciones de inventario y se documentarán en el kardex, no sobrescribiendo el stock desde un CRUD. Una ficha con movimientos de kardex no se puede eliminar; así el historial no queda desconectado del material.

## Cómo se conectan las capas

El formulario envía `accionMaterial`, `nombre`, `idtipo_material`, `idproveedor` y `unidad_medida`. El controlador valida los datos y llama al modelo. El modelo prepara un `INSERT` o `UPDATE` con placeholders. Para mostrar la ficha completa, hace `INNER JOIN` con tipo y proveedor; el stock se presenta como solo lectura.

## Funciones y variables importantes

- `ctrMostrarMateriales($idMaterial = null)`: parámetro opcional; `null` significa listar, un ID significa obtener una ficha.
- `ctrProcesarMaterial()`: lee `accionMaterial` para distinguir alta, edición y eliminación. Usa `filter_var` para el ID y captura errores de validación o restricciones de MariaDB.
- `obtenerDatosValidos($entrada)`: recibe el arreglo del formulario, limpia cadenas con `trim`, valida longitudes de acuerdo con MariaDB y devuelve solo los campos descriptivos válidos.
- `mdlMostrarMateriales()`: hace `SELECT` y une el material con sus tablas de tipo y proveedor.
- `mdlIngresarMaterial($datos)`: inserta una ficha; no envía stock, por lo que MariaDB aplica el valor por defecto `0.00`.
- `mdlEditarMaterial($datos)`: actualiza nombre, tipo, proveedor y unidad; deliberadamente no toca `stock_actual`.
- `mdlBorrarMaterial($idMaterial)`: bloquea la fila con `FOR UPDATE`, comprueba el historial de kardex y solo permite borrarla si no hay movimientos. La FK de `detalle_material` protege además planificaciones asociadas.

El parámetro `$datos` es un arreglo asociativo: cada clave se transforma en un placeholder SQL. Por ejemplo, `:nombre` recibe `$datos["nombre"]`; el texto del formulario nunca se concatena como SQL.

## Reproducir el módulo

1. Lista los campos del formulario y compáralos con la tabla `materiales`.
2. Valida en el servidor aunque también uses `required`/`maxlength` en HTML.
3. Usa SQL parametrizado para los valores recibidos.
4. No permitas que un formulario de edición reemplace arbitrariamente existencias.
5. Comprueba que las claves foráneas de tipo/proveedor apunten a registros existentes.

## Pruebas y errores

- Crear un material y comprobar que inicia en `0.00`.
- Editar tipo o unidad y comprobar que `stock_actual` no cambia.
- Usar un tipo/proveedor inexistente: la base debe rechazarlo y la aplicación debe mostrar un error claro.
- Para agregar existencias, se necesita el módulo de entradas/kardex; no cambies el stock a mano.

## Ejercicio

Añade una validación de servidor para que el nombre no sea solo espacios, manteniendo el límite de 150 caracteres del SQL.
