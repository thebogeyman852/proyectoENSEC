# 03 — Módulo de pedidos

## Propósito y problema resuelto

El pedido registra cliente, productos solicitados, precios y materiales previstos para fabricar. Como INNOMADE fabrica bajo pedido, crear o editar una orden no modifica las existencias. La salida física se registra con una acción independiente.

## Archivos y comunicación MVC

- `vistas/modulos/pedidos.php`: muestra formulario, pedidos, planificación, consumos y formulario de salida.
- `controladores/pedidos.controlador.php`: valida acciones `crear`, `editar`, `estado`, `eliminar` y `consumir_material`.
- `modelos/pedidos.modelo.php`: consulta y modifica `pedido`, `detalle_pedido`, `detalle_material`, `materiales` y `kardex`.
- `index.php`: envía POST autenticados de la ruta `pedidos` al controlador.

La vista envía campos `name=...`; el controlador valida y forma datos; el modelo ejecuta SQL parametrizado; después se redirige a la misma ruta y se presenta un mensaje de sesión.

## Tablas y consultas

- `pedido`: `idpedido`, `idusuario`, `idcliente`, `total`, `estado`, `fecha_pedido`.
- `detalle_pedido`: `idpedido`, `idproducto`, `cantidad`, `precio_unitario`.
- `detalle_material`: `idpedido`, `idmaterial`, `cantidad_usada` y, tras la migración, `estado_planificacion`. Los registros antiguos quedan `LEGACY`; los nuevos son `PLANIFICADO`.
- `kardex`: salidas reales de material con usuario, pedido, motivo y stock anterior/resultante.

El modelo calcula el total desde cantidad × precio de cada línea; inserta cabecera y detalles; consulta pagos para no permitir editar el total por debajo de lo ya pagado; y agrupa los movimientos del kardex por `referencia_id` para mostrar consumos reales.

## Funciones principales, paso a paso

### Controlador

- `ctrMostrarPedidos()` llama al modelo y devuelve la lista que consume la vista.
- `ctrProcesarPedido()` lee `$_POST["accionPedido"]`. `$accion` decide la operación; `$idPedido` y `$idMaterial` identifican registros; `$cantidad` y `$motivo` son valores recibidos del formulario. Antes de cualquier escritura comprueba la sesión y el identificador CSRF; luego valida IDs, formatos y longitudes de cada acción.
- `obtenerDetalles()` recorre los arreglos paralelos `idproducto[]`, `cantidad[]` y `precio_unitario[]`. Cada índice representa una línea del pedido. Si una línea está vacía se omite; si está incompleta o inválida se rechaza toda la operación.
- `obtenerMateriales()` recorre `idmaterial[]` y `cantidad_usada[]`, que aquí representan la planificación. El ciclo no consulta ni actualiza stock.
- `redirigir()` guarda el mensaje en `$_SESSION` y envía al usuario de vuelta a pedidos.

### Modelo

- `mdlMostrarPedidos()` consulta pedidos, detalles, planificación, pagos y movimientos del kardex en bloques. Usa `$indicePedidos` para ubicar cada resultado hijo dentro de su pedido sin hacer una consulta SQL por cada fila.
- `mdlCrearPedido()` abre una transacción para insertar cabecera, líneas de producto y planificación. No contiene un `UPDATE productos` ni un `UPDATE materiales`.
- `mdlEditarPedido()` mantiene la regla de pagos, reemplaza líneas y solo elimina planificaciones etiquetadas `PLANIFICADO`; las filas antiguas `LEGACY` no se borran ni se convierten.
- `mdlCambiarEstado()` actualiza estado sin restar ni restaurar inventario.
- Al cambiar a `CANCELADO`, `mdlCambiarEstado()` bloquea el pedido, devuelve los movimientos reales de salida aún no compensados y actualiza el estado en la misma transacción. Cada entrada conserva su salida original y usa una clave derivada del movimiento de origen para no devolverla dos veces.
- `mdlBorrarPedido()` bloquea la eliminación cuando existe cualquier pago, fila de materiales o movimiento kardex vinculado. Esto evita perder relaciones/historial.
- `mdlRegistrarConsumoMaterial()` valida sus argumentos, abre transacción, bloquea el pedido/material, rechaza pedidos `CANCELADO`, compara stock, actualiza el saldo e inserta el kardex. En cualquier excepción, ejecuta `rollBack()`.
- `aCentimos()` transforma una cadena decimal de hasta dos posiciones en centésimas enteras. Por ejemplo, `"12.30"` pasa a `1230`; comparar enteros evita errores de redondeo binario.

### Consultas de inventario

La escritura lógica se parece a este SQL parametrizado:

```sql
SELECT stock_actual FROM materiales WHERE idmaterial = :id FOR UPDATE;
UPDATE materiales SET stock_actual = :stock WHERE idmaterial = :id;
INSERT INTO kardex
  (id_solicitud, tipo_item, id_item, tipo_movimiento, cantidad, stock_anterior,
   stock_resultante, referencia_tipo, referencia_id, motivo, id_usuario)
VALUES
  (:solicitud, 'material', :material, 'salida', :cantidad, :anterior,
   :resultante, 'pedido', :pedido, :motivo, :usuario);
```

`FOR UPDATE` bloquea la fila mientras dura la transacción para que dos usuarios no validen simultáneamente el mismo saldo y lo dejen negativo. Los valores dinámicos se enlazan con placeholders PDO; los valores constantes (`material`, `salida`, `pedido`) pertenecen a la operación.

## Cómo funciona el consumo

1. El formulario envía pedido, material, cantidad, motivo, token CSRF y una clave de solicitud única.
2. El controlador verifica formatos, sesión, campos obligatorios y CSRF.
3. El modelo inicia una transacción y bloquea pedido/material con `SELECT ... FOR UPDATE`.
4. Convierte el stock y la cantidad a centésimas enteras para comparar decimales de dos posiciones sin errores de coma flotante.
5. Si el stock alcanza, actualiza `materiales.stock_actual` e inserta la salida en `kardex`, usando `id_solicitud` para identificar un reenvío del mismo formulario.
6. Confirma la transacción. Ante error, revierte ambas escrituras.

La migración `Migraciones/2026-11-11-kardex-consumos-idempotentes.sql` etiqueta los registros antiguos como `LEGACY` sin reescribirlos y añade una clave única para que un reenvío del mismo formulario no reste stock dos veces. El editor solo reemplaza filas marcadas `PLANIFICADO`. Antes de aplicarla, las planificaciones y salidas se ocultan/deshabilitan y el modelo conserva las filas históricas; la vista indica que hace falta la migración.

## Cancelación y devolución de consumos

La cancelación consulta exclusivamente `kardex` con `tipo_item='material'`, `tipo_movimiento='salida'` y `referencia_tipo='pedido'`. Las filas de `detalle_material` con estado `PLANIFICADO` no son consumos y no se devuelven. Por cada salida real pendiente de compensación, se bloquea el material, se incrementa su stock y se inserta una entrada en kardex con referencia al pedido y motivo que identifica el movimiento original.

La clave de cada entrada se deriva del pedido y del `id_kardex` de la salida original. El bloqueo del pedido serializa cancelaciones y consumos concurrentes; ante reintentos o una cancelación posterior a reabrir el pedido, las entradas existentes se validan y omiten. Una falla de stock, artículo, kardex o auditoría revierte todas las devoluciones y evita que cambie el estado. No se eliminan ni modifican las salidas originales. La interfaz pide confirmación y las rutas de consumo/salida rechazan pedidos cancelados.

## Construirlo manualmente

1. Dibuja los campos que necesita un pedido y compáralos con el SQL.
2. Implementa `obtenerDetalles()` y `obtenerMateriales()` en el controlador, usando el índice de los arreglos para relacionar campos de cada línea.
3. Implementa `mdlCrearPedido()` y una función privada de inserción de detalles; verifica que no hagan cambios de stock.
4. Implementa el consumo como operación separada. Primero bloquea y lee; después valida; luego actualiza e inserta historial en la misma transacción.
5. Usa placeholders PDO (`:idpedido`) para valores y `commit()` solo al final de todas las escrituras exitosas.
6. Agrega manejo de excepciones con `rollBack()` y una redirección después de cada POST.

## Pruebas

- Crear un pedido y comparar `materiales.stock_actual` y `productos.stock` antes/después: deben mantenerse iguales.
- Después de migrar, comprobar que el material anterior se muestra como histórico y un plan nuevo aparece como planificado.
- Crear, editar y cambiar estados distintos de `CANCELADO`: no deben variar existencias.
- Registrar una salida menor que el stock: debe reducir material y añadir una fila kardex coherente.
- Cancelar después de consumos reales: devolver exactamente las salidas, conservarlas y excluir materiales solo planificados.
- Repetir cancelación o reabrir y cancelar de nuevo: no duplicar devoluciones.
- Intentar consumir o registrar una salida asociada a un pedido cancelado: debe rechazarse.
- Intentar exceder stock: no debe cambiar ni la tabla materiales ni el kardex.
- Reenviar el mismo POST: debe rechazarse como duplicado.
- Forzar una devolución inválida en un pedido con varios consumos: la transacción no debe dejar stock, kardex o estado parcialmente modificados.

Las pruebas de planificación y consumo real requieren una base de pruebas con la migración aplicada.

### Verificación funcional en `innomade_pruebas` — 2026-11-15

La inspección previa encontró dos pedidos existentes, sin salidas materiales de kardex asociadas a ellos; por eso no se alteraron datos históricos. Se probaron por HTTP `index.php?ruta=pedidos` (GET 200 y POST 302): creación con planificación, consumos reales, cancelación, reintento, reapertura y nueva cancelación. Las consultas directas confirmaron que solo las salidas reales se devolvieron, las salidas originales permanecieron, las planificaciones no cambiaron stock y cada entrada quedó ligada al pedido con cantidad, saldos, usuario y motivo correctos. Los intentos repetidos y los consumos posteriores a cancelar no generaron movimientos.

También se probó una cancelación con dos materiales y se forzó temporalmente que el segundo no tuviera capacidad para recibir su devolución: la solicitud falló, el pedido siguió sin cancelar, el primer stock volvió al saldo previo al intento y no quedó ninguna entrada parcial. Tras restaurar el saldo de prueba, la cancelación completa funcionó. La eliminación de un pedido con historial relacionado fue bloqueada, y un producto inexistente no dejó una cabecera huérfana.

Al finalizar, se retiraron los pedidos, detalles y movimientos temporales y se restauraron las existencias de materiales y productos. No quedaron salidas ligadas a pedidos. Los eventos de auditoría no se borraron por ser inmutables; al cierre había 17 eventos en el módulo de pedidos. El esquema ya tenía `kardex.id_solicitud` y su índice único, por lo que no se ejecutaron migraciones. La base original no se modificó.

## Datos históricos y precaución

El dump v3.2 contiene `detalle_material` para pedidos previos y existencias ya descontadas, pero no ofrece manera de saber automáticamente qué filas eran planificación y cuáles consumo real. La migración solo les asigna la etiqueta `LEGACY`; no modifica cantidades ni stock. Se necesita conciliar esos datos con el usuario antes de migrarlos al kardex.

## Errores frecuentes

- **`Unknown column id_solicitud`**: aún no se ejecutó la migración aditiva.
- **`Unknown column` en una consulta**: revisa exactamente nombres y acentos en el SQL.
- **No se descuenta stock**: confirma que registraste el consumo real, no solo el pedido.
- **La operación se revierte**: busca error PDO, referencia inexistente o stock insuficiente.

## Ejercicio

Agrega una validación para que el motivo no acepte solo espacios. Explica qué capa debe validar el campo y por qué la vista por sí sola no es suficiente.
