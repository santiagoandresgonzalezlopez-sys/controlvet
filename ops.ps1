<#
.SYNOPSIS
  Ejecuta un playbook de Ansible de ControlVet desde Windows.

.DESCRIPTION
  Ansible no corre de forma nativa en Windows: este script lanza el
  controlador Ansible en el contenedor Linux "ops" de la versión indicada
  (por defecto, la desplegada actualmente) y monta la carpeta backups\.

.EXAMPLE
  .\ops.ps1 status
  .\ops.ps1 backup
  .\ops.ps1 recover
  .\ops.ps1 rollback
  .\ops.ps1 deploy -Version 1.1.3
#>
param(
    [Parameter(Mandatory = $true)]
    [ValidateSet('site', 'deploy', 'rollback', 'backup', 'restore', 'recover', 'status')]
    [string]$Playbook,
    [string]$Version,
    [string]$Registry = 'localhost:5000',
    [string[]]$ExtraVars = @()
)

$ErrorActionPreference = 'Stop'
$backups = Join-Path $PSScriptRoot 'backups'
New-Item -ItemType Directory -Force $backups | Out-Null

$opsVersion = $Version
if (-not $opsVersion) {
    $opsVersion = (docker run --rm -v controlvet_ops:/opt/controlvet alpine:3.20 cat /opt/controlvet/releases/current 2>$null)
    if (-not $opsVersion) { throw 'No hay versión desplegada registrada; indique -Version.' }
    $opsVersion = $opsVersion.Trim()
}

$vars = @('-e', 'cv_backup_dir=/backups', '-e', "cv_registry=$Registry")
if ($Version) { $vars += @('-e', "cv_version=$Version") }
foreach ($v in $ExtraVars) { $vars += @('-e', $v) }

# Ansible escribe avisos en stderr: no deben interrumpir el script.
$ErrorActionPreference = 'Continue'
docker run --rm `
    -v /var/run/docker.sock:/var/run/docker.sock `
    -v controlvet_ops:/opt/controlvet `
    -v "${backups}:/backups" `
    "$Registry/controlvet/ops:$opsVersion" `
    ansible-playbook "playbooks/$Playbook.yml" @vars
exit $LASTEXITCODE
