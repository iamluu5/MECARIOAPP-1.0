<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Helpers\Validator;
use App\Interfaces\AuthInterface;
use App\Models\Anomalia;
use App\Models\LoginLog;
use App\Models\Usuario;
use App\Services\KeyManager;
use App\Services\PasswordHashService;
use Throwable;

/**
 * Controlador de autenticación y registro público de clientes.
 *
 * El sistema conserva trazabilidad de los intentos de acceso y aplica un
 * bloqueo temporal después de tres contraseñas incorrectas. El bloqueo
 * temporal evita dejar la instalación inutilizable cuando solo existe el
 * usuario administrador inicial.
 */
final class AuthController implements AuthInterface
{
    private Usuario $usuarios;
    private LoginLog $loginLogs;
    private Anomalia $anomalias;
    private PasswordHashService $passwords;
    private KeyManager $keys;

    public function __construct()
    {
        $this->usuarios = new Usuario();
        $this->loginLogs = new LoginLog();
        $this->anomalias = new Anomalia();
        $this->passwords = new PasswordHashService();
        $this->keys = new KeyManager();
    }

    public function mostrarLogin(): void
    {
        if (Session::estaAutenticado()) {
            Url::redirigir('/dashboard');
        }

        View::renderizar('auth/login', [
            'titulo' => 'Iniciar sesión',
        ]);
    }

    public function mostrarRegistro(): void
    {
        if (Session::estaAutenticado()) {
            Url::redirigir('/dashboard');
        }

        View::renderizar('auth/registro', [
            'titulo' => 'Crear cuenta de cliente',
            'datos' => [
                'nombre' => '',
                'apellido' => '',
                'usuario' => '',
                'correo' => '',
            ],
            'errores' => [],
        ]);
    }

    public function registrarCliente(): void
    {
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'La solicitud no superó la validación de seguridad.');
            Url::redirigir('/registro');
        }

        $datos = [
            'nombre' => Sanitizer::texto($_POST['nombre'] ?? ''),
            'apellido' => Sanitizer::texto($_POST['apellido'] ?? ''),
            'usuario' => Sanitizer::texto($_POST['usuario'] ?? ''),
            'correo' => Sanitizer::correo($_POST['correo'] ?? ''),
            'password' => (string) ($_POST['password'] ?? ''),
            'password_confirmacion' => (string) ($_POST['password_confirmacion'] ?? ''),
            'activo' => 1,
        ];

        $errores = [];

        if (mb_strlen($datos['nombre']) < 2) {
            $errores[] = 'El nombre debe tener al menos 2 caracteres.';
        }

        if (mb_strlen($datos['apellido']) < 2) {
            $errores[] = 'El apellido debe tener al menos 2 caracteres.';
        }

        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $datos['usuario'])) {
            $errores[] = 'El usuario debe tener entre 3 y 50 caracteres y usar solo letras, números, punto, guion o guion bajo.';
        }

        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no tiene un formato válido.';
        }

        if (mb_strlen($datos['password']) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if ($datos['password'] !== $datos['password_confirmacion']) {
            $errores[] = 'Las contraseñas no coinciden.';
        }

        if ($this->usuarios->existeUsuario($datos['usuario'])) {
            $errores[] = 'Ese nombre de usuario ya está registrado.';
        }

        if ($this->usuarios->existeCorreo($datos['correo'])) {
            $errores[] = 'Ese correo electrónico ya está registrado.';
        }

        $rolCliente = $this->usuarios->obtenerRolIdPorNombre('Cliente');
        if ($rolCliente === null) {
            $errores[] = 'No está configurado el rol Cliente. Importe la base de datos actualizada.';
        }

        if ($errores !== []) {
            unset($datos['password'], $datos['password_confirmacion']);
            View::renderizar('auth/registro', [
                'titulo' => 'Crear cuenta de cliente',
                'datos' => $datos,
                'errores' => $errores,
            ]);
            return;
        }

        try {
            $datos['password_hash'] = $this->passwords->generarEvidencia($datos['password']);
            unset($datos['password'], $datos['password_confirmacion']);
            $this->usuarios->crear($datos, [(int) $rolCliente]);

            Session::mensaje('success', 'Cuenta creada correctamente. Ya puedes iniciar sesión.');
            Url::redirigir('/login');
        } catch (Throwable) {
            Session::mensaje('error', 'No se pudo crear la cuenta. Revisa los datos e intenta nuevamente.');
            Url::redirigir('/registro');
        }
    }

    public function iniciarSesion(): void
    {
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'La solicitud no superó la validación de seguridad.');
            Url::redirigir('/login');
        }

        $nombreUsuario = Sanitizer::texto($_POST['usuario'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $validador = new Validator();
        $validador
            ->requerido('usuario', $nombreUsuario)
            ->requerido('contraseña', $password);

        if (!$validador->esValido()) {
            Session::mensaje('error', $validador->primerError());
            Url::redirigir('/login');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'IP-desconocida';
        $usuario = $this->usuarios->buscarParaLogin($nombreUsuario);

        if ($usuario === null) {
            $this->loginLogs->registrar(null, $nombreUsuario, $ip, 'fallido', 'Credenciales inválidas.');
            Session::mensaje('error', 'Usuario o contraseña incorrectos.');
            Url::redirigir('/login');
        }

        $idUsuario = (int) $usuario['id_usuario'];

        if ((int) $usuario['activo'] !== 1) {
            $this->loginLogs->registrar($idUsuario, $nombreUsuario, $ip, 'inactivo', 'Intento de acceso con cuenta inactiva.');
            Session::mensaje('error', 'La cuenta está desactivada. Contacta al administrador.');
            Url::redirigir('/login');
        }

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $maximo = (int) $config['security']['max_login_attempts'];
        $minutosBloqueo = (int) $config['security']['lockout_minutes'];

        if ((int) $usuario['bloqueado'] === 1) {
            $bloqueadoHasta = $usuario['bloqueado_hasta'] ?? null;
            $desbloqueo = $bloqueadoHasta ? strtotime((string) $bloqueadoHasta) : false;

            if ($desbloqueo !== false && $desbloqueo <= time()) {
                $this->usuarios->desbloquear($idUsuario);
                $usuario['bloqueado'] = 0;
                $usuario['intentos_fallidos'] = 0;
            } else {
                $minutosRestantes = $desbloqueo !== false
                    ? max(1, (int) ceil(($desbloqueo - time()) / 60))
                    : $minutosBloqueo;
                $this->loginLogs->registrar($idUsuario, $nombreUsuario, $ip, 'bloqueado', 'Intento durante bloqueo temporal.');
                Session::mensaje('error', 'La cuenta está bloqueada temporalmente. Intenta nuevamente en aproximadamente ' . $minutosRestantes . ' minuto(s).');
                Url::redirigir('/login');
            }
        }

        if (!$this->passwords->verificarEvidencia($password, (string) $usuario['password_hash'])) {
            $intentos = min($maximo, (int) $usuario['intentos_fallidos'] + 1);
            $this->usuarios->registrarIntentoFallido($idUsuario, $intentos);

            $estado = 'fallido';
            $mensaje = 'Contraseña incorrecta.';

            if ($intentos >= $maximo) {
                $this->usuarios->bloquear($idUsuario, $minutosBloqueo);
                $estado = 'bloqueado';
                $mensaje = 'Cuenta bloqueada temporalmente por exceso de intentos.';
                $this->anomalias->registrar($idUsuario, 'Login', $mensaje, $ip, 'alta');
            }

            $this->loginLogs->registrar($idUsuario, $nombreUsuario, $ip, $estado, $mensaje);

            if ($estado === 'bloqueado') {
                Session::mensaje('error', 'Se alcanzaron tres intentos fallidos. La cuenta se bloqueó temporalmente por ' . $minutosBloqueo . ' minutos.');
            } else {
                $restantes = max(0, $maximo - $intentos);
                Session::mensaje('error', 'Usuario o contraseña incorrectos. Intentos restantes: ' . $restantes . '.');
            }

            Url::redirigir('/login');
        }

        $this->usuarios->reiniciarIntentos($idUsuario);
        $roles = $this->usuarios->obtenerRoles($idUsuario);
        $permisos = $this->usuarios->obtenerPermisos($idUsuario);

        // Las cuentas internas disponen de un par RSA para firmar acciones críticas.
        if (!in_array('Cliente', $roles, true)) {
            try {
                $this->keys->asegurarClave($idUsuario);
            } catch (Throwable $e) {
                $this->anomalias->registrar(
                    $idUsuario,
                    'Criptografía',
                    'No fue posible preparar la llave RSA del usuario: ' . $e->getMessage(),
                    $ip,
                    'alta'
                );
            }
        }

        Session::regenerar();
        Session::guardar('usuario', [
            'id_usuario' => $idUsuario,
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'usuario' => $usuario['usuario'],
            'correo' => $usuario['correo'],
            'roles' => $roles,
            'permisos' => $permisos,
        ]);

        Csrf::regenerar();
        $this->loginLogs->registrar($idUsuario, $nombreUsuario, $ip, 'exitoso', 'Inicio de sesión correcto.');
        Session::mensaje('success', 'Bienvenido al sistema.');
        Url::redirigir('/dashboard');
    }

    public function cerrarSesion(): void
    {
        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'No fue posible validar el cierre de sesión.');
            Url::redirigir('/dashboard');
        }

        Session::destruir();
        Url::redirigir('/login');
    }
}
