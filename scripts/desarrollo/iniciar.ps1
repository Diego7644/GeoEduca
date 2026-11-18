# Arranca PostgreSQL si el contenedor existe, Laravel en 8000 y Vite en 5173.
# No instala Tailscale, no cambia contraseñas y no publica la app en Internet.
$ErrorActionPreference = 'Stop'
$root = Resolve-Path (Join-Path $PSScriptRoot '..\..')
$backend = Join-Path $root 'backend'
$frontend = Join-Path $root 'frontend'

function Test-PortListening([int] $Port) {
    return [bool](Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue)
}

$docker = Get-Command docker -ErrorAction SilentlyContinue
if ($docker) {
    $container = docker ps -a --filter 'name=geoeduca-postgres' --format '{{.Names}} {{.Status}}'
    if ($container -match 'geoeduca-postgres' -and $container -notmatch 'Up') {
        docker start geoeduca-postgres | Out-Null
        Write-Host 'Contenedor geoeduca-postgres iniciado.'
    } elseif ($container -match 'Up') {
        Write-Host 'PostgreSQL de GeoEduca ya esta en marcha.'
    } else {
        Write-Host 'No se encontro el contenedor geoeduca-postgres. La API fallara si el puerto 5433 no responde.'
    }
}

if (Test-PortListening 8000) {
    Write-Host 'Laravel ya escucha en 127.0.0.1:8000.'
} else {
    Start-Process powershell -ArgumentList '-NoExit', '-Command', "Set-Location '$backend'; php artisan serve --host=127.0.0.1 --port=8000"
    Write-Host 'Laravel se esta abriendo en una ventana nueva, puerto 8000.'
}

if (Test-PortListening 5173) {
    Write-Host 'Vite ya escucha en el puerto 5173.'
} else {
    Start-Process powershell -ArgumentList '-NoExit', '-Command', "Set-Location '$frontend'; npm run dev"
    Write-Host 'Vite se esta abriendo en una ventana nueva, puerto 5173.'
}

Write-Host 'La laptop puede usar http://127.0.0.1:5173. El iPhone necesita la URL HTTPS.'
& powershell -NoProfile -File "$PSScriptRoot\publicar-https.ps1"
if ($LASTEXITCODE -ne 0) {
    Write-Host 'Los servicios locales siguen disponibles. La camara del iPhone queda bloqueada hasta autorizar Tailscale.'
}
