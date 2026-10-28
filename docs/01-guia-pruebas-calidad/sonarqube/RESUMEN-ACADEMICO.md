# Resumen académico – Etapa de Pruebas y Calidad de Software de GEOEDUCA

Práctica de Pruebas y Calidad de Software. Documento redactado el 2026-10-27 y **actualizado el 2026-10-28** a partir de las evidencias del repositorio
(`docs/01-guia-pruebas-calidad/`). **Todas las cifras proceden de esos archivos; no se ha estimado ni inventado ningún resultado.**
La actualización incorporó la ejecución de UI-09 a UI-12, las capturas del panel de SonarQube y la corrección de dos afirmaciones de la versión anterior
(condiciones del Quality Gate y calificación de fiabilidad; ver 5.3).

> **Alcance de esta etapa:** se evaluó el estado *actual* del prototipo. **No se corrigió ningún defecto ni se desarrolló ninguna
> funcionalidad.** Todos los fallos y requisitos incumplidos que se describen siguen sin corregir.

## 1. Objetivo y alcance

Evaluar la calidad del prototipo de GEOEDUCA (sistema de asistencia escolar con QR dinámico, geolocalización y avisos a apoderados;
backend Laravel 11 y frontend React 18) mediante tres enfoques complementarios: pruebas manuales y de API, pruebas automatizadas con
PHPUnit y análisis estático con SonarQube. El prototipo usa autenticación simulada (`MockUserAuthenticator`), no tiene base de datos y
sus pantallas muestran datos fijos; por tanto, los resultados describen un **prototipo de interfaz**, no un sistema funcional.

## 2. Metodología

1. **Pruebas manuales y de API (50 casos).** Peticiones HTTP reales contra el backend en ejecución y casos de interfaz en el navegador.
   Cada caso registra precondiciones, datos ficticios, procedimiento, resultado esperado, resultado real y evidencia (JSON o captura).
2. **Pruebas automatizadas (124 casos PHPUnit).** Dos suites separadas:
   - `Caracterizacion` (89): fija el comportamiento **actual**, incluidos sus defectos conocidos. Debe aprobar siempre.
   - `RequisitosSeguridad` (35): comprueba el comportamiento **seguro esperado**; falla mientras exista el defecto. No se usó `skip`,
     `todo` ni aserciones triviales, y las expectativas no se modificaron para ocultar fallos.
3. **Análisis estático con SonarQube** (Community Build, en Docker local, con el informe de PHPUnit importado).

**Criterio de resultado (pruebas manuales):** *Aprobado* = el comportamiento real coincide con el esperado; *Fallido* = la funcionalidad
existe y difiere de lo esperado (en seguridad, lo esperado es el comportamiento de un sistema seguro); *Bloqueado* = la funcionalidad no
existe y no es evaluable. Los casos bloqueados se informan aparte y no entran en el porcentaje de aprobación (aprobados / ejecutados).
Ningún caso queda sin ejecutar.

## 3. Herramientas y versiones

| Componente | Versión |
|---|---|
| PHP / Laravel | 8.2.12 / 11.57.0 |
| PHPUnit / Mockery | 11.5.57 / 1.6.15 |
| React / Vite / Node | 18.3.1 / 5.4.21 / v24.19.0 |
| SonarQube Community Build | 26.10.0.133078 (Docker) con PostgreSQL 16.15 |
| SonarScanner CLI | 8.1.0.6389 (imagen `sonar-scanner-cli:12.3.0.4285_8.1.0`, Java 21.0.12.1) |
| Plugins de análisis | PHP 4.1.0, JavaScript 14.0.0 |
| Perfil de calidad | «Sonar way core» para CSS, JS y PHP (según el log del escáner) |
| Docker (motor) / sistema operativo | 29.9.2 / Windows, compilación 10.0.26200 |

Control de versiones: commits `83e45cc`, `5766b24`, `21b12b0` y `b326574` en la rama `practica-pruebas-calidad`. El análisis de Sonar leyó
el árbol de trabajo (commit `b326574`) con archivos de configuración de Sonar aún sin commitear; **el código de producción analizado es idéntico
al del commit `b326574`**.

## 4. Tipos de prueba aplicados y su justificación

| Tipo | Qué verifica en GEOEDUCA | Justificación |
|---|---|---|
| Funcional | Login de los tres perfiles, salud de la API, contratos de justificación y aprobación | Comprueba que lo que el sistema promete a cada perfil funciona de extremo a extremo |
| De validación de datos | DNI, contraseña, perfil y formularios (422 con errores por campo) | La validación es la primera barrera frente a entradas incorrectas y está duplicada en frontend y backend |
| De integración | Proxy de Vite hacia Laravel, CORS, contratos JSON | El frontend y el backend son procesos separados; la mayoría de los fallos reales aparecen en su unión |
| De seguridad | Autenticación, autorización por rol, cierre de sesión, límite de intentos, cabeceras, errores, entradas hostiles | Es un sistema con datos de menores y ubicación; el prototipo declara seguridad que debe poder demostrarse |
| De caracterización (regresión) | Fotografía del comportamiento actual, incluido el defectuoso | Permite detectar cualquier cambio futuro y distinguir «lo que hace hoy» de «lo que debe hacer» |
| Unitarias | `MockUserAuthenticator`, `AuthService` (con Mockery), `DemoAccounts` | Aíslan la costura que se sustituirá al conectar la base de datos |
| Análisis estático | Defectos, vulnerabilidades, mantenibilidad y duplicación del código | Encuentra clases de problemas que las pruebas dinámicas no ejercitan (p. ej. accesibilidad) |

## 5. Resultados reales

### 5.1 Pruebas manuales y de API (50 casos)

| | Aprobado | Fallido | Bloqueado | No ejecutado | Total |
|---|---:|---:|---:|---:|---:|
| API | 23 | 11 | 4 | 0 | 38 |
| Interfaz | 7 | 5 | 0 | 0 | 12 |
| **Total** | **30** | **16** | **4** | **0** | **50** |

**Casos ejecutados: 30 + 16 = 46. Aprobación sobre los ejecutados: 30 / 46 = 65,2 %** (los 4 bloqueados no entran en el cálculo).
*(La versión anterior de este documento daba 28 / 42 = 66,7 % porque UI-09 a UI-12 estaban sin ejecutar.)*
Por tipo (API): funcionales 6/6, validación de datos 9/9, funcional negativo 2/2, integración 3/4, seguridad **3/13**.
Bloqueados: QR dinámico (B-01), geolocalización (B-02), notificaciones (B-03) y persistencia (B-04), porque la funcionalidad no existe.
Interfaz: UI-09 y UI-10 (login de padre y de docente) **aprobaron**; UI-11 (vista móvil de 375 px, contenido recortado) y UI-12 (backend caído, mensaje técnico «status code 500») **fallaron**.
Detalle: `api/matriz-resultados-api.md`, `matriz-resultados-ui.md`, `../PLAN-DE-PRUEBAS.md`.

### 5.2 Pruebas automatizadas con PHPUnit (124 casos)

| Suite | Pruebas | Aserciones | Aprobadas | Fallidas | Omitidas | Duración |
|---|---:|---:|---:|---:|---:|---:|
| Caracterizacion | 89 | 305 | **89** | 0 | 0 | 2,05 s |
| RequisitosSeguridad | 35 | 74 | 8 | **27** | 0 | 0,93 s |
| **Total** | **124** | 379 | **97** | **27** | 0 | 2,98 s |

**Las 27 pruebas fallidas** (todas en `RequisitosSeguridad`; **ninguna corregida**):

| Requisito incumplido | Fallos | Resultado real | IDs manuales |
|---|---:|---|---|
| Las rutas protegidas exigen autenticación (sin credenciales) | 6 | Esperaba 401, recibió 200 | S-01, S-02, S-03 |
| Un token inventado se rechaza | 6 | Esperaba 401, recibió 200 | S-04 |
| Un perfil no usa rutas de otro perfil | 6 | Esperaba 403, recibió 200 | S-05 |
| El token queda inválido tras cerrar sesión; un token válido identifica al usuario | 3 | Recibió 200; `user = null` con token válido | S-06 |
| Límite de intentos de login | 1 | 12 intentos fallidos, todos 401, ningún 429 | S-08 |
| Cabecera `X-Content-Type-Options: nosniff` | 1 | Cabecera ausente | S-10 |
| Un 404 de API con la configuración por defecto no filtra traza | 1 | Con `APP_DEBUG=true` expone `trace, file, line, exception` | S-11 |
| Un error de validación sin `Accept` responde 422 JSON | 1 | Recibió 302 | I-05 |
| Un DNI como arreglo u objeto JSON no provoca error 500 | 2 | HTTP 500 (`Array to string conversion`, `LoginRequest.php:22`) | AUTO-01 (nuevo) |

Aprobadas en la suite de requisitos: las 8 entradas hostiles de S-07 (inyección SQL y script HTML se rechazan con 401/422, sin token ni detalles
internos). **AUTO-01 es un hallazgo nuevo de la automatización**, que las pruebas manuales no habían cubierto.
Detalle: `automatizadas/README.md`, `automatizadas/resultados-por-prueba.csv`, `automatizadas/trazabilidad-matriz-manual.csv`.

### 5.3 Análisis con SonarQube

- **Proyecto:** `geoeduca-pruebas-calidad`. **Análisis vigente:** `bc4c4544`, tarea de procesamiento `SUCCESS`, commit `b326574`.
- Hay **tres análisis** del mismo proyecto; el primero y el segundo están purgados, y todas las evidencias vigentes pertenecen al tercero.
- Se indexaron **70 archivos** (48 de código fuente y 22 de pruebas). El Quality Gate vigente es el predeterminado, **«Sonar way», sin modificar**.

**Quality Gate: OK (*Passed*).** *Corrección respecto de la versión anterior de este documento, que decía «una única condición».* El gate «Sonar way» define **cuatro condiciones sobre el código nuevo**
(0 incidencias, hotspots revisados, cobertura ≥ 80 % y duplicación ≤ 3 %; `capturas/SQ-02_quality-gate-sonar-way-condiciones.png`). La API de estado del proyecto solo devolvió **una condición evaluada**:
`new_violations`, con valor 0 (`quality-gate.json`). Las otras tres no tuvieron valor que evaluar sobre el código nuevo («There are not enough lines to compute coverage», duplicación «Not computed»;
`capturas/SQ-03_resumen-new-code.png`). El «código nuevo» se mide frente a la versión anterior (referencia 2026-10-28T02:37:01Z); como el código no cambió entre análisis, no hay incidencias nuevas.

**Métricas:**

| Dimensión | Métrica | Valor |
|---|---|---|
| Tamaño | Líneas de código / líneas totales / archivos | 3.242 / 3.594 / 48 |
| Tamaño | Distribución de líneas de código | CSS 18 · JavaScript 2.718 · PHP 506 |
| Tamaño | Funciones / clases / sentencias | 196 / 11 / 465 |
| Fiabilidad | Bugs · calificación | 0 · **A** (modelo clásico); el panel (modo MQR) muestra **C** por las 9 incidencias de accesibilidad (ver `INTERPRETACION-METRICAS.md` §1 y `metricas-mqr.json`) |
| Seguridad | Vulnerabilidades · calificación | **2 · C** |
| Seguridad | Security Hotspots · calificación de revisión | 0 · A |
| Mantenibilidad | *Code smells* · deuda técnica · calificación | 9 · 45 min · A |
| Duplicación | Densidad de líneas duplicadas · bloques | 0,0 % · 0 |
| Complejidad | Ciclomática / cognitiva | 333 / 128 |
| Pruebas | Importadas / fallidas / errores / omitidas | 124 / 27 / 0 / 0 |
| Pruebas | Tasa de éxito · tiempo de ejecución | 78,2 % · 3.825 ms |
| Cobertura | Medida | **No medida** (ver 5.4) |

**Recepción de los resultados de PHPUnit:** Sonar importó 124 casos y 27 fallos, igual que el informe local (`verificacion-pruebas-phpunit.json`).
Se importó el informe completo, con los fallos; no se filtraron los aprobados.

### 5.4 Cobertura de código: no medida

**La cobertura no fue medida.** El equipo no dispone de Xdebug ni de PCOV, por lo que no existe ningún informe de cobertura (el log del escáner dice
`No PHPUnit coverage reports provided`). SonarQube muestra `coverage = 0,0 %` únicamente porque su sensor *Zero Coverage* asigna 0 a los archivos
sin informe; **ese 0 % automático no es una medición y no debe citarse como cobertura real**. Sonar sí identifica 398 líneas ejecutables, pero sin
informe no sabe cuántas recorren las pruebas. El frontend no tiene pruebas automatizadas, por lo que tampoco hay cobertura de JavaScript.

### 5.5 Incidencias de SonarQube (11)

| Categoría | Cantidad | Reglas | Dónde |
|---|---:|---|---|
| **Vulnerabilidades** (tipo VULNERABILITY, impacto en seguridad) | 2 | `javascript:S2068`, «posible contraseña fija» | `frontend/src/services/authService.js`, líneas 44 y 46 |
| **Problemas de accesibilidad** (Sonar los clasifica como *code smell* con impacto en fiabilidad) | 9 | `S6853` ×6 (etiqueta de formulario sin control asociado o sin texto accesible); `S6848` ×3 (elementos interactivos no nativos) | `DashboardPadre.jsx` (483, 691, 722, 750), `DashboardAuxiliar.jsx` (589, 602), `Modal.jsx` (15, 19), `Sidebar.jsx` (22) |
| **Security Hotspots** | 0 | — | — |

Todas son de severidad MAJOR y todas están en el **frontend; no hay ninguna en PHP**. Diferencias a tener presentes:
- Una *vulnerabilidad* es un problema de seguridad que Sonar considera real; un *Security Hotspot* es código sensible que requiere revisión humana
  (aquí, 0); un problema de *accesibilidad* afecta a la usabilidad para personas con discapacidad y no a la seguridad.
- La API de hotspots (`api/hotspots`) está **deprecada** por SonarSource: sus hallazgos pasan a ser incidencias de seguridad.
  La recopilación inicial falló con HTTP 400 porque usaba el parámetro `projectKey`, renombrado a `project` en SonarQube 10.2; el servidor respondió
  *«A value must be provided for either parameter 'project' or parameter 'hotspots'»* (`diagnostico-hotspots-http400.json`). Corregido en el recolector.
- La deuda técnica de 45 min equivale a los 9 *code smells* (9 × 5 min); las 2 vulnerabilidades suman 60 min de esfuerzo estimado de revisión.

## 6. Interpretación

### 6.1 «Quality Gate OK» no equivale a ausencia de vulnerabilidades ni a pruebas aprobadas

El Quality Gate estuvo en **OK** y, al mismo tiempo: hay **2 vulnerabilidades** abiertas (calificación de seguridad C), **27 pruebas fallidas**
y **no hay cobertura medida**. No hay contradicción, porque el gate evalúa solo el *código nuevo* respecto a la versión de referencia: la única condición que tuvo valor
fue «sin incidencias nuevas». No evalúa las vulnerabilidades ya existentes, ni los resultados de las pruebas funcionales, ni la cobertura global (sus condiciones de cobertura y duplicación
no tuvieron valor que evaluar; si se midiera la cobertura del código nuevo, la condición de 80 % podría evaluarse).
En consecuencia:
- **No indica ausencia de vulnerabilidades.** Las dos vulnerabilidades existen y el gate no las considera por no ser «código nuevo».
- **No indica que las pruebas funcionales hayan pasado.** Las 27 pruebas fallidas son un resultado independiente que el gate no usa.
- **No refleja los defectos de seguridad reales del sistema** (ver 6.2): Sonar no los detectó.
- No se conoce qué estado habría tenido el gate en el primer análisis (sus datos fueron purgados).

### 6.2 Lo que detectó cada herramienta (complementariedad)

| Hallazgo | Manual / API | PHPUnit | SonarQube |
|---|:---:|:---:|:---:|
| Sin autenticación en `/api/auth/me` y `/api/asistencia/*` | Sí | Sí (12) | **No** |
| Sin autorización por rol (`student` abre rutas de auxiliar) | Sí (también en navegador) | Sí (6) | **No** |
| El token no se invalida al cerrar sesión | Sí | Sí (3) | **No** |
| Sin límite de intentos de login | Sí | Sí (1) | **No** |
| Errores con traza por `APP_DEBUG=true` / sin cabecera `nosniff` | Sí | Sí (2) | **No** |
| HTTP 500 con DNI como arreglo (AUTO-01) | No | Sí (2) | **No** |
| QR no dinámico, fecha fija en el panel, funcionalidades inexistentes | Sí | Parcial (caracterización) | **No** |
| Contraseñas fijas en el frontend (`authService.js`) | Auditoría estática | Documentado como limitación | **Sí (2)** |
| Problemas de accesibilidad en formularios y componentes | No | No | **Sí (9)** |

Las herramientas son **complementarias**: las pruebas dinámicas reproducen los fallos de comportamiento y seguridad; el análisis estático detecta
problemas de código (accesibilidad, contraseñas fijas) que no se ejercitan en ejecución. Sonar **no** señaló las contraseñas de demostración
de `backend/app/Auth/DemoAccounts.php`, ni el CSRF desactivado para `api/*`, ni el `(string)` de AUTO-01. Es coherente con la limitación de
Community Build, que no incluye análisis de flujo de datos (*taint analysis*) para PHP; ese análisis pertenece a la edición Developer.

### 6.3 Requisitos incumplidos (sin corregir)

Los 27 fallos de PHPUnit y los 16 casos manuales fallidos representan requisitos de seguridad y de robustez que **hoy no se cumplen**:
autenticación obligatoria, autorización por rol en el servidor, invalidación del token al cerrar sesión, limitación de intentos de login,
cabeceras de seguridad, errores sin detalles internos, respuestas JSON uniformes y tolerancia a entradas malformadas. **Ninguno ha sido corregido:**
el código de producción no se modificó en toda esta etapa. Las pruebas están preparadas para pasar a aprobadas cuando cada defecto se corrija.

## 7. Limitaciones

1. **Cobertura no medida** (sin Xdebug ni PCOV); el 0 % de Sonar no es una medición.
2. **Frontend sin pruebas automatizadas.** Vitest 3.2.7 se descartó porque introducía 2 vulnerabilidades críticas y la versión corregida exige
   Vite 6 o superior (hoy 5.4.21); esa actualización no se autorizó.
3. **Sonar Community Build:** sin *taint analysis* para PHP; solo analiza la rama principal; la API de hotspots está deprecada.
4. **Quality Gate predeterminado sin ajustar**: de sus cuatro condiciones sobre código nuevo solo una tuvo valor; no se modificó por indicación expresa.
5. **Análisis de dependencias omitido por Sonar** (`Dependency analysis skipped` en el log). Las vulnerabilidades de dependencias
   (advisory crítico de `laravel/framework` 11.57.0 y 7 vulnerabilidades de `npm audit`) se conocen por las auditorías de herramientas
   de paquetes, no por SonarQube.
6. **Cuatro funcionalidades inexistentes** (QR, geolocalización, notificaciones, persistencia), por lo que sus casos están bloqueados. UI-11 solo midió el panel auxiliar a 375 px, y UI-12 se probó con el proxy de Vite, no en un despliegue sin proxy.
7. **Tres análisis del mismo proyecto:** solo el último conserva datos. El análisis se hizo sobre el árbol de trabajo, no sobre un commit limpio.
8. Las pruebas describen un **prototipo con autenticación simulada y sin base de datos**; sus resultados no se extrapolan a un sistema productivo.
9. Las fechas de los archivos de Sonar están en UTC (p. ej. 04:07 UTC equivale a 23:07 hora local del 2026-10-27).

## 8. Conclusiones

1. El prototipo **cumple en lo que ya implementa de forma básica**: el login por perfil, las validaciones de datos y los contratos de la API se comportan
   como se espera (todas las pruebas funcionales y de validación aprobaron).
2. **No cumple los requisitos de seguridad esperados**: faltan autenticación y autorización en el servidor, invalidación de sesión y límite de intentos. Esto
   quedó demostrado de forma independiente por pruebas manuales (16 casos fallidos, 11 de ellos de API) y automatizadas (27 pruebas fallidas), con trazabilidad entre ambas.
3. **Las funcionalidades centrales del producto (QR dinámico, geolocalización, notificaciones, persistencia) no existen**; la interfaz las simula.
4. El análisis estático encontró **2 vulnerabilidades y 9 problemas de accesibilidad en el frontend**, y ninguna en el backend; **el Quality Gate en OK no debe
   interpretarse como señal de calidad suficiente**, por lo explicado en 6.1.
5. La suite de dos niveles (caracterización y requisitos) permite verificar objetivamente cada corrección futura: los 27 fallos son el listado
   medible de lo que falta.
6. Se descubrió un defecto adicional (AUTO-01) solo gracias a la automatización, lo que justifica combinar las tres técnicas.

## 9. Evidencias

| Evidencia | Ubicación (`docs/01-guia-pruebas-calidad/`) |
|---|---|
| Pruebas manuales y de API | `README.md`, `api/` (42 archivos), `matriz-resultados-ui.md`, `ui/` |
| Pruebas automatizadas | `automatizadas/` (JUnit, testdox, CSV de resultados, trazabilidad, resumen) |
| SonarQube | `sonarqube/` (métricas, Quality Gate, incidencias, hotspots, PHPUnit en Sonar, salida del escáner, coherencia) |
| Verificación de coherencia | `sonarqube/verificacion-coherencia-independiente.json` (13 de 13 comprobaciones coinciden) |
| Aclaración sobre `coherencia-del-analisis.json` | Marcó `todoCoincide: false` por un artefacto del script (comparaba con archivos del análisis anterior); ver `sonarqube/NOTA-COHERENCIA.md`. El original se conserva sin cambios |
| Capturas del panel de SonarQube | `sonarqube/capturas/SQ-01` a `SQ-09` |
| Métricas del modelo MQR | `sonarqube/metricas-mqr.json` |
| Interpretación de las métricas y de las preguntas 4.3 | `sonarqube/INTERPRETACION-METRICAS.md` |
| Plan, incidencias, comparación y mejoras | `PLAN-DE-PRUEBAS.md`, `REGISTRO-DE-INCIDENCIAS.md`, `COMPARACION-MANUAL-AUTOMATIZADA.md`, `PROPUESTAS-DE-MEJORA.md` |
| Auditoría de dependencias | `dependencias/composer-audit.json`, `dependencias/npm-audit.json` |
| Informe técnico final | `INFORME-TECNICO-FINAL.md` |
