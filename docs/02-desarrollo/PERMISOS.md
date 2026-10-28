# Matriz de permisos (F1.3)

El servidor decide el rol con el usuario del token Sanctum. Un `role` enviado en el JSON **después** del login no impersona a nadie.

Hoy hay tres roles de producto: `student`, `parent`, `staff`. **Docente y auxiliar comparten `staff`**. No hay panel de administración (D6 sigue abierto).

## Rutas

| Recurso | Estudiante | Apoderado | Personal (`staff`) | Anónimo |
|---|---|---|---|---|
| `POST /api/auth/login` | — | — | — | sí (throttle) |
| `POST /api/auth/recuperar*` | — | — | — | sí (throttle, sin enumeración) |
| `GET /api/auth/me` | propio | propio | propio | 401 |
| `POST /api/auth/logout` | propia sesión | propia | propia | 401 |
| `POST /api/auth/acceso*` | 403 | 403 | identifica alumno / lector off | 401 |
| `GET /api/asistencia/estudiante` | propio panel JSON | 403 | 403 | 401 |
| `GET /api/asistencia/padre` | 403 | hijos vinculados | 403 | 401 |
| `POST /api/asistencia/padre/vincular` | 403 | solo si `guardianId` coincide | 403 | 401 |
| `POST /api/asistencia/justificacion` | 403 | solo hijo vinculado | 403 | 401 |
| `GET /api/asistencia/auxiliar/casos` | 403 | 403 | sí (directorio del catálogo) | 401 |
| `POST /api/asistencia/auxiliar/aprobar` | 403 | 403 | sí | 401 |
| `PUT /api/asistencia/configuracion` | 403 | 403 | sí | 401 |
| `GET /api/asistencia/configuracion` | lectura | lectura | lectura | 401 |
| `GET /api/asistencia/reportes` | propio | familia | operativo | 401 |
| `GET/POST …/notificaciones` | las suyas | las suyas | las suyas | 401 |

## Propiedad

- Estudiante: el panel se arma con el actor mapeado desde `(dni, role)` del token. No hay `student_id` en la URL.
- Apoderado: `linked` / `guardianId` del catálogo JSON. Un tutor persistido que no está en ese catálogo ve `children: []` y no justifica hijos ajenos.
- Personal: ve el directorio de contingencia (DNIs del prototipo). Es el operador de puerta, no un administrador global.

Defensa en profundidad: middleware `role:` en las rutas **y** `AsistenciaStore::guard()`.

## Frontend

`ProtectedRoute` oculta paneles en el navegador; no es control de acceso. El API ya no se deja engañar cambiando `geoeduca_user.role` en `localStorage`.
