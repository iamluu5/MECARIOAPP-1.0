<?php

use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container checkout-page">
    <a class="volver" href="<?= Sanitizer::html(Url::ruta('/carrito')) ?>">&larr; Volver al carrito</a>
    <section class="module-hero">
        <div>
            <span class="eyebrow">Finalizar compra</span>
            <h1>Entrega y pago</h1>
            <p>Selecciona cómo deseas recibir tu compra y el método con el que deseas pagar.</p>
        </div>
    </section>

    <div class="checkout-layout">
        <section class="card payment-panel">
            <form method="POST" action="<?= Sanitizer::html(Url::ruta('/checkout/metodo')) ?>" id="checkout-form">
                <?= Csrf::campo() ?>

                <div class="checkout-section-title">
                    <span class="checkout-step">1</span>
                    <div><h2>Método de entrega</h2><p class="muted">¿Cómo deseas recibir tus piezas?</p></div>
                </div>

                <div class="delivery-options">
                    <label class="delivery-option">
                        <input type="radio" name="metodo_entrega" value="retiro" checked required>
                        <span class="delivery-icon" aria-hidden="true">🏪</span>
                        <span><strong>Retiro en el local</strong><small>Recoge tu pedido en la sucursal principal de Mecario.</small><b>Sin costo adicional</b></span>
                    </label>
                    <label class="delivery-option">
                        <input type="radio" name="metodo_entrega" value="delivery" required>
                        <span class="delivery-icon" aria-hidden="true">🚚</span>
                        <span><strong>Delivery</strong><small>Coordinamos la entrega en la dirección indicada.</small><b>+$<?= Sanitizer::html(number_format((float) $costoDelivery, 2)) ?></b></span>
                    </label>
                </div>

                <div id="delivery-fields" class="delivery-fields" hidden>
                    <div class="form-group">
                        <label for="direccion_entrega">Dirección de entrega</label>
                        <textarea id="direccion_entrega" name="direccion_entrega" rows="3" maxlength="255" placeholder="Ej.: corregimiento, barriada, calle y punto de referencia"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="telefono_entrega">Teléfono de contacto</label>
                        <input id="telefono_entrega" name="telefono_entrega" type="tel" maxlength="20" placeholder="Ej.: 6000-0000">
                    </div>
                </div>

                <div class="checkout-section-title payment-title">
                    <span class="checkout-step">2</span>
                    <div><h2>Método de pago</h2><p class="muted">Selecciona una opción para continuar.</p></div>
                </div>

                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="metodo" value="yappy" required>
                        <span class="payment-logo-wrap"><img src="<?= Sanitizer::html(Url::asset('img/payment/yappy.svg')) ?>" alt="Yappy"></span>
                        <span><strong>Yappy</strong><small>Pago mediante Yappy</small></span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="metodo" value="visa" required>
                        <span class="payment-logo-wrap"><img src="<?= Sanitizer::html(Url::asset('img/payment/visa.svg')) ?>" alt="Visa"></span>
                        <span><strong>Visa</strong><small>Pago con tarjeta Visa</small></span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="metodo" value="mastercard" required>
                        <span class="payment-logo-wrap"><img src="<?= Sanitizer::html(Url::asset('img/payment/mastercard.svg')) ?>" alt="Mastercard"></span>
                        <span><strong>Mastercard</strong><small>Pago con tarjeta Mastercard</small></span>
                    </label>
                </div>
                <button class="btn btn-block" type="submit">Continuar con el pago</button>
            </form>
        </section>

        <aside class="card order-summary" data-base-total="<?= Sanitizer::html(number_format((float) $total, 2, '.', '')) ?>" data-delivery="<?= Sanitizer::html(number_format((float) $costoDelivery, 2, '.', '')) ?>">
            <span class="eyebrow">Tu compra</span>
            <?php foreach ($items as $item): ?>
                <div class="order-line">
                    <span><?= Sanitizer::html($item['nombre_parte']) ?> × <?= (int) $item['cantidad_carrito'] ?></span>
                    <strong>$<?= Sanitizer::html(number_format((float) $item['subtotal'], 2)) ?></strong>
                </div>
            <?php endforeach; ?>
            <div class="summary-line"><span>Subtotal</span><strong>$<?= Sanitizer::html(number_format((float) $subtotal, 2)) ?></strong></div>
            <div class="summary-line"><span>ITBMS (7%)</span><strong>$<?= Sanitizer::html(number_format((float) $itbms, 2)) ?></strong></div>
            <div class="summary-line" id="delivery-cost-line"><span>Entrega</span><strong id="delivery-cost">$0.00</strong></div>
            <div class="summary-total">
                <span>Total</span>
                <strong id="checkout-total">$<?= Sanitizer::html(number_format((float) $total, 2)) ?></strong>
            </div>
        </aside>
    </div>
</main>
<script>
(() => {
    const form = document.getElementById('checkout-form');
    if (!form) return;
    const fields = document.getElementById('delivery-fields');
    const address = document.getElementById('direccion_entrega');
    const phone = document.getElementById('telefono_entrega');
    const summary = document.querySelector('.order-summary');
    const deliveryCost = document.getElementById('delivery-cost');
    const totalNode = document.getElementById('checkout-total');
    const baseTotal = Number(summary?.dataset.baseTotal || 0);
    const fee = Number(summary?.dataset.delivery || 0);

    function updateDelivery() {
        const selected = form.querySelector('input[name="metodo_entrega"]:checked')?.value || 'retiro';
        const isDelivery = selected === 'delivery';
        fields.hidden = !isDelivery;
        address.required = isDelivery;
        phone.required = isDelivery;
        deliveryCost.textContent = '$' + (isDelivery ? fee : 0).toFixed(2);
        totalNode.textContent = '$' + (baseTotal + (isDelivery ? fee : 0)).toFixed(2);
    }

    form.querySelectorAll('input[name="metodo_entrega"]').forEach(input => input.addEventListener('change', updateDelivery));
    updateDelivery();
})();
</script>
