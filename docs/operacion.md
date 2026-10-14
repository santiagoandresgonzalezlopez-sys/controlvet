# OPERATE · Runbook de operación con Ansible (sin contenedores)

> Página de Confluence: **ControlVet › Operate › Runbook**. Responsable: Santiago Gonzalez (suplente: Sebastian Gomez).

## Componentes del entorno

| Componente | Dónde corre | Cómo se inicia |
|---|---|---|
| Frontend y 6 microservicios | Apache de XAMPP (Windows), puerto 80 | Panel de XAMPP o `recover.yml` |
| MariaDB de ControlVet | Binarios de XAMPP, `127.0.0.1:3307`, datos en `C:\xampp\controlvet-db` | `.\infra\xampp\bd.ps1 iniciar` |
| Jenkins (Release) | Java 21 portable en `C:\jenkins`, `http://localhost:8091` | `.\infra\jenkins\iniciar.ps1` |
| Ansible (Operate) y Nagios (Monitor) | WSL2 Ubuntu 24.04 | `.\infra\wsl\iniciar.ps1` |

**Ansible no se ejecuta de forma nativa en Windows.** Corre en WSL2 y administra Windows/XAMPP mediante la interoperabilidad de WSL: rutas `/mnt/c/...` y ejecutables de Windows (`php.exe`, `curl.exe`, `cmd.exe`, `mysqldump.exe`). Los procesos de Windows (MariaDB, Apache, Jenkins) se inician **desacoplados** (WMI), porque un cierre brusco daña las tablas Aria de MariaDB.

Preparación inicial de WSL (una vez): `wsl -d Ubuntu-24.04 -u root -- bash /mnt/c/xampp/htdocs/controlvet/infra/wsl/preparar.sh`.

## Ejecución

Desde Windows: `.\ops.ps1 <playbook> -Entorno staging|produccion [-Version X.Y.Z]`.

| Playbook | Para qué | Variables |
|---|---|---|
| `deploy.yml` | Verifica SHA-256 del artefacto, lo extrae en `C:\controlvet\releases\<entorno>\<versión>`, configura esquemas y usuarios, **cambia el enlace** `htdocs\cv` (o `cv-staging`), verifica `/health` de los 6 servicios (versión y BD), frontend y login. **Rollback automático** si falla | `cv_entorno`, `cv_version` |
| `rollback.yml` | Reactiva la versión anterior (o `cv_destino`) y la verifica | `cv_entorno`, `cv_destino` opcional |
| `backup.yml` | `mysqldump --single-transaction` de los 6 esquemas del entorno, comprimido, validado, con retención de 7 | `cv_entorno` |
| `restore.yml` | Restaura un respaldo (por defecto el último) y verifica | `cv_entorno`, `archivo` opcional |
| `recover.yml` | Diagnostica Apache, MariaDB y cada servicio; reinicia lo caído y verifica | `cv_entorno` |
| `status.yml` | Versión activa, salud de cada servicio e historial | `cv_entorno` |
| `monitoreo.yml` | Instala y configura Nagios en WSL y valida su configuración | — |

Estado operativo: `C:\controlvet\estado\<entorno>\` (`current`, `previous`, `history.log`). Artefactos: `C:\controlvet\artefactos\<versión>\` (`ESTADO` = candidato/publicado). Respaldos: `C:\controlvet\respaldos\`.

## Procedimientos

### Despliegue
Lo ejecuta Jenkins: staging con datos reiniciados → pruebas e2e → promoción → producción. Manual: `.\ops.ps1 deploy -Entorno produccion -Version 1.1.N`.

### Reversión
1. `.\ops.ps1 rollback -Entorno produccion` (o `-ExtraVars cv_destino=1.1.N`).
2. Confirmar en Nagios que todos los servicios vuelven a OK.
3. Registrar el motivo en Jira (incidencia tipo Bug).

### Respaldo y recuperación de datos
1. `.\ops.ps1 backup -Entorno produccion` (programable con el Programador de tareas de Windows).
2. `.\ops.ps1 restore -Entorno produccion [-ExtraVars archivo=/mnt/c/controlvet/respaldos/<archivo>]`.

### Respuesta a alertas (reinicio y recuperación)
1. Nagios registra `PROBLEM` (ver `docs/monitoreo.md`).
2. `.\ops.ps1 recover -Entorno produccion`: detecta si cayó Apache o la base de datos, la reinicia y verifica salud y dependencias.
3. Confirmar el `RECOVERY` en Nagios y registrar la acción en Jira.

## Secretos

Todos fuera del repositorio y de htdocs, en `C:\xampp\controlvet-secretos`: `bd-root.env` (root de la instancia), `entorno-<prefijo>.php` (claves de BD y JWT de cada entorno, compartidas por todas sus versiones para permitir el rollback), `monitor.env` (usuario `nagios_mon`, solo USAGE), `jenkins.env` y `nagios_admin`. La configuración generada en cada versión (`config/servicio.php`) es PHP: aunque se solicite por URL no muestra su contenido y además el `.htaccess` responde 403.
