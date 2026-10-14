<#
.SYNOPSIS
  Instala (si hace falta) e inicia Jenkins NATIVO en Windows para ControlVet.

.DESCRIPTION
  - Jenkins LTS (WAR) con un JDK 21 portable en C:\jenkins (no modifica el Java del sistema).
  - Plugins con la herramienta oficial jenkins-plugin-manager (infra\jenkins\plugins.txt).
  - Configuración como código (infra\jenkins\casc.yaml): usuario administrador y job.
  - Clave del administrador aleatoria, guardada fuera del repositorio en
    C:\xampp\controlvet-secretos\jenkins.env.
  - Proceso desacoplado (WMI): sigue activo al cerrar la consola. http://localhost:8091

.EXAMPLE
  .\infra\jenkins\iniciar.ps1
  .\infra\jenkins\iniciar.ps1 -GitUrl https://github.com/<usuario>/controlvet.git
#>
param(
    [string]$Base = 'C:\jenkins',
    [string]$GitUrl = 'file:///C:/xampp/htdocs/controlvet',
    [int]$Puerto = 8091,
    # Origen directo de los plugins (evita espejos que no responden).
    [string]$DescargaPlugins = 'https://archives.jenkins.io/plugins'
)

$ErrorActionPreference = 'Stop'
$java = Join-Path $Base 'jdk-21\bin\java.exe'
$war = Join-Path $Base 'jenkins.war'
$home_ = Join-Path $Base 'home'
$secretos = 'C:\xampp\controlvet-secretos'
$credenciales = Join-Path $secretos 'jenkins.env'

foreach ($req in $java, $war, (Join-Path $Base 'jenkins-plugin-manager.jar')) {
    if (-not (Test-Path $req)) { throw "Falta $req (ver docs/operacion.md, sección Jenkins)" }
}
if (Get-NetTCPConnection -State Listen -LocalPort $Puerto -ErrorAction SilentlyContinue) { "Jenkins ya está activo en http://localhost:$Puerto"; return }

New-Item -ItemType Directory -Force $home_, $secretos | Out-Null
if (-not (Test-Path $credenciales)) {
    $bytes = New-Object byte[] 18; [Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
    $clave = [Convert]::ToBase64String($bytes) -replace '[+/=]', 'x'
    Set-Content $credenciales "# Credenciales de Jenkins (NO versionar)`nJENKINS_ADMIN_USER=admin`nJENKINS_ADMIN_PASSWORD=$clave" -Encoding ascii
}
$cred = @{}
Get-Content $credenciales | Where-Object { $_ -match '^([A-Z_]+)=(.*)$' } | ForEach-Object { $cred[$Matches[1]] = $Matches[2] }

$pluginsDir = Join-Path $home_ 'plugins'
if (-not (Test-Path (Join-Path $pluginsDir 'configuration-as-code.jpi'))) {
    'Instalando plugins de Jenkins...'
    $env:JENKINS_UC_DOWNLOAD_URL = $DescargaPlugins
    & $java -jar (Join-Path $Base 'jenkins-plugin-manager.jar') --war $war --plugin-download-directory $pluginsDir --plugin-file (Join-Path $PSScriptRoot 'plugins.txt') | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'No fue posible instalar los plugins' }
}

# Variables para Jenkins y JCasC (solo para este proceso).
$vars = @{
    JENKINS_HOME           = $home_
    CASC_JENKINS_CONFIG    = (Join-Path $PSScriptRoot 'casc.yaml')
    JENKINS_ADMIN_USER     = $cred['JENKINS_ADMIN_USER']
    JENKINS_ADMIN_PASSWORD = $cred['JENKINS_ADMIN_PASSWORD']
    CONTROLVET_GIT_URL     = $GitUrl
}
$set = ($vars.GetEnumerator() | ForEach-Object { "set `"$($_.Key)=$($_.Value)`"" }) -join ' && '
$log = Join-Path $Base 'jenkins.log'
$opts = '-Djenkins.install.runSetupWizard=false -Dhudson.plugins.git.GitSCM.ALLOW_LOCAL_CHECKOUT=true -Dfile.encoding=UTF-8'
$cmd = "cmd /c `"$set && `"$java`" $opts -jar `"$war`" --httpPort=$Puerto --httpListenAddress=127.0.0.1 >> `"$log`" 2>&1`""
Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{ CommandLine = $cmd; CurrentDirectory = $Base } | Out-Null

for ($i = 0; $i -lt 120 -and -not (Get-NetTCPConnection -State Listen -LocalPort $Puerto -ErrorAction SilentlyContinue); $i++) { Start-Sleep 1 }
if (-not (Get-NetTCPConnection -State Listen -LocalPort $Puerto -ErrorAction SilentlyContinue)) { throw "Jenkins no arrancó; revise $log" }
"Jenkins activo en http://localhost:$Puerto (usuario $($cred['JENKINS_ADMIN_USER']); clave en $credenciales). Origen del código: $GitUrl"
