<?php

require 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['slug'])) {
    die("Slug no enviado");
}

$slug = $_GET['slug'];

/* =============================
   CURSO
============================= */

$stmt = $pdo->prepare("SELECT * FROM dir_cursos_catalogo WHERE curso_slug=?");
$stmt->execute([$slug]);
$curso = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$curso) {
    die("Curso no encontrado: " . htmlspecialchars($slug));
}

$curso_id = $curso['id'];

/* =============================
   IMAGEN HEADER DINÁMICA
============================= */

$header_default = "img/header_default.jpg";

// Lista de posibles extensiones
$extensiones = ['jpg', 'png', 'webp'];

$header_img = $header_default;

foreach ($extensiones as $ext) {
    $ruta = "img/header{$curso_id}." . $ext;

    if (file_exists($ruta)) {
        $header_img = $ruta;
        break;
    }
}

/* =============================
   DATOS RELACIONADOS
============================= */

function getList($pdo, $sql, $id)
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$perfil = getList($pdo, "SELECT descripcion FROM dir_cursos_perfil_egreso WHERE curso_id=? ORDER BY orden", $curso_id);

$requisitos = getList($pdo, "SELECT requisito FROM dir_cursos_requisitos_previos WHERE curso_id=? ORDER BY orden", $curso_id);

$continuidad = getList($pdo, "SELECT curso_relacionado FROM dir_cursos_continuidad WHERE curso_id=? ORDER BY orden", $curso_id);

/* =============================
   UNIDADES
============================= */

$stmt = $pdo->prepare("SELECT * FROM dir_cursos_unidades WHERE curso_id=? ORDER BY orden");
$stmt->execute([$curso_id]);
$unidades = $stmt->fetchAll();

foreach ($unidades as &$u) {
    $stmt = $pdo->prepare("SELECT contenido FROM dir_cursos_unidades_contenidos WHERE unidad_id=? ORDER BY orden");
    $stmt->execute([$u['id']]);
    $u['contenidos'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/* =============================
   DATA DIRECTA (SIN JSON)
============================= */

$courseData = [
    "curso" => [
        "nombre" => $curso['curso_nombre'] ?? '',
        "descripcion_corta" => $curso['curso_descripcion_corta'] ?? '',
        "contexto" => $curso['curso_contexto'] ?? '',
        "objetivo_general" => $curso['curso_objetivo_general'] ?? '',
        "modalidad" => $curso['curso_modalidad'] ?? '',
        "horas" => $curso['horas_cronologicas'] ?? '',
        "codigo_sence" => $curso['curso_codigo_sence'] ?? '',
        "empresa_id" => $curso['empresa_id'] ?? null,
    ],
    "perfil" => $perfil,
    "requisitos" => $requisitos,
    "unidades" => $unidades
];

$year = date('Y');

// =============================
// FILTRAR UNIDADES VÁLIDAS
// =============================
$unidades_validas = array_values(array_filter($courseData['unidades'], function ($u) {
    return trim($u['titulo_unidad'] ?? '') !== ''
        || trim($u['objetivo_unidad'] ?? '') !== ''
        || !empty($u['contenidos']);
}));

// =============================
// VER SI EXISTEN OBJETIVOS
// =============================
$mostrar_objetivo = false;
$mostrar_modalidad_unidad = false;

foreach ($unidades_validas as $u) {

    if (!empty(trim($u['objetivo_unidad'] ?? ''))) {
        $mostrar_objetivo = true;
    }

    if (!empty(trim($u['modalidad'] ?? ''))) {
        $mostrar_modalidad_unidad = true;
    }

    // optimización: si ya encontramos ambas, salimos
    if ($mostrar_objetivo && $mostrar_modalidad_unidad) {
        break;
    }
}
?>

<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($courseData['curso']['nombre']) ?></title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<script src="assets/js/form.js"></script>

<body>

    <header class="hero"
        style="background-image: url('<?= htmlspecialchars($header_img) ?>'); background-size: cover; background-position: center;">
        <?php include 'layout/nav.php'; ?>

        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="tag">
                        <?php if (!empty($courseData['curso']['modalidad']) || !empty($courseData['curso']['horas'])): ?>
                        <?= htmlspecialchars($courseData['curso']['modalidad'] ?: 'Curso') ?>
                        <?php if (!empty($courseData['curso']['horas'])): ?>
                        | <?= htmlspecialchars($courseData['curso']['horas']) ?> horas
                        <?php endif; ?>
                        <?php else: ?>
                        Curso de Capacitación
                        <?php endif; ?>
                    </div>

                    <h1><?= htmlspecialchars($courseData['curso']['nombre']) ?></h1>

                    <?php if (!empty($courseData['curso']['descripcion_corta'])): ?>
                    <p class="mb-4 text-white">
                        <?= htmlspecialchars($courseData['curso']['descripcion_corta']) ?>
                    </p>
                    <?php endif; ?>

                    <?php if (
                        !empty($courseData['curso']['modalidad']) ||
                        !empty($courseData['curso']['horas']) ||
                        !empty($courseData['curso']['codigo_sence'])
                    ): ?>
                    <div class="hero-card">
                        <h5>Incluye</h5>

                        <ul class="include-list mb-0">
                            <?php if (!empty($courseData['curso']['modalidad'])): ?>
                            <li>✔ Modalidad: <?= htmlspecialchars($courseData['curso']['modalidad']) ?></li>
                            <?php endif; ?>

                            <?php if (!empty($courseData['curso']['horas'])): ?>
                            <li>✔ Duración: <?= htmlspecialchars($courseData['curso']['horas']) ?> horas</li>
                            <?php endif; ?>

                            <?php if (!empty($courseData['curso']['codigo_sence'])): ?>
                            <li>✔ Código SENCE: <?= htmlspecialchars($courseData['curso']['codigo_sence']) ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap mt-4">
                        <a href="#inscripcion" class="btn btn-accent mr-2 mb-2">Contáctate con Nosotros</a>

                        <?php if (!empty($unidades_validas)): ?>
                        <a href="#temario" class="btn btn-outline-light mb-2">Contenidos</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <?php if (!empty($courseData['curso']['contexto'])): ?>
    <section class="section section-tight">
        <div class="container">
            <div class="content-card">
                <div class="section-heading">
                    <span class="section-kicker">Información</span>
                    <h2>Contexto</h2>
                </div>

                <div class="rich-text">
                    <p><?= nl2br(htmlspecialchars($courseData['curso']['contexto'])) ?></p>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($courseData['curso']['objetivo_general'])): ?>
    <section class="section section-tight">
        <div class="container">
            <div class="content-card content-card-accent">
                <div class="section-heading">
                    <span class="section-kicker">Propósito</span>
                    <h2>Objetivo General</h2>
                </div>

                <div class="rich-text">
                    <p><?= nl2br(htmlspecialchars($courseData['curso']['objetivo_general'])) ?></p>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($courseData['perfil'])): ?>
    <section class="section section-tight">
        <div class="container">
            <div class="content-card">
                <div class="section-heading">
                    <span class="section-kicker">Resultados esperados</span>
                    <h2>Perfil de Egreso</h2>
                </div>

                <ul class="content-list mb-0">
                    <?php foreach ($courseData['perfil'] as $p): ?>
                    <?php if (!empty(trim($p))): ?>
                    <li><?= htmlspecialchars($p) ?></li>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($unidades_validas)): ?>
    <section class="section section-tight" id="temario">
        <div class="container">
            <div class="content-card">
                <div class="section-heading">
                    <span class="section-kicker">Plan de estudio</span>
                    <h2>Temario</h2>
                </div>

                <div class="table-wrap">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Unidad</th>

                                <?php if ($mostrar_objetivo): ?>
                                <th>Objetivo</th>
                                <?php endif; ?>

                                <th>Contenidos</th>

                                <?php if ($mostrar_modalidad_unidad): ?>
                                <th>Modalidad</th>
                                <?php endif; ?>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($unidades_validas as $u): ?>
                            <tr>
                                <td class="col-numero">
                                    <?= htmlspecialchars($u['numero_unidad'] ?? '') ?>
                                </td>

                                <td>
                                    <?php if (!empty($u['titulo_unidad'])): ?>
                                    <span class="unit-badge"><?= htmlspecialchars($u['titulo_unidad']) ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <?php if ($mostrar_objetivo): ?>
                                <td>
                                    <?= !empty($u['objetivo_unidad'])
                                                ? nl2br(htmlspecialchars($u['objetivo_unidad']))
                                                : '<span class="text-muted">-</span>' ?>
                                </td>
                                <?php endif; ?>

                                <td>
                                    <?php if (!empty($u['contenidos'])): ?>
                                    <ul class="content-list mb-0">
                                        <?php foreach ($u['contenidos'] as $c): ?>
                                        <?php if (!empty(trim($c))): ?>
                                        <li><?= htmlspecialchars($c) ?></li>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <?php if ($mostrar_modalidad_unidad): ?>
                                <td>
                                    <?= !empty($u['modalidad'])
                                                ? htmlspecialchars($u['modalidad'])
                                                : '<span class="text-muted">-</span>' ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="contact-cta-section" id="inscripcion">
        <div class="contact-cta-overlay"></div>

        <div class="container contact-cta-content">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="contact-cta-text">
                        <h2>Contáctate con nosotros</h2>
                        <p>
                            Déjanos tus datos y te contactamos de vuelta.<br>
                            Utiliza el formulario para solicitar más información sobre fechas, horarios y valores.
                        </p>
                    </div>
                </div>

                <div class="col-lg-5 offset-lg-1">
                    <div class="contact-form-box">
                        <form id="leadForm" action="procesar_lead.php" method="post">
                            <input type="hidden" name="curso"
                                value="<?= htmlspecialchars($courseData['curso']['nombre'] ?? '') ?>">
                            <input type="hidden" name="slug" value="<?= htmlspecialchars($slug ?? '') ?>">
                            <input type="hidden" name="curso_id" value="<?= htmlspecialchars($curso_id ?? '') ?>">

                            <div id="leadFormMessage" class="mb-2"></div>

                            <div class="form-group">
                                <input type="text" name="nombre" class="form-control contact-input"
                                    placeholder="Nombre completo" required>
                            </div>

                            <div class="form-group">
                                <input type="email" name="email" class="form-control contact-input"
                                    placeholder="Correo electrónico" required>
                            </div>

                            <div class="form-group">
                                <input type="text" name="telefono" class="form-control contact-input"
                                    placeholder="Teléfono">
                            </div>

                            <div class="form-group">
                                <input type="text" name="actividad" class="form-control contact-input"
                                    placeholder="Actividad">
                            </div>

                            <div class="form-group">
                                <select name="region" class="form-control contact-input" required>
                                    <option value="">Seleccionar región</option>
                                    <option>Arica y Parinacota</option>
                                    <option>Tarapacá</option>
                                    <option>Antofagasta</option>
                                    <option>Atacama</option>
                                    <option>Coquimbo</option>
                                    <option>Valparaíso</option>
                                    <option>Metropolitana</option>
                                    <option>O’Higgins</option>
                                    <option>Maule</option>
                                    <option>Ñuble</option>
                                    <option>Biobío</option>
                                    <option>La Araucanía</option>
                                    <option>Los Ríos</option>
                                    <option>Los Lagos</option>
                                    <option>Aysén</option>
                                    <option>Magallanes</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <textarea name="mensaje" rows="5" class="form-control contact-input"
                                    placeholder="Mensaje" required></textarea>
                            </div>

                            <button type="submit" class="btn contact-submit-btn btn-block">
                                Quiero más información
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="text-center p-3">
        <small>&copy; <?= $year ?> CAP USACH</small>
    </footer>

</body>

</html>