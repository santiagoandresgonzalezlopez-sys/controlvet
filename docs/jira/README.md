# PLAN · Backlog para Jira

> **Estado: preparado, NO importado.** No se dispuso de acceso a una instancia de Jira desde este entorno; el proyecto y sus incidencias deben crearse importando el CSV (tarea CV-1).

## Importación

1. Jira › Crear proyecto › *Kanban* (empresa) · nombre **ControlVet**, clave **CV**.
2. Configuración del sistema › Importación externa › **CSV** › `backlog-controlvet.csv` (codificación UTF-8, separador coma).
3. Mapear columnas: `Issue Id`→Issue Id, `Issue Type`→Tipo, `Summary`→Resumen, `Assignee`→Responsable (asociar los nombres a las cuentas de Santiago Gonzalez y Sebastian Gomez), `Priority`→Prioridad, `Story Points`→Story Points, `Parent`→Parent (vincula cada tarea con su épica de fase), `Status`→Estado, `Labels`→Etiquetas, `Description`→Descripción.
4. Crear los vínculos *bloquea / es bloqueada por* de la tabla de dependencias (abajo).

## Contenido

- 8 épicas, una por fase DevOps (PLAN … MONITOR).
- 43 incidencias: historias de usuario, tareas técnicas y 8 defectos (encontrados por las pruebas y la revisión de seguridad).
- Distribución: Santiago Gonzalez 23 · Sebastian Gomez 20 · 115 puntos.
- Estados: 37 *Done* (ejecutadas y verificadas en este repositorio) · 6 *To Do* (requieren acceso externo o son mejoras).

## Dependencias principales

| Incidencia | Depende de |
|---|---|
| CV-6 Login/JWT | CV-5 Kernel |
| CV-8 Pacientes | CV-6 |
| CV-9 Citas | CV-8 |
| CV-10 Clínica | CV-8 |
| CV-12 Documentos | CV-9, CV-10 |
| CV-13 Frontend | CV-7 … CV-12 |
| CV-32 Jenkinsfile | CV-31, CV-20, CV-21 |
| CV-37 Deploy Ansible | CV-36, CV-33 |
| CV-40 Prueba de alerta | CV-39, CV-38 |

## Pendientes (To Do)

CV-1 crear proyecto · CV-2 tablero · CV-15 publicar en Confluence · CV-16 repositorio GitHub · CV-41 JWT RS256 · CV-42 alertas por correo/chat.
