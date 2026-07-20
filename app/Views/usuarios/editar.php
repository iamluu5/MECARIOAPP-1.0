<?php
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="module-hero">
        <div><span class="eyebrow">Actualizar acceso</span><h1>Editar usuario</h1><p>Actualiza los datos, roles o contraseña del usuario seleccionado.</p></div>
        <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/usuarios')) ?>">Volver</a>
    </section>
    <?php $modo = 'editar'; $action = '/usuarios/actualizar/' . $usuario['id']; require __DIR__ . '/formulario.php'; ?>
</main>
