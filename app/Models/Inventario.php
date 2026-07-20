<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Modelo del inventario principal de autopartes. */
final class Inventario
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstancia();
    }

    /**
     * Consulta con filtros por texto/nombre, estado, tipo de parte y tipo de coche.
     * El filtro de auto representa marca + modelo + año mediante id_auto.
     */
    public function listar(
        string $busqueda = '',
        string $estado = '',
        int $idParte = 0,
        int $idAuto = 0,
        int $idSeccion = 0
    ): array {
        $sql = 'SELECT ip.*, a.marca, a.modelo, a.anio, p.nombre_parte,
                       s.codigo AS codigo_seccion, s.nombre_seccion
                FROM inventario_partes ip
                INNER JOIN autos a ON a.id_auto = ip.id_auto
                INNER JOIN partes p ON p.id_parte = ip.id_parte
                INNER JOIN secciones s ON s.id_seccion = ip.id_seccion
                WHERE 1=1';
        $params = [];

        if ($busqueda !== '') {
            $sql .= ' AND (
                ip.codigo_inventario LIKE :b
                OR ip.descripcion_corta LIKE :b
                OR a.marca LIKE :b
                OR a.modelo LIKE :b
                OR p.nombre_parte LIKE :b
                OR s.nombre_seccion LIKE :b
            )';
            $params['b'] = '%' . $busqueda . '%';
        }
        if ($estado !== '') {
            $sql .= ' AND ip.activo = :activo';
            $params['activo'] = (int)$estado;
        }
        if ($idParte > 0) {
            $sql .= ' AND ip.id_parte = :parte';
            $params['parte'] = $idParte;
        }
        if ($idAuto > 0) {
            $sql .= ' AND ip.id_auto = :auto';
            $params['auto'] = $idAuto;
        }
        if ($idSeccion > 0) {
            $sql .= ' AND ip.id_seccion = :seccion';
            $params['seccion'] = $idSeccion;
        }

        $sql .= ' ORDER BY ip.fecha_creacion DESC';
        return $this->db->consultarTodos($sql, $params);
    }

    public function obtener(int $id): ?array
    {
        return $this->db->consultarUno(
            'SELECT ip.*, a.marca, a.modelo, a.anio, p.nombre_parte,
                    s.codigo AS codigo_seccion, s.nombre_seccion
             FROM inventario_partes ip
             INNER JOIN autos a ON a.id_auto = ip.id_auto
             INNER JOIN partes p ON p.id_parte = ip.id_parte
             INNER JOIN secciones s ON s.id_seccion = ip.id_seccion
             WHERE ip.id_inventario = :id',
            ['id' => $id]
        );
    }

    public function autosActivos(): array
    {
        return $this->db->consultarTodos(
            'SELECT id_auto, marca, modelo, anio FROM autos
             WHERE activo=1 ORDER BY marca, modelo, anio DESC'
        );
    }

    public function partesActivas(): array
    {
        return $this->db->consultarTodos(
            'SELECT id_parte, nombre_parte FROM partes
             WHERE activo=1 ORDER BY nombre_parte'
        );
    }

    public function seccionesActivas(): array
    {
        return $this->db->consultarTodos(
            'SELECT id_seccion, codigo, nombre_seccion FROM secciones
             WHERE activo=1 ORDER BY nombre_seccion'
        );
    }

    public function crear(array $d): int
    {
        return $this->db->insertar(
            'INSERT INTO inventario_partes
                (id_auto,id_parte,id_seccion,creado_por,codigo_inventario,
                 descripcion_corta,observaciones,condicion_pieza,precio,cantidad,
                 thumbnail,imagen_grande,activo)
             VALUES
                (:auto,:parte,:seccion,:creado,:codigo,:descripcion,:observaciones,
                 :condicion,:precio,:cantidad,:thumbnail,:imagen_grande,:activo)',
            [
                'auto'=>$d['id_auto'], 'parte'=>$d['id_parte'], 'seccion'=>$d['id_seccion'],
                'creado'=>$d['creado_por'], 'codigo'=>$d['codigo_inventario'],
                'descripcion'=>$d['descripcion_corta'], 'observaciones'=>$d['observaciones'] ?: null,
                'condicion'=>$d['condicion_pieza'], 'precio'=>$d['precio'], 'cantidad'=>$d['cantidad'],
                'thumbnail'=>$d['thumbnail'] ?: null, 'imagen_grande'=>$d['imagen_grande'] ?: null,
                'activo'=>$d['activo'],
            ]
        );
    }

    public function actualizar(int $id, array $d): void
    {
        $this->db->ejecutar(
            'UPDATE inventario_partes
             SET id_auto=:auto,id_parte=:parte,id_seccion=:seccion,
                 codigo_inventario=:codigo,descripcion_corta=:descripcion,
                 observaciones=:observaciones,condicion_pieza=:condicion,
                 precio=:precio,cantidad=:cantidad,thumbnail=:thumbnail,
                 imagen_grande=:imagen_grande,activo=:activo
             WHERE id_inventario=:id',
            [
                'id'=>$id,'auto'=>$d['id_auto'],'parte'=>$d['id_parte'],'seccion'=>$d['id_seccion'],
                'codigo'=>$d['codigo_inventario'],'descripcion'=>$d['descripcion_corta'],
                'observaciones'=>$d['observaciones'] ?: null,'condicion'=>$d['condicion_pieza'],
                'precio'=>$d['precio'],'cantidad'=>$d['cantidad'],
                'thumbnail'=>$d['thumbnail'] ?: null,'imagen_grande'=>$d['imagen_grande'] ?: null,
                'activo'=>$d['activo'],
            ]
        );
    }

    public function cambiarEstado(int $id, int $activo): void
    {
        $this->db->ejecutar(
            'UPDATE inventario_partes SET activo=:activo WHERE id_inventario=:id',
            ['id'=>$id,'activo'=>$activo]
        );
    }

    public function existeCodigo(string $codigo, ?int $idIgnorar = null): bool
    {
        $sql = 'SELECT id_inventario FROM inventario_partes WHERE codigo_inventario=:codigo';
        $p = ['codigo'=>$codigo];
        if ($idIgnorar !== null) {
            $sql .= ' AND id_inventario <> :id';
            $p['id'] = $idIgnorar;
        }
        $sql .= ' LIMIT 1';
        return (bool)$this->db->consultarUno($sql, $p);
    }
}
