<?php

declare(strict_types=1);

use App\Controllers\InventarioController;
use App\Core\Router;

/** Rutas asignadas a Franco: inventario principal. */
return static function (Router $router): void {
    $router->get('/inventario', [InventarioController::class, 'index']);
    $router->get('/inventario/reporte-excel', [InventarioController::class, 'exportarExcel']);
    $router->get('/inventario/crear', [InventarioController::class, 'crear']);
    $router->post('/inventario/guardar', [InventarioController::class, 'guardar']);
    $router->get('/inventario/ver/{id}', [InventarioController::class, 'ver']);
    $router->get('/inventario/editar/{id}', [InventarioController::class, 'editar']);
    $router->post('/inventario/actualizar/{id}', [InventarioController::class, 'actualizar']);
    $router->post('/inventario/estado/{id}', [InventarioController::class, 'cambiarEstado']);
};
