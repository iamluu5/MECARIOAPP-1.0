<?php

use App\Core\Session;
use App\Helpers\Sanitizer;
use App\Helpers\Url;

$config = require dirname(__DIR__, 3) . '/config/config.php';
$tituloPagina = isset($titulo) ? $titulo . ' | ' . $config['app']['name'] : $config['app']['name'];
$usuarioSesion = Session::usuario();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Sanitizer::html($tituloPagina) ?></title>
    <link rel="stylesheet" href="<?= Sanitizer::html(Url::asset('css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="header-content">
        <a class="brand" href="<?= Sanitizer::html(Url::ruta('/')) ?>" aria-label="Mecario - Inicio">
            <img class="brand-logo" src="<?= Sanitizer::html(Url::asset('img/mecario-logo-transparent.png')) ?>" alt="Mecario">
        </a>
        <?php if ($usuarioSesion !== null): ?>
            <div class="user-info" aria-label="Usuario en sesión">
                <svg class="user-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor"/>
                </svg>
                <span>Sesión: <strong><?= Sanitizer::html($usuarioSesion['usuario'] ?? 'Usuario') ?></strong></span>
            </div>
        <?php endif; ?>
    </div>
</header>
<?php require __DIR__ . '/menu.php'; ?>
<?php require __DIR__ . '/mensajes.php'; ?>
