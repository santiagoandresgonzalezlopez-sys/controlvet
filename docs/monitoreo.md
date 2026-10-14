# MONITOR · Supervisión con Nagios (WSL2)

> Página de Confluence: **ControlVet › Monitor**. Responsable: Sebastian Gomez (suplente: Santiago Gonzalez).

## Despliegue

Nagios Core 4 (paquete `nagios4` de Ubuntu 24.04) corre en WSL2 y lo instala y configura Ansible (`playbooks/monitoreo.yml`): comandos, contactos, hosts y servicios de ControlVet, plugin propio de MariaDB, interfaz web con usuario y clave en `http://localhost:8092/nagios4` (usuario `nagiosadmin`; clave en `C:\xampp\controlvet-secretos\nagios_admin`). La configuración se valida con `nagios4 -v` en cada ejecución.

Sin contenedores, los microservicios son endpoints del Apache de XAMPP: cada uno se supervisa como un **servicio de Nagios independiente** sobre el host `xampp-apache`.

## Qué se supervisa (producción, `/cv`)

| Host | Servicio Nagios | Comprobación | Umbrales |
|---|---|---|---|
| xampp-apache (IP de Windows vista desde WSL) | disponibilidad | `check_tcp :80` | DOWN si no conecta |
| xampp-apache | **API auth … API documentos** (6) | `check_http /cv/api/<servicio>/health`: 200 y `"status":"ok"` (incluye la conexión del servicio a su esquema) | WARNING > 1 s · CRITICAL > 3 s, error HTTP o estado ≠ ok |
| xampp-apache | **Login web** | `/cv/web/index.php?route=login` contiene "ControlVet" | WARNING > 2 s · CRITICAL > 5 s |
| xampp-apache | **Dependencias del frontend** | `/cv/web/health.php` (alcanza los 6 servicios) | CRITICAL si alguna dependencia falla |
| mariadb-controlvet | **MariaDB conexion** | `mysqladmin.exe ping` (interoperabilidad WSL) con `nagios_mon`, solo USAGE | CRITICAL si no responde |

Frecuencia: cada 60 s; tras 2 fallos consecutivos el estado pasa a HARD y se notifica (detección en 1–2 minutos). Contactos: `santiago.gonzalez`, `sebastian.gomez` (grupo `controlvet-ops`).

## Alertas

Cada notificación (`PROBLEM`, `RECOVERY`) se registra en `/var/log/nagios4/controlvet-alertas.log` (WSL) con fecha, host, servicio, estado y salida del plugin:

```powershell
wsl -d Ubuntu-24.04 -u root -- cat /var/log/nagios4/controlvet-alertas.log
```

El envío por correo o chat no está configurado (no hay servidor de correo; tarea CV-42).

## Investigación de incidentes

1. **Identificar**: panel *Services* de Nagios o el registro de alertas.
2. **Diagnosticar**: `.\ops.ps1 status -Entorno produccion`; `curl.exe http://localhost/cv/api/<servicio>/health` (¿falla `checks.database`?); log de errores de Apache (`C:\xampp\apache\logs\error.log`, logs JSON de los servicios con `request_id`).
3. **Corregir**: `.\ops.ps1 recover -Entorno produccion` (Apache o MariaDB caídos); versión defectuosa → `rollback`; datos → `restore`.
4. **Verificar**: Nagios registra `RECOVERY` y `status` muestra todos los servicios en OK.
5. **Registrar**: incidencia en Jira con causa, hora de la alerta, hora de recuperación y acción.
