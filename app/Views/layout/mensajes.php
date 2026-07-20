<?php

use App\Core\Session;
use App\Helpers\Sanitizer;

$mensajes = Session::consumirMensajes();
?>
<?php if ($mensajes !== []): ?>
    <section class="flash-container" aria-live="polite">
        <?php foreach ($mensajes as $mensaje): ?>
            <div
                class="flash flash-<?= Sanitizer::html(
                    $mensaje['tipo'] ?? 'info'
                ) ?>"
            >
                <?= Sanitizer::html($mensaje['texto'] ?? '') ?>

                <button
                    class="flash-close"
                    type="button"
                    aria-label="Cerrar mensaje"
                >
                    &times;
                </button>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
