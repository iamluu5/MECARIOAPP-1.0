<?php

use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="card">
        <span class="eyebrow">Error 405</span>
        <h1>Método no permitido</h1>
        <p>La ruta existe, pero no admite el tipo de solicitud enviado.</p>
        <a class="btn" href="<?= Sanitizer::html(Url::ruta('/')) ?>">
            Volver al inicio
        </a>
    </section>
</main>
