# GEOEDUCA

Prototipo web de control de asistencia escolar. El repositorio principal del proyecto es [Diego7644/GeoEduca](https://github.com/Diego7644/GeoEduca).

Hoy las pantallas funcionan y varias acciones se guardan, pero **no es un sistema de asistencia real**: el historial, el QR, el GPS y las notificaciones no ejecutan un proceso productivo. El login sí es real (Sanctum contra `users`). La asistencia sigue en JSON. El detalle está en [`docs/02-desarrollo/`](docs/02-desarrollo/README.md).

## Tecnologías

| Capa | Stack |
|---|---|
| Backend | PHP 8.2, Laravel 11, Laravel Sanctum |
| Frontend | React 18, Vite 5, React Router, Tailwind CSS, Axios |
| Pruebas | PHPUnit 11, Mockery |
| Persistencia actual | `users`, tokens y recuperación en PostgreSQL. Asistencia aún en JSON local (`storage/app/geoeduca-state.json`) |

## Estructura

```
backend/                         API Laravel
frontend/                        SPA React
docs/01-guia-pruebas-calidad/    Guía académica pública (práctica de pruebas)
docs/02-desarrollo/              Diagnóstico técnico y plan de implementación
backend/tests/                   Suite PHPUnit reutilizable
```

El código de la aplicación está en `backend/` y `frontend/`. La guía académica no se mezcla con esos archivos.

## Estado actual

| Capacidad | Qué hay hoy |
|---|---|
| Paneles por rol (estudiante, padre/tutor, auxiliar) | La interfaz carga datos de la API |
| Autenticación | F1.2: `users` + Sanctum (hash, caducidad, revocación). F1.3: el carnet no inicia sesión; recuperación hasheada |
| Autorización | `auth:sanctum` + middleware `role:` sobre el usuario del token |
| Asistencia | El historial se genera por fórmula; no hay registros reales |
| QR | Patrón visual de 8×8; no es un código QR escaneable ni se valida en el servidor |
| GPS | El navegador calcula si está en el perímetro; la coordenada no se envía |
| Notificaciones | Lista dentro de la aplicación; no hay correo, SMS ni push |
| Base de datos | Login, tokens y recuperación en PostgreSQL. Asistencia aún en JSON |

Las cuentas de demostración viven en el código del prototipo. No se documentan aquí. Hay que rotarlas antes de cualquier despliegue.

## Instalación y ejecución

Hacen falta PHP 8.2+, Composer, Node.js 18+ y npm. El login real necesita las tablas (`php artisan migrate`). Sin PostgreSQL se puede usar SQLite solo para arrancar. Ver [`docs/02-desarrollo/CONFIGURACION-POSTGRES.md`](docs/02-desarrollo/CONFIGURACION-POSTGRES.md).

### Backend

```bash
cd backend
composer install
cp .env.example .env          # en Windows: copy .env.example .env
php artisan key:generate
php artisan serve             # http://127.0.0.1:8000
```

Para login y recuperación: defina `DB_PASSWORD` en `backend/.env`, levante Docker si usa PostgreSQL y ejecute `php artisan migrate` (instrucciones en la guía de PostgreSQL).

### Frontend

En otra terminal:

```bash
cd frontend
npm install
npm run dev                   # http://localhost:5173
```

Vite reenvía `/api` al backend. Los dos servidores tienen que estar en marcha.

### Pruebas automatizadas

```bash
cd backend
php vendor/bin/phpunit
```

Hay cuatro suites:

- `Caracterizacion`: describe el comportamiento actual y debe aprobar.
- `Persistencia`: esquema y relaciones Eloquent en SQLite. Debe aprobar.
- `Autenticacion`: login Sanctum, revocación y aislamiento. Debe aprobar.
- `RequisitosSeguridad`: comportamiento seguro esperado. En F1.2/F1.3 estas pruebas aprueban.

```bash
php vendor/bin/phpunit --testsuite Caracterizacion
php vendor/bin/phpunit --testsuite Persistencia
php vendor/bin/phpunit --testsuite Autenticacion
php vendor/bin/phpunit --testsuite RequisitosSeguridad
```

## Documentación

| Dónde | Para qué |
|---|---|
| [`docs/02-desarrollo/`](docs/02-desarrollo/README.md) | Diagnóstico, arquitectura, plan, riesgos y primera etapa (persistencia y autenticación) |
| [`docs/01-guia-pruebas-calidad/`](docs/01-guia-pruebas-calidad/README.md) | Evidencias públicas de la práctica de Pruebas y Calidad de Software |

La guía académica es un entregable de curso. Su finalización corresponde al compañero responsable. El desarrollo del producto continúa a partir de `docs/02-desarrollo/PROXIMAS-TAREAS.md`.
