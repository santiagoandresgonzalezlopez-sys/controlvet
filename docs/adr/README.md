# Registro de decisiones de arquitectura (ADR)

> Página de Confluence: **ControlVet › Code › Decisiones técnicas**.

| ADR | Decisión | Estado |
|---|---|---|
| 001 | Descomposición en 6 microservicios por contexto de negocio + frontend | Aceptada |
| 002 | Instancia MariaDB dedicada con un esquema y un usuario por servicio | Aceptada (sustituye a la versión con contenedor) |
| 003 | Copia local de `propietario_id` en citas, historias y documentos | Aceptada |
| 004 | JWT HS256 con secreto compartido entre servicios | Aceptada (con limitación) |
| 005 | Composer en lugar de Maven para el build | Aceptada |
| 006 | Consistencia eventual del peso de la mascota | Aceptada |
| 007 | GitHub solo como VCS; Jenkins nativo obtiene el código por polling | Aceptada |
| 008 | Ansible y Nagios en WSL2 (Ubuntu) | Aceptada |
| 009 | Microservicios sin Docker, ejecutados en XAMPP | Aceptada (requisito del docente) |
| 010 | Configuración por petición (`Env::get`), nunca `putenv()` | Aceptada |
| 011 | Secretos fuera de htdocs y configuración como PHP | Aceptada |

---

## ADR-001 · Descomposición en microservicios

**Contexto.** El monolito mezclaba seis capacidades con JOIN cruzados. **Decisión.** Un servicio por contexto (auth, pacientes, citas, clinica, inventario, documentos) con estructura `controller/ validador/ microservicio/`, y el frontend MVVM existente como cliente (patrón *Backend for Frontend* con composición de API). **Consecuencias.** Código, datos, pruebas y endpoints independientes por servicio; aparecen llamadas HTTP y la necesidad de componer datos en el frontend.

## ADR-002 · Instancia MariaDB dedicada, esquema y usuario por servicio

**Contexto.** El MySQL general de XAMPP (3306) es compartido con otros proyectos del equipo y tenía tablas de sistema dañadas que provocaban caídas al conceder privilegios. **Decisión.** Instancia propia con los binarios de XAMPP (`infra/xampp/bd.ps1`, `127.0.0.1:3307`, datos en `C:\xampp\controlvet-db`). Por servicio: esquema `<prefijo><servicio>` y usuario con `SELECT/INSERT/UPDATE/DELETE` **solo sobre las tablas de su esquema**; sin FK entre esquemas. **Consecuencias.** El motor impone el aislamiento (error 1142 al leer otro esquema, verificado por prueba). La instancia es un punto único de fallo: se mitiga con respaldos (Ansible) y supervisión (Nagios). Los entornos (desarrollo, staging, producción) comparten instancia con prefijos distintos.

## ADR-003 · Copia de `propietario_id`

**Decisión.** Al crear una cita, historia o documento se copia el `propietario_id` obtenido del servicio pacientes. **Consecuencias.** Lecturas aisladas para el rol cliente sin llamadas remotas; si una mascota cambia de dueño, el histórico conserva el dueño de la fecha del evento.

## ADR-004 · JWT HS256 compartido

**Decisión.** Todos los servicios de un entorno validan con el mismo `JWT_SECRET` (aleatorio, generado por el instalador). **Limitación.** Un servicio comprometido podría emitir tokens. **Pendiente.** RS256 (clave privada solo en `auth`).

## ADR-005 · Composer en lugar de Maven

**Contexto.** La matriz asigna Maven a Build. ControlVet es 100 % PHP; Maven compila y empaqueta proyectos Java y no gestiona dependencias PHP. **Decisión.** Build con Composer (`composer.json` + `composer.lock` por componente) y empaquetado en un ZIP versionado con dependencias de producción y SHA-256 (`infra/ci/empaquetar.ps1`). No se crea un componente Java artificial. **Pendiente.** Confirmar con el docente si la rúbrica exige demostrar Maven.

## ADR-006 · Consistencia eventual del peso

**Decisión.** La historia clínica es la fuente de verdad; el peso actual (propiedad de pacientes) se actualiza por API y, si falla, la respuesta indica `peso_sincronizado: false`. Sin transacciones distribuidas.

## ADR-007 · GitHub solo como control de versiones

**Decisión.** Sin GitHub Actions, Projects ni Packages. Jenkins (nativo en Windows, `infra/jenkins/`) consulta el repositorio por *polling* (`H/5 * * * *`) y ejecuta el `Jenkinsfile`. Los artefactos se publican en `C:\controlvet\artefactos` y en Jenkins. Mientras no exista el remoto, Jenkins usa el repositorio local (`file:///C:/xampp/htdocs/controlvet`).

## ADR-008 · Ansible y Nagios en WSL2

**Contexto.** Ansible y Nagios no tienen versión nativa para Windows. **Decisión.** Ubuntu 24.04 en WSL2. Ansible administra Windows/XAMPP mediante la interoperabilidad de WSL (`/mnt/c`, `php.exe`, `curl.exe`, `cmd.exe`); Nagios 4 supervisa los endpoints de producción y la instancia MariaDB. **Consecuencias.** Un solo equipo ejecuta todo; el inventario se puede ampliar a un servidor Linux por SSH.

## ADR-009 · Microservicios sin Docker

**Contexto.** Requisito del docente: los microservicios no pueden usar Docker; deben estar programados, con su capa de validación y endpoints definidos. **Decisión.** Cada servicio se publica en Apache de XAMPP mediante el `.htaccess` raíz (`/api/<servicio>/` → `services/<servicio>/public/index.php`). Deploy = artefacto versionado + configuración por Ansible + cambio atómico de enlace (*junction*). Se retiraron Dockerfiles, `compose.yaml`, imágenes, contenedores y volúmenes de ControlVet. **Consecuencias.** Sin aislamiento de procesos entre servicios (comparten Apache), por eso el aislamiento se garantiza en código (ADR-010), en datos (ADR-002) y en la exposición web (ADR-011).

## ADR-010 · Configuración por petición

**Contexto.** Apache para Windows usa un único proceso multihilo; `putenv()` es global al proceso. Las pruebas e2e mostraron que una llamada `citas → pacientes` hacía que pacientes usara la configuración de citas. **Decisión.** `ControlVet\Kernel\Config\Env` carga `config/servicio.php` en memoria estática de la petición y `Env::get()` sustituye a `getenv()`. El frontend usa `cv_env()` con el mismo criterio.

## ADR-011 · Secretos fuera de htdocs

**Decisión.** Configuración de cada servicio como archivo PHP que retorna un arreglo (si se solicita por URL se ejecuta sin mostrar nada) y, además, bloqueo 403 en `.htaccess`. Claves root, de monitoreo, de Jenkins, de Nagios y de cada entorno en `C:\xampp\controlvet-secretos`, fuera del árbol publicado.
