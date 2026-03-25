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

foreach ($unidades_validas as $u) {
    if (!empty(trim($u['objetivo_unidad'] ?? ''))) {
        $mostrar_objetivo = true;
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

<body>

<?php include 'layout/nav.php'; ?>

<header class="hero">
<div class="container">
<div class="row align-items-center">

<div class="col-lg-6">
<h1><?= htmlspecialchars($courseData['curso']['nombre']) ?></h1>

<?php if (!empty($courseData['curso']['descripcion_corta'])): ?>
<p><?= htmlspecialchars($courseData['curso']['descripcion_corta']) ?></p>
<?php endif; ?>

<?php if (!empty($courseData['requisitos'])): ?>
<h5>Requisitos</h5>
<ul>
<?php foreach ($courseData['requisitos'] as $r): ?>
<li><?= htmlspecialchars($r) ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

</div>

</div>
</div>
</header>

<!-- CONTEXTO -->
<?php if (!empty($courseData['curso']['contexto'])): ?>
<section class="section">
<div class="container">
<h2>Contexto</h2>
<p><?= htmlspecialchars($courseData['curso']['contexto']) ?></p>
</div>
</section>
<?php endif; ?>

<!-- OBJETIVO -->
<?php if (!empty($courseData['curso']['objetivo_general'])): ?>
<section class="section">
<div class="container">
<h2>Objetivo General</h2>
<p><?= htmlspecialchars($courseData['curso']['objetivo_general']) ?></p>
</div>
</section>
<?php endif; ?>

<!-- PERFIL -->
<?php if (!empty($courseData['perfil'])): ?>
<section class="section">
<div class="container">
<h2>Perfil de Egreso</h2>
<ul>
<?php foreach ($courseData['perfil'] as $p): ?>
<li><?= htmlspecialchars($p) ?></li>
<?php endforeach; ?>
</ul>
</div>
</section>
<?php endif; ?>

<!-- TEMARIO -->
<?php if (!empty($unidades_validas)): ?>
<section class="section">
<div class="container">
<h2>Temario</h2>

<table class="table table-bordered">
<thead>
<tr>
<th>N°</th>
<th>Unidad</th>

<?php if ($mostrar_objetivo): ?>
<th>Objetivo</th>
<?php endif; ?>

<th>Contenidos</th>
</tr>
</thead>

<tbody>
<?php foreach ($unidades_validas as $u): ?>
<tr>

<td><?= $u['numero_unidad'] ?></td>

<td><?= htmlspecialchars($u['titulo_unidad']) ?></td>

<?php if ($mostrar_objetivo): ?>
<td>
    <?= !empty($u['objetivo_unidad']) 
        ? htmlspecialchars($u['objetivo_unidad']) 
        : '-' ?>
</td>
<?php endif; ?>

<td>
<?php if (!empty($u['contenidos'])): ?>
    <ul>
        <?php foreach ($u['contenidos'] as $c): ?>
            <li><?= htmlspecialchars($c) ?></li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    -
<?php endif; ?>
</td>

</tr>
<?php endforeach; ?>
</tbody>

</table>
</div>
</section>
<?php endif; ?>

<!-- MODALIDAD -->
<?php if (!empty($courseData['curso']['modalidad']) || !empty($courseData['curso']['horas']) || !empty($courseData['curso']['codigo_sence'])): ?>
<section class="section">
<div class="container">
<h2>Información del curso</h2>

<?php if (!empty($courseData['curso']['modalidad'])): ?>
<p><strong>Modalidad:</strong> <?= htmlspecialchars($courseData['curso']['modalidad']) ?></p>
<?php endif; ?>

<?php if (!empty($courseData['curso']['horas'])): ?>
<p><strong>Duración:</strong> <?= $courseData['curso']['horas'] ?> horas</p>
<?php endif; ?>

<?php if (!empty($courseData['curso']['codigo_sence'])): ?>
<p><strong>Código SENCE:</strong> <?= htmlspecialchars($courseData['curso']['codigo_sence']) ?></p>
<?php endif; ?>

</div>
</section>
<?php endif; ?>

<footer class="text-center p-3">
<small>&copy; <?= $year ?> CAP USACH</small>
</footer>

</body>
</html>