<?php

use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container payment-page">
    <a class="volver" href="<?= Sanitizer::html(Url::ruta('/checkout')) ?>">&larr; Cambiar método</a>
    <section class="payment-simulator yappy-simulator">
        <div class="payment-brand payment-brand-simple">
            <img src="<?= Sanitizer::html(Url::asset('img/payment/yappy.svg')) ?>" alt="Yappy">
        </div>
        <h1>Mecario S.A.</h1>
        <p class="muted">Pago pendiente</p>
        <div class="payment-amount">$<?= Sanitizer::html(number_format((float) $total, 2)) ?></div>
        <div class="payment-delivery-summary">
            <span><?= Sanitizer::html(($entrega['metodo_entrega'] ?? 'retiro') === 'delivery' ? '🚚 Delivery' : '🏪 Retiro en local') ?></span>
            <?php if (($entrega['metodo_entrega'] ?? 'retiro') === 'delivery'): ?><small>Incluye costo de entrega de $<?= Sanitizer::html(number_format((float)($entrega['costo_entrega'] ?? 0), 2)) ?></small><?php endif; ?>
        </div>
        <img class="fake-qr" src="<?= Sanitizer::html(Url::asset('img/payment/qr.svg')) ?>" alt="Código QR de pago">
        <p class="payment-reference">Referencia: <strong><?= Sanitizer::html($referencia) ?></strong></p>
        <div class="payment-status"><span class="status-dot"></span> En espera de confirmación</div>
        <form method="POST" action="<?= Sanitizer::html(Url::ruta('/pago/yappy/confirmar')) ?>">
            <?= Csrf::campo() ?>
            <button class="btn btn-block" type="submit">Confirmar pago realizado</button>
        </form>
    </section>
</main>
