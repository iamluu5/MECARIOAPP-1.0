<?php

use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="module-hero">
        <div>
            <span class="eyebrow">Seguridad administrativa</span>
            <h1>Gestión de usuarios</h1>
            <p>Administra las cuentas del sistema. Las cuentas internas y sus roles se crean aquí; el formulario público de registro asigna únicamente el rol Cliente.</p>
        </div>
        <div class="action-row">
            <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/dashboard')) ?>">Volver al panel</a>
            <a class="btn" href="<?= Sanitizer::html(Url::ruta('/usuarios/crear')) ?>">Crear usuario</a>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card"><strong><?= (int) ($estadisticas['total'] ?? 0) ?></strong><span>Total</span></article>
        <article class="stat-card"><strong><?= (int) ($estadisticas['activos'] ?? 0) ?></strong><span>Activos</span></article>
        <article class="stat-card"><strong><?= (int) ($estadisticas['inactivos'] ?? 0) ?></strong><span>Inactivos</span></article>
        <article class="stat-card"><strong><?= (int) ($estadisticas['bloqueados'] ?? 0) ?></strong><span>Bloqueados</span></article>
    </section>

    <section class="card">
        <form class="filter-form" method="GET" action="<?= Sanitizer::html(Url::ruta('/usuarios')) ?>">
            <div class="form-group">
                <label for="buscar">Buscar</label>
                <input id="buscar" name="buscar" value="<?= Sanitizer::html($busqueda ?? '') ?>" placeholder="Nombre, apellido, usuario o correo">
            </div>
            <div class="form-group">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="1" <?= ($estado ?? '') === '1' ? 'selected' : '' ?>>Activos</option>
                    <option value="0" <?= ($estado ?? '') === '0' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>
            <div class="form-actions compact">
                <button class="btn" type="submit">Filtrar</button>
                <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/usuarios')) ?>">Limpiar</a>
            </div>
        </form>
    </section>

    <section class="card table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Roles</th>
                    <th>Estado</th>
                    <th>Bloqueo</th>
                    <th>Intentos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($usuarios)): ?>
                <tr><td colspan="7" class="empty-state">No se encontraron usuarios.</td></tr>
            <?php endif; ?>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td>
                        <strong><?= Sanitizer::html($usuario['nombre_completo'] ?? ($usuario['nombre'] ?? '')) ?></strong>
                        <small>@<?= Sanitizer::html($usuario['usuario']) ?></small>
                    </td>
                    <td><?= Sanitizer::html($usuario['correo']) ?></td>
                    <td><?= $usuario['roles_texto'] ? '<span class="chip">' . Sanitizer::html($usuario['roles_texto']) . '</span>' : '<span class="muted">Sin rol</span>' ?></td>
                    <td><?= (int) $usuario['activo'] === 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>' ?></td>
                    <td><?= (int) $usuario['bloqueado'] === 1 ? '<span class="badge badge-warning">Bloqueado</span>' : '<span class="badge badge-success">Libre</span>' ?></td>
                    <td><?= (int) $usuario['intentos_fallidos'] ?>/3</td>
                    <td class="acciones-tabla">
                        <a class="btn btn-mini" href="<?= Sanitizer::html(Url::ruta('/usuarios/ver/' . $usuario['id'])) ?>">Ver</a>
                        <a class="btn btn-mini btn-secundario" href="<?= Sanitizer::html(Url::ruta('/usuarios/editar/' . $usuario['id'])) ?>">Editar</a>
                        <?php if ((int) $usuario['bloqueado'] === 1): ?>
                            <form method="POST" action="<?= Sanitizer::html(Url::ruta('/usuarios/desbloquear/' . $usuario['id'])) ?>">
                                <?= Csrf::campo() ?>
                                <button class="btn btn-mini" type="submit">Desbloquear</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="<?= Sanitizer::html(Url::ruta('/usuarios/estado/' . $usuario['id'])) ?>" data-confirm="¿Cambiar estado del usuario?">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="activo" value="<?= (int) $usuario['activo'] === 1 ? 0 : 1 ?>">
                            <button class="btn btn-mini <?= (int) $usuario['activo'] === 1 ? 'btn-danger' : '' ?>" type="submit">
                                <?= (int) $usuario['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
