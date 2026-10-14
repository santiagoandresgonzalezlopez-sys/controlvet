<#
.SYNOPSIS
  Mantiene activa la distribución WSL de ControlVet (Nagios y Ansible).

.DESCRIPTION
  WSL apaga una distribución cuando no tiene sesiones abiertas; Nagios
  dejaría de supervisar. Este script inicia un proceso desacoplado que la
  mantiene activa y comprueba que Nagios y su interfaz (8092) respondan.

.EXAMPLE
  .\infra\wsl\iniciar.ps1
#>
param([string]$Distro = 'Ubuntu-24.04')

$ErrorActionPreference = 'Stop'
$env:WSL_UTF8 = '1'
$activo = Get-CimInstance Win32_Process -Filter "Name='wsl.exe'" |
    Where-Object { $_.CommandLine -match [regex]::Escape($Distro) -and $_.CommandLine -match 'controlvet-keepalive' }

if (-not $activo) {
    Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{
        CommandLine = "wsl.exe -d $Distro -u root -- bash -c 'exec -a controlvet-keepalive sleep infinity'"
    } | Out-Null
    Start-Sleep 3
}

wsl -d $Distro -u root -- bash -c 'systemctl start nagios4 apache2 2>/dev/null || service nagios4 start; systemctl is-active nagios4 apache2'
"Nagios: http://localhost:8092/nagios4"
