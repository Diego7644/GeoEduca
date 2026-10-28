# Auditoría de privacidad de la copia pública

Fecha: 2026-10-28. Ámbito: archivos que se proponen publicar en `Diego7644/GeoEduca` desde el clon local de integración, rama `integracion/pruebas-y-documentacion`. **No se reproduce ningún secreto ni dato personal.**

Esta auditoría cubre texto, JSON, XML, CSV, scripts, PNG (píxeles y metadatos) y el DOCX académico. Un archivo no se considera seguro solo porque una búsqueda de texto no encontró coincidencias.

## Criterio

Se publica solo lo que (a) es necesario para la evidencia académica o el desarrollo, y (b) no expone secretos, identificadores personales innecesarios ni rutas de máquina. Si un original no se puede redactar sin invalidar la evidencia, se deja fuera y se marca para revisión humana.

## Resultado

**Apto para publicación condicionado a autorización de commit**, con las omisiones y redacciones de las secciones siguientes. No se encontraron tokens de API, claves de SonarQube, archivos `.env`, contraseñas en claro de la guía, correos personales ni rutas de perfil de Windows en la copia pública.

## Archivos omitidos (originales intactos fuera de este repositorio)

| ID | Qué se omitió | Motivo | Dónde queda el original |
|---|---|---|---|
| EXCL-GUIA-PROFESOR | DOCX original del profesor | Material del curso, no es evidencia propia | Archivo académico privado |
| EXCL-SONAR-ENTORNO | Carpeta `sonar/` (Docker, scripts, plantilla) | Entorno local; `sonar/.env` contiene credenciales de la instancia | Archivo académico privado (`.env` además está en `.gitignore`) |
| EXCL-SONAR-PROPERTIES | `sonar-project.properties` | Configuración del análisis local; no aporta a quien no tenga el servidor | Archivo académico privado |
| EXCL-INFORME-DOCX | `INFORME-TECNICO-FINAL.docx` | Un DOCX regenerado reintroduce metadatos de autor/máquina; el Markdown redactado sí se publica | Archivo académico privado |
| EXCL-ENV | `backend/.env`, `frontend/.env`, `sonar/.env` | Secretos de entorno | Nunca versionados; presentes solo en el árbol local privado y en el respaldo |
| EXCL-UI-ORIGINALES | PNG de UI-02, UI-04 y UI-12 sin barra | Muestran dígitos del DNI de demostración en el campo de login | Árbol privado y respaldo; hashes del original coinciden con el respaldo |
| EXCL-URL-PRIVADA | URL del repositorio académico privado | Identifica la cuenta privada | Solo en el archivo privado |

## Redacciones aplicadas a la copia pública

| ID | Qué se cambió | Cómo | Por qué no invalida la evidencia |
|---|---|---|---|
| RED-DNI-ESTUDIANTE | DNI de demostración del perfil estudiante/tutor | Sustituido por `<DNI-DEMO-ESTUDIANTE>` (y variantes de 7/9 dígitos por `<DNI-DE-7-DIGITOS>` / `<DNI-DE-9-DIGITOS>`) | El resultado (estado HTTP, mensaje, aprobado/fallido) no depende del valor concreto del DNI |
| RED-RUTAS | Rutas absolutas de Windows | Sustituidas por `<RAIZ_PROYECTO>` | Solo cambia la localización de archivos, no métricas ni veredictos |
| RED-CUENTA | Identificador de la cuenta del archivo privado | Sustituido por `<CUENTA-PRIVADA>` | No forma parte de ningún resultado de prueba |
| RED-TOKEN | Cabeceras `Authorization` y cuerpos con token | Ya estaban como `Bearer <REDACTED>` / `\u003cREDACTED\u003e` | El testdox y los JSON siguen mostrando el estado HTTP |
| RED-PASSWORD | Contraseñas de demostración en evidencias | Ya estaban como `<MASKED>` | El script las lee de variables de entorno, no del repositorio |
| RED-PNG-DNI | UI-02, UI-04, UI-12 | Barra negra sobre el campo de DNI (7920 píxeles). Contraseña ya enmascarada por el navegador | Se ve el mensaje de error, el estado del botón y el recorte; no se leen los dígitos |
| RED-PORTADA | Nombre, asignatura, docente, institución | Campos `[COMPLETAR]` | La guía los pide; completarlos es revisión humana, no un secreto técnico |
| RED-PHPUNIT-LOG | Salida de PHPUnit contra la rama de Diego | Rutas absolutas → `<RAIZ_PROYECTO>` | Los nombres de prueba, aserciones y fallos se conservan |

## Qué se conservó a propósito

| Dato | Dónde | Motivo |
|---|---|---|
| DNI de demostración del personal (`40111222`) y nombres de las cuentas de demo del producto | Código de Diego (ya público) y algunas evidencias | Redactarlo solo en la guía y dejarlo en `DemoAccounts` crearía dos verdades. No se modificó el código de producción |
| `74829104` | Pruebas y casos F-06 / V-09 | Identificador ficticio de estudiante, no un DNI de persona |
| `99999999` | Caso N-03 | DNI inexistente para no enumeración |
| Palabra `Bearer` | Matrices y testdox | Tipo de token, no un valor |
| `APP_KEY` falsa en `backend/phpunit.xml` | Suite de pruebas | Valor sintético exclusivo de PHPUnit, forzado por el XML, distinto de cualquier `.env` |
| Nombres de demo del producto (p. ej. Mateo Valenzuela) | Código de Diego y capturas de panel | Ya publicados en la rama base |

Las **contraseñas de demostración del producto** siguen en el código de Diego (archivo de cuentas de demo y respaldo del frontend). Esta integración **no las copió** a la documentación y **no las reproduce aquí**. Rotarlas es una tarea de F1, no de esta publicación.

## Imágenes y metadatos

- 19 PNG de la copia pública: solo chunks `IHDR`, `IDAT`, `IEND`. Sin `tEXt`, `iTXt`, `eXIf` ni perfiles.
- JSON de SonarQube: ninguna clave `token` / `password` / `secret` / `authorization` / `apiKey` con valor.
- No hay archivos `.docx` en la copia pública.

## Commits futuros (aún no hechos)

El commit base de Diego ya está en GitHub con el correo de autor que él usó; **no se reescribe**. Los commits de integración, cuando se autoricen, deben usar identidad local:

- `user.name` = `GiovanniCP05`
- `user.email` = `GiovanniCP05@users.noreply.github.com`

No usar el correo institucional global. El `push` está deshabilitado en este clon (`remote.origin.pushurl` apunta a un valor inválido a propósito).

## Revisión humana pendiente

| ID | Qué | Por qué no se resolvió aquí |
|---|---|---|
| HUM-PORTADA | Completar o dejar en `[COMPLETAR]` los datos de la portada del informe | Son datos personales o institucionales del autor de la práctica |
| HUM-DEMO-PRODUCTO | Decidir si las cuentas de demo del código de Diego se rotan antes de un despliegue real | Ya están en el repositorio público; cambiarlas es un cambio de producción, fuera de esta integración |
| HUM-DOCX-INFORME | Si el curso exige Word, generar el DOCX **después** de autorizar, desde el Markdown redactado, y volver a revisar metadatos | Regenerarlo ahora reintroduciría autor/máquina |

## Comprobaciones ejecutadas

1. Búsqueda de DNI de estudiante, correo institucional, rutas absolutas de Windows, cuenta privada, prefijos de token (SonarQube, GitHub, API) y `APP_KEY` de producción: **0 coincidencias** en la copia pública.
2. Metadatos PNG: solo IHDR/IDAT/IEND.
3. `.env` no rastreados; `.gitignore` los ignora.
4. Originales de UI-02/04/12 y del DOCX del profesor: hashes iguales entre el árbol privado y el respaldo.
