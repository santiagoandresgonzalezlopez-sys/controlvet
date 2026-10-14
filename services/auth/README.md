# Microservicio `auth`

**Responsabilidad:** autenticación, emisión de JWT, gestión de usuarios y roles.
**Datos propios:** esquema `cv_auth` (tablas `roles`, `usuarios`). Usuario de BD: `auth_svc` (solo DML sobre `cv_auth`).
**Dependencias de otros servicios:** ninguna.
**Responsable:** Santiago Gonzalez.

## Estructura

| Carpeta | Contenido |
|---|---|
| `controller/` | `AuthController`: rutas HTTP, autorización por rol, respuesta JSON |
| `validador/` | `UsuarioValidador`: validación de login, alta/edición y estado |
| `microservicio/` | `AuthService` (reglas de negocio), `UsuarioRepository` (puerto) y `PdoUsuarioRepository` (MySQL) |
| `public/index.php` | Punto de entrada (Apache `FallbackResource`) |
| `database/` | Esquema y datos semilla del servicio |
| `tests/Unit` | PHPUnit con repositorio en memoria |

## Contrato API

Autenticación: `Authorization: Bearer <JWT>` salvo `POST /auth/login` y `GET /health`.
Errores: `{"error": "...", "errors": {"campo": "mensaje"}}` con 401, 403, 404, 405, 409, 422 o 500.

| Método | Ruta | Roles | Descripción |
|---|---|---|---|
| GET | `/health` | público | Estado del proceso y de `cv_auth` (200/503) |
| POST | `/auth/login` | público | `{email, password}` → `{token, usuario}`; 401 si las credenciales son incorrectas |
| GET | `/auth/me` | autenticado | Usuario del token (401 si está inactivo) |
| GET | `/roles` | administrador | Catálogo de roles |
| GET | `/usuarios` | administrador | Lista sin `password_hash` |
| GET | `/usuarios/existe-email?email=&excluir=` | administrador | `{existe: bool}` |
| GET | `/usuarios/{id}` | administrador | Detalle |
| POST | `/usuarios` | administrador | Alta (`nombre, apellido, email, password≥6, rol_id, propietario_id?`) |
| PUT | `/usuarios/{id}` | administrador | Edición (contraseña opcional) |
| PATCH | `/usuarios/{id}/estado` | administrador | `{estado: activo\|inactivo}`; 409 si se desactiva a sí mismo |
| GET | `/veterinarios[?todos=1]` | autenticado | Veterinarios activos (o todos) |
| GET | `/veterinarios/{id}` | autenticado | Usado por `citas` y `documentos` |

## JWT emitido

`{"sub": id, "rol": "...", "email": "...", "propietario_id": n|null, "iss": "ControlVet", "iat", "exp"}` firmado HS256 con `JWT_SECRET` (aleatorio por entorno, ≥ 32 caracteres, en `config/servicio.php`). Duración: `JWT_TTL_SECONDS` (4 h por defecto).

## Configuración

`config/servicio.php` (generado por `infra/xampp/instalar.php`, no versionado): `JWT_SECRET`, `JWT_ISSUER`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `APP_VERSION`, `APP_BASE_PATH`, `APP_TIMEZONE`. Endpoints publicados en `http://localhost/<instalación>/api/auth` (ver `docs/endpoints.md`).
