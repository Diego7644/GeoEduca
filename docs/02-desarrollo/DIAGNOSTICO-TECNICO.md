# Diagnóstico técnico actual

Fecha original: 2026-10-28, commit `800e5e2`. PHPUnit de M0: `RESULTADOS-PHPUNIT-INTEGRACION.md`.

**Actualización F1.1** (`feature/f1-persistencia`): existen migraciones, modelos Eloquent y PostgreSQL 16 local. La API **no los usa**. Login y asistencia siguen en JSON. Ver `MODELO-DE-DATOS.md` y `RESULTADOS-PHPUNIT-F1.1.md`. **No se considera implementada una pantalla:** hace falta un proceso de servidor o de navegador con datos que no sean fijos y un resultado comprobable.

Etiquetas: **Real**, **Parcial**, **Simulada**, **No existe**.

## Resumen por módulo

| Módulo | Veredicto | Hecho comprobado |
|---|---|---|
| Backend Laravel | **Parcial** | 20 rutas de API. Lógica en `AsistenciaStore` (~1.400 líneas) sobre un JSON |
| Frontend React | **Parcial** | Paneles cargan la API; Reportes y Configuración activos; GPS local; CSV en el navegador |
| Modelos y migraciones | **Parcial (F1.1)** | Tablas y modelos creados; la API no los consulta |
| Base de datos | **Parcial (F1.1)** | PostgreSQL 16 con esquema; el estado de la API sigue en `storage/app/geoeduca-state.json` |
| Autenticación | **Parcial / simulada** | Token `mock-<hex>` guardado y validado en el JSON; sin expiración; cuentas de demostración |
| Autorización | **Parcial** | `guard()` y `guardianId` en el almacén; sin middleware Sanctum |
| Personas | **Simulada** | 3 cuentas y 8 estudiantes fijos en código |
| Asistencia | **Simulada** | Historial de 100 días generado por fórmula (`plan()` / `rowsFor()`) |
| QR | **Simulada** | Token de 60 s; patrón 8×8; no es un QR escaneable; ningún endpoint lo valida |
| Geolocalización | **Parcial** | Haversine en el navegador; la coordenada **no viaja** al servidor |
| Notificaciones | **Parcial** | Lista en pantalla; «Push/SMS OK» no envía nada |
| APIs | **Real** | Axios + proxy de Vite; 20 rutas |
| Reportes | **Parcial** | CSV generado en el navegador |
| Configuración | **Parcial** | Se guarda en el JSON; solo el auxiliar edita |
| Pruebas | **Parcial** | Suite reutilizable integrada en esta rama; 9 requisitos de seguridad siguen fallando |

## Backend

- Rutas (`backend/routes/api.php`): `health`, 7 de `auth` y 12 de `asistencia`. El comentario `TODO: proteger con middleware('auth:sanctum')` sigue: **ninguna ruta tiene middleware** de autenticación.
- `AsistenciaController::actor()` responde 401 si el token no está en el JSON. `AsistenciaStore::guard()` responde 403 si el rol no coincide. PHPUnit confirma 401/403 en asistencia y 200 `{user:null}` en `/auth/me` sin sesión.
- `AsistenciaStore::mutate()` reescribe todo el JSON con `flock(LOCK_EX)` en cada petición. Sirve en una máquina; no escala.
- Recuperación de contraseña: código de 6 dígitos (15 min). En entorno `local` el código se devuelve en la respuesta (`demo_code`). La contraseña nueva se guarda en texto plano.
- Login por carnet: acepta cualquier DNI de las cuentas de demostración; no hay carnet físico.
- `LoginRequest` conserva el defecto AUTO-01: un DNI enviado como arreglo provoca HTTP 500. PHPUnit R1 lo reproduce.
- Sin `throttle`. CSRF desactivado para `api/*`.
- `.env.example` tiene `APP_DEBUG=true`. Sanctum está instalado y no se usa.

## Frontend

- Token y usuario en `localStorage`. `ProtectedRoute` solo comprueba que existan: el rol del navegador se puede editar. El servidor ya rechaza el rol equivocado (403), pero la pantalla sigue abierta.
- El frontend conserva cuentas de respaldo sin conexión (`VITE_USE_MOCK_AUTH`).
- GPS: `watchPosition` + haversine. Solo cambia un letrero.
- QR: `qrToken.js` dibuja hasta 64 cuadros a partir de un hash. El auxiliar «escanea» pegando un DNI (F2).
- Componentes grandes: `DashboardPadre.jsx`, `DashboardEstudiante.jsx`, `DashboardAuxiliar.jsx`, `Login.jsx`.

## Seguridad conocida (rama de Diego)

| ID | Problema | Estado |
|---|---|---|
| SEC-01 | Asistencia sin autenticación | Mitigado en el controlador; sin middleware |
| SEC-02 | Sin autorización por rol | Mitigado (`guard`, `guardianId`) |
| SEC-03 | Logout no invalida el token | Mitigado para asistencia; `/me` sigue 200 |
| SEC-04 | Sesiones sin expiración | Presente |
| SEC-05 | Sin límite de intentos | Presente (12×401) |
| SEC-06 | `APP_DEBUG=true` en la plantilla | Presente |
| SEC-07 | Cabeceras de seguridad ausentes | Presente (`nosniff` falta) |
| SEC-08 | DNI como arreglo → 500 | Presente |
| SEC-09 | Contraseña recuperada en texto plano | Presente |
| SEC-10 | Código de recuperación en la respuesta local | Presente |
| SEC-11 | Contraseñas de demostración en el código | Presente |
| SEC-12 | Login por carnet sin carnet | Presente |
| SEC-13 | Token en `localStorage` | Presente |
| SEC-14 | Aviso crítico de `laravel/framework` y vulnerabilidades npm | Presente (dependencias de producción no se tocaron) |
| SEC-15 | Token QR sin validar ni de un solo uso | Presente |
| SEC-16 | GPS no se valida en el servidor | Presente |
| SEC-17 | Estado completo en un JSON | Presente; el archivo está en `.gitignore` |

## Veredicto

La rama de Diego convierte GEOEDUCA en un **prototipo interactivo con servidor**, no en un sistema funcional. Las acciones se guardan; asistencia, estudiantes, QR y notificaciones siguen inventados. El cimiento que falta es **base de datos, usuarios reales, autenticación y autorización con Sanctum**.
