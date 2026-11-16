# 14 — Cambios realizados

## 2026-11-11 — Compatibilidad antes de la migración de pedidos

- Se corrigió el acceso a `estado_planificacion`: si la columna aún no existe, las filas se consideran `LEGACY` sin emitir `Undefined array key`.
- La pantalla consulta `information_schema` y avisa/deshabilita las acciones dependientes de la migración.
- El modelo preserva planificaciones heredadas al editar antes de migrar y rechaza registrar materiales nuevos sin el esquema requerido.
- Validación de VS Code: sin errores reportados en modelo, controlador y vista de pedidos. No se conectó a MariaDB ni se ejecutó la migración.

## 2026-11-11 — Nombre de base de datos en la conexión

Se actualizó el DSN PDO en `modelos/conexion.php` para apuntar a `sistemainventario_v3.2`, manteniendo `localhost`, usuario `root` y contraseña vacía. La base debe existir y contener el esquema importado antes de iniciar la aplicación. No se creó ni modificó ninguna base de datos.

## 2026-11-11 — Pedidos, materiales y salida real

### Cambios

- Los pedidos y su edición dejan de reservar/devolver stock de productos.
- Los materiales asociados al pedido se guardan como planificación y no cambian el stock.
- Se añadió el registro de consumos reales de materiales con verificación de existencias y transacción.
- Cada consumo guarda stock anterior/resultante, usuario, motivo y referencia al pedido en `kardex`.
- El formulario de consumo usa protección CSRF y una clave única de solicitud para evitar reenvíos duplicados.
- El CRUD de materiales ya no permite editar existencias directamente; al crear, el stock queda en cero.
- Los registros existentes de `detalle_material` se distinguen como `LEGACY` mediante una migración aditiva; no se ejecutó SQL ni se modificaron conexión, datos o existencias.

### Migración pendiente

`Migraciones/2026-11-11-kardex-consumos-idempotentes.sql` agrega la clasificación de planificaciones antiguas y una clave de solicitud nullable y única a `kardex`. Crear respaldo y ejecutar primero en una copia de pruebas.

### Comprobación

Pendiente validar con MariaDB de pruebas y ejecutar flujos end-to-end. Se debe informar cualquier prueba de sintaxis por separado de una prueba de base activa.

### Recuperación

La migración no se ha ejecutado. Antes de probarla, respalda la base y aplícala en una copia. Si la prueba falla, restaura esa copia completa; no elimines las columnas nuevas si ya se registraron consumos, porque se perdería la protección contra reenvíos y la clasificación de las filas.

### Riesgo histórico conocido

Las filas existentes de `detalle_material` y el stock de v3.2 no permiten determinar de forma fiable si esos datos eran planificación o consumo real. No se migraron ni corrigieron automáticamente.

## Revisión CRUD — estado de esta entrega

### Cambios realizados

- Categorías: formularios POST funcionales para crear/editar/eliminar; validación del identificador y CSRF; SQL preparado con nombre de tabla fijo.
- Productos: se retiró la modificación de stock desde alta/edición; se añadió CSRF y bloqueo de borrado cuando el producto tenga movimientos en kardex.
- Herramientas: el CRUD deja las existencias en solo lectura y crea nuevas herramientas con stock cero; se impide borrarlas con historial heredado o kardex.
- Movimientos de herramientas: se agregó motivo y escritura de stock + kardex dentro de una transacción; el historial de pantalla conserva la lectura de `inventario` heredado.
- Materiales, tipos, proveedores, clientes y pagos: se añadió protección CSRF a las acciones existentes, conservando el CRUD/validaciones previas.
- Usuarios y acceso: se agregó CSRF en login, configuración inicial y acciones de administración; las acciones de administración requieren perfil Administrador. No se completó la revisión de carga de fotos ni la autorización de los demás módulos.
- No se añadieron columnas de herramientas que no estén en el SQL, no se modificaron clientes duplicados y no se ejecutó SQL de migración.

### Documentación y pruebas

- Se creó `15_DIAGNOSTICO_Y_CORRECCION_CRUD.md` con los hallazgos, soluciones y pendientes.
- Se creó `13_Guia_para_Programar_Sin_IA.md` como tutorial MVC/PDO para principiantes.
- Los diagnósticos de VS Code no reportaron errores en los archivos PHP editados principales; esto no equivale a una prueba funcional.
- Queda pendiente ejecutar en una copia de pruebas de XAMPP todos los flujos CRUD, restricciones FK y movimientos. No se confirmó la base activa desde phpMyAdmin.
- Pedidos todavía requiere auditoría CRUD y verificación de migración; compras, ajustes, recuperación, auditoría y reportes continúan pendientes.

### Ejercicio de aprendizaje

Revisa el flujo de categoría de la vista al modelo y describe qué ocurre si el ID de una categoría no es un entero positivo. Después intenta implementar una búsqueda por nombre siguiendo `13_Guia_para_Programar_Sin_IA.md`.

## 2026-11-12 — Auditoría CRUD, revisión de sintaxis y verificación de esquema

### Cambios realizados

- Se agregó registro transaccional de auditoría a altas, cambios y eliminaciones/desactivaciones de categorías, catálogos, productos, materiales, herramientas, usuarios y pedidos.
- Los pedidos registran además cambios de estado y consumo real. Los pagos registran alta, edición y anulación; las operaciones de inventario y compras ya registran sus acciones.
- Las contraseñas y sus hashes no se copian al JSON de auditoría. La acción antigua de eliminación de usuarios ahora desactiva la cuenta y conserva sus referencias; también se impide desactivar al último administrador activo.
- Se corrigieron errores de sintaxis detectados en `modelos/pagos.modelo.php`, `modelos/recuperacion.modelo.php` y `modelos/reportes.modelo.php`, y se integró el registro de anulación de pagos dentro de su transacción.
- La pantalla de auditoría informa que requiere la migración si la tabla aún no existe. El botón legado de usuarios aclara que desactiva y conserva el historial.
- Se creó `16_Guia_Modulos_Inventario_Compras_Seguridad_Reportes.md` con explicación MVC, consultas, variables, reglas y pasos de prueba.

### Base activa y migraciones

- Una conexión PDO de solo lectura devolvió `DATABASE() = sistemainventario_v3.2` y 17 tablas. No se insertaron ni actualizaron registros. No se abrió la interfaz de phpMyAdmin; el resultado corresponde al servicio local al que se conecta el DSN.
- La base activa no contiene `auditoria`, `solicitud_recuperacion`, `detalle_material.estado_planificacion`, `kardex.id_solicitud`, ni las columnas de estado/anulación de pagos e idempotencia de compras. Las migraciones no se ejecutaron.
- El volcado `sistemainventario_v3.2.sql` declara `sistemainventario_v3` en su encabezado. La conexión sigue apuntando a `sistemainventario_v3.2` y no se cambió.

### Pruebas realizadas y no realizadas

- `php -l` pasó en los 55 archivos PHP del proyecto después de corregir los tres errores encontrados.
- Ocho consultas de lectura reales pasaron contra la base activa: existencias, kardex, consumos por pedido, compras por proveedor, herramientas, pedidos, pagos y listado de compras. No se imprimieron datos personales; los resultados fueron de 0 a 13 filas.
- No se ejecutaron operaciones CRUD de escritura, movimientos, compras, pagos, cambios de usuario, pruebas de navegador ni envío SMTP. No declarar esos flujos funcionalmente validados.
- Debido a que la tabla de auditoría y otras columnas aún no están en la base activa, las escrituras dependientes de esas migraciones se deben probar solo después de aplicarlas a una copia autorizada y respaldada.
