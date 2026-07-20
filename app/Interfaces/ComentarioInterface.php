<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Contrato de comentarios públicos y moderación.
 */
interface ComentarioInterface
{
    public function agregar(array $datos): bool;

    public function aprobar(
        int $idComentario,
        int $idModerador
    ): bool;

    public function ocultar(
        int $idComentario,
        int $idModerador
    ): bool;

    public function eliminar(
        int $idComentario,
        int $idModerador
    ): bool;

    public function listarPublicados(
        int $idInventario
    ): array;
}
