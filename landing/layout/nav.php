<?php
$empresaId = $courseData['curso']['empresa_id'] ?? null;

$empresasConfig = [
    2 => [
        'logo' => 'img/logo-sdt.png',
        'color' => '#EF4E33',
    ],
    1 => [
        'logo' => 'img/capacitacion-usach-logo.png',
        'color' => '#2E3167',
    ],
    4 => [
        'logo' => 'img/fueo-usach-logo.png',
        'color' => '#F58220',
    ],
    3 => [
        'logo' => 'img/logo-fude.png',
        'color' => '#F9B000',
    ],
];

$defaultBrand = [
    'logo' => 'img/img-logocap.png',
    'color' => '#2E3167',
];

$brand = $empresasConfig[$empresaId] ?? $defaultBrand;
?>


<nav class="navbar navbar-expand-lg navbar-dark bg-transparent py-3 floating-nav wow slideInDown"
    data-wow-duration="1.2s" data-wow-delay="0.2s" id="mainNav"
    style="--brand-color: <?= htmlspecialchars($brand['color']) ?>;">
    <div class="container py-2">
        <?php $mostrarLogo = true; ?>

        <?php if ($mostrarLogo): ?>
        <a class="navbar-brand font-weight-bold" href="#inicio">
            <img src="<?= htmlspecialchars($brand['logo']) ?>" alt="" class="d-inline-block align-top">
        </a>
        <?php endif; ?>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ml-auto">
                <!--<li class="nav-item pr-1"><a class="nav-link" href="#inicio">Inicio</a></li>
        <li class="nav-item pr-1"><a class="nav-link" href="#contexto">Contexto</a></li>
        <li class="nav-item pr-1"><a class="nav-link" href="#objetivo">Objetivo</a></li>
        <li class="nav-item pr-1"><a class="nav-link" href="#perfil">Perfil</a></li>
        <li class="nav-item pr-1"><a class="nav-link" href="#temario">Temario</a></li>
        <li class="nav-item pr-1"><a class="nav-link" href="#modalidad">Modalidad</a></li>
        <li class="nav-item pr-1"><a class="nav-link" href="#inscripcion">Contacto</a></li>-->
            </ul>
        </div>
    </div>
</nav>