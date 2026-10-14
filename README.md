# ControlVet · Sistema veterinario con microservicios y DevOps

Proyecto académico de **Santiago Gonzalez** y **Sebastian Gomez**. ControlVet v1.0.0 era un monolito PHP/MVVM sobre XAMPP (etiqueta `v1.0.0-monolito`). La v1.1 lo divide en microservicios autónomos y añade un proceso DevOps verificable de 8 fases.

| Fase | Herramienta | Dónde |
|---|---|---|
| Plan | Jira | `docs/jira/` (backlog CSV importable) |
| Code | Confluence + Git (GitHub solo como VCS) | `docs/`, `services/*/README.md` |
| Build | Composer + Docker (Maven no aplica a PHP, ver ADR-005) | `composer.json`, `*/Dockerfile` |
| Test | PHPUnit | `*/tests/Unit`, `tests/e2e` |
| Release | Jenkins | `Jenkinsfile`, `infra/jenkins/` |
| Deploy | Docker / Docker Compose | `compose.yaml` |
| Operate | Ansible | `infra/ansible/` |
| Monitor | Nagios | `infra/nagios/` |

Estado verificado de cada fase: [`docs/matriz-verificacion.md`](docs/matriz-verificacion.md).

## Estructura

```
web/                 frontend MVVM original (vistas, ViewModels); los modelos consumen las APIs
services/<servicio>/ auth · pacientes · citas · clinica · inventario · documentos
    controller/  validador/  microservicio/  public/  database/  tests/  Dockerfile
libs/kernel/         infraestructura transversal (HTTP, JWT, logs, salud) sin lógica de negocio
tests/e2e/           pruebas de integración, API, aislamiento y regresión
infra/               db, jenkins, ci, ansible, nagios, compose.platform.yaml
docs/                arquitectura, ADR, convenciones, runbook, monitoreo, Jira, Confluence
```

## Puesta en marcha

Requisitos: Docker Desktop (8 GB), Git. XAMPP ya no es necesario para la v1.1.

```bash
# 1. Plataforma DevOps (una vez): copiar infra/.env.platform.example a infra/.env.platform y definir contraseñas
docker compose -f infra/compose.platform.yaml --env-file infra/.env.platform up -d --build
```

2. Jenkins (`http://localhost:8091`) ejecuta el job `controlvet-pipeline`: pruebas, imágenes versionadas, publicación en `localhost:5000` y despliegue con Ansible.
3. Aplicación: `http://localhost:8090` · Nagios: `http://localhost:8092/nagios`.

Desarrollo manual sin Jenkins: copiar `.env.example` a `.env`, completar los secretos y ejecutar `docker compose up -d --build`.

Pruebas unitarias de un componente:

```bash
cd services/citas && composer install && vendor/bin/phpunit
```

## Usuarios de demostración (solo datos semilla de laboratorio)

administrador, recepcionista, veterinario y cliente; credenciales en `services/auth/database/002_seed.sql` y visibles en el login solo con `APP_ENV=dev`.
