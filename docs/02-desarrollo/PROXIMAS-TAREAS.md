# Próximas tareas

## Hecho: F1.1 – Esquema

En `main` (`6678249`, PR #3). PostgreSQL, modelos, seeders de desarrollo, suite Persistencia.

## Hecho: F1.2 – Login real

En `main` (`3ea06ad`, PR #4). Sanctum, `Hash::check`, `/me` 401, throttle de login, sin mock en el frontend.

## Hecho: F1.3 – Accesos y recuperación (esta rama, local)

Rama `feature/f1-seguridad-accesos`, **sin push**.

- `POST /api/auth/acceso` ya no emite sesión: solo personal autenticado identifica un DNI.
- Recuperación en `password_reset_codes` con hash, caducidad, un solo uso y límite de intentos.
- Middleware `role:` sobre el usuario del token.
- El JSON sigue para asistencia; ya no guarda códigos ni contraseñas nuevas.

Detalle: [`AUTENTICACION.md`](AUTENTICACION.md), [`RECUPERACION.md`](RECUPERACION.md), [`PERMISOS.md`](PERMISOS.md), [`RESULTADOS-PHPUNIT-F1.3.md`](RESULTADOS-PHPUNIT-F1.3.md).

## Ahora: F1.5 / M4

- Cada carga protegida consulta `/api/auth/me` (hoy el frontend aún confía en `localStorage`).
- Mensaje claro si el backend no responde (UI-12).
- Migrar asistencia fuera del JSON cuando se autorice.

## Más adelante

- Canal real de entrega del código de recuperación (correo o SMS). Hasta entonces producción no puede completar el flujo.
- Separar docente y auxiliar si el producto lo exige. Panel de administración (D6) sigue fuera de alcance.
- Cookie HttpOnly de Sanctum cuando frontend y backend compartan dominio (D7).
