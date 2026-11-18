# Crea una base vacía, la siembra y la elimina. No usa la base geoeduca de trabajo.
$ErrorActionPreference = 'Stop'
$root = Resolve-Path (Join-Path $PSScriptRoot '..')
$backend = Join-Path $root 'backend'
$check = 'geoeduca_install_check'

function Read-EnvValue([string] $Path, [string] $Name) {
    $line = Get-Content $Path | Where-Object { $_ -like "$Name=*" } | Select-Object -First 1
    if (-not $line) { return '' }
    return $line.Substring($Name.Length + 1).Trim()
}

$demoPassword = Read-EnvValue (Join-Path $backend '.env.demo') 'GEO_DEV_PASSWORD'
if ($demoPassword.Length -lt 8) {
    Write-Host 'backend/.env.demo no tiene GEO_DEV_PASSWORD utilizable.'
    exit 1
}

docker exec geoeduca-postgres psql -U geoeduca -d postgres -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$check' AND pid <> pg_backend_pid();" | Out-Null
docker exec geoeduca-postgres psql -U geoeduca -d postgres -c "DROP DATABASE IF EXISTS $check;" | Out-Null
docker exec geoeduca-postgres psql -U geoeduca -d postgres -c "CREATE DATABASE $check;" | Out-Null

$env:DB_DATABASE = $check
$env:GEO_DEV_PASSWORD = $demoPassword
$env:GEO_SEED_PROTOTYPE = 'false'
Set-Location $backend
php artisan migrate --force
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php artisan db:seed --force
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php artisan geoeduca:verificar-demo
$code = $LASTEXITCODE

Remove-Item Env:DB_DATABASE -ErrorAction SilentlyContinue
Remove-Item Env:GEO_DEV_PASSWORD -ErrorAction SilentlyContinue
Remove-Item Env:GEO_SEED_PROTOTYPE -ErrorAction SilentlyContinue
docker exec geoeduca-postgres psql -U geoeduca -d postgres -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$check' AND pid <> pg_backend_pid();" | Out-Null
docker exec geoeduca-postgres psql -U geoeduca -d postgres -c "DROP DATABASE IF EXISTS $check;" | Out-Null
Write-Host "instalacion_nueva=$code"
exit $code
