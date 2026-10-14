<#
.SYNOPSIS
  Ejecuta las pruebas de integración, API, aislamiento y regresión contra los
  microservicios publicados en XAMPP (sin Docker).

.EXAMPLE
  .\tests\e2e\ejecutar.ps1
  .\tests\e2e\ejecutar.ps1 -BaseUrl http://localhost/cv-staging -Suite smoke
#>
param(
    [string]$BaseUrl = 'http://localhost/controlvet',
    [string]$Raiz = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path,
    [string]$Suite = '',
    [string]$Junit = ''
)

$ErrorActionPreference = 'Stop'
$servicios = 'auth', 'pacientes', 'citas', 'clinica', 'inventario', 'documentos'

foreach ($s in $servicios) {
    [Environment]::SetEnvironmentVariable(($s.ToUpper() + '_URL'), "$BaseUrl/api/$s")
    # Credenciales de BD de cada servicio (para la prueba de aislamiento de esquemas).
    $config = Join-Path $Raiz "services\$s\config\servicio.php"
    foreach ($l in Get-Content $config) {
        if ($l -match "^\s*'(DB_PASSWORD|DB_USER|DB_NAME|DB_HOST|DB_PORT)'\s*=>\s*'([^']*)'") {
            $nombre = if ($Matches[1] -in 'DB_PASSWORD', 'DB_USER', 'DB_NAME') { $s.ToUpper() + '_' + $Matches[1] } else { $Matches[1] }
            [Environment]::SetEnvironmentVariable($nombre, $Matches[2])
        }
    }
}
$env:WEB_URL = "$BaseUrl/web"

$argsPhpunit = @('vendor/bin/phpunit', '--testdox')
if ($Suite) { $argsPhpunit += @('--testsuite', $Suite) }
if ($Junit) { $argsPhpunit += @('--log-junit', $Junit) }

Push-Location $PSScriptRoot
try {
    & C:\xampp\php\php.exe @argsPhpunit
    exit $LASTEXITCODE
} finally {
    Pop-Location
}
