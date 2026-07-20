<?php

use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="card form-card">
        <span class="eyebrow">Ventas</span>
        <h1>Nueva venta</h1>
        <p class="muted">
            Seleccione las piezas del inventario disponible y la cantidad
            de cada una. El precio siempre se toma automáticamente desde
            el inventario, nunca del formulario.
        </p>

        <?php if ($inventario === []): ?>
            <p class="muted">
                No hay piezas disponibles en el inventario para vender.
            </p>
        <?php else: ?>
            <form
                id="form-venta"
                action="<?= Sanitizer::html(Url::ruta('/ventas/guardar')) ?>"
                method="POST"
            >
                <?= Csrf::campo() ?>

                <div class="table-wrapper">
                    <table id="tabla-detalle">
                        <thead>
                            <tr>
                                <th>Pieza</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Las filas se agregan dinámicamente con JavaScript. -->
                        </tbody>
                    </table>
                </div>

                <button class="btn" type="button" id="btn-agregar-fila">
                    Agregar pieza
                </button>

                <div class="form-group">
                    <label for="observacion">Observación (opcional)</label>
                    <textarea
                        id="observacion"
                        name="observacion"
                        maxlength="255"
                        rows="3"
                    ></textarea>
                </div>

                <p>
                    Total estimado:
                    <strong id="total-estimado">$0.00</strong>
                </p>

                <button class="btn" type="submit">
                    Registrar venta
                </button>
            </form>
        <?php endif; ?>
    </section>
</main>

<script id="datos-inventario" type="application/json">
    <?= json_encode($inventario, JSON_UNESCAPED_UNICODE) ?>
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var datosInventario = document.getElementById('datos-inventario');

    if (!datosInventario) {
        return;
    }

    var inventario = JSON.parse(datosInventario.textContent);
    var cuerpoTabla = document.querySelector('#tabla-detalle tbody');
    var totalEstimado = document.getElementById('total-estimado');
    var botonAgregar = document.getElementById('btn-agregar-fila');
    var indiceFila = 0;

    function actualizarTotal() {
        var total = 0;

        cuerpoTabla.querySelectorAll('.subtotal-fila').forEach(function (celda) {
            total += parseFloat(celda.dataset.valor || '0');
        });

        totalEstimado.textContent = '$' + total.toFixed(2);
    }

    function construirOpciones() {
        var opciones = '<option value="">Seleccione una pieza</option>';

        inventario.forEach(function (pieza) {
            opciones += '<option value="' + pieza.id_inventario + '" '
                + 'data-precio="' + pieza.precio + '" '
                + 'data-existencia="' + pieza.cantidad + '">'
                + pieza.nombre_parte + ' - ' + pieza.marca + ' ' + pieza.modelo
                + ' (' + pieza.anio + ') - Disponible: ' + pieza.cantidad
                + '</option>';
        });

        return opciones;
    }

    function agregarFila() {
        var fila = document.createElement('tr');
        var indiceActual = indiceFila;
        indiceFila += 1;

        fila.innerHTML =
            '<td>'
            + '<select name="detalles[' + indiceActual + '][id_inventario]" required>'
            + construirOpciones()
            + '</select>'
            + '</td>'
            + '<td class="precio-fila">$0.00</td>'
            + '<td>'
            + '<input type="number" name="detalles[' + indiceActual + '][cantidad]" '
            + 'min="1" value="1" required>'
            + '</td>'
            + '<td class="subtotal-fila" data-valor="0">$0.00</td>'
            + '<td><button type="button" class="btn btn-quitar">Quitar</button></td>';

        cuerpoTabla.appendChild(fila);

        var select = fila.querySelector('select');
        var inputCantidad = fila.querySelector('input[type="number"]');
        var celdaPrecio = fila.querySelector('.precio-fila');
        var celdaSubtotal = fila.querySelector('.subtotal-fila');
        var botonQuitar = fila.querySelector('.btn-quitar');

        function recalcularFila() {
            var opcionSeleccionada = select.options[select.selectedIndex];
            var precio = opcionSeleccionada
                ? parseFloat(opcionSeleccionada.dataset.precio || '0')
                : 0;
            var existencia = opcionSeleccionada
                ? parseInt(opcionSeleccionada.dataset.existencia || '0', 10)
                : 0;

            var cantidad = parseInt(inputCantidad.value || '0', 10);

            if (existencia > 0 && cantidad > existencia) {
                cantidad = existencia;
                inputCantidad.value = String(cantidad);
            }

            inputCantidad.max = existencia > 0 ? String(existencia) : '';

            var subtotal = precio * cantidad;

            celdaPrecio.textContent = '$' + precio.toFixed(2);
            celdaSubtotal.textContent = '$' + subtotal.toFixed(2);
            celdaSubtotal.dataset.valor = String(subtotal);

            actualizarTotal();
        }

        select.addEventListener('change', recalcularFila);
        inputCantidad.addEventListener('input', recalcularFila);

        botonQuitar.addEventListener('click', function () {
            fila.remove();
            actualizarTotal();
        });

        recalcularFila();
    }

    botonAgregar.addEventListener('click', agregarFila);

    // Primera fila disponible desde que se abre el formulario.
    agregarFila();
});
</script>
