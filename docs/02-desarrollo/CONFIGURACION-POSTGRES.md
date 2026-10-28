# Configuración local de PostgreSQL (F1.1)

El prototipo **arranca sin base de datos**. Login y asistencia siguen en JSON. PostgreSQL hace falta para crear el esquema, sembrar datos de desarrollo y, a partir de F1.2, autenticar contra `users`.

## Requisitos

- Docker Engine (imagen `postgres:16-alpine` en `docker-compose.yml` de la raíz).
- PHP 8.2 con `pdo_pgsql`. En XAMPP la extensión suele estar comentada en `php.ini`. No hace falta editar el `php.ini` si se invoca:

```bash
php -d extension=pdo_pgsql artisan migrate
```

En Windows, si `php -m` no lista `pdo_pgsql`, compruebe que existen `php_pdo_pgsql.dll` y `libpq.dll` junto al PHP.

## Instalación

Desde la raíz del repositorio:

```bash
cd backend
copy .env.example .env
php artisan key:generate
```

Edite `backend/.env` (nunca se versiona):

- `DB_CONNECTION=pgsql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=5432` (o `5433` si 5432 ya lo usa PostgreSQL de Windows)
- `DB_DATABASE=geoeduca`
- `DB_USERNAME=geoeduca`
- `DB_PASSWORD=` contraseña local, no reutilizar credenciales reales
- `GEO_DEV_PASSWORD=` solo si va a ejecutar `db:seed`
- `APP_ENV=local`

Compose lee variables del entorno o de `--env-file`. Desde la raíz:

```bash
docker compose --env-file backend/.env up -d
cd backend
php -d extension=pdo_pgsql artisan migrate
php -d extension=pdo_pgsql artisan db:seed
```

`db:seed` crea la fila de `school_settings`. Con `GEO_DEV_PASSWORD` siembra `10000001`–`10000003`. Con `GEO_SEED_PROTOTYPE=true` (solo `APP_ENV=local`) siembra hasheadas las cuentas históricas del prototipo para que el frontend de demostración pueda entrar. El autenticador nunca lee `DemoAccounts`.

## Arranque del prototipo sin PostgreSQL

En `backend/.env` puede dejar:

```
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

`php artisan serve` no consulta las tablas nuevas. El frontend no cambia.

Si `.env` apunta a PostgreSQL y el contenedor está caído, el prototipo JSON sigue respondiendo: Laravel no abre conexión hasta la primera consulta Eloquent.

## Pruebas

PHPUnit **no usa** este PostgreSQL. `backend/phpunit.xml` fuerza SQLite `:memory:` y `DB_FOREIGN_KEYS=true`.

```bash
cd backend
php vendor/bin/phpunit --testsuite Persistencia
php vendor/bin/phpunit
```

## Verificación rápida del esquema

```bash
docker exec geoeduca-postgres psql -U geoeduca -d geoeduca -c "\dt"
```

Deben aparecer `users`, `staff_members`, `students`, `guardian_student`, `school_settings`, `attendance_records`, `justifications`, `qr_tokens`, `in_app_notifications`, `password_reset_codes`, `audit_logs`, `personal_access_tokens` y `migrations`.
