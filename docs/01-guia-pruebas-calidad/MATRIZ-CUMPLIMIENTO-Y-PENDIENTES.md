# Cumplimiento de la Guía de Práctica de Ejecución de Pruebas – GEOEDUCA

Actualizada el 2026-10-28 tras completar las cuatro actividades y el informe final. Fuente: la *Guía de Práctica de Ejecución de Pruebas* del curso
(4 actividades, 9 tablas, estructura del informe de 9 secciones y rúbrica de 20 puntos). El DOCX original del profesor **no se publica** en este repositorio; se conserva en el archivo académico privado. **Este documento no asigna ninguna calificación.**

**Estados:** COMPLETO (hay evidencia y está redactado) · PARCIAL (falta una parte, indicada) · PENDIENTE (no existe).
Las rutas son relativas a `docs/01-guia-pruebas-calidad/`, salvo las que empiezan por `backend/`. La carpeta `sonar/` y `sonar-project.properties` pertenecen al archivo académico privado y no forman parte de esta copia pública.

## 0. Lo que la guía exige y lo que NO exige

| La guía exige | La guía NO exige |
|---|---|
| ≥ 3 tipos de pruebas **justificados**; matriz de planificación | Un Quality Gate aprobado |
| Ejecutar los casos y registrar resultados **reales**, con evidencias | Una cobertura mínima |
| Aprobación manual = aprobados / ejecutados; los **bloqueados se registran aparte** | Corregir los defectos encontrados |
| Pruebas automatizadas con código, comandos, **capturas de la ejecución** y análisis de fallos | Implementar nuevas funcionalidades |
| Análisis de SonarQube con **capturas** del panel, del Quality Gate y de las métricas, interpretación y mejoras priorizadas | Automatizar todas las pruebas manuales |
| Informe técnico individual con **9 secciones** | Que los resultados sean favorables |

La guía solo define los estados Aprobado, Fallido y Bloqueado; el estado «Pendiente» que se usó provisionalmente en cuatro casos de interfaz ya no existe: se ejecutaron todos.

## A. Matriz de cumplimiento

### Actividad 1. Identificación de las pruebas

| Ref. guía | Requisito | Evidencia (ruta exacta) | Estado | Observación |
|---|---|---|---|---|
| 1.1 (Tabla 1) | Nombre del proyecto | `INFORME-TECNICO-FINAL.md` §2.1 | COMPLETO | |
| 1.1 | Objetivo del software (problema y finalidad) | `INFORME-TECNICO-FINAL.md` §2.1 | PARCIAL | Redactado a partir del texto de la interfaz y del diseño del prototipo; **el autor debe confirmar la redacción oficial** |
| 1.1 | Tecnologías reales | `INFORME-TECNICO-FINAL.md` §2.2 (PHP 8.2.12, Laravel 11.57.0, Sanctum 4.3.3 sin uso, React 18.3.1, Vite 5.4.21, Tailwind 3.4.19, Axios 1.20.0; sin base de datos en uso) | COMPLETO | |
| 1.1 | Funcionalidades principales implementadas | `INFORME-TECNICO-FINAL.md` §2.3 (implementadas, simuladas y no existentes) | COMPLETO | |
| 1.1 | Entorno de ejecución | `INFORME-TECNICO-FINAL.md` §2.1 y §4.1 | COMPLETO | |
| 1.1 | Herramientas de pruebas | `INFORME-TECNICO-FINAL.md` §2.1 | COMPLETO | |
| 1.2 (Tabla 2) | Al menos 3 tipos de prueba | `PLAN-DE-PRUEBAS.md` §2; `INFORME-TECNICO-FINAL.md` §3.1 (6 tipos manuales, más automatizadas y análisis estático) | COMPLETO | Rendimiento no se aplicó, y está dicho |
| 1.2 | Justificación según funciones, riesgos y características | `INFORME-TECNICO-FINAL.md` §3.1; `sonarqube/RESUMEN-ACADEMICO.md` §4 | COMPLETO | |
| 1.3 (Tabla 3) | Matriz de planificación: ID, funcionalidad, tipo, procedimiento, resultado esperado y referencia a la ejecución | `PLAN-DE-PRUEBAS.md` §3 (50 casos, generados por programa desde las matrices) e `INFORME-TECNICO-FINAL.md` §3.2 | COMPLETO | |
| 1.3 | Casos adaptados a las funcionalidades reales | `api/matriz-resultados-api.md`, `matriz-resultados-ui.md` | COMPLETO | |
| Evidencia 1 | Matriz de planificación y justificación | Filas anteriores | COMPLETO | |

### Actividad 2. Ejecución de las pruebas

| Ref. guía | Requisito | Evidencia (ruta exacta) | Estado | Observación |
|---|---|---|---|---|
| 2.1 | Checklist de preparación del entorno | `INFORME-TECNICO-FINAL.md` §4.1 | COMPLETO | Indica que no hay base de datos |
| 2.1 | Registrar la configuración para reproducir | `ejecutar-pruebas-api.ps1`, `automatizadas/README.md`, `backend/phpunit.xml`; `sonar/README.md` queda en el archivo privado | COMPLETO | |
| 2.2 | Ejecutar los casos definidos | `api/matriz-resultados-api.md` (38) y `matriz-resultados-ui.md` (12): **50 de 50 con estado** (30 aprobados, 16 fallidos, 4 bloqueados) | COMPLETO | UI-09 a UI-12 se ejecutaron el 2026-10-28 |
| 2.2 (Tabla 4) | Registrar los 9 campos por caso | `INFORME-TECNICO-FINAL.md` §4.2; matrices de origen | COMPLETO | |
| 2.2 | Evidencia por caso | `api/TC-*.json` (38), `ui/*.png` (10), `ui/mediciones-dom.json`, `ui/mediciones-dom-ui09-ui12.json` | COMPLETO | UI-05, UI-07 y UI-08 se evidenciaron con mediciones, no con captura |
| 2.3 (Tabla 5) | Total, ejecutados, aprobados, fallidos, bloqueados | `README.md`, `INFORME-TECNICO-FINAL.md` §4.3: 50 / 46 / 30 / 16 / 4 | COMPLETO | |
| 2.3 | Porcentaje = aprobados / ejecutados, bloqueados fuera | 30 / 46 = **65,2 %** | COMPLETO | |
| 2.3 | Incidencias con comportamiento observado y consecuencias | `REGISTRO-DE-INCIDENCIAS.md` (16 incidencias con descripción, comportamiento, evidencia, impacto y prioridad) | COMPLETO | |
| Evidencia 2 | Casos, capturas o registros, resumen e incidencias | Filas anteriores | COMPLETO | |

### Actividad 3. Implementación y ejecución de pruebas automatizadas

| Ref. guía | Requisito | Evidencia (ruta exacta) | Estado | Observación |
|---|---|---|---|---|
| 3.1 | Identificar qué casos manuales se automatizan | `automatizadas/trazabilidad-matriz-manual.csv`: 35 de 38 casos de API | COMPLETO | Sin automatizar: I-01, I-02, S-09 y los 12 de interfaz, con motivo |
| 3.1 | Justificar la selección con los criterios de la guía | `INFORME-TECNICO-FINAL.md` §5.1 | COMPLETO | |
| 3.2 | Nombre y propósito de la herramienta | `INFORME-TECNICO-FINAL.md` §5.2; `backend/phpunit.xml` | COMPLETO | La guía cita PHPUnit y Pest para PHP/Laravel; Mockery lo añade el proyecto |
| 3.2 | Casos de prueba automatizados | `automatizadas/resultados-por-prueba.csv` (124), `testdox-*.txt`; `INFORME-TECNICO-FINAL.md` §5.3 | COMPLETO | |
| 3.2 | Código fuente | `backend/tests/` (18 clases + 4 de apoyo; commit `b326574`); fragmentos en `INFORME-TECNICO-FINAL.md` §5.4 | COMPLETO | |
| 3.2 | Comandos de ejecución | `automatizadas/README.md`; `INFORME-TECNICO-FINAL.md` §5.5 | COMPLETO | |
| 3.2 | Evidencias de la ejecución | `automatizadas/salida-*.txt`, `junit-*.xml`; segunda ejecución con fecha, comando y salida en `automatizadas/ejecucion-2026-10-28/` | PARCIAL | **Faltan las capturas de pantalla de la terminal**: las herramientas disponibles no capturan la terminal (tarea manual, anexo C del informe) |
| 3.2 | Resultados y análisis de los fallos | `INFORME-TECNICO-FINAL.md` §5.7 (27 fallos trazados a incidencias y casos manuales) | COMPLETO | |
| 3.3 (Tabla 7) | Total, aprobadas, fallidas, omitidas y tiempo | `automatizadas/resumen-automatizadas.json`, `automatizadas/resumen-comparativo-ejecuciones.json`: 124 / 97 / 27 / 0 / 2,98 s | COMPLETO | |
| 3.3 | Porcentaje de aprobación | **97 / 124 = 78,2 %** (100 % y 22,9 % por suite): `automatizadas/README.md`, `INFORME-TECNICO-FINAL.md` §5.6 | COMPLETO | |
| 3.3 | Omitidas o no ejecutadas por separado | 0 omitidas, 0 incompletas | COMPLETO | |
| 3.4 | Comparación manual vs automatizada (6 aspectos) | `COMPARACION-MANUAL-AUTOMATIZADA.md`; `INFORME-TECNICO-FINAL.md` sección 6 (concordancia 35 de 35, calculada por programa) | COMPLETO | |
| Evidencia 3 | Código, comandos, reporte, capturas y comparación | Código ✓, comandos ✓, reporte ✓, comparación ✓, capturas ✗ | PARCIAL | Solo faltan las capturas |

### Actividad 4. Análisis estático con SonarQube

| Ref. guía | Requisito | Evidencia (ruta exacta) | Estado | Observación |
|---|---|---|---|---|
| 4.1 | Preparar el proyecto | Archivo académico privado (`sonar-project.properties`, `sonar/`). No se publica | COMPLETO | Conservado fuera de este repositorio |
| 4.1 | Configurar el proyecto en SonarQube | Clave `geoeduca-pruebas-calidad`: `sonarqube/resumen-sonarqube.json` | COMPLETO | |
| 4.1 | Ejecutar el análisis | `sonarqube/salida-escaner.txt` (`ANALYSIS SUCCESSFUL`, 70 archivos) | COMPLETO | |
| 4.1 | Verificar que finalizó | `sonarqube/ce-task.json` (`SUCCESS`) | COMPLETO | |
| 4.1 | Acceder al panel de resultados | `sonarqube/capturas/SQ-01` a `SQ-09` (capturas reales del panel autenticado) | COMPLETO | |
| 4.1 | Registrar las métricas | `sonarqube/metricas.json`, `sonarqube/metricas-mqr.json` | COMPLETO | |
| 4.1 | Cobertura «cuando corresponda» | `automatizadas/cobertura-no-disponible.txt`; `sonarqube/INTERPRETACION-METRICAS.md` §4; `sonarqube/capturas/SQ-07_cobertura-0-automatico.png` | COMPLETO | Declarada **no medida** y justificada; la guía no la exige. Instalar PCOV es opcional |
| 4.2 (Tabla 8) | Quality Gate, bugs, vulnerabilidades, code smells, cobertura, duplicación y las tres calificaciones | `sonarqube/INTERPRETACION-METRICAS.md` §2; `INFORME-TECNICO-FINAL.md` §7.2 | COMPLETO | Incluye la nota sobre fiabilidad A (clásica) y C (MQR) |
| 4.2 | Interpretación de cada métrica | Misma tabla, columna «Interpretación» | COMPLETO | |
| 4.2 | No inventar métricas | `sonarqube/metricas.json` (campo `metricasSolicitadasSinValor`) | COMPLETO | |
| 4.3 (8 preguntas) | Quality Gate; problemas; cantidades; cobertura; partes críticas; qué corregir primero y por qué; relación con las pruebas; mejoras | `sonarqube/INTERPRETACION-METRICAS.md` §5; `INFORME-TECNICO-FINAL.md` §7.6 | COMPLETO | Las 8 respondidas |
| Evidencia 4 | Captura del panel principal | `sonarqube/capturas/SQ-01_panel-principal-overall-code.png` | COMPLETO | Muestra la clave del proyecto en el encabezado |
| Evidencia 4 | Captura del Quality Gate | `sonarqube/capturas/SQ-01_panel-principal-overall-code.png` (estado *Passed*) y `sonarqube/capturas/SQ-02_quality-gate-sonar-way-condiciones.png` (condiciones, solo lectura) | COMPLETO | |
| Evidencia 4 | Capturas o reportes de las métricas | `SQ-03` a `SQ-09`; `sonarqube/metricas.json`, `incidencias.json`, `quality-gate.json` | COMPLETO | |
| Evidencia 4 | Interpretación | `sonarqube/INTERPRETACION-METRICAS.md` | COMPLETO | |
| Evidencia 4 | Lista priorizada de mejoras | `PROPUESTAS-DE-MEJORA.md` (14 mejoras de software y 6 de proceso) | COMPLETO | No se implementó ninguna |
| Rúbrica (nivel 1) | Demostrar que el análisis corresponde al proyecto evaluado | `sonarqube/salida-escaner.txt`; `sonarqube/verificacion-coherencia-independiente.json`; `sonarqube/capturas/SQ-01` y `SQ-09` | COMPLETO | |

### Informe técnico final (sección V de la guía)

| # | Sección exigida | Ubicación en `INFORME-TECNICO-FINAL.md` | Estado | Observación |
|---|---|---|---|---|
| 1 | Portada | Sección 1 | PARCIAL | Faltan estudiante, asignatura, docente, sección, institución y fecha (marcadores **[COMPLETAR]**) |
| 2 | Descripción del proyecto de software | Sección 2 | COMPLETO | Objetivo a confirmar |
| 3 | Identificación y planificación de las pruebas | Sección 3 | COMPLETO | |
| 4 | Ejecución de las pruebas y resultados obtenidos | Sección 4 | COMPLETO | |
| 5 | Implementación y ejecución de las pruebas automatizadas | Sección 5 | PARCIAL | Falta insertar las capturas de la terminal (5.8) |
| 6 | Comparación entre pruebas manuales y automatizadas | Sección 6 | COMPLETO | |
| 7 | Análisis de calidad del código con SonarQube | Sección 7 | COMPLETO | |
| 8 | Conclusiones | Sección 8 | COMPLETO | |
| 9 | Evidencias y anexos | Sección 9 (anexos A a D) | COMPLETO | |

Entregables: `INFORME-TECNICO-FINAL.md` y `INFORME-TECNICO-FINAL.docx` (generado con `herramientas/md_a_docx.py`, solo con Python estándar; validado estructuralmente, **no visualizado en Word**).

## B. Evidencias reutilizadas y nuevas

| Para | Archivos (en `docs/01-guia-pruebas-calidad/` salvo indicación) |
|---|---|
| Plan y resultados manuales de API | `api/matriz-resultados-api.md`, `api/resultados-api.json`, `api/TC-*.json` (38) |
| Resultados de interfaz | `matriz-resultados-ui.md`, `ui/*.png` (10; **5 nuevas**: UI-09, UI-10, UI-11 ×2, UI-12), `ui/mediciones-dom.json`, `ui/mediciones-dom-ui09-ui12.json` (**nuevo**) |
| PHPUnit | `backend/tests/**`, `backend/phpunit.xml`, `automatizadas/*` y `automatizadas/ejecucion-2026-10-28/` (**nueva ejecución**) |
| SonarQube | `sonarqube/*.json`, `sonarqube/capturas/` (**9 capturas nuevas**), `sonarqube/metricas-mqr.json` (**nuevo**), `sonarqube/NOTA-COHERENCIA.md` (**nuevo**) |
| Dependencias | `dependencias/composer-audit.json`, `dependencias/npm-audit.json` (**nuevos**) |
| Documentos de síntesis (**nuevos**) | `PLAN-DE-PRUEBAS.md`, `REGISTRO-DE-INCIDENCIAS.md`, `COMPARACION-MANUAL-AUTOMATIZADA.md`, `PROPUESTAS-DE-MEJORA.md`, `sonarqube/INTERPRETACION-METRICAS.md`, `INFORME-TECNICO-FINAL.md`/`.docx` |

## C. Los tres análisis de SonarQube y los archivos de coherencia

- El servidor tiene **tres análisis**; el único vigente es `bc4c4544` (tarea `a8f898fa`, commit `b326574`). La pestaña *Activity* del panel los muestra (`sonarqube/capturas/SQ-09_historial-de-analisis.png`).
- **La documentación usa exclusivamente el análisis vigente.** Todos los UUID presentes en `docs/` y `sonar/` son tres: el análisis vigente, su tarea y el identificador interno del proyecto.
- `sonarqube/coherencia-del-analisis.json` dice `todoCoincide: false` por un defecto de lógica del script, no por una incoherencia. **Se conservó sin modificar**; la causa y la evidencia que respalda la coherencia
  (`verificacion-coherencia-independiente.json`, 13 de 13, y las capturas del panel) están en `sonarqube/NOTA-COHERENCIA.md`.
- Dos afirmaciones de documentos anteriores **se corrigieron al ver el panel real**: el Quality Gate «Sonar way» tiene cuatro condiciones (solo una tuvo valor), y la fiabilidad es A en el modelo clásico y **C** en el panel (MQR).

## D. Documentación obsoleta: estado

| # | Hallazgo anterior | Estado |
|---|---|---|
| C1, C2, C4 | `README.md`: commit base, «no se implementó PHPUnit ni SonarQube», índice sin `automatizadas/` ni `sonarqube/` | **Corregido** (índice reescrito) |
| C3 | `README.md`: sección «Casos recomendados para automatizar (E0-B)» | **Eliminada**; reemplazada por enlaces |
| C5, C6, C9 | `automatizadas/README.md`: commit base, recomendaciones previas a Sonar, porcentaje de aprobación ausente | **Corregidos** |
| C7 | `sonar/README.md`: «todavía no se ha ejecutado ningún análisis» | **Corregido** |
| C8 | `coherencia-del-analisis.json` | **Aclarado** en `NOTA-COHERENCIA.md` |
| C10 | «Windows 10» | **Corregido** a «Windows (compilación 10.0.26200)» |
| C11, C12 | Cobertura `0.0` en datos crudos; fechas UTC | Documentado en `sonarqube/INTERPRETACION-METRICAS.md` §4 y §6 |

## E. Evaluación por indicador de la rúbrica (sin nota)

### Indicador 1 – Ejecución de pruebas (8 puntos)

| Criterio del nivel «Destacado» | Situación |
|---|---|
| Identifica y justifica los tipos de pruebas | Cumplido (`PLAN-DE-PRUEBAS.md`, informe §3) |
| Ejecuta casos variados | Cumplido: 6 tipos, 50 casos, **46 ejecutados** y 4 bloqueados con causa |
| Registra resultados reales | Cumplido |
| Evidencias verificables | Cumplido (38 JSON, 10 capturas y mediciones) |
| Analiza los defectos | Cumplido (`REGISTRO-DE-INCIDENCIAS.md`) |
| Descripción del proyecto y entorno | Cumplido, con el objetivo **pendiente de confirmar** |

### Indicador 2 – Automatización de pruebas (7 puntos)

| Criterio del nivel «Destacado» | Situación |
|---|---|
| Implementa y ejecuta pruebas pertinentes | Cumplido: 124 pruebas, dos ejecuciones con idéntico resultado |
| Código verificable | Cumplido (`backend/tests/`, publicado) |
| Resultados reproducibles | Cumplido |
| **Evidencias: capturas de la ejecución** | **Parcial**: faltan las capturas de la terminal |
| Análisis de fallos y justificación | Cumplido |
| Comparación manual vs automatizada | Cumplido |

### Indicador 3 – Análisis con SonarQube (5 puntos)

| Criterio del nivel «Destacado» | Situación |
|---|---|
| Análisis verificable del proyecto evaluado | Cumplido (capturas del panel, salida del escáner, coherencia) |
| Métricas relevantes registradas | Cumplido |
| Interpretación de los hallazgos | Cumplido |
| Mejoras priorizadas y justificadas | Cumplido |
| Capturas del panel, del Quality Gate y de las métricas | Cumplido |

## F. Pendientes que requieren intervención humana

| # | Pendiente | Por qué no se pudo hacer | Instrucción |
|---|---|---|---|
| 1 | **Capturas de pantalla de PHPUnit** (2) | Ninguna herramienta disponible captura la terminal | En una terminal, `cd backend` y: `php vendor/bin/phpunit --testsuite Caracterizacion` (debe verse `OK (89 tests, 305 assertions)`) y `php vendor/bin/phpunit --testsuite RequisitosSeguridad` (debe verse `Tests: 35, Assertions: 74, Failures: 27`). Insertarlas en la sección 5.8 del informe |
| 2 | **Datos de la portada** | Son personales o institucionales | Completar los campos **[COMPLETAR]** de la sección 1 |
| 3 | **Confirmar el objetivo del proyecto** | No consta un documento de objetivos | Revisar el texto de la sección 2.1 |
| 4 | **Abrir el `.docx` en Word y revisarlo** | Se validó la estructura, pero no se pudo visualizar | Abrir, comprobar tablas e imágenes y exportar a PDF si se necesita |
| 5 | **Formato de entrega** | La guía no lo especifica | Confirmar con el docente |
| 6 | Revocar los tokens de SonarQube | Es una acción de la cuenta del usuario | *My Account → Security* |
| 7 | Commit y publicación | Requiere autorización explícita y no se hizo ninguno | Revisar el inventario de cambios y autorizar |

No se propone instalar PCOV o Xdebug, actualizar Vite o Laravel, corregir defectos ni iniciar la integración con el repositorio de el repositorio principal ni la planificación DLC.
