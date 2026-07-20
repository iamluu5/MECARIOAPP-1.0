<?php

declare(strict_types=1);

use App\Controllers\CuentaController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/mi-cuenta/password', [CuentaController::class, 'password']);
    $router->post('/mi-cuenta/password', [CuentaController::class, 'actualizarPassword']);
};
