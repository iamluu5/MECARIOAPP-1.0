<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Helpers\Sanitizer;
use App\Models\Comentario;

/**
 * Controlador de la página pública del rastro.
 *
 * Responsable: Joselyn.
 *
 * El controlador realiza únicamente consultas de lectura para construir la
 * vitrina pública. Las operaciones de escritura del inventario permanecen
 * aisladas en su módulo administrativo y protegidas por permisos.
 */
final class PublicController
{
    private Database $db;
    private Comentario $comentarios;

    public function __construct()
    {
        $this->db = Database::getInstancia();
        $this->comentarios = new Comentario();
    }

    /**
     * Página de inicio pública: presentación del rastro, bondades del
     * sistema y una muestra de piezas destacadas.
     */
    public function index(): void
    {
        View::renderizar('publico/index', [
            'titulo' => 'Inicio',
            'secciones' => $this->obtenerSecciones(),
            'destacados' => $this->obtenerCatalogo(limite: 6),
        ]);
    }

    /**
     * Catálogo completo con búsqueda por nombre de parte o por auto, y
     * filtro por sección/categoría.
     */
    public function catalogo(): void
    {
        $busqueda = Sanitizer::texto($_GET['q'] ?? '');
        $idSeccion = isset($_GET['seccion'])
            ? Sanitizer::entero($_GET['seccion'])
            : null;

        View::renderizar('publico/catalogo', [
            'titulo' => 'Catálogo de partes',
            'secciones' => $this->obtenerSecciones(),
            'resultados' => $this->obtenerCatalogo(
                busqueda: $busqueda,
                idSeccion: $idSeccion
            ),
            'busqueda' => $busqueda,
            'idSeccion' => $idSeccion,
        ]);
    }

    /**
     * Catálogo filtrado por una sección/categoría específica.
     */
    public function categoria(string $id): void
    {
        $idSeccion = Sanitizer::entero($id);

        View::renderizar('publico/catalogo', [
            'titulo' => 'Catálogo de partes',
            'secciones' => $this->obtenerSecciones(),
            'resultados' => $this->obtenerCatalogo(idSeccion: $idSeccion),
            'busqueda' => '',
            'idSeccion' => $idSeccion,
        ]);
    }

    /**
     * Detalle público de una pieza: costo, existencias y comentarios.
     */
    public function detalle(string $id): void
    {
        $idInventario = Sanitizer::entero($id);
        $parte = $this->obtenerParte($idInventario);

        if ($parte === null) {
            http_response_code(404);
            View::renderizar('errors/404', [
                'titulo' => 'Pieza no encontrada',
            ]);
            return;
        }

        View::renderizar('publico/detalle', [
            'titulo' => $parte['nombre_parte'],
            'parte' => $parte,
            'comentarios' => $this->comentarios->listarPublicados($idInventario),
        ]);
    }

    /**
     * Secciones activas, usadas como categorías del catálogo público.
     */
    private function obtenerSecciones(): array
    {
        return $this->db->consultarTodos(
            'SELECT id_seccion, codigo, nombre_seccion
                FROM secciones
                WHERE activo = 1
                ORDER BY nombre_seccion'
        );
    }

    /**
     * Piezas visibles al público: activas y con existencias.
     */
    private function obtenerCatalogo(
        string $busqueda = '',
        ?int $idSeccion = null,
        ?int $limite = null
    ): array {
        $condiciones = ['ip.activo = 1', 'ip.cantidad > 0'];
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones[] = '(
                p.nombre_parte LIKE :busqueda
                OR a.marca LIKE :busqueda
                OR a.modelo LIKE :busqueda
            )';
            $parametros['busqueda'] = '%' . $busqueda . '%';
        }

        if ($idSeccion !== null && $idSeccion > 0) {
            $condiciones[] = 'ip.id_seccion = :id_seccion';
            $parametros['id_seccion'] = $idSeccion;
        }

        $sql = 'SELECT
                ip.id_inventario,
                ip.descripcion_corta,
                ip.condicion_pieza,
                ip.precio,
                ip.cantidad,
                ip.thumbnail,
                ip.imagen_grande,
                a.marca,
                a.modelo,
                a.anio,
                p.nombre_parte,
                s.nombre_seccion
            FROM inventario_partes ip
            INNER JOIN autos a ON a.id_auto = ip.id_auto
            INNER JOIN partes p ON p.id_parte = ip.id_parte
            INNER JOIN secciones s ON s.id_seccion = ip.id_seccion
            WHERE ' . implode(' AND ', $condiciones) . '
            ORDER BY ip.fecha_creacion DESC';

        if ($limite !== null) {
            $sql .= ' LIMIT ' . $limite;
        }

        return $this->db->consultarTodos($sql, $parametros);
    }

    /**
     * Una sola pieza visible al público, con sus datos de auto y sección.
     */
    private function obtenerParte(int $idInventario): ?array
    {
        return $this->db->consultarUno(
            'SELECT
                ip.id_inventario,
                ip.descripcion_corta,
                ip.observaciones,
                ip.condicion_pieza,
                ip.precio,
                ip.cantidad,
                ip.thumbnail,
                ip.imagen_grande,
                a.marca,
                a.modelo,
                a.anio,
                p.nombre_parte,
                s.nombre_seccion
            FROM inventario_partes ip
            INNER JOIN autos a ON a.id_auto = ip.id_auto
            INNER JOIN partes p ON p.id_parte = ip.id_parte
            INNER JOIN secciones s ON s.id_seccion = ip.id_seccion
            WHERE ip.id_inventario = :id
                AND ip.activo = 1',
            ['id' => $idInventario]
        );
    }
}
