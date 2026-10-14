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
    $env = Join-Path $Raiz "services\$s\config\servicio.env"
    foreach ($l in Get-Content $env) {
        if ($l -match '^(DB_PASSWORD|DB_HOST|DB_PORT)=(.*)$') {
            $nombre = if ($Matches[1] -eq 'DB_PASSWORD') { $s.ToUpper() + '_DB_PASSWORD' } else { $Matches[1] }
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
