<?php

use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container payment-page">
    <a class="volver" href="<?= Sanitizer::html(Url::ruta('/checkout')) ?>">&larr; Cambiar método</a>
    <section class="payment-simulator card-simulator">
        <div class="payment-brand payment-brand-simple">
            <img src="<?= Sanitizer::html(Url::asset('img/payment/' . $marcaClave . '.svg')) ?>" alt="<?= Sanitizer::html($datosTarjeta['marca']) ?>">
        </div>
        <h1>Pago con <?= Sanitizer::html($datosTarjeta['marca']) ?></h1>
        <div class="payment-amount">$<?= Sanitizer::html(number_format((float) $total, 2)) ?></div>
        <div class="payment-delivery-summary">
            <span><?= Sanitizer::html(($entrega['metodo_entrega'] ?? 'retiro') === 'delivery' ? '🚚 Delivery' : '🏪 Retiro en local') ?></span>
            <?php if (($entrega['metodo_entrega'] ?? 'retiro') === 'delivery'): ?><small>Incluye costo de entrega de $<?= Sanitizer::html(number_format((float)($entrega['costo_entrega'] ?? 0), 2)) ?></small><?php endif; ?>
        </div>
        <form method="POST" action="<?= Sanitizer::html(Url::ruta('/pago/tarjeta/confirmar')) ?>">
            <?= Csrf::campo() ?>
            <input type="hidden" name="marca" value="<?= Sanitizer::html($marcaClave) ?>">
            <div class="form-group">
                <label>Número de tarjeta</label>
                <input class="payment-readonly-input" name="numero" value="<?= Sanitizer::html($datosTarjeta['numero']) ?>" readonly>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Vencimiento</label>
                    <input class="payment-readonly-input" name="expiracion" value="<?= Sanitizer::html($datosTarjeta['expiracion']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>CVV</label>
                    <input class="payment-readonly-input" name="cvv" value="<?= Sanitizer::html($datosTarjeta['cvv']) ?>" readonly>
                </div>
            </div>
            <button class="btn btn-block" type="submit">Confirmar pago</button>
        </form>
    </section>
</main>
