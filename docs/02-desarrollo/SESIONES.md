# Sesiones React y Laravel (F1.4)

Rama: `feature/f1-sesiones-frontend` (local). El contrato de la API no cambió.

## Qué confirma la sesión

Al arrancar, `AuthProvider` llama a `GET /api/auth/me` si hay un Bearer en `localStorage`. Hasta que responde, la interfaz muestra «Comprobando sesión…» y no abre paneles.

| Estado | Cuándo | Qué ve el usuario |
|---|---|---|
| `checking` | Hay token y `/me` no ha respondido | Espera |
| `authenticated` | `/me` devolvió un rol `student`, `parent` o `staff` | Panel de ese rol |
| `anonymous` | No hay token, o `/me` respondió 401 | Login |
| `offline` | No hubo respuesta de red | Reintentar, sin abrir el panel |
| `error` | El servidor respondió otro error | Reintentar, sin abrir el panel |

El objeto guardado en `geoeduca_user` no autoriza. Si dice `staff` y `/me` dice `student`, la ruta `/auxiliar` vuelve a `/estudiante`.

## Cierre y respuestas tardías

`logout` borra el almacenamiento y el estado **antes** de `POST /api/auth/logout`, y envía el Bearer en esa petición para revocar el PAT. Un contador de época descarta un `/me` que llega después.

Un 401 cierra la sesión solo si el token fallido es el que sigue guardado. Un 401 de `POST /api/auth/login` no borra otra sesión ni trata el formulario como expirado. Un 401 de un token ya reemplazado no pisa el login nuevo.

## Recuperación en la interfaz

No hay correo ni SMS. «¿Olvidaste tu contraseña?» abre un aviso: la recuperación automática no está disponible y la pantalla no dice si la cuenta existe. No hay formulario de código. El API de F1.3 sigue en el backend para cuando exista un canal.

## Bearer en localStorage

Hoy el token vive en `localStorage` (`geoeduca_token`). Cualquier script de la página (XSS) puede leerlo. La alternativa prevista, cuando el navegador y Laravel compartan dominio, es la cookie de sesión de Sanctum con `HttpOnly`, `Secure` y `SameSite`. Esa cookie no la lee JavaScript. No se migró en F1.4 porque el frontend (puerto 5173) y la API (puerto 8000) siguen en orígenes distintos y el contrato actual es Bearer.

## Limitación vista en el navegador

El usuario de desarrollo `10000001` entra bien y `/me` confirma `student`. El panel de asistencia sigue saliendo del JSON y, si el DNI no está en el catálogo, `AsistenciaStore` usa el id numérico de `users`. El primer usuario local (id 1) coincide con el estudiante de demostración del catálogo, así que el panel muestra ese historial. No es una sesión falsa: el rol sí sale del backend. Hay que separar esos ids al migrar la asistencia.
