# Matriz de verificación DevOps · ControlVet v1.1

Fecha de verificación: 2026-10-14. Entorno: Windows 11 + Docker Desktop 29.8 (8 GB), un único equipo.

**Niveles de estado:** *Planificada* (documentada, sin ejecutar) · *Configurada* (archivos listos, sin ejecución) · *Ejecutada* (se corrió) · *Verificada* (se corrió y se comprobó el resultado esperado con evidencia).

Evidencias en `docs/evidencias/` y en el historial de Jenkins (`http://localhost:8091/job/controlvet-pipeline/`).

| Fase | Herramienta | Tarea | Responsable | Comando / procedimiento | Resultado | Estado | Evidencia |
|---|---|---|---|---|---|---|---|
| Plan | Jira | Backlog con épicas por fase, historias, tareas, defectos, prioridades, puntos, responsables y dependencias | Santiago Gonzalez | Generación de `backlog-controlvet.csv` (importación CSV de Jira) | 8 épicas, 44 incidencias (24/20 por responsable, 117 puntos), 9 defectos reales | **Configurada** (no importada: sin acceso a Jira) | `docs/jira/` |
| Plan | Jira | Crear proyecto CV, tablero y seguimiento | Santiago Gonzalez / Sebastian Gomez | Importar CSV (CV-1, CV-2) | Pendiente | **Planificada** | `docs/jira/README.md` |
| Code | Git | Línea base y control de versiones | Santiago Gonzalez | `git init`, etiqueta `v1.0.0-monolito`, commits convencionales | Historial con la migración completa | **Verificada** | `git log`, `git tag` |
| Code | GitHub | Repositorio remoto (solo VCS) | Santiago Gonzalez | Crear repo y `git push` (CV-16) | Sin remoto: requiere cuenta y autorización del equipo | **Planificada** | ADR-007 |
| Code | Confluence | Arquitectura, convenciones, contratos API, ADR, runbook | Sebastian Gomez | Redacción en Markdown listo para Confluence | 13 páginas preparadas | **Configurada** (no publicadas: sin acceso) | `docs/confluence/README.md` |
| Code | PHP | 6 microservicios autónomos con `controller/ validador/ microservicio/` | Ambos | Ver `docs/arquitectura.md` | Esquema y usuario de BD propios, `/health`, logs JSON, Dockerfile y pruebas por servicio | **Verificada** | `services/*`, pruebas e2e |
| Build | Composer | Dependencias reproducibles | Santiago Gonzalez | `composer validate` + `composer install` (etapas Jenkins) | 9 `composer.json`/`.lock` válidos | **Verificada** | Jenkins #2/#4 |
| Build | Maven | Build de componentes Java | Santiago Gonzalez | — | No aplica: no existe código Java (ADR-005) | **No aplica** (decisión documentada) | `docs/adr/README.md` |
| Build | Docker | Imágenes versionadas multi-etapa | Sebastian Gomez | `docker compose build` en Jenkins | 8 imágenes de aplicación + `ops`, etiqueta `1.1.<build>` | **Verificada** | `reports/imagenes.txt` |
| Test | PHPUnit | Unitarias (validadores y reglas de negocio) | Sebastian Gomez | `vendor/bin/phpunit` por componente (Jenkins) | 93 pruebas OK (kernel 19, auth 14, pacientes 17, citas 18, clinica 10, inventario 8, documentos 7) | **Verificada** | `junit-unit-*.xml` |
| Test | PHPUnit | Cobertura | Sebastian Gomez | `--coverage-text` con pcov | Líneas: 30–80 % por componente (controladores/PDO cubiertos por e2e, no por unitarias) | **Ejecutada** | `docs/evidencias/cobertura-1.1.2.txt` |
| Test | PHPUnit | Integración BD, API REST, autenticación/autorización, aislamiento, citas, mascotas, consultas, regresión web | Santiago Gonzalez | Stack efímero `cv-ci-<build>` + contenedor e2e (Jenkins) | 87 pruebas OK | **Verificada** | `docs/evidencias/e2e-1.1.2.txt` |
| Test | PHPUnit | Registro de defectos | Ambos | Defectos encontrados por pruebas/revisiones → Jira | 9 defectos registrados y corregidos (CV-24…CV-30, CV-43, CV-44) | **Verificada** | `backlog-controlvet.csv` |
| Release | Jenkins | Jenkins como código (usuario, job, polling SCM) | Santiago Gonzalez | `docker compose -f infra/compose.platform.yaml up -d --build` | Job creado sin pasos manuales; builds #3–#5 iniciados por "SCM change" | **Verificada** | API de Jenkins |
| Release | Jenkins | Pipeline declarativo con gates | Santiago Gonzalez | `Jenkinsfile` | #1 fallo (ruta agente), #2 éxito, #3 detenido por el control de secretos (correcto), #4 éxito | **Verificada** | `jenkins-build-2-console.txt` (redactado) |
| Release | Jenkins + registry | Versiones, notas y artefactos | Sebastian Gomez | Etapa Release | Imágenes en `localhost:5000`, notas de versión, manifiesto, tarball | **Verificada** | `RELEASE_NOTES-1.1.2.md` |
| Deploy | Docker Compose | Red interna, puertos mínimos, persistencia, salud | Sebastian Gomez | `compose.yaml` | Solo `web` publica (8090); 8 contenedores *healthy*; volumen `controlvet_db_data` | **Verificada** | `docker ps`, Nagios |
| Deploy | Docker + Ansible | Despliegue de versión identificada | Santiago Gonzalez | Etapa Deploy → `deploy.yml -e cv_version=…` | 1.1.2 y 1.1.4 desplegadas y verificadas (versión en `/health`) | **Verificada** | `history.log` |
| Operate | Ansible | Ejecución en Linux (no nativo en Windows) | Santiago Gonzalez | Imagen `controlvet/ops` (Alpine, ansible-core 2.17) + `ops.ps1` | Playbooks ejecutados desde Windows vía contenedor | **Verificada** | `docs/evidencias/ansible-*.txt` |
| Operate | Ansible | Preparación y secretos | Santiago Gonzalez | `tasks/preparar.yml` (en cada playbook) | Secretos aleatorios persistentes 0600; credencial de monitoreo solo para grupo nagios | **Verificada** | — |
| Operate | Ansible | Respaldo / restauración | Sebastian Gomez | `ops.ps1 backup` / `ops.ps1 restore` | Volcado con 6 esquemas validado; restauración con verificación OK | **Verificada** | `ansible-backup.txt`, `ansible-restore.txt` |
| Operate | Ansible | Reinicio y recuperación | Santiago Gonzalez | `ops.ps1 recover` | Detectó y reinició `inventario`; entorno verificado | **Verificada** | `ansible-recover.txt` |
| Operate | Ansible | Rollback manual | Santiago Gonzalez | `ops.ps1 rollback` y `ops.ps1 deploy -Version 1.1.4` | 1.1.4 → 1.1.2 → 1.1.4, versiones verificadas | **Verificada** | `ansible-rollback.txt`, `ansible-redeploy.txt` |
| Operate | Ansible | Rollback automático ante despliegue fallido | Santiago Gonzalez | `deploy.yml -e cv_version=9.9.9` | v1.1.4: falló (CV-44). Corregido en 1.1.5: ver fila siguiente | **Ejecutada** (defecto encontrado) | `ansible-deploy-fallido-rollback.txt` |
| Operate | Ansible | Reprueba del rollback automático | Santiago Gonzalez | `deploy.yml -e cv_version=9.9.9` con ops 1.1.5 | PENDIENTE_V2 | PENDIENTE_V2_ESTADO | `ansible-deploy-fallido-rollback-v2.txt` |
| Monitor | Nagios | Configuración validada | Sebastian Gomez | `nagios -v` en el build de la imagen | 0 errores, 0 avisos (detectó y bloqueó una configuración inválida) | **Verificada** | `infra/nagios/` |
| Monitor | Nagios | Disponibilidad, endpoints de salud, BD, errores HTTP, umbrales | Sebastian Gomez | 8 hosts + 11 servicios, cada 60 s, HARD tras 2 fallos | 8 UP y 11 OK con el stack sano | **Verificada** | `nagios-estado.txt` |
| Monitor | Nagios | Alerta por fallo controlado y acción correctiva | Ambos | `docker stop controlvet-inventario-1` → alerta → `ops.ps1 recover` | Fallo 17:57:15Z · PROBLEM 17:58:57Z (inventario DOWN, frontend CRITICAL 503) · RECOVERY 17:59:57Z | **Verificada** | `prueba-alerta.txt`, `nagios-alertas.log` |
| Monitor | Nagios | Alertas por correo/chat | Sebastian Gomez | — (CV-42) | Solo registro en archivo; no hay servidor de correo | **Planificada** | `docs/monitoreo.md` |

## Limitaciones y pendientes

1. **Jira y Confluence**: sin acceso desde este entorno. El contenido está preparado para importarlo/publicarlo; no se afirma que la integración esté activa.
2. **GitHub**: el repositorio es local; Jenkins usa un espejo de solo lectura (`file:///repo`). Basta con crear el remoto y cambiar `CONTROLVET_GIT_URL` en `infra/.env.platform`. No se usan Actions, Projects ni Packages.
3. **Maven**: no aplica a un proyecto 100 % PHP (ADR-005). Si la rúbrica exige demostrarlo, hay que confirmar con el docente un componente Java con necesidad real.
4. **Ansible** se ejecuta en un contenedor Linux sobre el mismo equipo; el inventario para una VM remota existe pero no se ejecutó.
5. **Base de datos**: un servidor MariaDB con un esquema y usuario por servicio (ADR-002), no un servidor por servicio.
6. **JWT HS256 compartido** entre servicios (ADR-004); migración a RS256 pendiente (CV-41).
7. **Jenkins** accede al socket Docker del equipo (equivale a root sobre Docker): aceptable solo en laboratorio local.
8. **Cobertura unitaria** moderada (30–80 %); las capas HTTP y PDO se cubren con las 87 pruebas de integración.
9. El modo XAMPP del monolito ya no se usa en v1.1 (sigue disponible en la etiqueta `v1.0.0-monolito`).
