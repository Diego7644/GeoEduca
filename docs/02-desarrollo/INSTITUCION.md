# Institución educativa

GeoEduca usa la identidad verificada en Identicole, Padrón 2026, código modular `0372599`:

- Nombre: Institución Educativa Mariscal Castilla.
- Nivel: Secundaria. No es el colegio primario del mismo nombre.
- Lugar: Calle Arequipa cuadra 9 s/n, El Tambo, Huancayo, Junín, Perú.
- UGEL: Huancayo.
- Código de local: 223246.
- Fuente: https://identicole.minedu.gob.pe/colegio/03725990

La clase `App\Support\InstitutionIdentity` es el único origen de esos datos. La fila de `school_settings` se corrigió solo cuando todavía decía Colegio San Gabriel, Av. Las Palmeras, Monterrico. Ese punto estaba en Lima (`-12.0862, -77.0543`) y se dejó vacío.

## Pendiente de validación

- No hay un escudo oficial autorizado en el repositorio. La interfaz no muestra uno inventado.
- Las fuentes públicas no coinciden en el nombre de la dirección. No se guarda un director.
- Identicole no publicó una coordenada del local. El GPS de referencia queda sin punto hasta que la institución lo confirme.
- El horario de ingreso y salida sigue siendo un valor configurable de la aplicación, no el horario oficial del colegio.
- El censo de Identicole no se copia como estadística del sistema.
