<#
.SYNOPSIS
  Ejecuta un playbook de Ansible de ControlVet desde Windows.

.DESCRIPTION
  Ansible no corre de forma nativa en Windows: este script lo ejecuta en
  WSL2 (Ubuntu-24.04), que administra XAMPP mediante /mnt/c y los
  ejecutables de Windows.

.EXAMPLE
  .\ops.ps1 status   -Entorno produccion
  .\ops.ps1 backup   -Entorno produccion
  .\ops.ps1 recover  -Entorno produccion
  .\ops.ps1 rollback -Entorno produccion
  .\ops.ps1 deploy   -Entorno produccion -Version 1.1.7
  .\ops.ps1 monitoreo
#>
param(
    [Parameter(Mandatory = $true)]
    [ValidateSet('deploy', 'rollback', 'backup', 'restore', 'recover', 'status', 'monitoreo')]
    [string]$Playbook,
    [ValidateSet('staging', 'produccion')][string]$Entorno = 'produccion',
    [string]$Version,
    [string]$Distro = 'Ubuntu-24.04',
    [string[]]$ExtraVars = @()
)

$env:WSL_UTF8 = '1'
$repo = (wsl -d $Distro -u root -- wslpath -a "$PSScriptRoot").Trim()

$vars = "-e cv_entorno=$Entorno"
if ($Version) { $vars += " -e cv_version=$Version" }
foreach ($v in $ExtraVars) { $vars += " -e $v" }

wsl -d $Distro -u root -- bash -lc "cd '$repo/infra/ansible' && ansible-playbook -i inventarios/local.yml playbooks/$Playbook.yml $vars"
exit $LASTEXITCODE
