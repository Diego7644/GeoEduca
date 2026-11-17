# Instalacion de demostracion. No reemplaza backend/.env ni una base ya creada.
$ErrorActionPreference = 'Stop'
$root = Resolve-Path (Join-Path $PSScriptRoot '..')
$backend = Join-Path $root 'backend'
$envFile = Join-Path $backend '.env'
$demo = Join-Path $backend '.env.demo'

if (Test-Path $envFile) {
    Write-Host 'backend/.env ya existe. No se reemplaza y no se ejecuta migrate ni seed sobre esa base.'
    Write-Host 'Para probar la instalacion nueva, use una copia limpia del repositorio.'
    exit 1
}

Copy-Item $demo $envFile
$busy = Get-NetTCPConnection -LocalPort 5432 -State Listen -ErrorAction SilentlyContinue
if ($busy) {
    $lines = Get-Content $envFile
    $lines = $lines | ForEach-Object { if ($_ -like 'DB_PORT=*') { 'DB_PORT=5433' } else { $_ } }
    [System.IO.File]::WriteAllLines($envFile, $lines)
    Write-Host 'El puerto 5432 esta ocupado. Docker usara el puerto 5433.'
}

Set-Location $backend
if (-not (Test-Path (Join-Path $backend 'vendor'))) {
    composer install --no-interaction
}
php artisan key:generate --force
Set-Location $root
docker compose --env-file backend/.env up -d
Set-Location $backend
php artisan migrate --force
php artisan db:seed --force
Write-Host 'Listo. En una terminal: php artisan serve'
Write-Host 'En otra: cd frontend; npm install; npm run dev'
Write-Host 'Cuentas y contrasena: docs/02-desarrollo/INSTALACION-DEMO.md'
