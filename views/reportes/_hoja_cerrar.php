</main>
<?php if ($modo === 'imprimir' && empty($_GET['ver'])): ?>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
<?php endif; ?>
</body>
</html>
