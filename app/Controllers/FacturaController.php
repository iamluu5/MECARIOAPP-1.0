<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Models\Venta;
use App\Services\InvoicePdfService;
use RuntimeException;
use Throwable;

final class FacturaController
{
    private Venta $ventas;
    private InvoicePdfService $pdf;

    public function __construct()
    {
        $this->ventas = new Venta();
        $this->pdf = new InvoicePdfService();
    }

    public function descargar(string $id): void
    {
        if (!Session::estaAutenticado()) {
            Url::redirigir('/login');
        }

        $idVenta = Sanitizer::entero($id);
        $usuario = Session::usuario();

        $venta = Session::esCliente()
            ? $this->ventas->obtenerVentaUsuario(
                $idVenta,
                (int) ($usuario['id_usuario'] ?? 0)
            )
            : (
                Session::tienePermiso('ventas.ver')
                    ? $this->ventas->obtenerVenta($idVenta)
                    : null
            );

        if ($venta === null) {
            Session::mensaje(
                'error',
                'No tiene acceso a la factura solicitada.'
            );

            Url::redirigir(
                Session::esCliente()
                    ? '/mis-compras'
                    : '/ventas'
            );

            return;
        }

        try {

            // Obtener los productos incluidos en la venta.
            $detalle = $this->ventas->obtenerDetalleVenta($idVenta);

            // Generar la factura PDF.
            $ruta = $this->pdf->generar(
                $venta,
                $detalle
            );

            // Verificar que realmente exista.
            if (!is_file($ruta)) {
                throw new RuntimeException(
                    'El archivo PDF no fue encontrado después de generarse.'
                );
            }

            $nombre = basename($ruta);

            // Limpiar cualquier buffer previo para evitar
            // que HTML o espacios dañen el PDF.
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            header('Content-Type: application/pdf');

            header(
                'Content-Disposition: attachment; filename="' .
                $nombre .
                '"'
            );

            header(
                'Content-Length: ' .
                filesize($ruta)
            );

            header(
                'X-Content-Type-Options: nosniff'
            );

            readfile($ruta);

            exit;

        } catch (RuntimeException $e) {

            /*
             * Los errores controlados por nosotros
             * se muestran directamente.
             */
            error_log(
                '[MECARIO FACTURA - RUNTIME] ' .
                $e->getMessage()
            );

            Session::mensaje(
                'error',
                $e->getMessage()
            );

        } catch (Throwable $e) {

            /*
             * IMPORTANTE:
             *
             * Antes esta parte ocultaba completamente
             * el error real y únicamente mostraba:
             *
             * "No fue posible generar la factura PDF..."
             *
             * Ahora mostramos temporalmente el mensaje
             * técnico para poder identificar el problema.
             */

            $mensaje =
                'Error al generar la factura: ' .
                $e->getMessage();

            error_log(
                '[MECARIO FACTURA] ' .
                $e->getMessage() .
                ' | Archivo: ' .
                $e->getFile() .
                ' | Línea: ' .
                $e->getLine()
            );

            Session::mensaje(
                'error',
                $mensaje
            );
        }

        Url::redirigir(
            Session::esCliente()
                ? '/mis-compras'
                : '/ventas/ver/' . $idVenta
        );
    }
}