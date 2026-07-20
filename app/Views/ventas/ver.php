<?php

use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="card">
        <span class="eyebrow">Ventas</span>
        <h1>Venta #<?= Sanitizer::html((string) $venta['id_venta']) ?></h1>

        <div class="grid-cards two-cols">
            <article class="inner-panel">
                <h2>Información general</h2>
                <dl class="detail-list">
                    <dt>Factura</dt><dd><?= Sanitizer::html((string)($venta['numero_factura'] ?? 'Pendiente')) ?></dd>
                    <dt>Fecha</dt><dd><?= Sanitizer::html((string)$venta['fecha_venta']) ?></dd>
                    <dt>Usuario</dt><dd><?= Sanitizer::html((string)$venta['usuario']) ?></dd>
                    <dt>Origen</dt><dd><?= Sanitizer::html(($venta['origen'] ?? 'interno') === 'cliente' ? 'Compra de cliente' : 'Venta interna') ?></dd>
                    <dt>Método</dt><dd><?= Sanitizer::html((string)($venta['metodo_pago'] ?? 'Efectivo')) ?></dd>
                    <dt>Estado pago</dt><dd><?= Sanitizer::html(($venta['estado_pago'] ?? 'no_aplica') === 'confirmado' ? 'Confirmado' : 'No aplica') ?></dd>
                    <dt>Referencia</dt><dd><?= Sanitizer::html((string)($venta['referencia_pago'] ?? '-')) ?></dd>
                    <dt>Entrega</dt><dd><?= Sanitizer::html(($venta['metodo_entrega'] ?? 'retiro') === 'delivery' ? 'Delivery' : 'Retiro en local') ?></dd>
                    <?php if (($venta['metodo_entrega'] ?? 'retiro') === 'delivery'): ?>
                        <dt>Dirección</dt><dd><?= Sanitizer::html((string)($venta['direccion_entrega'] ?? '-')) ?></dd>
                        <dt>Teléfono</dt><dd><?= Sanitizer::html((string)($venta['telefono_entrega'] ?? '-')) ?></dd>
                    <?php endif; ?>
                    <dt>Estado venta</dt><dd><?= Sanitizer::html(ucfirst((string)$venta['estado'])) ?></dd>
                </dl>
            </article>
            <article class="inner-panel">
                <h2>Resumen de factura</h2>
                <div class="invoice-total-box">
                    <div class="invoice-total-row"><span>Subtotal</span><strong>$<?= Sanitizer::html(number_format((float)($venta['subtotal'] ?? 0), 2)) ?></strong></div>
                    <div class="invoice-total-row"><span>ITBMS (7%)</span><strong>$<?= Sanitizer::html(number_format((float)($venta['itbms'] ?? 0), 2)) ?></strong></div>
                    <div class="invoice-total-row"><span>Entrega</span><strong>$<?= Sanitizer::html(number_format((float)($venta['costo_entrega'] ?? 0), 2)) ?></strong></div>
                    <div class="invoice-total-row grand-total"><span>Total</span><strong>$<?= Sanitizer::html(number_format((float)$venta['total'], 2)) ?></strong></div>
                </div>
                <h3>Observación</h3>
                <p class="muted"><?= Sanitizer::html((string)($venta['observacion'] ?? 'Sin observaciones')) ?></p>
            </article>
        </div>

        <h2>Piezas vendidas</h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Parte</th><th>Categoría</th><th>Auto</th><th>Cantidad</th><th>Precio unitario</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($detalle as $item): ?>
                    <tr>
                        <td><?= Sanitizer::html((string)$item['nombre_parte']) ?></td>
                        <td><?= Sanitizer::html((string)$item['categoria']) ?></td>
                        <td><?= Sanitizer::html($item['marca'].' '.$item['modelo'].' ('.$item['anio'].')') ?></td>
                        <td><?= Sanitizer::html((string)$item['cantidad']) ?></td>
                        <td>$<?= Sanitizer::html(number_format((float)$item['precio_unitario'],2)) ?></td>
                        <td>$<?= Sanitizer::html(number_format((float)$item['subtotal'],2)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="form-actions">
            <a class="btn" href="<?= Sanitizer::html(Url::ruta('/factura/'.$venta['id_venta'])) ?>">🧾 Descargar factura PDF</a>
            <a class="btn btn-excel" href="<?= Sanitizer::html(Url::ruta('/ventas/exportar/'.$venta['id_venta'])) ?>"><img class="btn-icon" src="<?= Sanitizer::html(Url::asset('img/payment/excel-icon.svg')) ?>" alt=""> Exportar a Excel</a>
            <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/ventas')) ?>">Volver al listado</a>
        </div>
    </section>
</main>
