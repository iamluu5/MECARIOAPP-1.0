<?php

declare(strict_types=1);

/**
 * ============================================================
 * MECARIO - FRONT CONTROLLER
 * ============================================================
 *
 * Este archivo es el punto de entrada principal del sistema.
 *
 * Flujo:
 * Navegador
 *    ↓
 * public/index.php
 *    ↓
 * Router
 *    ↓
 * Controller
 *    ↓
 * Model / Service
 *    ↓
 * View
 */

$raizProyecto = dirname(__DIR__);

$autoloadComposer =
    $raizProyecto . '/vendor/autoload.php';


/**
 * ============================================================
 * AUTOLOAD DE COMPOSER
 * ============================================================
 *
 * Carga todas las dependencias instaladas mediante Composer,
 * incluyendo TCPDF y las librerías utilizadas por el proyecto.
 */

if (is_file($autoloadComposer)) {

    require $autoloadComposer;

} else {

    /**
     * Autoload alternativo para las clases App\
     * en caso de que Composer no esté disponible.
     */

    spl_autoload_register(
        static function (string $clase) use ($raizProyecto): void {

            $prefijo = 'App\\';

            if (!str_starts_with($clase, $prefijo)) {
                return;
            }

            $claseRelativa = substr(
                $clase,
                strlen($prefijo)
            );

            $archivo =
                $raizProyecto
                . '/app/'
                . str_replace(
                    '\\',
                    '/',
                    $claseRelativa
                )
                . '.php';

            if (is_file($archivo)) {
                require $archivo;
            }
        }
    );
}


/**
 * ============================================================
 * CONFIGURACIÓN DE FUENTES TCPDF
 * ============================================================
 *
 * Las fuentes generadas por tc-lib-pdf-font se encuentran
 * dentro de:
 *
 * vendor/tecnickcom/tc-lib-pdf-font/target/fonts/
 *
 * K_PATH_FONTS indica a TCPDF dónde debe buscarlas.
 */

$fontPath =
    $raizProyecto
    . '/vendor/tecnickcom/tc-lib-pdf-font/target/fonts';

if (
    !defined('K_PATH_FONTS')
    && is_dir($fontPath)
) {

    $realFontPath = realpath($fontPath);

    if ($realFontPath !== false) {

        // Normalizar las barras para Windows.
        $realFontPath = str_replace(
            '\\',
            '/',
            $realFontPath
        );

        // Garantizar que la ruta termine en "/".
        $realFontPath =
            rtrim(
                $realFontPath,
                '/'
            )
            . '/';

        define(
            'K_PATH_FONTS',
            $realFontPath
        );
    }
}


/**
 * ============================================================
 * RECURSOS INTERNOS DE TCPDF / TC-LIB-PDF
 * ============================================================
 *
 * La generación de documentos PDF/A necesita recursos
 * adicionales, entre ellos el perfil de color:
 *
 * sRGB.icc.z
 *
 * En la instalación actual se encuentra en:
 *
 * vendor/tecnickcom/tc-lib-pdf/src/include/
 *
 * K_ALLOWED_PATHS permite que TCPDF acceda a esta ubicación.
 */

$tcpdfIncludePath =
    $raizProyecto
    . '/vendor/tecnickcom/tc-lib-pdf/src/include';

$invoiceStoragePath =
    $raizProyecto
    . '/storage';

if (
    !defined('K_ALLOWED_PATHS')
    && is_dir($tcpdfIncludePath)
) {

    $realTcpdfIncludePath = realpath(
        $tcpdfIncludePath
    );

    $realInvoiceStoragePath = realpath(
        $invoiceStoragePath
    );

    if (
        $realTcpdfIncludePath !== false
        && $realInvoiceStoragePath !== false
    ) {

        $realTcpdfIncludePath =
            str_replace(
                '\\',
                '/',
                $realTcpdfIncludePath
            );

        $realInvoiceStoragePath =
            str_replace(
                '\\',
                '/',
                $realInvoiceStoragePath
            );

        define(
            'K_ALLOWED_PATHS',
            [
                $realTcpdfIncludePath,
                $realInvoiceStoragePath,
            ]
        );
    }
}


/**
 * ============================================================
 * CLASES PRINCIPALES DEL SISTEMA
 * ============================================================
 */

use App\Core\ErrorHandler;
use App\Core\Router;
use App\Core\Session;


/**
 * ============================================================
 * CONTROL GLOBAL DE ERRORES
 * ============================================================
 */

ErrorHandler::registrar();


/**
 * ============================================================
 * INICIAR SESIÓN PHP
 * ============================================================
 */

Session::iniciar();


/**
 * ============================================================
 * CREAR ROUTER
 * ============================================================
 */

$router = new Router();


/**
 * ============================================================
 * CARGAR ARCHIVOS DE RUTAS
 * ============================================================
 *
 * Cada módulo puede tener su propio archivo dentro de /routes.
 *
 * Ejemplos:
 *
 * routes/auth.php
 * routes/inventario.php
 * routes/ventas.php
 * routes/facturas.php
 *
 * Todos se cargan automáticamente.
 */

$archivosRutas = glob(
    $raizProyecto . '/routes/*.php'
) ?: [];

sort($archivosRutas);

foreach ($archivosRutas as $archivoRuta) {

    $registrarRutas = require $archivoRuta;

    if (is_callable($registrarRutas)) {

        $registrarRutas(
            $router
        );
    }
}


/**
 * ============================================================
 * EJECUTAR LA PETICIÓN
 * ============================================================
 */

$router->despachar();
