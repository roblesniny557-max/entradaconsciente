<?php
// view/components/navbar.php — se incluye desde layout_inicio() con $activo y $u disponibles
$iniciales = implode('', array_map(
    fn($p) => mb_strtoupper(mb_substr($p, 0, 1)),
    array_slice(preg_split('/\s+/', trim($u['nombre'])), 0, 2)
));

$enlaces = [
    ['asistencia', 'index.php', 'Tomar asistencia', 'asistencia', false],
    ['reportes', 'reporte_inasistencias.php', 'Reportes', 'reportes', false],
    ['estudiantes', 'estudiantes.php', 'Estudiantes', 'estudiantes', true],
    ['usuarios', 'usuarios.php', 'Usuarios', 'usuarios', true],
];
?>
<nav class="nav" id="nav">
    <div class="nav-inner">
        <a href="index.php" class="brand">
            <img src="<?= e(config('logo_url')) ?>" alt="Escudo <?= e(config('institucion')) ?>" onerror="this.style.display='none'">
            <div>
                <div class="brand-titulo"><?= e(config('sistema')) ?></div>
                <div class="brand-sub"><?= e(config('institucion')) ?></div>
            </div>
        </a>

        <button type="button" class="btn btn-fantasma btn-icono nav-toggle" id="navToggle" aria-label="Abrir menú">
            <?= icono('menu') ?>
        </button>

        <div class="nav-links">
            <?php foreach ($enlaces as [$clave, $url, $texto, $ic, $soloAdmin]): ?>
                <?php if ($soloAdmin && !es_admin()) continue; ?>
                <a href="<?= $url ?>" class="nav-link <?= $activo === $clave ? 'activo' : '' ?>">
                    <?= icono($ic) ?> <?= e($texto) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="nav-usuario">
            <a href="perfil.php" class="usuario-chip" title="Mi perfil">
                <span class="avatar"><?= e($iniciales) ?></span>
                <span class="usuario-datos">
                    <span class="usuario-nombre"><?= e($u['nombre']) ?></span>
                    <span class="usuario-rol"><?= $u['rol'] === 'admin' ? 'Administrador' : 'Docente' ?></span>
                </span>
            </a>
            <form action="../controller/logout.php" method="POST" id="formSalir">
                <?= csrf_campo() ?>
                <button type="submit" class="btn btn-peligro btn-sm"><?= icono('salir', 'ic-sm') ?> Salir</button>
            </form>
        </div>
    </div>
</nav>
