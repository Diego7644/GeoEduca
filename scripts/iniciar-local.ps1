$root = Split-Path -Parent $PSScriptRoot
$php = Join-Path $root 'backend'
$web = Join-Path $root 'frontend'

Write-Host 'GEOEDUCA local'
Write-Host "Backend: $php"
Write-Host "Frontend: $web"
Write-Host 'Abra dos terminales si estos procesos deben quedar en primer plano.'
Write-Host '1. cd backend; php artisan migrate; php artisan serve'
Write-Host '2. cd frontend; npm run dev'
Write-Host 'Vite publica el puerto 5173, o el siguiente libre, y reenvía /api a 127.0.0.1:8000.'
