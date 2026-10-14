# Microservicio `clinica`

**Responsabilidad:** historia clínica (registro inmutable de consultas) y fórmulas médicas.
**Datos propios:** esquema `cv_clinica` (`historias_clinicas`, `formulas_medicas`). Usuario de BD: `clinica_svc`.
**Dependencias:** `pacientes` (`GET /mascotas/{id}`, `PATCH /mascotas/{id}/peso`).
**Responsable:** Sebastian Gomez.

## Reglas de negocio

- Solo el rol veterinario registra consultas y fórmulas; el autor se toma del JWT (se ignora `veterinario_id` del cuerpo).
- La historia es **inmutable**: no existen `PUT`/`PATCH`/`DELETE` (405).
- El paciente debe existir y estar activo (422).
- El peso se sincroniza con `pacientes`; si ese servicio falla, la historia se conserva y la respuesta indica `"peso_sincronizado": false` (consistencia eventual, ver ADR-006).
- El cliente solo accede a historias y fórmulas de sus mascotas.

## Contrato API

| Método | Ruta | Roles | Descripción |
|---|---|---|---|
| GET | `/health` | público | Estado y conexión a `cv_clinica` |
| GET | `/historias?mascota_id=&propietario_id=&veterinario_id=&limite=` | todos (alcance por rol) | Más recientes primero |
| GET | `/historias/{id}` | todos (cliente: solo las suyas) | Detalle |
| POST | `/historias` | veterinario | `{mascota_id, motivo_consulta, diagnostico, peso?, sintomas?, tratamiento?, observaciones?, cita_id?}` |
| GET | `/historias/{id}/formulas` | todos (cliente: solo las suyas) | Fórmulas de la consulta |
| POST | `/historias/{id}/formulas` | veterinario | `{medicamento, dosis, frecuencia, duracion, indicaciones?}` |
| GET | `/formulas/{id}` | todos (cliente: solo las suyas) | Detalle con datos de la historia |
