<?php
use App\Core\Session;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="module-hero">
        <div><span class="eyebrow">No repudio</span><h1>Auditoría firmada digitalmente</h1><p>Cada acción crítica conserva hash SHA-256, firma RSA, huella de la llave pública, IP y fecha.</p></div>
        <div class="action-row"><a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/dashboard')) ?>">Volver al inicio</a></div>
    </section>

    <section class="card">
        <div class="card-top"><div><span class="eyebrow">Gestión de llaves</span><h2>Llaves RSA de usuarios internos</h2></div></div>
        <div class="table-wrapper"><table><thead><tr><th>Usuario</th><th>Algoritmo</th><th>Huella SHA-256</th><th>Estado</th><th>Creada</th><th>Acción</th></tr></thead><tbody>
        <?php if (($claves ?? []) === []): ?><tr><td colspan="6" class="empty-state">Las llaves se generan automáticamente al iniciar sesión o firmar una acción.</td></tr><?php endif; ?>
        <?php foreach ($claves as $clave): ?><tr>
            <td><?= Sanitizer::html($clave['usuario_nombre'] . ' (' . $clave['usuario'] . ')') ?></td>
            <td><?= Sanitizer::html((string)$clave['algoritmo']) ?></td>
            <td><code class="fingerprint"><?= Sanitizer::html((string)$clave['huella_sha256']) ?></code></td>
            <td><?= (int)$clave['activa'] === 1 ? '<span class="badge badge-success">Activa</span>' : '<span class="badge">Histórica</span>' ?></td>
            <td><?= Sanitizer::html((string)$clave['fecha_creacion']) ?></td>
            <td><?php if ((int)$clave['activa'] === 1 && Session::tienePermiso('auditoria.gestionar')): ?><form method="POST" action="<?= Sanitizer::html(Url::ruta('/auditoria/claves/rotar/' . $clave['id_usuario'])) ?>" data-confirm="¿Rotar la llave RSA de este usuario? La llave anterior se conservará para verificar firmas históricas."><?= Csrf::campo() ?><button class="btn btn-mini btn-secundario" type="submit">Rotar llave</button></form><?php else: ?>—<?php endif; ?></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
    </section>

    <section class="card table-wrapper">
        <div class="card-top"><div><span class="eyebrow">Trazabilidad</span><h2>Últimas acciones firmadas</h2></div><span class="muted">Verificación criptográfica en tiempo real</span></div>
        <table><thead><tr><th>Fecha</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>Entidad</th><th>IP</th><th>Firma</th></tr></thead><tbody>
        <?php if (($registros ?? []) === []): ?><tr><td colspan="7" class="empty-state">Aún no existen acciones firmadas.</td></tr><?php endif; ?>
        <?php foreach ($registros as $r): ?><tr>
            <td><?= Sanitizer::html((string)$r['fecha_evento']) ?></td>
            <td><?= Sanitizer::html($r['usuario_nombre'] . ' (' . $r['usuario'] . ')') ?></td>
            <td><?= Sanitizer::html((string)$r['modulo']) ?></td>
            <td><?= Sanitizer::html((string)$r['accion']) ?></td>
            <td><?= Sanitizer::html((string)$r['entidad']) ?><?= $r['entidad_id'] !== null ? ' #' . Sanitizer::html((string)$r['entidad_id']) : '' ?></td>
            <td><?= Sanitizer::html((string)$r['ip']) ?></td>
            <td><?= !empty($r['firma_valida']) ? '<span class="badge badge-success">Válida</span>' : '<span class="badge badge-danger">No válida</span>' ?></td>
        </tr><?php endforeach; ?>
        </tbody></table>
    </section>
</main>
