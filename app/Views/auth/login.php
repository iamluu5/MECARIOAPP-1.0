<?php

use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="card form-card">
        <span class="eyebrow">Acceso a Mecario</span>
        <h1>Iniciar sesión</h1>
        <p class="muted">El sistema registra IP, fecha y resultado de cada intento. Después de tres contraseñas incorrectas, la cuenta se bloquea temporalmente por 15 minutos.</p>

        <form action="<?= Sanitizer::html(Url::ruta('/login')) ?>" method="POST" autocomplete="on">
            <?= Csrf::campo() ?>
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" maxlength="50" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" maxlength="100" required autocomplete="current-password">
            </div>
            <div class="form-actions">
                <button class="btn" type="submit">Ingresar</button>
                <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/registro')) ?>">Crear cuenta de cliente</a>
            </div>
        </form>
    </section>
</main>
