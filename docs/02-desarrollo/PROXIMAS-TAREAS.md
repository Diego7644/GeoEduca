# Próximas tareas

## Hecho: F1.1 – Esquema

En `main` (`6678249`, PR #3). PostgreSQL, modelos, seeders de desarrollo, suite Persistencia.

## Hecho: F1.2 – Login real (esta rama, local)

Rama `feature/f1-autenticacion`, **sin push**.

- `EloquentUserAuthenticator` + `Hash::check`. Sin fallback a `DemoAccounts`.
- Tokens Sanctum con caducidad; `logout` revoca; `/me` → 401 sin token.
- `auth:sanctum` en asistencia; throttle en login; `nosniff`; DNI no textual → 422.
- El JSON ya no guarda sesiones nuevas. Los paneles de asistencia sí siguen en JSON.
- Frontend: se retiró el mock `VITE_USE_MOCK_AUTH`. El contrato `identifier`/`password`/`role` no cambió.

Detalle: [`AUTENTICACION.md`](AUTENTICACION.md), [`RESULTADOS-PHPUNIT-F1.2.md`](RESULTADOS-PHPUNIT-F1.2.md).

## Ahora: F1.3 / restante de F1.4

Gran parte de F1.3 (middleware Sanctum) y F1.4 (throttle, DNI, `nosniff`, `APP_DEBUG`) quedó en F1.2. Falta:

- Recuperación: código hasheado, caducidad, un solo uso, sin devolverlo.
- `POST /api/auth/acceso`: no emitir sesión solo con DNI.
- Política tutor→hijo sobre datos reales (hoy el JSON y `guard()` cubren el rol).
- Dejar de usar `AsistenciaStore` para el historial cuando exista M4.

## F1.5 – Frontend

- Cada carga protegida consulta `/api/auth/me` (hoy confía en `localStorage`).
- Mensaje claro si el backend no responde (UI-12).
