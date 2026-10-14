/**
 * Ruta: controlvet/assets/js/app.js
 * Interacciones del lado del cliente. Incluye el consumo del endpoint
 * api/stock.php autenticado con JWT (Bearer token) para actualizar
 * rápidamente el stock de un producto sin recargar la página.
 */

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-stock').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = btn.dataset.id;
            const actual = btn.dataset.cantidad;
            const nueva = prompt('Nueva cantidad disponible:', actual);
            if (nueva === null || nueva === '' || isNaN(nueva)) return;

            // El JWT emitido en el login se envía como cookie HttpOnly (controlvet_jwt)
            // y también se replica en el header Authorization si está disponible.
            fetch('api/stock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ id: id, cantidad_disponible: parseInt(nueva, 10) }),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    const fila = document.querySelector('tr[data-id="' + id + '"]');
                    if (fila) {
                        fila.querySelector('.cantidad-disponible').textContent = data.producto.cantidad_disponible;
                        fila.classList.toggle('table-danger', data.stock_bajo);
                    }
                })
                .catch(function () { alert('No fue posible actualizar el stock. Verifique la sesión.'); });
        });
    });
});
