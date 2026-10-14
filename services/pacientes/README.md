# Microservicio `pacientes`

**Responsabilidad:** propietarios y mascotas (pacientes).
**Datos propios:** esquema `cv_pacientes` (`propietarios`, `mascotas`). Usuario de BD: `pacientes_svc`.
**Dependencias de otros servicios:** ninguna. Lo consultan `citas`, `clinica`, `documentos` y el frontend.
**Responsable:** Sebastian Gomez.

## Reglas de negocio

- El documento del propietario es único (422).
- Un propietario solo se desactiva (nunca se borra) y no si tiene mascotas activas (409).
- Una mascota exige un propietario existente y activo (422); el sexo es `macho|hembra`; peso ≥ 0; nacimiento no futuro.
- **Aislamiento:** el rol `cliente` solo obtiene datos de su `propietario_id` (claim del JWT), aunque envíe otros filtros.
- El peso actual solo lo actualiza un veterinario (lo invoca `clinica` al registrar una consulta).

## Contrato API

| Método | Ruta | Roles | Descripción |
|---|---|---|---|
| GET | `/health` | público | Estado y conexión a `cv_pacientes` |
| GET | `/propietarios?buscar=` | admin, recepción | Activos; búsqueda por nombre/apellido/documento |
| GET | `/propietarios/existe-documento?documento=&excluir=` | admin, recepción | `{existe}` |
| GET | `/propietarios/{id}` | todos (cliente: solo el suyo) | Detalle |
| GET | `/propietarios/{id}/mascotas` | todos (cliente: solo el suyo) | Mascotas activas |
| POST / PUT | `/propietarios[/{id}]` | admin, recepción | Alta / edición |
| PATCH | `/propietarios/{id}/desactivar` | admin, recepción | 409 si tiene mascotas activas |
| GET | `/mascotas?buscar=&propietario_id=&ids=1,2` | todos (cliente: solo las suyas) | `ids` incluye inactivas (histórico) |
| GET | `/mascotas/{id}` | todos (cliente: solo las suyas) | Incluye nombre y teléfono del propietario |
| POST / PUT | `/mascotas[/{id}]` | admin, recepción | Alta / edición |
| PATCH | `/mascotas/{id}/peso` | veterinario | `{peso}` (0–1000 kg) |
| GET | `/resumen` | admin, recepción | `{propietarios, mascotas}` activos |
