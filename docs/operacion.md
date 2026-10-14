# OPERATE · Runbook de operación con Ansible

> Página de Confluence: **ControlVet › Operate › Runbook**. Responsable: Santiago Gonzalez (suplente: Sebastian Gomez).

## Cómo se ejecuta Ansible en este equipo

El equipo es Windows 11 + Docker Desktop. **Ansible no se ejecuta de forma nativa en Windows** y no hay una distribución WSL con Ansible instalada. Por eso el controlador Ansible corre en la imagen Linux `controlvet/ops` (Alpine + ansible-core 2.17 + docker CLI), que:

- se construye y publica en cada release (`localhost:5000/controlvet/ops:<versión>`),
- administra el motor Docker del equipo por `/var/run/docker.sock` (inventario `local`, conexión `local`),
- guarda su estado en el volumen `controlvet_ops` (`/opt/controlvet`): secretos (0600), `controlvet.env`, versión actual/anterior, historial y respaldos.

El inventario `inventories/lab` deja preparado el uso contra una VM Linux por SSH (**planificado, no ejecutado**).

## Ejecución manual

```bash
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock -v controlvet_ops:/opt/controlvet localhost:5000/controlvet/ops:<versión> ansible-playbook playbooks/<playbook>.yml [-e var=valor]
```

Para dejar los respaldos en la carpeta `backups/` del proyecto se añade `-v "$PWD/backups:/backups"` y `-e cv_backup_dir=/backups`.

| Playbook | Para qué | Variables |
|---|---|---|
| `site.yml` | Verificar Docker/Compose, crear `/opt/controlvet`, generar secretos persistentes y `controlvet.env` | — |
| `deploy.yml` | Desplegar una versión publicada; verifica contenedores healthy, `/health` (con BD) y versión; **rollback automático** si falla | `cv_version` (obligatoria) |
| `rollback.yml` | Volver a la versión anterior (o a una concreta) y verificar | `cv_version` (opcional) |
| `backup.yml` | Volcado consistente de los 6 esquemas, comprimido, con validación y retención (7) | `cv_backup_dir` |
| `restore.yml` | Detener servicios, restaurar un volcado, reiniciar y verificar | `archivo` (opcional: el más reciente) |
| `recover.yml` | Detectar contenedores detenidos o *unhealthy*, reiniciarlos y verificar | — |
| `status.yml` | Versión, contenedores e historial de operación | — |

Todos registran sus acciones en `/opt/controlvet/releases/history.log` y Ansible en `/opt/controlvet/logs/ansible.log`.

## Procedimientos

### Despliegue normal
Lo ejecuta Jenkins en la etapa *Deploy* con la imagen `ops` de la misma versión. Resultado esperado: `failed=0` y la línea `DEPLOY <versión> OK` en el historial.

### Reversión
1. `ansible-playbook playbooks/rollback.yml` (anterior) o `-e cv_version=1.1.N`.
2. Comprobar en Nagios que todos los servicios vuelven a OK.
3. Registrar el motivo en Jira (incidencia tipo Bug).

### Respaldo y recuperación de datos
1. Respaldo diario: `backup.yml` (se puede programar en el Programador de tareas de Windows con el comando `docker run` anterior).
2. Restauración: `restore.yml -e archivo=/opt/controlvet/backups/<archivo>.sql.gz`. Los servicios se detienen durante la restauración para evitar escrituras.

### Reinicio y recuperación de servicios (respuesta a alertas)
1. Nagios registra `PROBLEM` (ver `docs/monitoreo.md`).
2. Ejecutar `recover.yml`: identifica los contenedores afectados, los reinicia y verifica salud y dependencias.
3. Confirmar el `RECOVERY` en Nagios y registrar la acción correctiva.

## Secretos

- Se generan una sola vez con `lookup('password')` en `/opt/controlvet/secrets` (aleatorios, 28–48 caracteres) y nunca se versionan.
- El `.env` de la raíz del repositorio es solo para desarrollo manual (`docker compose up`); el entorno gestionado por Ansible usa `/opt/controlvet/controlvet.env`.
- Si el volumen de datos se creó con otros secretos, los usuarios de BD no coincidirán: en ese caso respaldar, recrear el volumen y restaurar.
