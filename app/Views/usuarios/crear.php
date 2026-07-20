<?php
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="module-hero">
        <div><span class="eyebrow">Nuevo acceso</span><h1>Crear usuario</h1><p>Crea cuentas internas y asígnales el rol correspondiente según sus responsabilidades. Las cuentas de clientes se generan desde el registro público.</p></div>
        <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/usuarios')) ?>">Volver</a>
    </section>
    <?php $modo = 'crear'; $action = '/usuarios/guardar'; require __DIR__ . '/formulario.php'; ?>
</main>
