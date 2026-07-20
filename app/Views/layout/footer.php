<?php

use App\Helpers\Sanitizer;
use App\Helpers\Url;

$config = require dirname(__DIR__, 3) . '/config/config.php';
?>
<footer class="site-footer">
    <p>
        &copy; <?= date('Y') ?>
        <?= Sanitizer::html($config['app']['name']) ?>.
        Proyecto semestral de Desarrollo de Software 7.
    </p>
</footer>

<script src="<?= Sanitizer::html(Url::asset('js/main.js')) ?>"></script>
</body>
</html>
