# PHPUnit en F1.2

Fecha: 2026-10-28. Comando: `php vendor/bin/phpunit` en `backend/`. PHPUnit 11.5.57, PHP 8.2.12. **148 pruebas, 505 aserciones, 0 fallos.**

No se modificaron las expectativas de `RequisitosSeguridad` para ocultar defectos: el código pasó a cumplirlas. La caracterización se adaptó al comportamiento nuevo (login Sanctum, 401 en `/me`, throttle, DNI arreglo → 422).

## Totales

| Suite | Resultado |
|---|---|
| Caracterizacion | OK |
| Persistencia | OK |
| Autenticacion | OK (nueva) |
| RequisitosSeguridad | OK (los 9 fallos de M0/F1.1 ya no aplican) |
| **Total** | **148 OK** |

## Los nueve fallos anteriores

| Antes | Ahora |
|---|---|
| `/api/auth/me` sin credenciales → 200 `{user:null}` | **401** |
| `/api/auth/me` con token inventado → 200 | **401** |
| Sin cabecera `nosniff` | **`X-Content-Type-Options: nosniff`** |
| 404 con `APP_DEBUG` de `.env.example` filtraba traza | **`.env.example` tiene `APP_DEBUG=false`; 404 limpio** |
| Validación sin `Accept` → 302 | **422 JSON** (`ForceJsonResponse` en el grupo api) |
| DNI como arreglo → 500 | **422** (`string` + `bail`) |
| DNI como objeto → 500 | **422** |
| 12 logins fallidos → 12×401 | **429** (`throttle:login`, 5/min) |
| `/me` tras logout → 200 | **401** (PAT borrado + `Auth::forgetGuards`) |

Pendientes de producto (no son esos 9): login por carnet sin contraseña (`POST /api/auth/acceso`); recuperación con código en claro en el JSON; asistencia aún en JSON.

## Adaptaciones de caracterización (no se ocultaron fallos)

- Token Sanctum `{id}|…` en lugar de `mock-`.
- Binding `EloquentUserAuthenticator`.
- Validación de asistencia con sesión (el 401 va antes que el 422 si no hay token).
- Middleware `ThrottleRequests` y `Authenticate` presentes.
