<?php
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="module-hero">
        <div><span class="eyebrow">Mi cuenta</span><h1>Cambiar contraseña</h1><p>Actualiza tu credencial de acceso sin intervención del administrador.</p></div>
        <div class="action-row"><a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/dashboard')) ?>">Volver al inicio</a></div>
    </section>
    <section class="card form-card narrow-card">
        <form method="POST" action="<?= Sanitizer::html(Url::ruta('/mi-cuenta/password')) ?>">
            <?= Csrf::campo() ?>
            <div class="form-group"><label for="password_actual">Contraseña actual</label><input type="password" id="password_actual" name="password_actual" required autocomplete="current-password"></div>
            <div class="form-group"><label for="password_nueva">Nueva contraseña</label><input type="password" id="password_nueva" name="password_nueva" required minlength="8" autocomplete="new-password"><small>Mínimo 8 caracteres.</small></div>
            <div class="form-group"><label for="password_confirmacion">Confirmar nueva contraseña</label><input type="password" id="password_confirmacion" name="password_confirmacion" required minlength="8" autocomplete="new-password"></div>
            <div class="form-actions"><button class="btn" type="submit">Actualizar contraseña</button></div>
        </form>
    </section>
</main>
