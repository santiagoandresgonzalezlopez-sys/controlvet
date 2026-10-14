# MONITOR · Supervisión con Nagios

> Página de Confluence: **ControlVet › Monitor**. Responsable: Sebastian Gomez (suplente: Santiago Gonzalez).

## Despliegue

Nagios Core 4.5 (imagen `manios/nagios` + configuración propia en `infra/nagios/`) corre en la plataforma (`infra/compose.platform.yaml`) y se publica en `http://localhost:8092/nagios` (usuario y contraseña en `infra/.env.platform`, no versionado). La configuración se valida con `nagios -v` al construir la imagen. Ansible conecta Nagios a la red interna `controlvet_backend` en cada despliegue para que pueda alcanzar servicios no publicados.

## Qué se supervisa

| Host (contenedor) | Comprobación | Comando | Umbrales |
|---|---|---|---|
| auth, pacientes, citas, clinica, inventario, documentos | Disponibilidad del host | `check_tcp :80` | DOWN si no conecta |
| cada microservicio | **API salud**: `GET /health` → 200 y `"status":"ok"` (incluye conexión a su esquema) | `check_http` | WARNING > 1 s · CRITICAL > 3 s, error HTTP o estado ≠ ok |
| web | **Login web** (`/index.php?route=login` contiene "ControlVet") | `check_http` | WARNING > 2 s · CRITICAL > 5 s |
| web | **Dependencias del frontend** (`/health.php`: alcanza los 6 servicios) | `check_http` | CRITICAL si alguna dependencia falla |
| db | Puerto 3306 y **MariaDB conexion** con el usuario `nagios_mon` (solo USAGE) | `check_tcp`, `check_mysql` | CRITICAL si no conecta |

Frecuencia: cada 60 s; tras 2 fallos consecutivos el estado pasa a HARD y se notifica (detección en 1–2 minutos). Contactos: `santiago.gonzalez`, `sebastian.gomez` (grupo `controlvet-ops`).

## Alertas

Cada notificación (`PROBLEM`, `RECOVERY`) se escribe en `/opt/nagios/var/controlvet-alertas.log` con fecha, host, servicio, estado y salida del plugin:

```bash
docker exec controlvet-nagios cat /opt/nagios/var/controlvet-alertas.log
```

El envío por correo o chat no está configurado (no hay servidor de correo en el entorno; tarea CV-42).

## Investigación de incidentes

1. **Identificar**: panel *Services* de Nagios o el registro de alertas → host y servicio afectados.
2. **Diagnosticar**:
   - `docker ps -a --filter label=com.docker.compose.project=controlvet` (estado y salud),
   - `docker logs --tail 50 controlvet-<servicio>-1` (logs JSON con `request_id`),
   - `docker exec controlvet-web-1 curl -s http://<servicio>/health` (¿falla la base de datos?).
3. **Corregir**: `ansible-playbook playbooks/recover.yml` (reinicio verificado); si la causa es una versión defectuosa, `rollback.yml`; si son datos, `restore.yml`.
4. **Verificar**: Nagios registra `RECOVERY`; `status.yml` muestra todos los contenedores *healthy*.
5. **Registrar**: incidencia en Jira con causa, hora de detección (alerta), hora de recuperación y acción.
