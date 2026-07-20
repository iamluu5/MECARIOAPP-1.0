<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Responsable: Franco. Modelo de secciones físicas. */
final class Seccion
{
    private Database $db;
    public function __construct(){ $this->db=Database::getInstancia(); }
    public function listar(string $busqueda='', string $estado=''): array { $sql='SELECT id_seccion,codigo,nombre_seccion,descripcion,activo,fecha_creacion FROM secciones WHERE 1=1'; $p=[]; if($busqueda!==''){$sql.=' AND (codigo LIKE :b OR nombre_seccion LIKE :b)';$p['b']='%'.$busqueda.'%';} if($estado!==''){$sql.=' AND activo=:activo';$p['activo']=(int)$estado;} $sql.=' ORDER BY nombre_seccion'; return $this->db->consultarTodos($sql,$p); }
    public function obtener(int $id): ?array { return $this->db->consultarUno('SELECT * FROM secciones WHERE id_seccion=:id',['id'=>$id]); }
    public function crear(array $d): int { return $this->db->insertar('INSERT INTO secciones (codigo,nombre_seccion,descripcion,activo) VALUES (:codigo,:nombre,:descripcion,:activo)',['codigo'=>$d['codigo'],'nombre'=>$d['nombre_seccion'],'descripcion'=>$d['descripcion'] ?: null,'activo'=>(int)$d['activo']]); }
    public function actualizar(int $id,array $d): void { $this->db->ejecutar('UPDATE secciones SET codigo=:codigo,nombre_seccion=:nombre,descripcion=:descripcion,activo=:activo WHERE id_seccion=:id',['id'=>$id,'codigo'=>$d['codigo'],'nombre'=>$d['nombre_seccion'],'descripcion'=>$d['descripcion'] ?: null,'activo'=>(int)$d['activo']]); }
    public function cambiarEstado(int $id,int $activo): void { $this->db->ejecutar('UPDATE secciones SET activo=:activo WHERE id_seccion=:id',['id'=>$id,'activo'=>$activo]); }
    public function tieneInventarioAsociado(int $id): bool { return (bool)$this->db->consultarUno('SELECT id_inventario FROM inventario_partes WHERE id_seccion=:id LIMIT 1',['id'=>$id]); }
    public function existeCodigo(string $codigo,?int $idIgnorar=null): bool { $sql='SELECT id_seccion FROM secciones WHERE codigo=:codigo'; $p=['codigo'=>$codigo]; if($idIgnorar!==null){$sql.=' AND id_seccion<>:id';$p['id']=$idIgnorar;} $sql.=' LIMIT 1'; return (bool)$this->db->consultarUno($sql,$p); }
    public function existeNombre(string $nombre,?int $idIgnorar=null): bool { $sql='SELECT id_seccion FROM secciones WHERE nombre_seccion=:nombre'; $p=['nombre'=>$nombre]; if($idIgnorar!==null){$sql.=' AND id_seccion<>:id';$p['id']=$idIgnorar;} $sql.=' LIMIT 1'; return (bool)$this->db->consultarUno($sql,$p); }
}
