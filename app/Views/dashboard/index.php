<?php

use App\Core\Session;
use App\Helpers\Sanitizer;
use App\Helpers\Url;

$nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''));
$esCliente = Session::esCliente();
$modulos = [
    ['permiso' => 'usuarios.ver', 'ruta' => '/usuarios', 'titulo' => 'Usuarios', 'texto' => 'Altas, edición, activación y desbloqueo de cuentas internas.'],
    ['permiso' => 'roles.ver', 'ruta' => '/roles', 'titulo' => 'Roles y permisos', 'texto' => 'Perfiles de acceso y permisos por módulo.'],
    ['permiso' => 'autos.ver', 'ruta' => '/autos', 'titulo' => 'Autos', 'texto' => 'Catálogo de vehículos de origen de las piezas.'],
    ['permiso' => 'partes.ver', 'ruta' => '/partes', 'titulo' => 'Partes', 'texto' => 'Tipos de piezas automotrices disponibles.'],
    ['permiso' => 'secciones.ver', 'ruta' => '/secciones', 'titulo' => 'Secciones', 'texto' => 'Ubicación física dentro del rastro.'],
    ['permiso' => 'inventario.ver', 'ruta' => '/inventario', 'titulo' => 'Inventario', 'texto' => 'Piezas, stock, precios, condición e imágenes.'],
    ['permiso' => 'ventas.ver', 'ruta' => '/ventas', 'titulo' => 'Ventas', 'texto' => 'Registro de ventas, descuento de stock, gráficas y reporte Excel.'],
    ['permiso' => 'comentarios.moderar', 'ruta' => '/comentarios', 'titulo' => 'Comentarios', 'texto' => 'Moderación de comentarios del catálogo público.'],
    ['permiso' => 'seguridad.ver', 'ruta' => '/seguridad', 'titulo' => 'Seguridad', 'texto' => 'Intentos de login, IP, fechas y anomalías registradas.'],
    ['permiso' => 'auditoria.ver', 'ruta' => '/auditoria', 'titulo' => 'Auditoría firmada', 'texto' => 'Verifica acciones críticas mediante huellas SHA-256 y firmas RSA por usuario.'],
];

$modulosVisibles = array_values(array_filter(
    $modulos,
    static fn(array $modulo): bool => Session::tienePermiso($modulo['permiso'])
));
?>
<main class="container">
    <?php if ($esCliente): ?>
        <section class="hero admin-hero">
            <span class="eyebrow">Mi cuenta</span>
            <h1>Hola, <?= Sanitizer::html($nombreCompleto !== '' ? $nombreCompleto : 'cliente') ?></h1>
            <p>Encuentra piezas para tu vehículo y administra tu compra desde un solo lugar.</p>
        </section>

        <section class="grid-cards module-grid customer-actions">
            <a class="card module-card customer-action" href="<?= Sanitizer::html(Url::ruta('/catalogo')) ?>">
                <span class="module-icon">🔎</span><h2>Catálogo</h2>
            </a>
            <a class="card module-card customer-action" href="<?= Sanitizer::html(Url::ruta('/carrito')) ?>">
                <span class="module-icon">🛒</span><h2>Mi carrito</h2>
            </a>
            <a class="card module-card customer-action" href="<?= Sanitizer::html(Url::ruta('/mis-compras')) ?>">
                <span class="module-icon">🧾</span><h2>Mis compras</h2>
            </a>
        </section>
    <?php else: ?>
        <section class="hero admin-hero">
            <span class="eyebrow">Panel administrativo</span>
            <h1>Bienvenido, <?= Sanitizer::html($nombreCompleto !== '' ? $nombreCompleto : 'usuario') ?></h1>
            <p>Gestiona el rastro automotriz desde un entorno ordenado, seguro y dividido por módulos.</p>
            <div class="hero-acciones"><a class="btn" href="<?= Sanitizer::html(Url::ruta('/catalogo')) ?>">Ver catálogo público</a></div>
        </section>

        <section class="grid-cards module-grid">
            <?php foreach ($modulosVisibles as $modulo): ?>
                <a class="card module-card" href="<?= Sanitizer::html(Url::ruta($modulo['ruta'])) ?>">
                    <span class="module-icon">●</span>
                    <h2><?= Sanitizer::html($modulo['titulo']) ?></h2>
                    <p class="muted"><?= Sanitizer::html($modulo['texto']) ?></p>
                </a>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
