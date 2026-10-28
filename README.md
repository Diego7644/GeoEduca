# GEOEDUCA

Prototipo web de control de asistencia escolar. El repositorio principal del proyecto es [Diego7644/GeoEduca](https://github.com/Diego7644/GeoEduca).

Hoy las pantallas funcionan y varias acciones se guardan, pero **no es un sistema funcional**: no hay base de datos, la autenticación es simulada y el historial de asistencia, el QR, el GPS y las notificaciones no ejecutan un proceso real. El siguiente objetivo de desarrollo es persistencia, autenticación segura y gestión de roles. El detalle está en [`docs/02-desarrollo/`](docs/02-desarrollo/README.md).

## Tecnologías

| Capa | Stack |
|---|---|
| Backend | PHP 8.2, Laravel 11, Laravel Sanctum (instalado, aún no usado) |
| Frontend | React 18, Vite 5, React Router, Tailwind CSS, Axios |
| Pruebas | PHPUnit 11, Mockery |
| Persistencia actual | archivo JSON local (`storage/app/geoeduca-state.json`, ignorado por Git). No hay migraciones ni modelos |

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
| Autenticación | Cuentas de demostración y tokens `mock-` guardados en un JSON, sin expiración |
| Autorización | El servidor comprueba el rol en el controlador; no hay middleware Sanctum |
| Asistencia | El historial se genera por fórmula; no hay registros reales |
| QR | Patrón visual de 8×8; no es un código QR escaneable ni se valida en el servidor |
| GPS | El navegador calcula si está en el perímetro; la coordenada no se envía |
| Notificaciones | Lista dentro de la aplicación; no hay correo, SMS ni push |
| Base de datos | No existe |

Las cuentas de demostración viven en el código del prototipo. No se documentan aquí. Hay que rotarlas antes de cualquier despliegue.

## Instalación y ejecución

Hacen falta PHP 8.2+, Composer, Node.js 18+ y npm. **No hace falta un motor de base de datos** para arrancar el prototipo.

### Backend

```bash
cd backend
composer install
cp .env.example .env          # en Windows: copy .env.example .env
php artisan key:generate
php artisan serve             # http://127.0.0.1:8000
```

No ejecutes `php artisan migrate`: todavía no hay migraciones.

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

Hay dos suites:

- `Caracterizacion`: describe el comportamiento actual y debe aprobar.
- `RequisitosSeguridad`: describe el comportamiento seguro esperado. Hoy falla a propósito (defecto de autenticación, cabeceras, límite de intentos, etc.). No se modifican esas expectativas para ocultar fallos.

```bash
php vendor/bin/phpunit --testsuite Caracterizacion
php vendor/bin/phpunit --testsuite RequisitosSeguridad
```

## Documentación

| Dónde | Para qué |
|---|---|
| [`docs/02-desarrollo/`](docs/02-desarrollo/README.md) | Diagnóstico, arquitectura, plan, riesgos y primera etapa (persistencia y autenticación) |
| [`docs/01-guia-pruebas-calidad/`](docs/01-guia-pruebas-calidad/README.md) | Evidencias públicas de la práctica de Pruebas y Calidad de Software |

La guía académica es un entregable de curso. Su finalización corresponde al compañero responsable. El desarrollo del producto continúa a partir de `docs/02-desarrollo/PROXIMAS-TAREAS.md`.
