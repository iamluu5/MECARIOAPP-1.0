<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Url;
use App\Models\Seguridad;

/**
 * Módulo administrativo de auditoría de seguridad.
 */
final class SeguridadController
{
    private Seguridad $seguridad;

    public function __construct()
    {
        $this->seguridad = new Seguridad();
    }

    public function index(): void
    {
        if (!Session::estaAutenticado()) {
            Url::redirigir('/login');
        }

        if (!Session::tienePermiso('seguridad.ver')) {
            http_response_code(403);
            View::renderizar('errors/403', [
                'titulo' => 'Acceso no autorizado',
            ]);
            return;
        }

        View::renderizar('seguridad/index', [
            'titulo' => 'Seguridad y auditoría',
            'resumen' => $this->seguridad->resumen(),
            'intentos' => $this->seguridad->listarIntentos(),
            'anomalias' => $this->seguridad->listarAnomalias(),
        ]);
    }
}
