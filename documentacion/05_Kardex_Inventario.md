# 05 — Kardex e inventario

## Esquema existente

El dump `sistemainventario_v3.2.sql` ya define `kardex` con identificador, fecha, `tipo_item`, `id_item`, entrada/salida, cantidad, stock anterior/resultante, costo, tipo/ID de referencia, motivo y usuario. La tabla es InnoDB. Su relación a artículos es polimórfica (`tipo_item` + `id_item`), por lo que no tiene una FK directa a cada catálogo.

Los flujos integrados para pedidos son el consumo real de materiales y su devolución al cancelar el pedido. Aún no representan el módulo completo de entradas, ajustes, devoluciones generales, compras o movimientos de herramientas.

## Escrituras atómicas

Una transacción agrupa el `UPDATE materiales` y el `INSERT kardex`: ambas escrituras deben confirmarse juntas. Si la salida supera el stock o falla la inserción, `rollBack()` revierte la actualización.

El modelo bloquea el pedido y el material con `FOR UPDATE`, lee el stock, verifica suficiencia, calcula el nuevo saldo, y guarda `tipo_item='material'`, `tipo_movimiento='salida'`, `referencia_tipo='pedido'`, el ID del pedido, motivo, usuario y ambos saldos.

Al cancelar, el modelo vuelve a bloquear el pedido y selecciona únicamente esas salidas reales. Por cada salida aún no compensada, bloquea y aumenta el stock del material e inserta una entrada en kardex con referencia al mismo pedido. Una clave estable derivada del ID de la salida identifica su devolución; las salidas originales permanecen intactas. Si cualquier devolución falla, también se revierte el cambio de estado y todo el trabajo previo de la transacción.

## Evitar doble envío

El formulario envía un identificador aleatorio `id_solicitud`. La migración también agrega `estado_planificacion` a `detalle_material`, con `LEGACY` como valor inicial para no reinterpretar los registros anteriores. En `kardex` crea una columna nullable y un índice único. Los registros históricos quedan sin clave y no se reescriben; los nuevos movimientos deben llevar clave. Un envío repetido choca con la restricción única y se revierte la transacción.

La migración debe aplicarse una sola vez en `sistemainventario_v3.2`, después de crear respaldo y verificar que se trabaja sobre una copia/entorno de pruebas. No se ejecutó automáticamente. La pantalla consulta `information_schema` y no ofrece las acciones de materiales hasta detectar las dos columnas de la migración.

## Plan de pruebas

En un entorno de prueba, contrasta cada fila del kardex con el stock. Prueba salida exacta al stock disponible, una centésima por encima, doble envío de la misma solicitud y fallo inducido al insertar. Comprueba que cada caso deja el número esperado de movimientos y que nunca queda stock cambiado sin su kardex.

## Pendiente

Implementar entradas manuales, ajustes, devoluciones generales, compras y movimiento de herramientas; consultar un kardex filtrable; y decidir cómo conservar el historial cuando se elimina o archiva un artículo.
