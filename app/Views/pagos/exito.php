<?php

use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container payment-success-page">
    <section class="card success-card">
        <div class="success-check">✓</div>
        <span class="eyebrow">Compra registrada</span>
        <h1>Pago realizado correctamente</h1>
        <p class="muted">La venta #<?= (int) $venta['id_venta'] ?> se guardó en el sistema, se generó su factura y el inventario fue actualizado.</p>

        <div class="success-summary">
            <div><span>Factura</span><strong><?= Sanitizer::html((string)($venta['numero_factura'] ?? '-')) ?></strong></div>
            <div><span>Método</span><strong><?= Sanitizer::html($venta['metodo_pago']) ?></strong></div>
            <div><span>Entrega</span><strong><?= Sanitizer::html(($venta['metodo_entrega'] ?? 'retiro') === 'delivery' ? 'Delivery' : 'Retiro en local') ?></strong></div>
        </div>

        <div class="invoice-total-box">
            <div class="invoice-total-row"><span>Subtotal</span><strong>$<?= Sanitizer::html(number_format((float)($venta['subtotal'] ?? 0), 2)) ?></strong></div>
            <div class="invoice-total-row"><span>ITBMS (7%)</span><strong>$<?= Sanitizer::html(number_format((float)($venta['itbms'] ?? 0), 2)) ?></strong></div>
            <div class="invoice-total-row"><span>Entrega</span><strong>$<?= Sanitizer::html(number_format((float)($venta['costo_entrega'] ?? 0), 2)) ?></strong></div>
            <div class="invoice-total-row grand-total"><span>Total</span><strong>$<?= Sanitizer::html(number_format((float) $venta['total'], 2)) ?></strong></div>
        </div>

        <?php if (($venta['metodo_entrega'] ?? 'retiro') === 'delivery'): ?>
            <div class="delivery-confirmation"><strong>Dirección de entrega</strong><p><?= Sanitizer::html((string)($venta['direccion_entrega'] ?? '-')) ?></p><span class="muted">Contacto: <?= Sanitizer::html((string)($venta['telefono_entrega'] ?? '-')) ?></span></div>
        <?php else: ?>
            <div class="delivery-confirmation"><strong>Retiro en local</strong><p>Tu pedido quedó registrado para retiro en la sucursal principal de Mecario.</p></div>
        <?php endif; ?>

        <div class="form-actions center-actions">
            <a class="btn" href="<?= Sanitizer::html(Url::ruta('/factura/' . $venta['id_venta'])) ?>">🧾 Descargar factura PDF</a>
            <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/mis-compras')) ?>">Ver mis compras</a>
            <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/dashboard')) ?>">Volver al inicio</a>
        </div>
    </section>
</main>
