<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Modelo Usuario.
 *
 * Responsable: Luisa.
 *
 * Centraliza las consultas del módulo de usuarios y del login. Se respetan
 * los nombres reales de la base Mecario: id_usuario, id_rol, nombre_rol y
 * codigo para permisos. En los SELECT se usan alias cuando la vista necesita
 * nombres más cortos como id o nombre.
 */
final class Usuario
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstancia();
    }

    public function buscarParaLogin(string $usuario): ?array
    {
        return $this->db->consultarUno(
            'SELECT
                id_usuario,
                nombre,
                apellido,
                usuario,
                correo,
                password_hash,
                activo,
                bloqueado,
                intentos_fallidos,
                fecha_actualizacion,
                bloqueado_hasta
            FROM usuarios
            WHERE usuario = :usuario
            LIMIT 1',
            ['usuario' => $usuario]
        );
    }

    public function obtenerRoles(int $usuarioId): array
    {
        $filas = $this->db->consultarTodos(
            'SELECT r.nombre_rol
            FROM usuario_rol ur
            INNER JOIN roles r ON r.id_rol = ur.id_rol
            WHERE ur.id_usuario = :id_usuario
              AND r.activo = 1
            ORDER BY r.nombre_rol',
            ['id_usuario' => $usuarioId]
        );

        return array_column($filas, 'nombre_rol');
    }

    public function obtenerPermisos(int $usuarioId): array
    {
        $filas = $this->db->consultarTodos(
            'SELECT DISTINCT p.codigo
            FROM usuario_rol ur
            INNER JOIN roles r ON r.id_rol = ur.id_rol
            INNER JOIN rol_permiso rp ON rp.id_rol = r.id_rol
            INNER JOIN permisos p ON p.id_permiso = rp.id_permiso
            WHERE ur.id_usuario = :id_usuario
              AND r.activo = 1
              AND p.activo = 1
            ORDER BY p.codigo',
            ['id_usuario' => $usuarioId]
        );

        return array_column($filas, 'codigo');
    }

    public function registrarIntentoFallido(int $usuarioId, int $intentos): void
    {
        $this->db->ejecutar(
            'UPDATE usuarios
            SET intentos_fallidos = :intentos
            WHERE id_usuario = :id_usuario',
            ['intentos' => $intentos, 'id_usuario' => $usuarioId]
        );
    }

    public function incrementarIntentosFallidos(int $usuarioId): void
    {
        $this->db->ejecutar(
            'UPDATE usuarios
            SET intentos_fallidos = intentos_fallidos + 1
            WHERE id_usuario = :id_usuario',
            ['id_usuario' => $usuarioId]
        );
    }

    public function bloquear(int $usuarioId, int $minutos = 15): void
    {
        // MySQL no admite de forma consistente un placeholder PDO dentro de
        // INTERVAL. Se normaliza a entero antes de incorporarlo a la consulta.
        $minutos = max(1, min(1440, $minutos));
        $this->db->ejecutar(
            'UPDATE usuarios
             SET bloqueado = 1,
                 bloqueado_hasta = DATE_ADD(NOW(), INTERVAL ' . $minutos . ' MINUTE)
             WHERE id_usuario = :id_usuario',
            ['id_usuario' => $usuarioId]
        );
    }

    public function reiniciarIntentos(int $usuarioId): void
    {
        $this->db->ejecutar(
            'UPDATE usuarios
            SET intentos_fallidos = 0, bloqueado = 0, bloqueado_hasta = NULL
            WHERE id_usuario = :id_usuario',
            ['id_usuario' => $usuarioId]
        );
    }

    public function listar(string $busqueda = '', string $estado = ''): array
    {
        $sql = 'SELECT
                u.id_usuario AS id,
                u.nombre,
                u.apellido,
                CONCAT(u.nombre, " ", u.apellido) AS nombre_completo,
                u.usuario,
                u.correo,
                u.activo,
                u.bloqueado,
                u.intentos_fallidos,
                u.fecha_creacion,
                (
                    SELECT GROUP_CONCAT(r.nombre_rol ORDER BY r.nombre_rol SEPARATOR ", ")
                    FROM usuario_rol ur
                    INNER JOIN roles r ON r.id_rol = ur.id_rol
                    WHERE ur.id_usuario = u.id_usuario
                ) AS roles_texto
            FROM usuarios u
            WHERE 1 = 1';

        $params = [];

        if ($busqueda !== '') {
            $sql .= ' AND (
                u.nombre LIKE :buscar
                OR u.apellido LIKE :buscar
                OR u.usuario LIKE :buscar
                OR u.correo LIKE :buscar
            )';
            $params['buscar'] = '%' . $busqueda . '%';
        }

        if ($estado !== '') {
            $sql .= ' AND u.activo = :estado';
            $params['estado'] = (int) $estado;
        }

        $sql .= ' ORDER BY u.fecha_creacion DESC, u.nombre ASC';

        return $this->db->consultarTodos($sql, $params);
    }

    public function obtener(int $id): ?array
    {
        return $this->db->consultarUno(
            'SELECT
                u.id_usuario AS id,
                u.nombre,
                u.apellido,
                CONCAT(u.nombre, " ", u.apellido) AS nombre_completo,
                u.usuario,
                u.correo,
                u.password_hash,
                u.activo,
                u.bloqueado,
                u.intentos_fallidos,
                u.fecha_creacion,
                (
                    SELECT GROUP_CONCAT(r.nombre_rol ORDER BY r.nombre_rol SEPARATOR ", ")
                    FROM usuario_rol ur
                    INNER JOIN roles r ON r.id_rol = ur.id_rol
                    WHERE ur.id_usuario = u.id_usuario
                ) AS roles_texto
            FROM usuarios u
            WHERE u.id_usuario = :id_usuario
            LIMIT 1',
            ['id_usuario' => $id]
        );
    }

    public function crear(array $datos, array $roles): int
    {
        $idUsuario = $this->db->insertar(
            'INSERT INTO usuarios
                (nombre, apellido, usuario, correo, password_hash, activo, bloqueado, intentos_fallidos, fecha_creacion)
            VALUES
                (:nombre, :apellido, :usuario, :correo, :password_hash, :activo, 0, 0, NOW())',
            [
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'usuario' => $datos['usuario'],
                'correo' => $datos['correo'],
                'password_hash' => $datos['password_hash'],
                'activo' => (int) $datos['activo'],
            ]
        );

        $this->sincronizarRoles($idUsuario, $roles);
        return $idUsuario;
    }

    public function actualizar(int $id, array $datos, array $roles): void
    {
        $sql = 'UPDATE usuarios
            SET nombre = :nombre,
                apellido = :apellido,
                usuario = :usuario,
                correo = :correo,
                activo = :activo';

        $params = [
            'id_usuario' => $id,
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'usuario' => $datos['usuario'],
            'correo' => $datos['correo'],
            'activo' => (int) $datos['activo'],
        ];

        if (!empty($datos['password_hash'])) {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = $datos['password_hash'];
        }

        $sql .= ' WHERE id_usuario = :id_usuario';

        $this->db->ejecutar($sql, $params);
        $this->sincronizarRoles($id, $roles);
    }

    public function cambiarEstado(int $id, int $activo): void
    {
        $this->db->ejecutar(
            'UPDATE usuarios SET activo = :activo WHERE id_usuario = :id_usuario',
            ['activo' => $activo, 'id_usuario' => $id]
        );
    }

    public function desbloquear(int $id): void
    {
        $this->db->ejecutar(
            'UPDATE usuarios
            SET bloqueado = 0,
                intentos_fallidos = 0,
                bloqueado_hasta = NULL
            WHERE id_usuario = :id_usuario',
            ['id_usuario' => $id]
        );
    }


    public function obtenerRolIdPorNombre(string $nombreRol): ?int
    {
        $fila = $this->db->consultarUno(
            'SELECT id_rol FROM roles WHERE nombre_rol = :nombre AND activo = 1 LIMIT 1',
            ['nombre' => $nombreRol]
        );

        return $fila === null ? null : (int) $fila['id_rol'];
    }

    public function listarRolesActivos(): array
    {
        return $this->db->consultarTodos(
            'SELECT id_rol AS id, nombre_rol AS nombre, descripcion
            FROM roles
            WHERE activo = 1
            ORDER BY nombre_rol'
        );
    }

    /**
     * Roles disponibles para cuentas internas creadas por un administrador.
     * El rol Cliente se asigna exclusivamente desde el registro público.
     */
    public function listarRolesInternosActivos(): array
    {
        return $this->db->consultarTodos(
            'SELECT id_rol AS id, nombre_rol AS nombre, descripcion
            FROM roles
            WHERE activo = 1
              AND LOWER(nombre_rol) <> "cliente"
            ORDER BY nombre_rol'
        );
    }

    public function obtenerRolesUsuario(int $usuarioId): array
    {
        $filas = $this->db->consultarTodos(
            'SELECT id_rol FROM usuario_rol WHERE id_usuario = :id_usuario',
            ['id_usuario' => $usuarioId]
        );

        return array_map('intval', array_column($filas, 'id_rol'));
    }

    public function sincronizarRoles(int $usuarioId, array $roles): void
    {
        $this->db->ejecutar(
            'DELETE FROM usuario_rol WHERE id_usuario = :id_usuario',
            ['id_usuario' => $usuarioId]
        );

        foreach ($roles as $rolId) {
            $this->db->ejecutar(
                'INSERT INTO usuario_rol (id_usuario, id_rol)
                VALUES (:id_usuario, :id_rol)',
                ['id_usuario' => $usuarioId, 'id_rol' => (int) $rolId]
            );
        }
    }

    public function existeUsuario(string $usuario, ?int $idIgnorar = null): bool
    {
        $sql = 'SELECT id_usuario FROM usuarios WHERE usuario = :usuario';
        $params = ['usuario' => $usuario];

        if ($idIgnorar !== null) {
            $sql .= ' AND id_usuario <> :id_usuario';
            $params['id_usuario'] = $idIgnorar;
        }

        $sql .= ' LIMIT 1';
        return (bool) $this->db->consultarUno($sql, $params);
    }

    public function existeCorreo(string $correo, ?int $idIgnorar = null): bool
    {
        $sql = 'SELECT id_usuario FROM usuarios WHERE correo = :correo';
        $params = ['correo' => $correo];

        if ($idIgnorar !== null) {
            $sql .= ' AND id_usuario <> :id_usuario';
            $params['id_usuario'] = $idIgnorar;
        }

        $sql .= ' LIMIT 1';
        return (bool) $this->db->consultarUno($sql, $params);
    }


    public function obtenerPasswordHash(int $idUsuario): ?string
    {
        $fila = $this->db->consultarUno(
            'SELECT password_hash FROM usuarios WHERE id_usuario = :id LIMIT 1',
            ['id' => $idUsuario]
        );

        return $fila === null ? null : (string) $fila['password_hash'];
    }

    public function actualizarPassword(int $idUsuario, string $passwordHash): void
    {
        $this->db->ejecutar(
            'UPDATE usuarios SET password_hash = :hash WHERE id_usuario = :id',
            ['hash' => $passwordHash, 'id' => $idUsuario]
        );
    }

    public function estadisticas(): array
    {
        return [
            'total' => (int) ($this->db->consultarUno('SELECT COUNT(*) AS total FROM usuarios')['total'] ?? 0),
            'activos' => (int) ($this->db->consultarUno('SELECT COUNT(*) AS total FROM usuarios WHERE activo = 1')['total'] ?? 0),
            'inactivos' => (int) ($this->db->consultarUno('SELECT COUNT(*) AS total FROM usuarios WHERE activo = 0')['total'] ?? 0),
            'bloqueados' => (int) ($this->db->consultarUno('SELECT COUNT(*) AS total FROM usuarios WHERE bloqueado = 1')['total'] ?? 0),
        ];
    }
}
