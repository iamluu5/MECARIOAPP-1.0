<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Url;

/**
 * Controlador de la pantalla principal administrativa.
 */
final class DashboardController
{
    public function index(): void
    {
        /**
         * Este control evita que una persona no autenticada vea el panel.
         */
        if (!Session::estaAutenticado()) {
            Session::mensaje(
                'warning',
                'Debe iniciar sesión para acceder al panel.'
            );
            Url::redirigir('/login');
        }

        View::renderizar('dashboard/index', [
            'titulo' => 'Panel principal',
            'usuario' => Session::usuario(),
        ]);
    }
}
