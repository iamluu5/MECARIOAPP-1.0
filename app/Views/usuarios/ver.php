<?php
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="module-hero">
        <div>
            <span class="eyebrow">Detalle de cuenta</span>
            <h1><?= Sanitizer::html($usuario['nombre_completo'] ?? $usuario['nombre']) ?></h1>
            <p>Información de acceso, estado y roles asignados.</p>
        </div>
        <div class="action-row">
            <a class="btn" href="<?= Sanitizer::html(Url::ruta('/usuarios/editar/' . $usuario['id'])) ?>">Editar</a>
            <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/usuarios')) ?>">Volver</a>
        </div>
    </section>

    <section class="grid-cards two-cols">
        <article class="card detail-card">
            <h2>Información general</h2>
            <dl>
                <dt>Nombre</dt><dd><?= Sanitizer::html($usuario['nombre']) ?></dd>
                <dt>Apellido</dt><dd><?= Sanitizer::html($usuario['apellido']) ?></dd>
                <dt>Usuario</dt><dd>@<?= Sanitizer::html($usuario['usuario']) ?></dd>
                <dt>Correo</dt><dd><?= Sanitizer::html($usuario['correo']) ?></dd>
                <dt>Creación</dt><dd><?= Sanitizer::html($usuario['fecha_creacion']) ?></dd>
            </dl>
        </article>
        <article class="card detail-card">
            <h2>Seguridad</h2>
            <p><?= (int) $usuario['activo'] === 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>' ?></p>
            <p><?= (int) $usuario['bloqueado'] === 1 ? '<span class="badge badge-warning">Bloqueado</span>' : '<span class="badge badge-success">No bloqueado</span>' ?></p>
            <p><span class="badge">Intentos fallidos: <?= (int) $usuario['intentos_fallidos'] ?>/3</span></p>
            <?php if ((int) $usuario['bloqueado'] === 1): ?>
                <form method="POST" action="<?= Sanitizer::html(Url::ruta('/usuarios/desbloquear/' . $usuario['id'])) ?>">
                    <?= Csrf::campo() ?>
                    <button class="btn" type="submit">Desbloquear usuario</button>
                </form>
            <?php endif; ?>
        </article>
    </section>

    <section class="card">
        <h2>Roles asignados</h2>
        <div class="chips">
            <?php if (!empty($usuario['roles_texto'])): ?>
                <?php foreach (explode(',', $usuario['roles_texto']) as $rol): ?>
                    <span class="chip"><?= Sanitizer::html(trim($rol)) ?></span>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="muted">Este usuario no tiene roles asignados.</span>
            <?php endif; ?>
        </div>
    </section>
</main>
