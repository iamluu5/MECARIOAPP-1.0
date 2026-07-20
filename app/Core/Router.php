<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Router sencillo de la aplicación.
 *
 * Su trabajo es relacionar una URL con una acción de un controlador.
 *
 * El Router NO realiza consultas SQL y NO contiene HTML.
 */
final class Router
{
    /**
     * Almacena las rutas agrupadas por método HTTP.
     */
    private array $rutas = [];

    public function get(string $ruta, callable|array $accion): void
    {
        $this->agregar('GET', $ruta, $accion);
    }

    public function post(string $ruta, callable|array $accion): void
    {
        $this->agregar('POST', $ruta, $accion);
    }

    private function agregar(
        string $metodo,
        string $ruta,
        callable|array $accion
    ): void {
        $this->rutas[$metodo][] = [
            'ruta' => $this->normalizar($ruta),
            'accion' => $accion,
        ];
    }

    /**
     * Busca la ruta solicitada y ejecuta su controlador.
     */
    public function despachar(): void
    {
        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $rutaActual = $this->obtenerRutaActual();

        foreach ($this->rutas[$metodo] ?? [] as $rutaRegistrada) {
            $parametros = $this->coincide(
                $rutaRegistrada['ruta'],
                $rutaActual
            );

            if ($parametros !== false) {
                $this->ejecutarAccion(
                    $rutaRegistrada['accion'],
                    $parametros
                );
                return;
            }
        }

        /**
         * Si la URL existe para otro método, se responde 405.
         * Ejemplo: intentar abrir con GET una ruta registrada solo como POST.
         */
        if ($this->existeEnOtroMetodo($rutaActual, $metodo)) {
            http_response_code(405);
            View::renderizar('errors/405', [
                'titulo' => 'Método no permitido',
            ]);
            return;
        }

        http_response_code(404);
        View::renderizar('errors/404', [
            'titulo' => 'Página no encontrada',
        ]);
    }

    /**
     * Obtiene la ruta actual eliminando la carpeta /public del inicio.
     *
     * Esto permite que funcione en:
     * http://localhost/mecario/public/login
     */
    private function obtenerRutaActual(): string
    {
        $uri = parse_url(
            $_SERVER['REQUEST_URI'] ?? '/',
            PHP_URL_PATH
        ) ?: '/';

        $base = dirname($_SERVER['SCRIPT_NAME'] ?? '');

        if (
            $base !== ''
            && $base !== '/'
            && str_starts_with($uri, $base)
        ) {
            $uri = substr($uri, strlen($base));
        }

        return $this->normalizar($uri);
    }

    private function normalizar(string $ruta): string
    {
        $ruta = '/' . trim($ruta, '/');

        return $ruta === '//' ? '/' : $ruta;
    }

    /**
     * Permite rutas con parámetros.
     *
     * Ejemplo registrado:
     * /usuarios/ver/{id}
     *
     * Ejemplo solicitado:
     * /usuarios/ver/10
     *
     * El método devuelve ["10"] para enviarlo al controlador.
     */
    private function coincide(
        string $rutaRegistrada,
        string $rutaActual
    ): array|false {
        $patron = preg_replace(
            '#\{[a-zA-Z_][a-zA-Z0-9_]*\}#',
            '([^/]+)',
            $rutaRegistrada
        );

        if (!preg_match('#^' . $patron . '$#', $rutaActual, $coincidencias)) {
            return false;
        }

        array_shift($coincidencias);

        return $coincidencias;
    }

    private function ejecutarAccion(
        callable|array $accion,
        array $parametros
    ): void {
        /**
         * Cuando la acción tiene este formato:
         * [UsuarioController::class, 'index']
         *
         * se crea el controlador automáticamente.
         */
        if (
            is_array($accion)
            && isset($accion[0], $accion[1])
            && is_string($accion[0])
        ) {
            $controlador = new $accion[0]();
            $metodo = $accion[1];

            if (!method_exists($controlador, $metodo)) {
                throw new RuntimeException(
                    "El método {$metodo} no existe en el controlador."
                );
            }

            $controlador->{$metodo}(...$parametros);
            return;
        }

        $accion(...$parametros);
    }

    private function existeEnOtroMetodo(
        string $rutaActual,
        string $metodoActual
    ): bool {
        foreach ($this->rutas as $metodo => $rutas) {
            if ($metodo === $metodoActual) {
                continue;
            }

            foreach ($rutas as $ruta) {
                if ($this->coincide($ruta['ruta'], $rutaActual) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}
