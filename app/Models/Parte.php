<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Responsable: Franco. Modelo del catálogo de partes. */
final class Parte
{
    private Database $db;
    public function __construct(){ $this->db = Database::getInstancia(); }
    public function listar(string $busqueda='', string $estado=''): array { $sql='SELECT id_parte,nombre_parte,descripcion,activo,fecha_creacion FROM partes WHERE 1=1'; $p=[]; if($busqueda!==''){ $sql.=' AND nombre_parte LIKE :b'; $p['b']='%'.$busqueda.'%'; } if($estado!==''){ $sql.=' AND activo=:activo'; $p['activo']=(int)$estado; } $sql.=' ORDER BY nombre_parte'; return $this->db->consultarTodos($sql,$p); }
    public function obtener(int $id): ?array { return $this->db->consultarUno('SELECT * FROM partes WHERE id_parte=:id',['id'=>$id]); }
    public function crear(array $d): int { return $this->db->insertar('INSERT INTO partes (nombre_parte, descripcion, activo) VALUES (:nombre,:descripcion,:activo)', ['nombre'=>$d['nombre_parte'],'descripcion'=>$d['descripcion'] ?: null,'activo'=>(int)$d['activo']]); }
    public function actualizar(int $id,array $d): void { $this->db->ejecutar('UPDATE partes SET nombre_parte=:nombre, descripcion=:descripcion, activo=:activo WHERE id_parte=:id',['id'=>$id,'nombre'=>$d['nombre_parte'],'descripcion'=>$d['descripcion'] ?: null,'activo'=>(int)$d['activo']]); }
    public function cambiarEstado(int $id,int $activo): void { $this->db->ejecutar('UPDATE partes SET activo=:activo WHERE id_parte=:id',['id'=>$id,'activo'=>$activo]); }
    public function tieneInventarioAsociado(int $id): bool { return (bool)$this->db->consultarUno('SELECT id_inventario FROM inventario_partes WHERE id_parte=:id LIMIT 1',['id'=>$id]); }
    public function existe(string $nombre,?int $idIgnorar=null): bool { $sql='SELECT id_parte FROM partes WHERE nombre_parte=:nombre'; $p=['nombre'=>$nombre]; if($idIgnorar!==null){$sql.=' AND id_parte <> :id';$p['id']=$idIgnorar;} $sql.=' LIMIT 1'; return (bool)$this->db->consultarUno($sql,$p); }
}
