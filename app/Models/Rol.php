<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Modelo Rol.
 *
 * Responsable: Luisa.
 *
 * Administra roles y permisos usando los nombres reales del SQL:
 * id_rol, nombre_rol, id_permiso y codigo.
 */
final class Rol
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstancia();
    }

    public function listar(string $busqueda = '', string $estado = ''): array
    {
        $sql = 'SELECT
                r.id_rol AS id,
                r.nombre_rol AS nombre,
                r.descripcion,
                r.activo,
                r.fecha_creacion,
                (SELECT COUNT(*) FROM usuario_rol ur WHERE ur.id_rol = r.id_rol) AS total_usuarios,
                (SELECT COUNT(*) FROM rol_permiso rp WHERE rp.id_rol = r.id_rol) AS total_permisos,
                (
                    SELECT GROUP_CONCAT(p.codigo ORDER BY p.codigo SEPARATOR ", ")
                    FROM rol_permiso rp
                    INNER JOIN permisos p ON p.id_permiso = rp.id_permiso
                    WHERE rp.id_rol = r.id_rol
                ) AS permisos_texto
            FROM roles r
            WHERE 1 = 1';

        $params = [];

        if ($busqueda !== '') {
            $sql .= ' AND (r.nombre_rol LIKE :buscar OR r.descripcion LIKE :buscar)';
            $params['buscar'] = '%' . $busqueda . '%';
        }

        if ($estado !== '') {
            $sql .= ' AND r.activo = :estado';
            $params['estado'] = (int) $estado;
        }

        $sql .= ' ORDER BY r.nombre_rol ASC';
        return $this->db->consultarTodos($sql, $params);
    }

    public function obtener(int $id): ?array
    {
        return $this->db->consultarUno(
            'SELECT
                r.id_rol AS id,
                r.nombre_rol AS nombre,
                r.descripcion,
                r.activo,
                r.fecha_creacion,
                (SELECT COUNT(*) FROM usuario_rol ur WHERE ur.id_rol = r.id_rol) AS total_usuarios,
                (SELECT COUNT(*) FROM rol_permiso rp WHERE rp.id_rol = r.id_rol) AS total_permisos
            FROM roles r
            WHERE r.id_rol = :id_rol
            LIMIT 1',
            ['id_rol' => $id]
        );
    }

    public function crear(array $datos, array $permisos): int
    {
        $idRol = $this->db->insertar(
            'INSERT INTO roles (nombre_rol, descripcion, activo, fecha_creacion)
            VALUES (:nombre, :descripcion, :activo, NOW())',
            [
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'],
                'activo' => (int) $datos['activo'],
            ]
        );

        $this->sincronizarPermisos($idRol, $permisos);
        return $idRol;
    }

    public function actualizar(int $id, array $datos, array $permisos): void
    {
        $this->db->ejecutar(
            'UPDATE roles
            SET nombre_rol = :nombre,
                descripcion = :descripcion,
                activo = :activo
            WHERE id_rol = :id_rol',
            [
                'id_rol' => $id,
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'],
                'activo' => (int) $datos['activo'],
            ]
        );

        $this->sincronizarPermisos($id, $permisos);
    }

    public function cambiarEstado(int $id, int $activo): void
    {
        $this->db->ejecutar(
            'UPDATE roles SET activo = :activo WHERE id_rol = :id_rol',
            ['id_rol' => $id, 'activo' => $activo]
        );
    }

    public function listarPermisosActivos(): array
    {
        return $this->db->consultarTodos(
            'SELECT
                id_permiso AS id,
                codigo AS clave,
                codigo AS nombre,
                modulo,
                accion,
                descripcion
            FROM permisos
            WHERE activo = 1
            ORDER BY modulo, accion'
        );
    }

    public function obtenerPermisosRol(int $rolId): array
    {
        $filas = $this->db->consultarTodos(
            'SELECT id_permiso FROM rol_permiso WHERE id_rol = :id_rol',
            ['id_rol' => $rolId]
        );

        return array_map('intval', array_column($filas, 'id_permiso'));
    }

    public function obtenerPermisosDetalle(int $rolId): array
    {
        return $this->db->consultarTodos(
            'SELECT
                p.id_permiso AS id,
                p.codigo AS clave,
                p.codigo AS nombre,
                p.modulo,
                p.accion,
                p.descripcion
            FROM rol_permiso rp
            INNER JOIN permisos p ON p.id_permiso = rp.id_permiso
            WHERE rp.id_rol = :id_rol
            ORDER BY p.modulo, p.accion',
            ['id_rol' => $rolId]
        );
    }

    public function usuariosConRol(int $rolId): array
    {
        return $this->db->consultarTodos(
            'SELECT
                u.id_usuario AS id,
                u.nombre,
                u.apellido,
                CONCAT(u.nombre, " ", u.apellido) AS nombre_completo,
                u.usuario,
                u.correo,
                u.activo
            FROM usuario_rol ur
            INNER JOIN usuarios u ON u.id_usuario = ur.id_usuario
            WHERE ur.id_rol = :id_rol
            ORDER BY u.nombre, u.apellido',
            ['id_rol' => $rolId]
        );
    }

    public function sincronizarPermisos(int $rolId, array $permisos): void
    {
        $this->db->ejecutar('DELETE FROM rol_permiso WHERE id_rol = :id_rol', ['id_rol' => $rolId]);

        foreach ($permisos as $permisoId) {
            $this->db->ejecutar(
                'INSERT INTO rol_permiso (id_rol, id_permiso)
                VALUES (:id_rol, :id_permiso)',
                ['id_rol' => $rolId, 'id_permiso' => (int) $permisoId]
            );
        }
    }

    public function existeNombre(string $nombre, ?int $idIgnorar = null): bool
    {
        $sql = 'SELECT id_rol FROM roles WHERE nombre_rol = :nombre';
        $params = ['nombre' => $nombre];

        if ($idIgnorar !== null) {
            $sql .= ' AND id_rol <> :id_rol';
            $params['id_rol'] = $idIgnorar;
        }

        $sql .= ' LIMIT 1';
        return (bool) $this->db->consultarUno($sql, $params);
    }

    public function estadisticas(): array
    {
        return [
            'total' => (int) ($this->db->consultarUno('SELECT COUNT(*) AS total FROM roles')['total'] ?? 0),
            'activos' => (int) ($this->db->consultarUno('SELECT COUNT(*) AS total FROM roles WHERE activo = 1')['total'] ?? 0),
            'inactivos' => (int) ($this->db->consultarUno('SELECT COUNT(*) AS total FROM roles WHERE activo = 0')['total'] ?? 0),
            'permisos' => (int) ($this->db->consultarUno('SELECT COUNT(*) AS total FROM permisos WHERE activo = 1')['total'] ?? 0),
        ];
    }
}
