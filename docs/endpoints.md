# Catálogo de endpoints de los microservicios (XAMPP, sin Docker)

> Página de Confluence: **ControlVet › Code › Endpoints**. Base: `http://localhost/controlvet/api/<servicio>`.

Cada microservicio se ejecuta en Apache de XAMPP con su propio punto de entrada (`services/<servicio>/public/index.php`), su configuración (`config/servicio.env`), su esquema y su usuario MySQL. El `.htaccess` de la raíz publica solo los endpoints y bloquea (403) el código interno, las configuraciones y los secretos.

## Convenciones comunes

| Aspecto | Regla |
|---|---|
| Formato | JSON UTF-8 (`Content-Type: application/json`); los PDF devuelven `application/pdf` |
| Autenticación | `Authorization: Bearer <JWT>` obtenido en `POST /api/auth/auth/login` (salvo `/health` y el login) |
| Identificadores | `{id}` solo acepta dígitos; otro valor → 404 |
| Validación | La hace la capa `validador/` de cada servicio **en el servidor**; los errores devuelven 422 con un mensaje por campo |
| Correlación | Cabecera opcional `X-Request-Id`; se registra en los logs de todos los servicios implicados |

Errores: `{"error": "mensaje", "errors": {"campo": "mensaje"}}`

| Código | Significado |
|---|---|
| 400 | Solicitud mal formada |
| 401 | Sin token, token inválido, manipulado o expirado; credenciales incorrectas en el login |
| 403 | Rol sin permiso o recurso de otro propietario (rol cliente) |
| 404 | Recurso o ruta inexistente |
| 405 | Método no permitido (p. ej. editar una historia clínica) |
| 409 | Conflicto con una regla de negocio |
| 422 | Datos inválidos (detalle por campo) |
| 500 | Error interno (sin detalles; queda en el log) |
| 503 | Base de datos o servicio dependiente no disponible |

Roles: **A** administrador · **R** recepcionista · **V** veterinario · **C** cliente (solo datos propios) · **P** público.

---

## 1. auth · `http://localhost/controlvet/api/auth`

| Método | Endpoint | Roles | Validador (`UsuarioValidador`) | Respuesta |
|---|---|---|---|---|
| GET | `/health` | P | — | 200 `{"status":"ok","service":"auth","version","checks":{"database":"ok"}}` / 503 |
| POST | `/auth/login` | P | `email` obligatorio y válido; `password` obligatorio | 200 `{token, usuario}` · 401 `Correo o contraseña incorrectos.` · 422 |
| GET | `/auth/me` | A R V C | — | 200 usuario del token |
| GET | `/roles` | A | — | 200 lista |
| GET | `/usuarios` | A | — | 200 lista (sin `password_hash`) |
| GET | `/usuarios/existe-email?email=&excluir=` | A | — | 200 `{existe}` |
| GET | `/usuarios/{id}` | A | — | 200 · 404 |
| POST | `/usuarios` | A | `nombre`, `apellido` (≤80), `email` válido y único, `rol_id` existente, `password` 6–72 | 201 · 422 |
| PUT | `/usuarios/{id}` | A | igual que POST; `password` opcional | 200 · 404 · 422 |
| PATCH | `/usuarios/{id}/estado` | A | `estado` ∈ {activo, inactivo} | 200 · 409 si se desactiva a sí mismo |
| GET | `/veterinarios[?todos=1]` | A R V C | — | 200 lista |
| GET | `/veterinarios/{id}` | A R V C | — | 200 · 404 |

Ejemplo:

```bash
curl -s -X POST http://localhost/controlvet/api/auth/auth/login -H "Content-Type: application/json" -d '{"email":"recepcion@controlvet.com","password":"<clave>"}'
```

## 2. pacientes · `http://localhost/controlvet/api/pacientes`

| Método | Endpoint | Roles | Validador (`PacientesValidador`) / regla de negocio | Respuesta |
|---|---|---|---|---|
| GET | `/health` | P | — | 200 / 503 |
| GET | `/propietarios?buscar=` | A R | — | 200 activos |
| GET | `/propietarios/existe-documento?documento=&excluir=` | A R | — | 200 `{existe}` |
| GET | `/propietarios/{id}` | A R V C* | C solo el suyo | 200 · 403 · 404 |
| GET | `/propietarios/{id}/mascotas` | A R V C* | C solo el suyo | 200 |
| POST | `/propietarios` | A R | `nombre`, `apellido` (≤80), `documento` (≤30, **único**), `telefono` (≤20), `email` válido, `direccion` ≤180 | 201 · 422 |
| PUT | `/propietarios/{id}` | A R | igual | 200 · 404 · 422 |
| PATCH | `/propietarios/{id}/desactivar` | A R | no si tiene mascotas activas | 200 · 409 |
| GET | `/mascotas?buscar=&propietario_id=&ids=1,2` | A R V C* | C: siempre filtrado a su propietario | 200 |
| GET | `/mascotas/{id}` | A R V C* | C solo las suyas | 200 · 403 · 404 |
| POST | `/mascotas` | A R | `nombre`, `especie`, `sexo` ∈ {macho, hembra}, `propietario_id` (existente y **activo**), `peso_actual` ≥ 0, `fecha_nacimiento` válida y no futura | 201 · 422 |
| PUT | `/mascotas/{id}` | A R | igual | 200 · 404 · 422 |
| PATCH | `/mascotas/{id}/peso` | V | `peso` numérico entre 0 y 1000 | 200 · 422 |
| GET | `/resumen` | A R | — | 200 `{propietarios, mascotas}` |

## 3. citas · `http://localhost/controlvet/api/citas`

Depende de: `pacientes` (`GET /mascotas/{id}`) y `auth` (`GET /veterinarios/{id}`), por HTTP con el JWT del usuario.

| Método | Endpoint | Roles | Validador (`CitaValidador`) / regla de negocio | Respuesta |
|---|---|---|---|---|
| GET | `/health` | P | — | 200 / 503 |
| GET | `/citas?fecha=&veterinario_id=&estado=&propietario_id=` | A R V* C* | filtros: fecha `AAAA-MM-DD`, estado válido, ids enteros. V: solo su agenda; C: solo sus mascotas | 200 · 422 |
| GET | `/citas/proximas?limite=&veterinario_id=&propietario_id=` | A R V* C* | desde hoy, sin canceladas | 200 |
| GET | `/citas/{id}` | A R V C* | C solo las suyas | 200 · 403 · 404 |
| POST | `/citas` | A R | `mascota_id`, `veterinario_id` (enteros), `fecha` válida, `hora` `HH:MM`, `motivo` (≤200). Mascota activa y veterinario activo (consultados a sus servicios); **sin doble reserva** del veterinario | 201 · 422 · 503 |
| PUT | `/citas/{id}` | A R | igual | 200 · 404 · 422 |
| PATCH | `/citas/{id}/estado` | A R V* | `estado` ∈ {programada, confirmada, atendida, cancelada}. V: solo sus citas y solo `atendida` | 200 · 403 · 422 |

## 4. clinica · `http://localhost/controlvet/api/clinica`

Depende de: `pacientes` (`GET /mascotas/{id}`, `PATCH /mascotas/{id}/peso`).

| Método | Endpoint | Roles | Validador (`ClinicaValidador`) / regla de negocio | Respuesta |
|---|---|---|---|---|
| GET | `/health` | P | — | 200 / 503 |
| GET | `/historias?mascota_id=&propietario_id=&veterinario_id=&limite=` | A R V C* | ids y límite enteros; C: solo sus mascotas | 200 · 422 |
| GET | `/historias/{id}` | A R V C* | C solo las suyas | 200 · 403 · 404 |
| POST | `/historias` | V | `mascota_id`, `motivo_consulta` (≤200), `diagnostico` obligatorios; `peso` 0–1000; `cita_id` entero. Paciente activo; autor = usuario del JWT. Sincroniza el peso con pacientes (`peso_sincronizado`) | 201 · 422 |
| PUT/PATCH | `/historias/{id}` | — | **inmutable** | 405 |
| GET | `/historias/{id}/formulas` | A R V C* | C solo las suyas | 200 |
| POST | `/historias/{id}/formulas` | V | `medicamento` (≤150), `dosis`, `frecuencia`, `duracion` (≤80) obligatorios | 201 · 404 · 422 |
| GET | `/formulas/{id}` | A R V C* | C solo las suyas | 200 · 403 · 404 |

## 5. inventario · `http://localhost/controlvet/api/inventario`

Uso exclusivo del administrador (los demás roles reciben 403).

| Método | Endpoint | Validador (`ProductoValidador`) / regla de negocio | Respuesta |
|---|---|---|---|
| GET | `/health` | — (público) | 200 / 503 |
| GET | `/productos?buscar=` | — | 200 activos |
| GET | `/productos/stock-bajo` | cantidad ≤ mínimo | 200 |
| GET | `/productos/{id}` | — | 200 · 404 |
| POST | `/productos` | `nombre` (≤120), `categoria` (≤60), `stock_minimo` y `cantidad_disponible` enteros ≥ 0, `precio_referencia` ≥ 0 | 201 (incluye `stock_bajo`) · 422 |
| PUT | `/productos/{id}` | igual, sin cantidad (no se modifica aquí) | 200 · 404 · 422 |
| PATCH | `/productos/{id}/stock` | `cantidad_disponible` entero ≥ 0; producto activo | 200 · 409 · 422 |
| PATCH | `/productos/{id}/desactivar` | baja lógica | 200 · 404 |
| GET | `/resumen` | — | 200 `{total, stock_bajo}` |

## 6. documentos · `http://localhost/controlvet/api/documentos`

Depende de: `citas`, `clinica`, `pacientes`, `auth` (lectura). El contenido del PDF siempre se obtiene de esos servicios, nunca del cuerpo de la petición.

| Método | Endpoint | Roles | Validador (`DocumentoValidador`) / regla | Respuesta |
|---|---|---|---|---|
| GET | `/health` | P | — | 200 / 503 |
| GET | `/documentos` | A R V C* | C: solo los suyos | 200 |
| POST | `/documentos/comprobante-cita/{id}` | A R V C* | referencia 1..2147483647; propiedad verificada | 200 PDF · 403 · 404 |
| POST | `/documentos/resumen-consulta/{id}` | A R V C* | igual | 200 PDF · 403 · 404 |
| POST | `/documentos/formula-medica/{id}` | A R V C* | igual | 200 PDF · 403 · 404 |

---

## Frontend (consumidor de los endpoints)

| URL | Descripción |
|---|---|
| `http://localhost/controlvet/web/` | Interfaz MVVM original (las mismas rutas `?route=` de v1.0.0) |
| `http://localhost/controlvet/web/health.php` | Salud agregada: consulta `/health` de los 6 servicios (200 / 503) |
| `http://localhost/controlvet/web/api/stock.php` | Pasarela AJAX (JWT) hacia `PATCH /api/inventario/productos/{id}/stock` |

## Mapa de dependencias entre endpoints

```
web ──► auth, pacientes, citas, clinica, inventario, documentos
citas ──► pacientes GET /mascotas/{id} · auth GET /veterinarios/{id}
clinica ──► pacientes GET /mascotas/{id} · PATCH /mascotas/{id}/peso
documentos ──► citas GET /citas/{id} · clinica GET /historias/{id}, /formulas/{id}
               pacientes GET /mascotas/{id} · auth GET /veterinarios/{id}
```
