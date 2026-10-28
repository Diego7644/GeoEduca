# Arquitectura

## Hoy (commit `800e5e2`)

```mermaid
flowchart LR
  subgraph cliente [Navegador]
    UI[React 18 + Vite]
    LS[localStorage token y usuario]
    GPS[Geolocalización local]
    QR[Patrón 8x8]
  end
  subgraph api [Laravel 11]
    AC[AuthController]
    AS[AsistenciaController]
    AUTH[AuthService + MockUserAuthenticator]
    DEMO[DemoAccounts]
    STORE[AsistenciaStore]
  end
  JSON[(storage/app/geoeduca-state.json)]
  UI --> LS
  UI -->|Axios /api| AC
  UI --> AS
  UI --> GPS
  UI --> QR
  AC --> AUTH
  AUTH --> DEMO
  AUTH --> STORE
  AS --> STORE
  STORE --> JSON
```

Flujos:

1. Login: `POST /api/auth/login` busca `(dni, role)` en `users`, comprueba el hash y emite un token Sanctum. No hay fallback a `DemoAccounts`.
2. Asistencia: el controlador lee el token, pide el actor al almacén y, si el rol coincide, muta el JSON y devuelve un `panel`.
3. Justificación: exige `child_id` y comprueba que el estudiante esté a cargo del tutor.
4. Frontend: el proxy de Vite reenvía `/api` a Laravel. CORS permite `localhost:5173` y `127.0.0.1:5173`.

F1.2: el login usa `users` + Sanctum. Las sesiones nuevas no van al JSON. La asistencia **sí** sigue en `AsistenciaStore`. Colas y correo no existen.

## Destino de F1 (persistencia + autenticación/autorización)

```mermaid
flowchart LR
  subgraph cliente [Navegador]
    UI[React]
    SESION[Sesión Sanctum]
  end
  subgraph api [Laravel]
    MW[middleware auth:sanctum y rol]
    AUTH[AuthService contra BD]
    POL[Políticas de propiedad]
  end
  subgraph datos [Base de datos]
    U[users]
    T[personal_access_tokens]
    S[students / guardian_student]
  end
  UI --> SESION
  SESION --> MW
  MW --> AUTH
  MW --> POL
  AUTH --> U
  AUTH --> T
  POL --> S
```

Las tablas de F1.1 ya existen. `AsistenciaStore` y `DemoAccounts` se retiran en F1.2–F1.3, cuando Sanctum cubra el mismo contrato. Hasta entonces, las pruebas de caracterización fotografían el JSON.

## Límites de confianza actuales

| Superficie | Quién confía | Riesgo |
|---|---|---|
| Token en `localStorage` | El frontend | XSS lo lee |
| Rol en el navegador | `ProtectedRoute` | Se puede editar; el API ya no se deja engañar en asistencia |
| JSON único | Todo el backend | Un archivo = todas las sesiones y contraseñas recuperadas |
| Cuentas de demostración | Login | Credenciales en el código, ya públicas en esta rama |
