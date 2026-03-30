<?php

session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['excel_alumnos'])) {
    header("Location: index.php?page=importar_alumnos");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?page=importar_alumnos");
    exit;
}

$alumnos = $_SESSION['excel_alumnos'];
$edicion_id = $_POST['edicion_id'] ?? null;

/* seleccionados */
$seleccionados = $_POST['alumnos'] ?? [];

$matriculados = 0;
$actualizados = 0;
$omitidos = 0;

try {

    $pdo->beginTransaction();

    foreach ($alumnos as $a) {

        $email = trim($a['email'] ?? '');

        if ($email == '') {
            $omitidos++;
            continue;
        }

        /* =============================
           VALIDAR SELECCIÓN
        ============================= */

        if (!in_array($email, $seleccionados)) {
            continue;
        }

        $nombre = $a['nombre'] ?? '';
        $apellido_p = $a['apellido_paterno'] ?? '';
        $apellido_m = $a['apellido_materno'] ?? '';

        /* =============================
           NOTA
        ============================= */

        $nota_excel = $a['nota'] ?? 0;

        $nota = floatval($nota_excel);

        /* convertir 70 → 7.0 */
        if ($nota >= 10) {
            $nota = $nota / 10;
        }

        /* límites */
        if ($nota > 7)
            $nota = 7;
        if ($nota < 1)
            $nota = 1;


        $nota = round($nota, 1);

        $aprobado = ($nota >= 4) ? 1 : 0;

        /* =============================
           BUSCAR / CREAR ALUMNO
        ============================= */

        $stmt = $pdo->prepare("
            SELECT id FROM dir_cursos_alumnos WHERE email = ?
        ");
        $stmt->execute([$email]);

        $alumno = $stmt->fetch();

        if ($alumno) {
            $alumno_id = $alumno['id'];
        } else {

            $stmt = $pdo->prepare("
                INSERT INTO dir_cursos_alumnos
                (nombre, apellido_paterno, apellido_materno, email)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$nombre, $apellido_p, $apellido_m, $email]);

            $alumno_id = $pdo->lastInsertId();
        }

        /* =============================
           VALIDAR SI YA EXISTE MATRÍCULA
        ============================= */

        $stmt = $pdo->prepare("
            SELECT id FROM dir_cursos_matriculas
            WHERE alumno_id = ? AND edicion_id = ?
        ");
        $stmt->execute([$alumno_id, $edicion_id]);

        $existe = $stmt->fetch();

        if ($existe) {

            /* 🔥 ACTUALIZAR NOTA */
            $stmt = $pdo->prepare("
                UPDATE dir_cursos_matriculas
                SET nota_final = ?, aprobado = ?
                WHERE alumno_id = ? AND edicion_id = ?
            ");

            $stmt->execute([
                $nota,
                $aprobado,
                $alumno_id,
                $edicion_id
            ]);

            $actualizados++;

        } else {

            /* NUEVA MATRÍCULA */
            $stmt = $pdo->prepare("
                INSERT INTO dir_cursos_matriculas
                (alumno_id, edicion_id, nota_final, aprobado)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $alumno_id,
                $edicion_id,
                $nota,
                $aprobado
            ]);

            $matriculados++;
        }
    }

    $pdo->commit();

    unset($_SESSION['excel_alumnos']);

} catch (Exception $e) {

    $pdo->rollBack();
    die("Error al procesar matrículas: " . $e->getMessage());
}

?>

<div class="container-fluid">

    <h2>Proceso completado</h2>

    <div class="alert alert-success">

        <strong>Nuevos matriculados:</strong>
        <?= $matriculados ?> <br>
        <strong>Actualizados (ya existían):</strong>
        <?= $actualizados ?> <br>
        <strong>Omitidos (sin email):</strong>
        <?= $omitidos ?>

    </div>

    <a href="index.php?page=matriculas&edicion_id=<?= $edicion_id ?>" class="btn btn-primary">
        Ver matrículas
    </a>

    <a href="index.php?page=importar_alumnos" class="btn btn-secondary">
        Importar otro Excel
    </a>

</div>