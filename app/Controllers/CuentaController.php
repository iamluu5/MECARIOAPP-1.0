<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Url;
use App\Models\Usuario;
use App\Services\AuditTrailService;
use App\Services\PasswordHashService;

/** Módulo de autoservicio para cambio de contraseña. */
final class CuentaController
{
    private Usuario $usuarios;
    private PasswordHashService $passwords;
    private AuditTrailService $auditoria;

    public function __construct()
    {
        $this->usuarios = new Usuario();
        $this->passwords = new PasswordHashService();
        $this->auditoria = new AuditTrailService();
    }

    public function password(): void
    {
        $this->exigirAutenticacion();
        View::renderizar('cuenta/password', ['titulo' => 'Cambiar contraseña']);
    }

    public function actualizarPassword(): void
    {
        $this->exigirAutenticacion();

        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'La solicitud no superó la validación de seguridad.');
            Url::redirigir('/mi-cuenta/password');
        }

        $usuarioSesion = Session::usuario();
        $idUsuario = (int) ($usuarioSesion['id_usuario'] ?? 0);
        $actual = (string) ($_POST['password_actual'] ?? '');
        $nueva = (string) ($_POST['password_nueva'] ?? '');
        $confirmacion = (string) ($_POST['password_confirmacion'] ?? '');

        $hashActual = $this->usuarios->obtenerPasswordHash($idUsuario);
        if ($hashActual === null || !$this->passwords->verificarEvidencia($actual, $hashActual)) {
            Session::mensaje('error', 'La contraseña actual no es correcta.');
            Url::redirigir('/mi-cuenta/password');
        }

        if (mb_strlen($nueva) < 8) {
            Session::mensaje('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
            Url::redirigir('/mi-cuenta/password');
        }

        if ($nueva !== $confirmacion) {
            Session::mensaje('error', 'La confirmación de la nueva contraseña no coincide.');
            Url::redirigir('/mi-cuenta/password');
        }

        if ($this->passwords->verificarEvidencia($nueva, $hashActual)) {
            Session::mensaje('error', 'La nueva contraseña debe ser diferente de la actual.');
            Url::redirigir('/mi-cuenta/password');
        }

        $this->usuarios->actualizarPassword(
            $idUsuario,
            $this->passwords->generarEvidencia($nueva)
        );

        $this->auditoria->registrarSeguro(
            $idUsuario,
            'Cuenta',
            'cambio_password',
            'usuarios',
            $idUsuario,
            ['resultado' => 'contraseña actualizada']
        );

        Session::mensaje('success', 'Contraseña actualizada correctamente.');
        Url::redirigir('/dashboard');
    }

    private function exigirAutenticacion(): void
    {
        if (!Session::estaAutenticado()) {
            Url::redirigir('/login');
        }
    }
}
