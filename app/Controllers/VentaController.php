<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Helpers\Validator;
use App\Models\Venta;
use App\Services\AuditTrailService;
use RuntimeException;

/**
 * Controlador del módulo de ventas.
 *
 * Responsabilidades:
 * - Mostrar el listado de ventas.
 * - Mostrar el formulario de registro.
 * - Validar la información recibida.
 * - Procesar una venta.
 * - Mostrar el detalle de una venta.
 * - Exportar el detalle de una venta a Excel.
 *
 * El controlador NO contiene consultas SQL.
 * Toda la comunicación con la base de datos se realiza
 * mediante el modelo Venta.
 */
final class VentaController
{
    /**
     * Modelo del módulo de ventas.
     */
    private Venta $ventas;
    private AuditTrailService $auditoria;

    public function __construct()
    {
        $this->ventas = new Venta();
        $this->auditoria = new AuditTrailService();
    }

    /**
     * Muestra el listado de ventas registradas.
     */
    public function index(): void
    {
        if (!Session::estaAutenticado()) {
            Url::redirigir('/login');
        }

        if (!Session::tienePermiso('ventas.ver')) {
            Session::mensaje(
                'error',
                'No tiene permisos para consultar las ventas.'
            );

            Url::redirigir('/dashboard');
        }

        $desde = Sanitizer::texto($_GET['desde'] ?? '');
        $hasta = Sanitizer::texto($_GET['hasta'] ?? '');

        if ($desde !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $desde = '';
        }

        if ($hasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $hasta = '';
        }

        $ventas = $this->ventas->listarVentas($desde, $hasta);

        View::renderizar('ventas/index', [
            'titulo' => 'Ventas',
            'ventas' => $ventas,
            'desde' => $desde,
            'hasta' => $hasta,
            'estadisticas' => $this->ventas->obtenerEstadisticasVentas(),
            'grafica' => $this->ventas->obtenerVentasUltimosDias(7),
            'metodosPago' => $this->ventas->obtenerVentasPorMetodoPago(),
            'ventasMensuales' => $this->ventas->obtenerVentasPorMes(6),
            'totalesCategoria' => $this->ventas->obtenerTotalesPorCategoria(),
            'partesMasVendidas' => $this->ventas->obtenerPartesMasVendidas(5),
        ]);
    }

    /**
     * Muestra el formulario para registrar una nueva venta.
     */
    public function crear(): void
    {
        if (!Session::estaAutenticado()) {
            Url::redirigir('/login');
        }

        if (!Session::tienePermiso('ventas.crear')) {
            Session::mensaje(
                'error',
                'No tiene permisos para registrar ventas.'
            );

            Url::redirigir('/ventas');
        }

        $inventario = $this->ventas->obtenerInventarioDisponible();

        View::renderizar('ventas/crear', [
            'titulo' => 'Nueva venta',
            'inventario' => $inventario,
        ]);
    }

    /**
     * Procesa el registro de una nueva venta.
     */
    public function guardar(): void
    {
        if (!Session::estaAutenticado()) {
            Url::redirigir('/login');
        }

        if (!Session::tienePermiso('ventas.crear')) {
            Session::mensaje(
                'error',
                'No tiene permisos para registrar ventas.'
            );

            Url::redirigir('/ventas');
        }

        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje(
                'error',
                'La solicitud no superó la validación de seguridad.'
            );

            Url::redirigir('/ventas/crear');
        }

        $observacion = Sanitizer::texto(
            $_POST['observacion'] ?? ''
        );

        $detallesRecibidos = $_POST['detalles'] ?? [];

        /**
         * Se descartan filas vacías o incompletas que puedan llegar desde
         * el formulario dinámico (por ejemplo una fila agregada y luego
         * dejada sin seleccionar).
         */
        $detalles = [];

        if (is_array($detallesRecibidos)) {
            foreach ($detallesRecibidos as $detalle) {
                $idInventario = Sanitizer::entero(
                    $detalle['id_inventario'] ?? 0
                );

                $cantidad = Sanitizer::entero(
                    $detalle['cantidad'] ?? 0
                );

                if ($idInventario > 0 && $cantidad > 0) {
                    $detalles[] = [
                        'id_inventario' => $idInventario,
                        'cantidad' => $cantidad,
                    ];
                }
            }
        }

        $validador = new Validator();

        $validador->requerido(
            'detalle de la venta',
            $detalles === [] ? '' : 'ok'
        );

        if (!$validador->esValido()) {
            Session::mensaje(
                'error',
                $validador->primerError()
            );

            Url::redirigir('/ventas/crear');
        }

        $usuario = Session::usuario();

        if ($usuario === null) {
            Url::redirigir('/login');
        }

        try {

            $idVenta = $this->ventas->procesarVenta(
                (int) $usuario['id_usuario'],
                $detalles,
                $observacion !== ''
                    ? $observacion
                    : null,
                [
                    'origen' => 'interno',
                    'metodo_pago' => 'Efectivo',
                    'estado_pago' => 'no_aplica',
                    'metodo_entrega' => 'retiro',
                    'costo_entrega' => 0,
                ]
            );

            $this->auditoria->registrarSeguro((int)$usuario['id_usuario'], 'Ventas', 'venta_interna', 'ventas', $idVenta, ['detalles'=>count($detalles)]);

            Session::mensaje(
                'success',
                'La venta se registró correctamente.'
            );

            Url::redirigir('/ventas/ver/' . $idVenta);

        } catch (RuntimeException $exception) {

            Session::mensaje(
                'error',
                $exception->getMessage()
            );

            Url::redirigir('/ventas/crear');
        }
    }

    /**
     * Muestra el detalle completo de una venta.
     */
    public function ver(string $id): void
    {
        if (!Session::estaAutenticado()) {
            Url::redirigir('/login');
        }

        if (!Session::tienePermiso('ventas.ver')) {
            Session::mensaje(
                'error',
                'No tiene permisos para consultar las ventas.'
            );

            Url::redirigir('/dashboard');
        }

        $idVenta = Sanitizer::entero($id);

        $venta = $this->ventas->obtenerVenta($idVenta);

        if ($venta === null) {
            http_response_code(404);

            View::renderizar('errors/404', [
                'titulo' => 'Venta no encontrada',
            ]);

            return;
        }

        $detalle = $this->ventas->obtenerDetalleVenta($idVenta);

        View::renderizar('ventas/ver', [
            'titulo' => 'Detalle de venta #' . $idVenta,
            'venta' => $venta,
            'detalle' => $detalle,
        ]);
    }

    /**
     * Exporta un reporte consolidado de todas las ventas filtradas por fecha.
     */
    public function exportarReporteExcel(): void
    {
        if (!Session::estaAutenticado()) {
            Url::redirigir('/login');
        }

        if (!Session::tienePermiso('ventas.exportar')) {
            Session::mensaje('error', 'No tiene permisos para exportar reportes de ventas.');
            Url::redirigir('/ventas');
        }

        $desde = Sanitizer::texto($_GET['desde'] ?? '');
        $hasta = Sanitizer::texto($_GET['hasta'] ?? '');

        if ($desde !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $desde = '';
        }

        if ($hasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $hasta = '';
        }

        $filas = $this->ventas->obtenerReporteVentas($desde, $hasta);
        $nombreArchivo = 'reporte_ventas_' . date('Ymd_His') . '.xls';

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo "\xEF\xBB\xBF";

        echo '<table border="1">';
        echo '<tr><th colspan="22">REPORTE DE VENTAS - MECARIO</th></tr>';
        echo '<tr><td>Desde</td><td>' . Sanitizer::html($desde !== '' ? $desde : 'Inicio') . '</td>'
            . '<td>Hasta</td><td>' . Sanitizer::html($hasta !== '' ? $hasta : 'Actualidad') . '</td></tr>';
        echo '</table><br>';

        echo '<table border="1">';
        echo '<tr>'
            . '<th>Venta</th><th>Factura</th><th>Fecha</th><th>Usuario</th><th>Origen</th><th>Método pago</th><th>Estado pago</th><th>Método entrega</th><th>Costo entrega</th><th>Dirección</th><th>Teléfono</th><th>Estado venta</th>'
            . '<th>Código</th><th>Parte</th><th>Categoría</th><th>Auto</th><th>Cantidad</th>'
            . '<th>Precio unitario</th><th>Subtotal detalle</th><th>Subtotal venta</th><th>ITBMS</th><th>Total venta</th>'
            . '</tr>';

        foreach ($filas as $fila) {
            echo '<tr>';
            echo '<td>#' . Sanitizer::html((string) $fila['id_venta']) . '</td>';
            echo '<td>' . Sanitizer::html((string) ($fila['numero_factura'] ?? '-')) . '</td>';
            echo '<td>' . Sanitizer::html((string) $fila['fecha_venta']) . '</td>';
            echo '<td>' . Sanitizer::html((string) $fila['usuario_responsable']) . '</td>';
            echo '<td>' . Sanitizer::html((string) $fila['origen']) . '</td>';
            echo '<td>' . Sanitizer::html((string) $fila['metodo_pago']) . '</td>';
            echo '<td>' . Sanitizer::html((string) $fila['estado_pago']) . '</td>';
            echo '<td>' . Sanitizer::html((string) $fila['metodo_entrega']) . '</td>';
            echo '<td>' . Sanitizer::html(number_format((float) $fila['costo_entrega'], 2)) . '</td>';
            echo '<td>' . Sanitizer::html((string) ($fila['direccion_entrega'] ?? '-')) . '</td>';
            echo '<td>' . Sanitizer::html((string) ($fila['telefono_entrega'] ?? '-')) . '</td>';
            echo '<td>' . Sanitizer::html((string) $fila['estado']) . '</td>';
            echo '<td>' . Sanitizer::html((string) $fila['codigo_inventario']) . '</td>';
            echo '<td>' . Sanitizer::html((string) $fila['nombre_parte']) . '</td>';
            echo '<td>' . Sanitizer::html((string) $fila['categoria']) . '</td>';
            echo '<td>' . Sanitizer::html((string) $fila['auto']) . '</td>';
            echo '<td>' . Sanitizer::html((string) $fila['cantidad']) . '</td>';
            echo '<td>' . Sanitizer::html(number_format((float) $fila['precio_unitario'], 2)) . '</td>';
            echo '<td>' . Sanitizer::html(number_format((float) $fila['subtotal'], 2)) . '</td>';
            echo '<td>' . Sanitizer::html(number_format((float) $fila['subtotal_venta'], 2)) . '</td>';
            echo '<td>' . Sanitizer::html(number_format((float) $fila['itbms'], 2)) . '</td>';
            echo '<td>' . Sanitizer::html(number_format((float) $fila['total_venta'], 2)) . '</td>';
            echo '</tr>';
        }

        if ($filas === []) {
            echo '<tr><td colspan="22">No hay ventas para el rango seleccionado.</td></tr>';
        }

        echo '</table>';
        exit;
    }

    /**
     * Exporta el detalle de una venta como un archivo de Excel.
     *
     * Se genera un archivo con extensión .xls a partir de una tabla HTML,
     * ya que Excel es capaz de abrir e interpretar este formato sin
     * necesidad de librerías externas.
     */
    public function exportarExcel(string $id): void
    {
        if (!Session::estaAutenticado()) {
            Url::redirigir('/login');
        }

        if (!Session::tienePermiso('ventas.ver')) {
            Session::mensaje(
                'error',
                'No tiene permisos para exportar esta información.'
            );

            Url::redirigir('/dashboard');
        }

        $idVenta = Sanitizer::entero($id);

        $venta = $this->ventas->obtenerVenta($idVenta);

        if ($venta === null) {
            http_response_code(404);

            View::renderizar('errors/404', [
                'titulo' => 'Venta no encontrada',
            ]);

            return;
        }

        $detalle = $this->ventas->obtenerDetalleVenta($idVenta);

        $this->descargarExcelVenta($venta, $detalle);
    }

    /**
     * Genera y envía al navegador el archivo de Excel con el
     * detalle de la venta.
     */
    private function descargarExcelVenta(array $venta, array $detalle): void
    {
        $nombreArchivo = 'venta_' . $venta['id_venta'] . '.xls';

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header(
            'Content-Disposition: attachment; filename="' . $nombreArchivo . '"'
        );
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM: evita que Excel muestre mal los acentos y la letra "ñ".
        echo "\xEF\xBB\xBF";

        echo '<table border="1">';

        echo '<tr><th colspan="2">Detalle de venta #'
            . Sanitizer::html((string) $venta['id_venta'])
            . '</th></tr>';

        echo '<tr><td>Fecha</td><td>'
            . Sanitizer::html((string) $venta['fecha_venta'])
            . '</td></tr>';

        echo '<tr><td>Usuario</td><td>'
            . Sanitizer::html((string) $venta['usuario'])
            . '</td></tr>';

        echo '<tr><td>Origen</td><td>'
            . Sanitizer::html((string) ($venta['origen'] ?? 'interno'))
            . '</td></tr>';

        echo '<tr><td>Método de pago</td><td>'
            . Sanitizer::html((string) ($venta['metodo_pago'] ?? 'Efectivo'))
            . '</td></tr>';

        echo '<tr><td>Estado de pago</td><td>'
            . Sanitizer::html((string) ($venta['estado_pago'] ?? 'no_aplica'))
            . '</td></tr>';

        echo '<tr><td>Método de entrega</td><td>'
            . Sanitizer::html(($venta['metodo_entrega'] ?? 'retiro') === 'delivery' ? 'Delivery' : 'Retiro en local')
            . '</td></tr>';

        echo '<tr><td>Costo de entrega</td><td>'
            . Sanitizer::html(number_format((float) ($venta['costo_entrega'] ?? 0), 2))
            . '</td></tr>';

        if (($venta['metodo_entrega'] ?? 'retiro') === 'delivery') {
            echo '<tr><td>Dirección de entrega</td><td>'
                . Sanitizer::html((string) ($venta['direccion_entrega'] ?? '-'))
                . '</td></tr>';
            echo '<tr><td>Teléfono de contacto</td><td>'
                . Sanitizer::html((string) ($venta['telefono_entrega'] ?? '-'))
                . '</td></tr>';
        }

        echo '<tr><td>Subtotal</td><td>$' . Sanitizer::html(number_format((float)($venta['subtotal'] ?? 0), 2)) . '</td></tr>';
        echo '<tr><td>ITBMS (7%)</td><td>$' . Sanitizer::html(number_format((float)($venta['itbms'] ?? 0), 2)) . '</td></tr>';
        echo '<tr><td>Total</td><td>$' . Sanitizer::html(number_format((float)($venta['total'] ?? 0), 2)) . '</td></tr>';

        echo '<tr><td>Estado</td><td>'
            . Sanitizer::html((string) $venta['estado'])
            . '</td></tr>';

        echo '<tr><td>Observación</td><td>'
            . Sanitizer::html((string) ($venta['observacion'] ?? 'Sin observaciones'))
            . '</td></tr>';

        echo '</table><br>';

        echo '<table border="1">';

        echo '<tr>'
            . '<th>Parte</th>'
            . '<th>Auto</th>'
            . '<th>Cantidad</th>'
            . '<th>Precio unitario</th>'
            . '<th>Subtotal</th>'
            . '</tr>';

        foreach ($detalle as $item) {
            $auto = $item['marca'] . ' ' . $item['modelo']
                . ' (' . $item['anio'] . ')';

            echo '<tr>';
            echo '<td>' . Sanitizer::html((string) $item['nombre_parte']) . '</td>';
            echo '<td>' . Sanitizer::html($auto) . '</td>';
            echo '<td>' . Sanitizer::html((string) $item['cantidad']) . '</td>';
            echo '<td>'
                . Sanitizer::html(number_format((float) $item['precio_unitario'], 2))
                . '</td>';
            echo '<td>'
                . Sanitizer::html(number_format((float) $item['subtotal'], 2))
                . '</td>';
            echo '</tr>';
        }

        echo '<tr>';
        echo '<td colspan="4"><strong>Total</strong></td>';
        echo '<td><strong>'
            . Sanitizer::html(number_format((float) $venta['total'], 2))
            . '</strong></td>';
        echo '</tr>';

        echo '</table>';

        exit;
    }
}
