<#
.SYNOPSIS
  Etapa "Validar" del pipeline: sintaxis PHP, composer.json válidos y control
  de secretos versionados. Se puede ejecutar igual en local o en Jenkins.

.EXAMPLE
  .\infra\ci\validar.ps1
#>
param(
    [string]$Raiz = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path,
    [string]$Php = 'C:\xampp\php\php.exe',
    [string]$Reporte = ''
)

# Los programas externos (php, composer, git) escriben avisos en stderr; los
# errores se detectan por su código de salida ($LASTEXITCODE), no por stderr.
$ErrorActionPreference = 'Continue'
Push-Location $Raiz
try {
    Write-Host '== Sintaxis PHP (php -l) =='
    $archivos = Get-ChildItem libs, services, web, tests, infra -Recurse -Filter *.php |
        Where-Object { $_.FullName -notmatch '\\vendor\\' }
    $errores = 0
    foreach ($f in $archivos) {
        $salida = & $Php -l $f.FullName 2>&1
        if ($LASTEXITCODE -ne 0) { $errores++; Write-Host $salida }
    }
    $resumen = "$($archivos.Count) archivos PHP revisados, $errores con errores"
    Write-Host $resumen
    if ($Reporte) { $resumen | Set-Content $Reporte -Encoding utf8 }
    if ($errores -gt 0) { throw 'Errores de sintaxis PHP' }

    Write-Host '== composer validate =='
    foreach ($c in 'libs/kernel', 'services/auth', 'services/pacientes', 'services/citas', 'services/clinica', 'services/inventario', 'services/documentos', 'tests/e2e') {
        composer validate --no-check-publish --no-interaction -d $c
        if ($LASTEXITCODE -ne 0) { throw "composer.json inválido en $c" }
    }

    Write-Host '== Control de secretos versionados =='
    $prohibidos = git ls-files | Where-Object { $_ -match '(^|/)(\.env|servicio\.php|web\.php|bd-root\.env|monitor\.env|jenkins\.env)$' }
    if ($prohibidos) { throw "Archivos de secretos versionados: $prohibidos" }

    # Asignaciones de secretos con un valor literal largo (no variables ni ejemplos).
    $patron = 'BEGIN (RSA|OPENSSH|EC) PRIVATE KEY|(JWT_SECRET|DB_PASSWORD|DB_ROOT_PASSWORD|JENKINS_ADMIN_PASSWORD)[''"]?[ ]*(=|=>)[ ]*[''"]?[A-Za-z0-9_-]{20,}'
    $hallazgos = git grep -nE $patron -- . ':!*.example'
    if ($LASTEXITCODE -eq 0) { Write-Host $hallazgos; throw 'Posible secreto en el repositorio' }
    Write-Host 'Sin secretos detectados'
} finally {
    Pop-Location
}
