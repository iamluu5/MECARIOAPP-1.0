<?php

declare(strict_types=1);

/**
 * Punto de entrada ubicado en la raíz del proyecto.
 *
 * Cuando se abre:
 * http://localhost/mecario/
 *
 * este archivo redirige automáticamente hacia la carpeta pública:
 * http://localhost/mecario/public/
 *
 * De esta manera, Apache no muestra el listado "Index of".
 */
header('Location: public/');
exit;
