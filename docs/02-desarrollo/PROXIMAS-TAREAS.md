# Próximas tareas

La primera etapa funcional es **F1: persistencia de datos, autenticación y autorización**. No empieza el código de F1 hasta que M0 esté publicado (commit + PR autorizados).

## Ahora (M0, espera autorización)

1. Revisar esta carpeta y `../01-guia-pruebas-calidad/AUDITORIA-DE-PRIVACIDAD.md`.
2. Autorizar tres commits locales según `GRUPOS-DE-COMMIT.md`.
3. Resolver D2 (fork o escritura) y abrir PR contra `mejoras-interfaces-dinamicas`. **No fusionar con `main`.**
4. Decidir D5 (motor de base de datos) antes de escribir migraciones.

## F1.1 – Esquema

- Crear migraciones: `users` (DNI único por rol, rol, hash de contraseña), `personal_access_tokens`.
- Modelos y relaciones mínimas. Seeder **solo de desarrollo**, sin copiar las cuentas de demostración actuales al código de producción.
- SQLite en memoria para PHPUnit; el motor de desarrollo según D5.
- Pruebas: `migrate:fresh` deja un esquema consistente; DNI duplicado por rol se rechaza.

## F1.2 – Login real

- `AuthService` contra la base. Retirar `MockUserAuthenticator` del camino por defecto.
- Tokens Sanctum con expiración. `logout` revoca el token actual.
- `/api/auth/me` sin token o con token inválido/revocado → `401`.
- No devolver `demo_code` ni guardar la contraseña nueva en texto plano.

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

## Hecho en esta integración (no repetir)

- Copia de infraestructura PHPUnit sobre el código de Diego, sin tocar producción.
- Adaptación de caracterización.
- Guía académica pública redactada.
- Diagnóstico y plan de F1.
