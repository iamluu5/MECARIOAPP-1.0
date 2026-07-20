<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Factura
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstancia();
    }

    public function obtenerPorVenta(int $idVenta): ?array
    {
        return $this->db->consultarUno(
            'SELECT * FROM facturas WHERE id_venta = :venta LIMIT 1',
            ['venta' => $idVenta]
        );
    }

    public function marcarGenerada(
        int $idVenta,
        string $rutaPdf,
        string $hash
    ): void {
        $this->db->ejecutar(
            'UPDATE facturas
             SET ruta_pdf = :ruta,
                 hash_pdf_sha256 = :hash,
                 huella_certificado_sha256 = NULL,
                 estado_firma = "pendiente",
                 fecha_firma = NULL
             WHERE id_venta = :venta',
            ['ruta'=>$rutaPdf,'hash'=>$hash,'venta'=>$idVenta]
        );
    }
}
