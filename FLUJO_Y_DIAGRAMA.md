# Flujo del sistema de inventario

Para la guía de instalación, explicación detallada de los módulos y ruta de aprendizaje para desarrolladores, consulta [documentacion/GUIA_DESARROLLADOR.md](./documentacion/GUIA_DESARROLLADOR.md). Para el historial fechado de cambios, consulta [documentacion/README.md](./documentacion/README.md).

## Esquema vigente

La aplicación usa la base `sistemainventario_v3`, cuyo esquema actualizado está en `sistemainventario_v3.2.sql`. Los nombres y relaciones importantes son:

- `usuario` mantiene las cuentas y la fecha del último acceso.
- `categoria` clasifica productos.
- `productos` guarda código, descripción, existencias, precio e información dimensional.
- `cliente` y `pedido` relacionan al cliente con el usuario que registra la operación.
- `detalle_pedido` relaciona cada pedido con productos, cantidades y precios unitarios.
- `pagos` registra abonos vinculados al pedido.
- `tipo_material`, `proveedor` y `materiales` administran materiales con su unidad y stock decimal.
- `detalle_material` contiene materiales planificados para un pedido. La columna `estado_planificacion` se agrega mediante migración para conservar registros anteriores como `LEGACY`.
- `herramienta` guarda el stock de herramientas.
- `inventario` conserva el historial heredado de entradas/salidas de herramientas.
- `kardex` registra movimientos con artículo, tipo, cantidad, saldo anterior/resultante, referencia, motivo y usuario.
- `compra`, `detalle_compra` y `ajuste_inventario` están en el esquema; sus flujos de aplicación siguen pendientes.

Crear, editar o cambiar el estado de un pedido no altera inventario. Los materiales planificados se separan de las salidas reales, que se registran en `kardex` cuando se usan.

## Flujo de una petición

1. `index.php` carga controladores y modelos, inicia la sesión y procesa las escrituras POST antes de emitir HTML.
2. `ControladorPlantilla::crtPlantilla()` renderiza el acceso o la aplicación autenticada.
3. La plantilla acepta únicamente las rutas enumeradas; los módulos de clientes, proveedores y tipos de material comparten `catalogo.php`.
4. Los controladores validan la entrada y coordinan las operaciones de modelos.
5. Los modelos ejecutan consultas PDO mediante `Conexion::conectar()`. El consumo real de materiales actualiza stock y kardex en una transacción; los movimientos heredados de herramientas usan `inventario`.

## Acceso inicial

Las cuentas nuevas se guardan usando `password_hash`; las contraseñas antiguas en texto plano se actualizan a hash después de un inicio de sesión correcto. Si la tabla `usuario` está vacía, la pantalla de acceso permite crear la primera cuenta administradora.

## Operaciones disponibles

| Ruta | Operaciones | Tablas |
| --- | --- | --- |
| `usuarios` | Alta, edición, activación/desactivación y baja | `usuario` |
| `categorias` | Alta, edición, baja y consulta | `categoria` |
| `productos` | Alta, edición, baja y consulta | `productos`, `categoria` |
| `clientes` | Alta, edición, baja y consulta | `cliente` |
| `proveedores` | Alta, edición, baja y consulta | `proveedor` |
| `tipos-material` | Alta, edición, baja y consulta | `tipo_material` |
| `materiales` | Alta, edición, baja y consulta | `materiales` |
| `herramientas` | Alta, edición, baja y consulta | `herramienta` |
| `movimientos` | Registro y consulta heredada de entradas/salidas de herramientas | `inventario` |
| `pedidos` | Alta, edición de cliente/detalles, estado, consumo real y consulta | `pedido`, `detalle_pedido`, `detalle_material`, `materiales`, `kardex` |
| `pagos` | Alta, edición, baja y consulta | `pagos` |

Un movimiento de inventario actualiza el stock de la herramienta y agrega un renglón al historial en una misma transacción. Los movimientos se conservan como bitácora y no se editan ni eliminan desde la interfaz.

La salida real de materiales comprueba el stock y actualiza `materiales` junto con `kardex` en una transacción. No se descuenta stock al crear/editar/cancelar un pedido. La aplicación bloquea borrar pedidos con pagos, materiales o movimientos relacionados.

## Configuración

La conexión de desarrollo apunta a MySQL en `localhost`, base `sistemainventario_v3`, usuario `root` y contraseña vacía. Ajusta estos datos en `modelos/conexion.php` si el entorno usa otras credenciales.
