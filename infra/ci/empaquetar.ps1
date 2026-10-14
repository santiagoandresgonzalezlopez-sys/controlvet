<#
.SYNOPSIS
  BUILD de ControlVet sin contenedores: genera el artefacto versionado
  dist\controlvet-<versión>.zip (+ .sha256) listo para desplegar en XAMPP.

.DESCRIPTION
  - Copia solo lo necesario en tiempo de ejecución (sin pruebas, docs ni secretos).
  - Instala las dependencias de producción de cada microservicio con Composer
    (--no-dev, autoload optimizado): el paquete no necesita Internet al desplegar.
  - Escribe la versión completa en VERSION (la expone /health de cada servicio).

.EXAMPLE
  .\infra\ci\empaquetar.ps1 -Version 1.1.7
#>
param(
    [Parameter(Mandatory = $true)][ValidatePattern('^\d+\.\d+\.\d+$')][string]$Version,
    [string]$Raiz = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path,
    [string]$Salida = (Join-Path (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path 'dist'),
    [string]$Composer = 'composer'
)

$ErrorActionPreference = 'Stop'
$servicios = 'auth', 'pacientes', 'citas', 'clinica', 'inventario', 'documentos'
$trabajo = Join-Path $Raiz "build\controlvet-$Version"

if (Test-Path $trabajo) { Remove-Item $trabajo -Recurse -Force }
New-Item -ItemType Directory -Force $trabajo, $Salida | Out-Null

function Copiar([string]$origen, [string]$destino, [string[]]$excluir = @()) {
    New-Item -ItemType Directory -Force (Split-Path $destino) | Out-Null
    if (Test-Path $origen -PathType Container) {
        robocopy $origen $destino /E /NFL /NDL /NJH /NJS /NP /XF $excluir /XD vendor tests .phpunit.cache | Out-Null
        if ($LASTEXITCODE -ge 8) { throw "robocopy falló copiando $origen" }
    } else {
        Copy-Item $origen $destino
    }
}

# Raíz publicada y kernel (necesario para instalar las dependencias de los servicios).
Copiar (Join-Path $Raiz '.htaccess') (Join-Path $trabajo '.htaccess')
Copiar (Join-Path $Raiz 'libs\kernel') (Join-Path $trabajo 'libs\kernel')
Copiar (Join-Path $Raiz 'infra\xampp') (Join-Path $trabajo 'infra\xampp') @('*.env')
Copiar (Join-Path $Raiz 'web') (Join-Path $trabajo 'web') @('web.php', 'web.env')
Set-Content -Path (Join-Path $trabajo 'VERSION') -Value $Version -Encoding ascii -NoNewline

foreach ($s in $servicios) {
    $origen = Join-Path $Raiz "services\$s"
    $destino = Join-Path $trabajo "services\$s"
    foreach ($d in 'controller', 'validador', 'microservicio', 'public', 'database') {
        Copiar (Join-Path $origen $d) (Join-Path $destino $d)
    }
    foreach ($f in 'composer.json', 'composer.lock') { Copiar (Join-Path $origen $f) (Join-Path $destino $f) }
    New-Item -ItemType Directory -Force (Join-Path $destino 'config') | Out-Null

    & $Composer install -d $destino --no-dev --no-interaction --no-progress --prefer-dist --classmap-authoritative --quiet
    if ($LASTEXITCODE -ne 0) { throw "composer install falló en $s" }
}

$zip = Join-Path $Salida "controlvet-$Version.zip"
if (Test-Path $zip) { Remove-Item $zip -Force }
# tar.exe (bsdtar de Windows) genera ZIP con separadores "/": Compress-Archive de
# PowerShell 5.1 usa "\" y el paquete no se extraería bien desde Linux (Ansible/WSL).
& "$env:SystemRoot\System32\tar.exe" -a -c -f $zip -C $trabajo .
if ($LASTEXITCODE -ne 0) { throw 'No fue posible crear el ZIP' }
$hash = (Get-FileHash $zip -Algorithm SHA256).Hash.ToLower()
# Formato de sha256sum (verificable desde Ansible en WSL).
Set-Content -Path "$zip.sha256" -Value "$hash  controlvet-$Version.zip" -Encoding ascii -NoNewline

Remove-Item $trabajo -Recurse -Force
"Artefacto: $zip ($([math]::Round((Get-Item $zip).Length / 1MB, 2)) MB) - SHA-256 $hash"
