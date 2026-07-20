<?php

declare(strict_types=1);

use App\Controllers\SeguridadController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/seguridad', [SeguridadController::class, 'index']);
};
