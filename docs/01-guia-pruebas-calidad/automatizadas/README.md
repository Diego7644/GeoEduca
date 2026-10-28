# Pruebas automatizadas PHPUnit – GEOEDUCA

Suite automatizada de la práctica de Pruebas y Calidad de Software. Ejecución original: 2026-10-27; segunda ejecución de verificación: 2026-10-28 (ver «Segunda ejecución»).

- Rama `practica-pruebas-calidad`. Las pruebas están **commiteadas y publicadas** en el commit `b326574` (`test: automatizar pruebas PHPUnit y documentar defectos`).
- Entorno: PHP 8.2.12, PHPUnit 11.5.57, Laravel 11.57.0, Windows (compilación 10.0.26200).
- Alcance: solo backend (Laravel). No se modificó `backend/app/`, `backend/routes/` ni `frontend/src/`.
- No se instalaron paquetes nuevos. Cobertura: **no disponible** (ver más abajo).

## Resultado

| Suite | Pruebas | Aserciones | Aprobadas | Fallidas | Errores | Omitidas | Duración PHPUnit |
|---|---:|---:|---:|---:|---:|---:|---:|
| `Caracterizacion` | 89 | 305 | **89** | 0 | 0 | 0 | 2,05 s |
| `RequisitosSeguridad` | 35 | 74 | 8 | **27** | 0 | 0 | 0,93 s |
| **Total** | **124** | 379 | 97 | 27 | 0 | 0 | 2,98 s |

Las 66 funciones de prueba generan 124 casos al contar cada dataset de un *data provider* por separado.
No hay pruebas omitidas, incompletas (`todo`) ni con aserciones triviales: todo fallo es real.
La duración de la tabla es la suma de tiempos de JUnit; la línea `Time:` de PHPUnit marcó 2,110 s y 0,963 s (incluye el arranque).

**Porcentaje de aprobación** (aprobadas / ejecutadas × 100; omitidas: 0):

| | Aprobadas / ejecutadas | Aprobación |
|---|---:|---:|
| `Caracterizacion` | 89 / 89 | **100 %** |
| `RequisitosSeguridad` | 8 / 35 | **22,9 %** |
| **Total** | **97 / 124** | **78,2 %** (coincide con la «tasa de éxito» que importó SonarQube) |

Interpretación: el 78,2 % global **no es una medida de calidad del sistema**. Mezcla una suite que describe lo que el sistema hace hoy (100 %) con otra que mide los requisitos seguros (22,9 %).
La cifra que refleja el estado frente a los requisitos es la de `RequisitosSeguridad`. Cálculos y comparación de ejecuciones en `resumen-comparativo-ejecuciones.json`.

## Segunda ejecución (2026-10-28)

Se volvieron a ejecutar las dos suites, **sin modificar ninguna prueba ni el código**, para tener un registro fechado adicional. Comandos, fecha y salida completos en
`ejecucion-2026-10-28/` (`ejecucion-2026-10-28/salida-caracterizacion.txt` y `ejecucion-2026-10-28/salida-requisitosseguridad.txt`, con cabecera de fecha, comando y código de salida).

| Suite | Resultado | `Time:` (2.ª ejecución) | `Time:` (original) |
|---|---|---:|---:|
| `Caracterizacion` | `OK (89 tests, 305 assertions)`, código 0 | 3,167 s | 2,110 s |
| `RequisitosSeguridad` | `Tests: 35, Assertions: 74, Failures: 27`, código 1 | 1,749 s | 0,963 s |

Los resultados son **idénticos** (mismas cantidades y los mismos 27 nombres de prueba fallida: 0 diferencias); solo cambió el tiempo. Se conservan ambos registros; el original sigue en los archivos
de esta carpeta y la segunda ejecución en `ejecucion-2026-10-28/`. La ejecución no dejó cambios persistentes (SQLite en memoria; `storage/` y `database/` sin variación).
Las **capturas de pantalla de la terminal** no se pudieron generar con las herramientas disponibles y quedan como tarea manual (ver `INFORME-TECNICO-FINAL.md`, anexo C).

## Cómo se distinguen los dos tipos de prueba

| | `Caracterizacion` | `RequisitosSeguridad` |
|---|---|---|
| Qué comprueba | Lo que GEOEDUCA hace **hoy** | Lo que un sistema **seguro** debe hacer |
| Resultado esperado hoy | Todo aprueba, **incluidos los defectos conocidos** (por ejemplo `/api/auth/me` responde 200 sin token) | Aprueba lo que se cumple y **falla lo que no** |
| Si se corrige un defecto | La prueba de caracterización del defecto debe invertirse o eliminarse | La prueba de requisito pasa a aprobar |
| Grupos | `defecto-conocido` marca las que fotografían un defecto | `requisito-incumplido` y `requisito-cumplido` |

Que `Caracterizacion` esté al 100 % **no significa que el sistema sea correcto**: significa que las pruebas describen fielmente su
comportamiento actual, defectos incluidos. La medida de calidad respecto de los requisitos es la suite `RequisitosSeguridad`.

## Cómo reproducir

```powershell
cd backend
composer dump-autoload                                   # solo la primera vez (mapeo Tests\ en composer.json)
php vendor/bin/phpunit --testsuite Caracterizacion       # debe aprobar 89/89
php vendor/bin/phpunit --testsuite RequisitosSeguridad   # hoy: 27 fallos de 35
php vendor/bin/phpunit --group S-05                      # una prueba por ID de la matriz manual
php vendor/bin/phpunit --group requisito-incumplido      # solo los requisitos que fallan
php vendor/bin/phpunit --testsuite Caracterizacion --log-junit junit.xml --testdox-text testdox.txt
```

Configuración aislada en `backend/phpunit.xml`: `APP_ENV=testing`, base de datos SQLite `:memory:` (no se usa), sesiones y caché en
memoria, log nulo y una `APP_KEY` falsa solo para pruebas. No se leen datos ni credenciales del `.env` de desarrollo.
`php artisan test` no está disponible (no está instalado `nunomaduro/collision`); se usa `vendor/bin/phpunit`.

## Archivos de pruebas (18 clases de prueba + 4 de apoyo)

| Ruta | Contenido |
|---|---|
| `backend/phpunit.xml` | Suites, entorno aislado y origen de cobertura (`app/`) |
| `backend/tests/TestCase.php` | Base que arranca la aplicación real |
| `backend/tests/Support/{DemoCredentials,Cargas,IniciaSesion}.php` | Cuentas de demostración leídas de `DemoAccounts`, cargas ficticias y login auxiliar |
| `tests/Caracterizacion/Feature/SaludTest.php` | Salud y raíz (F-01) |
| `tests/Caracterizacion/Feature/LoginPerfilesTest.php` | Login de los 3 perfiles (F-02 a F-04) |
| `tests/Caracterizacion/Feature/LoginRechazoTest.php` | Rechazos y no enumeración (N-01 a N-03) |
| `tests/Caracterizacion/Feature/LoginValidacionTest.php` | Validación del login (V-01 a V-06) |
| `tests/Caracterizacion/Feature/AsistenciaContratoTest.php` | Contratos de justificación/aprobación y marcadores (F-05, F-06, B-04) |
| `tests/Caracterizacion/Feature/AsistenciaValidacionTest.php` | Validación de justificación/aprobación (V-07 a V-09) |
| `tests/Caracterizacion/Feature/ContratoRutasTest.php` | Inventario de rutas; ausencia de QR/geo/notificaciones (B-01 a B-03) |
| `tests/Caracterizacion/Feature/CorsTest.php` | CORS (I-03, I-04) |
| `tests/Caracterizacion/Feature/ComportamientoActualInseguroTest.php` | Fotografía de los defectos conocidos (S-01 a S-08, S-11, AUTO-01) |
| `tests/Caracterizacion/Unit/{MockUserAuthenticator,AuthService,DemoAccounts}Test.php` | Unidad, con Mockery en `AuthService` |
| `tests/Seguridad/AutenticacionRequeridaTest.php` | Requisito: autenticación (S-01 a S-04) |
| `tests/Seguridad/AutorizacionPorRolTest.php` | Requisito: autorización por rol (S-05) |
| `tests/Seguridad/SesionYLogoutTest.php` | Requisito: token inválido tras logout (S-06) |
| `tests/Seguridad/LimiteIntentosLoginTest.php` | Requisito: límite de intentos (S-08) |
| `tests/Seguridad/EndurecimientoHttpTest.php` | Requisitos: `nosniff`, errores sin traza, sin `Accept` (S-10, S-11, I-05) |
| `tests/Seguridad/EntradasHostilesTest.php` | Requisito: entradas hostiles sin 500 (S-07, AUTO-01) |

Cambios de configuración fuera de `tests/`: `backend/phpunit.xml` (nuevo) y `backend/composer.json` (solo el bloque `autoload-dev`, que mapea
`Tests\` a `tests/`; no cambia dependencias ni el `content-hash` del lock).

## Defectos reproducidos automáticamente

| Defecto (informe manual) | Requisito que lo reproduce | Fallos | Mensaje real |
|---|---|---:|---|
| INC-01 Sin autenticación en `/api/auth/me` y `/api/asistencia/*` | `AutenticacionRequeridaTest` (sin credenciales y token inventado, 6 rutas cada una) | 12 | `Expected response status code [401] but received 200.` |
| INC-02 Sin autorización por rol | `AutorizacionPorRolTest` (estudiante y padre en rutas ajenas) | 6 | `Expected response status code [403] but received 200.` |
| INC-03 El token no se invalida al cerrar sesión | `SesionYLogoutTest` | 3 | `received 200` y `user=null` con token válido |
| INC-04 Sin límite de intentos de login | `LimiteIntentosLoginTest` | 1 | `Estados obtenidos: 401, 401, … (12 veces)` |
| INC-07 `APP_DEBUG=true` por defecto expone traza | `EndurecimientoHttpTest` (S-11) | 1 | `expone: trace, file, line, exception` |
| INC-09 Sin cabecera `nosniff` | `EndurecimientoHttpTest` (S-10) | 1 | `Header [X-Content-Type-Options] not present` |
| INC-10 302 en lugar de 422 sin `Accept` | `EndurecimientoHttpTest` (I-05) | 1 | `Expected 422 but received 302.` |
| **AUTO-01 (nuevo)** DNI como arreglo u objeto JSON provoca HTTP 500 | `EntradasHostilesTest` | 2 | `respondió 500` |

**AUTO-01 es un defecto nuevo** que la batería manual no cubrió: `LoginRequest::prepareForValidation()` (`app/Http/Requests/Auth/LoginRequest.php`
línea 22) hace `(string) $this->input('identifier')`; si llega un arreglo u objeto JSON se produce `ErrorException: Array to string conversion`
y la API responde 500 en lugar de 422. No filtra datos, pero es un error del servidor provocable por cualquier cliente con una sola petición.
Gravedad propuesta: **Media**.

Aprueban 8 requisitos: las 8 entradas hostiles de S-07 (inyección SQL en DNI, contraseña y perfil; script HTML; contraseña y perfil como arreglo)
se rechazan con 401 o 422, sin token ni detalles internos.

## Relación con la matriz manual

Detalle completo en `trazabilidad-matriz-manual.csv` (calculado a partir de los atributos `#[Group]` y del JUnit, no escrito a mano).
Un método con varios grupos cuenta en cada ID, así que los totales por ID **no son sumables entre filas**.

- Coincide con la matriz manual en todos los IDs automatizados: cada caso manual «Aprobado» tiene su prueba aprobando y cada «Fallido»
  automatizable tiene su requisito fallando.
- **Sin prueba automatizada (3):**
  - **I-01 e I-02** (proxy de Vite): necesitan Vite en ejecución; la parte de la API (contrato del login) la cubre F-02.
  - **S-09** (`X-Powered-By`): la cabecera la añade el intérprete de PHP (`expose_php=1` en `php.ini`), no Laravel, y no es observable en una
    prueba en proceso. Se mantiene como caso manual.
- B-01 a B-04 son pruebas de **caracterización de la ausencia** de la funcionalidad: aprueban hoy y deberán actualizarse al implementarla.
- Las 12 pruebas de interfaz (UI-01 a UI-12) no se automatizan aquí: requieren pruebas de frontend, bloqueadas por la decisión pendiente sobre Vitest.

## Cobertura de código

**No disponible.** PHPUnit informa `No code coverage driver available` (ver `cobertura-no-disponible.txt`): el PHP de XAMPP no tiene `xdebug`
ni `pcov`. Generarla exige instalar un driver y activarlo en `php.ini`; no se instaló nada sin autorización. Además, con
`failOnWarning="true"` esa advertencia hace que el comando termine con código 1 aunque todas las pruebas aprueben.

## Seguridad de las evidencias

- Las contraseñas de demostración **no aparecen** en ningún archivo de esta carpeta. Se leen de `DemoAccounts` en tiempo de ejecución y los
  *data providers* contienen solo los campos que cambian, porque PHPUnit imprime los valores del dataset en la cabecera de un fallo
  (una primera versión de la suite los imprimía; se corrigió en origen y toda la evidencia se regeneró).
- Verificado por búsqueda: 0 contraseñas de demostración, 0 tokens `mock-…`, 0 rutas absolutas, 0 cookies en esta carpeta.
- Las rutas absolutas del equipo se sustituyeron por `<RAIZ_PROYECTO>` (en los XML, `&lt;RAIZ_PROYECTO&gt;`). No altera ningún resultado.

## Archivos de esta carpeta

| Archivo | Contenido |
|---|---|
| `resumen-automatizadas.json` | Totales, aserciones, duraciones, comandos y entorno |
| `junit-caracterizacion.xml`, `junit-seguridad.xml` | Informes JUnit XML |
| `testdox-caracterizacion.txt`, `testdox-seguridad.txt` | Lista legible de pruebas y resultado |
| `salida-caracterizacion.txt`, `salida-seguridad.txt` | Salida completa de PHPUnit |
| `resultados-por-prueba.csv` | Una fila por caso: suite, clase, método, dataset, IDs, estado, tiempo, motivo |
| `trazabilidad-matriz-manual.csv` | Relación con los IDs de la matriz manual |
| `cobertura-no-disponible.txt` | Evidencia de que no hay driver de cobertura |
| `resumen-comparativo-ejecuciones.json` | Porcentajes de aprobación y comparación de las dos ejecuciones |
| `ejecucion-2026-10-28/` | Segunda ejecución: fecha, comando, código de salida y salida completa de cada suite |

## Análisis con SonarQube

Las recomendaciones previas al análisis que figuraban aquí quedaron superadas por los **resultados reales**, que están en ../sonarqube/:

- Configuración y ejecución: el entorno Docker de SonarQube no se publica; se conserva en el archivo académico privado. La entrada de pruebas es `backend/tests` y se importó el informe JUnit **completo** de las dos suites, con sus 27 fallos.
- Resultados e interpretación: ../sonarqube/RESUMEN-ACADEMICO.md y ../sonarqube/INTERPRETACION-METRICAS.md.
- Sonar importó 124 pruebas y 27 fallos, igual que el informe local (../sonarqube/verificacion-pruebas-phpunit.json).
- Cobertura: no medida (ver «Cobertura de código» más arriba).
