<#
.SYNOPSIS
  Instancia MariaDB dedicada de ControlVet (sin Docker), con los binarios de XAMPP.

.DESCRIPTION
  Usa su propio directorio de datos (C:\xampp\controlvet-db) y el puerto 3307,
  separado del MySQL general de XAMPP (3306), para que los usuarios y
  privilegios por microservicio no dependan de otras bases del equipo.
  Solo escucha en 127.0.0.1. La clave de root se genera una vez y se guarda
  FUERA de htdocs, en C:\xampp\controlvet-secretos\bd-root.env.

.EXAMPLE
  .\infra\xampp\bd.ps1 iniciar
  .\infra\xampp\bd.ps1 estado
  .\infra\xampp\bd.ps1 detener
#>
param(
    [Parameter(Mandatory = $true)][ValidateSet('iniciar', 'detener', 'estado')][string]$Accion,
    [string]$DataDir = 'C:\xampp\controlvet-db',
    [int]$Puerto = 3307
)

$ErrorActionPreference = 'Stop'
$bin = 'C:\xampp\mysql\bin'
$secretos = 'C:\xampp\controlvet-secretos'
New-Item -ItemType Directory -Force $secretos | Out-Null
$rootEnv = Join-Path $secretos 'bd-root.env'
# Migración: versiones anteriores guardaban la clave dentro del proyecto.
$antiguo = Join-Path $PSScriptRoot 'bd-root.env'
if ((Test-Path $antiguo) -and -not (Test-Path $rootEnv)) { Move-Item $antiguo $rootEnv }
$ini = Join-Path $DataDir 'my.ini'

function Get-RootPass {
    if (-not (Test-Path $rootEnv)) {
        $bytes = New-Object byte[] 21; [Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
        $pass = ([Convert]::ToBase64String($bytes) -replace '[+/=]', 'x')
        Set-Content -Path $rootEnv -Encoding ascii -Value "# Clave root de la instancia MariaDB de ControlVet. NO versionar.`nDB_ROOT_PASSWORD=$pass"
    }
    ((Get-Content $rootEnv | Where-Object { $_ -like 'DB_ROOT_PASSWORD=*' }) -split '=', 2)[1]
}

function Test-Activa {
    $null -ne (Get-NetTCPConnection -State Listen -LocalPort $Puerto -ErrorAction SilentlyContinue)
}

switch ($Accion) {
    'iniciar' {
        if (Test-Activa) { "Instancia ControlVet ya activa en 127.0.0.1:$Puerto"; break }
        if (-not (Test-Path (Join-Path $DataDir 'mysql'))) {
            "Inicializando directorio de datos $DataDir ..."
            & "$bin\mysql_install_db.exe" "--datadir=$DataDir" "--port=$Puerto" "--password=$(Get-RootPass)" | Out-Null
            if ($LASTEXITCODE -ne 0) { throw 'mysql_install_db falló' }
            Add-Content -Path $ini -Encoding ascii -Value "bind-address=127.0.0.1`ncharacter-set-server=utf8mb4`ncollation-server=utf8mb4_unicode_ci"
        }
        # Proceso desacoplado (WMI): no termina cuando se cierra la consola que lo
        # lanzó. Un cierre brusco de mysqld daña las tablas Aria del sistema.
        $consola = Join-Path $DataDir 'mysqld-consola.log'
        Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{
            CommandLine      = "cmd /c `"`"$bin\mysqld.exe`" --defaults-file=`"$ini`" --standalone --console >> `"$consola`" 2>&1`""
            CurrentDirectory = $bin
        } | Out-Null
        for ($i = 0; $i -lt 30 -and -not (Test-Activa); $i++) { Start-Sleep -Milliseconds 500 }
        if (-not (Test-Activa)) { throw "La instancia no arrancó; revise $DataDir\*.err" }
        "Instancia ControlVet activa en 127.0.0.1:$Puerto"
    }
    'detener' {
        $env:MYSQL_PWD = Get-RootPass
        & "$bin\mysqladmin.exe" -uroot -h127.0.0.1 "-P$Puerto" shutdown
        Remove-Item Env:MYSQL_PWD
        "Instancia detenida"
    }
    'estado' {
        if (Test-Activa) { "ACTIVA en 127.0.0.1:$Puerto (datos: $DataDir)" } else { "DETENIDA" }
    }
}
