<?php

use App\Core\Session;
use App\Helpers\Sanitizer;
use App\Helpers\Url;
?>
<main class="container">
    <section class="hero hero-publico">
        <span class="eyebrow">Rastro de autopartes</span>
        <h1>Encuentre la pieza correcta, al precio correcto</h1>
        <p>
            Consulte en línea el inventario real del rastro: carrocería,
            motores, vidrios y piezas eléctricas, organizadas por
            categoría, con fotografías, precio y existencias actualizadas.
        </p>

        <div class="hero-acciones">
            <a class="btn" href="<?= Sanitizer::html(Url::ruta('/catalogo')) ?>">
                Ver catálogo completo
            </a>
            <?php if (!Session::estaAutenticado()): ?>
                <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/registro')) ?>">Crear cuenta de cliente</a>
                <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/login')) ?>">Iniciar sesión</a>
            <?php else: ?>
                <a class="btn btn-secundario" href="<?= Sanitizer::html(Url::ruta('/carrito')) ?>"><span aria-hidden="true">🛒</span> Ver carrito</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="seccion-valor">
        <span class="eyebrow">Por qué confiar en este sistema</span>
        <h2>Bondades y valor estratégico del sistema</h2>

        <div class="grid-cards">
            <article class="card card-valor">
                <h3>Seguridad bajo estándares OWASP</h3>
                <p class="muted">
                    El sistema se desarrolló siguiendo las guías de OWASP
                    para prevenir inyecciones SQL, XSS y manipulación de
                    datos, protegiendo la información del rastro y de
                    sus clientes.
                </p>
            </article>

            <article class="card card-valor">
                <h3>Arquitectura ordenada y escalable</h3>
                <p class="muted">
                    Con un diseño MVC orientado a objetos, cada módulo
                    (usuarios, inventario, ventas, comentarios) funciona
                    de forma independiente, facilitando nuevas
                    funcionalidades sin arriesgar lo ya construido.
                </p>
            </article>

            <article class="card card-valor">
                <h3>Trazabilidad de acceso</h3>
                <p class="muted">
                    Los intentos de inicio de sesión registran usuario,
                    dirección IP, fecha y resultado. Las anomalías de acceso
                    pueden ser revisadas desde el módulo administrativo de seguridad.
                </p>
            </article>

            <article class="card card-valor">
                <h3>Manejo centralizado de errores</h3>
                <p class="muted">
                    La conexión a la base de datos y el control de errores se
                    concentran en clases reutilizables, facilitando el diagnóstico
                    y evitando repetir lógica técnica entre módulos.
                </p>
            </article>
        </div>
    </section>

    <?php if ($destacados !== []): ?>
        <section class="seccion-destacados">
            <span class="eyebrow">Recién ingresadas</span>
            <h2>Piezas destacadas</h2>

            <div class="grid-cards grid-catalogo">
                <?php foreach ($destacados as $pieza): ?>
                    <?php include __DIR__ . '/_tarjeta-parte.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($secciones !== []): ?>
        <section class="seccion-categorias">
            <span class="eyebrow">Explorar por categoría</span>
            <h2>Categorías del rastro</h2>

            <div class="chips">
                <?php foreach ($secciones as $seccion): ?>
                    <a
                        class="chip"
                        href="<?= Sanitizer::html(
                            Url::ruta('/categoria/' . $seccion['id_seccion'])
                        ) ?>"
                    >
                        <?= Sanitizer::html($seccion['nombre_seccion']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
