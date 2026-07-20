<?php

use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="module-hero">
        <div><span class="eyebrow">Historial</span><h1>Mis compras</h1></div>
        <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/dashboard')) ?>">Volver al inicio</a>
    </section>
    <section class="card table-wrapper">
        <table>
            <thead><tr><th>#</th><th>Factura</th><th>Fecha</th><th>Método</th><th>Entrega</th><th>ITBMS</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php if ($compras === []): ?>
                <tr><td colspan="9" class="empty-state">Todavía no has realizado compras.</td></tr>
            <?php endif; ?>
            <?php foreach ($compras as $compra): ?>
                <tr>
                    <td>#<?= (int) $compra['id_venta'] ?></td>
                    <td><?= Sanitizer::html((string)($compra['numero_factura'] ?? '-')) ?></td>
                    <td><?= Sanitizer::html($compra['fecha_venta']) ?></td>
                    <td><?= Sanitizer::html($compra['metodo_pago']) ?></td>
                    <td><?= Sanitizer::html(($compra['metodo_entrega'] ?? 'retiro') === 'delivery' ? 'Delivery' : 'Retiro en local') ?></td>
                    <td>$<?= Sanitizer::html(number_format((float)($compra['itbms'] ?? 0), 2)) ?></td>
                    <td>$<?= Sanitizer::html(number_format((float) $compra['total'], 2)) ?></td>
                    <td><span class="badge badge-success"><?= Sanitizer::html($compra['estado_pago'] === 'confirmado' ? 'Pago confirmado' : $compra['estado_pago']) ?></span></td>
                    <td>
                        <div class="action-row">
                            <a class="btn btn-mini btn-secundario" href="<?= Sanitizer::html(Url::ruta('/compra/exito/' . $compra['id_venta'])) ?>">Ver</a>
                            <a class="btn btn-mini" href="<?= Sanitizer::html(Url::ruta('/factura/' . $compra['id_venta'])) ?>">Factura PDF</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
