# 15 — Diagnóstico y corrección de CRUD

## Alcance y estado

Se revisaron las rutas y archivos MVC de los CRUD existentes. Esta entrega implementa correcciones en categorías, productos, herramientas, catálogos, materiales, pagos y movimientos de herramientas. No se modificaron registros de la base ni se ejecutaron migraciones. Los clientes duplicados no se tocaron.

La comprobación disponible fue estática mediante los diagnósticos de VS Code. No se pudo probar la aplicación contra MariaDB/phpMyAdmin de XAMPP, por lo que las pruebas de alta, consulta, edición y eliminación quedan pendientes en una copia de pruebas.

## Hallazgos y acciones

| Módulo | Archivo afectado | Problema encontrado | Causa del problema | Solución aplicada | Resultado de la prueba |
|---|---|---|---|---|---|
| Categorías | `vistas/modulos/categorias.php`, `controladores/categorias.controlador.php`, `modelos/categorias.modelo.php`, `index.php` | La vista dependía de botones/modales sin manejadores funcionales y el borrado anterior podía dispararse por GET. | No había flujo POST completo ni JavaScript que conectara las acciones de edición. | Se reemplazó por formularios POST directos; se validan IDs y token CSRF; el modelo limita la tabla/campos permitidos; errores PDO se muestran con un mensaje general. | Sin errores reportados por diagnósticos estáticos. CRUD real y restricciones FK pendientes en XAMPP. |
| Productos | `vistas/modulos/productos.php`, `controladores/productos.controlador.php`, `modelos/productos.modelo.php` | Crear/editar aceptaba `stock` y escribía existencias directamente desde el formulario. | El stock estaba incluido en la validación y SQL del CRUD, sin movimiento de inventario. | Se eliminó `stock` del alta/edición; queda visible solo como lectura y el alta usa el default `0` del esquema. Se añadió CSRF. El borrado verifica que no haya fila de kardex antes de eliminar. | Sin errores reportados por diagnósticos estáticos. Falta probar carga de imagen, restricciones de pedidos y transacción con MariaDB. |
| Materiales | `vistas/modulos/materiales.php`, `controladores/materiales.controlador.php` | Formularios de alta/edición/eliminación no enviaban token CSRF. | El controlador no validaba token y los formularios no lo incluían. | Se añadió validación del token en el controlador y el campo oculto en los tres formularios. El stock continúa fuera del CRUD, como ya estaba implementado. | Sin errores reportados por diagnósticos estáticos. Falta probar el CRUD y su FK en XAMPP. |
| Tipos de material, proveedores y clientes | `vistas/modulos/catalogo.php`, `controladores/catalogos.controlador.php` | Las acciones POST carecían de CSRF. | No se incluía token en el formulario ni se comprobaba antes de ejecutar el CRUD. | Se añadió token a crear/editar/eliminar y verificación en el controlador. Los nombres de tabla/columnas continúan limitados por la lista estática del modelo. | Sin errores reportados por diagnósticos estáticos. No se alteraron clientes duplicados. Probar relaciones y eliminación en XAMPP. |
| Herramientas | `vistas/modulos/herramientas.php`, `controladores/herramientas.controlador.php`, `modelos/herramientas.modelo.php` | Alta/edición permitía cambiar stock y el borrado podía desvincular el historial heredado. | El stock se escribía desde el CRUD y no se comprobaban movimientos antes de borrar. | Stock nuevo queda en cero; el CRUD ya no acepta ni escribe stock. La eliminación se bloquea si existe historial en `inventario` o `kardex`; las acciones llevan CSRF. Los campos código/marca/modelo/ubicación no se agregaron porque no existen en la tabla actual. | Sin errores reportados por diagnósticos estáticos. Falta comprobar transacciones/consultas y visualización en XAMPP. |
| Movimientos de herramientas | `controladores/movimientos.controlador.php`, `modelos/movimientos.modelo.php`, `vistas/modulos/movimientos.php` | Los movimientos existentes actualizaban stock y `inventario`, pero no el kardex ni guardaban motivo. | El módulo utilizaba únicamente el esquema heredado de movimientos de herramientas. | Se solicita motivo, se actualiza stock y se inserta en `kardex` dentro de la misma transacción; el historial combina movimientos históricos de `inventario` con registros nuevos del kardex. | Diagnóstico estático sin errores reportados. No se probó la transacción con MariaDB. |
| Pagos | `vistas/modulos/pagos.php`, `controladores/pagos.controlador.php` | Formularios sin token CSRF. | El controlador no verificaba token. | Se agregó token a crear/editar/eliminar y validación antes del cambio. La validación de saldo y bloqueo del pedido con transacción ya existían y se conservaron. | Sin errores reportados por diagnósticos estáticos. No se probó pago real, saldo o eliminación. |
| Usuarios | `vistas/modulos/usuarios.php`, `vistas/modulos/login.php`, `controladores/usuarios.controlador.php` | Las acciones de usuarios y los formularios de acceso no validaban CSRF; el controlador no exigía rol administrador para administrar usuarios. | Formularios y controlador no compartían un token y las rutas confiaban en la presentación del menú. | Se agregó CSRF en login, creación inicial, alta, edición, cambio de estado y eliminación; el controlador exige perfil Administrador para alta/administración. La revisión de carga de fotos, permisos del resto de módulos y flujos de contraseña sigue pendiente. | Sin errores reportados por diagnósticos estáticos en los archivos revisados. No se probó con sesiones/roles reales en XAMPP. |
| Pedidos | `vistas/modulos/pedidos.php`, `controladores/pedidos.controlador.php`, `modelos/pedidos.modelo.php` | Existen cambios anteriores de planificación y consumo real, con una migración aditiva aún no ejecutada. | La BD activa no se pudo comprobar y algunas funciones requieren las columnas de migración. | Se conservaron los cambios anteriores; no se reintrodujo descuento automático por crear o editar pedidos. | Validación en MariaDB y aplicación de la migración en copia de pruebas pendientes. |

## Esquema y conexión

- La conexión en `modelos/conexion.php` actualmente apunta a `localhost` y `sistemainventario_v3.2`. No se cambió en esta entrega.
- El nombre efectivo de la base seleccionada en phpMyAdmin no se pudo confirmar.
- El SQL disponible define `herramienta` con `idherramienta`, `nombre`, `estado` y `stock`; por eso no se inventaron columnas para código, marca, modelo o ubicación.
- El SQL define `kardex` con tipo de artículo, ID, tipo/cantidad, stock anterior/resultante, referencia, motivo y usuario. La tabla no tiene FK polimórfica hacia cada artículo.
- No se modificó ningún dato ni se aplicó una migración.

## Pruebas que faltan en XAMPP

Usa una copia de la base de datos y verifica desde phpMyAdmin que la copia contiene el esquema esperado antes de probar:

1. Categorías: crear, editar, borrar una categoría libre y confirmar que una categoría asociada no se borre.
2. Productos: crear y editar sin que el stock cambie; intentar eliminar un producto con referencias y verificar que se conserve.
3. Herramientas: crear y editar; confirmar stock inicial cero; registrar entrada y salida con motivo; intentar salida mayor al stock y confirmar rollback.
4. Verificar que cada movimiento nuevo crea una fila `tipo_item='herramienta'` en `kardex` con stock anterior/resultante, usuario y motivo.
5. Catálogos, materiales y pagos: crear/editar/eliminar registros de prueba y verificar las claves foráneas; intentar enviar un formulario sin token y comprobar que no se guarde.
6. Usuarios y pedidos: aún necesitan su auditoría funcional completa.

No consideres terminado un CRUD hasta ejecutar estas pruebas contra la base de pruebas.

## Actualización 2026-11-12 — auditoría y revisión estática

Se añadieron registros de auditoría dentro de las transacciones a operaciones CRUD de categorías, catálogos, productos, materiales, herramientas, usuarios y pedidos. Pedidos también registra cambios de estado y consumo real. Pagos registra creación, edición y anulación; inventario y compras ya generan eventos. El historial excluye contraseñas y hashes. La acción antigua de eliminación de usuarios ahora desactiva y conserva el registro, y no permite dejar el sistema sin un administrador activo.

| Módulo | Archivo afectado | Problema encontrado | Causa del problema | Solución aplicada | Resultado de la prueba |
|---|---|---|---|---|---|
| Catálogos y categorías | `modelos/catalogos.modelo.php`, `modelos/categorias.modelo.php` | Las acciones CRUD no generaban registros anteriores/nuevos en auditoría. | No se insertaban eventos dentro de la transacción del cambio. | Se añadieron bloqueos de lectura, eventos de alta/edición/eliminación y rollback si falla la auditoría. | `php -l` pasó. Prueba de escritura pendiente: no está creada la tabla de auditoría en la base activa. |
| Productos | `modelos/productos.modelo.php` | Las altas, cambios y eliminaciones no quedaban auditadas. | No había inserción transaccional de auditoría. | Se añadió auditoría; el borrado mantiene las comprobaciones de historial y no toca stock desde el CRUD. | `php -l` pasó. CRUD real pendiente. |
| Materiales y herramientas | `modelos/materiales.modelo.php`, `modelos/herramientas.modelo.php` | Altas y cambios no dejaban trazabilidad. | El CRUD no guardaba valores previos/nuevos ni auditaba el borrado protegido. | Se añadieron eventos transaccionales y snapshot anterior; stock sigue fuera del CRUD. | `php -l` pasó. CRUD real pendiente. |
| Usuarios | `modelos/usuarios.modelo.php`, `vistas/modulos/usuarios.php` | El borrado físico podía romper relaciones e historial; acciones no quedaban auditadas. | El modelo ejecutaba `DELETE` y no registraba cambios de estado. | La operación de borrado anterior desactiva, preserva referencias, registra la acción y protege al último administrador. El formulario informa que conserva historial. | `php -l` pasó. Cambios de estado reales pendientes. |
| Pedidos y consumos | `modelos/pedidos.modelo.php` | Altas, ediciones, estados y consumos reales no tenían eventos completos. | Las transacciones guardaban el negocio y el kardex, pero no el registro de auditoría correspondiente. | Se registran encabezado/detalle, estados y consumos dentro de la misma transacción; se agregó comprobación de detalle de pedido antes de eliminar. | `php -l` pasó. Escrituras requieren migraciones aún no aplicadas. |
| Pagos | `modelos/pagos.modelo.php` | Un bloque de auditoría de anulación estaba fuera de todo método y el archivo no compilaba. | El bloque quedó insertado en alcance de clase; la anulación tampoco guardaba el evento en su transacción. | Se quitó el bloque inválido y se insertó la auditoría después de actualizar el pago, antes del `commit`. | Error detectado y corregido; `php -l` pasó. No se probó anulación real. |
| Recuperación de contraseña | `modelos/recuperacion.modelo.php` | La declaración de rechazo estaba anidada dentro de aprobación y el modelo no compilaba. | Faltaba cerrar el método de aprobación antes de declarar rechazo. | Se separaron los métodos, se verificó la actualización de estado y se conservó el rollback ante error. | Error detectado y corregido; `php -l` pasó. No se probó Gmail ni una solicitud real. |
| Reportes | `modelos/reportes.modelo.php` | El método para comprobar columnas estaba declarado dentro del método de filtros y el archivo no compilaba. | Faltaba cerrar el bloque de filtros antes de la declaración siguiente. | Se corrigió el alcance del método y se incluye explícitamente el modelo de inventario reutilizado. | Error detectado y corregido; `php -l` pasó y las consultas de lectura principales funcionaron. |
| Auditoría | `vistas/modulos/auditoria.php` | Si la migración no está aplicada, la consulta podía terminar en error de PDO sin mensaje útil. | La vista solo controlaba errores de autorización. | Ahora informa que hace falta la migración documentada y no presenta una tabla de eventos ficticia. | Sintaxis comprobada; base activa confirma que la migración está pendiente. |

### Verificación de la base local y estado real

- La conexión PDO de solo lectura confirmó `sistemainventario_v3.2` en el servidor local de XAMPP y 17 tablas. No se inspeccionó visualmente phpMyAdmin y no se cambió `modelos/conexion.php`.
- El dump SQL declara `sistemainventario_v3`; por tanto, el archivo no debe confundirse con el nombre de la base a la que apunta la aplicación.
- La base activa carece de las columnas/tablas de las dos migraciones requeridas. No se ejecutaron scripts SQL ni escrituras.
- Se ejecutó `php -l` en los 55 archivos PHP: resultado satisfactorio después de reparar tres errores de sintaxis.
- Se ejecutaron ocho consultas de lectura de los módulos contra la base local, todas satisfactorias. No equivale a probar formularios, permisos, CRUD, transacciones, kardex, SMTP ni botones.
- Las funciones que escriben auditoría dependen de la tabla creada por la migración. No deben probarse en la base activa antes de aplicar la migración en una copia de pruebas.

## Actualización 2026-11-14 — prueba funcional real de categorías

Las pruebas se realizaron después de aplicar las migraciones, exclusivamente sobre `innomade_pruebas`. El origen `sistemainventario_v3.2` no se usó para escribir. Se inició temporalmente el servidor PHP integrado porque `http://localhost/INNOMADE/index.php` respondía 404; las solicitudes reales se enviaron a `http://127.0.0.1:8082/INNOMADE/index.php?ruta=categorias`.

| Operación | Prueba realizada | Resultado |
|---|---|---|
| READ | Abrir la ruta, renderizar el formulario con token CSRF y cargar la tabla desde MariaDB. | HTTP 200; categorías existentes visibles. |
| CREATE | Enviar el formulario válido con CSRF y verificar mensaje, fila listada y persistencia en MariaDB. | Correcto. |
| Validación CSRF | Enviar alta sin token válido y comprobar mensaje. | Rechazada; no se creó registro. |
| Validación requerida | Enviar nombre vacío y comprobar mensaje. | Rechazada; no se creó registro. |
| UPDATE | Editar nombre y descripción por POST; comprobar respuesta y leer valores de MariaDB. | Correcto y persistente. |
| DELETE relacionado | Intentar borrar categoría existente con productos asociados. | FK lo impidió; mensaje claro y categoría conservada. |
| DELETE permitido | Borrar el registro temporal sin relaciones y verificar que ya no existe. | Correcto. |

Se comprobó que la tabla `categoria` terminó con sus 6 filas originales y sin filas temporales `QA_CAT_`; las relaciones de productos con las categorías originales permanecieron intactas. La tabla de auditoría conserva 6 eventos identificables de las pruebas (2 altas, 2 ediciones y 2 eliminaciones); no se borraron, pues el historial es inmutable.

No se modificaron archivos del CRUD de categorías. No se realizó una inspección visual autenticada con captura de pantalla. Durante la comprobación del navegador se observaron errores globales de recursos (`jquery.cookie.js` 404 y `$(...).tree is not a function`); no impidieron las solicitudes HTTP del CRUD y quedan pendientes de diagnóstico en la revisión visual general.

**Estado:** CRUD de categorías probado por HTTP y contra MariaDB de pruebas. Los demás módulos se prueban progresivamente.

## Actualización 2026-11-14 — prueba funcional real de clientes

Se usó el mismo procedimiento HTTP contra `innomade_pruebas`, sin escribir en `sistemainventario_v3.2`. Antes de probar, `cliente` tenía 4 filas. La FK `pedido.idcliente` usa `RESTRICT`; el cliente 1 tenía un pedido asociado.

| Operación | Prueba realizada | Resultado |
|---|---|---|
| READ | Abrir `index.php?ruta=clientes`, comprobar HTTP 200, formulario, CSRF y listado. | Correcto. El campo teléfono presenta `maxlength=20`. |
| CREATE | Alta válida por POST; comprobar mensaje, fila en listado y persistencia en MariaDB. | Correcto. |
| Validación CSRF | Alta sin token válido. | Rechazada con mensaje; sin fila nueva. |
| Validaciones de campos | Nombre vacío y teléfono de 21 caracteres. | Ambos rechazados con mensaje; sin fila nueva. |
| UPDATE | Cambiar nombre, teléfono y dirección por POST y volver a leerlos de MariaDB. | Correcto y persistente. |
| DELETE relacionado | Intentar borrar el cliente 1, con pedido asociado. | FK lo impidió; se mostró el mensaje de registros relacionados y se conservó el pedido. |
| DELETE permitido | Borrar el cliente temporal sin pedidos. | Correcto; ya no existe en MariaDB. |

Al finalizar, `cliente` conservó sus 4 filas originales, no quedaron filas temporales `QA_CLIENTE_` y el pedido del cliente 1 permaneció. La auditoría conserva 3 eventos de prueba (alta, edición y eliminación); no se borraron por ser historial inmutable.

No se modificaron el controlador, modelo ni vista del catálogo de clientes. No se hizo captura visual autenticada. No se encontraron fallos funcionales en las operaciones probadas.

**Estado:** CRUD de clientes probado por HTTP y contra MariaDB de pruebas. Los demás módulos se prueban progresivamente.

## Actualización 2026-11-14 — prueba funcional real de proveedores

Las pruebas se realizaron exclusivamente sobre `innomade_pruebas`. Antes de probar había 4 proveedores y ninguna compra. El esquema tiene un índice no único en `compra.id_proveedor`, pero **no tiene una clave foránea** desde `compra` hacia `proveedor`; sí hay una FK desde `materiales.idproveedor`.

| Operación | Prueba realizada | Resultado |
|---|---|---|
| READ | Abrir `index.php?ruta=proveedores`, comprobar HTTP 200, formulario, CSRF y listado. | Correcto. Los límites de teléfono (20) y dirección (255) aparecen en el formulario. |
| CREATE | Alta válida por POST; comprobar mensaje, fila listada y persistencia. | Correcto. |
| UPDATE | Editar nombre, teléfono y dirección; volver a leerlos de MariaDB. | Correcto y persistente. |
| Validación CSRF | Alta sin token válido. | Rechazada con mensaje; sin fila nueva. |
| Validaciones de campos | Nombre vacío, teléfono de 21 caracteres y dirección de 256 caracteres. | Rechazados con mensaje; sin filas nuevas. |
| DELETE con compra | Crear temporalmente una cabecera de compra identificable para el proveedor de prueba e intentar eliminarlo por el CRUD. | Bloqueado con mensaje específico; proveedor y compra permanecieron durante la prueba. Se eliminó únicamente la cabecera temporal después de comprobar el bloqueo. |
| DELETE con material | Intentar eliminar proveedor existente con materiales asociados. | La FK lo impidió; se conservó el proveedor y sus materiales. |
| DELETE permitido | Eliminar el proveedor de prueba después de retirar su compra temporal. | Correcto; eliminado de MariaDB. |

### Causa y corrección

El borrado genérico no consultaba compras y la base no tiene FK `compra.id_proveedor`; podía dejar una compra huérfana. `modelos/catalogos.modelo.php` ahora comprueba compras asociadas dentro de la transacción y rechaza el borrado con un mensaje específico. `modelos/compras.modelo.php` bloquea la fila del proveedor (`FOR UPDATE`) al registrar una compra, coordinando la creación con el borrado para evitar la carrera entre ambas operaciones. No se alteró el esquema ni se ejecutaron migraciones. El bloqueo se probó funcionalmente; no se hizo una prueba concurrente.

La validación de teléfono aplica el máximo de longitud existente; no se agregó un patrón de formato porque el esquema y las reglas actuales no especifican uno. No se modificaron el controlador ni la vista de proveedores. Los dos modelos modificados pasaron `php -l`.

Al finalizar, quedaron los 4 proveedores originales, ninguna compra (como al inicio), ningún registro temporal `QA_PROV_` y 3 eventos de auditoría de prueba (alta, edición y eliminación). No se hizo captura visual autenticada.

**Observación independiente:** durante la verificación final apareció en `innomade_pruebas` la categoría `Juancho` (`idcategoria=12`), ausente en `sistemainventario_v3.2`, con un evento `crear` de Categorías a las 20:23:58. No fue creada ni modificada por las pruebas de Proveedores y se dejó intacta.

## Actualización 2026-11-14 — prueba funcional real de tipos de material

Las pruebas se realizaron por HTTP contra la aplicación local y se verificaron con consultas de solo lectura a MariaDB. La conexión de la aplicación y las consultas confirmaron `innomade_pruebas`; no se usó la base original para escrituras. Antes de probar había 4 tipos y 4 materiales.

El esquema activo confirma `tipo_material.idtipo_material` como clave primaria autoincremental, `nombre VARCHAR(100) NOT NULL` y `descripcion TEXT NULL`. La FK `materiales.idtipo_material` usa `ON DELETE RESTRICT`; los tipos 1 y 2 tienen dos materiales cada uno.

| Operación | Prueba realizada | Resultado |
|---|---|---|
| READ | Abrir `index.php?ruta=tipos-material`; comprobar HTTP 200, formulario con CSRF y listado. | Correcto. |
| CREATE | Alta por POST; comprobar mensaje, fila en el listado y valores persistidos en MariaDB. | Correcto. |
| UPDATE | Editar nombre y descripción por POST; volver a consultar ambos valores en MariaDB. | Correcto y persistente. |
| Validación CSRF | Enviar una creación con token inválido. | Rechazada; mensaje de sesión vencida y ninguna fila creada. |
| Validación requerida | Enviar nombre vacío. | Rechazada; sin fila nueva. |
| Límite del nombre | Enviar 101 caracteres, cuando la columna admite 100. | Rechazado; sin fila nueva. |
| Límite UTF-8 de descripción | Enviar 65.536 bytes (16.384 caracteres UTF-8 de 4 bytes). | Rechazado antes de escribir; sin truncamiento ni fila nueva. |
| DELETE relacionado | Intentar borrar el tipo 1, que tiene materiales. | Bloqueado por la FK; tipo y materiales conservaron sus valores. |
| DELETE permitido | Borrar el tipo temporal creado sin relaciones. | Correcto; ya no existe en MariaDB. |

### Causa y corrección

La columna `descripcion` es `TEXT`, cuyo límite depende de bytes (65.535), pero el controlador contaba caracteres. Para textos UTF-8 multibyte, el controlador podía aceptar una descripción que superara la capacidad de la columna; además, el modo SQL activo no incluye modo estricto. En `controladores/catalogos.controlador.php`, la validación de la descripción de tipos de material ahora mide bytes con `strlen`; el nombre continúa validándose por caracteres y contra su límite de 100. No se modificaron el modelo ni la vista, el esquema ni las reglas de relación. No se cambiaron Categorías, Clientes o Proveedores.

Al finalizar, `tipo_material` conservó sus 4 filas iniciales, `materiales` sus 4 filas y no quedaron registros temporales `QA_TIPO_20261114_2034%`. La tabla de auditoría conserva los 3 eventos de alta, edición y eliminación de la prueba; no se borraron. El controlador pasó `php -l`.

**Reproducción manual:** iniciar sesión en la aplicación conectada a `innomade_pruebas`, abrir `index.php?ruta=tipos-material`, crear un nombre único con descripción, editar ambos campos y confirmar en MariaDB; probar nombre vacío, nombre de más de 100 caracteres y una descripción multibyte superior a 65.535 bytes; intentar borrar un tipo con materiales y luego borrar el tipo de prueba sin relaciones. Confirmar que no queden filas temporales y que los registros asociados permanezcan.

No se realizó captura visual autenticada ni prueba de concurrencia. El servidor PHP temporal usado para los POST fue detenido al terminar.

**Estado:** CRUD de tipos de material probado por HTTP y contra MariaDB de pruebas.

## Actualización 2026-11-14 — prueba funcional real de materiales e inventario

Las operaciones se probaron por HTTP contra la aplicación local y se verificaron con consultas de solo lectura a MariaDB. La conexión confirmó `innomade_pruebas`; no se escribieron datos en la base original. El esquema activo de `materiales` contiene `nombre VARCHAR(150)`, `idtipo_material`, `idproveedor`, `unidad_medida VARCHAR(50)` y `stock_actual DECIMAL(10,2) DEFAULT 0.00`. No tiene columna `descripcion` ni una relación separada con `categoria`; la clasificación disponible es el tipo de material. No se inventaron campos ni se modificó el esquema.

| Operación | Prueba realizada | Resultado |
|---|---|---|
| READ | Abrir `index.php?ruta=materiales`; comprobar HTTP 200, formulario, CSRF, clasificación, proveedor y listado. | Correcto. El stock se muestra, pero no existe un control de formulario para editarlo. |
| CREATE | Alta temporal por POST, incluso enviando un `stock_actual` falsificado. | Correcto; el stock quedó en cero por el valor predeterminado de la base y el POST no lo cambió. |
| UPDATE | Cambiar nombre, tipo, proveedor y unidad; enviar también un stock falsificado. | Correcto; todos los campos disponibles persistieron y el stock permaneció en cero. |
| Validaciones y relaciones | Token CSRF inválido, nombre vacío o de 151 caracteres, unidad vacía o de 51 caracteres, tipo inexistente y proveedor inexistente. | Todos rechazados; no quedó ninguna fila de prueba. Las FK rechazaron las referencias inexistentes. |
| DELETE permitido | Eliminar por el CRUD el material temporal sin pedidos ni kardex. | Correcto; se eliminó de MariaDB. |
| DELETE asociado a pedido | Intentar borrar el material 4, que tiene un detalle de pedido asociado. | Bloqueado por `fk_detmat_material` (`RESTRICT`); material y detalle permanecieron. |
| Entrada de inventario | Registrar por HTTP una entrada de `0.75` sobre el material 1. | Correcto; stock pasó de `50.00` a `50.75` y el kardex guardó tipo, artículo, cantidad, saldo anterior/resultante, referencia de solicitud y motivo. |
| Salida de inventario | Registrar por HTTP una salida de `0.75` sobre el mismo material. | Correcto; kardex registró la salida y el stock volvió a `50.00`. |
| Salida excesiva | Intentar retirar `50.01` con stock `50.00`. | Rechazada con mensaje; stock sin cambios y sin fila de kardex para esa solicitud. |
| DELETE con movimientos | Intentar borrar el material 1 después de los movimientos de prueba. | Bloqueado por el modelo; el material se conservó. |
| CSRF de inventario | Enviar una entrada con token inválido. | Rechazada; no cambió stock ni creó movimiento. |

### Persistencia y limpieza

La tabla de materiales terminó con sus 4 filas iniciales y los valores originales; el material temporal `QA_MAT_20261114_2042` fue eliminado. El stock del material 1 volvió a `50.00`. Se conservaron deliberadamente las 2 filas de kardex y 2 eventos de auditoría de entrada/salida que prueban la trazabilidad; no se borró historial. También permanecen 3 eventos de auditoría del alta, edición y eliminación del material temporal. No se creó un pedido nuevo ni se probó un consumo originado desde pedidos; sí se comprobó la FK contra un detalle ya existente.

No se encontró un fallo funcional en las operaciones probadas, por lo que no se modificaron el controlador, el modelo ni la vista de materiales/inventario. Las dos relaciones de eliminación están protegidas: el kardex se verifica explícitamente en el modelo y los detalles de pedido quedan protegidos por la FK. El stock se cambia mediante movimientos transaccionales que actualizan existencias y kardex juntos.

**Reproducción manual:** iniciar sesión en la aplicación conectada a `innomade_pruebas`; abrir `index.php?ruta=materiales`, crear y editar un material de prueba, confirmar que no hay campo de stock y verificar que el stock inicial sea cero; luego abrir `index.php?ruta=inventario`, registrar entrada y salida con motivo, verificar stock y kardex después de cada operación, e intentar una salida superior al stock. Intentar borrar un material con pedido y otro con movimientos; comprobar que ambos se conservan. Eliminar únicamente el material temporal sin relaciones y verificar que el stock original quedó restaurado.

No se hizo captura visual autenticada ni prueba de concurrencia. El servidor PHP y la sesión temporales se limpiaron al finalizar.

**Estado:** CRUD de Materiales e inventario probado por HTTP y contra MariaDB de pruebas.

## Actualización 2026-11-14 — prueba funcional real de productos

Las pruebas HTTP y las verificaciones de MariaDB se realizaron exclusivamente en `innomade_pruebas`. El esquema activo confirma que `productos.stock` es un `INT NOT NULL DEFAULT 0`, separado de `materiales.stock_actual DECIMAL(10,2)`. La FK `detalle_pedido.idproducto` tiene `ON DELETE RESTRICT`; el producto 1 ya tenía un detalle de pedido asociado. No se ejecutaron migraciones ni se alteró el esquema.

| Operación | Prueba realizada | Resultado |
|---|---|---|
| READ | Abrir `index.php?ruta=productos`; comprobar HTTP 200, formulario CSRF, categoría, listado y etiqueta explícita del stock de producto terminado. | Correcto. El formulario no contiene un campo editable `stock`. |
| CREATE | Crear un producto con categoría, código, descripción, precio y dimensiones, enviando además un `stock` falsificado. | Correcto; MariaDB asignó stock `0` por defecto y el dato enviado fue ignorado. |
| UPDATE | Editar código, descripción, categoría, precio y dimensiones; incluir otro stock falsificado. | Correcto; los cambios persistieron y `productos.stock` no cambió. |
| Validaciones y relaciones | CSRF inválido, código obligatorio, precio cero y categoría inexistente. | Rechazados; no dejaron productos nuevos. |
| DELETE permitido | Eliminar el producto temporal sin pedidos ni kardex. | Correcto; no quedó fila temporal. |
| DELETE relacionado | Intentar borrar el producto 1, con pedido asociado. | Bloqueado por la FK `RESTRICT`; producto y detalle permanecieron. |
| Separación de inventarios | Intentar registrar `tipo_item=producto` por el endpoint de movimientos de materiales/herramientas. | Rechazado; no cambió el stock del producto ni se agregó kardex de productos. Los stocks de materiales permanecieron intactos. |
| Validación de longitudes UTF-8 | Crear código de 45 caracteres y descripción de 200 caracteres multibyte, ambos dentro de sus límites `VARCHAR`. | Correcto; los valores persistieron sin rechazos por longitud en bytes. |

### Causa y correcciones

El controlador usaba `strlen()` para validar `codigo VARCHAR(50)` y `descripcion VARCHAR(255)`. En el cotejamiento `utf8mb4`, esos límites son caracteres; la validación por bytes podía rechazar valores válidos con caracteres multibyte. Se cambió a `mb_strlen()` y se probó el alta/edición con texto acentuado que ocupa más bytes que caracteres.

La vista indicaba que las existencias de productos se actualizaban mediante movimientos de inventario, pero el flujo manual actual solo admite materiales y herramientas. Para evitar confundir el stock legado de productos terminados con el inventario de materiales, la vista ahora identifica ese stock como solo lectura e independiente. No se añadió un flujo nuevo para modificarlo: el CRUD no escribe `productos.stock` y el endpoint de inventario rechaza `tipo_item=producto`.

El esquema no tiene índice único para `productos.codigo` y ya existen códigos repetidos en los datos originales. No se inventó ni añadió una regla de unicidad. Tampoco se probó la carga de imágenes.

Al finalizar, los 5 productos originales mantuvieron sus valores y stock; no quedó producto temporal. Los materiales conservaron sus existencias (suma `493.00`), el kardex siguió con 0 filas de tipo producto y el detalle del producto 1 permaneció. Se conservaron 3 eventos de auditoría de la prueba de alta, edición y eliminación; no se borró historial. El controlador y la vista pasaron `php -l`.

**Reproducción manual:** iniciar sesión en la aplicación conectada a `innomade_pruebas`; abrir `index.php?ruta=productos`, crear y editar un producto y comprobar en MariaDB que stock empieza en cero y no cambia; intentar borrar el producto 1 y confirmar que su detalle de pedido permanece. Verificar que la vista distingue el stock de producto terminado del inventario de materiales. Eliminar únicamente el producto temporal sin relaciones.

No se hizo captura visual autenticada ni prueba de concurrencia. El servidor PHP, la sesión y los archivos temporales de prueba se limpiaron al finalizar.

**Estado:** CRUD de Productos probado por HTTP y contra MariaDB de pruebas. No se ha avanzado a Herramientas.

## Actualización 2026-11-14 — campo código en Herramientas

### Diagnóstico y cambios

La tabla real `herramienta` en `innomade_pruebas` tenía `idherramienta`, `nombre`, `estado` y `stock`, pero no `codigo`; el controlador, el modelo y los formularios tampoco manejaban ese dato. Había 4 herramientas históricas. No se les asignaron códigos automáticamente.

Antes del cambio se creó un respaldo nuevo de `innomade_pruebas` con `mysqldump` (38.734 bytes). Se confirmó nuevamente que PDO estaba conectado a `innomade_pruebas`, que la columna y el índice no existían, y luego se aplicó una sola vez [2026-11-14-herramienta-codigo.sql](./Migraciones/2026-11-14-herramienta-codigo.sql). El esquema final contiene `codigo VARCHAR(50) NULL` y el índice único `uq_herramienta_codigo`. Los códigos históricos permanecen `NULL`; los formularios exigen un código al crear o editar. El índice de MariaDB impide duplicados incluso ante solicitudes concurrentes; la aplicación presenta un mensaje específico para el error de duplicado.

Se actualizaron [herramientas.controlador.php](../controladores/herramientas.controlador.php), [herramientas.modelo.php](../modelos/herramientas.modelo.php) y [herramientas.php](../vistas/modulos/herramientas.php). Código aparece en el formulario de alta, en cada fila editable y en el listado. La validación del servidor recorta espacios, exige el dato y limita su longitud a 50 caracteres. CREATE conserva stock inicial cero; UPDATE no modifica stock. No se cambiaron los flujos de movimientos, kardex ni otros módulos.

### Pruebas HTTP y persistencia

Las solicitudes se hicieron contra `http://localhost:8080/INNOMADE/` y se verificaron en MariaDB. Se usó una sesión temporal autenticada con un usuario activo de la copia y un token CSRF válido; además se probó un token inválido. No se modificaron credenciales ni protecciones de seguridad.

| Operación | Prueba | Resultado |
|---|---|---|
| READ | GET de Herramientas; comprobar encabezado y campos de código, token CSRF y controles de stock. | HTTP 200; Código aparece en alta y listado, CSRF presente y no hay campo editable para stock. |
| CREATE | Alta de `HER-QA-20261114-2106`; se envió también un stock falsificado. | Correcta; el registro persistió con stock `0`. |
| UPDATE | Cambiar código a `HER-QA-EDIT-20261114-2106`, nombre y estado; enviar stock falsificado. | Correcta; los datos persistieron y stock permaneció en `0`. El código actualizado apareció en el listado HTTP. |
| Duplicado | Intentar una segunda alta con el código existente. | Rechazada con mensaje específico; quedó una sola fila con ese código. |
| Obligatorio y longitud | Enviar código compuesto solo por espacios y código de 51 caracteres. | Ambos rechazados por el servidor, sin registros. |
| CSRF | Enviar una creación con token inválido. | Rechazada; no se creó registro. |
| CREATE/UPDATE/DELETE | Borrar la herramienta de prueba sin movimientos. | Correcto; no quedó herramienta temporal en MariaDB ni en el listado. |
| UPDATE histórico | Editar la herramienta 1 sin asignarle código. | Rechazado como obligatorio; sus datos permanecieron intactos. Los registros históricos se pueden editar asignándoles un código único. |
| DELETE relacionado | Intentar borrar la herramienta 1, con dos filas históricas en `inventario` y movimientos en kardex. | Bloqueado con mensaje claro; la herramienta y sus relaciones se conservaron. |
| Movimientos | Entrada de 1 y salida de 1 sobre la herramienta 1, ambas por HTTP. | Stock `5 → 6 → 5`; el kardex guardó ambos movimientos con saldos anterior y resultante correctos. |
| Salida excesiva | Intentar retirar 6 con stock 5. | Rechazada; stock y kardex no cambiaron por esa solicitud. |

Al finalizar, siguen existiendo las 4 herramientas originales con stocks `5`, `1`, `3` y `1`; las cuatro mantienen `codigo = NULL`. No quedan códigos ni herramientas de prueba. Se conservaron las dos filas nuevas de kardex y sus eventos de auditoría como historial; no se borraron movimientos. La prueba tampoco eliminó las dos filas históricas de `inventario`.

### Pruebas no realizadas

No se ejecutó una prueba concurrente de altas con el mismo código; la restricción única de MariaDB es la protección final frente a esa carrera. No se probaron las credenciales de inicio de sesión ni se tomó captura visual autenticada: la comprobación de la vista fue mediante el HTML de las respuestas HTTP autenticadas. No se restauró el respaldo. La consola del navegador mostró errores preexistentes de recursos JavaScript, pero el HTML del CRUD se sirvió correctamente y no se modificaron esos recursos fuera del alcance.

El controlador, modelo y vista pasaron `php -l`; `git diff --check` terminó correctamente. Se destruyó y eliminó la sesión temporal al terminar.

**Estado:** funcionalidad de código de Herramientas probada por HTTP y contra `innomade_pruebas`. La migración se aplicó únicamente a esa copia; no se ejecutaron migraciones anteriores ni se modificó `sistemainventario_v3.2`.
