# PHPUnit en F1.1

Fecha: 2026-10-28. Comando: `php vendor/bin/phpunit --testdox` en `backend/`. PHPUnit 11.5.57, PHP 8.2.12. Tiempo ≈ 11 s. Memoria 42 MB.

**No se modificaron expectativas de `RequisitosSeguridad` para ocultar fallos.**

## Totales

| Suite | Pruebas | Resultado |
|---|---:|---|
| Caracterizacion | 90 | OK |
| Persistencia | 12 | OK |
| RequisitosSeguridad | 35 | 9 fallos (los mismos de M0) |
| **Total** | **137** | **128 aprueban, 9 fallan** (465 aserciones) |

Suite Persistencia sola: `12 tests, 34 assertions`, tiempo 1,3 s.

## Persistencia (nuevo)

| Área | Qué comprueba |
|---|---|
| Esquema | `migrate:fresh` crea las tablas de F1.1 |
| Usuarios | Hash bcrypt, mismo DNI en dos roles, rechazo del mismo DNI+rol |
| Relaciones | Tutor↔estudiantes, docente de aula, auxiliar con `user_id` |
| Asistencia | Alta, unicidad por día, justificación ligada a tutor e hijo |
| Prototipo | Login sigue en `MockUserAuthenticator`; un login demo no inserta en `users` |

## Caracterización B-04

Se **adaptó** a un hecho nuevo: ahora existen modelos Eloquent. Sigue afirmando que `AsistenciaController` usa `AsistenciaStore` y `geoeduca-state.json`. No oculta un defecto de seguridad.

## Los nueve fallos conocidos (sin cambio)

| Prueba | Esperado | Obtenido |
|---|---|---|
| `GET /api/auth/me` sin credenciales | 401 | 200 `{user:null}` |
| `GET /api/auth/me` con token inventado | 401 | 200 `{user:null}` |
| Cabecera `X-Content-Type-Options` | `nosniff` | ausente |
| 404 de API con `APP_DEBUG=true` | sin traza | expone `trace`, `file`, `line`, `exception` |
| Validación sin `Accept` | 422 JSON | 302 |
| DNI como arreglo | 401 o 422 | 500 |
| DNI como objeto | 401 o 422 | 500 |
| 12 logins fallidos | algún 429 | 12×401 |
| `/api/auth/me` tras logout | 401 | 200 `{user:null}` |

Ningún fallo nuevo. Trabajo de F1.2–F1.4.

La corrida de integración previa (125 pruebas, sin suite Persistencia) está en [`RESULTADOS-PHPUNIT-INTEGRACION.md`](RESULTADOS-PHPUNIT-INTEGRACION.md).
