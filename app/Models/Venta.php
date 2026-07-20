<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Interfaces\VendibleInterface;
use App\Services\CommerceCalculator;
use DateTimeImmutable;
use RuntimeException;

/** Ventas, facturación fiscal básica y salida transaccional de inventario. */
final class Venta implements VendibleInterface
{
    private Database $db;
    private CommerceCalculator $calculator;

    public function __construct()
    {
        $this->db = Database::getInstancia();
        $this->calculator = new CommerceCalculator();
    }

    public function listarVentas(string $desde = '', string $hasta = ''): array
    {
        $sql = 'SELECT
                v.id_venta, v.subtotal, v.itbms, v.total, v.estado, v.observacion,
                v.origen, v.metodo_pago, v.estado_pago, v.metodo_entrega,
                v.costo_entrega, v.fecha_venta, u.usuario, f.numero_factura
            FROM ventas v
            INNER JOIN usuarios u ON u.id_usuario = v.id_usuario
            LEFT JOIN facturas f ON f.id_venta = v.id_venta
            WHERE 1 = 1';
        $params = [];

        if ($desde !== '') {
            $sql .= ' AND DATE(v.fecha_venta) >= :desde';
            $params['desde'] = $desde;
        }
        if ($hasta !== '') {
            $sql .= ' AND DATE(v.fecha_venta) <= :hasta';
            $params['hasta'] = $hasta;
        }

        $sql .= ' ORDER BY v.fecha_venta DESC, v.id_venta DESC';
        return $this->db->consultarTodos($sql, $params);
    }

    public function listarVentasUsuario(int $idUsuario): array
    {
        return $this->db->consultarTodos(
            'SELECT v.id_venta, v.subtotal, v.itbms, v.total, v.estado, v.origen,
                    v.metodo_pago, v.estado_pago, v.referencia_pago, v.metodo_entrega,
                    v.direccion_entrega, v.telefono_entrega, v.costo_entrega,
                    v.fecha_venta, f.numero_factura
             FROM ventas v
             LEFT JOIN facturas f ON f.id_venta = v.id_venta
             WHERE v.id_usuario = :usuario AND v.origen = "cliente"
             ORDER BY v.fecha_venta DESC, v.id_venta DESC',
            ['usuario' => $idUsuario]
        );
    }

    public function obtenerReporteVentas(string $desde = '', string $hasta = ''): array
    {
        $sql = 'SELECT
                v.id_venta, f.numero_factura, v.fecha_venta,
                u.usuario AS usuario_responsable, v.origen, v.metodo_pago,
                v.estado_pago, v.metodo_entrega, v.direccion_entrega,
                v.telefono_entrega, v.costo_entrega, v.subtotal AS subtotal_venta,
                v.itbms, v.total AS total_venta, v.estado,
                p.nombre_parte, s.nombre_seccion AS categoria,
                CONCAT(a.marca, " ", a.modelo, " (", a.anio, ")") AS auto,
                i.codigo_inventario, vd.cantidad, vd.precio_unitario, vd.subtotal
            FROM ventas v
            INNER JOIN usuarios u ON u.id_usuario = v.id_usuario
            INNER JOIN venta_detalles vd ON vd.id_venta = v.id_venta
            INNER JOIN inventario_partes i ON i.id_inventario = vd.id_inventario
            INNER JOIN partes p ON p.id_parte = i.id_parte
            INNER JOIN secciones s ON s.id_seccion = i.id_seccion
            INNER JOIN autos a ON a.id_auto = i.id_auto
            LEFT JOIN facturas f ON f.id_venta = v.id_venta
            WHERE 1 = 1';
        $params = [];

        if ($desde !== '') {
            $sql .= ' AND DATE(v.fecha_venta) >= :desde';
            $params['desde'] = $desde;
        }
        if ($hasta !== '') {
            $sql .= ' AND DATE(v.fecha_venta) <= :hasta';
            $params['hasta'] = $hasta;
        }

        $sql .= ' ORDER BY v.fecha_venta DESC, v.id_venta DESC, vd.id_detalle ASC';
        return $this->db->consultarTodos($sql, $params);
    }

    public function obtenerVenta(int $idVenta): ?array
    {
        return $this->db->consultarUno(
            'SELECT v.id_venta, v.id_usuario, v.subtotal, v.itbms, v.total, v.estado,
                    v.observacion, v.origen, v.metodo_pago, v.estado_pago,
                    v.referencia_pago, v.metodo_entrega, v.direccion_entrega,
                    v.telefono_entrega, v.costo_entrega, v.fecha_venta,
                    u.usuario, u.nombre, u.apellido, u.correo,
                    f.id_factura, f.numero_factura, f.estado_firma, f.hash_pdf_sha256
             FROM ventas v
             INNER JOIN usuarios u ON u.id_usuario = v.id_usuario
             LEFT JOIN facturas f ON f.id_venta = v.id_venta
             WHERE v.id_venta = :id',
            ['id' => $idVenta]
        );
    }

    public function obtenerVentaUsuario(int $idVenta, int $idUsuario): ?array
    {
        return $this->db->consultarUno(
            'SELECT v.id_venta, v.id_usuario, v.subtotal, v.itbms, v.total, v.estado,
                    v.observacion, v.origen, v.metodo_pago, v.estado_pago,
                    v.referencia_pago, v.metodo_entrega, v.direccion_entrega,
                    v.telefono_entrega, v.costo_entrega, v.fecha_venta,
                    u.usuario, u.nombre, u.apellido, u.correo,
                    f.id_factura, f.numero_factura, f.estado_firma, f.hash_pdf_sha256
             FROM ventas v
             INNER JOIN usuarios u ON u.id_usuario = v.id_usuario
             LEFT JOIN facturas f ON f.id_venta = v.id_venta
             WHERE v.id_venta = :id AND v.id_usuario = :usuario AND v.origen = "cliente"',
            ['id' => $idVenta, 'usuario' => $idUsuario]
        );
    }

    public function obtenerDetalleVenta(int $idVenta): array
    {
        return $this->db->consultarTodos(
            'SELECT vd.id_detalle, vd.cantidad, vd.precio_unitario, vd.subtotal,
                    p.nombre_parte, s.nombre_seccion AS categoria,
                    i.codigo_inventario, i.descripcion_corta, a.marca, a.modelo, a.anio
             FROM venta_detalles vd
             INNER JOIN inventario_partes i ON i.id_inventario = vd.id_inventario
             INNER JOIN partes p ON p.id_parte = i.id_parte
             INNER JOIN secciones s ON s.id_seccion = i.id_seccion
             INNER JOIN autos a ON a.id_auto = i.id_auto
             WHERE vd.id_venta = :id
             ORDER BY vd.id_detalle ASC',
            ['id' => $idVenta]
        );
    }

    public function obtenerInventarioDisponible(): array
    {
        return $this->db->consultarTodos(
            'SELECT i.id_inventario, i.codigo_inventario, p.nombre_parte,
                    a.marca, a.modelo, a.anio, i.precio, i.cantidad, i.condicion_pieza
             FROM inventario_partes i
             INNER JOIN partes p ON p.id_parte = i.id_parte
             INNER JOIN autos a ON a.id_auto = i.id_auto
             WHERE i.activo = 1 AND i.cantidad > 0
             ORDER BY p.nombre_parte, a.marca, a.modelo'
        );
    }

    /** Venta + factura + detalles + descuento de inventario, todo en transacción. */
    public function procesarVenta(
        int $idUsuario,
        array $detalles,
        ?string $observacion = null,
        array $pago = []
    ): int {
        if ($detalles === []) {
            throw new RuntimeException('La venta debe contener al menos una pieza.');
        }

        $origen = in_array(($pago['origen'] ?? 'interno'), ['interno', 'cliente'], true)
            ? (string) ($pago['origen'] ?? 'interno') : 'interno';
        $metodosPermitidos = ['Efectivo', 'Yappy', 'Visa', 'Mastercard'];
        $metodoPago = in_array(($pago['metodo_pago'] ?? 'Efectivo'), $metodosPermitidos, true)
            ? (string) ($pago['metodo_pago'] ?? 'Efectivo') : 'Efectivo';
        $estadoPago = ($pago['estado_pago'] ?? 'no_aplica') === 'confirmado' ? 'confirmado' : 'no_aplica';
        $referencia = isset($pago['referencia_pago']) ? substr((string) $pago['referencia_pago'], 0, 80) : null;
        $metodoEntrega = in_array(($pago['metodo_entrega'] ?? 'retiro'), ['retiro', 'delivery'], true)
            ? (string) ($pago['metodo_entrega'] ?? 'retiro') : 'retiro';
        $direccionEntrega = $metodoEntrega === 'delivery' ? substr(trim((string) ($pago['direccion_entrega'] ?? '')), 0, 255) : null;
        $telefonoEntrega = $metodoEntrega === 'delivery' ? substr(trim((string) ($pago['telefono_entrega'] ?? '')), 0, 30) : null;
        $costoEntrega = $metodoEntrega === 'delivery' ? max(0.0, round((float) ($pago['costo_entrega'] ?? 0), 2)) : 0.0;

        if ($metodoEntrega === 'delivery' && ($direccionEntrega === '' || $telefonoEntrega === '')) {
            throw new RuntimeException('Faltan los datos necesarios para coordinar el delivery.');
        }

        return $this->db->transaccion(function () use (
            $idUsuario, $detalles, $observacion, $origen, $metodoPago,
            $estadoPago, $referencia, $metodoEntrega, $direccionEntrega,
            $telefonoEntrega, $costoEntrega
        ): int {
            $normalizados = [];
            $subtotal = 0.0;

            foreach ($detalles as $detalle) {
                $idInventario = (int) ($detalle['id_inventario'] ?? 0);
                $cantidad = (int) ($detalle['cantidad'] ?? 0);
                $inventario = $this->obtenerInventarioBloqueado($idInventario);

                if ($inventario === null) {
                    throw new RuntimeException('Una de las piezas seleccionadas ya no existe o está inactiva.');
                }

                $this->verificarExistencias($inventario, $cantidad);
                $precio = (float) $inventario['precio'];
                $subtotal += $precio * $cantidad;
                $normalizados[] = ['id_inventario' => $idInventario, 'cantidad' => $cantidad, 'precio' => $precio];
            }

            $totales = $this->calculator->calcular($subtotal, $costoEntrega);

            $idVenta = $this->registrarVenta(
                $idUsuario,
                $totales['subtotal'],
                $totales['itbms'],
                $totales['total'],
                $observacion,
                $origen,
                $metodoPago,
                $estadoPago,
                $referencia,
                $metodoEntrega,
                $direccionEntrega,
                $telefonoEntrega,
                $totales['costo_entrega']
            );

            foreach ($normalizados as $detalle) {
                $this->registrarDetalle($idVenta, $detalle['id_inventario'], $detalle['cantidad'], $detalle['precio']);
                $this->disminuirInventario($detalle['id_inventario'], $detalle['cantidad']);
            }

            $this->registrarFactura($idVenta, $totales);
            return $idVenta;
        });
    }

    public function obtenerEstadisticasVentas(): array
    {
        return $this->db->consultarUno(
            'SELECT
                SUM(CASE WHEN DATE(fecha_venta) = CURDATE() AND estado = "completada" THEN 1 ELSE 0 END) AS ventas_hoy,
                COALESCE(SUM(CASE WHEN DATE(fecha_venta) = CURDATE() AND estado = "completada" THEN total ELSE 0 END), 0) AS ingresos_hoy,
                SUM(CASE WHEN fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND estado = "completada" THEN 1 ELSE 0 END) AS ventas_7_dias,
                COALESCE(SUM(CASE WHEN fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND estado = "completada" THEN total ELSE 0 END), 0) AS ingresos_7_dias
             FROM ventas'
        ) ?? ['ventas_hoy'=>0,'ingresos_hoy'=>0,'ventas_7_dias'=>0,'ingresos_7_dias'=>0];
    }

    public function obtenerVentasUltimosDias(int $dias = 7): array
    {
        $dias = max(2, min(30, $dias));
        $filas = $this->db->consultarTodos(
            'SELECT DATE(fecha_venta) AS fecha, COUNT(*) AS cantidad_ventas, COALESCE(SUM(total), 0) AS total
             FROM ventas
             WHERE estado = "completada" AND fecha_venta >= DATE_SUB(CURDATE(), INTERVAL ' . ($dias - 1) . ' DAY)
             GROUP BY DATE(fecha_venta) ORDER BY fecha ASC'
        );
        $porFecha = [];
        foreach ($filas as $fila) {
            $porFecha[(string)$fila['fecha']] = ['fecha'=>(string)$fila['fecha'],'cantidad_ventas'=>(int)$fila['cantidad_ventas'],'total'=>(float)$fila['total']];
        }
        $resultado = [];
        $hoy = new DateTimeImmutable('today');
        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = $hoy->modify('-' . $i . ' days')->format('Y-m-d');
            $resultado[] = $porFecha[$fecha] ?? ['fecha'=>$fecha,'cantidad_ventas'=>0,'total'=>0.0];
        }
        return $resultado;
    }

    public function obtenerVentasPorMetodoPago(): array
    {
        return $this->db->consultarTodos(
            'SELECT metodo_pago, COUNT(*) AS cantidad_ventas, COALESCE(SUM(total), 0) AS total
             FROM ventas WHERE estado = "completada"
             GROUP BY metodo_pago ORDER BY total DESC, metodo_pago ASC'
        );
    }

    /** Estadística solicitada por la rúbrica: total vendido por mes. */
    public function obtenerVentasPorMes(int $meses = 6): array
    {
        $meses = max(3, min(12, $meses));
        $filas = $this->db->consultarTodos(
            'SELECT DATE_FORMAT(fecha_venta, "%Y-%m") AS periodo,
                    COUNT(*) AS cantidad_ventas, COALESCE(SUM(total), 0) AS total
             FROM ventas
             WHERE estado = "completada"
               AND fecha_venta >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ' . ($meses - 1) . ' MONTH), "%Y-%m-01")
             GROUP BY DATE_FORMAT(fecha_venta, "%Y-%m") ORDER BY periodo ASC'
        );
        $map = [];
        foreach ($filas as $f) {
            $map[$f['periodo']] = ['periodo'=>$f['periodo'],'cantidad_ventas'=>(int)$f['cantidad_ventas'],'total'=>(float)$f['total']];
        }
        $resultado = [];
        $base = new DateTimeImmutable('first day of this month');
        for ($i = $meses - 1; $i >= 0; $i--) {
            $periodo = $base->modify('-' . $i . ' months')->format('Y-m');
            $resultado[] = $map[$periodo] ?? ['periodo'=>$periodo,'cantidad_ventas'=>0,'total'=>0.0];
        }
        return $resultado;
    }

    /** Total monetario y unidades vendidas agrupadas por categoría/sección. */
    public function obtenerTotalesPorCategoria(): array
    {
        return $this->db->consultarTodos(
            'SELECT s.nombre_seccion AS categoria,
                    SUM(vd.cantidad) AS unidades,
                    COALESCE(SUM(vd.subtotal), 0) AS total
             FROM venta_detalles vd
             INNER JOIN ventas v ON v.id_venta = vd.id_venta AND v.estado = "completada"
             INNER JOIN inventario_partes i ON i.id_inventario = vd.id_inventario
             INNER JOIN secciones s ON s.id_seccion = i.id_seccion
             GROUP BY s.id_seccion, s.nombre_seccion
             ORDER BY total DESC, categoria ASC'
        );
    }

    /** Ranking de partes más vendidas por unidades. */
    public function obtenerPartesMasVendidas(int $limite = 5): array
    {
        $limite = max(1, min(20, $limite));
        return $this->db->consultarTodos(
            'SELECT p.nombre_parte,
                    SUM(vd.cantidad) AS unidades,
                    COALESCE(SUM(vd.subtotal), 0) AS total
             FROM venta_detalles vd
             INNER JOIN ventas v ON v.id_venta = vd.id_venta AND v.estado = "completada"
             INNER JOIN inventario_partes i ON i.id_inventario = vd.id_inventario
             INNER JOIN partes p ON p.id_parte = i.id_parte
             GROUP BY p.id_parte, p.nombre_parte
             ORDER BY unidades DESC, total DESC, p.nombre_parte ASC
             LIMIT ' . $limite
        );
    }

    private function obtenerInventarioBloqueado(int $idInventario): ?array
    {
        return $this->db->consultarUno(
            'SELECT id_inventario, precio, cantidad FROM inventario_partes
             WHERE id_inventario = :id AND activo = 1 FOR UPDATE',
            ['id' => $idInventario]
        );
    }

    private function registrarVenta(
        int $idUsuario,
        float $subtotal,
        float $itbms,
        float $total,
        ?string $observacion,
        string $origen,
        string $metodoPago,
        string $estadoPago,
        ?string $referencia,
        string $metodoEntrega,
        ?string $direccionEntrega,
        ?string $telefonoEntrega,
        float $costoEntrega
    ): int {
        return $this->db->insertar(
            'INSERT INTO ventas
                (id_usuario, subtotal, itbms, total, observacion, origen, metodo_pago,
                 estado_pago, referencia_pago, metodo_entrega, direccion_entrega,
                 telefono_entrega, costo_entrega)
             VALUES
                (:usuario, :subtotal, :itbms, :total, :observacion, :origen, :metodo_pago,
                 :estado_pago, :referencia_pago, :metodo_entrega, :direccion_entrega,
                 :telefono_entrega, :costo_entrega)',
            [
                'usuario'=>$idUsuario,'subtotal'=>$subtotal,'itbms'=>$itbms,'total'=>$total,
                'observacion'=>$observacion,'origen'=>$origen,'metodo_pago'=>$metodoPago,
                'estado_pago'=>$estadoPago,'referencia_pago'=>$referencia,
                'metodo_entrega'=>$metodoEntrega,'direccion_entrega'=>$direccionEntrega,
                'telefono_entrega'=>$telefonoEntrega,'costo_entrega'=>$costoEntrega,
            ]
        );
    }

    private function registrarFactura(int $idVenta, array $totales): void
    {
        $numero = 'MEC-FAC-' . date('Y') . '-' . str_pad((string)$idVenta, 6, '0', STR_PAD_LEFT);
        $this->db->ejecutar(
            'INSERT INTO facturas
                (id_venta, numero_factura, subtotal, itbms, costo_entrega, total, estado_firma)
             VALUES
                (:venta, :numero, :subtotal, :itbms, :entrega, :total, "pendiente")',
            [
                'venta'=>$idVenta,'numero'=>$numero,'subtotal'=>$totales['subtotal'],
                'itbms'=>$totales['itbms'],'entrega'=>$totales['costo_entrega'],'total'=>$totales['total'],
            ]
        );
    }

    private function registrarDetalle(int $idVenta, int $idInventario, int $cantidad, float $precioUnitario): void
    {
        $this->db->ejecutar(
            'INSERT INTO venta_detalles (id_venta, id_inventario, cantidad, precio_unitario)
             VALUES (:venta, :inventario, :cantidad, :precio)',
            ['venta'=>$idVenta,'inventario'=>$idInventario,'cantidad'=>$cantidad,'precio'=>$precioUnitario]
        );
    }

    private function verificarExistencias(array $inventario, int $cantidadSolicitada): void
    {
        if ($cantidadSolicitada <= 0) {
            throw new RuntimeException('La cantidad debe ser mayor que cero.');
        }
        if ((int)$inventario['cantidad'] < $cantidadSolicitada) {
            throw new RuntimeException('No existe suficiente inventario para completar la venta. Actualiza tu carrito.');
        }
    }

    private function disminuirInventario(int $idInventario, int $cantidad): void
    {
        $sentencia = $this->db->consultar(
            'UPDATE inventario_partes
             SET cantidad = cantidad - :cantidad_resta
             WHERE id_inventario = :id AND cantidad >= :cantidad_min',
            ['cantidad_resta'=>$cantidad,'cantidad_min'=>$cantidad,'id'=>$idInventario]
        );
        if ($sentencia->rowCount() !== 1) {
            throw new RuntimeException('El inventario cambió durante la venta. La operación fue cancelada para evitar stock negativo.');
        }
    }
}
