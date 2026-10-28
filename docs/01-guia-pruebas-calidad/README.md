# Guía de Pruebas y Calidad de Software – GEOEDUCA

Copia **pública y revisada** de las evidencias de la práctica de Pruebas y Calidad de Software.

Sigue la *Guía de Práctica de Ejecución de Pruebas* del curso (4 actividades, 9 secciones del informe). El DOCX original del profesor **no está en esta carpeta**: se conserva únicamente en el archivo académico privado. Ver `AUDITORIA-DE-PRIVACIDAD.md`.

Contiene **pruebas manuales y de API**, **pruebas automatizadas con PHPUnit** y **análisis estático con SonarQube**, más el informe técnico final.

- Las cifras de esta carpeta describen el **código base** sobre el que se ejecutó la práctica (sin modificar producción). El último commit de esa rama académica es `b326574`.
- La suite PHPUnit **adaptada** a la rama `mejoras-interfaces-dinamicas` vive en `backend/tests/` y se documenta en `REGISTRO-DE-ADAPTACION-DE-PRUEBAS.md`. Esos resultados no sustituyen las evidencias académicas de `automatizadas/`.
- Entorno de la práctica: PHP 8.2.12, Laravel 11.57.0, PHPUnit 11.5.57, Node v24.19.0, Vite 5.4.21, SonarQube Community Build 26.10.0.133078.
- Backend `http://127.0.0.1:8000` (`php artisan serve`), frontend `http://localhost:5173` (`npm run dev`).
- **Durante la práctica no se modificó el código de producción** (`backend/app`, `backend/routes`, `backend/config`, `backend/bootstrap`, `frontend/src`) ni se corrigió ningún defecto.

## Dónde está cada cosa (por actividad de la guía)

| Actividad | Documento principal | Evidencias |
|---|---|---|
| Informe final (sección V de la guía) | `INFORME-TECNICO-FINAL.md` | Todas las siguientes |
| 1. Identificación y planificación | `PLAN-DE-PRUEBAS.md` (matriz de 50 casos) | `api/matriz-resultados-api.md`, `matriz-resultados-ui.md` |
| 2. Ejecución de pruebas | Este `README.md` (totales) y `REGISTRO-DE-INCIDENCIAS.md` | `api/`, `ui/`, `ejecutar-pruebas-api.ps1`, `dependencias/` |
| 3. Automatización | `automatizadas/README.md`, `COMPARACION-MANUAL-AUTOMATIZADA.md` | `automatizadas/` (evidencia académica) y `backend/tests/` (suite reutilizable) |
| 4. SonarQube | `sonarqube/RESUMEN-ACADEMICO.md`, `sonarqube/INTERPRETACION-METRICAS.md`, `PROPUESTAS-DE-MEJORA.md` | `sonarqube/`, `sonarqube/capturas/` |
| Control de cumplimiento | `MATRIZ-CUMPLIMIENTO-Y-PENDIENTES.md` | — |
| Integración posterior | `REGISTRO-DE-ADAPTACION-DE-PRUEBAS.md` | `../02-desarrollo/RESULTADOS-PHPUNIT-INTEGRACION.md` |
| Privacidad de esta copia | `AUDITORIA-DE-PRIVACIDAD.md` | — |

## Contenido de la carpeta

| Ruta | Qué contiene |
|---|---|
| `ejecutar-pruebas-api.ps1` | Script que emite las peticiones HTTP reales y guarda la evidencia. Las contraseñas de demostración se leen de variables de entorno (`GEO_PW_STUDENT`, `GEO_PW_PARENT`, `GEO_PW_STAFF`) y no se escriben en ningún archivo |
| `api/TC-<ID>.json` | Una evidencia por caso de API: petición, cabeceras, cuerpo, estado, respuesta, esperado y resultado |
| `api/matriz-resultados-api.md` | Matriz de los 38 casos de API (generada por el script) |
| `api/resumen-api.json`, `api/resultados-api.json` | Totales y resultados por caso |
| `api/route-list.json` | Salida de `php artisan route:list` (base de los casos B-01 a B-03) |
| `matriz-resultados-ui.md` | Matriz de los 12 casos de interfaz |
| `ui/*.png` | Capturas de los casos de interfaz (UI-01, 02, 03, 04, 06, 09, 10, 11 ×2 y 12). En UI-02, UI-04 y UI-12 el campo de DNI está tapado a nivel de píxel |
| `ui/mediciones-dom.json`, `ui/mediciones-dom-ui09-ui12.json` | Mediciones literales tomadas del navegador (UI-01 a UI-08 y UI-09 a UI-12) |
| `PLAN-DE-PRUEBAS.md` | Plan de pruebas: tipos, justificación y matriz de planificación de los 50 casos con referencia a la ejecución |
| `REGISTRO-DE-INCIDENCIAS.md` | 16 incidencias consolidadas (manuales, interfaz, PHPUnit, SonarQube y dependencias) |
| `COMPARACION-MANUAL-AUTOMATIZADA.md` | Apartado 3.4 de la guía |
| `PROPUESTAS-DE-MEJORA.md` | Mejoras priorizadas (no implementadas) |
| `INFORME-TECNICO-FINAL.md` | Informe con las 9 secciones exigidas |
| `MATRIZ-CUMPLIMIENTO-Y-PENDIENTES.md` | Cumplimiento de cada requisito de la guía y tareas que requieren intervención humana |
| `automatizadas/` | Resultados PHPUnit de la práctica sobre el código base. Ver su `README.md` |
| `sonarqube/` | Resultados del análisis: métricas, Quality Gate, incidencias, resumen académico, interpretación, `capturas/` y verificación de coherencia |
| `dependencias/` | Salida de `composer audit` y `npm audit` (2026-10-28) |
| `REGISTRO-DE-ADAPTACION-DE-PRUEBAS.md` | Qué se adaptó en la suite al integrar el código de Diego, y por qué |
| `AUDITORIA-DE-PRIVACIDAD.md` | Qué se omitió o redactó en esta copia pública, y por qué |

## Criterio de resultado (pruebas manuales)

- **Aprobado:** el comportamiento real coincide con el esperado.
- **Fallido:** la funcionalidad existe y su comportamiento real difiere del esperado (en seguridad, el esperado es el de un sistema seguro).
- **Bloqueado:** la funcionalidad no existe y no se puede evaluar. Se informa aparte y no entra en el porcentaje de aprobación.

Un caso no se aprueba porque la pantalla muestre datos: se evalúa el comportamiento. Los contratos de F-05 y F-06
se aprueban solo como contrato de validación y respuesta; la persistencia se evalúa en B-04 (bloqueado).

## Totales (práctica sobre el código base)

| | Aprobado | Fallido | Bloqueado | No ejecutado | Total |
|---|---:|---:|---:|---:|---:|
| API (38 casos) | 23 | 11 | 4 | 0 | 38 |
| Interfaz (12 casos) | 7 | 5 | 0 | 0 | 12 |
| **Total (50 casos)** | **30** | **16** | **4** | **0** | **50** |

Casos ejecutados = 30 + 16 = 46. **Aprobación sobre los ejecutados: 30 / 46 = 65,2 %.** Los 4 casos bloqueados no entran en el cálculo.
Los casos de interfaz se ejecutaron en dos tandas (UI-01 a UI-08 el 2026-10-27 y UI-09 a UI-12 el 2026-10-28); el código de producción no cambió entre ambas (ver `matriz-resultados-ui.md`).

Por tipo (API): funcionales 6/6, validación de datos 9/9, funcional negativo 2/2, integración 3/4, seguridad 3/13.

## Incidencias

Las 16 incidencias, con descripción, comportamiento observado, evidencia, impacto y prioridad, están en **`REGISTRO-DE-INCIDENCIAS.md`**
(2 críticas, 5 altas, 5 medias y 4 bajas). Ninguna está corregida en el código de producción.

## Automatización y análisis estático (práctica)

- **PHPUnit (código base):** 124 pruebas, 97 aprobadas y 27 fallidas (78,2 % de aprobación global; 100 % en `Caracterizacion` y 22,9 % en `RequisitosSeguridad`). Ver `automatizadas/README.md`.
- **PHPUnit (rama de Diego, tras adaptar caracterización):** 125 pruebas, 116 aprobadas y 9 fallidas. Ver `REGISTRO-DE-ADAPTACION-DE-PRUEBAS.md`.
- **SonarQube:** Quality Gate aprobado, 0 *bugs*, 2 vulnerabilidades, 9 *code smells* (accesibilidad), 0 *Security Hotspots*, cobertura **no medida**. Ver `sonarqube/RESUMEN-ACADEMICO.md`. El entorno Docker de SonarQube no se publica.

## Seguridad de las evidencias

- Los tokens se sustituyen por `<REDACTED>` y las contraseñas por `<MASKED>` en todos los JSON (PowerShell 5.1 los escapa como `\u003c…\u003e`).
- El DNI de demostración del estudiante se sustituye por `<DNI-DEMO-ESTUDIANTE>` en esta copia pública.
- Las rutas absolutas del equipo se sustituyen por `<RAIZ_PROYECTO>`.
- En UI-02, UI-04 y UI-12 una barra negra cubre los dígitos del DNI en el campo de login. El campo de contraseña ya aparece enmascarado por el navegador.
- Los originales sin redactar se conservan fuera de este repositorio. Detalle en `AUDITORIA-DE-PRIVACIDAD.md`.
- Para repetir la batería de API: levantar ambos servidores, definir las tres variables de entorno y ejecutar
  `powershell -ExecutionPolicy Bypass -File docs\01-guia-pruebas-calidad\ejecutar-pruebas-api.ps1`.
