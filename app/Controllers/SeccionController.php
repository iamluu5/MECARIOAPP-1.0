<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Models\Seccion;
use Throwable;

/** Responsable: Franco. CRUD de secciones físicas del rastro. */
final class SeccionController
{
    private Seccion $secciones;
    public function __construct(){ $this->secciones = new Seccion(); }
    public function index(): void { $this->exigirPermiso('secciones.ver'); $busqueda=Sanitizer::texto($_GET['buscar'] ?? ''); $estado=$_GET['estado'] ?? ''; View::renderizar('secciones/index',['titulo'=>'Secciones','secciones'=>$this->secciones->listar($busqueda,$estado),'busqueda'=>$busqueda,'estado'=>$estado]); }
    public function crear(): void { $this->exigirPermiso('secciones.gestionar'); View::renderizar('secciones/form',['titulo'=>'Nueva sección','seccion'=>null,'errores'=>[]]); }
    public function guardar(): void { $this->exigirPermiso('secciones.gestionar'); if(!$this->csrfValido()){Session::mensaje('error','Solicitud inválida.');Url::redirigir('/secciones/crear');} $datos=$this->datosFormulario(); $errores=$this->validar($datos); if($errores!==[]){View::renderizar('secciones/form',['titulo'=>'Nueva sección','seccion'=>$datos,'errores'=>$errores]);return;} try{$this->secciones->crear($datos);Session::mensaje('success','Sección registrada correctamente.');Url::redirigir('/secciones');}catch(Throwable){Session::mensaje('error','No se pudo guardar la sección.');Url::redirigir('/secciones/crear');} }
    public function editar(string $id): void { $this->exigirPermiso('secciones.gestionar'); $seccion=$this->secciones->obtener((int)$id); if(!$seccion){Session::mensaje('error','La sección no existe.');Url::redirigir('/secciones');} View::renderizar('secciones/form',['titulo'=>'Editar sección','seccion'=>$seccion,'errores'=>[]]); }
    public function actualizar(string $id): void { $this->exigirPermiso('secciones.gestionar'); $id=(int)$id; if(!$this->secciones->obtener($id)){Session::mensaje('error','La sección no existe.');Url::redirigir('/secciones');} if(!$this->csrfValido()){Session::mensaje('error','Solicitud inválida.');Url::redirigir('/secciones/editar/'.$id);} $datos=$this->datosFormulario(); $errores=$this->validar($datos,$id); if($errores!==[]){$datos['id_seccion']=$id;View::renderizar('secciones/form',['titulo'=>'Editar sección','seccion'=>$datos,'errores'=>$errores]);return;} try{$this->secciones->actualizar($id,$datos);Session::mensaje('success','Sección actualizada correctamente.');Url::redirigir('/secciones');}catch(Throwable){Session::mensaje('error','No se pudo actualizar la sección.');Url::redirigir('/secciones/editar/'.$id);} }
    public function cambiarEstado(string $id): void { $this->exigirPermiso('secciones.gestionar'); $id=(int)$id; if(!$this->csrfValido()){Session::mensaje('error','Solicitud inválida.');Url::redirigir('/secciones');} $activo=isset($_POST['activo'])?(int)$_POST['activo']:0; if($activo===0 && $this->secciones->tieneInventarioAsociado($id)){Session::mensaje('error','No se puede desactivar: la sección está en uso dentro de inventario.');Url::redirigir('/secciones');} $this->secciones->cambiarEstado($id,$activo);Session::mensaje('success',$activo===1?'Sección activada.':'Sección desactivada.');Url::redirigir('/secciones'); }
    private function datosFormulario(): array { return ['codigo'=>strtoupper(Sanitizer::texto($_POST['codigo'] ?? '')),'nombre_seccion'=>Sanitizer::texto($_POST['nombre_seccion'] ?? ''),'descripcion'=>Sanitizer::texto($_POST['descripcion'] ?? ''),'activo'=>isset($_POST['activo'])?1:0]; }
    private function validar(array $datos,?int $idIgnorar=null): array { $e=[]; if(mb_strlen($datos['codigo'])<1) $e[]='El código es obligatorio.'; if(mb_strlen($datos['nombre_seccion'])<3) $e[]='El nombre de la sección debe tener al menos 3 caracteres.'; if($this->secciones->existeCodigo($datos['codigo'],$idIgnorar)) $e[]='Ya existe una sección con ese código.'; if($this->secciones->existeNombre($datos['nombre_seccion'],$idIgnorar)) $e[]='Ya existe una sección con ese nombre.'; return $e; }
    private function csrfValido(): bool { return Csrf::validar($_POST['csrf_token'] ?? null); }
    private function exigirPermiso(string $permiso): void { if(!Session::estaAutenticado()) Url::redirigir('/login'); if(!Session::tienePermiso($permiso)){Session::mensaje('error','No tiene permisos para acceder a esta opción.');Url::redirigir('/dashboard');} }
}
