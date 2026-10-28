# Autenticación y accesos (F1.2–F1.3)

Rama F1.3: `feature/f1-seguridad-accesos` (local). F1.2 ya está en `main` (`3ea06ad`, PR #4).

## Cómo se identifica una cuenta

La clave natural es **`(dni, role)`**, no el DNI solo. El prototipo permite que el mismo DNI sea estudiante y tutor. El campo `role` de `POST /api/auth/login` elige la fila. Un DNI correcto con el perfil equivocado recibe el mismo 401 genérico que una contraseña mala.

El rol **no se vuelve a leer del cliente** después del login: las rutas usan `$request->user()->role` del token Sanctum y el middleware `role:`.

## Flujo de login

1. `LoginRequest` valida DNI (8 dígitos, no arreglo), contraseña y rol.
2. `EloquentUserAuthenticator` busca en `users` y comprueba con `Hash::check`.
3. Emite un token Sanctum (`{id}|{secreto}`) con caducidad (`SANCTUM_EXPIRATION`, 120 minutos).
4. **No** llama a `AsistenciaStore::rememberSession`.
5. `logout` borra el PAT y hace `Auth::forgetGuards()`.
6. `/api/auth/me` y las rutas de asistencia exigen `auth:sanctum`.

## Acceso por carnet (cambio F1.3)

`POST /api/auth/acceso` **ya no inicia sesión**. Conocer un DNI no crea token.

Finalidad real: que el **personal de puerta** (rol `staff` ya autenticado) identifique a un estudiante del catálogo JSON. Respuesta: `{ identified, student: { name, grade, code, dni } }`, sin `token` ni `user`.

`POST /api/auth/acceso/detectar` exige la misma sesión de personal y responde que **no hay lector físico conectado**. No enumera cuentas ni devuelve el primer DNI del rol.

Sin token → 401. Token de estudiante o tutor → 403. El frontend no usa estos endpoints para entrar al portal: los botones NFC/QR del login avisan que el carnet no abre la web.

## Recuperación

Ver [`RECUPERACION.md`](RECUPERACION.md). Códigos hasheados en `password_reset_codes`. No hay correo ni SMS: la API no finge un envío.

## Endpoints

| Método | Ruta | Auth | Notas |
|---|---|---|---|
| POST | `/api/auth/login` | no (`throttle:login`, 5/min) | `identifier`, `password`, `role` |
| POST | `/api/auth/logout` | Sanctum | Revoca el token |
| GET | `/api/auth/me` | Sanctum | 401 sin token válido |
| POST | `/api/auth/acceso` | Sanctum + `role:staff` | Identifica DNI en puerta; **no** emite sesión |
| POST | `/api/auth/acceso/detectar` | Sanctum + `role:staff` | Lector no conectado; sin PII |
| POST | `/api/auth/recuperar` | no (`throttle:recovery`, 10/min) | Mensaje genérico; sin código |
| POST | `/api/auth/recuperar/confirmar` | no (`throttle:recovery`) | Un solo uso; revoca tokens |
| * | `/api/asistencia/*` | Sanctum + `role:` según panel | Datos aún en JSON |

Respuesta de login (contrato del frontend, sin cambios):

```json
{ "token": "1|…", "token_type": "Bearer", "user": { "id", "name", "roleLabel", "identifier", "role" } }
```

## Configuración

En `backend/.env`:

- `SANCTUM_EXPIRATION=120`
- `GEO_SEED_PROTOTYPE=true` solo en local si se necesitan las cuentas históricas hasheadas. El autenticador **no** las lee del código.
- `GEO_DEV_PASSWORD` siembra `10000001`–`10000003`.
- `GEO_RECOVERY_TTL=15`, `GEO_RECOVERY_MAX_ATTEMPTS=5`, `GEO_RECOVERY_DEV_REVEAL=false`
- `APP_DEBUG=false` en `.env.example`.

## Qué no hay

- Fallback silencioso a `MockUserAuthenticator` o a cuentas en el frontend.
- Sesión nueva por conocer un DNI.
- Códigos de recuperación en texto claro ni en la respuesta de producción.
- Sesiones nuevas en `geoeduca-state.json`.
