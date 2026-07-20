<?php

use App\Core\Session;
use App\Helpers\Sanitizer;
use App\Helpers\Url;

$consultaReporte = http_build_query(['desde' => $desde ?? '', 'hasta' => $hasta ?? '']);
$graficaDatos = $grafica ?? [];
$maxGrafica = 1.0;
foreach ($graficaDatos as $punto) {
    $maxGrafica = max($maxGrafica, (float) $punto['total']);
}

// Coordenadas de una línea SVG responsiva para los últimos siete días.
$chartWidth = 760;
$chartHeight = 250;
$padX = 48;
$padTop = 28;
$padBottom = 46;
$usableWidth = $chartWidth - ($padX * 2);
$usableHeight = $chartHeight - $padTop - $padBottom;
$points = [];
$areaPoints = [];
$countPoints = max(1, count($graficaDatos) - 1);
foreach ($graficaDatos as $i => $punto) {
    $x = $padX + ($usableWidth * ($i / $countPoints));
    $ratio = $maxGrafica > 0 ? ((float) $punto['total'] / $maxGrafica) : 0;
    $y = $padTop + $usableHeight - ($ratio * $usableHeight);
    $points[] = [
        'x' => round($x, 2),
        'y' => round($y, 2),
        'total' => (float) $punto['total'],
        'fecha' => (string) $punto['fecha'],
    ];
}
$polyline = implode(' ', array_map(static fn(array $p): string => $p['x'] . ',' . $p['y'], $points));
if ($points !== []) {
    $areaPoints = $padX . ',' . ($padTop + $usableHeight) . ' ' . $polyline . ' ' . ($padX + $usableWidth) . ',' . ($padTop + $usableHeight);
}

$metodos = $metodosPago ?? [];
$totalMetodos = array_sum(array_map(static fn(array $fila): float => (float) $fila['total'], $metodos));
$palette = ['#e7aa2f', '#232323', '#64748b', '#f1c75b'];
$segments = [];
$start = 0.0;
foreach ($metodos as $i => $fila) {
    $percentage = $totalMetodos > 0 ? ((float) $fila['total'] / $totalMetodos) * 100 : 0;
    if ($percentage > 0) {
        $end = $start + $percentage;
        $segments[] = $palette[$i % count($palette)] . ' ' . number_format($start, 2, '.', '') . '% ' . number_format($end, 2, '.', '') . '%';
        $start = $end;
    }
}
$donutStyle = $segments !== [] ? 'conic-gradient(' . implode(', ', $segments) . ')' : 'conic-gradient(#e2e8f0 0 100%)';

// Gráfica mensual solicitada por la rúbrica.
$mensuales = $ventasMensuales ?? [];
$maxMensual = 1.0;
foreach ($mensuales as $fila) {
    $maxMensual = max($maxMensual, (float) $fila['total']);
}
$monthWidth = 760;
$monthHeight = 235;
$monthPadX = 52;
$monthPadTop = 26;
$monthPadBottom = 48;
$monthUsableW = $monthWidth - ($monthPadX * 2);
$monthUsableH = $monthHeight - $monthPadTop - $monthPadBottom;
$monthPoints = [];
$monthCount = max(1, count($mensuales) - 1);
foreach ($mensuales as $i => $fila) {
    $x = $monthPadX + ($monthUsableW * ($i / $monthCount));
    $ratio = $maxMensual > 0 ? ((float) $fila['total'] / $maxMensual) : 0;
    $y = $monthPadTop + $monthUsableH - ($ratio * $monthUsableH);
    $monthPoints[] = [
        'x' => round($x, 2),
        'y' => round($y, 2),
        'total' => (float) $fila['total'],
        'cantidad' => (int) $fila['cantidad_ventas'],
        'periodo' => (string) $fila['periodo'],
    ];
}
$monthPolyline = implode(' ', array_map(static fn(array $p): string => $p['x'] . ',' . $p['y'], $monthPoints));
$categorias = $totalesCategoria ?? [];
$maxCategoria = 1.0;
foreach ($categorias as $fila) {
    $maxCategoria = max($maxCategoria, (float) $fila['total']);
}
$topPartes = $partesMasVendidas ?? [];
?>
<main class="container">
    <section class="module-hero">
        <div><span class="eyebrow">Ventas</span><h1>Ventas registradas</h1><p>Consulta las ventas, analiza el movimiento reciente y descarga el reporte consolidado.</p></div>
        <div class="action-row">
            <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/dashboard')) ?>">Volver al inicio</a>
            <?php if (Session::tienePermiso('ventas.exportar')): ?><a class="btn btn-excel" href="<?= Sanitizer::html(Url::ruta('/ventas/reporte-excel') . ($consultaReporte !== '' ? '?' . $consultaReporte : '')) ?>"><img class="btn-icon" src="<?= Sanitizer::html(Url::asset('img/payment/excel-icon.svg')) ?>" alt=""> Descargar reporte Excel</a><?php endif; ?>
            <?php if (Session::tienePermiso('ventas.crear')): ?><a class="btn" href="<?= Sanitizer::html(Url::ruta('/ventas/crear')) ?>">Nueva venta</a><?php endif; ?>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card"><strong><?= (int)($estadisticas['ventas_hoy'] ?? 0) ?></strong><span>Ventas hoy</span></article>
        <article class="stat-card"><strong>$<?= Sanitizer::html(number_format((float)($estadisticas['ingresos_hoy'] ?? 0),2)) ?></strong><span>Ingresos hoy</span></article>
        <article class="stat-card"><strong><?= (int)($estadisticas['ventas_7_dias'] ?? 0) ?></strong><span>Ventas últimos 7 días</span></article>
        <article class="stat-card"><strong>$<?= Sanitizer::html(number_format((float)($estadisticas['ingresos_7_dias'] ?? 0),2)) ?></strong><span>Ingresos últimos 7 días</span></article>
    </section>

    <section class="analytics-grid">
        <article class="card sales-chart-card line-chart-card">
            <div class="card-top"><div><span class="eyebrow">Tendencia</span><h2>Ventas de los últimos 7 días</h2></div><span class="muted">Monto total por día</span></div>
            <div class="line-chart-wrap">
                <svg class="sales-line-chart" viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" role="img" aria-label="Gráfica de línea de ventas de los últimos siete días">
                    <?php for ($grid = 0; $grid <= 4; $grid++):
                        $gy = $padTop + ($usableHeight * ($grid / 4));
                        $value = $maxGrafica * (1 - ($grid / 4));
                    ?>
                        <line class="chart-grid-line" x1="<?= $padX ?>" y1="<?= $gy ?>" x2="<?= $chartWidth - $padX ?>" y2="<?= $gy ?>"></line>
                        <text class="chart-y-label" x="<?= $padX - 8 ?>" y="<?= $gy + 4 ?>" text-anchor="end">$<?= Sanitizer::html(number_format($value, 0)) ?></text>
                    <?php endfor; ?>
                    <?php if ($areaPoints !== []): ?><polygon class="chart-area" points="<?= Sanitizer::html((string) $areaPoints) ?>"></polygon><?php endif; ?>
                    <?php if ($polyline !== ''): ?><polyline class="chart-line" points="<?= Sanitizer::html($polyline) ?>"></polyline><?php endif; ?>
                    <?php foreach ($points as $point): ?>
                        <g class="chart-point-group">
                            <circle class="chart-point" cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="5"><title><?= Sanitizer::html((new DateTimeImmutable($point['fecha']))->format('d/m/Y')) ?>: $<?= Sanitizer::html(number_format($point['total'], 2)) ?></title></circle>
                            <?php if ($point['total'] > 0): ?><text class="chart-value-label" x="<?= $point['x'] ?>" y="<?= max(15, $point['y'] - 12) ?>" text-anchor="middle">$<?= Sanitizer::html(number_format($point['total'], 0)) ?></text><?php endif; ?>
                            <text class="chart-x-label" x="<?= $point['x'] ?>" y="<?= $chartHeight - 14 ?>" text-anchor="middle"><?= Sanitizer::html((new DateTimeImmutable($point['fecha']))->format('d/m')) ?></text>
                        </g>
                    <?php endforeach; ?>
                </svg>
            </div>
        </article>

        <article class="card payment-chart-card">
            <div class="card-top"><div><span class="eyebrow">Distribución</span><h2>Ventas por método de pago</h2></div><span class="muted">Monto acumulado</span></div>
            <div class="payment-chart-content">
                <div class="donut-chart" style="background: <?= Sanitizer::html($donutStyle) ?>">
                    <div class="donut-center"><span>Total vendido</span><strong>$<?= Sanitizer::html(number_format((float) $totalMetodos, 2)) ?></strong></div>
                </div>
                <div class="chart-legend">
                    <?php if ($metodos === []): ?><p class="muted">Aún no hay ventas registradas.</p><?php endif; ?>
                    <?php foreach ($metodos as $i => $fila): ?>
                        <div class="legend-row">
                            <span class="legend-dot" style="background: <?= Sanitizer::html($palette[$i % count($palette)]) ?>"></span>
                            <div><strong><?= Sanitizer::html((string) $fila['metodo_pago']) ?></strong><small><?= (int) $fila['cantidad_ventas'] ?> venta<?= (int) $fila['cantidad_ventas'] === 1 ? '' : 's' ?></small></div>
                            <b>$<?= Sanitizer::html(number_format((float) $fila['total'], 2)) ?></b>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
    </section>

    <section class="analytics-grid analytics-grid-wide">
        <article class="card sales-chart-card line-chart-card">
            <div class="card-top"><div><span class="eyebrow">Estadística mensual</span><h2>Ventas por mes</h2></div><span class="muted">Últimos <?= count($mensuales) ?> meses</span></div>
            <div class="line-chart-wrap">
                <svg class="sales-line-chart" viewBox="0 0 <?= $monthWidth ?> <?= $monthHeight ?>" role="img" aria-label="Gráfica de ventas mensuales">
                    <?php for ($grid = 0; $grid <= 4; $grid++): $gy = $monthPadTop + ($monthUsableH * ($grid / 4)); $value = $maxMensual * (1 - ($grid / 4)); ?>
                        <line class="chart-grid-line" x1="<?= $monthPadX ?>" y1="<?= $gy ?>" x2="<?= $monthWidth - $monthPadX ?>" y2="<?= $gy ?>"></line>
                        <text class="chart-y-label" x="<?= $monthPadX - 8 ?>" y="<?= $gy + 4 ?>" text-anchor="end">$<?= Sanitizer::html(number_format($value, 0)) ?></text>
                    <?php endfor; ?>
                    <?php if ($monthPolyline !== ''): ?><polyline class="chart-line" points="<?= Sanitizer::html($monthPolyline) ?>"></polyline><?php endif; ?>
                    <?php foreach ($monthPoints as $point): ?>
                        <g class="chart-point-group">
                            <circle class="chart-point" cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="5"><title><?= Sanitizer::html($point['periodo']) ?>: <?= (int)$point['cantidad'] ?> ventas · $<?= Sanitizer::html(number_format($point['total'], 2)) ?></title></circle>
                            <?php if ($point['total'] > 0): ?><text class="chart-value-label" x="<?= $point['x'] ?>" y="<?= max(15, $point['y'] - 12) ?>" text-anchor="middle">$<?= Sanitizer::html(number_format($point['total'], 0)) ?></text><?php endif; ?>
                            <text class="chart-x-label" x="<?= $point['x'] ?>" y="<?= $monthHeight - 14 ?>" text-anchor="middle"><?= Sanitizer::html((new DateTimeImmutable($point['periodo'] . '-01'))->format('m/Y')) ?></text>
                        </g>
                    <?php endforeach; ?>
                </svg>
            </div>
        </article>

        <article class="card category-sales-card">
            <div class="card-top"><div><span class="eyebrow">Categorías</span><h2>Total vendido por categoría</h2></div><span class="muted">Monto y unidades</span></div>
            <div class="category-bars">
                <?php if ($categorias === []): ?><p class="muted">Aún no hay ventas para agrupar por categoría.</p><?php endif; ?>
                <?php foreach ($categorias as $fila): $pct = $maxCategoria > 0 ? ((float)$fila['total'] / $maxCategoria) * 100 : 0; ?>
                    <div class="category-bar-row">
                        <div class="category-bar-head"><strong><?= Sanitizer::html((string)$fila['categoria']) ?></strong><span><?= (int)$fila['unidades'] ?> unidades · $<?= Sanitizer::html(number_format((float)$fila['total'], 2)) ?></span></div>
                        <div class="category-bar-track"><span style="width: <?= Sanitizer::html(number_format($pct, 2, '.', '')) ?>%"></span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="card table-wrapper">
        <div class="card-top"><div><span class="eyebrow">Ranking</span><h2>Partes más vendidas</h2></div><span class="muted">Ordenadas por unidades</span></div>
        <table>
            <thead><tr><th>Posición</th><th>Parte</th><th>Unidades vendidas</th><th>Monto vendido</th></tr></thead>
            <tbody>
                <?php if ($topPartes === []): ?><tr><td colspan="4" class="empty-state">Aún no hay ventas registradas para construir el ranking.</td></tr><?php endif; ?>
                <?php foreach ($topPartes as $i => $fila): ?><tr><td>#<?= $i + 1 ?></td><td><?= Sanitizer::html((string)$fila['nombre_parte']) ?></td><td><?= (int)$fila['unidades'] ?></td><td>$<?= Sanitizer::html(number_format((float)$fila['total'], 2)) ?></td></tr><?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <form class="filter-form" method="GET" action="<?= Sanitizer::html(Url::ruta('/ventas')) ?>">
            <div class="form-group"><label for="desde">Desde</label><input type="date" id="desde" name="desde" value="<?= Sanitizer::html($desde ?? '') ?>"></div>
            <div class="form-group"><label for="hasta">Hasta</label><input type="date" id="hasta" name="hasta" value="<?= Sanitizer::html($hasta ?? '') ?>"></div>
            <div class="form-actions compact"><button class="btn" type="submit">Filtrar</button><a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/ventas')) ?>">Limpiar</a></div>
        </form>
    </section>

    <section class="card table-wrapper">
        <table>
            <thead><tr><th>#</th><th>Fecha</th><th>Usuario</th><th>Origen</th><th>Método</th><th>Entrega</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php if ($ventas === []): ?><tr><td colspan="9" class="empty-state">No existen ventas para los criterios seleccionados.</td></tr><?php endif; ?>
                <?php foreach ($ventas as $venta): ?>
                    <tr>
                        <td>#<?= Sanitizer::html((string)$venta['id_venta']) ?></td><td><?= Sanitizer::html((string)$venta['fecha_venta']) ?></td><td><?= Sanitizer::html((string)$venta['usuario']) ?></td>
                        <td><span class="badge"><?= Sanitizer::html($venta['origen'] === 'cliente' ? 'Compra cliente' : 'Venta interna') ?></span></td>
                        <td><?= Sanitizer::html((string)$venta['metodo_pago']) ?></td><td><?= Sanitizer::html(($venta['metodo_entrega'] ?? 'retiro') === 'delivery' ? 'Delivery' : 'Retiro') ?></td><td>$<?= Sanitizer::html(number_format((float)$venta['total'],2)) ?></td>
                        <td><span class="badge <?= $venta['estado'] === 'completada' ? 'badge-success' : 'badge-danger' ?>"><?= Sanitizer::html(ucfirst((string)$venta['estado'])) ?></span></td>
                        <td><div class="action-row"><a class="btn btn-mini btn-secundario" href="<?= Sanitizer::html(Url::ruta('/ventas/ver/' . $venta['id_venta'])) ?>">Ver detalle</a><a class="btn btn-mini" href="<?= Sanitizer::html(Url::ruta('/factura/' . $venta['id_venta'])) ?>">Factura</a></div></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
