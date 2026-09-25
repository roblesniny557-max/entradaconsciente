<?php
// view/components/layout.php
// Estructura común de las páginas internas: <head>, navbar, mensajes flash y scripts.
require_once __DIR__ . '/../../model/auth.php';
require_once __DIR__ . '/../../model/iconos.php';

function layout_inicio(string $titulo, string $activo = ''): void
{
    $u = usuario_actual();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($titulo) ?> · <?= e(config('sistema')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/app.css?v=4">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>window.ICONOS = <?= json_encode(iconos_svg()) ?>;</script>
    <script src="assets/app.js?v=4"></script>
</head>
<body>
<?php if ($u) { include __DIR__ . '/navbar.php'; } ?>
<main class="pagina">
    <?php
}

function layout_fin(): void
{
    $flash = tomar_flash();
    ?>
</main>
<?php if ($flash): ?>
<script>notificar(<?= json_encode($flash['mensaje']) ?>, <?= json_encode($flash['tipo'] === 'error' ? 'error' : 'success') ?>);</script>
<?php endif; ?>
</body>
</html>
    <?php
}
