# Carpeta de documentación

Este directorio reúne las guías, informes y registros de trabajo del sistema. La idea es poder recordar qué se cambió, cuándo se hizo, por qué y cómo comprobarlo, aunque pase tiempo entre una modificación y otra.

## Documentos actuales

- [Guía del desarrollador](./GUIA_DESARROLLADOR.md): funcionamiento básico, instalación, herramientas, módulos y ejercicios de aprendizaje.
- [Registro del 8 de noviembre de 2026](./2026-11-08-adaptacion-base-datos.md): cambios realizados al adaptar la aplicación al esquema nuevo.

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
