# Nota sobre los dos archivos de coherencia

Aclara por qué `coherencia-del-analisis.json` dice `"todoCoincide": false` y qué evidencia respalda que, aun así, las evidencias de SonarQube son coherentes.
**Ninguno de los dos archivos se modificó**: ambos se conservan tal como los generaron las herramientas.

## 1. Qué dice cada archivo

| Archivo | Cómo se generó | Resultado |
|---|---|---|
| `coherencia-del-analisis.json` | Lo escribió `sonar/scripts/recolectar-evidencias.ps1` al terminar la recopilación | `todoCoincide: false`, por **dos** banderas en falso: `evidenciaGuardadaCorrespondeAlAnalisisVigente` y `metricasGuardadasDistintasDeLaLecturaActual` (`test_execution_time`) |
| `verificacion-coherencia-independiente.json` | Calculado sin credenciales a partir de los archivos locales y de consultas de solo lectura (`SELECT`) a la base de datos de SonarQube, sin ejecutar PHPUnit ni el escáner | `todoCoincide: true`, **13 de 13** comprobaciones correctas |

## 2. Causa de la discrepancia

En esa ejecución el script comparó los `ce-task.json` y `metricas.json` **previos** (del segundo análisis, ya sustituidos) con los del tercero y marcó las dos banderas en falso, porque los
archivos previos efectivamente pertenecían a otro análisis y tenían otro `test_execution_time`. Es un **defecto de la lógica del script** (se corrigió después: la comparación con archivos
previos solo se aplica con la opción `-Completar`), no una incoherencia entre las evidencias actuales.

Lo que **sí** coincidía en `coherencia-del-analisis.json`, incluso en esa ejecución: el Quality Gate guardado igual al actual, el informe de PHPUnit importado igual al local, y los cinco
cruces de métricas con incidencias (bugs 0, vulnerabilidades 2, code smells 9, violaciones 11 y hotspots 0).

## 3. Evidencia que respalda la coherencia

1. `verificacion-coherencia-independiente.json`: el `ce-task.json`, la tarea de la base de datos, la salida del escáner y `resumen-sonarqube.json` apuntan al mismo análisis (`bc4c4544`)
   y a la misma tarea (`a8f898fa`), con estado `SUCCESS`; Sonar importó 124 pruebas y 27 fallos, igual que el informe JUnit local.
2. Una búsqueda de todos los UUID presentes en `docs/` y `sonar/` encontró únicamente tres: el análisis vigente, su tarea y el identificador interno del proyecto. Ningún archivo cita un análisis anterior.
3. **Confirmación visual posterior** (2026-10-28, sesión autenticada del navegador): el panel muestra el proyecto `geoeduca-pruebas-calidad`, versión `practica-pruebas-calidad`, 2 incidencias de seguridad,
   9 de fiabilidad y 3 de mantenibilidad (11 incidencias de 3.594 líneas), cobertura 0,0 % y duplicación 0,0 % (`capturas/SQ-01_panel-principal-overall-code.png`), y la pestaña *Activity* lista los tres
   análisis del 2026-10-27 (`capturas/SQ-09_historial-de-analisis.png`).

## 4. Tratamiento en el informe

- Se cita `verificacion-coherencia-independiente.json` como evidencia de coherencia y se **menciona** la discrepancia y su causa.
- No se afirma que el script original fuera correcto ni se oculta su resultado.
- Regenerar `coherencia-del-analisis.json` con el script corregido es opcional: exige que el usuario introduzca su token por entrada oculta, y no aporta evidencia nueva.
  Si se hiciera, el original debería conservarse con otro nombre.
