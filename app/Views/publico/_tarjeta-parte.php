<?php

use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;

$miniatura = $pieza['thumbnail'] ?? null;
?>
<article class="card card-parte">
    <a class="card-parte-link" href="<?= Sanitizer::html(Url::ruta('/parte/' . $pieza['id_inventario'])) ?>">
        <div class="card-parte-imagen">
            <?php if ($miniatura): ?>
                <img src="<?= Sanitizer::html(Url::upload($miniatura)) ?>" alt="<?= Sanitizer::html($pieza['nombre_parte']) ?>" loading="lazy">
            <?php else: ?>
                <span class="card-parte-sin-imagen" aria-hidden="true"><?= Sanitizer::html(mb_substr($pieza['nombre_parte'], 0, 1)) ?></span>
            <?php endif; ?>
            <span class="badge-seccion"><?= Sanitizer::html($pieza['nombre_seccion']) ?></span>
        </div>

        <div class="card-parte-info">
            <h3><?= Sanitizer::html($pieza['nombre_parte']) ?></h3>
            <p class="muted"><?= Sanitizer::html($pieza['marca'] . ' ' . $pieza['modelo'] . ' · ' . $pieza['anio']) ?></p>
            <div class="card-parte-precio">
                <span class="precio">$<?= Sanitizer::html(number_format((float) $pieza['precio'], 2)) ?></span>
                <span class="existencias"><?= Sanitizer::html((string) $pieza['cantidad']) ?> disponibles</span>
            </div>
        </div>
    </a>

    <form class="card-cart-form" method="POST" action="<?= Sanitizer::html(Url::ruta('/carrito/agregar/' . $pieza['id_inventario'])) ?>">
        <?= Csrf::campo() ?>
        <input type="hidden" name="cantidad" value="1">
        <input type="hidden" name="volver" value="/catalogo">
        <button class="btn btn-cart" type="submit"><span aria-hidden="true">🛒</span> Agregar al carrito</button>
    </form>
</article>
