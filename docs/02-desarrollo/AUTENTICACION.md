# Autenticación (F1.2)

Rama: `feature/f1-autenticacion`. El login ya no usa `DemoAccounts` ni guarda la sesión en el JSON.

## Cómo se identifica una cuenta

La clave natural es **`(dni, role)`**, no el DNI solo. El prototipo permite que el mismo DNI sea estudiante y tutor. El campo `role` del `POST /api/auth/login` elige la fila. Un DNI correcto con el perfil equivocado recibe el mismo 401 genérico que una contraseña mala.

## Flujo

1. `LoginRequest` valida DNI (8 dígitos, no arreglo), contraseña y rol.
2. `EloquentUserAuthenticator` busca en `users` y comprueba con `Hash::check`.
3. Emite un token Sanctum (`{id}|{secreto}`) con caducidad (`SANCTUM_EXPIRATION`, 120 minutos).
4. **No** llama a `AsistenciaStore::rememberSession`.
5. `logout` borra el PAT y hace `Auth::forgetGuards()` para que el token no siga vivo en el mismo proceso (pruebas y Octane).
6. `/api/auth/me` y las rutas de asistencia exigen `auth:sanctum`.

Los paneles de asistencia **siguen saliendo del JSON**. `AsistenciaStore::actorForUser()` traduce el usuario persistido al actor del catálogo (ids 1/2/3 de las cuentas históricas, o el estudiante del catálogo por DNI).

## Endpoints

| Método | Ruta | Auth | Notas |
|---|---|---|---|
| POST | `/api/auth/login` | no (throttle:login, 5/min) | Cuerpo: `identifier`, `password`, `role` |
| POST | `/api/auth/logout` | Sanctum | Revoca el token |
| GET | `/api/auth/me` | Sanctum | 401 sin token válido |
| POST | `/api/auth/acceso` | throttle | Carnet: DNI+rol **sin contraseña** (pendiente de endurecer) |
| POST | `/api/auth/acceso/detectar` | no | Primer usuario del rol en la BD |
| POST | `/api/auth/recuperar*` | no | Sigue en el JSON (código en claro) |
| * | `/api/asistencia/*` | Sanctum | Datos aún en JSON; rol incorrecto → 403 |

Respuesta de login (contrato del frontend):

```json
{ "token": "1|…", "token_type": "Bearer", "user": { "id", "name", "roleLabel", "identifier", "role" } }
```

## Configuración

En `backend/.env`:

- `SANCTUM_EXPIRATION=120`
- `GEO_SEED_PROTOTYPE=true` solo en local si se necesitan las cuentas históricas hasheadas (`71405255` / `40111222`). El autenticador **no** las lee del código.
- `GEO_DEV_PASSWORD` siembra `10000001`–`10000003` (no inician los paneles JSON del catálogo).
- `APP_DEBUG=false` en `.env.example`.

## Qué no hay

- Fallback silencioso a `MockUserAuthenticator` o a cuentas en el frontend.
- Sesiones nuevas en `geoeduca-state.json`.
- Contraseñas en texto plano en `users`.
