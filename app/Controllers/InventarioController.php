<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Helpers\ImageUploader;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
use App\Models\Inventario;
use App\Services\AuditTrailService;
use RuntimeException;
use Throwable;

/** Responsable: Franco. CRUD del inventario principal de piezas. */
final class InventarioController
{
    private Inventario $inventario;
    private AuditTrailService $auditoria;
    private array $condiciones = ['Excelente', 'Buena', 'Regular', 'Para reparar'];

    public function __construct(){ $this->inventario = new Inventario(); $this->auditoria = new AuditTrailService(); }

    public function index(): void
    {
        $this->exigirPermiso('inventario.ver');
        $busqueda = Sanitizer::texto($_GET['buscar'] ?? '');
        $estado = (string)($_GET['estado'] ?? '');
        $idParte = Sanitizer::entero($_GET['parte'] ?? 0);
        $idAuto = Sanitizer::entero($_GET['auto'] ?? 0);
        $idSeccion = Sanitizer::entero($_GET['seccion'] ?? 0);
        View::renderizar('inventario/index', [
            'titulo'=>'Inventario',
            'piezas'=>$this->inventario->listar($busqueda,$estado,$idParte,$idAuto,$idSeccion),
            'busqueda'=>$busqueda,'estado'=>$estado,'idParte'=>$idParte,'idAuto'=>$idAuto,'idSeccion'=>$idSeccion,
            'partes'=>$this->inventario->partesActivas(),'autos'=>$this->inventario->autosActivos(),'secciones'=>$this->inventario->seccionesActivas(),
        ]);
    }

    public function crear(): void
    {
        $this->exigirPermiso('inventario.gestionar');
        View::renderizar('inventario/form', $this->datosVistaFormulario('Nueva pieza', null, []));
    }

    public function guardar(): void
    {
        $this->exigirPermiso('inventario.gestionar');
        if(!$this->csrfValido()){Session::mensaje('error','Solicitud inválida.');Url::redirigir('/inventario/crear');}
        $datos=$this->datosFormulario();
        $errores=$this->validar($datos);
        if($errores!==[]){View::renderizar('inventario/form',$this->datosVistaFormulario('Nueva pieza',$datos,$errores));return;}
        try{
            $datos=$this->procesarImagenes($datos);
            $usuario=Session::usuario(); $datos['creado_por']=(int)($usuario['id_usuario'] ?? 1);
            $id=$this->inventario->crear($datos);
            $this->auditoriaActual('crear','inventario_partes',$id,['codigo'=>$datos['codigo_inventario'],'cantidad'=>$datos['cantidad'],'precio'=>$datos['precio']]);
            Session::mensaje('success','Pieza registrada correctamente.'); Url::redirigir('/inventario/ver/'.$id);
        }catch(Throwable $e){Session::mensaje('error',$e instanceof RuntimeException ? $e->getMessage() : 'No se pudo guardar la pieza.');Url::redirigir('/inventario/crear');}
    }

    public function ver(string $id): void
    {
        $this->exigirPermiso('inventario.ver');
        $pieza=$this->inventario->obtener((int)$id);
        if(!$pieza){Session::mensaje('error','La pieza no existe.');Url::redirigir('/inventario');}
        View::renderizar('inventario/ver',['titulo'=>'Detalle de pieza','pieza'=>$pieza]);
    }

    public function editar(string $id): void
    {
        $this->exigirPermiso('inventario.gestionar');
        $pieza=$this->inventario->obtener((int)$id);
        if(!$pieza){Session::mensaje('error','La pieza no existe.');Url::redirigir('/inventario');}
        View::renderizar('inventario/form',$this->datosVistaFormulario('Editar pieza',$pieza,[]));
    }

    public function actualizar(string $id): void
    {
        $this->exigirPermiso('inventario.gestionar');
        $id=(int)$id; $actual=$this->inventario->obtener($id);
        if(!$actual){Session::mensaje('error','La pieza no existe.');Url::redirigir('/inventario');}
        if(!$this->csrfValido()){Session::mensaje('error','Solicitud inválida.');Url::redirigir('/inventario/editar/'.$id);}
        $datos=$this->datosFormulario(); $datos['id_inventario']=$id;
        $errores=$this->validar($datos,$id);
        if($errores!==[]){View::renderizar('inventario/form',$this->datosVistaFormulario('Editar pieza',array_merge($actual,$datos),$errores));return;}
        try{
            $datos['thumbnail']=$actual['thumbnail']; $datos['imagen_grande']=$actual['imagen_grande'];
            $datos=$this->procesarImagenes($datos,$actual);
            $this->inventario->actualizar($id,$datos);
            $this->auditoriaActual('actualizar','inventario_partes',$id,['codigo'=>$datos['codigo_inventario'],'cantidad'=>$datos['cantidad'],'precio'=>$datos['precio']]);
            Session::mensaje('success','Pieza actualizada correctamente.'); Url::redirigir('/inventario/ver/'.$id);
        }catch(Throwable $e){Session::mensaje('error',$e instanceof RuntimeException ? $e->getMessage() : 'No se pudo actualizar la pieza.');Url::redirigir('/inventario/editar/'.$id);}
    }

    public function cambiarEstado(string $id): void
    {
        $this->exigirPermiso('inventario.gestionar');
        if(!$this->csrfValido()){Session::mensaje('error','Solicitud inválida.');Url::redirigir('/inventario');}
        $activo=isset($_POST['activo']) ? (int)$_POST['activo'] : 0;
        $this->inventario->cambiarEstado((int)$id,$activo);
        $this->auditoriaActual($activo===1?'activar':'desactivar','inventario_partes',(int)$id,['activo'=>$activo]);
        Session::mensaje('success',$activo===1?'Pieza activada.':'Pieza desactivada.'); Url::redirigir('/inventario');
    }

    public function exportarExcel(): void
    {
        $this->exigirPermiso('inventario.exportar');
        $busqueda = Sanitizer::texto($_GET['buscar'] ?? '');
        $estado = (string)($_GET['estado'] ?? '');
        $idParte = Sanitizer::entero($_GET['parte'] ?? 0);
        $idAuto = Sanitizer::entero($_GET['auto'] ?? 0);
        $idSeccion = Sanitizer::entero($_GET['seccion'] ?? 0);
        $filas = $this->inventario->listar($busqueda, $estado, $idParte, $idAuto, $idSeccion);

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="inventario_actual_' . date('Ymd_His') . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo "\xEF\xBB\xBF";
        echo '<table border="1"><tr><th colspan="10">INVENTARIO ACTUAL - MECARIO</th></tr>';
        echo '<tr><td>Generado</td><td>' . Sanitizer::html(date('Y-m-d H:i:s')) . '</td><td>Filtro</td><td colspan="7">' . Sanitizer::html($busqueda !== '' ? $busqueda : 'Todos') . '</td></tr></table><br>';
        echo '<table border="1"><tr><th>Código</th><th>Parte</th><th>Categoría</th><th>Marca</th><th>Modelo</th><th>Año</th><th>Precio</th><th>Existencia</th><th>Condición</th><th>Estado</th></tr>';
        foreach ($filas as $f) {
            echo '<tr><td>'.Sanitizer::html((string)$f['codigo_inventario']).'</td><td>'.Sanitizer::html((string)$f['nombre_parte']).'</td><td>'.Sanitizer::html((string)$f['nombre_seccion']).'</td><td>'.Sanitizer::html((string)$f['marca']).'</td><td>'.Sanitizer::html((string)$f['modelo']).'</td><td>'.Sanitizer::html((string)$f['anio']).'</td><td>'.Sanitizer::html(number_format((float)$f['precio'],2)).'</td><td>'.(int)$f['cantidad'].'</td><td>'.Sanitizer::html((string)$f['condicion_pieza']).'</td><td>'.((int)$f['activo']===1?'Activo':'Inactivo').'</td></tr>';
        }
        if ($filas === []) echo '<tr><td colspan="10">No hay resultados para los filtros seleccionados.</td></tr>';
        echo '</table>';
        exit;
    }

    private function datosFormulario(): array
    {
        return [
            'id_auto'=>Sanitizer::entero($_POST['id_auto'] ?? 0),'id_parte'=>Sanitizer::entero($_POST['id_parte'] ?? 0),'id_seccion'=>Sanitizer::entero($_POST['id_seccion'] ?? 0),
            'codigo_inventario'=>strtoupper(Sanitizer::texto($_POST['codigo_inventario'] ?? '')),'descripcion_corta'=>Sanitizer::texto($_POST['descripcion_corta'] ?? ''),'observaciones'=>Sanitizer::texto($_POST['observaciones'] ?? ''),
            'condicion_pieza'=>Sanitizer::texto($_POST['condicion_pieza'] ?? 'Buena'),'precio'=>Sanitizer::decimal($_POST['precio'] ?? 0),'cantidad'=>Sanitizer::entero($_POST['cantidad'] ?? 0),
            'thumbnail'=>'','imagen_grande'=>'','activo'=>isset($_POST['activo']) ? 1 : 0,
        ];
    }

    private function validar(array $d, ?int $idIgnorar=null): array
    {
        $e=[];
        if($d['id_auto']<=0)$e[]='Debe seleccionar un auto.'; if($d['id_parte']<=0)$e[]='Debe seleccionar una parte.'; if($d['id_seccion']<=0)$e[]='Debe seleccionar una sección.';
        if(mb_strlen($d['codigo_inventario'])<3)$e[]='El código de inventario debe tener al menos 3 caracteres.';
        if(mb_strlen($d['descripcion_corta'])<5)$e[]='La descripción corta debe tener al menos 5 caracteres.';
        if(!in_array($d['condicion_pieza'],$this->condiciones,true))$e[]='La condición seleccionada no es válida.';
        if($d['precio']<0)$e[]='El precio no puede ser negativo.'; if($d['cantidad']<0)$e[]='La cantidad no puede ser negativa.';
        if($this->inventario->existeCodigo($d['codigo_inventario'],$idIgnorar))$e[]='Ya existe una pieza con ese código de inventario.';
        return $e;
    }

    private function procesarImagenes(array $datos, ?array $actual=null): array
    {
        $thumb = ImageUploader::guardarThumbnail($_FILES['thumbnail'] ?? []);
        $grande = ImageUploader::guardarGrande($_FILES['imagen_grande'] ?? []);
        if($thumb !== null){ if($actual) ImageUploader::eliminar($actual['thumbnail'] ?? null); $datos['thumbnail']=$thumb; }
        if($grande !== null){ if($actual) ImageUploader::eliminar($actual['imagen_grande'] ?? null); $datos['imagen_grande']=$grande; }
        return $datos;
    }

    private function datosVistaFormulario(string $titulo, ?array $pieza, array $errores): array
    { return ['titulo'=>$titulo,'pieza'=>$pieza,'errores'=>$errores,'autos'=>$this->inventario->autosActivos(),'partes'=>$this->inventario->partesActivas(),'secciones'=>$this->inventario->seccionesActivas(),'condiciones'=>$this->condiciones]; }
    private function csrfValido(): bool { return Csrf::validar($_POST['csrf_token'] ?? null); }
    private function auditoriaActual(string $accion, string $entidad, int $entidadId, array $datos = []): void
    {
        $usuario = Session::usuario();
        $this->auditoria->registrarSeguro((int)($usuario['id_usuario'] ?? 0), 'Inventario', $accion, $entidad, $entidadId, $datos);
    }

    private function exigirPermiso(string $permiso): void { if(!Session::estaAutenticado()) Url::redirigir('/login'); if(!Session::tienePermiso($permiso)){Session::mensaje('error','No tiene permisos para acceder a esta opción.');Url::redirigir('/dashboard');} }
}
