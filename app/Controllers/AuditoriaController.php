<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Models\Auditoria;
use App\Models\Usuario;
use App\Services\AuditTrailService;
use App\Services\KeyManager;
use Throwable;

/** Consulta de trazabilidad firmada y administración de rotación de llaves RSA. */
final class AuditoriaController
{
    private Auditoria $auditoria;
    private KeyManager $keys;
    private Usuario $usuarios;
    private AuditTrailService $trail;

    public function __construct()
    {
        $this->auditoria = new Auditoria();
        $this->keys = new KeyManager();
        $this->usuarios = new Usuario();
        $this->trail = new AuditTrailService();
    }

    public function index(): void
    {
        $this->exigirPermiso('auditoria.ver');
        $registros = $this->auditoria->listar(100);

        foreach ($registros as &$registro) {
            $registro['firma_valida'] = $this->auditoria->verificar($registro);
        }
        unset($registro);

        View::renderizar('auditoria/index', [
            'titulo' => 'Auditoría firmada',
            'registros' => $registros,
            'claves' => $this->keys->listarClaves(),
            'usuarios' => $this->usuarios->listar('', '1'),
        ]);
    }

    public function rotarClave(string $id): void
    {
        $this->exigirPermiso('auditoria.gestionar');

        if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
            Session::mensaje('error', 'La solicitud no superó la validación de seguridad.');
            Url::redirigir('/auditoria');
        }

        $idUsuario = Sanitizer::entero($id);
        if ($idUsuario <= 0 || !$this->usuarios->obtener($idUsuario)) {
            Session::mensaje('error', 'El usuario indicado no existe.');
            Url::redirigir('/auditoria');
        }

        try {
            $nueva = $this->keys->rotarClave($idUsuario);
            $actor = Session::usuario();
            $this->trail->registrarSeguro(
                (int) ($actor['id_usuario'] ?? 0),
                'Auditoría',
                'rotacion_clave_rsa',
                'claves_usuario',
                (int) $nueva['id_clave'],
                ['usuario_afectado' => $idUsuario, 'huella' => $nueva['huella_sha256']]
            );
            Session::mensaje('success', 'La llave RSA fue rotada. Las firmas históricas continúan siendo verificables con la llave anterior.');
        } catch (Throwable $e) {
            Session::mensaje('error', 'No fue posible rotar la llave RSA: ' . $e->getMessage());
        }

        Url::redirigir('/auditoria');
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
