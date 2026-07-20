<?php

use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="card">
        <span class="eyebrow">Moderación</span>
        <h1>Comentarios públicos</h1>

        <p class="muted">
            <?php if ($pendientes > 0): ?>
                Hay
                <strong><?= Sanitizer::html((string) $pendientes) ?></strong>
                comentario(s) pendiente(s) de revisión.
            <?php else: ?>
                No hay comentarios pendientes por revisar.
            <?php endif; ?>
        </p>
    </section>

    <?php if ($comentarios === []): ?>
        <section class="card">
            <p class="muted">Todavía no se ha recibido ningún comentario.</p>
        </section>
    <?php else: ?>
        <section class="card table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Pieza</th>
                        <th>Visitante</th>
                        <th>Comentario</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comentarios as $comentario): ?>
                        <tr>
                            <td>
                                <a
                                    href="<?= Sanitizer::html(
                                        Url::ruta(
                                            '/parte/' . $comentario['id_inventario']
                                        )
                                    ) ?>"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <?= Sanitizer::html(
                                        $comentario['marca'] . ' '
                                        . $comentario['modelo'] . ' — '
                                        . $comentario['nombre_parte']
                                    ) ?>
                                </a>
                            </td>
                            <td>
                                <?= Sanitizer::html(
                                    $comentario['nombre_visitante']
                                ) ?>
                                <?php if (!empty($comentario['correo_visitante'])): ?>
                                    <br>
                                    <span class="muted">
                                        <?= Sanitizer::html(
                                            $comentario['correo_visitante']
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= Sanitizer::html($comentario['comentario']) ?></td>
                            <td>
                                <?= Sanitizer::html(
                                    (new DateTimeImmutable(
                                        $comentario['fecha_comentario']
                                    ))->format('d/m/Y H:i')
                                ) ?>
                            </td>
                            <td>
                                <?php if ((int) $comentario['activo'] === 0): ?>
                                    <span class="badge badge-danger">Eliminado</span>
                                <?php elseif ((int) $comentario['publicado'] === 1): ?>
                                    <span class="badge badge-success">Publicado</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td class="acciones-tabla">
                                <?php if ((int) $comentario['activo'] === 1 && (int) $comentario['publicado'] === 1): ?>
                                    <form
                                        method="POST"
                                        action="<?= Sanitizer::html(
                                            Url::ruta(
                                                '/comentarios/ocultar/'
                                                . $comentario['id_comentario']
                                            )
                                        ) ?>"
                                        data-confirm="¿Ocultar este comentario de la página pública?"
                                    >
                                        <?= Csrf::campo() ?>
                                        <button class="btn btn-secundario" type="submit">
                                            Ocultar
                                        </button>
                                    </form>
                                <?php elseif ((int) $comentario['activo'] === 1): ?>
                                    <form
                                        method="POST"
                                        action="<?= Sanitizer::html(
                                            Url::ruta(
                                                '/comentarios/aprobar/'
                                                . $comentario['id_comentario']
                                            )
                                        ) ?>"
                                    >
                                        <?= Csrf::campo() ?>
                                        <button class="btn" type="submit">
                                            Aprobar
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ((int) $comentario['activo'] === 1): ?>
                                    <form
                                        method="POST"
                                        action="<?= Sanitizer::html(
                                            Url::ruta(
                                                '/comentarios/eliminar/'
                                                . $comentario['id_comentario']
                                            )
                                        ) ?>"
                                        data-confirm="¿Eliminar lógicamente este comentario?"
                                    >
                                        <?= Csrf::campo() ?>
                                        <button class="btn btn-danger" type="submit">
                                            Eliminar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</main>
