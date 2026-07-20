<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Session;
use App\Interfaces\CarritoInterface;
use RuntimeException;

/**
 * Carrito de compras almacenado en sesión.
 *
 * No guarda precios enviados por el navegador. Cada lectura consulta la BD para
 * impedir que un cliente modifique el precio desde HTML o herramientas del navegador.
 */
final class Carrito implements CarritoInterface
{
    private const CLAVE = 'carrito';
    private ?Database $db = null;

    public function __construct()
    {
        // La conexión se crea solo cuando el carrito necesita consultar inventario.
    }

    public function agregar(int $idInventario, int $cantidad = 1): void
    {
        if ($idInventario <= 0 || $cantidad <= 0) {
            throw new RuntimeException('La pieza o cantidad indicada no es válida.');
        }

        $pieza = $this->obtenerPiezaDisponible($idInventario);
        if ($pieza === null) {
            throw new RuntimeException('La pieza ya no está disponible.');
        }

        $carrito = $this->datosSesion();
        $actual = (int) ($carrito[$idInventario] ?? 0);
        $nuevaCantidad = $actual + $cantidad;

        if ($nuevaCantidad > (int) $pieza['cantidad']) {
            throw new RuntimeException('La cantidad solicitada supera las existencias disponibles.');
        }

        $carrito[$idInventario] = $nuevaCantidad;
        Session::guardar(self::CLAVE, $carrito);
    }

    public function actualizar(array $cantidades): void
    {
        $carrito = $this->datosSesion();

        foreach ($cantidades as $id => $cantidadRecibida) {
            $idInventario = (int) $id;
            $cantidad = max(0, (int) $cantidadRecibida);

            if (!array_key_exists($idInventario, $carrito)) {
                continue;
            }

            if ($cantidad === 0) {
                unset($carrito[$idInventario]);
                continue;
            }

            $pieza = $this->obtenerPiezaDisponible($idInventario);
            if ($pieza === null) {
                unset($carrito[$idInventario]);
                continue;
            }

            $carrito[$idInventario] = min($cantidad, (int) $pieza['cantidad']);
        }

        Session::guardar(self::CLAVE, $carrito);
    }

    public function eliminar(int $idInventario): void
    {
        $carrito = $this->datosSesion();
        unset($carrito[$idInventario]);
        Session::guardar(self::CLAVE, $carrito);
    }

    public function vaciar(): void
    {
        Session::eliminar(self::CLAVE);
        Session::eliminar('pago_actual');
    }

    public function obtenerItems(): array
    {
        $carrito = $this->datosSesion();
        if ($carrito === []) {
            return [];
        }

        $items = [];
        $carritoLimpio = [];

        foreach ($carrito as $idInventario => $cantidadSolicitada) {
            $pieza = $this->obtenerPiezaDisponible((int) $idInventario);
            if ($pieza === null) {
                continue;
            }

            $cantidad = min((int) $cantidadSolicitada, (int) $pieza['cantidad']);
            if ($cantidad <= 0) {
                continue;
            }

            $pieza['cantidad_carrito'] = $cantidad;
            $pieza['subtotal'] = round(((float) $pieza['precio']) * $cantidad, 2);
            $items[] = $pieza;
            $carritoLimpio[(int) $idInventario] = $cantidad;
        }

        if ($carritoLimpio !== $carrito) {
            Session::guardar(self::CLAVE, $carritoLimpio);
        }

        return $items;
    }

    public function calcularTotal(): float
    {
        $total = 0.0;
        foreach ($this->obtenerItems() as $item) {
            $total += (float) $item['subtotal'];
        }
        return round($total, 2);
    }

    public function cantidadTotal(): int
    {
        return array_sum(array_map('intval', $this->datosSesion()));
    }

    /**
     * Convierte el carrito al formato esperado por Venta::procesarVenta().
     */
    public function obtenerDetallesVenta(): array
    {
        return array_map(
            static fn(array $item): array => [
                'id_inventario' => (int) $item['id_inventario'],
                'cantidad' => (int) $item['cantidad_carrito'],
            ],
            $this->obtenerItems()
        );
    }

    private function datosSesion(): array
    {
        $carrito = Session::obtener(self::CLAVE, []);
        return is_array($carrito) ? $carrito : [];
    }

    private function obtenerPiezaDisponible(int $idInventario): ?array
    {
        $this->db ??= Database::getInstancia();

        return $this->db->consultarUno(
            'SELECT
                i.id_inventario,
                i.codigo_inventario,
                i.descripcion_corta,
                i.precio,
                i.cantidad,
                i.thumbnail,
                p.nombre_parte,
                a.marca,
                a.modelo,
                a.anio
            FROM inventario_partes i
            INNER JOIN partes p ON p.id_parte = i.id_parte
            INNER JOIN autos a ON a.id_auto = i.id_auto
            WHERE i.id_inventario = :id
                AND i.activo = 1
                AND i.cantidad > 0',
            ['id' => $idInventario]
        );
    }
}
