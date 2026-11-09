# Guía sencilla para desarrollar y mantener el sistema

Esta guía explica cómo arrancar el proyecto, cómo se conectan sus partes, qué cambió al usar la base de datos `sistemainventario_v2` y cómo aprender a mantenerlo por cuenta propia. Está escrita para leerla junto al código y probar cada concepto en un entorno local.

## 1. ¿Qué hace la aplicación?

Es una aplicación web para administrar inventario y operaciones de un taller. Permite mantener usuarios, categorías, clientes, proveedores y tipos de material; gestionar materiales y herramientas; registrar pedidos y pagos; y consultar los movimientos de herramientas.

La aplicación tiene dos partes principales:

- **Interfaz:** páginas PHP y formularios HTML que se muestran en el navegador.
- **Servidor y datos:** controladores y modelos PHP que validan las acciones y consultan MariaDB/MySQL con PDO.

La base de datos es la fuente de verdad: cada nombre de tabla, campo y relación que usa el código debe corresponder al esquema instalado.

## 2. Herramientas utilizadas y recomendadas

### Ya utilizadas en el proyecto

| Herramienta | Para qué sirve aquí |
| --- | --- |
| PHP | Ejecuta el servidor de la aplicación y produce las páginas HTML. |
| Apache de XAMPP | Sirve la aplicación en el navegador durante el desarrollo local. |
| MariaDB/MySQL | Guarda usuarios, pedidos, pagos y registros de inventario. |
| phpMyAdmin | Permite importar el archivo SQL y revisar tablas y datos. |
| PDO | Conecta PHP a la base de datos y ejecuta consultas parametrizadas. |
| HTML y CSS | Construyen formularios, tablas y la estructura de las pantallas. |
| JavaScript | Añade interacciones pequeñas, por ejemplo, agregar una línea a un pedido. |
| Star Admin y sus recursos | Proporcionan parte de la plantilla visual, estilos, iconos y bibliotecas. |
| Git | Permite revisar cambios y volver a una versión anterior con seguridad. |
| Visual Studio Code | Editor recomendado para navegar el código, buscar referencias y ver errores. |

El proyecto conserva algunas pantallas y recursos heredados de una plantilla anterior. Antes de quitar una biblioteca, revisa si la cargan otras páginas.

### Extensiones de PHP que conviene comprobar en XAMPP

- `pdo_mysql`, para que PDO pueda conectarse a MariaDB/MySQL.
- `mbstring`, porque las validaciones de longitud usan `mb_strlen`.
- `gd`, si se usan las funciones existentes de redimensionado de fotos.

En Windows se pueden revisar las extensiones habilitadas en el `php.ini` que utiliza Apache. Después de cambiarlo, reinicia Apache desde el panel de XAMPP.

### Herramientas que sería útil añadir más adelante

Estas son recomendaciones, no dependencias que el proyecto ya tenga instaladas:

1. **Composer:** instala y fija versiones de dependencias PHP.
2. **PHPUnit:** automatiza pruebas de validación y de operaciones con la base de datos.
3. **PHPStan o Psalm:** detecta problemas de tipos y errores probables antes de ejecutar la aplicación.
4. **PHP-CS-Fixer:** ayuda a mantener un formato uniforme del código.
5. **Variables de entorno (`.env`):** separan credenciales de base de datos del código; no se deben subir credenciales reales a Git.
6. **Migraciones de base de datos:** registran cambios del esquema de forma repetible y controlada, en lugar de depender de editar e importar manualmente un dump.

Es mejor incorporar estas herramientas de una en una, junto con una configuración y documentación de uso.

## 3. Preparar una copia local

1. Inicia Apache y MySQL desde XAMPP.
2. Crea o selecciona la base `sistemainventario_v2`.
3. En phpMyAdmin, importa `sistemainventario_v2.sql`.
4. Abre `modelos/conexion.php` y confirma que host, base, usuario y contraseña coinciden con la instalación local.
5. Coloca el proyecto dentro de la carpeta pública de Apache. En este entorno, la carpeta es `C:\xampp\htdocs\venta2026zabala`.
6. Abre `http://localhost/venta2026zabala/` en el navegador.
7. Si la tabla `usuario` está vacía, la pantalla de acceso ofrece crear la primera cuenta administradora.

La conexión actual usa `localhost`, base `sistemainventario_v2`, usuario `root` y contraseña vacía, que es una configuración local de XAMPP. **No es una configuración apropiada para publicar el sistema en Internet.**

Antes de importar el dump en una base que ya tenga información, crea una copia de respaldo y verifica qué hará la importación. El archivo SQL describe tablas y relaciones; no incluye registros que deban migrarse automáticamente desde una base anterior.

## 4. Recorrido de una petición

El patrón utilizado es un MVC sencillo:

- **Vista:** muestra datos y formularios.
- **Controlador:** recibe la acción, valida campos y decide qué operación solicitar.
- **Modelo:** ejecuta SQL y devuelve los datos o el resultado.

Ejemplo: al registrar un pago, el navegador envía un formulario a `index.php?ruta=pagos`; el controlador valida el pedido, el monto y el método de pago; `ModeloPagos` revisa el saldo pendiente e inserta el pago; la aplicación vuelve a la pantalla de pagos con un mensaje.

### Archivos principales

| Archivo o carpeta | Responsabilidad |
| --- | --- |
| `index.php` | Carga dependencias, inicia la sesión y dirige las escrituras POST autenticadas al controlador correspondiente. |
| `vistas/plantilla.php` | Muestra login o aplicación y resuelve las rutas permitidas. |
| `vistas/modulos/` | Contiene las páginas y formularios de cada módulo. |
| `controladores/` | Valida solicitudes y coordina las operaciones. |
| `modelos/` | Lee y modifica la base de datos mediante PDO. |
| `modelos/conexion.php` | Configura la conexión a la base de datos. |
| `sistemainventario_v2.sql` | Define el esquema actual y sus claves foráneas. |
| `FLUJO_Y_DIAGRAMA.md` y `diagrama_clases.mmd` | Resumen del flujo y relaciones principales. |

### Rutas de la aplicación

Las rutas se envían como `index.php?ruta=...`. La plantilla utiliza una lista permitida; una ruta no conocida muestra la pantalla 404.

| Ruta | Pantalla | Tablas principales |
| --- | --- | --- |
| `inicio` | Indicadores del panel | Herramientas, materiales, pedidos e inventario |
| `usuarios` | Administración de cuentas | `usuario` |
| `categorias` | Categorías | `categoria` |
| `clientes` | Clientes | `cliente` |
| `proveedores` | Proveedores | `proveedor` |
| `tipos-material` | Tipos de material | `tipo_material` |
| `materiales` | Materiales y stock decimal | `materiales`, `tipo_material`, `proveedor` |
| `herramientas` | Herramientas y stock entero | `herramienta` |
| `movimientos` | Entradas y salidas de herramientas | `inventario`, `herramienta`, `usuario` |
| `pedidos` | Pedidos, detalles y materiales usados | `pedido`, `detalle_pedido`, `detalle_material` |
| `pagos` | Pagos y saldos | `pagos`, `pedido`, `cliente` |

Las rutas de clientes, proveedores y tipos de material comparten `vistas/modulos/catalogo.php`. El catálogo selecciona su configuración a partir de la ruta permitida.

## 5. Qué implica el esquema nuevo

Las tablas centrales se relacionan así:

- Un pedido pertenece a un cliente y al usuario que lo registró.
- Cada línea de `detalle_pedido` pertenece a un pedido y referencia una categoría.
- Un pago pertenece a un pedido.
- Cada material pertenece a un tipo y un proveedor.
- `detalle_material` registra cuánto material se asoció a cada pedido.
- El stock de herramientas está en `herramienta`; `inventario` registra quién hizo una entrada o salida.

**Límite importante:** el esquema recibido no tiene una tabla de productos ni una relación de la línea del pedido con un artículo. Por eso el sistema no puede manejar un catálogo de productos individuales: el detalle del pedido usa categorías, cantidades y precios. Para vender productos concretos, primero hay que acordar y añadir la entidad y la relación a la base de datos, y después implementar su CRUD.

La base distingue dos inventarios:

- `materiales.stock_actual` es decimal, por ejemplo, metros o kilos.
- `herramienta.stock` es entero y los formularios de movimientos suman o restan unidades.

No intercambies estas tablas al hacer una consulta: representan cosas y unidades diferentes.

## 6. Funciones nuevas y editadas

Los prefijos ayudan a encontrar cada capa:

- `ctr...` suele identificar una operación de controlador.
- `mdl...` suele identificar una operación de modelo.
- Los controladores también pueden tener funciones privadas para validar o redirigir.

### Conexión y entrada de la aplicación

- `Conexion::conectar()` — `modelos/conexion.php`: abre PDO contra `sistemainventario_v2`, establece `utf8mb4` y activa excepciones de PDO para que los errores SQL no parezcan operaciones exitosas.
- `index.php`: carga controladores/modelos e inicia la sesión. Si llega un POST autenticado, delega la escritura según la ruta antes de producir HTML; esto permite redirigir sin el error de encabezados ya enviados.
- `ControladorPlantilla::crtPlantilla()` y `vistas/plantilla.php`: muestran login o navegación autenticada y limitan las rutas disponibles.

### Catálogos: clientes, proveedores y tipos de material

- `ControladorCatalogos::ctrMostrar($recurso)` pide al modelo el listado del catálogo elegido.
- `ControladorCatalogos::ctrProcesar($recurso)` resuelve crear, editar o eliminar; valida campos e identificadores; y guarda un mensaje para mostrar después de redirigir.
- `ModeloCatalogos::mdlMostrar()`, `mdlIngresar()`, `mdlEditar()` y `mdlBorrar()` SQL común.hacen el 
- `ModeloCatalogos::configuracion()` contiene una lista cerrada de tablas, claves y campos. Así el nombre de la tabla no se toma directamente de un formulario.

Los nombres de tablas y campos son SQL, no valores normales: no pueden sustituirse con parámetros `:nombre` de PDO. Por eso la configuración permitida es esencial; no se debe reemplazar por el nombre recibido libremente desde el navegador.

### Materiales

- `ControladorMateriales::ctrProcesarMaterial()` valida acciones e identificadores, requiere tipo y proveedor válidos y procesa alta, edición y baja.
- `ControladorMateriales::obtenerDatosValidos()` verifica los campos, límites de longitud y formato decimal del stock.
- `ModeloMateriales::mdlMostrarMateriales()` devuelve los datos del material con los nombres de su tipo y proveedor.
- `mdlIngresarMaterial()`, `mdlEditarMaterial()` y `mdlBorrarMaterial()` escriben en la tabla `materiales`.
- `vistas/modulos/materiales.php` muestra el formulario y permite seleccionar las relaciones obligatorias.

No se puede borrar un material que esté referenciado por un pedido si la base de datos no permite romper esa relación. En ese caso debe conservarse el registro o diseñarse una operación de archivado.

### Herramientas y movimientos

- `ControladorHerramientas::ctrProcesarHerramienta()` valida y coordina las acciones del CRUD.
- `ModeloHerramientas::mdlMostrarHerramientas()`, `mdlIngresarHerramienta()`, `mdlEditarHerramienta()` y `mdlBorrarHerramienta()` trabajan con la tabla `herramienta`.
- `ControladorMovimientos::ctrProcesarMovimiento()` valida tipo, cantidad e identificador antes de registrar.
- `ModeloMovimientos::mdlIngresarMovimiento()` ejecuta una transacción: bloquea el registro de la herramienta, comprueba el stock si es una salida, actualiza el stock e inserta el historial. Si una parte falla, revierte ambas.
- `ModeloMovimientos::mdlMostrarMovimientos()` une el historial con usuario y herramienta para mostrar nombres en lugar de solo identificadores.

Los movimientos se tratan como bitácora: la interfaz no permite editarlos ni eliminarlos, porque cambiar una entrada antigua sin recalcular el inventario podría dejar el stock incorrecto.

### Pedidos y consumo de materiales

- `ControladorPedidos::ctrProcesarPedido()` despacha alta, edición, cambio de estado y baja; valida los identificadores, cantidades, precios y estado.
- `obtenerDetalles()` convierte las líneas del formulario en datos validados para `detalle_pedido`.
- `obtenerMateriales()` convierte los consumos opcionales en datos decimales validados.
- `ModeloPedidos::mdlMostrarPedidos()` carga pedidos, detalles, materiales y total pagado, y los agrupa por identificador de pedido.
- `mdlCrearPedido()` calcula el total desde las líneas recibidas, crea el pedido y guarda detalles/consumos en una transacción.
- `mdlEditarPedido()` bloquea el pedido, impide que el total nuevo quede por debajo de lo ya pagado, restaura consumos anteriores, guarda las nuevas líneas y descuenta los consumos nuevos, todo en una transacción.
- `mdlCambiarEstado()` actualiza el estado usando una lista permitida por el controlador.
- `mdlBorrarPedido()` restaura el stock consumido y elimina el pedido en una transacción. Las claves foráneas en cascada eliminan sus detalles y pagos asociados.
- `calcularTotal()`, `registrarDetallesPedido()` y `registrarConsumoMateriales()` son funciones privadas del modelo para compartir esas tareas.
- `vistas/modulos/pedidos.php` presenta el alta, listado, cambio de estado y formulario de edición.

Editar un pedido restaura los materiales consumidos anteriormente y luego aplica los consumos enviados en el formulario. La transacción hace que la operación completa se confirme o se revierta; no deja solo una parte aplicada.

### Pagos

- `ControladorPagos::ctrProcesarPago()` valida y despacha alta, edición y baja de pagos.
- `ModeloPagos::mdlMostrarPagos()` muestra pagos con información del pedido y cliente.
- `mdlMostrarPedidosConSaldo()` lista pedidos que todavía aceptan pagos.
- `mdlIngresarPago()` y `mdlEditarPago()` comprueban el saldo dentro de una transacción para evitar que el monto supere el total pendiente.
- `mdlBorrarPago()` elimina un pago; al hacerlo, el saldo disponible del pedido vuelve a aumentar.
- `validarSaldo()` y `parametros()` son funciones privadas para reutilizar la comprobación y el armado de parámetros.

### Usuarios y acceso

- `ControladorUsuarios::crtIngresoUsuario()` busca la cuenta, comprueba que esté activa y valida la contraseña. Una contraseña antigua en texto plano se convierte a hash después de un ingreso correcto.
- `ctrHayUsuarios()` y `ModeloUsuarios::mdlContarUsuarios()` detectan una instalación sin cuentas.
- `ctrCrearPrimerUsuario()` y `mdlCrearPrimerUsuario()` crean la primera cuenta administradora solo si la tabla continúa vacía.
- `ctrCrearUsuario()` crea cuentas adicionales; `password_hash()` almacena una contraseña nueva como hash.
- `ctrProcesarUsuario()` actualiza perfil/datos, cambia estado o elimina una cuenta. No permite desactivar ni eliminar la cuenta actualmente conectada.
- `ModeloUsuarios::mdlActualizarUsuario()`, `mdlActualizarEstado()`, `mdlActualizarUltimoLogin()`, `mdlActualizarPassword()` y `mdlBorrarUsuario()` ejecutan las escrituras correspondientes.

El hash no se puede convertir de vuelta a la contraseña original. El inicio de sesión usa `password_verify()` para comprobarlo. No registres contraseñas en archivos de log.

### Categorías y panel

- `ControladorCategorias` coordina alta, lectura, edición y baja en `categoria`; informa si una clave foránea impide eliminar la categoría.
- `ModeloCategorias` contiene las consultas SQL de esas operaciones.
- `vistas/modulos/inicio.php` dejó de usar cantidades fijas y consulta herramientas, materiales, pedidos y movimientos para calcular sus indicadores.

En categorías se conservan algunos formularios heredados que procesan la acción desde la vista/controlador; la eliminación antigua se solicita mediante `GET`. Las demás escrituras del nuevo flujo se envían por POST a través de `index.php`. Antes de publicar, conviene migrar esa eliminación a POST y proteger los formularios con tokens CSRF.

## 7. Cómo añadir un CRUD nuevo, paso a paso

Practica primero con una tabla sencilla y sigue este orden:

1. **Entiende la tabla.** Lee su `CREATE TABLE`, tipos, claves primarias, valores por defecto y claves foráneas en `sistemainventario_v2.sql`.
2. **Define las acciones.** Escribe qué campos se listan y qué significa crear, editar y eliminar. Decide cómo se manejarán los registros relacionados.
3. **Implementa el modelo.** Crea funciones de lectura/escritura con consultas prepas.
4. **Implementa el controlador.** Lee `$_POST`, valida tipo, longitud, rango y campos obligatorios; prepara un radas. Usa una lista fija para nombres de tablas/campos; usa parámetros PDO para los valorearray limpio y llama al modelo. Muestra un error comprensible si la operación falla.
5. **Crea la vista.** Añade formularios con los mismos nombres que espera el controlador. Escapa valores al imprimirlos con `htmlspecialchars`.
6. **Conecta el formulario.** Añade la ruta a la lista permitida de `vistas/plantilla.php` y, si el módulo escribe mediante POST centralizado, agrega su despacho a `index.php`.
7. **Añade navegación.** Enlaza la ruta en `vistas/modulos/menu.php`.
8. **Prueba los casos normales y los errores.** Comprueba alta, listado, edición, baja, campos vacíos, números fuera de rango y referencias a registros inexistentes.
9. **Actualiza la documentación.** Añade la tabla, relación, rutas y operaciones nuevas en esta guía y en el diagrama.

Una buena práctica es copiar la estructura de un módulo parecido, no sus nombres de tabla o reglas de negocio. Por ejemplo, materiales y herramientas parecen similares en pantalla, pero tienen unidades, tipos de dato y relaciones diferentes.

## 8. Aprender a hacerlo sin depender de IA

La IA puede ayudar a explicar o revisar, pero el objetivo es poder demostrar qué hace cada cambio sin ella. Un método sencillo:

### Ejercicio A: seguir un dato

1. Abre una pantalla y localiza el atributo `name` del campo del formulario.
2. Busca ese nombre en `controladores/` para encontrar dónde se valida.
3. Sigue la llamada a `modelos/` para ver qué SQL se ejecuta.
4. Comprueba en phpMyAdmin la fila creada o modificada.
5. Explica con tus propias palabras cómo viajó el valor desde el navegador hasta la tabla.

### Ejercicio B: entender una relación

1. Elige un `FOREIGN KEY` en el SQL, por ejemplo, el de `pedido` y `cliente`.
2. Encuentra la consulta `JOIN` que usa esa relación.
3. Comprueba en la interfaz por qué se muestra el nombre del cliente y no solo `idcliente`.
4. Intenta borrar en una base de pruebas el registro padre y observa el efecto de la regla de la clave foránea.

### Ejercicio C: modificar una regla

1. Escoge una validación pequeña, como la longitud de un nombre.
2. Comprueba el límite que declara la columna en SQL.
3. Ajusta validación del controlador y atributo `maxlength` del formulario para que coincidan.
4. Prueba un valor válido, uno vacío y otro demasiado largo.
5. Revisa la fila resultante y el mensaje de error.

### Hábitos que aceleran el aprendizaje

- Lee el error completo y anota el archivo y la línea antes de cambiar código.
- Busca primero un módulo que ya resuelva una tarea parecida.
- Cambia una cosa por vez y prueba después de cada cambio.
- Usa `git diff` para revisar qué cambió; no descartes cambios que no sean tuyos.
- No pruebes borrados o consumos sobre datos reales: prepara registros de prueba y una copia de seguridad.
- Antes de guardar una operación de stock, escribe en papel el stock anterior, movimiento y stock esperado.
- Si no entiendes una consulta, identifica primero las tablas y columnas; después separa `SELECT`, `JOIN`, `WHERE` y parámetros.
- Mantén una nota de qué probaste, qué resultado esperabas y qué ocurrió.

### Si decides usar IA como apoyo

Pide una explicación localizada, por ejemplo: “Explícame qué datos recibe esta función y qué tabla modifica, sin cambiar el código”. Para una propuesta, indica el archivo, la tabla, las columnas y el comportamiento esperado. Luego:

1. compara la propuesta con el esquema SQL;
2. revisa cada archivo que la IA quiera modificar;
3. verifica que no haya credenciales ni datos personales en lo que compartes;
4. entiende la consulta y la validación antes de aceptarlas;
5. ejecuta las pruebas manuales y revisa `git diff`;
6. no despliegues cambios de autenticación, pagos o stock sin revisión humana.

La IA no ve automáticamente los efectos de una operación sobre tus datos ni sustituye las pruebas.

## 9. Reglas y mejoras pendientes importantes

- **Respaldo:** exporta la base antes de importar un dump, cambiar columnas o probar borrados.
- **Credenciales:** no publiques el usuario/contraseña local de XAMPP; para producción usa credenciales de privilegio mínimo y configuración fuera del repositorio.
- **Migración de datos:** no hay un proceso automático que copie productos, materiales o movimientos de un esquema anterior. Diseña y prueba una migración aparte si hay información que conservar.
- **Productos:** hace falta una tabla/relación de productos si cada línea de pedido debe señalar un artículo concreto.
- **Acceso por perfiles:** las cuentas tienen un campo `perfil`, pero el sistema actual no restringe todas las operaciones según el perfil; iniciar sesión no equivale a tener permisos diferenciados.
- **Protección de formularios:** antes de publicar, implementar protección CSRF en las operaciones POST y revisar las cargas de imágenes.
- **Validación y errores:** mantener la validación tanto en el servidor como en el formulario; el navegador por sí solo no es una barrera.
- **Pruebas automáticas:** todavía no hay una suite configurada. Conviene empezar por validaciones de pedidos/pagos y operaciones de stock con una base de pruebas aislada.
- **Estado de pedidos:** los valores de la interfaz son `PENDIENTE`, `EN_PROCESO`, `COMPLETADO` y `CANCELADO`. Si se modifica el modelo, confirmar que el nuevo valor cabe en la columna `estado`.

## 10. Lista rápida antes de dar un cambio por terminado

- [ ] El código usa las tablas y columnas que existen en el dump actual.
- [ ] Las consultas parametrizan valores y validan de forma segura nombres dinámicos de campos.
- [ ] Los formularios muestran los valores escapados y coinciden con las reglas de validación del controlador.
- [ ] Las operaciones relacionadas actualizan datos y stock de forma atómica cuando corresponde.
- [ ] Probé éxito, entradas inválidas y errores por claves foráneas.
- [ ] Revisé `git diff` y no incluí credenciales, datos reales ni cambios ajenos.
- [ ] Actualicé esta guía y el diagrama cuando cambió el esquema o el flujo.
