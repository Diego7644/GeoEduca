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

Las cuentas de demostración salen de `db:seed`. La contraseña de la plantilla está en [`docs/02-desarrollo/INSTALACION-DEMO.md`](docs/02-desarrollo/INSTALACION-DEMO.md). La matriz está en [`docs/02-desarrollo/MATRIZ-CUMPLIMIENTO-FUNCIONAL.md`](docs/02-desarrollo/MATRIZ-CUMPLIMIENTO-FUNCIONAL.md).

## Instalación desde cero

Hacen falta PHP 8.2+ con `pdo_pgsql`, Composer, Node.js 18+, npm y Docker. En Windows también puede usarse [`scripts/instalar-demo.ps1`](scripts/instalar-demo.ps1), que copia `backend/.env.demo` solo si todavía no existe `backend/.env`.

La plantilla de demostración crea administrador, auxiliar, padre/tutor y tres estudiantes sintéticos de 1.º A, 3.º B y 5.º C. La contraseña documentada es `prueba123`. El detalle está en [`docs/02-desarrollo/INSTALACION-DEMO.md`](docs/02-desarrollo/INSTALACION-DEMO.md).

1. Clonar el repositorio y entrar en la carpeta.
2. Copiar la plantilla de entorno y generar la clave de Laravel:

```bash
cd backend
composer install
copy .env.demo .env
php artisan key:generate
```

En Linux o macOS, `cp .env.demo .env`.

3. No edite un `.env` personal encima de una base que ya tenga datos. `DB_PASSWORD` de la plantilla solo abre el PostgreSQL nuevo de Docker.
4. Desde la raíz del repositorio:

```bash
docker compose --env-file backend/.env up -d
cd backend
php artisan migrate
php artisan db:seed
php artisan serve
```

`db:seed` crea la configuración de la I.E. Mariscal Castilla y las cuentas de la tabla de instalación. No imprime la contraseña y no borra asistencias ya guardadas.

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
