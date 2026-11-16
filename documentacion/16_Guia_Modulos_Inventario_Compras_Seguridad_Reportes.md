# 16 — Guía de inventario, compras, pagos, usuarios, auditoría y reportes

Esta guía explica cómo seguir los nuevos flujos en el proyecto MVC y cómo practicar su implementación. Las operaciones que cambian datos requieren las migraciones documentadas y deben probarse primero en una copia de la base.

## Recorrido común MVC

En `index.php` se cargan los controladores y modelos y se envían las solicitudes POST a la acción correspondiente. La vista construye el formulario, el controlador valida sesión, CSRF, identificadores y valores, y el modelo ejecuta SQL preparado con PDO. Las operaciones con stock o auditoría agrupan los cambios en una transacción.

Un patrón transaccional usado por los modelos es:

```php
$conexion = Conexion::conectar();
$conexion->beginTransaction();
try {
    // Consultar y bloquear el registro, validar y guardar.
    $conexion->commit();
} catch (Throwable $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    throw $error;
}
```

`beginTransaction()` abre una unidad de trabajo; `commit()` confirma el conjunto completo; `rollBack()` deshace los cambios si algo falla. No se debe actualizar el stock desde una vista.

## Inventario y kardex

### Propósito y archivos

- `vistas/modulos/inventario.php`: filtros y formulario de entrada, salida o ajuste.
- `controladores/inventario.controlador.php`: valida los datos, el permiso y el token CSRF.
- `modelos/inventario.modelo.php`: obtiene stock, lo bloquea durante la operación, calcula el nuevo saldo, lo actualiza y registra kardex.
- `modelos/movimientos.modelo.php` y `vistas/modulos/movimientos.php`: mantienen la pantalla heredada de movimientos de herramientas y la integran con el historial.

Los artículos controlados son materiales (`materiales.stock_actual`) y herramientas (`herramienta.stock`). El formulario no escribe directamente esas columnas. El selector usa nombres de tabla y columna definidos por el modelo, nunca valores enviados por el navegador.

### Qué ocurre al registrar

1. El controlador valida artículo, tipo, cantidad, motivo, usuario y `id_solicitud`.
2. El modelo inicia una transacción y evita solicitudes duplicadas.
3. La consulta de stock usa `FOR UPDATE`, que bloquea la fila mientras se calcula el saldo.
4. Una salida solo se acepta si hay existencias suficientes; herramientas usan unidades enteras.
5. Se actualiza el artículo y se inserta el movimiento de kardex con stock anterior y resultante, referencia, motivo y usuario.
6. La auditoría se registra en la misma transacción; si falla un paso, también se revierte el stock.

Consulta orientativa del historial:

```sql
SELECT fecha, tipo_item, id_item, tipo_movimiento, cantidad,
       stock_anterior, stock_resultante, referencia_tipo, referencia_id,
       motivo, id_usuario
FROM kardex
WHERE fecha >= :desde AND fecha < DATE_ADD(:hasta, INTERVAL 1 DAY)
ORDER BY fecha DESC, id_kardex DESC;
```

El modelo incorpora filtros por artículo, clase de movimiento, usuario y fechas. Los ajustes comparan el stock del sistema con el físico y dejan el registro en `ajuste_inventario` y kardex.

## Compras

### Archivos y responsabilidad

- `vistas/modulos/compras.php`: selecciona proveedor y permite agregar varios renglones de material o herramienta.
- `controladores/compras.controlador.php`: valida proveedor, fecha, artículo, cantidades, precios, observación y token CSRF.
- `modelos/compras.modelo.php`: calcula totales en el servidor, serializa el registro de compra, aumenta el stock y escribe sus detalles y entradas de kardex.

Cada renglón contiene tipo de artículo, ID, cantidad y costo unitario. El subtotal se calcula multiplicando cantidad por costo; se manejan cantidades monetarias en centavos para evitar errores de redondeo. Si hay renglones repetidos del mismo artículo, el stock resultante se acumula en el orden de la transacción.

SQL principal utilizado:

```sql
INSERT INTO compra (id_solicitud, id_proveedor, fecha, nro_documento, total, observacion, id_usuario)
VALUES (:solicitud, :proveedor, :fecha, :documento, :total, :observacion, :usuario);

INSERT INTO detalle_compra (id_compra, tipo_item, id_item, cantidad, costo_unitario)
VALUES (:compra, :tipo, :item, :cantidad, :costo);
```

El proveedor se valida y se relaciona con la cabecera de compra. Cada renglón se valida contra el catálogo indicado por `tipo_item` (materiales o herramientas); la relación del detalle es polimórfica y la base no tiene una FK directa hacia ambos catálogos. El proveedor seleccionado debe coincidir con el proveedor de ficha de cada material; el modelo lo comprueba bajo bloqueo transaccional y el formulario filtra las opciones. Las herramientas no tienen proveedor en su ficha.

La compra se confirma junto con el aumento de stock y las entradas de kardex. La anulación conserva compra, detalle y movimientos originales; antes de revertir existencias, el modelo verifica que todavía haya saldo suficiente. Si el material fue consumido después, la anulación se rechaza para no crear stock negativo ni borrar el historial.

## Herramientas

El esquema actual contiene `idherramienta`, `nombre`, `estado` y `stock`; el código no inventa campos de marca, modelo, código o ubicación. El alta del CRUD comienza con stock cero. La pantalla y el controlador están en `vistas/modulos/herramientas.php` y `controladores/herramientas.controlador.php`; las consultas están en `modelos/herramientas.modelo.php`.

El stock cambia desde movimientos de inventario, no desde el CRUD de herramientas. Los registros con movimientos heredados o kardex no se eliminan. La consulta del historial combina el kardex con registros históricos de `inventario`.

## Pagos

- `vistas/modulos/pagos.php`: muestra pagos y el total/saldo del pedido.
- `controladores/pagos.controlador.php`: valida sesión, CSRF, pedido, monto, método, clave de solicitud y motivo de anulación.
- `modelos/pagos.modelo.php`: bloquea el pedido, calcula los saldos en centavos, suma solo pagos vigentes y rechaza montos mayores al saldo.
- `modelos/pedidos.modelo.php` y `modelos/reportes.modelo.php`: muestran total pagado y saldo pendiente.

El alta valida el saldo dentro de una transacción para que pagos concurrentes no superen el total. La clave única `id_solicitud` hace que un reenvío exacto no cree otro pago y rechaza la reutilización de esa clave con datos diferentes. La edición calcula el saldo excluyendo el pago editado. No se aceptan nuevos pagos para pedidos cancelados ni se permite aumentar sus pagos existentes. La anulación conserva el registro y requiere las columnas de estado, usuario y motivo; no hay borrado físico. La edición de pedidos calcula el mínimo contra pagos registrados, no contra pagos anulados.

Esquema de cálculo:

```sql
SELECT total, estado FROM pedido WHERE idpedido = :id FOR UPDATE;
SELECT COALESCE(SUM(monto), 0)
FROM pagos
WHERE idpedido = :idpedido AND estado = 'registrado';
```

La migración `2026-11-15-pagos-idempotentes.sql` añade la clave nullable y un índice único; los pagos anteriores permanecen con clave NULL. Debe aplicarse una sola vez en una copia de pruebas. La migración de anulación `2026-11-12-inventario-compras-seguridad.sql` añade el estado y los datos de auditoría de la anulación.

## Usuarios y recuperación de contraseña

- `vistas/modulos/usuarios.php` y `vistas/modulos/login.php`: pantallas de administración, acceso y solicitudes.
- `controladores/usuarios.controlador.php`: exige perfil Administrador para cambios administrativos, CSRF y validación de datos.
- `modelos/usuarios.modelo.php`: crea y modifica cuentas; las contraseñas se reciben con `password_hash()`. Desactivar conserva las filas y sus relaciones.
- `controladores/recuperacion.controlador.php` y `modelos/recuperacion.modelo.php`: gestionan solicitud, decisión administrativa, expiración y uso único.
- `vistas/modulos/cambiar-password.php`: obliga a reemplazar la contraseña temporal antes de usar los demás módulos.

Las cuentas creadas por el flujo normal comienzan con `debe_cambiar_password = 0`. Solo la aprobación de una recuperación establece el valor en `1`; el login entonces exige una solicitud temporal aprobada, vigente y no usada.

La solicitud pública recibe solo el nombre de usuario; el servidor busca la cuenta activa y usa exclusivamente su Gmail registrado. La respuesta no revela si la cuenta existe. Se conserva una solicitud pendiente o una temporal vigente y se limita a una solicitud nueva por cuenta cada cinco minutos. El Administrador consulta el historial de estados y solo puede aprobar solicitudes pendientes de otra cuenta.

La clave temporal se genera de forma aleatoria con diez caracteres, se almacena como hash, vence a los cinco minutos y queda invalidada tras usarse. La contraseña nueva debe ser distinta de la temporal. Si el envío falla, la transacción revierte el cambio de contraseña y la aprobación. El sistema envía el mensaje mediante PHPMailer y Gmail; no se incluyen credenciales en el repositorio. Instala PHPMailer con Composer y configura Apache con variables locales `INNOMADE_SMTP_HOST`, `INNOMADE_SMTP_PORT`, `INNOMADE_SMTP_USER`, `INNOMADE_SMTP_PASSWORD` (contraseña de aplicación de Google), `INNOMADE_SMTP_FROM` y, opcionalmente, `INNOMADE_SMTP_FROM_NAME`.

No escribas el hash ni la contraseña temporal en archivos de registro. Para probar recuperación se necesita que el usuario tenga un correo Gmail válido y que las tablas/columnas de la migración estén aplicadas a la base de pruebas.

## Auditoría

- `modelos/auditoria.modelo.php`: inserta eventos y consulta un máximo de 1.000, con filtros por módulo, usuario y fecha.
- `controladores/auditoria.controlador.php`: limita la consulta al perfil Administrador.
- `vistas/modulos/auditoria.php`: presenta eventos sin controles para editarlos o borrarlos.

Los eventos contienen responsable, acción, módulo, tabla, ID y JSON anterior/nuevo. El guardado del evento está dentro de la misma transacción que la operación auditada. La migración crea triggers que rechazan `UPDATE` y `DELETE` sobre auditoría. Los hashes y contraseñas se excluyen del JSON.

## Reportes

- `modelos/reportes.modelo.php`: ejecuta consultas de existencias, consumos por pedido, compras por proveedor, herramientas, pedidos y pagos.
- `controladores/reportes.controlador.php`: valida filtros de fechas y solicita el reporte elegido.
- `vistas/modulos/reportes.php`: muestra resultados consultables y solo lectura.

Los reportes de movimientos reutilizan `ModeloInventario::mdlListarMovimientos`; fechas y filtros usan parámetros preparados. Las salidas tienen un límite para evitar devolver historiales enormes. Los reportes no deben calcular consumos a partir de materiales planificados: el consumo real proviene de salidas del kardex referidas a un pedido.

## Cómo probar manualmente en XAMPP

Haz una copia de seguridad de la base de prueba y revisa primero la sección de migraciones. No pruebes altas, ajustes, compras, pagos ni recuperación con datos reales.

1. Inventario: registrar una entrada; confirmar stock y kardex; intentar una salida mayor al stock y confirmar que no cambie ninguno de los dos.
2. Ajuste: registrar un conteo físico distinto; confirmar registro de ajuste y movimiento correspondiente.
3. Compra: seleccionar proveedor; confirmar que solo se ofrezcan sus materiales y que el servidor rechace materiales de otro proveedor; agregar herramientas y renglones repetidos, y verificar total, stock y kardex. Anular una compra con stock suficiente y probar que se rechace si ya no hay stock para revertir.
4. Herramientas: crear y editar una herramienta; comprobar que el formulario no cambie el stock; verificar que una herramienta con movimientos no se elimine.
5. Pagos: registrar varios pagos sin exceder el total; reenviar la misma clave y confirmar que no duplica; intentar reutilizarla con datos distintos y superar el saldo; anular un pago y comprobar que el historial se conserva y el saldo se recalcula. Confirmar que no se permita cobrar pedidos cancelados.
6. Usuarios: crear, editar, desactivar y activar cuentas con perfiles diferentes; comprobar que un usuario no pueda desactivar al último administrador.
7. Recuperación: solicitar con usuario conocido/desconocido, comprobar que el Gmail indicado no se puede cambiar, aprobar y rechazar como administrador, probar correo, vencimiento, uso único, límite de solicitudes y cambio obligatorio.
8. Auditoría: hacer una operación permitida, filtrar el evento y confirmar que los botones no permiten modificar ni borrar el registro.
9. Reportes: comparar cada resultado con un `SELECT` equivalente en phpMyAdmin y verificar filtros de fecha.

La verificación funcional de Compras se hizo exclusivamente en `innomade_pruebas`: registró una compra temporal con materiales del proveedor seleccionado (incluyendo dos renglones del mismo artículo) y una herramienta; comprobó cabecera, proveedor, tres detalles, total, acumulación de stock, tres movimientos de kardex, lecturas de lista/detalle, rechazo del reenvío duplicado, del proveedor/artículo inexistentes y de material asociado a otro proveedor. La prueba confirmó que un error no dejara cambios parciales. Se retiraron la compra, sus detalles y movimientos temporales y se restauró el stock; permanecen tres eventos de auditoría de la prueba porque la tabla es inmutable. No se modificó ni se conectó la base original. No se probaron anulaciones de compra, pagos, envíos SMTP ni pruebas de botones.

La verificación funcional de Pagos se hizo exclusivamente en `innomade_pruebas`: el pedido de prueba tenía total $1,800.00 y saldo completo. Se registró $1,799.90, se reenvió la misma solicitud sin duplicar, se rechazó reutilizar la clave con otro monto, se rechazaron $1,800.01 al editar y un pago para pedido cancelado, se aceptó editar al saldo exacto de $1,800.00 y se anuló el pago. Se comprobó que la lista conserva el pago anulado y que ya no se cuenta para el saldo; el reenvío de una solicitud anulada fue rechazado. Una edición de pedido con un pago anulado no quedó bloqueada por ese pago; se forzó un error de FK después de esa validación y la transacción restauró todos los datos. Los registros temporales se retiraron y permanecen tres eventos inmutables de auditoría. El pedido y el total originales quedaron restaurados. No se modificó la base original ni se probó concurrencia simultánea.

## Errores comunes al aprender

- Si el stock cambia sin kardex, la operación no se hizo dentro de la misma transacción o existe otra ruta de escritura directa.
- Si aparecen `SQLSTATE[42S22]` o tabla desconocida, compara las columnas existentes con las migraciones; no inventes una columna en el formulario.
- Si un `DELETE` falla por una clave foránea, conserva el registro o desactívalo en lugar de quitar la relación.
- Si un monto excede el saldo, revisa que los pagos anulados no se sumen y que el pedido se haya bloqueado con `FOR UPDATE`.
- Si Gmail no envía, revisa PHPMailer y variables locales, nunca agregues una contraseña al código.
- Un lint correcto no demuestra que la transacción, FK, sesión o correo funcionen en XAMPP.

## Dependencias de esquema

La conexión de desarrollo apunta a `innomade_pruebas`, donde están aplicadas las migraciones de kardex, inventario, compras, pagos y auditoría; la migración `2026-11-15-pagos-idempotentes.sql` se aplicó allí para estas pruebas. La base original `sistemainventario_v3.2` no se modificó. Consulta [Migraciones](./Migraciones/) y aplica cada archivo solo una vez en una copia respaldada.
