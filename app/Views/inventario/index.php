<?php
use App\Core\Session;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;

$consulta = http_build_query([
    'buscar' => $busqueda ?? '',
    'estado' => $estado ?? '',
    'parte' => $idParte ?? 0,
    'auto' => $idAuto ?? 0,
    'seccion' => $idSeccion ?? 0,
]);
?>
<main class="container">
<section class="module-hero">
    <div><span class="eyebrow">Inventario</span><h1>Piezas automotrices</h1><p>Consulta por nombre, tipo de pieza, coche o categoría y controla existencias, imágenes y precio.</p></div>
    <div class="action-row">
        <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/dashboard')) ?>">Volver al inicio</a>
        <?php if (Session::tienePermiso('inventario.exportar')): ?><a class="btn btn-excel" href="<?= Sanitizer::html(Url::ruta('/inventario/reporte-excel') . '?' . $consulta) ?>"><img class="btn-icon" src="<?= Sanitizer::html(Url::asset('img/payment/excel-icon.svg')) ?>" alt=""> Exportar inventario</a><?php endif; ?>
        <?php if (Session::tienePermiso('inventario.gestionar')): ?><a class="btn" href="<?= Sanitizer::html(Url::ruta('/inventario/crear')) ?>">Registrar pieza</a><?php endif; ?>
    </div>
</section>
<section class="card">
<form class="filter-form inventory-filters" method="GET">
    <div class="form-group"><label>Buscar por nombre o texto</label><input name="buscar" value="<?= Sanitizer::html($busqueda ?? '') ?>" placeholder="Pieza, código, marca, modelo o sección"></div>
    <div class="form-group"><label>Tipo de parte</label><select name="parte"><option value="0">Todas</option><?php foreach(($partes ?? []) as $p): ?><option value="<?= (int)$p['id_parte'] ?>" <?= (int)($idParte ?? 0)===(int)$p['id_parte']?'selected':'' ?>><?= Sanitizer::html($p['nombre_parte']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Tipo de coche</label><select name="auto"><option value="0">Todos</option><?php foreach(($autos ?? []) as $a): ?><option value="<?= (int)$a['id_auto'] ?>" <?= (int)($idAuto ?? 0)===(int)$a['id_auto']?'selected':'' ?>><?= Sanitizer::html($a['marca'].' '.$a['modelo'].' '.$a['anio']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Categoría / sección</label><select name="seccion"><option value="0">Todas</option><?php foreach(($secciones ?? []) as $s): ?><option value="<?= (int)$s['id_seccion'] ?>" <?= (int)($idSeccion ?? 0)===(int)$s['id_seccion']?'selected':'' ?>><?= Sanitizer::html($s['nombre_seccion']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Estado</label><select name="estado"><option value="">Todos</option><option value="1" <?= ($estado ?? '')==='1'?'selected':'' ?>>Activos</option><option value="0" <?= ($estado ?? '')==='0'?'selected':'' ?>>Inactivos</option></select></div>
    <div class="form-actions compact"><button class="btn">Filtrar</button><a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/inventario')) ?>">Limpiar</a></div>
</form>
</section>
<section class="card table-wrapper"><table><thead><tr><th>Código</th><th>Pieza</th><th>Auto</th><th>Categoría</th><th>Precio</th><th>Cantidad</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php if(empty($piezas)): ?><tr><td colspan="8" class="empty-state">No hay piezas para los criterios seleccionados.</td></tr><?php endif; ?>
<?php foreach($piezas as $pieza): ?><tr>
<td><span class="chip"><?= Sanitizer::html($pieza['codigo_inventario']) ?></span></td>
<td><strong><?= Sanitizer::html($pieza['nombre_parte']) ?></strong><small><?= Sanitizer::html($pieza['descripcion_corta']) ?></small></td>
<td><?= Sanitizer::html($pieza['marca'].' '.$pieza['modelo'].' '.$pieza['anio']) ?></td>
<td><?= Sanitizer::html($pieza['nombre_seccion']) ?></td>
<td>$<?= Sanitizer::html(number_format((float)$pieza['precio'],2)) ?></td><td><?= (int)$pieza['cantidad'] ?></td>
<td><?= (int)$pieza['activo']===1?'<span class="badge badge-success">Activo</span>':'<span class="badge badge-danger">Inactivo</span>' ?></td>
<td class="acciones-tabla"><a class="btn btn-mini" href="<?= Sanitizer::html(Url::ruta('/inventario/ver/'.$pieza['id_inventario'])) ?>">Ver</a><?php if(Session::tienePermiso('inventario.gestionar')): ?><a class="btn btn-mini btn-secundario" href="<?= Sanitizer::html(Url::ruta('/inventario/editar/'.$pieza['id_inventario'])) ?>">Editar</a><form method="POST" action="<?= Sanitizer::html(Url::ruta('/inventario/estado/'.$pieza['id_inventario'])) ?>" data-confirm="¿Cambiar estado de la pieza?"><?= Csrf::campo() ?><input type="hidden" name="activo" value="<?= (int)$pieza['activo']===1?0:1 ?>"><button class="btn btn-mini <?= (int)$pieza['activo']===1?'btn-danger':'' ?>" type="submit"><?= (int)$pieza['activo']===1?'Desactivar':'Activar' ?></button></form><?php endif; ?></td>
</tr><?php endforeach; ?></tbody></table></section>
</main>
