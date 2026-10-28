# Registro de adaptación de pruebas

Fecha: 2026-10-28. Base: commit `800e5e23983ce05e03611c46e4fa60e3fee3e080` (`mejoras-interfaces-dinamicas`).

La suite académica se escribió contra el **código base** (9 rutas, autenticación simulada sin validar el token, asistencia como marcador de posición). La rama de Diego cambia ese contrato. **No se fusionaron historiales Git.** Se copió la infraestructura y se adaptó solo la caracterización al comportamiento nuevo.

**No se modificó ninguna expectativa de `RequisitosSeguridad` para hacer pasar un fallo.** Los 9 fallos que quedan son defectos abiertos.

## Ejecuciones

| ID | Qué se corrió | Resultado real |
|---|---|---|
| R0 | Suites sin adaptar, sobre el código de Diego | Caracterización: 89 pruebas, 5 errores, 31 fallos. Seguridad: 35 pruebas, 11 fallos. Causa: constructor de `MockUserAuthenticator`, `child_id` obligatorio, 401/403 en asistencia, inventario de 9 vs 20 rutas |
| R1 | Caracterización adaptada; seguridad intacta | **125 pruebas, 426 aserciones, 9 fallos.** Caracterización: 90 pruebas, todas aprueban. Seguridad: 35 pruebas, 9 fallos. Aislamiento: no queda `storage/app/geoeduca-state.json` en el repositorio; 0 directorios temporales `geoeduca-pruebas-*` |

Salida completa de R1 (rutas de máquina sustituidas): `../02-desarrollo/resultados-phpunit-integracion.txt`. Resumen: `../02-desarrollo/RESULTADOS-PHPUNIT-INTEGRACION.md`.

La evidencia académica de la práctica (124 pruebas del código base) no se sobrescribió: sigue en `automatizadas/`.

## Infraestructura (sin cambiar producción)

| Archivo | Cambio |
|---|---|
| `backend/phpunit.xml` | Nuevo. Entorno aislado: SQLite en memoria, `APP_KEY` falsa, log nulo |
| `backend/composer.json` | Solo `autoload-dev` (`Tests\\` → `tests/`) y `require-dev` PHPUnit ^11.5 + Mockery ^1.6 |
| `backend/composer.lock` | 73 paquetes de producción **idénticos** a Diego; 29 paquetes de desarrollo nuevos |
| `.gitignore` | Superconjunto del de Diego (41 líneas añadidas, 0 eliminadas) |
| `.gitattributes` | Nuevo. Fuerza LF en `*.sh` |

## Caracterización adaptada

Cada cambio está marcado `ADAPTACIÓN` / `ADAPTADO` en el código.

| Archivo | Qué cambió | Por qué | ¿Se ocultó un fallo? |
|---|---|---|---|
| `tests/TestCase.php` | Cada prueba usa un `storage` temporal y lo borra al terminar | `AsistenciaStore` persiste en JSON; sin esto las pruebas se contaminan y el JSON podría entrar al repo | No |
| `tests/Support/Cargas.php` | `justificacion()` incluye `child_id => 1` | `JustificacionRequest` de Diego lo exige | No: las pruebas de validación vacía ahora esperan también `child_id` |
| `tests/Caracterizacion/Unit/MockUserAuthenticatorTest.php` | El autenticador se construye con `AsistenciaStore` | El constructor de Diego lo requiere | No |
| `tests/Caracterizacion/Feature/AsistenciaContratoTest.php` | Exige sesión del rol correcto; la respuesta es `panel`; la justificación reaparece después | El contrato dejó de ser un eco / marcador | No: se fotografía el contrato **nuevo** |
| `tests/Caracterizacion/Feature/AsistenciaValidacionTest.php` | Formulario vacío espera `child_id`, `type`, `event_date`, `reason` | El request ahora valida `child_id` | No |
| `tests/Caracterizacion/Feature/ComportamientoActualInseguroTest.php` | Asistencia 401/403; logout con token invalida asistencia; `/me` sigue en 200; AUTO-01 sigue en 500; S-08 sigue en 12×401 | Diego mitigó parte de los defectos en el controlador, no con middleware | No: lo mitigado se marca `AHORA`; lo abierto, `HOY` |
| `tests/Caracterizacion/Feature/ContratoRutasTest.php` | 20 rutas; notificaciones presentes; `app/Notifications` sigue sin existir | Diego añadió 11 rutas, entre ellas avisos en pantalla | No |

## Requisitos de seguridad (sin adaptar)

Siguen fallando, y deben seguir fallando hasta F1:

1. `GET /api/auth/me` sin token → 200 `{user:null}` (debería 401)
2. `GET /api/auth/me` con token inventado → 200 `{user:null}` (debería 401)
3. Falta cabecera `X-Content-Type-Options: nosniff`
4. Con `APP_DEBUG=true` (valor de `.env.example`) un 404 expone traza
5. Validación sin `Accept` redirige 302 en vez de 422 JSON
6. DNI enviado como arreglo → HTTP 500 (AUTO-01)
7. DNI enviado como objeto → HTTP 500
8. 12 logins fallidos → 12×401, nunca 429
9. Tras `logout`, `/api/auth/me` → 200 `{user:null}` (debería 401)

Dos fallos de R0 **dejaron de serlo** porque Diego ya autentica asistencia y responde 403 por rol. Eso es un cambio real del producto, no un ajuste de la prueba.

## Qué no se tocó

- `backend/app`, `backend/routes`, `backend/config`, `backend/bootstrap`, `frontend/src`
- Ninguna aserción de `tests/Seguridad/*`
- Resultados académicos de `automatizadas/`
