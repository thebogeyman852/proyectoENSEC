# Registro de cambios — 8 de noviembre de 2026

> **Fecha del registro:** 8 de noviembre de 2026 (fecha local de esta sesión).  
> **Objetivo:** dejar una guía de recuperación rápida que explique qué se añadió o editó al adaptar la aplicación a `sistemainventario_v2`, qué partes están activas y qué debe revisarse si se retoma el proyecto más adelante.

## 1. Resumen rápido para retomar el proyecto

1. Iniciar Apache y MySQL en XAMPP.
2. Verificar que la base importada se llame `sistemainventario_v2` y que coincida con [sistemainventario_v2.sql](../sistemainventario_v2.sql).
3. Revisar las credenciales de [conexion.php](../modelos/conexion.php). La configuración escrita actualmente es local (`localhost`, `root`, contraseña vacía).
4. Abrir la carpeta del proyecto en VS Code y comenzar el flujo desde [index.php](../index.php).
5. Si `usuario` no tiene filas, el login permite crear el primer administrador; después se inicia sesión normalmente.
6. La ruta de pedidos usa categorías como líneas de detalle: **no hay una tabla de producto individual en el esquema actual**.
7. Para entender una operación, seguir el orden: **formulario en `vistas/modulos/` → despacho en `index.php` o controlador → SQL en `modelos/` → tabla y relaciones del archivo SQL**.
8. Los cambios de stock de herramientas se hacen desde **Movimientos/Inventario**. Los consumos de materiales están asociados a pedidos. No confundir las dos clases de stock.
9. Antes de probar altas, bajas, pedidos o movimientos, usar una base local de pruebas y guardar una copia de seguridad.
10. La revisión de código no reemplaza la prueba funcional: falta validar todos los flujos conectándose al MariaDB local con el dump importado.

## 2. Qué pidió el cambio y qué significa para el proyecto

La aplicación anterior esperaba tablas y campos que no corresponden al esquema actualizado. El SQL nuevo contiene `usuario`, `categoria`, `cliente`, `pedido`, `detalle_pedido`, `pagos`, `tipo_material`, `proveedor`, `materiales`, `detalle_material`, `herramienta` e `inventario`.

Los cambios adaptan los modelos y pantallas a esas tablas. No se creó ni se importó una migración de datos desde la base anterior. El dump define la estructura y las claves foráneas, pero el código nuevo no traslada automáticamente registros existentes.

## 3. Mapa de cambios por archivo

### Punto de entrada y navegación

- [index.php](../index.php) **editado**:
  - carga los controladores y modelos que se usan en las rutas nuevas;
  - inicia la sesión si todavía no está activa;
  - antes de renderizar la página, dirige formularios POST autenticados a catálogos, materiales, herramientas, inventario, pedidos, pagos o usuarios;
  - la lista de rutas visuales se conserva en `vistas/plantilla.php`.
- [plantilla.php](../vistas/plantilla.php) **editado**:
  - muestra el login si no hay sesión y la aplicación si sí la hay;
  - mantiene una lista permitida de rutas;
  - envía clientes, proveedores y tipos de material a la vista común `catalogo.php`.
- [menu.php](../vistas/modulos/menu.php) **editado**: agrega enlaces a clientes, proveedores, tipos de material, pedidos y pagos. La opción de movimientos ahora se presenta como inventario.
- [inicio.php](../vistas/modulos/inicio.php) **editado**: sustituye cifras fijas por datos consultados de herramientas, materiales, pedidos e inventario.
- [cabecera.php](../vistas/modulos/cabecera.php) **editado previamente en el árbol de trabajo**: contiene la marca y la información de sesión del encabezado.

### Conexión y cuentas

- [conexion.php](../modelos/conexion.php) **editado**:
  - apunta a la base `sistemainventario_v2`;
  - establece el juego de caracteres `utf8mb4`;
  - hace que PDO informe errores SQL mediante excepciones.
- [usuarios.modelo.php](../modelos/usuarios.modelo.php) **editado**: lectura, creación, edición, estado, último acceso, cambio de contraseña, eliminación, conteo de usuarios y creación atómica del primer administrador.
- [usuarios.controlador.php](../controladores/usuarios.controlador.php) **editado**: valida el login, establece los datos de sesión, permite la creación inicial, crea cuentas y procesa las acciones de edición/estado/baja.
- [login.php](../vistas/modulos/login.php) **editado**: enseña el inicio de sesión o, si todavía no hay usuarios, el formulario de creación inicial.
- [usuarios.php](../vistas/modulos/usuarios.php) **editado**: lista cuentas, deja cambiar nombre/usuario/perfil/contraseña, activa o desactiva y elimina usuarios, evitando eliminar o desactivar al usuario conectado.

Las contraseñas nuevas usan `password_hash()`. En un login correcto, `password_verify()` comprueba el hash. Si la contraseña existente era texto plano, se convierte a hash después del primer login válido. El dump SQL no incluye una cuenta inicial.

### Catálogos añadidos

- [catalogos.modelo.php](../modelos/catalogos.modelo.php) **añadido**:
  - define qué tabla, clave primaria y columnas corresponden a cada catálogo;
  - implementa lectura, alta, edición y baja;
  - rechaza un catálogo que no figure en la configuración del servidor.
- [catalogos.controlador.php](../controladores/catalogos.controlador.php) **añadido**: valida los campos y las acciones para clientes, proveedores y tipos de material, muestra el resultado y redirige.
- [catalogo.php](../vistas/modulos/catalogo.php) **añadido**: vista común usada por las rutas `clientes`, `proveedores` y `tipos-material`.

Los nombres de tablas y columnas no son valores normales y no se parametrizan como `:campo` en PDO. Por eso se seleccionan desde una lista fija en el modelo, nunca desde un nombre arbitrario enviado por el navegador.

### Categorías

- [categorias.modelo.php](../modelos/categorias.modelo.php) y [categorias.controlador.php](../controladores/categorias.controlador.php) están en el árbol de trabajo con el CRUD de `categoria`; el controlador valida nombre e identificador e informa si la clave foránea impide eliminar la categoría.
- [categorias.php](../vistas/modulos/categorias.php) muestra categorías, formularios de alta/edición y acciones de eliminación.

**Nota:** esta pantalla conserva un patrón heredado: parte del procesamiento se ejecuta al incluir la vista, y la baja llega por `GET`. No es el patrón más seguro para producción. Se anotó como pendiente migrar esa baja a POST y proteger los formularios con CSRF.

### Materiales

- [materiales.modelo.php](../modelos/materiales.modelo.php) **adaptado** a `materiales`; obtiene nombres de tipo/proveedor con `JOIN` y persiste stock decimal.
- [materiales.controlador.php](../controladores/materiales.controlador.php) **adaptado**; valida nombre, unidad, stock y los identificadores de `tipo_material` y `proveedor`.
- [materiales.php](../vistas/modulos/materiales.php) **adaptado**; permite crear y editar materiales con tipo y proveedor obligatorios, y presenta stock decimal.

La tabla SQL llama `materiales` a la entidad material (plural), mientras que `tipo_material` y `proveedor` son tablas distintas. Un material con detalles de pedido relacionados puede no ser eliminable por la clave foránea.

### Herramientas y movimientos de inventario

- [herramientas.modelo.php](../modelos/herramientas.modelo.php) **adaptado** a `herramienta`, con `idherramienta`, `nombre`, `estado` y stock entero.
- [herramientas.controlador.php](../controladores/herramientas.controlador.php) **adaptado**; valida y despacha altas, ediciones y bajas.
- [herramientas.php](../vistas/modulos/herramientas.php) **adaptado**; muestra el CRUD de la tabla herramienta.
- [movimientos.modelo.php](../modelos/movimientos.modelo.php) **adaptado** a `inventario`; muestra historial unido a usuario/herramienta y registra un movimiento en transacción.
- [movimientos.controlador.php](../controladores/movimientos.controlador.php) **adaptado**; valida tipo, cantidad e identificador de herramienta y toma `idusuario` de la sesión.
- [movimientos.php](../vistas/modulos/movimientos.php) **adaptado**; ofrece entrada/salida y muestra la bitácora.

**Recorrido de una entrada o salida:** el formulario manda `idHerramienta`, `tipoMovimiento` y `cantidadMovimiento`; el controlador valida esos valores y agrega el usuario autenticado; el modelo bloquea el registro de la herramienta, calcula el stock nuevo y guarda el historial. Si una parte falla, revierte todo. Una salida que supera el stock se rechaza.

Los movimientos son historial: la interfaz no deja modificarlos ni borrarlos. Si se edita directamente el stock desde el CRUD de herramientas, el historial no refleja esa edición; para un ajuste real de existencias es preferible registrar el movimiento correspondiente.

### Pedidos y uso de materiales

- [pedidos.modelo.php](../modelos/pedidos.modelo.php) **añadido**:
  - `mdlMostrarPedidos()` carga pedidos y agrupa detalles, materiales y pagos por ID;
  - `mdlCrearPedido()` calcula el total desde las líneas, guarda pedido/detalles y descuenta consumos;
  - `mdlCambiarEstado()` actualiza el estado;
  - `mdlEditarPedido()` reemplaza los detalles y consumos, restaura primero el material anterior y rechaza un total inferior a lo ya pagado;
  - `mdlBorrarPedido()` restaura el material consumido antes de eliminar el pedido;
  - sus funciones privadas calculan total y registran detalles/consumos compartiendo lógica.
- [pedidos.controlador.php](../controladores/pedidos.controlador.php) **añadido**:
  - `ctrProcesarPedido()` despacha alta, edición, estado y eliminación;
  - `obtenerDetalles()` valida categoría, cantidad y precio de cada línea;
  - `obtenerMateriales()` valida consumos decimales opcionales;
  - `redirigir()` guarda el mensaje y vuelve a la ruta de pedidos.
- [pedidos.php](../vistas/modulos/pedidos.php) **añadido**: formularios y listado para pedidos, líneas, consumo de materiales, estado, edición y eliminación.

El esquema no incluye una tabla de productos ni una columna `idproducto` en `detalle_pedido`. Por tanto, las líneas se registran por categoría, cantidad y precio unitario. Si se necesitan productos individuales, primero hay que acordar y añadir la entidad/relación SQL y después adaptar la aplicación.

Los cambios relacionados usan transacciones para evitar guardar solo parte del pedido. Si falla la disponibilidad de materiales, toda la operación se revierte. La eliminación puede borrar pagos y detalles por las reglas `ON DELETE CASCADE` del esquema; la vista avisa al usuario.

### Pagos

- [pagos.modelo.php](../modelos/pagos.modelo.php) **añadido**:
  - `mdlMostrarPagos()` muestra pagos con información del pedido y cliente;
  - `mdlMostrarPedidosConSaldo()` obtiene pedidos cuyo saldo es mayor que cero;
  - `mdlIngresarPago()` y `mdlEditarPago()` comprueban el saldo dentro de una transacción;
  - `mdlBorrarPago()` elimina un pago;
  - `validarSaldo()` y `parametros()` son ayudas privadas del modelo.
- [pagos.controlador.php](../controladores/pagos.controlador.php) **añadido**: valida identificadores, monto y método, y despacha alta, edición o baja.
- [pagos.php](../vistas/modulos/pagos.php) **añadido**: registra abonos para pedidos con saldo y permite editar/eliminar pagos existentes.

### Documentación y archivos del módulo anterior

- [diagrama_clases.mmd](../diagrama_clases.mmd) **actualizado** para representar las entidades y relaciones actuales.
- [FLUJO_Y_DIAGRAMA.md](../FLUJO_Y_DIAGRAMA.md) **actualizado** con un resumen del esquema y enlaces a esta carpeta.
- [GUIA_DESARROLLADOR.md](./GUIA_DESARROLLADOR.md) explica instalación, MVC, módulos, herramientas y ejercicios de aprendizaje.
- [README.md](./README.md) es el índice de los informes y la plantilla para registros futuros.
- [README_DESARROLLADOR.md](../README_DESARROLLADOR.md) enlaza la documentación desde la raíz.
- Las rutas de productos individuales se quitaron del enrutamiento y menú, porque el SQL no tiene tabla de producto. El módulo viejo [productos.php](../vistas/modulos/productos.php) puede permanecer como archivo sin ruta activa.
- Los archivos de controladores/modelos de productos antiguos figuran eliminados (`controladores/productos.controlador.php` y `modelos/producto.modelo.php`); también hay archivos de artículos (`articulos.controlador.php`, `articulos.modelo.php`) en el árbol, pero `index.php` no los carga. No asumir que constituyen un CRUD funcional con el esquema actual.

## 4. Mapa de rutas y tablas

| Ruta | Función actual | Tablas principales |
| --- | --- | --- |
| `inicio` | Indicadores dinámicos | `herramienta`, `materiales`, `pedido`, `inventario` |
| `usuarios` | Administrar cuentas | `usuario` |
| `categorias` | Mantener categorías usadas por los detalles | `categoria` |
| `clientes` | CRUD de clientes | `cliente` |
| `proveedores` | CRUD de proveedores | `proveedor` |
| `tipos-material` | CRUD de tipos | `tipo_material` |
| `materiales` | Mantener materiales y su saldo decimal | `materiales`, `tipo_material`, `proveedor` |
| `herramientas` | Mantener herramientas y su stock | `herramienta` |
| `movimientos` | Entradas/salidas e historial | `inventario`, `herramienta`, `usuario` |
| `pedidos` | Crear, editar, cambiar estado y borrar pedidos | `pedido`, `detalle_pedido`, `detalle_material` |
| `pagos` | Registrar y administrar abonos | `pagos`, `pedido`, `cliente` |

## 5. Qué está probado y qué falta probar

### Comprobado en esta sesión

- El editor no reportó errores detectables en los archivos PHP inspeccionados.
- Una búsqueda de consultas antiguas en los modelos principales no encontró SQL dirigido a `articulo`, `movimientos` o `material`.
- No se encontró una suite automatizada de pruebas en el proyecto.

### No comprobado funcionalmente

No se ejecutó el sitio contra MariaDB con el dump importado. Falta abrir las pantallas y probar operaciones reales con datos ficticios: CRUD de catálogos, altas/ediciones/bajas, entradas y salidas, stock insuficiente, pedidos con y sin materiales, edición y eliminación de pedidos, pagos parciales, pagos que exceden el saldo y cuentas activas/inactivas.

Los diagnósticos del editor detectan problemas de sintaxis conocidos por VS Code, pero no garantizan que las consultas coincidan con una base importada o que cada flujo funcione en el navegador.

## 6. Pendientes conocidos

- No hay migración automática de registros desde la base anterior.
- No existe tabla de productos individuales: resolver el diseño antes de implementar ese catálogo.
- `perfil` se guarda, pero faltan permisos que restrinjan cada acción por rol.
- La configuración de base de datos está escrita directamente en el código para uso local; antes de publicar, usar configuración externa y credenciales de privilegio mínimo.
- Implementar protección CSRF para formularios, revisar cargas de fotos y mover la baja de categorías de GET a POST antes de exponer la aplicación.
- Crear una base de pruebas y automatizar validaciones de pedidos/pagos e invariantes de stock.
- Confirmar que los estados `PENDIENTE`, `EN_PROCESO`, `COMPLETADO` y `CANCELADO` caben en la definición SQL instalada.
- Revisar el flujo de imágenes/usuarios en XAMPP y comprobar que las extensiones PHP necesarias están habilitadas.

## 7. Cómo retomar el aprendizaje

Para estudiar el registro de inventario, abrir primero el formulario en `vistas/modulos/movimientos.php`, seguir el despacho `movimientos` en `index.php`, leer la validación en `controladores/movimientos.controlador.php` y terminar en la transacción de `modelos/movimientos.modelo.php`. Después comprobar en una base local el cambio en `herramienta.stock` y la fila añadida a `inventario`.

Para cualquier módulo, esta pregunta ayuda a orientarse: **¿qué campo envía la vista, qué regla valida el controlador, qué SQL ejecuta el modelo y qué tabla guarda el resultado?**

Para una explicación de los conceptos y ejercicios sencillos, continúa en [GUIA_DESARROLLADOR.md](./GUIA_DESARROLLADOR.md). Para registrar otra jornada, sigue la plantilla de [README.md](./README.md); crea otro informe con la fecha de esa jornada en lugar de reemplazar este registro.
