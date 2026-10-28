# Comparación entre pruebas manuales y automatizadas – GEOEDUCA

Corresponde al apartado **3.4** de la guía. Cada afirmación se apoya en un archivo del repositorio; las **interpretaciones** se marcan como tales.
Las rutas son relativas a `docs/01-guia-pruebas-calidad/`.

## 1. Funcionalidades verificadas manualmente

50 casos (38 de API y 12 de interfaz), ejecutados con peticiones HTTP reales (`ejecutar-pruebas-api.ps1`) y con el navegador integrado de Cursor
(`api/matriz-resultados-api.md`, `matriz-resultados-ui.md`).

| Tipo de prueba | Casos | Identificadores |
|---|---:|---|
| Funcional (incluye negativo, funcional/integración y funcionalidad no implementada) | 19 | F-01 a F-06, N-01, N-02, B-01 a B-04, UI-01, UI-04, UI-05, UI-07 a UI-10 |
| Validación de datos | 10 | V-01 a V-09, UI-02 |
| Seguridad (incluye la del cliente) | 15 | N-03, I-04, S-01 a S-11, UI-03, UI-06 |
| Integración | 4 | I-01, I-02, I-03, I-05 |
| Usabilidad | 1 | UI-11 |
| Resiliencia | 1 | UI-12 |

Listas calculadas por programa a partir del campo «Tipo» de las matrices (las mismas que usa `PLAN-DE-PRUEBAS.md`).
Nota: N-03 e I-04 están clasificados como «Seguridad» en la matriz, y I-05 como «Integración».

## 2. Funcionalidades verificadas mediante automatización

124 pruebas PHPUnit 11.5.57 en dos suites y 18 clases (`backend/tests/`, `automatizadas/README.md`):

| Suite | Pruebas | Qué verifica |
|---|---:|---|
| `Caracterizacion` | 89 | El comportamiento **actual**, incluidos sus defectos conocidos; incluye pruebas unitarias de `MockUserAuthenticator`, `AuthService` (con Mockery) y `DemoAccounts` |
| `RequisitosSeguridad` | 35 | El comportamiento **seguro esperado**: autenticación, autorización, sesión, límite de intentos, cabeceras, errores y entradas hostiles |

**35 de los 38 casos manuales de API** tienen contrapartida automatizada (`automatizadas/trazabilidad-matriz-manual.csv`). No se automatizaron:
**I-01 e I-02** (necesitan Vite en ejecución), **S-09** (la cabecera `X-Powered-By` la añade el intérprete de PHP, no es observable en una prueba en proceso)
y los **12 casos de interfaz** (requieren pruebas de frontend; Vitest 3.2.7 se descartó por introducir 2 vulnerabilidades críticas).

## 3. Coincidencias o diferencias entre los resultados

Se comparó, por programa, el resultado manual de cada caso con el de su prueba automatizada (`automatizadas/trazabilidad-matriz-manual.csv`):

| Resultado manual | Resultado automatizado | Casos | ¿Coinciden? |
|---|---|---:|---|
| Aprobado | La prueba de caracterización o de requisito aprueba | 21 | Sí |
| Fallido | El requisito de seguridad falla | 10 | Sí (I-05, S-01 a S-06, S-08, S-10, S-11) |
| Bloqueado | La caracterización de la **ausencia** de la funcionalidad aprueba | 4 | Sí (B-01 a B-04) |
| **Total comparable** | | **35** | **35 de 35, sin discrepancias** |

La única diferencia está en lo que cada modalidad **llegó a ver**, no en lo que ambas vieron: los 11 fallos manuales de API se reproducen automáticamente salvo S-09
(10 de 11), y la automatización añadió un defecto que la batería manual no cubrió (AUTO-01).

## 4. Defectos detectados por cada modalidad

| Defecto | Manual / API | Interfaz manual | PHPUnit | SonarQube |
|---|:---:|:---:|:---:|:---:|
| INC-01 Sin autenticación (S-01 a S-04) | Sí | — | Sí (12 fallos) | No |
| INC-02 Sin autorización por rol | Sí | Sí (UI-06) | Sí (6) | No |
| INC-03 Token vigente tras cerrar sesión | Sí | — | Sí (3) | No |
| INC-04 Sin límite de intentos | Sí | — | Sí (1) | No |
| INC-07 Traza con `APP_DEBUG=true` | Sí | — | Sí (1) | No |
| INC-09 Cabeceras de seguridad ausentes | Sí (S-09 y S-10) | — | Solo S-10 (1) | No |
| INC-10 `302` en vez de `422` sin `Accept` | Sí | — | Sí (1) | No |
| **INC-11 HTTP 500 con DNI como arreglo (AUTO-01)** | **No** | — | **Sí (2)** | No |
| INC-05 QR no dinámico, INC-08 fecha fija | — | Sí (UI-08, UI-07) | Parcial (solo describe la ausencia) | No |
| INC-13 Contenido recortado en móvil, INC-14 mensaje técnico | — | Sí (UI-11, UI-12) | No | No |
| INC-12 Contraseñas fijas en el frontend | Auditoría estática | — | No | **Sí (2)** |
| INC-15 Accesibilidad de formularios y componentes | No | No | No | **Sí (9)** |

Resumen: manual y de interfaz, **16 casos fallidos**; PHPUnit, **27 pruebas fallidas** (que cubren 8 incidencias); SonarQube, **11 incidencias**.
Cada técnica encontró algo que las otras no vieron: la automatización, INC-11; la interfaz manual, INC-05, INC-08, INC-13 e INC-14; el análisis estático, INC-12 e INC-15.
Detalle en `REGISTRO-DE-INCIDENCIAS.md`.

## 5. Ventajas y limitaciones observadas

| | Pruebas manuales y de API | Pruebas automatizadas (PHPUnit) |
|---|---|---|
| **Ventajas** | Ven lo que el usuario ve: fecha fija, QR que no cambia, contenido recortado en móvil, mensaje técnico con el backend caído. Permiten explorar sin escribir código. Cubren la interfaz y la unión entre procesos (Vite y Laravel) | Se repiten en segundos y de forma idéntica: la suite completa tarda 2,98 s (suma de JUnit) y dio los mismos 27 fallos en una segunda ejecución. Fijan cada requisito como una prueba que cambia a *aprobada* cuando se corrige el defecto. Probaron combinaciones hostiles de entrada que no se habían pensado (AUTO-01). Los grupos `#[Group('S-05')]` trazan cada prueba con su caso manual |
| **Limitaciones** | Cada repetición exige tiempo humano; el resultado depende de quien ejecuta. La interfaz no se repite igual dos veces (por ejemplo, sesiones y cronómetros). No se cronometró el esfuerzo manual, por lo que no se puede dar una cifra de ahorro | Solo ven lo que el código en proceso expone: no vieron `X-Powered-By` (S-09) ni el proxy de Vite (I-01, I-02). No cubren la interfaz: Vitest no se pudo usar. No miden cobertura (sin Xdebug ni PCOV). Una prueba de caracterización con el 100 % de aprobación **no demuestra que el sistema sea correcto**: solo que describe fielmente su comportamiento actual |

*Interpretación:* las dos modalidades son complementarias y ninguna sustituye a la otra. La automatización es la herramienta adecuada para fijar y repetir los requisitos de seguridad
de la API; la inspección manual sigue siendo necesaria para la interfaz.

## 6. Posibilidades de ampliar la automatización

*Propuestas; ninguna se implementó.*

1. **Pruebas de frontend** con Vitest y Testing Library sobre `authService`, el formulario de login y los paneles, **tras** decidir la actualización a Vite 6, que además cierra
   vulnerabilidades de `npm audit` (INC-16). Cubriría UI-02 a UI-05 y UI-08.
2. **Pruebas de extremo a extremo** con Playwright (UI-01, UI-09, UI-10, UI-11 con viewport móvil y UI-12 con el servidor detenido).
3. **Cobertura**: instalar PCOV o Xdebug, generar un informe Clover e importarlo en SonarQube, para sustituir el «0,0 % no medido» por un valor real.
4. **Pruebas de accesibilidad** (por ejemplo con `jest-axe`) que repliquen las 9 incidencias de Sonar y eviten su reaparición.
5. **Integración continua**: ejecutar PHPUnit, el análisis de Sonar y `composer audit` / `npm audit` en cada cambio.
6. **Pruebas de contrato y de persistencia** cuando se conecte la base de datos (hoy B-04 solo caracteriza la ausencia).
7. Un caso para **S-09** con una prueba contra el servidor real (`php artisan serve`), no en proceso, o configurando `expose_php=Off`.

## 7. Evidencias

| Requisito de la guía | Dónde está |
|---|---|
| Código de las pruebas | `backend/tests/` (18 clases y 4 de apoyo) y `backend/phpunit.xml`, publicados en el commit `b326574` |
| Comandos de ejecución | `automatizadas/README.md` («Cómo reproducir») |
| Reporte de resultados | `automatizadas/junit-*.xml`, `testdox-*.txt`, `salida-*.txt`, `resultados-por-prueba.csv`; segunda ejecución en `automatizadas/ejecucion-2026-10-28/` |
| Capturas de la ejecución | **Pendiente de intervención manual** (ver `INFORME-TECNICO-FINAL.md`, anexo C) |
| Análisis comparativo | Este documento |
