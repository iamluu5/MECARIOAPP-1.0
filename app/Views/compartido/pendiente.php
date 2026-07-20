<?php

use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="card">
        <span class="eyebrow">Módulo asignado</span>

        <h1><?= Sanitizer::html($modulo ?? 'Módulo') ?></h1>

        <p>
            La acción
            <strong><?= Sanitizer::html($accion ?? '') ?></strong>
            todavía es una plantilla inicial.
        </p>

        <p>
            Responsable:
            <strong><?= Sanitizer::html($responsable ?? 'Equipo') ?></strong>.
        </p>

        <p class="muted">
            El archivo existe para que la navegación inicial no produzca
            errores 404. El responsable debe reemplazar esta pantalla por
            la lógica real del controlador y su vista.
        </p>

        <a class="btn" href="<?= Sanitizer::html(Url::ruta('/dashboard')) ?>">
            Regresar al HOME
        </a>
    </section>
</main>
