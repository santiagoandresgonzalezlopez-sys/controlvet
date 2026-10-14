# ControlVet · Sistema veterinario con microservicios y DevOps

Proyecto académico de **Santiago Gonzalez** y **Sebastian Gomez**. ControlVet v1.0.0 era un monolito PHP/MVVM sobre XAMPP (etiqueta `v1.0.0-monolito`). La v1.1 lo divide en **6 microservicios programados en PHP** (cada uno con `controller/`, `validador/` y `microservicio/`), que se ejecutan **de forma nativa en XAMPP, sin Docker**, y añade un proceso DevOps verificable de 8 fases.

| Fase | Herramienta | Dónde |
|---|---|---|
| Plan | Jira | `docs/jira/` (backlog CSV importable) |
| Code | Confluence + Git (GitHub solo como VCS) | `docs/`, `services/*/README.md`, `docs/endpoints.md` |
| Build | Composer + empaquetado ZIP versionado (Maven no aplica a PHP, ver ADR-005) | `composer.json`, `infra/ci/empaquetar.ps1` |
| Test | PHPUnit | `*/tests/Unit`, `tests/e2e` |
| Release | Jenkins (nativo en Windows) | `Jenkinsfile`, `infra/jenkins/` |
| Deploy | XAMPP (Apache + MariaDB) con Ansible | `.htaccess`, `infra/xampp/`, `infra/ansible/playbooks/deploy.yml` |
| Operate | Ansible (en WSL2, no corre nativo en Windows) | `infra/ansible/`, `ops.ps1` |
| Monitor | Nagios 4 (en WSL2) | `infra/nagios/`, `infra/ansible/playbooks/monitoreo.yml` |

Estado verificado de cada fase: [`docs/matriz-verificacion.md`](docs/matriz-verificacion.md). Endpoints: [`docs/endpoints.md`](docs/endpoints.md).

## Estructura

```
.htaccess            publica /api/<servicio>/ y bloquea código interno y secretos
web/                 frontend MVVM original (vistas, ViewModels); los modelos consumen las APIs
services/<servicio>/ auth · pacientes · citas · clinica · inventario · documentos
    controller/  validador/  microservicio/  public/  database/  config/  tests/
libs/kernel/         infraestructura transversal (HTTP, JWT, configuración, logs, salud)
tests/e2e/           pruebas de integración, API, aislamiento y regresión
infra/xampp/         instancia MariaDB dedicada (bd.ps1) e instalador (instalar.php)
infra/ci/            empaquetado del artefacto versionado
infra/jenkins/       Jenkins nativo: plugins, configuración como código, arranque
infra/ansible/       playbooks de despliegue, rollback, respaldo, recuperación y monitoreo
infra/nagios/        configuración y plugin de Nagios
infra/wsl/           preparación de WSL2 (Ansible y Nagios)
docs/                arquitectura, endpoints, ADR, convenciones, runbook, monitoreo, Jira, Confluence
```

## Puesta en marcha (desarrollo, sin Docker)

Requisitos: XAMPP (Apache + PHP 8.2 + MariaDB), Composer y Git.

```powershell
# 1. Instancia MariaDB dedicada de ControlVet (127.0.0.1:3307; no toca el MySQL general de XAMPP)
.\infra\xampp\bd.ps1 iniciar
# 2. Dependencias de cada microservicio
foreach ($c in 'libs/kernel','services/auth','services/pacientes','services/citas','services/clinica','services/inventario','services/documentos','tests/e2e') { composer install -d $c }
# 3. Esquemas, usuarios por servicio y configuración (secretos fuera de htdocs)
C:\xampp\php\php.exe infra\xampp\instalar.php
```

- Frontend: `http://localhost/controlvet/web/`
- Endpoints: `http://localhost/controlvet/api/<servicio>/...` (p. ej. `/api/citas/health`)
- Pruebas: `.\tests\e2e\ejecutar.ps1` (87 pruebas contra los endpoints reales)

Pipeline y entornos gestionados: Jenkins (`.\infra\jenkins\iniciar.ps1`, `http://localhost:8091`) despliega con Ansible en **staging** (`/cv-staging`) y **producción** (`/cv`). Nagios: `http://localhost:8092/nagios4`.

## Usuarios de demostración (solo datos semilla de laboratorio)

administrador, recepcionista, veterinario y cliente; credenciales en `services/auth/database/002_seed.sql`, visibles en el login solo con `APP_ENV=dev`.
