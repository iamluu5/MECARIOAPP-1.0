<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Gestiona la generación y conservación del certificado
 * autofirmado utilizado para firmar las facturas de Mecario.
 *
 * Compatible con:
 * - XAMPP
 * - WampServer
 * - Configuraciones personalizadas mediante OPENSSL_CONF
 */
final class InvoiceCertificateManager
{
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/config.php';
    }

    /**
     * Garantiza que exista el certificado y la llave privada
     * utilizados para firmar digitalmente las facturas.
     *
     * @return array{
     *     certificate:string,
     *     private_key:string,
     *     password:string,
     *     fingerprint:string
     * }
     */
    public function asegurar(): array
    {
        // =====================================================
        // OBTENER CONFIGURACIÓN DE FACTURAS
        // =====================================================

        $cert = (string) $this->config['invoices']['certificate'];
        $key = (string) $this->config['invoices']['private_key'];
        $password = (string) $this->config['invoices']['private_key_password'];


        // =====================================================
        // SI YA EXISTEN, NO SE GENERAN NUEVAMENTE
        // =====================================================

        if (is_file($cert) && is_file($key)) {
            return [
                'certificate' => $cert,
                'private_key' => $key,
                'password' => $password,
                'fingerprint' => $this->fingerprint($cert),
            ];
        }


        // =====================================================
        // VERIFICAR EXTENSIÓN OPENSSL
        // =====================================================

        if (!extension_loaded('openssl')) {
            throw new RuntimeException(
                'OpenSSL debe estar habilitado en PHP para firmar facturas.'
            );
        }


        // =====================================================
        // BUSCAR OPENSSL.CNF AUTOMÁTICAMENTE
        // =====================================================

        $opensslConfig = $this->buscarOpenSslConfig();


        // =====================================================
        // CONFIGURACIÓN GENERAL DE OPENSSL
        // =====================================================

        $opensslOptions = [
            'config' => $opensslConfig,
            'digest_alg' => 'sha256',
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ];


        // =====================================================
        // CREAR DIRECTORIOS SI NO EXISTEN
        // =====================================================

        $this->crearDirectorio(dirname($cert));
        $this->crearDirectorio(dirname($key));


        // =====================================================
        // LIMPIAR ERRORES ANTERIORES DE OPENSSL
        // =====================================================

        $this->limpiarErroresOpenSsl();


        // =====================================================
        // 1. GENERAR LLAVE PRIVADA RSA
        // =====================================================

        $private = openssl_pkey_new($opensslOptions);

        if ($private === false) {
            throw new RuntimeException(
                'OpenSSL no pudo crear la llave de firma de facturas. ' .
                $this->obtenerErroresOpenSsl()
            );
        }


        // =====================================================
        // 2. DATOS DEL CERTIFICADO
        // =====================================================

        $dn = [
            'countryName' => 'PA',
            'stateOrProvinceName' => 'Panama',
            'localityName' => 'Panama',
            'organizationName' => 'Mecario S.A.',
            'organizationalUnitName' => 'Sistema de Facturacion',
            'commonName' => 'Mecario S.A.',
            'emailAddress' => 'ventas@mecario.local',
        ];


        // =====================================================
        // 3. CREAR SOLICITUD DE CERTIFICADO (CSR)
        // =====================================================

        $csr = openssl_csr_new(
            $dn,
            $private,
            $opensslOptions
        );

        if ($csr === false) {
            throw new RuntimeException(
                'OpenSSL no pudo crear la solicitud de certificado. ' .
                $this->obtenerErroresOpenSsl()
            );
        }


        // =====================================================
        // 4. GENERAR CERTIFICADO X.509 AUTOFIRMADO
        // =====================================================
        //
        // 3650 días = aproximadamente 10 años.
        // =====================================================

        $x509 = openssl_csr_sign(
            $csr,
            null,
            $private,
            3650,
            $opensslOptions
        );

        if ($x509 === false) {
            throw new RuntimeException(
                'OpenSSL no pudo generar el certificado de firma. ' .
                $this->obtenerErroresOpenSsl()
            );
        }


        // =====================================================
        // 5. EXPORTAR LLAVE PRIVADA
        // =====================================================

        $privatePem = '';

        $privateExported = openssl_pkey_export(
            $private,
            $privatePem,
            $password,
            $opensslOptions
        );

        if (!$privateExported) {
            throw new RuntimeException(
                'OpenSSL no pudo exportar la llave privada. ' .
                $this->obtenerErroresOpenSsl()
            );
        }


        // =====================================================
        // 6. EXPORTAR CERTIFICADO
        // =====================================================

        $certPem = '';

        $certificateExported = openssl_x509_export(
            $x509,
            $certPem
        );

        if (!$certificateExported) {
            throw new RuntimeException(
                'OpenSSL no pudo exportar el certificado. ' .
                $this->obtenerErroresOpenSsl()
            );
        }


        // =====================================================
        // 7. GUARDAR LLAVE PRIVADA
        // =====================================================

        if (
            file_put_contents(
                $key,
                $privatePem,
                LOCK_EX
            ) === false
        ) {
            throw new RuntimeException(
                'No fue posible guardar la llave privada de las facturas en: ' .
                $key
            );
        }


        // =====================================================
        // 8. GUARDAR CERTIFICADO
        // =====================================================

        if (
            file_put_contents(
                $cert,
                $certPem,
                LOCK_EX
            ) === false
        ) {
            throw new RuntimeException(
                'No fue posible guardar el certificado de las facturas en: ' .
                $cert
            );
        }


        // =====================================================
        // PERMISOS DE ARCHIVOS
        // =====================================================
        //
        // En Windows chmod tiene efectos limitados,
        // pero se mantiene por compatibilidad con Linux.
        // =====================================================

        @chmod($key, 0600);
        @chmod($cert, 0644);


        // =====================================================
        // RETORNAR INFORMACIÓN DEL CERTIFICADO
        // =====================================================

        return [
            'certificate' => $cert,
            'private_key' => $key,
            'password' => $password,
            'fingerprint' => $this->fingerprint($cert),
        ];
    }


    /**
     * Busca automáticamente el archivo openssl.cnf.
     *
     * La búsqueda intenta detectar:
     *
     * 1. Variable OPENSSL_CONF.
     * 2. PHP actualmente ejecutado.
     * 3. DocumentRoot del servidor.
     * 4. XAMPP.
     * 5. WampServer.
     *
     * De esta manera el proyecto puede funcionar
     * en diferentes computadoras sin modificar
     * manualmente las rutas.
     */
    private function buscarOpenSslConfig(): string
    {
        $paths = [];


        // =====================================================
        // 1. VARIABLE DE ENTORNO OPENSSL_CONF
        // =====================================================

        $environmentConfig = getenv('OPENSSL_CONF');

        if (
            is_string($environmentConfig) &&
            trim($environmentConfig) !== ''
        ) {
            $paths[] = $environmentConfig;
        }


        // =====================================================
        // 2. PHP.INI ACTUALMENTE UTILIZADO
        // =====================================================
        //
        // Ejemplo XAMPP:
        //
        // C:\xampp\php\php.ini
        //
        // dirname:
        // C:\xampp\php
        //
        // serverRoot:
        // C:\xampp
        //
        // Esto permite encontrar automáticamente:
        //
        // C:\xampp\php\extras\ssl\openssl.cnf
        // C:\xampp\apache\conf\openssl.cnf
        // =====================================================

        $phpIni = php_ini_loaded_file();

        if ($phpIni !== false) {

            $phpDirectory = dirname($phpIni);
            $serverRoot = dirname($phpDirectory);

            $paths[] =
                $phpDirectory .
                '/extras/ssl/openssl.cnf';

            $paths[] =
                $serverRoot .
                '/apache/conf/openssl.cnf';
        }


        // =====================================================
        // 3. PHP_BINDIR
        // =====================================================

        if (defined('PHP_BINDIR')) {

            $phpBinDirectory = PHP_BINDIR;
            $serverRoot = dirname($phpBinDirectory);

            $paths[] =
                $phpBinDirectory .
                '/extras/ssl/openssl.cnf';

            $paths[] =
                $serverRoot .
                '/apache/conf/openssl.cnf';
        }


        // =====================================================
        // 4. DOCUMENT_ROOT DEL SERVIDOR
        // =====================================================
        //
        // XAMPP:
        //
        // C:\xampp\htdocs
        //
        // Wamp:
        //
        // C:\wamp64\www
        // =====================================================

        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;

        if (
            is_string($documentRoot) &&
            $documentRoot !== ''
        ) {

            $realDocumentRoot = realpath($documentRoot);

            if ($realDocumentRoot !== false) {

                $serverRoot = dirname($realDocumentRoot);

                // XAMPP
                $paths[] =
                    $serverRoot .
                    '/apache/conf/openssl.cnf';

                $paths[] =
                    $serverRoot .
                    '/php/extras/ssl/openssl.cnf';
            }
        }


        // =====================================================
        // 5. RUTAS COMUNES DE XAMPP
        // =====================================================

        $xamppRoots = [
            'C:/xampp',
            'D:/xampp',
            'E:/xampp',
        ];

        foreach ($xamppRoots as $xamppRoot) {

            $paths[] =
                $xamppRoot .
                '/apache/conf/openssl.cnf';

            $paths[] =
                $xamppRoot .
                '/php/extras/ssl/openssl.cnf';
        }


        // =====================================================
        // 6. WAMPSERVER 64 BITS
        // =====================================================
        //
        // Como la versión de Apache puede variar:
        //
        // apache2.4.54
        // apache2.4.58
        // apache2.4.65
        //
        // usamos glob() para encontrarla automáticamente.
        // =====================================================

        $wampApachePaths = array_merge(

            glob(
                'C:/wamp64/bin/apache/apache*/conf/openssl.cnf'
            ) ?: [],

            glob(
                'C:/wamp/bin/apache/apache*/conf/openssl.cnf'
            ) ?: [],

            glob(
                'D:/wamp64/bin/apache/apache*/conf/openssl.cnf'
            ) ?: [],

            glob(
                'D:/wamp/bin/apache/apache*/conf/openssl.cnf'
            ) ?: [],

            glob(
                'E:/wamp64/bin/apache/apache*/conf/openssl.cnf'
            ) ?: []
        );

        foreach ($wampApachePaths as $path) {
            $paths[] = $path;
        }


        // =====================================================
        // 7. VERSIONES DE PHP DE WAMPSERVER
        // =====================================================

        $wampPhpPaths = array_merge(

            glob(
                'C:/wamp64/bin/php/php*/extras/ssl/openssl.cnf'
            ) ?: [],

            glob(
                'C:/wamp/bin/php/php*/extras/ssl/openssl.cnf'
            ) ?: [],

            glob(
                'D:/wamp64/bin/php/php*/extras/ssl/openssl.cnf'
            ) ?: [],

            glob(
                'D:/wamp/bin/php/php*/extras/ssl/openssl.cnf'
            ) ?: [],

            glob(
                'E:/wamp64/bin/php/php*/extras/ssl/openssl.cnf'
            ) ?: []
        );

        foreach ($wampPhpPaths as $path) {
            $paths[] = $path;
        }


        // =====================================================
        // 8. VALIDAR RUTAS
        // =====================================================

        foreach (array_unique($paths) as $path) {

            if (!is_string($path)) {
                continue;
            }

            // Normalizar barras para Windows/PHP.
            $path = str_replace(
                '\\',
                '/',
                trim($path)
            );

            $realPath = realpath($path);

            if (
                $realPath !== false &&
                is_file($realPath) &&
                is_readable($realPath)
            ) {
                return str_replace(
                    '\\',
                    '/',
                    $realPath
                );
            }
        }


        // =====================================================
        // NO SE ENCONTRÓ OPENSSL.CNF
        // =====================================================

        throw new RuntimeException(
            'No se encontró un archivo openssl.cnf válido. ' .
            'Verifica que OpenSSL esté habilitado en PHP y que ' .
            'tu instalación de XAMPP o WampServer incluya el ' .
            'archivo de configuración de OpenSSL.'
        );
    }


    /**
     * Crea un directorio si todavía no existe.
     */
    private function crearDirectorio(
        string $directory
    ): void {

        if (is_dir($directory)) {
            return;
        }

        if (
            !mkdir(
                $directory,
                0700,
                true
            ) &&
            !is_dir($directory)
        ) {
            throw new RuntimeException(
                'No fue posible crear el directorio: ' .
                $directory
            );
        }
    }


    /**
     * Vacía la cola de errores anteriores de OpenSSL.
     */
    private function limpiarErroresOpenSsl(): void
    {
        while (
            openssl_error_string() !== false
        ) {
            // Vaciar la cola de errores.
        }
    }


    /**
     * Recupera los mensajes de error generados
     * internamente por OpenSSL.
     */
    private function obtenerErroresOpenSsl(): string
    {
        $errors = [];

        while (
            ($error = openssl_error_string()) !== false
        ) {
            $errors[] = $error;
        }

        if ($errors === []) {
            return 'OpenSSL no proporcionó información adicional.';
        }

        return 'Detalle: ' .
            implode(
                ' | ',
                $errors
            );
    }


    /**
     * Calcula la huella SHA-256 del certificado.
     */
    private function fingerprint(
        string $certPath
    ): string {

        $pem = file_get_contents($certPath);

        if ($pem === false) {
            return '';
        }

        $cert = openssl_x509_read($pem);

        if ($cert === false) {
            return hash(
                'sha256',
                $pem
            );
        }

        $fingerprint = openssl_x509_fingerprint(
            $cert,
            'sha256'
        );

        if ($fingerprint === false) {
            return hash(
                'sha256',
                $pem
            );
        }

        return str_replace(
            ':',
            '',
            strtolower($fingerprint)
        );
    }
}