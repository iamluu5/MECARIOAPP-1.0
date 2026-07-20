<?php

declare(strict_types=1);

use App\Controllers\AuditoriaController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/auditoria', [AuditoriaController::class, 'index']);
    $router->post('/auditoria/claves/rotar/{id}', [AuditoriaController::class, 'rotarClave']);
};
