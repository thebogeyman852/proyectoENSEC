# 00 — Resumen general del proyecto

## Estado actual

INNOMADE es una aplicación web PHP con una arquitectura MVC sencilla, PDO y MariaDB. El punto de entrada es `index.php`; las vistas están en `vistas/`, la lógica de solicitudes en `controladores/` y las consultas en `modelos/`.

La configuración de `modelos/conexion.php` usa `localhost` y el nombre `sistemainventario_v3.2`. Se abrió una conexión PDO de solo lectura y `SELECT DATABASE()` confirmó que esa base existe en el MariaDB local de XAMPP y contiene 17 tablas. No se modificaron datos. No se abrió la interfaz de phpMyAdmin durante esta revisión. El encabezado del archivo `sistemainventario_v3.2.sql` declara `sistemainventario_v3`, que no coincide con el nombre de la base configurada; no se cambió la conexión.

## Módulos existentes

El proyecto contiene CRUD y pantallas para usuarios, categorías, productos, materiales, herramientas, movimientos, catálogos, pedidos y pagos. Se implementaron pantallas/modelos para inventario/kardex, compras, auditoría y reportes, además de recuperación de contraseña. Se validaron sintácticamente los 55 archivos PHP del proyecto y se ejecutaron ocho consultas de lectura de los módulos contra la base local, sin leer ni imprimir filas personales.

## Regla principal de producción

Un pedido y sus materiales planificados describen trabajo futuro. No representan una salida de inventario. El stock cambia únicamente con un movimiento real, registrado junto con el kardex y protegido por una transacción.

La creación/edición del pedido conserva la planificación en `detalle_material`, y el consumo real se registra aparte en `kardex`. Las entradas/salidas/ajustes de materiales y herramientas y las compras usan transacciones y registran movimientos. Herramientas conservan la lectura de movimientos heredados en `inventario`.

La creación y edición del catálogo de productos no modifican existencias. El stock legado de productos se muestra como solo lectura; el proyecto no debe tratar muebles terminados como inventario habitual.

## Cómo seguir el proyecto

1. Revisa primero el esquema de base de datos y la conexión.
2. Sigue el recorrido vista → controlador → modelo.
3. Haz cambios pequeños por módulo.
4. Antes de ejecutar una migración, respalda la base de datos y pruébala en una copia.
5. Distingue siempre entre una prueba de sintaxis y una prueba real con MariaDB.

## Estado y dependencias pendientes

La base activa no tiene todavía `auditoria`, `solicitud_recuperacion`, `detalle_material.estado_planificacion`, `kardex.id_solicitud`, ni las columnas nuevas de compras y pagos. Sin esas migraciones, las escrituras que dependen de idempotencia/auditoría/recuperación se revierten y no deben probarse como si estuvieran listas. No se ejecutó SQL ni se modificaron datos. Aplica las migraciones solo a una copia de pruebas después de respaldar.

No se ejecutaron operaciones CRUD de escritura, pruebas de stock, correo Gmail ni pruebas de botones en navegador. Por tanto, las funciones están implementadas pero no se declaran funcionalmente terminadas en XAMPP. La [guía de módulos](./16_Guia_Modulos_Inventario_Compras_Seguridad_Reportes.md) explica archivos, MVC, SQL y pruebas; revisa además el [diagnóstico CRUD](./15_DIAGNOSTICO_Y_CORRECCION_CRUD.md) y la [guía para programar sin IA](./13_Guia_para_Programar_Sin_IA.md).
