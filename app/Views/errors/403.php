<?php

use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="card">
        <span class="eyebrow">Error 403</span>
        <h1>Acceso no autorizado</h1>
        <p>
            No cuenta con permisos suficientes para ver esta sección
            del sistema.
        </p>
        <a class="btn" href="<?= Sanitizer::html(Url::ruta('/')) ?>">
            Volver al inicio
        </a>
    </section>
</main>
