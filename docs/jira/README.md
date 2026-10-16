# PLAN · Backlog en Jira

> **Estado: importado y verificado** en Jira Cloud, proyecto **ControlVet (CV)** con tablero Kanban.

## Importación realizada

1. Proyecto Kanban gestionado por la empresa: nombre **ControlVet**, clave **CV**.
2. ⚙ › Sistema › Importación de sistema externo › **CSV** con `backlog-controlvet.csv` (UTF-8, coma).
3. Columnas mapeadas: `Issue Id`, `Issue Type`, `Summary`, `Priority`, `Parent`, `Status`, `Labels`, `Description` y `Story Points`. Se mapearon valores para el tipo, la prioridad y el estado (`En curso` → En curso, `Done` → Listo, `To Do` → Backlog).
4. **Responsables:** el importador no pudo crear las cuentas a partir de los nombres del CSV, así que las incidencias quedaron sin asignar. Se asignaron después con dos cambios masivos por JQL (`key in (...)`): 31 a Santiago Gonzalez y 30 a Sebastian Gomez.
5. **Dependencias:** 21 vínculos "es bloqueada por" creados a mano (ver la tabla de abajo). Comprobación: `project = CV AND issueLinkType = "is blocked by"` devuelve 11 incidencias.

Resultado: 61 elementos (8 épicas + 10 historias + 27 tareas + 16 defectos), cada uno enlazado con su épica. Tablero: Backlog 6 · En curso 1 · Listo 54.

## Claves de Jira y del CSV

Jira creó primero las 8 épicas, así que **las claves de Jira no coinciden con la columna `Issue Id` del CSV**. La documentación del repositorio usa las **claves de Jira**. Las descripciones importadas conservan el texto "Depende de: …" con los identificadores del CSV; la referencia válida son los vínculos de Jira.

| Clave Jira | Issue Id (CSV) | Tipo | Resumen | Responsable |
|---|---|---|---|---|
| CV-1 | E1 | Epic | PLAN · Gestión del trabajo en Jira | Santiago Gonzalez |
| CV-2 | E2 | Epic | CODE · Microservicios y documentación técnica | Sebastian Gomez |
| CV-3 | E3 | Epic | BUILD · Dependencias y empaquetado | Santiago Gonzalez |
| CV-4 | E4 | Epic | TEST · Pruebas automatizadas | Sebastian Gomez |
| CV-5 | E5 | Epic | RELEASE · Pipeline Jenkins | Santiago Gonzalez |
| CV-6 | E6 | Epic | DEPLOY · Publicación en XAMPP | Sebastian Gomez |
| CV-7 | E7 | Epic | OPERATE · Automatización con Ansible (WSL2) | Santiago Gonzalez |
| CV-8 | E8 | Epic | MONITOR · Supervisión con Nagios (WSL2) | Sebastian Gomez |
| CV-9 | CV-1 | Task | Crear el proyecto ControlVet (clave CV) en Jira e importar este backlog | Santiago Gonzalez |
| CV-10 | CV-2 | Task | Configurar tablero Kanban (Por hacer / En curso / En revisión / Hecho) | Sebastian Gomez |
| CV-11 | CV-3 | Task | Definir criterios de aceptación y Definición de Hecho del equipo | Santiago Gonzalez |
| CV-12 | CV-4 | Story | Como equipo queremos una línea base versionada del monolito para poder comparar y revertir | Santiago Gonzalez |
| CV-13 | CV-5 | Task | Kernel transversal (router, JWT, configuración, logger, health, validador base) con pruebas | Santiago Gonzalez |
| CV-14 | CV-6 | Story | Como usuario quiero iniciar sesión y recibir un token para usar los módulos según mi rol | Santiago Gonzalez |
| CV-15 | CV-7 | Story | Como administrador quiero gestionar usuarios y roles | Santiago Gonzalez |
| CV-16 | CV-8 | Story | Como recepcionista quiero registrar propietarios y mascotas | Sebastian Gomez |
| CV-17 | CV-9 | Story | Como recepcionista quiero agendar citas sin choques de horario | Santiago Gonzalez |
| CV-18 | CV-10 | Story | Como veterinario quiero registrar consultas inmutables y fórmulas médicas | Sebastian Gomez |
| CV-19 | CV-11 | Story | Como administrador quiero controlar el inventario y ver alertas de stock bajo | Sebastian Gomez |
| CV-20 | CV-12 | Story | Como cliente quiero descargar comprobantes, resúmenes y fórmulas de mis mascotas | Santiago Gonzalez |
| CV-21 | CV-13 | Story | Como usuario quiero seguir usando la misma interfaz web tras la migración | Sebastian Gomez |
| CV-22 | CV-14 | Task | Documentar arquitectura, endpoints, convenciones y ADR (páginas Confluence) | Sebastian Gomez |
| CV-23 | CV-15 | Task | Publicar la documentación en el espacio Confluence CONTROLVET | Sebastian Gomez |
| CV-24 | CV-16 | Task | Crear el repositorio GitHub y subir la rama main (solo control de versiones) | Santiago Gonzalez |
| CV-25 | CV-45 | Story | Como docente quiero los microservicios programados con validador y endpoints, sin Docker | Santiago Gonzalez |
| CV-26 | CV-17 | Task | Gestionar dependencias con Composer (composer.lock por componente) | Santiago Gonzalez |
| CV-27 | CV-18 | Task | Artefacto ZIP versionado con dependencias de producción y SHA-256 (empaquetar.ps1) | Sebastian Gomez |
| CV-28 | CV-19 | Task | Evaluar Maven para la fase Build y documentar la decisión (ADR-005) | Santiago Gonzalez |
| CV-29 | CV-20 | Task | Pruebas unitarias PHPUnit de validadores y reglas de negocio por servicio | Sebastian Gomez |
| CV-30 | CV-21 | Task | Pruebas de API REST, autenticación y autorización por rol | Sebastian Gomez |
| CV-31 | CV-22 | Task | Pruebas de aislamiento de datos (cliente) y de base de datos (usuarios por esquema) | Santiago Gonzalez |
| CV-32 | CV-23 | Task | Pruebas de regresión del frontend por rol | Sebastian Gomez |
| CV-33 | CV-24 | Bug | Apache no entrega la cabecera Authorization a PHP: todas las APIs respondían 401 | Santiago Gonzalez |
| CV-34 | CV-25 | Bug | El frontend no cargaba Composicion.php: pantallas con citas/historias devolvían 500 | Sebastian Gomez |
| CV-35 | CV-26 | Bug | Código 419 se convertía en 500 en Apache al fallar CSRF | Sebastian Gomez |
| CV-36 | CV-27 | Bug | Veterinario podía cambiar el estado de cualquier cita (IDOR) | Santiago Gonzalez |
| CV-37 | CV-28 | Bug | Rutas que modifican datos por GET sin protección CSRF | Sebastian Gomez |
| CV-38 | CV-29 | Bug | Credenciales de demostración visibles en el login de producción | Santiago Gonzalez |
| CV-39 | CV-30 | Bug | PDF con tildes y ñ corruptas (UTF-8 sin codificación de fuente) | Santiago Gonzalez |
| CV-40 | CV-46 | Bug | putenv() mezclaba la configuración entre servicios en el proceso multihilo de Apache | Sebastian Gomez |
| CV-41 | CV-47 | Bug | Zona horaria Europe/Berlin de XAMPP: 'hoy' no coincidía con CURDATE() de MySQL | Sebastian Gomez |
| CV-42 | CV-48 | Bug | Secretos de configuración dentro de htdocs dependían solo del .htaccess | Santiago Gonzalez |
| CV-43 | CV-31 | Task | Jenkins nativo (WAR + JDK 21 portable) con configuración como código y polling | Santiago Gonzalez |
| CV-44 | CV-32 | Task | Jenkinsfile para Windows: validar, Composer, PHPUnit, empaquetado, staging+e2e, release, deploy | Santiago Gonzalez |
| CV-45 | CV-33 | Task | Repositorio de artefactos y promoción candidato -> publicado | Sebastian Gomez |
| CV-46 | CV-43 | Bug | El log de Jenkins imprimía secretos efímeros de CI al cargar ci.env con set -x | Santiago Gonzalez |
| CV-47 | CV-49 | Bug | El control de secretos daba falso negativo (patrón partido por PowerShell 5.1) | Sebastian Gomez |
| CV-48 | CV-34 | Task | Publicación de los microservicios en Apache de XAMPP (.htaccess, /api/<servicio>) | Sebastian Gomez |
| CV-49 | CV-35 | Task | Instancia MariaDB dedicada (3307) con esquema y usuario por servicio | Sebastian Gomez |
| CV-50 | CV-50 | Task | Entornos staging (/cv-staging) y producción (/cv) por enlace versionado | Santiago Gonzalez |
| CV-51 | CV-51 | Bug | Tras cambiar el enlace Apache seguía sirviendo la versión anterior (realpath_cache de PHP) | Sebastian Gomez |
| CV-52 | CV-36 | Task | Ansible en WSL2 (Ubuntu 24.04) administrando Windows/XAMPP | Santiago Gonzalez |
| CV-53 | CV-37 | Task | Playbooks deploy (con rollback automático), rollback y status | Santiago Gonzalez |
| CV-54 | CV-38 | Task | Playbooks backup, restore y recover | Sebastian Gomez |
| CV-55 | CV-44 | Bug | El rollback automático reintentaba la versión fallida (precedencia de -e cv_version) | Santiago Gonzalez |
| CV-56 | CV-52 | Bug | Rutas de Windows perdían las barras invertidas al pasar por Ansible/wsl.exe | Sebastian Gomez |
| CV-57 | CV-53 | Bug | bd.ps1 detener no detenía la instancia y aun así informaba éxito | Sebastian Gomez |
| CV-58 | CV-39 | Task | Nagios 4 en WSL2 configurado por Ansible (hosts, servicios, umbrales, contactos) | Sebastian Gomez |
| CV-59 | CV-40 | Task | Probar alerta con fallo controlado y registrar la acción correctiva | Santiago Gonzalez |
| CV-60 | CV-41 | Task | Migrar firma JWT a RS256 (clave privada solo en auth) | Santiago Gonzalez |
| CV-61 | CV-42 | Task | Enviar alertas de Nagios por correo/chat del equipo | Sebastian Gomez |

## Dependencias (claves de Jira)

| Incidencia | Es bloqueada por |
|---|---|
| CV-14 Inicio de sesión / JWT | CV-13 Kernel transversal |
| CV-16 Propietarios y mascotas | CV-14 |
| CV-17 Agenda de citas | CV-16 |
| CV-18 Consultas y fórmulas | CV-16 |
| CV-20 Documentos PDF | CV-17, CV-18 |
| CV-21 Frontend sobre las APIs | CV-15, CV-16, CV-17, CV-18, CV-19, CV-20 |
| CV-25 Microservicios sin Docker | CV-21 |
| CV-44 Jenkinsfile para Windows | CV-43, CV-29, CV-30 |
| CV-50 Entornos staging y producción | CV-49 |
| CV-53 Playbooks de deploy y rollback | CV-52, CV-45 |
| CV-59 Prueba de alerta controlada | CV-58, CV-54 |

## Pendientes (Por hacer)

Completadas tras la importación: CV-9 (proyecto e importación), CV-10 (tablero), CV-23 (Confluence) y CV-24 (GitHub).

Quedan como mejoras futuras: CV-60 JWT RS256 · CV-61 alertas por correo/chat.
