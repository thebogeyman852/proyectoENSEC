# 02 — Arquitectura MVC del proyecto

## Recorrido de una petición

1. El navegador solicita `index.php?ruta=pedidos`.
2. `index.php` carga controladores/modelos e inicia la sesión.
3. Para un POST autenticado, despacha al controlador correspondiente.
4. `vistas/plantilla.php` comprueba la sesión y carga el módulo permitido.
5. El controlador valida datos y solicita la operación al modelo.
6. El modelo ejecuta SQL parametrizado con PDO y devuelve el resultado.
7. El controlador redirige y la vista muestra el mensaje de sesión.

## Responsabilidades

- **Vista (`vistas/modulos/`)**: formulario y presentación; escapa texto antes de imprimirlo.
- **Controlador (`controladores/`)**: interpreta `$_POST`, valida y maneja errores de negocio.
- **Modelo (`modelos/`)**: conoce tablas/columnas y realiza consultas.
- **`index.php` y plantilla**: cargan dependencias y enrutan.

## Ejemplo didáctico

En el formulario de pedidos, `name="idcliente"` produce `$_POST["idcliente"]`. `ControladorPedidos` valida el identificador, crea un arreglo de datos y llama a `ModeloPedidos::mdlCrearPedido()`. El modelo inserta el pedido y sus detalles en una transacción. La redirección evita que actualizar el navegador repita el formulario.

Para crear un módulo manualmente, primero identifica la ruta permitida; después implementa vista, controlador y modelo; finalmente verifica que el controlador/modelo se carguen y prueba tanto entradas válidas como inválidas.

## Errores comunes

- **Ruta 404**: falta en la lista permitida de `vistas/plantilla.php`.
- **Método/clase no encontrada**: falta cargar el archivo en `index.php`.
- **Columna inexistente**: compara la consulta con el dump SQL antes de cambiarla.
- **Datos inesperados**: revisa el nombre del campo HTML, `$_POST` y la validación del controlador.
