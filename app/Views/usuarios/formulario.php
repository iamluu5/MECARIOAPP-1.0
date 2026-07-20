<?php

use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;

$usuario = $usuario ?? [];
$roles = $roles ?? [];
$rolesSeleccionados = array_map('intval', $rolesSeleccionados ?? []);
$errores = $errores ?? [];
$modo = $modo ?? 'crear';
$action = $action ?? '/usuarios/guardar';
?>
<?php if ($errores !== []): ?>
    <div class="alerta alerta--error">
        <strong>Revisa estos puntos:</strong>
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= Sanitizer::html($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="card form-wide" method="POST" action="<?= Sanitizer::html(Url::ruta($action)) ?>">
    <?= Csrf::campo() ?>

    <div class="form-grid">
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" required value="<?= Sanitizer::html($usuario['nombre'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="apellido">Apellido</label>
            <input id="apellido" name="apellido" required value="<?= Sanitizer::html($usuario['apellido'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="usuario">Usuario</label>
            <input id="usuario" name="usuario" required value="<?= Sanitizer::html($usuario['usuario'] ?? '') ?>" placeholder="ejemplo: luisa.admin">
        </div>
        <div class="form-group">
            <label for="correo">Correo</label>
            <input id="correo" type="email" name="correo" required value="<?= Sanitizer::html($usuario['correo'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="password"><?= $modo === 'crear' ? 'Contraseña' : 'Nueva contraseña' ?></label>
            <input id="password" type="password" name="password" <?= $modo === 'crear' ? 'required' : '' ?> placeholder="<?= $modo === 'crear' ? 'Mínimo 8 caracteres' : 'Dejar vacío si no se cambiará' ?>">
        </div>
    </div>

    <label class="check-line">
        <input type="checkbox" name="activo" value="1" <?= (int) ($usuario['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
        Usuario activo
    </label>

    <section class="inner-panel">
        <h2>Roles asignados</h2>
        <p class="muted">Selecciona al menos un rol para controlar los permisos del usuario.</p>
        <div class="checkbox-grid">
            <?php foreach ($roles as $rol): ?>
                <label class="option-card">
                    <input type="checkbox" name="roles[]" value="<?= (int) $rol['id'] ?>" <?= in_array((int) $rol['id'], $rolesSeleccionados, true) ? 'checked' : '' ?>>
                    <span><strong><?= Sanitizer::html($rol['nombre']) ?></strong><small><?= Sanitizer::html($rol['descripcion'] ?? '') ?></small></span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="form-actions">
        <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/usuarios')) ?>">Cancelar</a>
        <button class="btn" type="submit"><?= $modo === 'crear' ? 'Guardar usuario' : 'Actualizar usuario' ?></button>
    </div>
</form>
