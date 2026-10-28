# Recuperación de cuentas (F1.3)

## Dependencia pendiente: no hay canal de entrega

GEOEDUCA **no envía correo, SMS ni push**. La API no simula que el código se mandó. El mensaje de `POST /api/auth/recuperar` es el mismo si la cuenta existe o no:

> Si existe una cuenta con esos datos, se registró una solicitud de recuperación. GEOEDUCA aún no envía el código por correo ni SMS.

Hasta que exista un canal real (M8 / notificaciones), un usuario de producción **no puede recibir el código**. El flujo queda persistido y listo para enganchar un mailer.

## Mecanismo de desarrollo (aislado)

`GEO_RECOVERY_DEV_REVEAL=true` añade `dev_code` a la respuesta **solo** si `APP_ENV` no es `production`. En producción se ignora aunque la variable esté en true. Las pruebas PHPUnit la dejan en `false` y usan un generador falso inyectado.

No usar esta bandera en un despliegue real.

## Contrato

### POST `/api/auth/recuperar`

Cuerpo: `{ "identifier": "8 dígitos", "role": "student|parent|staff" }`

- 200 siempre (cuenta existente o no), mismo `message`.
- 422 si el DNI o el rol no pasan la validación (`string` + `bail` + `digits:8`).
- 429 tras 10 solicitudes/minuto por DNI+IP.
- Nunca `code`, `demo_code` ni `dev_code` salvo el mecanismo de desarrollo.

Si la cuenta existe: se invalidan códigos previos no usados de ese `(dni, role)` y se inserta uno nuevo en `password_reset_codes` con `Hash::make`, caducidad (15 min) y `attempts = 0`.

### POST `/api/auth/recuperar/confirmar`

Cuerpo: `{ "identifier", "role", "code": "6 dígitos", "password": "≥8" }`

- 200 `{ "message": "Contraseña actualizada. Ya puede iniciar sesión." }` si el código es válido.
- 422 genérico (`No se pudo confirmar la recuperación.`) si el código es incorrecto, expiró, se reutilizó, se agotaron los intentos o la cuenta no existe. No se distingue el motivo.
- La fila se bloquea con `lockForUpdate`. Tras 5 fallos, `used_at` se marca y el código queda muerto.
- Éxito: `used_at` ahora, `users.password` con hash, todos los tokens Sanctum de esa cuenta revocados.

## Almacenamiento

Tabla `password_reset_codes`: `dni`, `role`, `code_hash` (oculto en el modelo), `expires_at`, `used_at`, `attempts`.

El JSON del prototipo **ya no** guarda `recovery` ni contraseñas nuevas. Los métodos `AsistenciaStore::startRecovery/confirmRecovery` lanzan si alguien los llama.

## Bitácora

`audit_logs` registra `recovery_requested`, `recovery_failed` y `recovery_confirmed` con rol y sufijo de DNI. Nunca código, hash ni contraseña.
