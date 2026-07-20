<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Factura;
use RuntimeException;

/** Genera facturas PDF descargables con TCPDF. */
final class InvoicePdfService
{
    private array $config;
    private Factura $facturas;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/config.php';
        $this->facturas = new Factura();
    }

    public function generar(array $venta, array $detalle): string
    {
        if (!class_exists('TCPDF')) {
            throw new RuntimeException('TCPDF no está instalado. Ejecuta "composer install" en la carpeta del proyecto.');
        }

        $factura = $this->facturas->obtenerPorVenta((int)$venta['id_venta']);
        if ($factura === null) {
            throw new RuntimeException('La venta no tiene una factura asociada.');
        }

        $dir = (string)$this->config['invoices']['directory'];
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('No fue posible crear el directorio de facturas.');
        }

        // El séptimo argumento activa el modo PDF/A en la API clásica de TCPDF.
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false, false);
        $pdf->SetCreator('Mecario');
        $pdf->SetAuthor((string)$this->config['business']['legal_name']);
        $pdf->SetTitle('Factura ' . $factura['numero_factura']);
        $pdf->SetSubject('Factura de venta de autopartes');
        $pdf->SetKeywords('Mecario, factura, autopartes, PDF');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 18);

        /* La firma con certificados se deshabilitó por incompatibilidad de TCPDF 7.
        $info = [
            'Name' => (string)$this->config['business']['legal_name'],
            'Location' => 'Panamá',
            'Reason' => 'Emisión de factura electrónica del sistema Mecario',
            'ContactInfo' => (string)$this->config['business']['email'],
        ];

        $certificadoUri = 'file://' . str_replace('\\', '/', $firma['certificate']);
        $llavePrivadaUri = 'file://' . str_replace('\\', '/', $firma['private_key']);

        // Algunas versiones de TCPDF 7/tc-lib-pdf intentan leer "extracerts"
        // incluso cuando se envía vacío. Proporcionar un PEM existente evita
        // ese defecto y permite construir la firma CMS correctamente.
        $pdf->setSignature(
            _signing_cert: $certificadoUri,
            _private_key: $llavePrivadaUri,
            _private_key_password: (string) $firma['password'],
            _extracerts: $certificadoUri,
            _cert_type: 2,
            _info: $info
        );
        */

        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        $cliente = trim((string)$venta['nombre'] . ' ' . (string)$venta['apellido']);
        $html = '<style>
            h1{font-size:20px;color:#1f2937;} .muted{color:#64748b;} table{border-collapse:collapse;width:100%;}
            th{background-color:#f4b52f;color:#111827;font-weight:bold;} th,td{border:1px solid #d1d5db;padding:7px;}
            .totals td{border:none;padding:5px;} .right{text-align:right;} .signature{margin-top:20px;border-top:1px solid #d1d5db;padding-top:10px;}
        </style>';
        $html .= '<h1>' . $this->e((string)$this->config['business']['legal_name']) . '</h1>';
        $html .= '<p class="muted">' . $this->e((string)$this->config['business']['address']) . '<br>'
            . $this->e((string)$this->config['business']['email']) . ' · ' . $this->e((string)$this->config['business']['phone']) . '</p>';
        $html .= '<table cellpadding="5"><tr><td><b>Factura:</b> ' . $this->e((string)$factura['numero_factura']) . '</td><td><b>Fecha:</b> ' . $this->e((string)$venta['fecha_venta']) . '</td></tr>'
            . '<tr><td><b>Cliente:</b> ' . $this->e($cliente !== '' ? $cliente : (string)$venta['usuario']) . '</td><td><b>Correo:</b> ' . $this->e((string)$venta['correo']) . '</td></tr>'
            . '<tr><td><b>Pago:</b> ' . $this->e((string)$venta['metodo_pago']) . '</td><td><b>Entrega:</b> ' . $this->e(($venta['metodo_entrega'] ?? 'retiro') === 'delivery' ? 'Delivery' : 'Retiro en local') . '</td></tr></table><br>';

        $html .= '<table cellpadding="6"><thead><tr><th>Código</th><th>Descripción</th><th>Auto</th><th>Cant.</th><th>P. unitario</th><th>Subtotal</th></tr></thead><tbody>';
        foreach ($detalle as $item) {
            $auto = $item['marca'] . ' ' . $item['modelo'] . ' (' . $item['anio'] . ')';
            $descripcion = $this->e((string)$item['nombre_parte']) . '<br><span class="muted">' . $this->e((string)($item['descripcion_corta'] ?? '')) . '</span>';
            $html .= '<tr><td>' . $this->e((string)$item['codigo_inventario']) . '</td><td>' . $descripcion . '</td><td>' . $this->e($auto) . '</td><td class="right">' . (int)$item['cantidad'] . '</td><td class="right">$' . number_format((float)$item['precio_unitario'], 2) . '</td><td class="right">$' . number_format((float)$item['subtotal'], 2) . '</td></tr>';
        }
        $html .= '</tbody></table><br>';
        $html .= '<table class="totals" cellpadding="5">'
            . '<tr><td width="60%"></td><td width="22%"><b>Subtotal:</b></td><td width="18%" class="right">$' . number_format((float)$venta['subtotal'], 2) . '</td></tr>'
            . '<tr><td width="60%"></td><td width="22%"><b>ITBMS (7%):</b></td><td width="18%" class="right">$' . number_format((float)$venta['itbms'], 2) . '</td></tr>'
            . '<tr><td width="60%"></td><td width="22%"><b>Entrega:</b></td><td width="18%" class="right">$' . number_format((float)$venta['costo_entrega'], 2) . '</td></tr>'
            . '<tr><td width="60%"></td><td width="22%"><b>TOTAL:</b></td><td width="18%" class="right"><b>$' . number_format((float)$venta['total'], 2) . '</b></td></tr></table>';

        if (($venta['metodo_entrega'] ?? 'retiro') === 'delivery') {
            $html .= '<p><b>Dirección de entrega:</b> ' . $this->e((string)$venta['direccion_entrega']) . '<br><b>Contacto:</b> ' . $this->e((string)$venta['telefono_entrega']) . '</p>';
        }
        $html .= '<p class="signature"><b>Factura generada por el sistema Mecario</b><br>La integridad del archivo se registra mediante una huella SHA-256.</p>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $ruta = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $factura['numero_factura'] . '.pdf';
        // Generar en memoria evita el subsistema de certificados y también las
        // diferencias del guardado "F" entre versiones de TCPDF.
        $contenidoPdf = $pdf->Output('', 'S');
        if (!is_string($contenidoPdf) || $contenidoPdf === '') {
            throw new RuntimeException('TCPDF no pudo generar el contenido de la factura.');
        }

        if (file_put_contents($ruta, $contenidoPdf, LOCK_EX) === false) {
            throw new RuntimeException('No fue posible guardar la factura generada.');
        }

        if (!is_file($ruta)) {
            throw new RuntimeException('TCPDF no pudo guardar la factura generada.');
        }

        $hash = hash_file('sha256', $ruta) ?: '';
        $this->facturas->marcarGenerada((int)$venta['id_venta'], $ruta, $hash);
        return $ruta;
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
