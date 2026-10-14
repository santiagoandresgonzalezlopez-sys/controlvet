# Registro de decisiones de arquitectura (ADR)

> Página de Confluence: **ControlVet › Code › Decisiones técnicas**.

| ADR | Decisión | Estado |
|---|---|---|
| 001 | Descomposición en 6 microservicios por contexto de negocio + frontend | Aceptada |
| 002 | Un esquema y un usuario de BD por servicio sobre un único servidor MariaDB | Aceptada |
| 003 | Copia local de `propietario_id` en citas, historias y documentos | Aceptada |
| 004 | JWT HS256 con secreto compartido entre servicios | Aceptada (con limitación) |
| 005 | Composer en lugar de Maven para el build | Aceptada |
| 006 | Consistencia eventual del peso de la mascota | Aceptada |
| 007 | GitHub solo como VCS; Jenkins obtiene el código por polling | Aceptada |
| 008 | Ansible ejecutado en un contenedor Linux | Aceptada |

---

## ADR-001 · Descomposición en microservicios

**Contexto.** El monolito mezclaba seis capacidades con JOIN cruzados. **Decisión.** Un servicio por contexto (auth, pacientes, citas, clinica, inventario, documentos) con estructura `controller/ validador/ microservicio/`, y el frontend MVVM existente como cliente (patrón *Backend for Frontend* con composición de API). **Consecuencias.** Despliegue y pruebas independientes; aparecen llamadas de red y la necesidad de componer datos en el frontend.

## ADR-002 · Esquema por servicio en un servidor compartido

**Contexto.** Seis servidores MariaDB exceden la memoria disponible del equipo (8 GB para Docker). **Decisión.** Un único servidor con un esquema `cv_<servicio>` y un usuario `<servicio>_svc` con privilegios `SELECT/INSERT/UPDATE/DELETE` **solo** sobre su esquema; sin FK entre esquemas. **Consecuencias.** El aislamiento lo impone el motor (verificado por prueba automatizada: error 1142 al leer otro esquema). Riesgo aceptado: el servidor es punto único de fallo y recurso compartido; mitigación: respaldos con Ansible y supervisión con Nagios. Migrar a un servidor por servicio solo requiere cambiar `DB_HOST`.

## ADR-003 · Copia de `propietario_id`

**Contexto.** El aislamiento del cliente requería JOIN con mascotas. **Decisión.** Al crear una cita/historia/documento se copia el `propietario_id` obtenido del servicio pacientes. **Consecuencias.** Lecturas aisladas sin llamadas remotas. Si una mascota cambia de dueño, el histórico conserva el dueño de la fecha del evento (comportamiento aceptado y documentado).

## ADR-004 · JWT HS256 compartido

**Decisión.** Todos los servicios validan con el mismo `JWT_SECRET`. **Limitación.** Cualquier servicio comprometido podría emitir tokens. **Pendiente.** Migrar a RS256 (clave privada solo en `auth`, pública en el resto).

## ADR-005 · Composer en lugar de Maven

**Contexto.** La matriz de la actividad asigna Maven a la fase Build. ControlVet es 100 % PHP; Maven compila y empaqueta proyectos Java y no gestiona dependencias PHP. **Decisión.** Build con Composer (`composer.json` + `composer.lock` por componente, autoload optimizado) y empaquetado en imágenes Docker multi-etapa versionadas; no se crea un componente Java artificial. **Consecuencias.** Build reproducible y real. Si la rúbrica exige demostrar Maven, solo debe hacerse con un componente Java que responda a una necesidad real (pendiente de confirmar con el docente).

## ADR-006 · Consistencia eventual del peso

**Decisión.** La historia clínica es la fuente de verdad; el peso actual de la mascota (propiedad de pacientes) se actualiza por API. Si falla, la historia se guarda, se registra un aviso y la respuesta indica `peso_sincronizado: false`. **Consecuencias.** No hay transacciones distribuidas; la diferencia se corrige con la siguiente consulta o manualmente.

## ADR-007 · GitHub solo como control de versiones

**Decisión.** Sin GitHub Actions, Projects ni Packages. Jenkins consulta el repositorio por *polling* (`H/5 * * * *`) y ejecuta el `Jenkinsfile`. Los artefactos se publican en el registro propio (`localhost:5000`) y en Jenkins. Mientras no exista el remoto, Jenkins usa un espejo local de solo lectura (`file:///repo`).

## ADR-008 · Ansible en contenedor Linux

**Contexto.** Ansible no se ejecuta de forma nativa en Windows y el equipo no tiene una distribución WSL con Ansible. **Decisión.** Imagen `controlvet/ops` (Alpine + ansible-core + docker CLI), versionada con cada release, que administra el motor Docker por el socket. **Consecuencias.** Mismos playbooks para Jenkins y para operación manual. El inventario `lab` está preparado para una VM Linux por SSH (no ejecutado).
