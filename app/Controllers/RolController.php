<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Models\Rol;
use Throwable;

/**
 * Controlador de roles y permisos.
 *
 * Responsable: Luisa.
 */
final class RolController
{
    private Rol $roles;

    public function __construct()
    {
        $this->roles = new Rol();
    }

    public function index(): void
    {
        $this->exigirPermiso('roles.ver');

        $busqueda = Sanitizer::texto($_GET['buscar'] ?? '');
        $estado = $_GET['estado'] ?? '';

        View::renderizar('roles/index', [
            'titulo' => 'Roles y permisos',
            'roles' => $this->roles->listar($busqueda, $estado),
            'estadisticas' => $this->roles->estadisticas(),
            'busqueda' => $busqueda,
            'estado' => $estado,
        ]);
    }

    public function crear(): void
    {
        $this->exigirPermiso('roles.gestionar');

        View::renderizar('roles/crear', [
            'titulo' => 'Crear rol',
            'rol' => ['id' => null, 'nombre' => '', 'descripcion' => '', 'activo' => 1],
            'permisos' => $this->roles->listarPermisosActivos(),
            'permisosSeleccionados' => [],
            'errores' => [],
        ]);
    }

    public function guardar(): void
    {
        $this->exigirPermiso('roles.gestionar');

        if (!$this->csrfValido()) {
            Session::mensaje('error', 'La solicitud no es válida.');
            Url::redirigir('/roles/crear');
        }

        $datos = $this->datosFormulario();
        $permisos = $this->permisosFormulario();
        $errores = $this->validarRol($datos, null);

        if ($errores !== []) {
            View::renderizar('roles/crear', [
                'titulo' => 'Crear rol',
                'rol' => $datos,
                'permisos' => $this->roles->listarPermisosActivos(),
                'permisosSeleccionados' => $permisos,
                'errores' => $errores,
            ]);
            return;
        }

        try {
            $idRol = $this->roles->crear($datos, $permisos);
            Session::mensaje('success', 'Rol creado correctamente.');
            Url::redirigir('/roles/ver/' . $idRol);
        } catch (Throwable) {
            Session::mensaje('error', 'No se pudo crear el rol.');
            Url::redirigir('/roles/crear');
        }
    }

    public function ver(string $id): void
    {
        $this->exigirPermiso('roles.ver');

        $rol = $this->roles->obtener((int) $id);
        if (!$rol) {
            Session::mensaje('error', 'El rol solicitado no existe.');
            Url::redirigir('/roles');
        }

        View::renderizar('roles/ver', [
            'titulo' => 'Detalle de rol',
            'rol' => $rol,
            'permisos' => $this->roles->obtenerPermisosDetalle((int) $id),
            'usuarios' => $this->roles->usuariosConRol((int) $id),
        ]);
    }

    public function editar(string $id): void
    {
        $this->exigirPermiso('roles.gestionar');

        $rol = $this->roles->obtener((int) $id);
        if (!$rol) {
            Session::mensaje('error', 'El rol solicitado no existe.');
            Url::redirigir('/roles');
        }

        View::renderizar('roles/editar', [
            'titulo' => 'Editar rol',
            'rol' => $rol,
            'permisos' => $this->roles->listarPermisosActivos(),
            'permisosSeleccionados' => $this->roles->obtenerPermisosRol((int) $id),
            'errores' => [],
        ]);
    }

    public function actualizar(string $id): void
    {
        $this->exigirPermiso('roles.gestionar');

        $id = (int) $id;
        $rolActual = $this->roles->obtener($id);

        if (!$rolActual) {
            Session::mensaje('error', 'El rol solicitado no existe.');
            Url::redirigir('/roles');
        }

        if (!$this->csrfValido()) {
            Session::mensaje('error', 'La solicitud no es válida.');
            Url::redirigir('/roles/editar/' . $id);
        }

        $datos = $this->datosFormulario();
        $permisos = $this->permisosFormulario();

        // El rol principal no puede perder su nombre, estado ni permisos,
        // porque eso podría dejar al sistema sin un usuario con control total.
        if (strtolower((string) $rolActual['nombre']) === 'administrador') {
            $datos['nombre'] = 'Administrador';
            $datos['activo'] = 1;
            $permisos = array_map(
                'intval',
                array_column($this->roles->listarPermisosActivos(), 'id')
            );
        }

        $errores = $this->validarRol($datos, $id);

        if ($errores !== []) {
            $datos['id'] = $id;
            View::renderizar('roles/editar', [
                'titulo' => 'Editar rol',
                'rol' => array_merge($rolActual, $datos),
                'permisos' => $this->roles->listarPermisosActivos(),
                'permisosSeleccionados' => $permisos,
                'errores' => $errores,
            ]);
            return;
        }

        try {
            $this->roles->actualizar($id, $datos, $permisos);
            Session::mensaje('success', 'Rol actualizado correctamente.');
            Url::redirigir('/roles/ver/' . $id);
        } catch (Throwable) {
            Session::mensaje('error', 'No se pudo actualizar el rol.');
            Url::redirigir('/roles/editar/' . $id);
        }
    }

    public function cambiarEstado(string $id): void
    {
        $this->exigirPermiso('roles.gestionar');

        $id = (int) $id;
        if (!$this->csrfValido()) {
            Session::mensaje('error', 'La solicitud no es válida.');
            Url::redirigir('/roles');
        }

        $rol = $this->roles->obtener($id);
        if (!$rol) {
            Session::mensaje('error', 'El rol no existe.');
            Url::redirigir('/roles');
        }

        $nuevoEstado = isset($_POST['activo']) ? (int) $_POST['activo'] : 0;
        if (strtolower((string) $rol['nombre']) === 'administrador' && $nuevoEstado === 0) {
            Session::mensaje('error', 'Por seguridad, no se puede desactivar el rol Administrador.');
            Url::redirigir('/roles');
        }

        $this->roles->cambiarEstado($id, $nuevoEstado);
        Session::mensaje('success', $nuevoEstado === 1 ? 'Rol activado correctamente.' : 'Rol desactivado correctamente.');
        Url::redirigir('/roles');
    }

    private function datosFormulario(): array
    {
        return [
            'nombre' => Sanitizer::texto($_POST['nombre'] ?? ''),
            'descripcion' => Sanitizer::texto($_POST['descripcion'] ?? ''),
            'activo' => isset($_POST['activo']) ? 1 : 0,
        ];
    }

    private function permisosFormulario(): array
    {
        $permisos = $_POST['permisos'] ?? [];
        return is_array($permisos) ? array_values(array_unique(array_map('intval', $permisos))) : [];
    }

    private function validarRol(array $datos, ?int $idIgnorar): array
    {
        $errores = [];

        if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) < 3) {
            $errores[] = 'El nombre del rol debe tener al menos 3 caracteres.';
        }

        if (mb_strlen($datos['nombre']) > 50) {
            $errores[] = 'El nombre del rol no puede superar los 50 caracteres.';
        }

        if (mb_strlen($datos['descripcion']) > 255) {
            $errores[] = 'La descripción no puede superar los 255 caracteres.';
        }

        if ($this->roles->existeNombre($datos['nombre'], $idIgnorar)) {
            $errores[] = 'Ya existe otro rol con ese nombre.';
        }

        return $errores;
    }

    private function csrfValido(): bool
    {
        return Csrf::validar($_POST['csrf_token'] ?? null);
    }

    private function exigirPermiso(string $permiso): void
    {
        if (!Session::estaAutenticado()) {
            Url::redirigir('/login');
        }

        if (!Session::tienePermiso($permiso)) {
            Session::mensaje('error', 'No tiene permisos para acceder a esta opción.');
            Url::redirigir('/dashboard');
        }
    }
}
