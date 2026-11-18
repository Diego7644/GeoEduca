# Muestra la URL HTTPS privada. No imprime credenciales ni modifica la tailnet.
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
    Write-Host 'Todavia no hay direccion HTTPS: Tailscale no esta instalado.'
    exit 2
}

$status = & $tailscale status --json | ConvertFrom-Json
$dns = [string]$status.Self.DNSName
$dns = $dns.Trim().TrimEnd('.')
if (-not $dns) {
    Write-Host 'Tailscale esta instalado, pero esta maquina no tiene nombre DNS. Inicia sesion en Tailscale y activa MagicDNS.'
    exit 3
}

Write-Host "https://$dns"
Write-Host 'Esa direccion solo responde dentro de tu tailnet. No ejecutes tailscale funnel.'
exit 0
