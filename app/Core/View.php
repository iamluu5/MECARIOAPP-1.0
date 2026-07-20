<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Clase encargada de cargar las vistas.
 *
 * Un controlador no debe repetir:
 * require header.php;
 * require vista.php;
 * require footer.php;
 *
 * View centraliza ese proceso y aplica DRY.
 */
final class View
{
    /**
     * Renderiza una vista dentro del layout general.
     *
     * @param string $vista Ruta relativa dentro de app/Views sin ".php".
     *                      Ejemplo: "auth/login".
     * @param array $datos Variables que estarán disponibles en la vista.
     * @param bool $usarLayout Indica si carga header y footer.
     */
    public static function renderizar(
        string $vista,
        array $datos = [],
        bool $usarLayout = true
    ): void {
        $archivoVista = dirname(__DIR__) . '/Views/' . $vista . '.php';

        if (!is_file($archivoVista)) {
            throw new RuntimeException("La vista {$vista} no existe.");
        }

        /**
         * Convierte las claves del arreglo en variables.
         * Ejemplo: ['titulo' => 'Login'] crea la variable $titulo.
         */
        extract($datos, EXTR_SKIP);

        if ($usarLayout) {
            require dirname(__DIR__) . '/Views/layout/header.php';
        }

        require $archivoVista;

        if ($usarLayout) {
            require dirname(__DIR__) . '/Views/layout/footer.php';
        }
    }
}
