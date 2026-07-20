<?php

use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="module-hero">
        <div>
            <span class="eyebrow">Auditoría</span>
            <h1>Seguridad e intentos de acceso</h1>
            <p>Consulta la trazabilidad de inicios de sesión, direcciones IP, fechas, bloqueos y anomalías detectadas.</p>
        </div>
        <div class="action-row">
            <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/dashboard')) ?>">Volver al inicio</a>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card"><strong><?= (int) $resumen['intentos_hoy'] ?></strong><span>Intentos hoy</span></article>
        <article class="stat-card"><strong><?= (int) $resumen['fallidos_hoy'] ?></strong><span>Fallidos/bloqueados hoy</span></article>
        <article class="stat-card"><strong><?= (int) $resumen['anomalias'] ?></strong><span>Anomalías registradas</span></article>
        <article class="stat-card"><strong><?= (int) $resumen['bloqueados'] ?></strong><span>Cuentas bloqueadas</span></article>
    </section>

    <section class="card table-wrapper">
        <h2>Últimos intentos de login</h2>
        <table>
            <thead><tr><th>Fecha</th><th>Usuario ingresado</th><th>IP</th><th>Estado</th><th>Mensaje</th></tr></thead>
            <tbody>
            <?php if ($intentos === []): ?><tr><td colspan="5" class="empty-state">No existen intentos registrados.</td></tr><?php endif; ?>
            <?php foreach ($intentos as $intento): ?>
                <tr>
                    <td><?= Sanitizer::html($intento['fecha_intento']) ?></td>
                    <td><?= Sanitizer::html($intento['usuario_ingresado']) ?></td>
                    <td><?= Sanitizer::html($intento['ip']) ?></td>
                    <td><span class="badge <?= in_array($intento['estado'], ['exitoso'], true) ? 'badge-success' : ($intento['estado'] === 'fallido' ? 'badge-warning' : 'badge-danger') ?>"><?= Sanitizer::html($intento['estado']) ?></span></td>
                    <td><?= Sanitizer::html($intento['mensaje'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="card table-wrapper">
        <h2>Anomalías registradas</h2>
        <table>
            <thead><tr><th>Fecha</th><th>Módulo</th><th>Usuario</th><th>IP</th><th>Nivel</th><th>Descripción</th></tr></thead>
            <tbody>
            <?php if ($anomalias === []): ?><tr><td colspan="6" class="empty-state">No existen anomalías registradas.</td></tr><?php endif; ?>
            <?php foreach ($anomalias as $anomalia): ?>
                <tr>
                    <td><?= Sanitizer::html($anomalia['fecha_registro']) ?></td>
                    <td><?= Sanitizer::html($anomalia['modulo']) ?></td>
                    <td><?= Sanitizer::html($anomalia['usuario']) ?></td>
                    <td><?= Sanitizer::html($anomalia['ip'] ?? '') ?></td>
                    <td><span class="badge <?= in_array($anomalia['nivel'], ['alta','critica'], true) ? 'badge-danger' : 'badge-warning' ?>"><?= Sanitizer::html($anomalia['nivel']) ?></span></td>
                    <td><?= Sanitizer::html($anomalia['descripcion']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
