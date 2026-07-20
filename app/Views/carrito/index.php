<?php

use App\Core\Session;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="module-hero">
        <div><span class="eyebrow">Compra</span><h1>Mi carrito</h1><p>Revisa las piezas y cantidades antes de continuar.</p></div>
        <div class="action-row"><a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/catalogo')) ?>">Seguir comprando</a></div>
    </section>

    <?php if ($items === []): ?>
        <section class="card empty-cart"><div class="empty-cart-icon">🛒</div><h2>Tu carrito está vacío</h2><a class="btn" href="<?= Sanitizer::html(Url::ruta('/catalogo')) ?>">Explorar catálogo</a></section>
    <?php else: ?>
        <div class="cart-layout">
            <section class="card">
                <form method="POST" action="<?= Sanitizer::html(Url::ruta('/carrito/actualizar')) ?>">
                    <?= Csrf::campo() ?>
                    <div class="cart-items">
                        <?php foreach ($items as $item): ?>
                            <article class="cart-item">
                                <div class="cart-thumb">
                                    <?php if (!empty($item['thumbnail'])): ?><img src="<?= Sanitizer::html(Url::upload($item['thumbnail'])) ?>" alt="<?= Sanitizer::html($item['nombre_parte']) ?>"><?php else: ?><span><?= Sanitizer::html(mb_substr($item['nombre_parte'],0,1)) ?></span><?php endif; ?>
                                </div>
                                <div class="cart-copy">
                                    <h3><?= Sanitizer::html($item['nombre_parte']) ?></h3>
                                    <p class="muted"><?= Sanitizer::html($item['marca'] . ' ' . $item['modelo'] . ' · ' . $item['anio']) ?></p>
                                    <span class="precio">$<?= Sanitizer::html(number_format((float)$item['precio'],2)) ?></span>
                                </div>
                                <div class="cart-qty"><label for="qty-<?= (int)$item['id_inventario'] ?>">Cantidad</label><input id="qty-<?= (int)$item['id_inventario'] ?>" type="number" name="cantidades[<?= (int)$item['id_inventario'] ?>]" min="0" max="<?= (int)$item['cantidad'] ?>" value="<?= (int)$item['cantidad_carrito'] ?>"></div>
                                <div class="cart-subtotal"><small>Subtotal</small><strong>$<?= Sanitizer::html(number_format((float)$item['subtotal'],2)) ?></strong></div>
                                <button class="btn btn-mini btn-danger" type="submit" formaction="<?= Sanitizer::html(Url::ruta('/carrito/eliminar/' . $item['id_inventario'])) ?>">Quitar</button>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-actions"><button class="btn btn-secundario" type="submit">Actualizar cantidades</button></div>
                </form>
            </section>

            <aside class="card cart-summary">
                <span class="eyebrow">Resumen</span>
                <div class="summary-line"><span>Productos</span><strong><?= count($items) ?></strong></div>
                <div class="summary-total"><span>Total</span><strong>$<?= Sanitizer::html(number_format((float)$total,2)) ?></strong></div>
                <?php if (Session::estaAutenticado() && Session::tienePermiso('compras.crear')): ?>
                    <a class="btn btn-block" href="<?= Sanitizer::html(Url::ruta('/checkout')) ?>">Continuar al pago</a>
                <?php else: ?>
                    <a class="btn btn-block" href="<?= Sanitizer::html(Url::ruta('/login')) ?>">Iniciar sesión para comprar</a>
                <?php endif; ?>
                <form method="POST" action="<?= Sanitizer::html(Url::ruta('/carrito/vaciar')) ?>" data-confirm="¿Vaciar todo el carrito?">
                    <?= Csrf::campo() ?><button class="btn btn-secundario btn-block" type="submit">Vaciar carrito</button>
                </form>
            </aside>
        </div>
    <?php endif; ?>
</main>
