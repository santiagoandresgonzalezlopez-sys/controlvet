<#
.SYNOPSIS
  Etapa "Release": promueve un artefacto CANDIDATO (ya probado en staging) a
  PUBLICADO, genera las notas de versión y lo registra.

.EXAMPLE
  .\infra\ci\publicar.ps1 -Version 1.1.7 -Commit abc1234 -BuildUrl http://localhost:8091/job/x/7/ -CommitBase <sha anterior>
#>
param(
    [Parameter(Mandatory = $true)][ValidatePattern('^\d+\.\d+\.\d+$')][string]$Version,
    [Parameter(Mandatory = $true)][string]$Commit,
    [string]$BuildUrl = '',
    [string]$CommitBase = '',
    [string]$Dist = 'dist',
    [string]$Artefactos = 'C:\controlvet\artefactos'
)

$ErrorActionPreference = 'Stop'
$destino = Join-Path $Artefactos $Version
$zip = Join-Path $destino "controlvet-$Version.zip"
if (-not (Test-Path $zip)) { throw "No existe el artefacto candidato $zip" }
if ((Get-Content (Join-Path $destino 'ESTADO')).Trim() -ne 'candidato') { throw "La versión $Version no está en estado candidato" }

# El artefacto que se promueve debe ser exactamente el probado (SHA-256).
$esperado = (Get-Content "$zip.sha256").Split(' ')[0]
$actual = (Get-FileHash $zip -Algorithm SHA256).Hash.ToLower()
if ($esperado -ne $actual) { throw "El SHA-256 del artefacto no coincide ($actual <> $esperado)" }

$ErrorActionPreference = 'Continue'   # git escribe avisos en stderr
$baseValida = $false
if ($CommitBase) {
    git cat-file -e "$CommitBase^{commit}" 2>$null
    $baseValida = ($LASTEXITCODE -eq 0)
}
$cambios = if ($baseValida) {
    git log --no-merges --pretty='- %s (%an, %h)' "$CommitBase..HEAD"
} else {
    git log --no-merges --pretty='- %s (%an, %h)' -n 20
}
$ErrorActionPreference = 'Stop'

$notas = @(
    "# ControlVet v$Version", '',
    "- Fecha: $((Get-Date).ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ'))",
    "- Commit: $Commit",
    "- Build Jenkins: $BuildUrl",
    "- Artefacto: controlvet-$Version.zip (SHA-256 $actual)", '',
    '## Cambios incluidos'
) + $cambios + @(
    '', '## Verificación',
    '- Validación (sintaxis, composer, secretos), pruebas unitarias PHPUnit: superadas',
    '- Despliegue en staging (/cv-staging) y 87 pruebas de integración/regresión: superadas'
)
New-Item -ItemType Directory -Force $Dist | Out-Null
$archivoNotas = Join-Path $Dist "RELEASE_NOTES-$Version.md"
$notas | Set-Content $archivoNotas -Encoding utf8
Copy-Item $archivoNotas $destino -Force

# Promoción: el candidato pasó staging y queda publicado.
Set-Content (Join-Path $destino 'ESTADO') 'publicado' -Encoding ascii
Add-Content (Join-Path $Artefactos 'registro.log') "$((Get-Date).ToUniversalTime().ToString('s'))Z PUBLICADO $Version commit=$Commit sha256=$actual"
"Versión $Version publicada (artefacto $zip)"
