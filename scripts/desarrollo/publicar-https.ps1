# Expone Vite solo dentro de la tailnet. No usa Tailscale Funnel ni abre el proyecto a Internet.
$ErrorActionPreference = 'Stop'

function Find-Tailscale {
    $candidates = @(
        "$env:ProgramFiles\Tailscale\tailscale.exe",
        "${env:ProgramFiles(x86)}\Tailscale\tailscale.exe",
        "$env:LOCALAPPDATA\Tailscale\tailscale.exe"
    )
    foreach ($path in $candidates) {
        if (Test-Path $path) { return $path }
    }
    $command = Get-Command tailscale -ErrorAction SilentlyContinue
    if ($command) { return $command.Source }
    return $null
}

$tailscale = Find-Tailscale
if (-not $tailscale) {
    Write-Host 'Tailscale no esta instalado. Este script no lo instala: hace falta tu autorizacion.'
    Write-Host 'Instalador oficial de Windows: https://tailscale.com/download/windows'
    Write-Host 'En el iPhone, la app oficial es Tailscale, con la misma cuenta.'
    Write-Host 'Cuando ambos esten conectados, vuelve a ejecutar este script.'
    exit 2
}

& $tailscale serve --bg --https=443 http://127.0.0.1:5173
if ($LASTEXITCODE -ne 0) {
    Write-Host 'Tailscale no pudo publicar el puerto 5173. Revisa que la sesion este iniciada y que HTTPS este activo en la consola de Tailscale.'
    exit $LASTEXITCODE
}

& "$PSScriptRoot\direccion-https.ps1"
exit $LASTEXITCODE
