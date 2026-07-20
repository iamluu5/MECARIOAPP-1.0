<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Validador reutilizable para formularios.
 *
 * Cada controlador puede encadenar reglas:
 *
 * $validador
 *     ->requerido('nombre', $nombre)
 *     ->longitudMaxima('nombre', $nombre, 100);
 */
final class Validator
{
    /**
     * Errores agrupados por nombre de campo.
     */
    private array $errores = [];

    public function requerido(
        string $campo,
        mixed $valor,
        ?string $mensaje = null
    ): self {
        if ($valor === null || trim((string) $valor) === '') {
            $this->errores[$campo][] =
                $mensaje ?? "El campo {$campo} es obligatorio.";
        }

        return $this;
    }

    public function correo(
        string $campo,
        mixed $valor,
        ?string $mensaje = null
    ): self {
        if (
            $valor !== null
            && $valor !== ''
            && filter_var($valor, FILTER_VALIDATE_EMAIL) === false
        ) {
            $this->errores[$campo][] =
                $mensaje ?? "El campo {$campo} debe contener un correo válido.";
        }

        return $this;
    }

    public function longitudMinima(
        string $campo,
        mixed $valor,
        int $minimo,
        ?string $mensaje = null
    ): self {
        if (mb_strlen((string) $valor) < $minimo) {
            $this->errores[$campo][] =
                $mensaje ?? "El campo {$campo} debe tener al menos {$minimo} caracteres.";
        }

        return $this;
    }

    public function longitudMaxima(
        string $campo,
        mixed $valor,
        int $maximo,
        ?string $mensaje = null
    ): self {
        if (mb_strlen((string) $valor) > $maximo) {
            $this->errores[$campo][] =
                $mensaje ?? "El campo {$campo} no puede superar {$maximo} caracteres.";
        }

        return $this;
    }

    public function enteroPositivo(
        string $campo,
        mixed $valor,
        bool $permitirCero = true
    ): self {
        $opciones = ['options' => ['min_range' => $permitirCero ? 0 : 1]];

        if (filter_var($valor, FILTER_VALIDATE_INT, $opciones) === false) {
            $this->errores[$campo][] =
                "El campo {$campo} debe ser un número entero positivo.";
        }

        return $this;
    }

    public function decimalPositivo(
        string $campo,
        mixed $valor,
        bool $permitirCero = true
    ): self {
        if (
            !is_numeric($valor)
            || (float) $valor < ($permitirCero ? 0 : 0.01)
        ) {
            $this->errores[$campo][] =
                "El campo {$campo} debe ser un número positivo.";
        }

        return $this;
    }

    public function enLista(
        string $campo,
        mixed $valor,
        array $permitidos
    ): self {
        if (!in_array($valor, $permitidos, true)) {
            $this->errores[$campo][] =
                "El valor enviado para {$campo} no está permitido.";
        }

        return $this;
    }

    public function esValido(): bool
    {
        return $this->errores === [];
    }

    public function obtenerErrores(): array
    {
        return $this->errores;
    }

    public function primerError(): string
    {
        foreach ($this->errores as $erroresCampo) {
            if (isset($erroresCampo[0])) {
                return $erroresCampo[0];
            }
        }

        return '';
    }
}
