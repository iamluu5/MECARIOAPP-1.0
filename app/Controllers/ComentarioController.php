<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Helpers\Validator;
use App\Models\Comentario;

/**
 * Módulo de comentarios y moderación.
 *
 * Los comentarios nuevos solo pueden ser creados por clientes autenticados.
 * Todo comentario nace pendiente (publicado = 0) y únicamente aparece en el
 * catálogo después de que un usuario con permiso de moderación lo aprueba.
 */
final class ComentarioController
{
    private Comentario $comentarios;

    public function __construct()
    {
        $this->comentarios = new Comentario();
    }

    public function index(): void
    {
        if (!$this->exigirPermisoModeracion()) {
            return;
        }

        View::renderizar('comentarios/moderar', [
            'titulo' => 'Comentarios públicos y moderación',
            'comentarios' => $this->comentarios->listarParaModeracion(),
            'pendientes' => $this->comentarios->contarPendientes(),
        ]);
    }

    /**
     * Recibe un comentario de un cliente autenticado.
     */
    public function guardarPublico(): void
    {
        $idInventario = Sanitizer::entero($_POST['id_inventario'] ?? 0);

        if (!Session::estaAutenticado()) {
            Session::mensaje('warning', 'Inicia sesión como cliente para escribir un comentario.');
            Url::redirigir('/login');
        }

        if (!Session::esCliente() || !Session::tienePermiso('comentarios.crear')) {
            Session::mensaje('error', 'Los comentarios están disponibles para clientes registrados.');
            Url::redirigir('/parte/' . $idInventario);
        }

        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'La solicitud no pudo validarse. Intenta nuevamente.');
            Url::redirigir('/parte/' . $idInventario);
        }

        $texto = Sanitizer::texto($_POST['comentario'] ?? '');
        $usuario = Session::usuario();
        $nombre = trim((string) ($usuario['nombre'] ?? '') . ' ' . (string) ($usuario['apellido'] ?? ''));
        $correo = Sanitizer::correo((string) ($usuario['correo'] ?? ''));

        $validador = new Validator();
        $validador
            ->requerido('id_inventario', $idInventario)
            ->enteroPositivo('id_inventario', $idInventario, permitirCero: false)
            ->requerido('comentario', $texto)
            ->longitudMaxima('comentario', $texto, 500);

        if (!$validador->esValido()) {
            Session::mensaje('error', $validador->primerError());
            Url::redirigir('/parte/' . $idInventario);
        }

        $this->comentarios->agregar([
            'id_inventario' => $idInventario,
            'id_usuario' => (int) ($usuario['id_usuario'] ?? 0),
            'nombre_visitante' => $nombre !== '' ? $nombre : (string) ($usuario['usuario'] ?? 'Cliente'),
            'correo_visitante' => $correo,
            'comentario' => $texto,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        Session::mensaje('success', 'Gracias por tu comentario. Se publicará después de ser revisado por un moderador.');
        Url::redirigir('/parte/' . $idInventario);
    }

    public function aprobar(string $id = '0'): void
    {
        if (!$this->exigirPermisoModeracion()) {
            return;
        }

        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'La solicitud no pudo validarse.');
            Url::redirigir('/comentarios');
        }

        $idComentador = Session::usuario()['id_usuario'] ?? 0;
        $this->comentarios->aprobar(Sanitizer::entero($id), (int) $idComentador);
        Session::mensaje('success', 'Comentario publicado.');
        Url::redirigir('/comentarios');
    }

    public function ocultar(string $id = '0'): void
    {
        if (!$this->exigirPermisoModeracion()) {
            return;
        }

        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'La solicitud no pudo validarse.');
            Url::redirigir('/comentarios');
        }

        $idModerador = Session::usuario()['id_usuario'] ?? 0;
        $this->comentarios->ocultar(Sanitizer::entero($id), (int) $idModerador);
        Session::mensaje('success', 'Comentario ocultado.');
        Url::redirigir('/comentarios');
    }

    public function eliminar(string $id = '0'): void
    {
        if (!$this->exigirPermisoModeracion()) {
            return;
        }

        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'La solicitud no pudo validarse.');
            Url::redirigir('/comentarios');
        }

        $idModerador = Session::usuario()['id_usuario'] ?? 0;
        $this->comentarios->eliminar(Sanitizer::entero($id), (int) $idModerador);
        Session::mensaje('success', 'Comentario eliminado lógicamente.');
        Url::redirigir('/comentarios');
    }

    private function exigirPermisoModeracion(): bool
    {
        if (!Session::estaAutenticado() || !Session::tienePermiso('comentarios.moderar')) {
            http_response_code(403);
            View::renderizar('errors/403', ['titulo' => 'Acceso no autorizado']);
            return false;
        }

        return true;
    }
}
