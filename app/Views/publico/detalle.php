<?php

use App\Core\Session;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;

$imagenGrande = $parte['imagen_grande'] ?? $parte['thumbnail'] ?? null;
?>
<main class="container">
    <a class="volver" href="<?= Sanitizer::html(Url::ruta('/catalogo')) ?>">&larr; Volver al catálogo</a>

    <section class="detalle-parte">
        <div class="detalle-imagen">
            <?php if ($imagenGrande): ?>
                <img src="<?= Sanitizer::html(Url::upload($imagenGrande)) ?>" alt="<?= Sanitizer::html($parte['nombre_parte']) ?>">
            <?php else: ?>
                <div class="detalle-sin-imagen" aria-hidden="true"><?= Sanitizer::html(mb_substr($parte['nombre_parte'], 0, 1)) ?></div>
            <?php endif; ?>
        </div>

        <div class="card detalle-info">
            <span class="chip"><?= Sanitizer::html($parte['nombre_seccion']) ?></span>
            <h1><?= Sanitizer::html($parte['nombre_parte']) ?></h1>
            <p class="muted"><?= Sanitizer::html($parte['marca'] . ' ' . $parte['modelo'] . ' · ' . $parte['anio']) ?></p>

            <?php if (!empty($parte['descripcion_corta'])): ?><p><?= Sanitizer::html($parte['descripcion_corta']) ?></p><?php endif; ?>

            <dl class="detalle-datos">
                <div><dt>Precio</dt><dd class="precio">$<?= Sanitizer::html(number_format((float) $parte['precio'], 2)) ?></dd></div>
                <div><dt>Unidades disponibles</dt><dd><?= Sanitizer::html((string) $parte['cantidad']) ?></dd></div>
                <div><dt>Condición</dt><dd><?= Sanitizer::html($parte['condicion_pieza']) ?></dd></div>
            </dl>

            <?php if (!empty($parte['observaciones'])): ?>
                <div class="detail-observation"><strong>Observaciones</strong><p class="muted"><?= Sanitizer::html($parte['observaciones']) ?></p></div>
            <?php endif; ?>

            <form class="add-cart-detail" method="POST" action="<?= Sanitizer::html(Url::ruta('/carrito/agregar/' . $parte['id_inventario'])) ?>">
                <?= Csrf::campo() ?>
                <input type="hidden" name="volver" value="/parte/<?= (int) $parte['id_inventario'] ?>">
                <div class="quantity-row">
                    <div class="form-group"><label for="cantidad">Cantidad</label><input id="cantidad" type="number" name="cantidad" min="1" max="<?= (int) $parte['cantidad'] ?>" value="1" required></div>
                    <button class="btn" type="submit"><span aria-hidden="true">🛒</span> Agregar al carrito</button>
                </div>
            </form>
        </div>
    </section>

    <section class="card seccion-comentarios">
        <h2>Comentarios de otros visitantes</h2>
        <?php if ($comentarios === []): ?>
            <p class="muted">Todavía no hay comentarios publicados para esta pieza. Sea el primero en comentar.</p>
        <?php else: ?>
            <ul class="lista-comentarios">
                <?php foreach ($comentarios as $comentario): ?>
                    <li class="comentario">
                        <div class="comentario-cabecera"><strong><?= Sanitizer::html($comentario['nombre_visitante']) ?></strong><span class="muted"><?= Sanitizer::html((new DateTimeImmutable($comentario['fecha_comentario']))->format('d/m/Y')) ?></span></div>
                        <p><?= Sanitizer::html($comentario['comentario']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (Session::estaAutenticado() && Session::esCliente() && Session::tienePermiso('comentarios.crear')): ?>
            <form class="form-comentario" action="<?= Sanitizer::html(Url::ruta('/comentarios/guardar')) ?>" method="POST">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id_inventario" value="<?= Sanitizer::html((string) $parte['id_inventario']) ?>">
                <div class="commenting-as">
                    <span>Comentando como</span>
                    <strong><?= Sanitizer::html(trim((string) (Session::usuario()['nombre'] ?? '') . ' ' . (string) (Session::usuario()['apellido'] ?? ''))) ?></strong>
                </div>
                <div class="form-group"><label for="comentario">Comentario</label><textarea id="comentario" name="comentario" rows="4" maxlength="500" required></textarea></div>
                <button class="btn" type="submit">Enviar comentario</button>
                <p class="muted nota-moderacion">Tu comentario aparecerá después de ser revisado por un moderador.</p>
            </form>
        <?php elseif (!Session::estaAutenticado()): ?>
            <div class="comment-login-box">
                <div><strong>¿Quieres dejar tu opinión?</strong><p class="muted">Inicia sesión o crea una cuenta de cliente para comentar.</p></div>
                <div class="action-row"><a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/login')) ?>">Iniciar sesión</a><a class="btn" href="<?= Sanitizer::html(Url::ruta('/registro')) ?>">Crear cuenta</a></div>
            </div>
        <?php endif; ?>
    </section>
</main>
