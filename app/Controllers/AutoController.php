<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Models\Auto;
use Throwable;

/** Responsable: Franco. CRUD del catálogo de autos de origen. */
final class AutoController
{
    private Auto $autos;

    public function __construct()
    {
        $this->autos = new Auto();
    }

    public function index(): void
    {
        $this->exigirPermiso('autos.ver');
        $busqueda = Sanitizer::texto($_GET['buscar'] ?? '');
        $estado = $_GET['estado'] ?? '';
        View::renderizar('autos/index', [
            'titulo' => 'Autos',
            'autos' => $this->autos->listar($busqueda, $estado),
            'busqueda' => $busqueda,
            'estado' => $estado,
        ]);
    }

    public function crear(): void
    {
        $this->exigirPermiso('autos.gestionar');
        View::renderizar('autos/form', ['titulo' => 'Nuevo auto', 'auto' => null, 'errores' => []]);
    }

    public function guardar(): void
    {
        $this->exigirPermiso('autos.gestionar');
        if (!$this->csrfValido()) { Session::mensaje('error','Solicitud inválida.'); Url::redirigir('/autos/crear'); }
        $datos = $this->datosFormulario();
        $errores = $this->validar($datos);
        if ($errores !== []) { View::renderizar('autos/form', ['titulo'=>'Nuevo auto','auto'=>$datos,'errores'=>$errores]); return; }
        try { $this->autos->crear($datos); Session::mensaje('success','Auto registrado correctamente.'); Url::redirigir('/autos'); }
        catch (Throwable) { Session::mensaje('error','No se pudo guardar el auto.'); Url::redirigir('/autos/crear'); }
    }

    public function editar(string $id): void
    {
        $this->exigirPermiso('autos.gestionar');
        $auto = $this->autos->obtener((int)$id);
        if (!$auto) { Session::mensaje('error','El auto no existe.'); Url::redirigir('/autos'); }
        View::renderizar('autos/form', ['titulo'=>'Editar auto','auto'=>$auto,'errores'=>[]]);
    }

    public function actualizar(string $id): void
    {
        $this->exigirPermiso('autos.gestionar');
        $id=(int)$id;
        if (!$this->autos->obtener($id)) { Session::mensaje('error','El auto no existe.'); Url::redirigir('/autos'); }
        if (!$this->csrfValido()) { Session::mensaje('error','Solicitud inválida.'); Url::redirigir('/autos/editar/'.$id); }
        $datos=$this->datosFormulario();
        $errores=$this->validar($datos, $id);
        if ($errores !== []) { $datos['id_auto']=$id; View::renderizar('autos/form', ['titulo'=>'Editar auto','auto'=>$datos,'errores'=>$errores]); return; }
        try { $this->autos->actualizar($id,$datos); Session::mensaje('success','Auto actualizado correctamente.'); Url::redirigir('/autos'); }
        catch (Throwable) { Session::mensaje('error','No se pudo actualizar el auto.'); Url::redirigir('/autos/editar/'.$id); }
    }

    public function cambiarEstado(string $id): void
    {
        $this->exigirPermiso('autos.gestionar');
        $id=(int)$id;
        if (!$this->csrfValido()) { Session::mensaje('error','Solicitud inválida.'); Url::redirigir('/autos'); }
        $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 0;
        if ($activo === 0 && $this->autos->tieneInventarioAsociado($id)) {
            Session::mensaje('error','No se puede desactivar: el auto tiene piezas registradas en inventario.'); Url::redirigir('/autos');
        }
        $this->autos->cambiarEstado($id,$activo);
        Session::mensaje('success',$activo === 1 ? 'Auto activado.' : 'Auto desactivado.');
        Url::redirigir('/autos');
    }

    private function datosFormulario(): array
    {
        return [
            'marca'=>Sanitizer::texto($_POST['marca'] ?? ''),
            'modelo'=>Sanitizer::texto($_POST['modelo'] ?? ''),
            'anio'=>Sanitizer::entero($_POST['anio'] ?? 0),
            'descripcion'=>Sanitizer::texto($_POST['descripcion'] ?? ''),
            'activo'=>isset($_POST['activo']) ? 1 : 0,
        ];
    }

    private function validar(array $datos, ?int $idIgnorar=null): array
    {
        $errores=[];
        if (mb_strlen($datos['marca']) < 2) $errores[]='La marca debe tener al menos 2 caracteres.';
        if (mb_strlen($datos['modelo']) < 2) $errores[]='El modelo debe tener al menos 2 caracteres.';
        $anioActual=(int)date('Y')+1;
        if ($datos['anio'] < 1950 || $datos['anio'] > $anioActual) $errores[]='El año debe estar entre 1950 y '.$anioActual.'.';
        if ($this->autos->existe($datos['marca'],$datos['modelo'],$datos['anio'],$idIgnorar)) $errores[]='Ya existe un auto con esa marca, modelo y año.';
        return $errores;
    }

    private function csrfValido(): bool { return Csrf::validar($_POST['csrf_token'] ?? null); }
    private function exigirPermiso(string $permiso): void
    {
        if (!Session::estaAutenticado()) Url::redirigir('/login');
        if (!Session::tienePermiso($permiso)) { Session::mensaje('error','No tiene permisos para acceder a esta opción.'); Url::redirigir('/dashboard'); }
    }
}
