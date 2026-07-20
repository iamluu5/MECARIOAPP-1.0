<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Interfaces\PagoSimuladoInterface;
use App\Models\Carrito;
use App\Models\Venta;
use App\Services\AuditTrailService;
use App\Services\CommerceCalculator;
use RuntimeException;
use Throwable;

/**
 * Gestiona el checkout del cliente, método de entrega y pago.
 *
 * La aplicación no se conecta a pasarelas bancarias ni almacena números de
 * tarjeta o códigos de seguridad. Solo conserva metadatos de la transacción.
 */
final class PagoController implements PagoSimuladoInterface
{
    private Carrito $carrito;
    private Venta $ventas;
    private CommerceCalculator $calculator;
    private AuditTrailService $auditoria;

    public function __construct()
    {
        $this->carrito = new Carrito();
        $this->ventas = new Venta();
        $this->calculator = new CommerceCalculator();
        $this->auditoria = new AuditTrailService();
    }

    public function checkout(): void
    {
        $this->exigirCliente();
        $items = $this->carrito->obtenerItems();

        if ($items === []) {
            Session::mensaje('warning', 'Tu carrito está vacío.');
            Url::redirigir('/carrito');
        }

        $config = require dirname(__DIR__, 2) . '/config/config.php';

        $totales = $this->calculator->calcular($this->carrito->calcularTotal(), 0.0);
        View::renderizar('pagos/checkout', [
            'titulo' => 'Finalizar compra',
            'items' => $items,
            'subtotal' => $totales['subtotal'],
            'itbms' => $totales['itbms'],
            'total' => $totales['total'],
            'costoDelivery' => (float) ($config['commerce']['delivery_fee'] ?? 5.00),
        ]);
    }

    public function seleccionarMetodo(): void
    {
        $this->exigirCliente();
        $this->validarCsrf('/checkout');

        if ($this->carrito->obtenerItems() === []) {
            Session::mensaje('warning', 'Tu carrito está vacío.');
            Url::redirigir('/carrito');
        }

        $metodo = strtolower(Sanitizer::texto($_POST['metodo'] ?? ''));
        $permitidos = ['yappy', 'visa', 'mastercard'];

        if (!in_array($metodo, $permitidos, true)) {
            Session::mensaje('error', 'Selecciona un método de pago válido.');
            Url::redirigir('/checkout');
        }

        $metodoEntrega = strtolower(Sanitizer::texto($_POST['metodo_entrega'] ?? ''));
        if (!in_array($metodoEntrega, ['retiro', 'delivery'], true)) {
            Session::mensaje('error', 'Selecciona cómo deseas recibir tu compra.');
            Url::redirigir('/checkout');
        }

        $direccion = Sanitizer::texto($_POST['direccion_entrega'] ?? '');
        $telefono = Sanitizer::texto($_POST['telefono_entrega'] ?? '');
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $costoEntrega = 0.0;

        if ($metodoEntrega === 'delivery') {
            if (mb_strlen($direccion) < 10 || mb_strlen($direccion) > 255) {
                Session::mensaje('error', 'Escribe una dirección de entrega válida.');
                Url::redirigir('/checkout');
            }

            if (!preg_match('/^[0-9+()\-\s]{7,20}$/', $telefono)) {
                Session::mensaje('error', 'Escribe un número de contacto válido para coordinar la entrega.');
                Url::redirigir('/checkout');
            }

            $costoEntrega = (float) ($config['commerce']['delivery_fee'] ?? 5.00);
        } else {
            $direccion = '';
            $telefono = '';
        }

        Session::guardar('entrega_actual', [
            'metodo_entrega' => $metodoEntrega,
            'direccion_entrega' => $direccion,
            'telefono_entrega' => $telefono,
            'costo_entrega' => $costoEntrega,
        ]);

        $prefijos = [
            'yappy' => 'YAP',
            'visa' => 'VIS',
            'mastercard' => 'MCC',
        ];

        Session::guardar('pago_actual', [
            'metodo' => $metodo,
            'referencia' => 'MEC-' . $prefijos[$metodo] . '-' . date('YmdHis') . '-' . random_int(100, 999),
            'creado' => time(),
        ]);

        if ($metodo === 'yappy') {
            Url::redirigir('/pago/yappy');
        }

        Url::redirigir('/pago/tarjeta/' . $metodo);
    }

    public function yappy(): void
    {
        $this->exigirCliente();
        $pago = $this->pagoSeleccionado('yappy');
        $entrega = $this->entregaSeleccionada();

        View::renderizar('pagos/yappy', [
            'titulo' => 'Pago con Yappy',
            'total' => $this->calcularTotalConEntrega($entrega),
            'referencia' => $pago['referencia'],
            'entrega' => $entrega,
        ]);
    }

    public function confirmarYappy(): void
    {
        $this->exigirCliente();
        $this->validarCsrf('/pago/yappy');
        $pago = $this->pagoSeleccionado('yappy');

        $this->finalizarCompra(
            'Yappy',
            (string) $pago['referencia']
        );
    }

    public function tarjeta(string $marca): void
    {
        $this->exigirCliente();
        $marca = strtolower(Sanitizer::texto($marca));

        if (!in_array($marca, ['visa', 'mastercard'], true)) {
            Url::redirigir('/checkout');
        }

        $this->pagoSeleccionado($marca);
        $entrega = $this->entregaSeleccionada();

        // Valores fijos mostrados por la aplicación y nunca persistidos.
        $datosTarjeta = $marca === 'visa'
            ? [
                'marca' => 'Visa',
                'numero' => '4111 1111 1111 1111',
                'expiracion' => '12/30',
                'cvv' => '123',
            ]
            : [
                'marca' => 'Mastercard',
                'numero' => '5555 5555 5555 4444',
                'expiracion' => '12/30',
                'cvv' => '123',
            ];

        View::renderizar('pagos/tarjeta', [
            'titulo' => 'Pago con ' . $datosTarjeta['marca'],
            'total' => $this->calcularTotalConEntrega($entrega),
            'datosTarjeta' => $datosTarjeta,
            'marcaClave' => $marca,
            'entrega' => $entrega,
        ]);
    }

    public function confirmarTarjeta(): void
    {
        $this->exigirCliente();
        $this->validarCsrf('/checkout');

        $marca = strtolower(Sanitizer::texto($_POST['marca'] ?? ''));
        if (!in_array($marca, ['visa', 'mastercard'], true)) {
            Session::mensaje('error', 'Método de tarjeta no válido.');
            Url::redirigir('/checkout');
        }

        $pago = $this->pagoSeleccionado($marca);
        $this->entregaSeleccionada();

        // Estos valores existen únicamente para completar el flujo de interfaz.
        // Ninguno de los campos se persiste en la base de datos.
        $numero = preg_replace('/\D+/', '', (string) ($_POST['numero'] ?? ''));
        $expiracion = Sanitizer::texto($_POST['expiracion'] ?? '');
        $cvv = preg_replace('/\D+/', '', (string) ($_POST['cvv'] ?? ''));

        $numeroEsperado = $marca === 'visa'
            ? '4111111111111111'
            : '5555555555554444';

        if ($numero !== $numeroEsperado || $expiracion !== '12/30' || $cvv !== '123') {
            Session::mensaje('error', 'No fue posible validar la información de pago.');
            Url::redirigir('/pago/tarjeta/' . $marca);
        }

        $this->finalizarCompra(
            $marca === 'visa' ? 'Visa' : 'Mastercard',
            (string) $pago['referencia']
        );
    }

    public function misCompras(): void
    {
        $this->exigirCliente();
        $usuario = Session::usuario();

        View::renderizar('pagos/mis-compras', [
            'titulo' => 'Mis compras',
            'compras' => $this->ventas->listarVentasUsuario((int) $usuario['id_usuario']),
        ]);
    }

    public function exito(string $id): void
    {
        $this->exigirCliente();
        $usuario = Session::usuario();
        $idVenta = Sanitizer::entero($id);
        $venta = $this->ventas->obtenerVentaUsuario($idVenta, (int) $usuario['id_usuario']);

        if ($venta === null) {
            http_response_code(404);
            View::renderizar('errors/404', ['titulo' => 'Compra no encontrada']);
            return;
        }

        View::renderizar('pagos/exito', [
            'titulo' => 'Compra registrada',
            'venta' => $venta,
            'detalle' => $this->ventas->obtenerDetalleVenta($idVenta),
        ]);
    }

    private function finalizarCompra(string $metodo, string $referencia): void
    {
        $usuario = Session::usuario();
        $detalles = $this->carrito->obtenerDetallesVenta();
        $entrega = $this->entregaSeleccionada();

        if ($detalles === []) {
            Session::mensaje('warning', 'Tu carrito está vacío o las piezas dejaron de estar disponibles.');
            Url::redirigir('/carrito');
        }

        try {
            $idVenta = $this->ventas->procesarVenta(
                (int) $usuario['id_usuario'],
                $detalles,
                'Compra realizada por un cliente desde el carrito web.',
                [
                    'origen' => 'cliente',
                    'metodo_pago' => $metodo,
                    'estado_pago' => 'confirmado',
                    'referencia_pago' => $referencia,
                    'metodo_entrega' => $entrega['metodo_entrega'],
                    'direccion_entrega' => $entrega['direccion_entrega'],
                    'telefono_entrega' => $entrega['telefono_entrega'],
                    'costo_entrega' => $entrega['costo_entrega'],
                ]
            );

            // El inventario se descuenta dentro de la misma transacción de la venta.
            $this->auditoria->registrarSeguro((int) $usuario['id_usuario'], 'Ventas', 'compra_cliente', 'ventas', $idVenta, ['metodo_pago'=>$metodo,'metodo_entrega'=>$entrega['metodo_entrega']]);
            $this->carrito->vaciar();
            Session::eliminar('pago_actual');
            Session::eliminar('entrega_actual');
            Session::mensaje('success', 'Pago confirmado y compra registrada correctamente.');
            Url::redirigir('/compra/exito/' . $idVenta);
        } catch (RuntimeException $exception) {
            Session::mensaje('error', $exception->getMessage());
            Url::redirigir('/carrito');
        } catch (Throwable) {
            Session::mensaje('error', 'No se pudo completar la compra. Intenta nuevamente.');
            Url::redirigir('/carrito');
        }
    }

    private function exigirCliente(): void
    {
        if (!Session::estaAutenticado()) {
            Session::mensaje('warning', 'Inicia sesión con tu cuenta de cliente para finalizar la compra.');
            Url::redirigir('/login');
        }

        if (!Session::esCliente() || !Session::tienePermiso('compras.crear')) {
            Session::mensaje('error', 'Esta función está disponible para cuentas de cliente.');
            Url::redirigir('/catalogo');
        }
    }

    private function pagoSeleccionado(string $esperado): array
    {
        $pago = Session::obtener('pago_actual', []);

        if (!is_array($pago) || ($pago['metodo'] ?? '') !== $esperado) {
            Session::mensaje('warning', 'Selecciona nuevamente el método de pago.');
            Url::redirigir('/checkout');
        }

        if ((int) ($pago['creado'] ?? 0) < time() - 1800) {
            Session::eliminar('pago_actual');
            Session::eliminar('entrega_actual');
            Session::mensaje('warning', 'La sesión de pago expiró. Selecciona el método nuevamente.');
            Url::redirigir('/checkout');
        }

        return $pago;
    }

    private function entregaSeleccionada(): array
    {
        $entrega = Session::obtener('entrega_actual', []);

        if (!is_array($entrega) || !in_array(($entrega['metodo_entrega'] ?? ''), ['retiro', 'delivery'], true)) {
            Session::mensaje('warning', 'Selecciona nuevamente el método de entrega.');
            Url::redirigir('/checkout');
        }

        return $entrega;
    }

    private function calcularTotalConEntrega(array $entrega): float
    {
        $totales = $this->calculator->calcular(
            $this->carrito->calcularTotal(),
            (float) ($entrega['costo_entrega'] ?? 0)
        );
        return (float) $totales['total'];
    }

    private function validarCsrf(string $rutaError): void
    {
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'La solicitud no superó la validación de seguridad.');
            Url::redirigir($rutaError);
        }
    }
}
