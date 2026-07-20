<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Models\Carrito;
use RuntimeException;

/**
 * Controlador del carrito de compras.
 */
final class CarritoController
{
    private Carrito $carrito;

    public function __construct()
    {
        $this->carrito = new Carrito();
    }

    public function index(): void
    {
        View::renderizar('carrito/index', [
            'titulo' => 'Mi carrito',
            'items' => $this->carrito->obtenerItems(),
            'total' => $this->carrito->calcularTotal(),
        ]);
    }

    public function agregar(string $id): void
    {
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'La solicitud no superó la validación de seguridad.');
            Url::redirigir('/catalogo');
        }

        $idInventario = Sanitizer::entero($id);
        $cantidad = max(1, Sanitizer::entero($_POST['cantidad'] ?? 1));

        try {
            $this->carrito->agregar($idInventario, $cantidad);
            Session::mensaje('success', 'La pieza se agregó al carrito.');
        } catch (RuntimeException $exception) {
            Session::mensaje('error', $exception->getMessage());
        }

        $volver = Sanitizer::texto($_POST['volver'] ?? '/carrito');
        $permitidas = ['/catalogo', '/carrito', '/parte/' . $idInventario];
        Url::redirigir(in_array($volver, $permitidas, true) ? $volver : '/carrito');
    }

    public function actualizar(): void
    {
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'La solicitud no superó la validación de seguridad.');
            Url::redirigir('/carrito');
        }

        $cantidades = $_POST['cantidades'] ?? [];
        $this->carrito->actualizar(is_array($cantidades) ? $cantidades : []);
        Session::mensaje('success', 'El carrito fue actualizado.');
        Url::redirigir('/carrito');
    }

    public function eliminar(string $id): void
    {
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'La solicitud no superó la validación de seguridad.');
            Url::redirigir('/carrito');
        }

        $this->carrito->eliminar(Sanitizer::entero($id));
        Session::mensaje('success', 'La pieza se eliminó del carrito.');
        Url::redirigir('/carrito');
    }

    public function vaciar(): void
    {
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'La solicitud no superó la validación de seguridad.');
            Url::redirigir('/carrito');
        }

        $this->carrito->vaciar();
        Session::mensaje('success', 'El carrito quedó vacío.');
        Url::redirigir('/carrito');
    }
}
