# Flujo del sistema de inventario

Para la guía de instalación, explicación detallada de los módulos y ruta de aprendizaje para desarrolladores, consulta [documentacion/GUIA_DESARROLLADOR.md](./documentacion/GUIA_DESARROLLADOR.md). Para el historial fechado de cambios, consulta [documentacion/README.md](./documentacion/README.md).

## Esquema vigente

La aplicación usa el esquema `sistemainventario_v2` definido en `sistemainventario_v2.sql`. Los nombres y relaciones importantes son:

- `usuario` mantiene las cuentas y la fecha del último acceso.
- `categoria` clasifica las líneas de un pedido.
- `cliente` y `pedido` relacionan al cliente con el usuario que registra la operación.
- `detalle_pedido` almacena categorías, cantidades y precios; el esquema no define una tabla independiente de productos.
- `pagos` registra abonos vinculados al pedido.
- `tipo_material`, `proveedor` y `materiales` administran materiales con su unidad y stock decimal.
- `detalle_material` relaciona materiales consumidos con un pedido.
- `herramienta` guarda el stock de herramientas.
- `inventario` conserva el historial de entradas y salidas de herramientas.

Por eso se retiró la ruta de catálogo de productos: el flujo de pedidos usa las categorías como líneas de detalle, tal como permite el esquema actual.

## Flujo de una petición

1. `index.php` carga controladores y modelos, inicia la sesión y procesa las escrituras POST antes de emitir HTML.
2. `ControladorPlantilla::crtPlantilla()` renderiza el acceso o la aplicación autenticada.
3. La plantilla acepta únicamente las rutas enumeradas; los módulos de clientes, proveedores y tipos de material comparten `catalogo.php`.
4. Los controladores validan la entrada y coordinan las operaciones de modelos.
5. Los modelos ejecutan consultas PDO mediante `Conexion::conectar()`. Los cambios de pedido, pago, consumo de materiales y movimiento de herramientas se realizan dentro de transacciones cuando afectan varias filas.

## Acceso inicial

Las cuentas nuevas se guardan usando `password_hash`; las contraseñas antiguas en texto plano se actualizan a hash después de un inicio de sesión correcto. Si la tabla `usuario` está vacía, la pantalla de acceso permite crear la primera cuenta administradora.

## Operaciones disponibles

| Ruta | Operaciones | Tablas |
| --- | --- | --- |
| `usuarios` | Alta, edición, activación/desactivación y baja | `usuario` |
| `categorias` | Alta, edición, baja y consulta | `categoria` |
| `clientes` | Alta, edición, baja y consulta | `cliente` |
| `proveedores` | Alta, edición, baja y consulta | `proveedor` |
| `tipos-material` | Alta, edición, baja y consulta | `tipo_material` |
| `materiales` | Alta, edición, baja y consulta | `materiales` |
| `herramientas` | Alta, edición, baja y consulta | `herramienta` |
| `movimientos` | Registro y consulta de entradas/salidas | `inventario` |
| `pedidos` | Alta, edición de cliente y detalles, estado, baja y consulta | `pedido`, `detalle_pedido`, `detalle_material` |
| `pagos` | Alta, edición, baja y consulta | `pagos` |

Un movimiento de inventario actualiza el stock de la herramienta y agrega un renglón al historial en una misma transacción. Los movimientos se conservan como bitácora y no se editan ni eliminan desde la interfaz.

Al crear o editar un pedido, los consumos asociados descuentan stock de materiales. Al borrar un pedido, sus consumos se revierten y la base de datos elimina sus detalles y pagos asociados mediante las relaciones en cascada.

## Configuración

La conexión de desarrollo apunta a MySQL en `localhost`, base `sistemainventario_v2`, usuario `root` y contraseña vacía. Ajusta estos datos en `modelos/conexion.php` si el entorno usa otras credenciales.
