<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Factura;
use RuntimeException;

/**
 * Genera facturas PDF/A con TCPDF
 * e incorpora firma digital mediante OpenSSL.
 */
final class InvoicePdfService
{
    private array $config;
    private Factura $facturas;
    private InvoiceCertificateManager $certificateManager;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/config.php';

        $this->facturas = new Factura();

        $this->certificateManager = new InvoiceCertificateManager();
    }

    /**
     * Genera la factura correspondiente a una venta.
     */
    public function generar(array $venta, array $detalle): string
    {
        // =====================================================
        // 1. VERIFICAR QUE TCPDF ESTÉ INSTALADO
        // =====================================================

        if (!class_exists('TCPDF')) {
            throw new RuntimeException(
                'TCPDF no está instalado. Ejecuta "composer install" en la carpeta del proyecto.'
            );
        }


        // =====================================================
        // 2. OBTENER FACTURA
        // =====================================================

        $factura = $this->facturas->obtenerPorVenta(
            (int) $venta['id_venta']
        );

        if ($factura === null) {
            throw new RuntimeException(
                'La venta no tiene una factura asociada.'
            );
        }


        // =====================================================
        // 3. PREPARAR DIRECTORIO DE FACTURAS
        // =====================================================

        $dir = (string) $this->config['invoices']['directory'];

        if (
            !is_dir($dir)
            && !mkdir($dir, 0775, true)
            && !is_dir($dir)
        ) {
            throw new RuntimeException(
                'No fue posible crear el directorio de facturas.'
            );
        }


        // =====================================================
        // 4. OBTENER CERTIFICADO Y LLAVE PRIVADA
        // =====================================================

        $firma = $this->certificateManager->asegurar();


        // =====================================================
        // 5. VALIDAR CERTIFICADO
        // =====================================================

        $certificadoPath = realpath(
            (string) $firma['certificate']
        );

        if (
            $certificadoPath === false
            || !is_file($certificadoPath)
            || !is_readable($certificadoPath)
        ) {
            throw new RuntimeException(
                'No se encontró el certificado utilizado para firmar la factura.'
            );
        }


        // =====================================================
        // 6. VALIDAR LLAVE PRIVADA
        // =====================================================

        $llavePrivadaPath = realpath(
            (string) $firma['private_key']
        );

        if (
            $llavePrivadaPath === false
            || !is_file($llavePrivadaPath)
            || !is_readable($llavePrivadaPath)
        ) {
            throw new RuntimeException(
                'No se encontró la llave privada utilizada para firmar la factura.'
            );
        }


        // =====================================================
        // 7. CONVERTIR RUTAS A FILE:///
        // =====================================================

        $certificadoUri = $this->crearFileUri(
            $certificadoPath
        );

        $llavePrivadaUri = $this->crearFileUri(
            $llavePrivadaPath
        );


        // =====================================================
        // 8. CREAR PDF EN MODO PDF/A
        // =====================================================
        //
        // El último parámetro TRUE mantiene activado PDF/A.
        // =====================================================

        $pdf = new \TCPDF(
            'P',
            'mm',
            'A4',
            true,
            'UTF-8',
            false,
            true
        );


        // =====================================================
        // 9. CONFIGURAR METADATOS
        // =====================================================

        $pdf->SetCreator('Mecario');

        $pdf->SetAuthor(
            (string) $this->config['business']['legal_name']
        );

        $pdf->SetTitle(
            'Factura ' . $factura['numero_factura']
        );

        $pdf->SetSubject(
            'Factura de venta de autopartes'
        );

        $pdf->SetKeywords(
            'Mecario, factura, autopartes, PDF/A, firma digital'
        );


        // =====================================================
        // 10. CONFIGURACIÓN DEL DOCUMENTO
        // =====================================================

        $pdf->setPrintHeader(false);

        $pdf->setPrintFooter(false);

        $pdf->SetMargins(
            15,
            15,
            15
        );

        $pdf->SetAutoPageBreak(
            true,
            18
        );


        // =====================================================
        // 11. CARGAR HELVETICA ANTES DE CONFIGURAR LA FIRMA
        // =====================================================
        //
        // IMPORTANTE:
        //
        // La fuente debe estar cargada antes de ejecutar
        // setSignature() para evitar:
        //
        // "The font helvetica has not been loaded"
        //
        // =====================================================

        $pdf->SetFont(
            'helvetica',
            '',
            10
        );


        // =====================================================
        // 12. CREAR LA PÁGINA
        // =====================================================

        $pdf->AddPage();


        // =====================================================
        // 13. INFORMACIÓN DE LA FIRMA DIGITAL
        // =====================================================

        $info = [
            'Name' =>
                (string) $this->config['business']['legal_name'],

            'Location' =>
                'Panamá',

            'Reason' =>
                'Emisión de factura electrónica del sistema Mecario',

            'ContactInfo' =>
                (string) $this->config['business']['email'],
        ];


        // =====================================================
        // 14. CONFIGURAR FIRMA DIGITAL
        // =====================================================
        //
        // Helvetica ya fue cargada antes de llegar aquí.
        //
        // En esta instalación mantenemos el certificado como
        // extracerts porque la fachada TCPDF 7 utilizada
        // anteriormente generaba:
        //
        // "Unable to read the extra certificates file"
        //
        // cuando no recibía un PEM válido.
        //
        // =====================================================

        $pdf->setSignature(
            _signing_cert: $certificadoUri,
            _private_key: $llavePrivadaUri,
            _private_key_password: (string) $firma['password'],
            _extracerts: '',
            _cert_type: 2,
            _info: $info
        );


        // =====================================================
        // 15. DATOS DEL CLIENTE
        // =====================================================

        $cliente = trim(
            (string) ($venta['nombre'] ?? '')
            . ' '
            . (string) ($venta['apellido'] ?? '')
        );


        // =====================================================
        // 16. ESTILOS
        // =====================================================

        $html = '
        <style>

            h1 {
                font-size: 20px;
                color: #1f2937;
            }

            .muted {
                color: #64748b;
            }

            table {
                border-collapse: collapse;
                width: 100%;
            }

            th {
                background-color: #f4b52f;
                color: #111827;
                font-weight: bold;
            }

            th,
            td {
                border: 1px solid #d1d5db;
                padding: 7px;
            }

            .totals td {
                border: none;
                padding: 5px;
            }

            .right {
                text-align: right;
            }

            .signature {
                margin-top: 20px;
                border-top: 1px solid #d1d5db;
                padding-top: 10px;
            }

            .digital-signature {
                margin-top: 15px;
                padding: 10px;
                background-color: #f8fafc;
                border: 1px solid #d1d5db;
                color: #475569;
                font-size: 9px;
            }

        </style>';


        // =====================================================
        // 17. INFORMACIÓN DE MECARIO
        // =====================================================

        $html .=
            '<h1>'
            . $this->e(
                (string) $this->config['business']['legal_name']
            )
            . '</h1>';


        $html .=
            '<p class="muted">'
            . $this->e(
                (string) $this->config['business']['address']
            )
            . '<br>'
            . $this->e(
                (string) $this->config['business']['email']
            )
            . ' · '
            . $this->e(
                (string) $this->config['business']['phone']
            )
            . '</p>';


        // =====================================================
        // 18. DATOS GENERALES DE LA FACTURA
        // =====================================================

        $html .=
            '<table cellpadding="5">

                <tr>

                    <td>
                        <b>Factura:</b> '
                        . $this->e(
                            (string) $factura['numero_factura']
                        )
                        . '
                    </td>

                    <td>
                        <b>Fecha:</b> '
                        . $this->e(
                            (string) $venta['fecha_venta']
                        )
                        . '
                    </td>

                </tr>

                <tr>

                    <td>
                        <b>Cliente:</b> '
                        . $this->e(
                            $cliente !== ''
                                ? $cliente
                                : (string) ($venta['usuario'] ?? '')
                        )
                        . '
                    </td>

                    <td>
                        <b>Correo:</b> '
                        . $this->e(
                            (string) ($venta['correo'] ?? '')
                        )
                        . '
                    </td>

                </tr>

                <tr>

                    <td>
                        <b>Pago:</b> '
                        . $this->e(
                            (string) $venta['metodo_pago']
                        )
                        . '
                    </td>

                    <td>
                        <b>Entrega:</b> '
                        . $this->e(
                            ($venta['metodo_entrega'] ?? 'retiro')
                                === 'delivery'
                                ? 'Delivery'
                                : 'Retiro en local'
                        )
                        . '
                    </td>

                </tr>

            </table>

            <br>';


        // =====================================================
        // 19. TABLA DE PRODUCTOS
        // =====================================================

        $html .= '
        <table cellpadding="6">

            <thead>

                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Auto</th>
                    <th>Cant.</th>
                    <th>P. unitario</th>
                    <th>Subtotal</th>
                </tr>

            </thead>

            <tbody>';


        foreach ($detalle as $item) {

            $auto =
                (string) $item['marca']
                . ' '
                . (string) $item['modelo']
                . ' ('
                . (string) $item['anio']
                . ')';


            $descripcion =
                $this->e(
                    (string) $item['nombre_parte']
                )
                . '<br>'
                . '<span class="muted">'
                . $this->e(
                    (string) ($item['descripcion_corta'] ?? '')
                )
                . '</span>';


            $html .=
                '<tr>

                    <td>'
                    . $this->e(
                        (string) $item['codigo_inventario']
                    )
                    . '</td>

                    <td>'
                    . $descripcion
                    . '</td>

                    <td>'
                    . $this->e($auto)
                    . '</td>

                    <td class="right">'
                    . (int) $item['cantidad']
                    . '</td>

                    <td class="right">$'
                    . number_format(
                        (float) $item['precio_unitario'],
                        2
                    )
                    . '</td>

                    <td class="right">$'
                    . number_format(
                        (float) $item['subtotal'],
                        2
                    )
                    . '</td>

                </tr>';
        }


        $html .= '
            </tbody>
        </table>
        <br>';


        // =====================================================
        // 20. TOTALES
        // =====================================================

        $html .=
            '<table class="totals" cellpadding="5">

                <tr>

                    <td width="60%"></td>

                    <td width="22%">
                        <b>Subtotal:</b>
                    </td>

                    <td width="18%" class="right">
                        $'
                        . number_format(
                            (float) $venta['subtotal'],
                            2
                        )
                        . '
                    </td>

                </tr>


                <tr>

                    <td width="60%"></td>

                    <td width="22%">
                        <b>ITBMS (7%):</b>
                    </td>

                    <td width="18%" class="right">
                        $'
                        . number_format(
                            (float) $venta['itbms'],
                            2
                        )
                        . '
                    </td>

                </tr>


                <tr>

                    <td width="60%"></td>

                    <td width="22%">
                        <b>Entrega:</b>
                    </td>

                    <td width="18%" class="right">
                        $'
                        . number_format(
                            (float) $venta['costo_entrega'],
                            2
                        )
                        . '
                    </td>

                </tr>


                <tr>

                    <td width="60%"></td>

                    <td width="22%">
                        <b>TOTAL:</b>
                    </td>

                    <td width="18%" class="right">

                        <b>
                            $'
                            . number_format(
                                (float) $venta['total'],
                                2
                            )
                            . '
                        </b>

                    </td>

                </tr>

            </table>';


        // =====================================================
        // 21. DATOS DE DELIVERY
        // =====================================================

        if (
            ($venta['metodo_entrega'] ?? 'retiro')
            === 'delivery'
        ) {

            $html .=
                '<p>

                    <b>Dirección de entrega:</b> '
                    . $this->e(
                        (string) ($venta['direccion_entrega'] ?? '')
                    )
                    . '

                    <br>

                    <b>Contacto:</b> '
                    . $this->e(
                        (string) ($venta['telefono_entrega'] ?? '')
                    )
                    . '

                </p>';
        }


        // =====================================================
        // 22. INFORMACIÓN VISIBLE DE FIRMA DIGITAL
        // =====================================================
        //
        // Esta leyenda es solamente informativa.
        //
        // La firma criptográfica real se realiza arriba
        // mediante setSignature().
        //
        // =====================================================

        $html .=
            '<div class="digital-signature">

                <b>
                    Documento firmado digitalmente por '
                    . $this->e(
                        (string)
                        $this->config['business']['legal_name']
                    )
                    . '
                </b>

                <br>

                Documento generado en formato PDF/A mediante
                el sistema Mecario.

            </div>';


        // =====================================================
        // 23. INFORMACIÓN DE INTEGRIDAD
        // =====================================================

        $html .=
            '<p class="signature">

                <b>
                    Factura generada por el sistema Mecario
                </b>

                <br>

                La integridad del archivo se registra mediante
                una huella criptográfica SHA-256.

            </p>';


        // =====================================================
        // 24. GENERAR CONTENIDO DEL PDF
        // =====================================================

        $pdf->writeHTML(
            $html,
            true,
            false,
            true,
            false,
            ''
        );


        // =====================================================
        // 25. RUTA FINAL
        // =====================================================

        $ruta =
            rtrim(
                $dir,
                '/\\'
            )
            . DIRECTORY_SEPARATOR
            . $factura['numero_factura']
            . '.pdf';


        // =====================================================
        // 26. GENERAR PDF EN MEMORIA
        // =====================================================

        $contenidoPdf = $pdf->Output(
            '',
            'S'
        );


        if (
            !is_string($contenidoPdf)
            || $contenidoPdf === ''
        ) {
            throw new RuntimeException(
                'TCPDF no pudo generar el contenido de la factura.'
            );
        }


        // =====================================================
        // 27. GUARDAR PDF
        // =====================================================

        if (
            file_put_contents(
                $ruta,
                $contenidoPdf,
                LOCK_EX
            )
            === false
        ) {
            throw new RuntimeException(
                'No fue posible guardar la factura generada.'
            );
        }


        if (!is_file($ruta)) {
            throw new RuntimeException(
                'TCPDF no pudo guardar la factura generada.'
            );
        }


        // =====================================================
        // 28. GENERAR HUELLA SHA-256
        // =====================================================

        $hash = hash_file(
            'sha256',
            $ruta
        ) ?: '';


        // =====================================================
        // 29. ACTUALIZAR FACTURA EN BASE DE DATOS
        // =====================================================

        $this->facturas->marcarGenerada(
            (int) $venta['id_venta'],
            $ruta,
            $hash
        );


        // =====================================================
        // 30. RETORNAR RUTA
        // =====================================================

        return $ruta;
    }


    /**
     * Convierte una ruta física en una URI file:///
     * compatible con Windows y sistemas Unix.
     */
    private function crearFileUri(
        string $path
    ): string {

        $path = str_replace(
            '\\',
            '/',
            $path
        );


        // TCPDF 7 / tc-lib-pdf elimina literalmente el prefijo
        // "file://" antes de leer certificados. En Windows debe
        // quedar "D:/...", no "/D:/...".

        if (
            preg_match(
                '/^[A-Za-z]:\//',
                $path
            )
            === 1
        ) {
            return 'file://' . $path;
        }


        // Linux / Unix:
        // /var/www/file.pem
        // ->
        // file:///var/www/file.pem

        return 'file://' . $path;
    }


    /**
     * Escapa los datos antes de insertarlos
     * en el HTML de la factura.
     */
    private function e(
        string $value
    ): string {

        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}
