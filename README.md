# GEOEDUCA

Prototipo web de control de asistencia escolar. El repositorio principal del proyecto es [Diego7644/GeoEduca](https://github.com/Diego7644/GeoEduca).

La asistencia, las justificaciones, las notificaciones internas y el QR se guardan en PostgreSQL. El login es Sanctum. El detalle y la matriz de cumplimiento están en [`docs/02-desarrollo/`](docs/02-desarrollo/README.md).

## Tecnologías

| Capa | Stack |
|---|---|
| Backend | PHP 8.2, Laravel 11, Laravel Sanctum |
| Frontend | React 18, Vite 5, React Router, Tailwind CSS, Axios |
| Pruebas | PHPUnit 11, Vitest 2 |
| Persistencia | PostgreSQL. El JSON de prototipo se conserva como respaldo y no es la fuente de la asistencia |

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
| Roles | Estudiante, apoderado, auxiliar, docente y administración. El personal se distingue por `staff_members.kind`, no por el id numérico |
| Autenticación | Sanctum. El carnet NFC no inicia sesión |
| Asistencia | Entrada, salida, tardanza y cierre de jornada en PostgreSQL. La ausencia no se marca antes de la hora de salida |
| QR | Token de un solo uso, 60 segundos, dibujado como QR. Lo valida solo el auxiliar |
| Notificaciones | Campana interna por destinatario. Sin correo, SMS ni WhatsApp |
| Justificaciones | El apoderado envía; auxiliar, docente de tutoría o administración revisan |
| GPS y NFC | El GPS no marca asistencia. No hay molinetes ni lector NFC |

Las cuentas locales salen de `db:seed` cuando `APP_ENV=local` y `GEO_SEED_PROTOTYPE=true`. La contraseña es `GEO_DEV_PASSWORD` en `backend/.env` y no se publica. La matriz está en [`docs/02-desarrollo/MATRIZ-CUMPLIMIENTO-FUNCIONAL.md`](docs/02-desarrollo/MATRIZ-CUMPLIMIENTO-FUNCIONAL.md).

## Instalación desde cero

Hacen falta PHP 8.2+ con `pdo_pgsql`, Composer, Node.js 18+, npm y Docker. El inicio de sesión consulta PostgreSQL. Si `DB_PASSWORD` está vacío, `POST /api/auth/login` no puede autenticarse contra el servidor y responde error de conexión.

1. Clonar el repositorio y entrar en la carpeta.
2. Copiar la plantilla de entorno y generar la clave de Laravel:

```bash
cd backend
composer install
copy .env.example .env
php artisan key:generate
```

En Linux o macOS, `cp .env.example .env`.

3. Editar `backend/.env` (no se versiona). `DB_PASSWORD` y `GEO_DEV_PASSWORD` son obligatorias para el entorno local y no deben ser contraseñas reales. Si el puerto 5432 ya lo usa otro PostgreSQL, use `DB_PORT=5433`.
4. Desde la raíz del repositorio:

```bash
docker compose --env-file backend/.env up -d
cd backend
php artisan migrate
php artisan db:seed
php artisan serve
```

`db:seed` crea la configuración del colegio. Con `GEO_DEV_PASSWORD` crea las cuentas ficticias `10000001` (estudiante), `10000002` (apoderado) y `10000003` (personal). No imprime la contraseña.

5. En otra terminal:

```bash
cd frontend
npm install
npm run dev
```

Vite reenvía `/api` a `http://127.0.0.1:8000`. El detalle de PostgreSQL está en [`docs/02-desarrollo/CONFIGURACION-POSTGRES.md`](docs/02-desarrollo/CONFIGURACION-POSTGRES.md).

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
cd ../frontend
npm test
npm run build
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
