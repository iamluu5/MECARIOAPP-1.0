<?php

use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="card catalogo-encabezado">
        <span class="eyebrow">Catálogo público</span>
        <div class="catalog-title-row"><h1>Partes disponibles</h1><a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/carrito')) ?>"><span aria-hidden="true">🛒</span> Ver carrito</a></div>

        <form
            class="form-busqueda"
            action="<?= Sanitizer::html(Url::ruta('/catalogo')) ?>"
            method="GET"
        >
            <div class="form-group form-group-busqueda">
                <label for="q">Buscar por parte, marca o modelo</label>
                <input
                    type="text"
                    id="q"
                    name="q"
                    placeholder="Ejemplo: puerta, Toyota, Corolla"
                    value="<?= Sanitizer::html($busqueda) ?>"
                >
            </div>
            <button class="btn" type="submit">Buscar</button>
        </form>

        <?php if ($secciones !== []): ?>
            <div class="chips">
                <a
                    class="chip <?= $idSeccion === null ? 'chip-activo' : '' ?>"
                    href="<?= Sanitizer::html(Url::ruta('/catalogo')) ?>"
                >
                    Todas
                </a>
                <?php foreach ($secciones as $seccion): ?>
                    <a
                        class="chip <?= (
                            $idSeccion === (int) $seccion['id_seccion']
                        ) ? 'chip-activo' : '' ?>"
                        href="<?= Sanitizer::html(
                            Url::ruta('/categoria/' . $seccion['id_seccion'])
                        ) ?>"
                    >
                        <?= Sanitizer::html($seccion['nombre_seccion']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($resultados === []): ?>
        <section class="card catalogo-vacio">
            <p class="muted">
                No se encontraron piezas con esos criterios. Intente con
                otro término de búsqueda o revise otra categoría.
            </p>
        </section>
    <?php else: ?>
        <div class="grid-cards grid-catalogo">
            <?php foreach ($resultados as $pieza): ?>
                <?php include __DIR__ . '/_tarjeta-parte.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
