# Próximas tareas

## Hecho: F1.1 – Esquema

En `main` (`6678249`, PR #3). PostgreSQL, modelos, seeders de desarrollo, suite Persistencia.

## Hecho: F1.2 – Login real

En `main` (`3ea06ad`, PR #4). Sanctum, `Hash::check`, `/me` 401, throttle de login, sin mock en el frontend.

## Hecho: F1.3 – Accesos y recuperación

En `main` (`c50a911`, PR #5).

- `POST /api/auth/acceso` ya no emite sesión: solo personal autenticado identifica un DNI.
- Recuperación en `password_reset_codes` con hash, caducidad, un solo uso y límite de intentos.
- Middleware `role:` sobre el usuario del token.
- El JSON sigue para asistencia; ya no guarda códigos ni contraseñas nuevas.

Detalle: [`AUTENTICACION.md`](AUTENTICACION.md), [`RECUPERACION.md`](RECUPERACION.md), [`PERMISOS.md`](PERMISOS.md), [`RESULTADOS-PHPUNIT-F1.3.md`](RESULTADOS-PHPUNIT-F1.3.md).

## Hecho: F1.4 – Sesión del frontend (esta rama, local)

Rama `feature/f1-sesiones-frontend`, **sin push**.

- Cada arranque confirma la sesión con `GET /api/auth/me`.
- Un 401, un token expirado o un rol guardado distinto no abren el panel.
- Logout revoca el PAT. Una respuesta tardía de `/me` no restaura la sesión.
- La recuperación en pantalla no simula correo ni SMS.

Detalle: [`SESIONES.md`](SESIONES.md), [`RESULTADOS-PRUEBAS-F1.4.md`](RESULTADOS-PRUEBAS-F1.4.md).

## Ahora: asistencia real

- Sacar el historial de `AsistenciaStore` cuando se autorice.
- No usar el id de `users` como id del catálogo JSON (el usuario local 1 cae en el estudiante de demostración).
- Cookie HttpOnly de Sanctum cuando frontend y API compartan dominio.

## Más adelante

- Canal real de entrega del código de recuperación (correo o SMS). Hasta entonces producción no puede completar el flujo.
- Separar docente y auxiliar si el producto lo exige. Panel de administración (D6) sigue fuera de alcance.
- Cookie HttpOnly de Sanctum cuando frontend y backend compartan dominio (D7).
