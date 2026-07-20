<?php

declare(strict_types=1);

/**
 * FRONT CONTROLLER.
 *
 * Este es el único archivo PHP al que Apache debe enviar
 * las rutas públicas.
 *
 * Flujo:
 * navegador -> public/index.php -> Router -> Controller -> Model/View
 */

$raizProyecto = dirname(__DIR__);
$autoloadComposer = $raizProyecto . '/vendor/autoload.php';


/**
 * ============================================================
 * AUTOLOAD DE COMPOSER
 * ============================================================
 *
 * Si se ejecutó "composer install" se utiliza el autoload
 * generado por Composer.
 *
 * Si Composer no está disponible, se utiliza un cargador
 * PSR-4 sencillo únicamente para las clases App\.
 */
if (is_file($autoloadComposer)) {

    require $autoloadComposer;

} else {

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

            $archivo = $raizProyecto
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
 * Las versiones modernas utilizadas por TCPDF 7 pueden
 * almacenar la información generada de las fuentes dentro de:
 *
 * vendor/tecnickcom/tc-lib-pdf-font/target/fonts/
 *
 * Definimos K_PATH_FONTS para indicarle explícitamente
 * a TCPDF dónde debe buscar los archivos de fuentes.
 *
 * Esto debe ejecutarse ANTES de crear cualquier instancia
 * de TCPDF.
 */
$fontPath = $raizProyecto
    . '/vendor/tecnickcom/tc-lib-pdf-font/target/fonts';

if (
    !defined('K_PATH_FONTS')
    && is_dir($fontPath)
) {

    $realFontPath = realpath($fontPath);

    if ($realFontPath !== false) {

        /*
         * Normalizamos las barras para que funcione correctamente
         * tanto en Windows como en otros sistemas operativos.
         */
        $realFontPath = str_replace(
            '\\',
            '/',
            $realFontPath
        );

        /*
         * Aseguramos que la ruta termine con "/".
         */
        $realFontPath = rtrim(
            $realFontPath,
            '/'
        ) . '/';

        define(
            'K_PATH_FONTS',
            $realFontPath
        );
    }
}


/**
 * ============================================================
 * IMPORTAR CLASES PRINCIPALES
 * ============================================================
 */

use App\Core\ErrorHandler;
use App\Core\Router;
use App\Core\Session;


/**
 * ============================================================
 * CONFIGURAR EL SISTEMA
 * ============================================================
 */

ErrorHandler::registrar();

Session::iniciar();


/**
 * ============================================================
 * CREAR ROUTER
 * ============================================================
 */

$router = new Router();


/**
 * ============================================================
 * CARGAR RUTAS
 * ============================================================
 *
 * Se cargan automáticamente todos los archivos PHP
 * existentes dentro de /routes.
 *
 * Gracias a esto, cada módulo puede tener su propio
 * archivo de rutas.
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
 * EJECUTAR LA RUTA SOLICITADA
 * ============================================================
 */

$router->despachar();