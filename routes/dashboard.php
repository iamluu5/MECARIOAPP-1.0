<?php

declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Core\Router;

/**
 * Ruta del HOME administrativo.
 */
return static function (Router $router): void {
    $router->get(
        '/dashboard',
        [DashboardController::class, 'index']
    );
};
