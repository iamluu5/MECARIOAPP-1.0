<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Models\Usuario;
use App\Services\AuditTrailService;
use App\Services\KeyManager;
use App\Services\PasswordHashService;
use Throwable;

/**
 * Controlador del módulo de usuarios administrativos.
 *
 * Responsable: Luisa.
 *
 * Funcionalidades: listar, crear, editar, ver detalle, activar/desactivar,
 * asignar roles y desbloquear cuentas bloqueadas por intentos fallidos.
 */
final class UsuarioController
{
    private Usuario $usuarios;
    private PasswordHashService $passwords;
    private KeyManager $keys;
    private AuditTrailService $auditoria;

    public function __construct()
    {
        $this->usuarios = new Usuario();
        $this->passwords = new PasswordHashService();
        $this->keys = new KeyManager();
        $this->auditoria = new AuditTrailService();
    }

    public function index(): void
    {
        $this->exigirPermiso('usuarios.ver');

        $busqueda = Sanitizer::texto($_GET['buscar'] ?? '');
        $estado = $_GET['estado'] ?? '';

        View::renderizar('usuarios/index', [
            'titulo' => 'Gestión de usuarios',
            'usuarios' => $this->usuarios->listar($busqueda, $estado),
            'roles' => $this->usuarios->listarRolesActivos(),
            'estadisticas' => $this->usuarios->estadisticas(),
            'busqueda' => $busqueda,
            'estado' => $estado,
        ]);
    }

    public function crear(): void
    {
        $this->exigirPermiso('usuarios.crear');

        View::renderizar('usuarios/crear', [
            'titulo' => 'Crear usuario',
            'usuario' => [
                'id' => null,
                'nombre' => '',
                'apellido' => '',
                'usuario' => '',
                'correo' => '',
                'activo' => 1,
            ],
            'roles' => $this->usuarios->listarRolesInternosActivos(),
            'rolesSeleccionados' => [],
            'errores' => [],
        ]);
    }

    public function guardar(): void
    {
        $this->exigirPermiso('usuarios.crear');

        if (!$this->csrfValido()) {
            Session::mensaje('error', 'La solicitud no es válida. Intenta nuevamente.');
            Url::redirigir('/usuarios/crear');
        }

        $datos = $this->datosFormulario();
        $rolesSeleccionados = $this->rolesFormulario();

        // Las cuentas creadas desde el panel son cuentas internas. El rol
        // Cliente se reserva para el registro público y se descarta incluso
        // si alguien intenta enviarlo manualmente desde el navegador.
        $rolCliente = $this->usuarios->obtenerRolIdPorNombre('Cliente');
        if ($rolCliente !== null) {
            $rolesSeleccionados = array_values(array_filter(
                $rolesSeleccionados,
                static fn(int $rolId): bool => $rolId !== $rolCliente
            ));
        }

        $errores = $this->validarUsuario($datos, $rolesSeleccionados, true);

        if ($errores !== []) {
            View::renderizar('usuarios/crear', [
                'titulo' => 'Crear usuario',
                'usuario' => $datos,
                'roles' => $this->usuarios->listarRolesInternosActivos(),
                'rolesSeleccionados' => $rolesSeleccionados,
                'errores' => $errores,
            ]);
            return;
        }

        try {
            $datos['password_hash'] = $this->passwords->generarEvidencia($datos['password']);
            unset($datos['password']);

            $idUsuario = $this->usuarios->crear($datos, $rolesSeleccionados);
            // La cuenta no se pierde si OpenSSL no está disponible en ese
            // instante. La identidad RSA también se asegura en el primer login.
            try {
                $this->keys->asegurarClave($idUsuario);
            } catch (Throwable $e) {
                error_log('[MECARIO RSA] ' . $e->getMessage());
            }
            $actor = Session::usuario();
            $this->auditoria->registrarSeguro((int) ($actor['id_usuario'] ?? 0), 'Usuarios', 'crear', 'usuarios', $idUsuario, ['usuario' => $datos['usuario'], 'roles' => $rolesSeleccionados]);
            Session::mensaje('success', 'Usuario creado correctamente. Su identidad criptográfica RSA queda asociada automáticamente al iniciar sesión.');
            Url::redirigir('/usuarios/ver/' . $idUsuario);
        } catch (Throwable) {
            Session::mensaje('error', 'No se pudo crear el usuario. Revisa los datos e intenta nuevamente.');
            Url::redirigir('/usuarios/crear');
        }
    }

    public function ver(string $id): void
    {
        $this->exigirPermiso('usuarios.ver');

        $usuario = $this->usuarios->obtener((int) $id);

        if (!$usuario) {
            Session::mensaje('error', 'El usuario solicitado no existe.');
            Url::redirigir('/usuarios');
        }

        View::renderizar('usuarios/ver', [
            'titulo' => 'Detalle de usuario',
            'usuario' => $usuario,
            'rolesSeleccionados' => $this->usuarios->obtenerRolesUsuario((int) $id),
            'roles' => $this->usuarios->listarRolesActivos(),
        ]);
    }

    public function editar(string $id): void
    {
        $this->exigirPermiso('usuarios.editar');

        $usuario = $this->usuarios->obtener((int) $id);

        if (!$usuario) {
            Session::mensaje('error', 'El usuario solicitado no existe.');
            Url::redirigir('/usuarios');
        }

        View::renderizar('usuarios/editar', [
            'titulo' => 'Editar usuario',
            'usuario' => $usuario,
            'roles' => $this->usuarios->listarRolesActivos(),
            'rolesSeleccionados' => $this->usuarios->obtenerRolesUsuario((int) $id),
            'errores' => [],
        ]);
    }

    public function actualizar(string $id): void
    {
        $this->exigirPermiso('usuarios.editar');

        $id = (int) $id;
        $usuarioActual = $this->usuarios->obtener($id);

        if (!$usuarioActual) {
            Session::mensaje('error', 'El usuario solicitado no existe.');
            Url::redirigir('/usuarios');
        }

        if (!$this->csrfValido()) {
            Session::mensaje('error', 'La solicitud no es válida. Intenta nuevamente.');
            Url::redirigir('/usuarios/editar/' . $id);
        }

        $datos = $this->datosFormulario(false);
        $rolesSeleccionados = $this->rolesFormulario();

        // Protege la cuenta administrativa principal para evitar perder el
        // acceso total al sistema por una edición accidental.
        if (strtolower((string) $usuarioActual['usuario']) === 'admin') {
            $datos['usuario'] = 'admin';
            $datos['activo'] = 1;
            $rolAdministrador = $this->usuarios->obtenerRolIdPorNombre('Administrador');
            if ($rolAdministrador !== null && !in_array($rolAdministrador, $rolesSeleccionados, true)) {
                $rolesSeleccionados[] = $rolAdministrador;
            }
        }

        $errores = $this->validarUsuario($datos, $rolesSeleccionados, false, $id);

        if ($errores !== []) {
            $datos['id'] = $id;
            View::renderizar('usuarios/editar', [
                'titulo' => 'Editar usuario',
                'usuario' => array_merge($usuarioActual, $datos),
                'roles' => $this->usuarios->listarRolesActivos(),
                'rolesSeleccionados' => $rolesSeleccionados,
                'errores' => $errores,
            ]);
            return;
        }

        try {
            if ($datos['password'] !== '') {
                $datos['password_hash'] = $this->passwords->generarEvidencia($datos['password']);
            }
            unset($datos['password']);

            $this->usuarios->actualizar($id, $datos, $rolesSeleccionados);
            $actor = Session::usuario();
            $this->auditoria->registrarSeguro((int) ($actor['id_usuario'] ?? 0), 'Usuarios', 'actualizar', 'usuarios', $id, ['usuario' => $datos['usuario'], 'activo' => $datos['activo'], 'roles' => $rolesSeleccionados]);
            Session::mensaje('success', 'Usuario actualizado correctamente.');
            Url::redirigir('/usuarios/ver/' . $id);
        } catch (Throwable) {
            Session::mensaje('error', 'No se pudo actualizar el usuario.');
            Url::redirigir('/usuarios/editar/' . $id);
        }
    }

    public function cambiarEstado(string $id): void
    {
        $this->exigirPermiso('usuarios.estado');

        $id = (int) $id;
        if (!$this->csrfValido()) {
            Session::mensaje('error', 'La solicitud no es válida.');
            Url::redirigir('/usuarios');
        }

        $usuario = $this->usuarios->obtener($id);
        if (!$usuario) {
            Session::mensaje('error', 'El usuario no existe.');
            Url::redirigir('/usuarios');
        }

        $nuevoEstado = isset($_POST['activo']) ? (int) $_POST['activo'] : 0;

        if (strtolower((string) $usuario['usuario']) === 'admin' && $nuevoEstado === 0) {
            Session::mensaje('error', 'Por seguridad, no se puede desactivar el usuario principal admin.');
            Url::redirigir('/usuarios');
        }

        $this->usuarios->cambiarEstado($id, $nuevoEstado);
        $actor = Session::usuario();
        $this->auditoria->registrarSeguro((int) ($actor['id_usuario'] ?? 0), 'Usuarios', $nuevoEstado === 1 ? 'activar' : 'deshabilitar', 'usuarios', $id, ['activo' => $nuevoEstado]);
        Session::mensaje('success', $nuevoEstado === 1 ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.');
        Url::redirigir('/usuarios');
    }

    public function desbloquear(string $id): void
    {
        $this->exigirPermiso('usuarios.estado');

        $id = (int) $id;
        if (!$this->csrfValido()) {
            Session::mensaje('error', 'La solicitud no es válida.');
            Url::redirigir('/usuarios');
        }

        if (!$this->usuarios->obtener($id)) {
            Session::mensaje('error', 'El usuario no existe.');
            Url::redirigir('/usuarios');
        }

        $this->usuarios->desbloquear($id);
        $actor = Session::usuario();
        $this->auditoria->registrarSeguro((int) ($actor['id_usuario'] ?? 0), 'Usuarios', 'desbloquear', 'usuarios', $id);
        Session::mensaje('success', 'Usuario desbloqueado correctamente.');
        Url::redirigir('/usuarios/ver/' . $id);
    }

    private function datosFormulario(bool $crear = true): array
    {
        return [
            'nombre' => Sanitizer::texto($_POST['nombre'] ?? ''),
            'apellido' => Sanitizer::texto($_POST['apellido'] ?? ''),
            'usuario' => Sanitizer::texto($_POST['usuario'] ?? ''),
            'correo' => Sanitizer::correo($_POST['correo'] ?? ''),
            'password' => (string) ($_POST['password'] ?? ''),
            'activo' => isset($_POST['activo']) ? 1 : 0,
        ];
    }

    private function rolesFormulario(): array
    {
        $roles = $_POST['roles'] ?? [];
        return is_array($roles) ? array_values(array_unique(array_map('intval', $roles))) : [];
    }

    private function validarUsuario(array $datos, array $roles, bool $crear, ?int $idIgnorar = null): array
    {
        $errores = [];

        if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) < 2) {
            $errores[] = 'El nombre debe tener al menos 2 caracteres.';
        }

        if ($datos['apellido'] === '' || mb_strlen($datos['apellido']) < 2) {
            $errores[] = 'El apellido debe tener al menos 2 caracteres.';
        }

        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $datos['usuario'])) {
            $errores[] = 'El usuario debe tener entre 3 y 50 caracteres. Solo se permiten letras, números, punto, guion y guion bajo.';
        }

        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no tiene un formato válido.';
        }

        if ($crear && mb_strlen($datos['password']) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if (!$crear && $datos['password'] !== '' && mb_strlen($datos['password']) < 8) {
            $errores[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
        }

        if ($roles === []) {
            $errores[] = 'Debes asignar al menos un rol al usuario.';
        }

        if ($this->usuarios->existeUsuario($datos['usuario'], $idIgnorar)) {
            $errores[] = 'Ya existe otro usuario con ese nombre de usuario.';
        }

        if ($this->usuarios->existeCorreo($datos['correo'], $idIgnorar)) {
            $errores[] = 'Ya existe otro usuario con ese correo electrónico.';
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
