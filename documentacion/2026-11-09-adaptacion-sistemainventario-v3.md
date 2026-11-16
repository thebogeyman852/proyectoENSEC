# Registro de cambios — 9 de noviembre de 2026

## Objetivo

Adaptar la aplicación al dump `sistemainventario_v3.sql`. A diferencia de v2, esta versión incorpora `productos` y hace que `detalle_pedido.idproducto` identifique cada producto vendido.

## Cambios realizados

- `modelos/conexion.php`: cambia la conexión local a la base `sistemainventario_v3`.
- `controladores/productos.controlador.php` y `modelos/productos.modelo.php`: agregan el CRUD del catálogo con validación para campos obligatorios, stock, precio y dimensiones.
- `vistas/modulos/productos.php`, `vistas/modulos/menu.php`, `vistas/plantilla.php` e `index.php`: habilitan el catálogo de productos en el menú, el enrutamiento y los formularios. El formulario permite subir imágenes PNG, JPG, GIF o WEBP (máximo 2 MB), ver una vista previa y conservar, cambiar o quitar la imagen al editar.
- `controladores/pedidos.controlador.php` y `modelos/pedidos.modelo.php`: cambian las líneas de pedido de categoría a producto; las escrituras reservan el stock en una transacción.
- `vistas/modulos/pedidos.php`: muestra productos, precio y stock, y comunica el efecto de cancelar o borrar un pedido.
- `FLUJO_Y_DIAGRAMA.md`, `diagrama_clases.mmd` y guías: actualizan el esquema documentado a v3.

## Regla de stock

Al crear un pedido se descuenta el stock de cada producto. Al editar un pedido activo, se devuelve el stock anterior y se reserva el nuevo detalle dentro de la misma transacción. Al cancelar o eliminar un pedido activo se devuelve su stock; al reactivar un pedido cancelado se valida y reserva de nuevo. Si no alcanza el stock, la operación se revierte. Los precios de las líneas quedan guardados en el detalle del pedido.

El consumo de materiales conserva su regla previa. Cancelar un pedido no revierte materiales porque el sistema los registra como usados; editar o borrar sí restaura y vuelve a aplicar el consumo según el detalle.

## Migración y comprobaciones

El dump v3 define otra relación en `detalle_pedido`; importar este esquema no convierte automáticamente registros de v2. Antes de reemplazar una base con datos, hacer una copia y preparar una migración de datos específica.

Se revisaron las consultas y campos contra el dump v3. Queda pendiente importar la base en MariaDB local y probar los CRUD, pedidos con stock suficiente e insuficiente, edición, cancelación, reactivación, borrado y pagos con datos de prueba. No se afirma que esos flujos hayan sido validados contra una base activa.
