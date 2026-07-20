<?php

use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="card form-card">
        <span class="eyebrow">Cuenta de cliente</span>
        <h1>Crear una cuenta</h1>
        <p class="muted">Regístrate para identificarte como cliente de Mecario. La compra y salida de inventario sigue siendo registrada por personal autorizado.</p>

        <?php if (!empty($errores)): ?>
            <div class="alerta alerta--error">
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= Sanitizer::html($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= Sanitizer::html(Url::ruta('/registro')) ?>" method="POST" autocomplete="on">
            <?= Csrf::campo() ?>
            <div class="form-grid">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" maxlength="100" required value="<?= Sanitizer::html($datos['nombre'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="apellido">Apellido</label>
                    <input id="apellido" name="apellido" maxlength="100" required value="<?= Sanitizer::html($datos['apellido'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input id="usuario" name="usuario" maxlength="50" required autocomplete="username" value="<?= Sanitizer::html($datos['usuario'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" maxlength="150" required autocomplete="email" value="<?= Sanitizer::html($datos['correo'] ?? '') ?>">
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" minlength="8" maxlength="100" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="password_confirmacion">Confirmar contraseña</label>
                    <input type="password" id="password_confirmacion" name="password_confirmacion" minlength="8" maxlength="100" required autocomplete="new-password">
                </div>
            </div>
            <div class="form-actions">
                <button class="btn" type="submit">Crear cuenta</button>
                <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/login')) ?>">Ya tengo cuenta</a>
            </div>
        </form>
    </section>
</main>
