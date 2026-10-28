# Interpretación de los resultados de SonarQube – GEOEDUCA

Corresponde a los apartados **4.2** (tabla 8) y **4.3** (ocho preguntas) de la guía. Cada valor se tomó del análisis vigente (`bc4c4544`, commit `b326574`) y se verificó contra
`metricas.json`, `metricas-mqr.json`, `quality-gate.json`, `incidencias.json` y las capturas de `capturas/`. Lo **comprobado** se distingue de la **interpretación**.
Las rutas son relativas a `docs/01-guia-pruebas-calidad/sonarqube/`.

## 1. Cómo leer el panel: dos modelos de calificación

SonarQube Community Build 26.10 trabaja en **modo MQR** (*Multi-Quality Rule*, indicado en el pie de página de las capturas). Mide cada incidencia por el **impacto** que tiene en
seguridad, fiabilidad o mantenibilidad, y además conserva las métricas del modelo clásico (*bugs*, *vulnerabilities*, *code smells*).
Esto explica un resultado que puede parecer contradictorio:

| Magnitud | Modelo clásico (`metricas.json`) | Modelo MQR (el que muestra el panel; `metricas-mqr.json`) |
|---|---|---|
| Fiabilidad | **A**, con 0 *bugs* | **C**, con 9 incidencias de impacto medio y 45 min de esfuerzo |
| Seguridad | **C**, con 2 vulnerabilidades | **C**, con 2 incidencias y 60 min de esfuerzo |
| Mantenibilidad | **A**, con 9 *code smells* y 45 min de deuda | **A**, con 3 incidencias de impacto bajo y 15 min de esfuerzo |

Son los **mismos 11 hallazgos** clasificados con dos criterios. Las 9 incidencias de accesibilidad son *code smells* (no *bugs*) para el modelo clásico, por lo que la fiabilidad clásica es A;
el modelo MQR les asigna impacto de fiabilidad medio, por lo que el panel muestra C. En MQR, una misma incidencia puede afectar a varias cualidades (las 3 incidencias `S6848`
afectan a fiabilidad y a mantenibilidad), por eso 2 + 9 + 3 suman 14 impactos para 11 incidencias. **Ambos valores son reales; el informe cita los dos.**

## 2. Tabla 8 – Registro de resultados

| Métrica | Resultado obtenido | Interpretación |
|---|---|---|
| **Quality Gate** | **Aprobado** (*Passed*), gate «Sonar way» sin modificar | Significa que el *código nuevo* no introdujo incidencias respecto de la versión de referencia. **No indica ausencia de defectos** (ver sección 3) |
| **Bugs** | **0** | El análisis estático no halló errores de lógica del tipo *bug* según las reglas del perfil «Sonar way». No significa que no existan: las pruebas manuales y PHPUnit hallaron 16 casos y 27 pruebas fallidas que Sonar no detecta |
| **Vulnerabilities** | **2** (`javascript:S2068`) | Dos posibles contraseñas fijas en `frontend/src/services/authService.js` (líneas 44 y 46): las cuentas de respaldo sin conexión. Son datos de demostración, pero viajan en el paquete del navegador |
| **Code Smells** | **9** (esfuerzo de 45 min) | Todos son de **accesibilidad** en el frontend: 6 × `S6853` (etiquetas de formulario sin control asociado o sin texto accesible) y 3 × `S6848` (elementos interactivos no nativos). Afectan a usuarios con lectores de pantalla o teclado |
| **Coverage** | **No medida** | No hay informe de cobertura (sin Xdebug ni PCOV). El «0,0 %» del panel lo asigna el sensor *Zero Coverage* a archivos sin informe y **no es una medición** (ver sección 4) |
| **Duplicated Lines (%)** | **0,0 %** (0 bloques, sobre 3.594 líneas) | Código sin duplicación significativa. En el código nuevo aparece «Not computed» |
| **Maintainability Rating** | **A** | La deuda técnica es baja: 45 min en el modelo clásico y 15 min en MQR. Con 3.242 líneas de código es pequeña |
| **Reliability Rating** | **A** (clásico) / **C** (MQR) | Sin *bugs*, pero con 9 incidencias de accesibilidad de impacto medio. Es la calificación que muestra el panel |
| **Security Rating** | **C** | Las 2 vulnerabilidades de impacto medio bajan la nota; 0 *Security Hotspots*. C no significa que el sistema sea poco seguro: el análisis no ve los defectos de autenticación y autorización |

Otras métricas registradas (`metricas.json`): 3.242 líneas de código (CSS 18 · JavaScript 2.718 · PHP 506), 3.594 líneas, 48 archivos, 196 funciones, 11 clases,
complejidad ciclomática 333 y cognitiva 128, 124 pruebas importadas, 27 fallidas, 0 errores, 0 omitidas, tasa de éxito 78,2 % y tiempo de ejecución 3.825 ms.

## 3. Quality Gate: lo que mide y lo que no

El gate «Sonar way» del servidor define **cuatro condiciones sobre el código nuevo** (`capturas/SQ-02_quality-gate-sonar-way-condiciones.png`):
0 incidencias nuevas, hotspots nuevos revisados, cobertura ≥ 80 % y duplicación ≤ 3 %.

- **Comprobado:** la API de estado del proyecto (`quality-gate.json`) devolvió **una sola condición evaluada**, `new_violations` con valor 0 (OK). La pestaña *New Code* muestra
  «New issues 0 · Required = 0», cobertura «There are not enough lines to compute coverage» y duplicación «Not computed» (`capturas/SQ-03_resumen-new-code.png`).
- **Interpretación:** las otras tres condiciones no tuvieron valor que evaluar sobre el código nuevo; por tanto no se puede afirmar que «aprobaran», solo que no se evaluaron.
  Si se hubiera medido la cobertura del código nuevo, la condición de 80 % podría haberse evaluado: el resultado «Aprobado» depende de que no hubiera código nuevo.
- **No se modificó ninguna condición del gate.**
- **El «código nuevo» se mide desde la versión anterior** (`PREVIOUS_VERSION`, referencia 2026-10-28T02:37:01Z). Como el código no cambió entre los tres análisis, no hay incidencias nuevas aunque
  existan 11 incidencias globales.

**Conclusión:** «Quality Gate aprobado» **no equivale** a «sin vulnerabilidades» ni a «pruebas aprobadas». En este proyecto coexisten el gate aprobado, 2 vulnerabilidades, 9 incidencias de
accesibilidad, 27 pruebas fallidas y cobertura no medida.

## 4. Cobertura: por qué el 0,0 % del panel no es una medición

- `capturas/SQ-07_cobertura-0-automatico.png` muestra 0,0 % para **todos** los archivos, incluso los que PHPUnit sí ejecuta (por ejemplo `backend/routes/api.php`, con 11 líneas «sin cubrir»).
  Eso es incompatible con una cobertura real, porque 124 pruebas recorren esa ruta.
- El log del escáner indica `No PHPUnit coverage reports provided` y el equipo no tiene Xdebug ni PCOV (`../automatizadas/cobertura-no-disponible.txt`).
- Por tanto el informe declara la cobertura como **no medida**. La guía solo pide el porcentaje «si está disponible» y no exige un mínimo.

## 5. Respuestas a las preguntas de 4.3

1. **¿Quality Gate aprobado o fallido?** Aprobado. Ver la sección 3: solo se evaluó una condición (sin incidencias nuevas).
2. **¿Qué problemas de calidad se identificaron?** 11 incidencias, todas de severidad *Medium* y todas en el frontend (JavaScript/JSX): 2 posibles contraseñas fijas y 9 problemas de accesibilidad.
   Ninguna en PHP ni en CSS. Esfuerzo total estimado: 1 h 45 min (`capturas/SQ-08_incidencias.png`).
3. **¿Cuántos *bugs*, vulnerabilidades y *code smells*?** 0, 2 y 9 respectivamente (0 *Security Hotspots*).
4. **¿Qué porcentaje de cobertura?** No disponible: no se midió (ver sección 4).
5. **¿Qué partes del código requieren mayor atención?**

   | Archivo | Incidencias | Motivo |
   |---|---:|---|
   | `frontend/src/pages/DashboardPadre.jsx` | 4 | Etiquetas de formulario sin control asociado (líneas 483, 691, 722, 750) |
   | `frontend/src/services/authService.js` | 2 | Posibles contraseñas fijas (líneas 44 y 46) |
   | `frontend/src/pages/DashboardAuxiliar.jsx` | 2 | Etiquetas sin control asociado (589 y 602) |
   | `frontend/src/components/Modal.jsx` | 2 | Elementos interactivos no nativos (15 y 19) |
   | `frontend/src/components/Sidebar.jsx` | 1 | Elemento interactivo no nativo (22) |

   *Interpretación:* los dos paneles grandes (`DashboardPadre.jsx`, `DashboardAuxiliar.jsx`) concentran 6 de las 11 incidencias. **El backend no tiene ninguna incidencia de Sonar, pero concentra los defectos
   reales de seguridad**: `backend/routes/api.php` declara las rutas sin middleware de autenticación (hay un `TODO` que lo reconoce), y `LoginRequest.php` (línea 22) provoca el HTTP 500 de INC-11.
   Sonar no los ve porque Community Build no analiza el flujo de datos de PHP.
6. **¿Qué problemas deberían corregirse primero y por qué?** Primero los de seguridad, aunque Sonar no los marque: **INC-01 e INC-02** (sin autenticación ni autorización: cualquier
   cliente accede a datos de menores) y **INC-16** (aviso crítico de `laravel/framework`, que se vuelve relevante al conectar autenticación real). Después, **INC-12**
   (las contraseñas fijas del frontend: se resuelve eliminando las cuentas de respaldo del paquete). Después, **INC-15** (accesibilidad, de bajo costo: 5 min por incidencia, 45 min las nueve; con las dos de seguridad, 1 h 45 min en total).
   El orden completo, con esfuerzo y beneficio, está en `../PROPUESTAS-DE-MEJORA.md`.
7. **¿Qué relación existe entre las pruebas y el análisis estático?** Son **complementarios y casi disjuntos**: de los 11 hallazgos de Sonar, ninguno coincide con los 16 casos manuales
   fallidos ni con las 27 pruebas fallidas; y ninguna de las 8 incidencias que reproduce PHPUnit fue detectada por Sonar. Solo hay solapamiento temático en las contraseñas de demostración
   (Sonar las ve en el frontend; la auditoría manual las ve también en `DemoAccounts.php`, que Sonar no señaló). Sonar encontró el problema que las pruebas dinámicas no ejercitan
   (accesibilidad); las pruebas encontraron los de comportamiento y seguridad. Tabla comparativa en `RESUMEN-ACADEMICO.md` §6.2 y `../COMPARACION-MANUAL-AUTOMATIZADA.md`.
8. **¿Qué mejoras concretas se proponen?** Lista priorizada y justificada en `../PROPUESTAS-DE-MEJORA.md`. **No se implementó ninguna.**

## 6. Limitaciones de este análisis

1. Cobertura no medida; el 0,0 % del panel no es una medición.
2. Sonar Community Build no incluye análisis de flujo de datos (*taint analysis*) para PHP: no detecta la falta de autenticación, la inyección ni los defectos de autorización.
   Ese análisis pertenece a la edición Developer (el panel lo avisa: «Limited security analysis», `capturas/SQ-01_panel-principal-overall-code.png`).
3. El análisis de dependencias se omitió (`Dependency analysis skipped`); las vulnerabilidades de dependencias provienen de `composer audit` y `npm audit` (`../dependencias/`).
4. Solo se analiza la rama principal y hay tres análisis del mismo proyecto; únicamente el último (`bc4c4544`) conserva datos.
5. El Quality Gate no se ajustó; sus condiciones de cobertura y duplicación no tuvieron valor que evaluar.
6. Las fechas de los archivos JSON están en UTC; las capturas del panel, en hora local (UTC-5).
7. La API de *hotspots* está deprecada: sus hallazgos pasan a ser incidencias de seguridad.

## 7. Capturas

| Captura | Qué demuestra |
|---|---|
| `capturas/SQ-01_panel-principal-overall-code.png` | Panel principal: Quality Gate *Passed*, seguridad C (2), fiabilidad C (9), mantenibilidad A (3), cobertura 0,0 %, duplicación 0,0 %, 0 hotspots |
| `capturas/SQ-02_quality-gate-sonar-way-condiciones.png` | Las cuatro condiciones del gate «Sonar way» (solo lectura) |
| `capturas/SQ-03_resumen-new-code.png` | Código nuevo: 0 incidencias, cobertura sin líneas suficientes, duplicación no calculada |
| `capturas/SQ-04_medidas-del-proyecto.png` | Panel de medidas (gráfico de riesgo) |
| `capturas/SQ-05_fiabilidad-por-archivo.png` | 9 incidencias de fiabilidad por archivo (4, 2, 2, 1), calificación C, 45 min |
| `capturas/SQ-06_seguridad-por-archivo.png` | 2 incidencias de seguridad en `authService.js`, calificación C, 1 h |
| `capturas/SQ-07_cobertura-0-automatico.png` | Cobertura 0,0 % en todos los archivos (automático, no medido) |
| `capturas/SQ-08_incidencias.png` | Lista de 11 incidencias, 1 h 45 min de esfuerzo |
| `capturas/SQ-09_historial-de-analisis.png` | Tres análisis del 2026-10-27 (21:37, 21:46 y 23:06 hora local); el vigente es el de las 23:06, versión `practica-pruebas-calidad` |
