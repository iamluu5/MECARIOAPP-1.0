<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Interfaces\ComentarioInterface;

/**
 * MODELO: COMENTARIOS PÚBLICOS Y MODERACIÓN.
 *
 * Responsable: Joselyn.
 *
 * Reglas de negocio:
 * - Todo comentario nuevo ingresa con publicado = 0 (pendiente de revisión).
 * - Solo un comentario con publicado = 1 y activo = 1 aparece en la
 *   página pública.
 * - "Ocultar" no elimina el registro: solo regresa publicado a 0, para
 *   mantener trazabilidad de quién y cuándo moderó el comentario.
 */
final class Comentario implements ComentarioInterface
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstancia();
    }

    /**
     * Guarda un comentario enviado desde la página pública.
     *
     * Queda pendiente de aprobación (publicado = 0) hasta que un
     * moderador lo revise.
     */
    public function agregar(array $datos): bool
    {
        $this->db->insertar(
            'INSERT INTO comentarios
                (
                    id_inventario,
                    id_usuario,
                    nombre_visitante,
                    correo_visitante,
                    comentario,
                    ip,
                    publicado,
                    activo
                )
            VALUES
                (
                    :id_inventario,
                    :id_usuario,
                    :nombre_visitante,
                    :correo_visitante,
                    :comentario,
                    :ip,
                    0,
                    1
                )',
            [
                'id_inventario' => $datos['id_inventario'],
                'id_usuario' => $datos['id_usuario'] ?? null,
                'nombre_visitante' => $datos['nombre_visitante'],
                'correo_visitante' => $datos['correo_visitante'] !== ''
                    ? $datos['correo_visitante']
                    : null,
                'comentario' => $datos['comentario'],
                'ip' => $datos['ip'] ?? null,
            ]
        );

        return true;
    }

    /**
     * Publica un comentario pendiente y registra quién lo aprobó.
     */
    public function aprobar(int $idComentario, int $idModerador): bool
    {
        return $this->db->ejecutar(
            'UPDATE comentarios
                SET publicado = 1,
                    activo = 1,
                    moderado_por = :moderador,
                    fecha_moderacion = NOW()
                WHERE id_comentario = :id',
            [
                'moderador' => $idModerador,
                'id' => $idComentario,
            ]
        ) >= 0;
    }

    /**
     * Retira un comentario de la vista pública sin borrarlo del historial.
     */
    public function ocultar(int $idComentario, int $idModerador): bool
    {
        return $this->db->ejecutar(
            'UPDATE comentarios
                SET publicado = 0,
                    moderado_por = :moderador,
                    fecha_moderacion = NOW()
                WHERE id_comentario = :id',
            [
                'moderador' => $idModerador,
                'id' => $idComentario,
            ]
        ) >= 0;
    }

    /**
     * Eliminación lógica: conserva trazabilidad pero retira el comentario
     * de los flujos activos y de la página pública.
     */
    public function eliminar(int $idComentario, int $idModerador): bool
    {
        return $this->db->ejecutar(
            'UPDATE comentarios
                SET publicado = 0,
                    activo = 0,
                    moderado_por = :moderador,
                    fecha_moderacion = NOW()
                WHERE id_comentario = :id',
            [
                'moderador' => $idModerador,
                'id' => $idComentario,
            ]
        ) >= 0;
    }

    /**
     * Comentarios visibles para el público de una pieza específica.
     */
    public function listarPublicados(int $idInventario): array
    {
        return $this->db->consultarTodos(
            'SELECT
                id_comentario,
                nombre_visitante,
                comentario,
                fecha_comentario
            FROM comentarios
            WHERE id_inventario = :id_inventario
                AND publicado = 1
                AND activo = 1
            ORDER BY fecha_comentario DESC',
            ['id_inventario' => $idInventario]
        );
    }

    /**
     * Todos los comentarios para la pantalla de moderación, con el nombre
     * de la parte y el auto a los que pertenece cada uno.
     */
    public function listarParaModeracion(): array
    {
        return $this->db->consultarTodos(
            'SELECT
                c.id_comentario,
                c.id_inventario,
                c.nombre_visitante,
                c.correo_visitante,
                c.comentario,
                c.publicado,
                c.activo,
                c.fecha_comentario,
                p.nombre_parte,
                a.marca,
                a.modelo
            FROM comentarios c
            INNER JOIN inventario_partes ip
                ON ip.id_inventario = c.id_inventario
            INNER JOIN partes p ON p.id_parte = ip.id_parte
            INNER JOIN autos a ON a.id_auto = ip.id_auto
            ORDER BY
                (c.publicado = 0) DESC,
                c.fecha_comentario DESC'
        );
    }

    /**
     * Cantidad de comentarios pendientes de revisión.
     *
     * Se usa para mostrar un aviso rápido en la pantalla de moderación.
     */
    public function contarPendientes(): int
    {
        $fila = $this->db->consultarUno(
            'SELECT COUNT(*) AS total
                FROM comentarios
                WHERE publicado = 0 AND activo = 1'
        );

        return (int) ($fila['total'] ?? 0);
    }

    public function buscarPorId(int $idComentario): ?array
    {
        return $this->db->consultarUno(
            'SELECT * FROM comentarios WHERE id_comentario = :id',
            ['id' => $idComentario]
        );
    }
}
