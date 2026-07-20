<?php

use App\Core\Session;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Models\Carrito;

$esCliente = Session::esCliente();
$cantidadCarrito = (new Carrito())->cantidadTotal();
?>
<nav class="main-menu" aria-label="Menú principal">
    <ul>
        <?php if (Session::estaAutenticado()): ?>
            <li><a href="<?= Sanitizer::html(Url::ruta('/dashboard')) ?>">Inicio</a></li>

            <?php if ($esCliente): ?>
                <li><a href="<?= Sanitizer::html(Url::ruta('/catalogo')) ?>">Catálogo</a></li>
                <li><a href="<?= Sanitizer::html(Url::ruta('/carrito')) ?>">Carrito <span class="cart-count"><?= (int) $cantidadCarrito ?></span></a></li>
                <li><a href="<?= Sanitizer::html(Url::ruta('/mis-compras')) ?>">Mis compras</a></li>
            <?php else: ?>
                <?php if (Session::tienePermiso('usuarios.ver')): ?><li><a href="<?= Sanitizer::html(Url::ruta('/usuarios')) ?>">Usuarios</a></li><?php endif; ?>
                <?php if (Session::tienePermiso('roles.ver')): ?><li><a href="<?= Sanitizer::html(Url::ruta('/roles')) ?>">Roles</a></li><?php endif; ?>
                <?php if (Session::tienePermiso('autos.ver')): ?><li><a href="<?= Sanitizer::html(Url::ruta('/autos')) ?>">Autos</a></li><?php endif; ?>
                <?php if (Session::tienePermiso('partes.ver')): ?><li><a href="<?= Sanitizer::html(Url::ruta('/partes')) ?>">Partes</a></li><?php endif; ?>
                <?php if (Session::tienePermiso('secciones.ver')): ?><li><a href="<?= Sanitizer::html(Url::ruta('/secciones')) ?>">Secciones</a></li><?php endif; ?>
                <?php if (Session::tienePermiso('inventario.ver')): ?><li><a href="<?= Sanitizer::html(Url::ruta('/inventario')) ?>">Inventario</a></li><?php endif; ?>
                <?php if (Session::tienePermiso('ventas.ver')): ?><li><a href="<?= Sanitizer::html(Url::ruta('/ventas')) ?>">Ventas</a></li><?php endif; ?>
                <?php if (Session::tienePermiso('comentarios.moderar')): ?><li><a href="<?= Sanitizer::html(Url::ruta('/comentarios')) ?>">Comentarios</a></li><?php endif; ?>
                <?php if (Session::tienePermiso('seguridad.ver')): ?><li><a href="<?= Sanitizer::html(Url::ruta('/seguridad')) ?>">Seguridad</a></li><?php endif; ?>
                <?php if (Session::tienePermiso('auditoria.ver')): ?><li><a href="<?= Sanitizer::html(Url::ruta('/auditoria')) ?>">Auditoría firmada</a></li><?php endif; ?>
                <li><a href="<?= Sanitizer::html(Url::ruta('/catalogo')) ?>">Catálogo</a></li>
            <?php endif; ?>

            <li><a href="<?= Sanitizer::html(Url::ruta('/mi-cuenta/password')) ?>">Cambiar contraseña</a></li>
            <li class="menu-right">
                <form class="menu-form" method="POST" action="<?= Sanitizer::html(Url::ruta('/logout')) ?>">
                    <?= Csrf::campo() ?>
                    <button type="submit">Cerrar sesión</button>
                </form>
            </li>
        <?php else: ?>
            <li><a href="<?= Sanitizer::html(Url::ruta('/')) ?>">Inicio</a></li>
            <li><a href="<?= Sanitizer::html(Url::ruta('/catalogo')) ?>">Catálogo</a></li>
            <li><a href="<?= Sanitizer::html(Url::ruta('/carrito')) ?>">Carrito <span class="cart-count"><?= (int) $cantidadCarrito ?></span></a></li>
            <li><a href="<?= Sanitizer::html(Url::ruta('/login')) ?>">Iniciar sesión</a></li>
            <li><a href="<?= Sanitizer::html(Url::ruta('/registro')) ?>">Registrarse</a></li>
        <?php endif; ?>
    </ul>
</nav>
