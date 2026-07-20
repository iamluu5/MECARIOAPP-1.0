<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Responsable: Franco. Modelo del catálogo de autos. */
final class Auto
{
    private Database $db;
    public function __construct(){ $this->db = Database::getInstancia(); }

    public function listar(string $busqueda='', string $estado=''): array
    {
        $sql='SELECT id_auto, marca, modelo, anio, descripcion, activo, fecha_creacion FROM autos WHERE 1=1';
        $params=[];
        if ($busqueda !== '') { $sql.=' AND (marca LIKE :b OR modelo LIKE :b OR anio LIKE :b)'; $params['b']='%'.$busqueda.'%'; }
        if ($estado !== '') { $sql.=' AND activo = :activo'; $params['activo']=(int)$estado; }
        $sql.=' ORDER BY marca, modelo, anio DESC';
        return $this->db->consultarTodos($sql,$params);
    }
    public function obtener(int $id): ?array { return $this->db->consultarUno('SELECT * FROM autos WHERE id_auto = :id', ['id'=>$id]); }
    public function crear(array $d): int
    { return $this->db->insertar('INSERT INTO autos (marca, modelo, anio, descripcion, activo) VALUES (:marca,:modelo,:anio,:descripcion,:activo)', ['marca'=>$d['marca'],'modelo'=>$d['modelo'],'anio'=>$d['anio'],'descripcion'=>$d['descripcion'] ?: null,'activo'=>(int)$d['activo']]); }
    public function actualizar(int $id, array $d): void
    { $this->db->ejecutar('UPDATE autos SET marca=:marca, modelo=:modelo, anio=:anio, descripcion=:descripcion, activo=:activo WHERE id_auto=:id', ['id'=>$id,'marca'=>$d['marca'],'modelo'=>$d['modelo'],'anio'=>$d['anio'],'descripcion'=>$d['descripcion'] ?: null,'activo'=>(int)$d['activo']]); }
    public function cambiarEstado(int $id,int $activo): void { $this->db->ejecutar('UPDATE autos SET activo=:activo WHERE id_auto=:id', ['id'=>$id,'activo'=>$activo]); }
    public function tieneInventarioAsociado(int $id): bool { return (bool)$this->db->consultarUno('SELECT id_inventario FROM inventario_partes WHERE id_auto=:id LIMIT 1', ['id'=>$id]); }
    public function existe(string $marca,string $modelo,int $anio,?int $idIgnorar=null): bool
    { $sql='SELECT id_auto FROM autos WHERE marca=:marca AND modelo=:modelo AND anio=:anio'; $p=['marca'=>$marca,'modelo'=>$modelo,'anio'=>$anio]; if ($idIgnorar !== null) { $sql.=' AND id_auto <> :id'; $p['id']=$idIgnorar; } $sql.=' LIMIT 1'; return (bool)$this->db->consultarUno($sql,$p); }
}
