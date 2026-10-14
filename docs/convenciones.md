# Convenciones de código, ramas, commits y revisiones

> Página de Confluence: **ControlVet › Code › Convenciones**.

## Código PHP

- PHP 8.2, `declare(strict_types=1)` en los microservicios, PSR-4 y PSR-12 (4 espacios, LF, UTF-8; ver `.editorconfig`).
- Nombres de dominio en español (`CitaService`, `guardarMascota`), como en el código original.
- Capas por servicio: `controller/` no contiene reglas de negocio; `validador/` solo valida forma y rango; `microservicio/` contiene reglas, puertos (interfaces) y adaptadores.
- Consultas siempre preparadas (PDO, `ATTR_EMULATE_PREPARES=false`).
- Errores de negocio con `HttpException` (404/409/422); nunca se exponen trazas.
- Logs JSON a stderr mediante `Logger`; prohibido registrar contraseñas o tokens.
- Toda regla nueva lleva prueba PHPUnit; toda ruta nueva se documenta en el README del servicio.

## Ramas

| Rama | Uso |
|---|---|
| `main` | Siempre desplegable. Jenkins construye y despliega desde aquí |
| `feature/CV-<n>-descripcion` | Trabajo de una historia o tarea de Jira |
| `fix/CV-<n>-descripcion` | Corrección de un defecto registrado en Jira |
| `release/<x.y>` | Estabilización opcional antes de cambiar `VERSION` |

## Commits (Conventional Commits en español)

`tipo(alcance): resumen en imperativo` + cuerpo opcional + `Refs: CV-<n>`.
Tipos: `feat`, `fix`, `test`, `docs`, `refactor`, `ci`, `ops`, `chore`. Ejemplo: `fix(citas): impedir doble reserva del veterinario`.

## Revisiones

- Ningún cambio llega a `main` sin la revisión del otro integrante (Santiago revisa a Sebastian y viceversa).
- Lista de control: pruebas en verde localmente (`composer install && vendor/bin/phpunit`), contrato API actualizado, sin secretos (`.env` nunca se versiona), sin acceso a esquemas de otro servicio.
- La revisión se registra en la incidencia de Jira (enlace al commit). GitHub se usa únicamente como repositorio.

## Versionado

`VERSION` contiene `MAJOR.MINOR`; Jenkins añade el número de build: `1.1.<build>`. Imágenes: `localhost:5000/controlvet/<servicio>:<versión>`. La línea base del monolito es la etiqueta `v1.0.0-monolito`.
