# Arquitectura de ControlVet v1.1 (microservicios)

> Página de Confluence: **ControlVet › Code › Arquitectura**. Responsables: Santiago Gonzalez y Sebastian Gomez.

## 1. Punto de partida

La versión 1.0.0 (etiqueta Git `v1.0.0-monolito`) era un monolito PHP 8.2 con patrón MVVM ejecutado en XAMPP: un `index.php` frontal, 9 modelos con SQL directo sobre una base `controlvet` compartida y JOIN entre todas las tablas. No contenía código Java.

## 2. Vista general (sin contenedores)

Los microservicios **no usan Docker**: cada uno es una aplicación PHP con su propio punto de entrada, configuración, esquema y usuario de base de datos, publicada por el Apache de XAMPP bajo `/<instalación>/api/<servicio>/` (ver `docs/endpoints.md`).

```mermaid
flowchart LR
    U[Navegador] -->|HTTP :80| WEB[web<br/>vistas + ViewModels<br/>composición de API]
    subgraph apache [Apache de XAMPP — un punto de entrada por servicio]
        WEB -->|/api/auth| AUTH[auth]
        WEB -->|/api/pacientes| PAC[pacientes]
        WEB -->|/api/citas| CIT[citas]
        WEB -->|/api/clinica| CLI[clinica]
        WEB -->|/api/inventario| INV[inventario]
        WEB -->|/api/documentos| DOC[documentos]
        CIT -->|mascota| PAC
        CIT -->|veterinario| AUTH
        CLI -->|mascota, peso| PAC
        DOC --> CIT & CLI & PAC & AUTH
    end
    AUTH & PAC & CIT & CLI & INV & DOC --- DB[(MariaDB dedicada 127.0.0.1:3307<br/>un esquema y un usuario por servicio)]
    subgraph wsl [WSL2 Ubuntu]
        ANS[Ansible] -.despliega/opera.-> apache
        NAG[Nagios 4] -.supervisa.-> apache
    end
    JEN[Jenkins nativo :8091] -->|pipeline| ANS
```

| Entorno | URL | Prefijo de esquemas | Gestionado por |
|---|---|---|---|
| Desarrollo | `http://localhost/controlvet/` (copia de trabajo) | `cv_` | Desarrolladores (`infra/xampp/instalar.php`) |
| Staging | `http://localhost/cv-staging/` | `cvstg_` (datos reiniciados en cada build) | Jenkins + Ansible |
| Producción | `http://localhost/cv/` | `cvprod_` | Jenkins + Ansible; supervisado por Nagios |

Cada versión de staging/producción se extrae en `C:\controlvet\releases\<entorno>\<versión>` y se activa cambiando el enlace (*junction*) `htdocs\cv` o `htdocs\cv-staging`; el rollback reactiva la versión anterior.

## 3. Límites de responsabilidad y propiedad de datos

| Servicio | Responsabilidad | Datos propios (esquema) | Usuario BD | Depende de | Responsable |
|---|---|---|---|---|---|
| auth | Login, JWT, usuarios, roles | `cv_auth`: roles, usuarios | `auth_svc` | — | Santiago Gonzalez |
| pacientes | Propietarios y mascotas | `cv_pacientes`: propietarios, mascotas | `pacientes_svc` | — | Sebastian Gomez |
| citas | Agenda y estados | `cv_citas`: citas | `citas_svc` | pacientes, auth | Santiago Gonzalez |
| clinica | Historia clínica inmutable, fórmulas | `cv_clinica`: historias_clinicas, formulas_medicas | `clinica_svc` | pacientes | Sebastian Gomez |
| inventario | Productos y stock | `cv_inventario`: productos | `inventario_svc` | — | Sebastian Gomez |
| documentos | PDF y registro de documentos | `cv_documentos`: documentos | `documentos_svc` | citas, clinica, pacientes, auth | Santiago Gonzalez |
| web | Interfaz MVVM original, sesión, CSRF | ninguno (sin credenciales de BD) | — | todos | Ambos |

Contratos API detallados: `services/<servicio>/README.md`.

## 4. Estructura interna obligatoria de cada servicio

```
services/<servicio>/
├── controller/      rutas HTTP, autorización por rol, respuestas JSON
├── validador/       validación de entrada en el servidor (422 con errores por campo)
├── microservicio/   reglas de negocio (Service), puertos (interfaces) y adaptadores (PDO, HTTP)
├── public/index.php punto de entrada (el .htaccess raíz enruta /api/<servicio>/ aquí)
├── config/          servicio.php generado por el instalador (conexión propia, no versionado)
├── database/        esquema y datos semilla propios
├── tests/Unit       PHPUnit con dobles en memoria
└── composer.json    dependencias propias + composer.lock
```

`libs/kernel` es una librería **transversal** versionada (router, petición/respuesta, JWT, configuración, logger JSON, validador base, `/health`). No contiene lógica de negocio ni conexiones: cada servicio crea la suya con su propia configuración.

**Aislamiento de configuración en Apache:** en Windows, Apache ejecuta todos los servicios en un único proceso con hilos. Por eso la configuración de cada servicio se guarda en memoria **de la petición** (`Env::get`) y nunca con `putenv()`, que es global al proceso y mezclaba la base de datos y el prefijo de rutas entre servicios que atendían peticiones a la vez (defecto detectado por las pruebas e2e, ADR-010).

## 5. Comunicación

- **Síncrona HTTP/JSON** a través de los endpoints publicados por Apache (`http://localhost/<instalación>/api/<servicio>`), configurados en `config/servicio.php`.
- **Propagación de identidad:** cada llamada reenvía el JWT del usuario; el servicio destino aplica sus propias reglas (no hay cuentas de servicio con privilegios globales).
- **Correlación:** cabecera `X-Request-Id` registrada en los logs JSON de cada servicio.
- **Fallos:** un servicio dependiente caído se traduce en 503 controlado; el frontend muestra "Servicio temporalmente no disponible".

## 6. Seguridad

| Control | Implementación |
|---|---|
| Autenticación | JWT HS256 emitido por `auth`; secreto aleatorio por entorno (≥ 32 caracteres), verificación de `alg` e `iss` |
| Autorización | Rol verificado en el frontend **y** en cada servicio |
| Aislamiento de datos | Claim `propietario_id` del cliente aplicado en pacientes, citas, clinica y documentos |
| Aislamiento de persistencia | Un usuario MariaDB por servicio con DML **solo sobre las tablas de su esquema** (probado en `AislamientoBaseDatosTest`); instancia dedicada en 127.0.0.1:3307 |
| Exposición web | `.htaccess`: solo `/api/<servicio>/` y `web/`; código interno, configuración, SQL, `.git` y `composer.*` responden 403 |
| CSRF | Token por sesión en formularios y enlaces que modifican datos |
| Secretos | Nada en Git. Configuración de cada servicio como PHP (`config/servicio.php`, no se muestra aunque se pida por URL); claves root, de monitoreo, de Jenkins y de cada entorno en `C:\xampp\controlvet-secretos` (fuera de htdocs). El pipeline bloquea secretos versionados |
| Errores | `display_errors` desactivado por cada servicio; logs estructurados con datos sensibles enmascarados |

## 7. Conservación de funcionalidades

El frontend conserva las mismas rutas `?route=`, vistas y ViewModels de v1.0.0. Los modelos mantienen sus firmas, pero ahora llaman a las APIs y **componen** los datos (`web/core/Composicion.php`) para entregar a las vistas exactamente las mismas claves que antes devolvían los JOIN. La suite `RegresionWebTest` recorre las pantallas por rol para demostrarlo.

Cambios de comportamiento deliberados (mejoras de seguridad o correcciones):

1. El veterinario solo marca como atendidas **sus** citas (antes podía cambiar cualquier cita a cualquier estado).
2. No se permite doble reserva del mismo veterinario en la misma fecha y hora.
3. Un administrador no puede desactivar su propia cuenta.
4. Las credenciales de demostración solo se muestran con `APP_ENV=dev`.
5. Los PDF muestran correctamente tildes y ñ.
6. Las acciones que modifican datos exigen token CSRF.
