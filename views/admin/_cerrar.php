</div>
</section>
<?= claves_js() ?>
<script src="public/js/panel.js?v=<?= filemtime(__DIR__ . '/../../public/js/panel.js') ?>"></script>
<?php foreach ($scripts ?? [] as $js): if (!is_file(__DIR__ . '/../../public/js/' . $js . '.js')) continue; ?>
<script src="public/js/<?= $js ?>.js?v=<?= filemtime(__DIR__ . '/../../public/js/' . $js . '.js') ?>"></script>
<?php endforeach; ?>
