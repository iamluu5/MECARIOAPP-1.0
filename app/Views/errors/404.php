<?php

use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="card">
        <span class="eyebrow">Error 404</span>
        <h1>Página no encontrada</h1>
        <p>La dirección solicitada no está registrada en el sistema.</p>
        <a class="btn" href="<?= Sanitizer::html(Url::ruta('/')) ?>">
            Volver al inicio
        </a>
    </section>
</main>
