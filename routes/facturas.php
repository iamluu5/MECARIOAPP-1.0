<?php

declare(strict_types=1);

use App\Controllers\FacturaController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/factura/{id}', [FacturaController::class, 'descargar']);
};
