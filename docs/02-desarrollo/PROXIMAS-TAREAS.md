# Próximas tareas

La primera etapa funcional es **F1: persistencia de datos, autenticación y autorización**.

## Hecho: F1.1 – Esquema (esta rama)

Rama `feature/f1-persistencia`, sin push ni fusión a `main`.

- Migraciones PostgreSQL: `users`, `staff_members`, `students`, `guardian_student`, `school_settings`, `attendance_records`, `justifications`, `qr_tokens`, `in_app_notifications`, `password_reset_codes`, `audit_logs`, `personal_access_tokens`.
- Modelos Eloquent y relaciones. Factories y seeder **solo de desarrollo** (`GEO_DEV_PASSWORD`), sin copiar `DemoAccounts`.
- SQLite en memoria para PHPUnit; PostgreSQL 16 en desarrollo (Docker).
- Pruebas: `migrate:fresh` consistente; DNI duplicado por rol rechazado; login del prototipo no escribe en `users`.
- El JSON `geoeduca-state.json` **no se eliminó**. La API no usa las tablas nuevas.

Detalle: [`MODELO-DE-DATOS.md`](MODELO-DE-DATOS.md), [`CONFIGURACION-POSTGRES.md`](CONFIGURACION-POSTGRES.md), [`RESULTADOS-PHPUNIT-F1.1.md`](RESULTADOS-PHPUNIT-F1.1.md).

## Ahora: F1.2 – Login real

- `AuthService` contra `users`. Retirar `MockUserAuthenticator` del camino por defecto.
- Contraseñas solo con hash. Tokens Sanctum con expiración. `logout` revoca el token actual.
- `/api/auth/me` sin token o con token inválido/revocado → `401`.
- No devolver `demo_code` ni guardar la contraseña nueva en texto plano.
- Mantener el contrato de login que el frontend ya usa (`identifier`, `password`, `role`).
- No borrar el JSON hasta que las sesiones dejen de vivir ahí (F1.3).

## F1.3 – Autorización

- `auth:sanctum` en todas las rutas salvo `health` y `login`.
- Middleware de rol. Política tutor→hijo en justificación y consultas del padre.
- Dejar de usar `AsistenciaStore` para sesiones.

## F1.4 – Endurecimiento

- `throttle` en login.
- `LoginRequest`: el DNI no se fuerza a `(string)`; arreglo u objeto → 422.
- `APP_DEBUG=false` en `.env.example`. Cabeceras de seguridad (`nosniff` como mínimo).
- Recuperación: código hasheado, caducidad, un solo uso, sin devolverlo.

## F1.5 – Frontend

- Retirar cuentas de respaldo con contraseñas del código.
- Cada carga protegida consulta `/api/auth/me`.
- Mensaje claro si el backend no responde (hoy UI-12 muestra un error técnico).

## Hecho en la integración (no repetir)

- Copia de infraestructura PHPUnit sobre el código de Diego, sin tocar producción.
- Adaptación de caracterización.
- Guía académica pública redactada.
- Diagnóstico y plan de F1.
- Publicación en `main` (`4992279`) vía PRs #1 y #2.
