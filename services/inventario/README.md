# Microservicio `inventario`

**Responsabilidad:** productos, existencias y alertas de stock bajo.
**Datos propios:** esquema `cv_inventario` (`productos`). Usuario de BD: `inventario_svc`.
**Dependencias:** ninguna. **Uso exclusivo del rol administrador** (403 para los demás).
**Responsable:** Sebastian Gomez.

## Reglas de negocio

- Stock bajo cuando `cantidad_disponible <= stock_minimo`.
- Cantidades, mínimos y precio no negativos (422; además `CHECK` en la tabla).
- La edición no modifica la cantidad: solo el endpoint de stock.
- No se actualiza el stock de un producto inactivo (409). La baja es lógica.

## Contrato API

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/health` | Estado y conexión a `cv_inventario` (público) |
| GET | `/productos?buscar=` | Activos, búsqueda por nombre/categoría |
| GET | `/productos/stock-bajo` | Productos en o bajo el mínimo |
| GET | `/productos/{id}` | Detalle |
| POST / PUT | `/productos[/{id}]` | Alta / edición |
| PATCH | `/productos/{id}/stock` | `{cantidad_disponible}` → producto + `stock_bajo` |
| PATCH | `/productos/{id}/desactivar` | Baja lógica |
| GET | `/resumen` | `{total, stock_bajo}` |
