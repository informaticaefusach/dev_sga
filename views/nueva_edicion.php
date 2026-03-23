<?php

require_once __DIR__ . '/../db.php';

$mensaje = "";

/* =============================
   OBTENER CURSOS
============================= */

$cursos = $pdo->query("
SELECT id, curso_nombre
FROM dir_cursos_catalogo
ORDER BY curso_nombre
")->fetchAll();


/* =============================
   GUARDAR EDICION
============================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $curso_id = $_POST['curso_id'] ?? null;
        $version = $_POST['version'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'] ?? null;
        $fecha_fin = $_POST['fecha_fin'] ?? null;
        $modalidad = $_POST['modalidad'] ?? '';
        $cupo = $_POST['cupo_maximo'] ?? 0;
        $estado = $_POST['estado'] ?? 1;

        $stmt = $pdo->prepare("
        INSERT INTO dir_cursos_ediciones
        (curso_id, version, fecha_inicio, fecha_fin, modalidad, cupo_maximo, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $curso_id,
            $version,
            $fecha_inicio,
            $fecha_fin,
            $modalidad,
            $cupo,
            $estado
        ]);

        header("Location: index.php?page=ediciones");
        exit;

    } catch (PDOException $e) {

        $mensaje = "Error al guardar la edición: " . $e->getMessage();

    }

}

?>

<div class="container-fluid">

    <h2 class="mb-4">Nueva Edición de Curso</h2>

    <?php if ($mensaje): ?>

        <div class="alert alert-danger">
            <?= $mensaje ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <div class="card shadow">

            <div class="card-body">

                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label>Curso</label>

                        <select name="curso_id" class="form-control" required>

                            <option value="">Seleccione un curso</option>

                            <?php foreach ($cursos as $c): ?>

                                <option value="<?= $c['id'] ?>">

                                    <?= htmlspecialchars($c['curso_nombre']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>



                    <div class="col-md-3 mb-3">

                        <label>Versión</label>

                        <input type="text" name="version" class="form-control" placeholder="Ej: 2025-01">

                    </div>


                    <div class="col-md-3 mb-3">

                        <label>Modalidad</label>

                        <select name="modalidad" class="form-control">

                            <option>Online</option>
                            <option>Presencial</option>
                            <option>Híbrido</option>

                        </select>

                    </div>



                    <div class="col-md-3 mb-3">

                        <label>Fecha inicio</label>

                        <input type="date" name="fecha_inicio" class="form-control">

                    </div>


                    <div class="col-md-3 mb-3">

                        <label>Fecha término</label>

                        <input type="date" name="fecha_fin" class="form-control">

                    </div>


                    <div class="col-md-3 mb-3">

                        <label>Cupo máximo</label>

                        <input type="number" name="cupo_maximo" class="form-control">

                    </div>


                    <div class="col-md-3 mb-3">

                        <label>Estado</label>

                        <select name="estado" class="form-control">

                            <option value="1">Activa</option>
                            <option value="0">Inactiva</option>

                        </select>

                    </div>


                </div>

            </div>

        </div>


        <div class="mt-3">

            <button class="btn btn-primary">

                Guardar edición

            </button>

            <a href="index.php?page=ediciones" class="btn btn-secondary">

                Cancelar

            </a>

        </div>


    </form>

</div>