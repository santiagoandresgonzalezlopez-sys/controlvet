# Microservicio `citas`

**Responsabilidad:** agenda de citas y su ciclo de estados.
**Datos propios:** esquema `cv_citas` (`citas`). Usuario de BD: `citas_svc`.
**Dependencias:** `pacientes` (`GET /mascotas/{id}`) y `auth` (`GET /veterinarios/{id}`), por HTTP reenviando el JWT del usuario. Nunca accede a sus bases de datos.
**Responsable:** Santiago Gonzalez.

## Reglas de negocio

- Una cita exige mascota activa y veterinario activo (validados en los servicios dueños) → 422.
- Un veterinario no puede tener dos citas no canceladas en la misma fecha y hora → 422 (`hora`).
- `propietario_id` se copia de la mascota al agendar (ver ADR-003) y aísla los datos del cliente.
- Estados: `programada`, `confirmada`, `atendida`, `cancelada`.
- El veterinario solo ve su agenda y solo puede marcar **atendidas sus propias** citas (403 en otro caso).
- El cliente solo ve las citas de sus mascotas.

## Contrato API

| Método | Ruta | Roles | Descripción |
|---|---|---|---|
| GET | `/health` | público | Estado y conexión a `cv_citas` |
| GET | `/citas?fecha=&veterinario_id=&estado=&propietario_id=` | todos (alcance por rol) | Listado |
| GET | `/citas/proximas?limite=&veterinario_id=&propietario_id=` | todos (alcance por rol) | Desde hoy, sin canceladas |
| GET | `/citas/{id}` | todos (cliente: solo las suyas) | Detalle |
| POST | `/citas` | admin, recepción | `{mascota_id, veterinario_id, fecha, hora, motivo, observaciones?}` |
| PUT | `/citas/{id}` | admin, recepción | Reprogramación / edición |
| PATCH | `/citas/{id}/estado` | admin, recepción, veterinario | `{estado}` |

Configuración (`config/servicio.php`): `PACIENTES_URL`, `AUTH_URL` (endpoints de los servicios dependientes) además de las comunes (`JWT_SECRET`, `DB_*`, `APP_*`).
