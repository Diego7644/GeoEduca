# Grupos de commit (aún no ejecutados)

Registro de cómo se publican los cambios de integración. Cuatro commits, en este orden, sobre `integracion/pruebas-y-documentacion`. Identidad local del clon: nombre `GiovanniCP05`, correo `GiovanniCP05@users.noreply.github.com`. No usar el correo institucional. No `--amend`, no `--no-verify`.

## 1. Infraestructura y pruebas

Archivos:

- `.gitignore`
- `.gitattributes`
- `backend/composer.json`
- `backend/composer.lock`
- `backend/phpunit.xml`
- `backend/tests/` (18 clases, 3 de apoyo, `TestCase.php`)

Mensaje sugerido:

```
test: añadir PHPUnit y adaptar la caracterización al contrato actual

La producción no cambia. RequisitosSeguridad sigue fallando donde el prototipo es inseguro.
```

## 2. Documentación académica pública

Archivos:

- `docs/01-guia-pruebas-calidad/` (copia redactada; sin DOCX del profesor ni `sonar/`)

Mensaje sugerido:

```
docs: publicar la guía de pruebas con evidencias redactadas

Se omiten el DOCX del profesor y el entorno local de SonarQube. Los originales quedan fuera.
```

## 3. Documentación técnica de desarrollo

Archivos:

- `docs/README.md`
- `docs/02-desarrollo/`

Mensaje sugerido:

```
docs: diagnosticar el prototipo y planear persistencia y autenticación

Primera etapa funcional: base de datos, login real y autorización por rol.
```

## 4. README general

Archivos:

- `README.md` (raíz del repositorio)

Mensaje sugerido:

```
docs: describir el prototipo, cómo ejecutarlo y dónde está la documentación

El README no presenta como terminadas las funciones que hoy son simuladas.
```

## Después de los commits

1. Comprobar `git log` y que el autor sea el noreply.
2. Restaurar el `pushurl` solo para esa operación, o empujar desde un fork.
3. `git push -u origin integracion/pruebas-y-documentacion` (o el remoto del fork).
4. Abrir PR hacia `mejoras-interfaces-dinamicas`, **no hacia `main`**.
5. No fusionar, no force push.
