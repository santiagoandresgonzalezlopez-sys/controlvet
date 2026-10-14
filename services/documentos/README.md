# Microservicio `documentos`

**Responsabilidad:** generar los PDF (comprobante de cita, resumen de consulta, fórmula médica) y llevar su registro histórico.
**Datos propios:** esquema `cv_documentos` (`documentos`). Usuario de BD: `documentos_svc`.
**Dependencias:** `citas`, `clinica`, `pacientes`, `auth` (lectura, reenviando el JWT del solicitante).
**Responsable:** Santiago Gonzalez.

## Reglas de negocio

- El contenido del PDF se obtiene **siempre** de los servicios dueños, nunca del cuerpo de la petición: un cliente no puede fabricar documentos ni leer datos ajenos (los servicios de origen responden 403/404).
- Antes de registrar se verifica de nuevo la propiedad (defensa en profundidad).
- Cada documento guarda `propietario_id` para que el cliente liste solo los suyos.
- El generador PDF propio (sin dependencias) codifica el texto en WinAnsi: tildes, ñ y ° se imprimen correctamente.

## Contrato API

| Método | Ruta | Roles | Descripción |
|---|---|---|---|
| GET | `/health` | público | Estado y conexión a `cv_documentos` |
| GET | `/documentos` | todos (cliente: solo los suyos) | Registro histórico |
| POST | `/documentos/comprobante-cita/{id}` | todos | `application/pdf` |
| POST | `/documentos/resumen-consulta/{id}` | todos | `application/pdf` |
| POST | `/documentos/formula-medica/{id}` | todos | `application/pdf` |
