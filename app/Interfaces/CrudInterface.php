<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Contrato general para los módulos CRUD.
 *
 * Una clase que implemente esta interfaz debe proporcionar todas estas acciones.
 * Se utiliza cambiarEstado() en vez de eliminar() porque el sistema trabaja
 * principalmente con eliminación lógica: activo = 1 o activo = 0.
 */
interface CrudInterface
{
    public function listar(): array;

    public function consultar(int $id): ?array;

    public function crear(array $datos): bool;

    public function actualizar(int $id, array $datos): bool;

    public function cambiarEstado(int $id, int $activo): bool;
}
