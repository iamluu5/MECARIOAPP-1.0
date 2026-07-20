<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Models\Parte;
use Throwable;

/** Responsable: Franco. CRUD del catálogo de partes. */
final class ParteController
{
    private Parte $partes;
    public function __construct(){ $this->partes = new Parte(); }

    public function index(): void
    {
        $this->exigirPermiso('partes.ver');
        $busqueda=Sanitizer::texto($_GET['buscar'] ?? ''); $estado=$_GET['estado'] ?? '';
        View::renderizar('partes/index', ['titulo'=>'Partes','partes'=>$this->partes->listar($busqueda,$estado),'busqueda'=>$busqueda,'estado'=>$estado]);
    }
    public function crear(): void { $this->exigirPermiso('partes.gestionar'); View::renderizar('partes/form', ['titulo'=>'Nueva parte','parte'=>null,'errores'=>[]]); }
    public function guardar(): void
    {
        $this->exigirPermiso('partes.gestionar'); if (!$this->csrfValido()) { Session::mensaje('error','Solicitud inválida.'); Url::redirigir('/partes/crear'); }
        $datos=$this->datosFormulario(); $errores=$this->validar($datos);
        if ($errores !== []) { View::renderizar('partes/form',['titulo'=>'Nueva parte','parte'=>$datos,'errores'=>$errores]); return; }
        try { $this->partes->crear($datos); Session::mensaje('success','Parte registrada correctamente.'); Url::redirigir('/partes'); } catch (Throwable) { Session::mensaje('error','No se pudo guardar la parte.'); Url::redirigir('/partes/crear'); }
    }
    public function editar(string $id): void
    {
        $this->exigirPermiso('partes.gestionar'); $parte=$this->partes->obtener((int)$id); if (!$parte) { Session::mensaje('error','La parte no existe.'); Url::redirigir('/partes'); }
        View::renderizar('partes/form',['titulo'=>'Editar parte','parte'=>$parte,'errores'=>[]]);
    }
    public function actualizar(string $id): void
    {
        $this->exigirPermiso('partes.gestionar'); $id=(int)$id; if (!$this->partes->obtener($id)) { Session::mensaje('error','La parte no existe.'); Url::redirigir('/partes'); }
        if (!$this->csrfValido()) { Session::mensaje('error','Solicitud inválida.'); Url::redirigir('/partes/editar/'.$id); }
        $datos=$this->datosFormulario(); $errores=$this->validar($datos,$id); if ($errores !== []) { $datos['id_parte']=$id; View::renderizar('partes/form',['titulo'=>'Editar parte','parte'=>$datos,'errores'=>$errores]); return; }
        try { $this->partes->actualizar($id,$datos); Session::mensaje('success','Parte actualizada correctamente.'); Url::redirigir('/partes'); } catch (Throwable) { Session::mensaje('error','No se pudo actualizar la parte.'); Url::redirigir('/partes/editar/'.$id); }
    }
    public function cambiarEstado(string $id): void
    {
        $this->exigirPermiso('partes.gestionar'); $id=(int)$id; if (!$this->csrfValido()) { Session::mensaje('error','Solicitud inválida.'); Url::redirigir('/partes'); }
        $activo=isset($_POST['activo']) ? (int)$_POST['activo'] : 0;
        if ($activo===0 && $this->partes->tieneInventarioAsociado($id)) { Session::mensaje('error','No se puede desactivar: la parte está en uso dentro de inventario.'); Url::redirigir('/partes'); }
        $this->partes->cambiarEstado($id,$activo); Session::mensaje('success',$activo===1?'Parte activada.':'Parte desactivada.'); Url::redirigir('/partes');
    }
    private function datosFormulario(): array { return ['nombre_parte'=>Sanitizer::texto($_POST['nombre_parte'] ?? ''),'descripcion'=>Sanitizer::texto($_POST['descripcion'] ?? ''),'activo'=>isset($_POST['activo']) ? 1 : 0]; }
    private function validar(array $datos, ?int $idIgnorar=null): array { $e=[]; if (mb_strlen($datos['nombre_parte'])<2) $e[]='El nombre de la parte debe tener al menos 2 caracteres.'; if ($this->partes->existe($datos['nombre_parte'],$idIgnorar)) $e[]='Ya existe una parte con ese nombre.'; return $e; }
    private function csrfValido(): bool { return Csrf::validar($_POST['csrf_token'] ?? null); }
    private function exigirPermiso(string $permiso): void { if (!Session::estaAutenticado()) Url::redirigir('/login'); if (!Session::tienePermiso($permiso)) { Session::mensaje('error','No tiene permisos para acceder a esta opción.'); Url::redirigir('/dashboard'); } }
}
