# Carpeta de documentación

Este directorio reúne las guías, informes y registros de trabajo del sistema. La idea es poder recordar qué se cambió, cuándo se hizo, por qué y cómo comprobarlo, aunque pase tiempo entre una modificación y otra.

## Documentos actuales

- [Resumen general del proyecto](./00_Resumen_General_Proyecto.md), [conexión PDO](./01_Conexion_PDO.md) y [arquitectura MVC](./02_Arquitectura_MVC.md): fundamentos para comenzar.
- Tutoriales implementados: [pedidos](./03_Modulo_Pedidos.md), [materiales](./04_Modulo_Materiales.md) y [kardex](./05_Kardex_Inventario.md).
- [Guía para programar sin IA](./13_Guia_para_Programar_Sin_IA.md): tutorial básico del flujo MVC, validación y PDO.
- [Diagnóstico y corrección de CRUD](./15_DIAGNOSTICO_Y_CORRECCION_CRUD.md): hallazgos, cambios y pruebas pendientes.
- [Guía de inventario, compras, pagos, usuarios, auditoría y reportes](./16_Guia_Modulos_Inventario_Compras_Seguridad_Reportes.md): explicación MVC, consultas y ejercicios por módulo.
- [Cambios realizados](./14_Cambios_Realizados.md): registro por módulo y pendientes.
- [Migración de idempotencia para kardex](./Migraciones/2026-11-11-kardex-consumos-idempotentes.sql): aplicar manualmente una sola vez, después de respaldar.
- [Migración de compras, auditoría, pagos y recuperación](./Migraciones/2026-11-12-inventario-compras-seguridad.sql): requiere primero la migración anterior.
- [Migración de idempotencia para pagos](./Migraciones/2026-11-15-pagos-idempotentes.sql): añade protección frente al reenvío duplicado de un pago.
- [Guía del desarrollador](./GUIA_DESARROLLADOR.md): funcionamiento básico, instalación, herramientas, módulos y ejercicios de aprendizaje.
- [Registro del 8 de noviembre de 2026](./2026-11-08-adaptacion-base-datos.md): cambios realizados al adaptar la aplicación al esquema nuevo.
- [Registro del 9 de noviembre de 2026](./2026-11-09-adaptacion-sistemainventario-v3.md): actualización del sistema al esquema v3, catálogo de productos y stock en pedidos.

## Cómo registrar un día de trabajo

Cuando se cambie código, base de datos, configuración o documentación:

1. Crea un archivo nuevo con la fecha ISO al inicio del nombre: `AAAA-MM-DD-tema.md`.
2. Escribe la fecha, objetivo y motivo del cambio.
3. Anota qué archivos o partes se modificaron y qué responsabilidad tiene cada cambio.
4. Explica el comportamiento antes y después con palabras sencillas.
5. Registra cómo se comprobó el cambio y qué quedó pendiente.
6. Actualiza la lista de documentos de este índice.

Se puede usar esta plantilla:

```markdown
# Registro de cambios — AAAA-MM-DD

## Objetivo
¿Qué se necesitaba y por qué?

## Cambios realizados
- `ruta/al/archivo.php`: qué se cambió y qué hace.

## Cómo funciona
Explicación sencilla del recorrido de los datos o de la nueva regla.

## Comprobaciones
- Qué se probó.
- Resultado.

## Pendiente o notas para la próxima sesión
- Limitaciones, decisiones pendientes o próximos pasos.

## Aprendizaje
- Qué conceptos conviene recordar o practicar.
```

## Buenas prácticas para estos registros

- Crear una entrada por fecha de trabajo; no reescribir los informes anteriores cuando se haga un cambio nuevo.
- Distinguir claramente lo que se implementó, lo que se probó y lo que solo se recomienda para el futuro.
- Registrar errores conocidos o limitaciones, no solo el resultado esperado.
- No guardar contraseñas, tokens, datos personales ni copias de la base con información real.
- Mantener los informes breves pero suficientemente claros para que otra persona pueda retomar el trabajo.

La documentación ayuda a recordar decisiones, pero no sustituye al código, los comentarios necesarios, las pruebas ni una copia de seguridad.

Las migraciones documentadas se aplicaron en `innomade_pruebas` para las pruebas funcionales; no se modificó la base original `sistemainventario_v3.2`.
