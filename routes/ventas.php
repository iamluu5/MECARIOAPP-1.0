<?php

declare(strict_types=1);

use App\Controllers\VentaController;
use App\Core\Router;

/**
 * Archivo de rutas asignado a Andrea.
 *
 * Separar las rutas por módulo evita que cuatro personas editen
 * public/index.php al mismo tiempo y reduce conflictos de Git.
 */
return static function (Router $router): void {
    $router->get('/ventas', [VentaController::class, 'index']);
    $router->get('/ventas/crear', [VentaController::class, 'crear']);
    $router->post('/ventas/guardar', [VentaController::class, 'guardar']);
    $router->get('/ventas/ver/{id}', [VentaController::class, 'ver']);
    $router->get('/ventas/reporte-excel', [VentaController::class, 'exportarReporteExcel']);
    $router->get('/ventas/exportar/{id}', [VentaController::class, 'exportarExcel']);
};
