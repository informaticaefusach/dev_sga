<?php

session_start();

require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['excel_alumnos'])) {

    header("Location: index.php?page=importar_alumnos");
    exit;

}

$alumnos = $_SESSION['excel_alumnos'];


/* =============================
   AGRUPAR ALUMNOS POR CURSO
============================= */

$grupos = [];

foreach ($alumnos as $a) {

    $curso = $a['curso_excel'] ?? 'Sin curso';

    $grupos[$curso][] = $a;

}


/* =============================
   OBTENER EDICIONES
============================= */

$stmt = $pdo->query("
SELECT 
e.id,
c.curso_nombre,
e.fecha_inicio,
e.fecha_fin
FROM dir_cursos_ediciones e
JOIN dir_cursos_catalogo c 
ON c.id = e.curso_id
ORDER BY e.id DESC
");

$ediciones = $stmt->fetchAll();

?>

<div class="container-fluid">

    <h2 class="mb-4">Revisión de Importación</h2>

    <div class="card shadow">

        <div class="card-body">

            <form method="POST" action="index.php?page=procesar_matriculas">

                <div class="row mb-4">

                    <div class="col-md-6">

                        <label>Seleccionar edición del curso</label>

                        <select name="edicion_id" class="form-control" required>

                            <option value="">Seleccione una edición</option>

                            <?php foreach ($ediciones as $e): ?>

                                <option value="<?= $e['id'] ?>">

                                    <?= htmlspecialchars($e['curso_nombre']) ?>
                                    (
                                    <?= $e['fecha_inicio'] ?> -
                                    <?= $e['fecha_fin'] ?>)

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>



                <h4 class="mb-3">Alumnos detectados en el Excel</h4>


                <?php foreach ($grupos as $curso => $lista): ?>

                    <div class="card mb-4">

                        <div class="card-header bg-dark text-white">

                            <strong>
                                <?= htmlspecialchars($curso) ?>
                            </strong>

                        </div>

                        <div class="card-body">

                            <div class="table-responsive">

                                <table class="table table-striped table-bordered">

                                    <thead class="table-light">

                                        <tr>

                                            <th width="40">
                                                <input type="checkbox" onclick="toggleGrupo(this)">
                                            </th>

                                            <th>#</th>
                                            <th>Nombre</th>
                                            <th>Apellido Paterno</th>
                                            <th>Apellido Materno</th>
                                            <th>Email</th>
                                            <th>Nota</th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        <?php foreach ($lista as $i => $a): ?>

                                            <tr>

                                                <td>

                                                    <input type="checkbox" name="alumnos[]" value="<?= $a['email'] ?>">

                                                </td>

                                                <td>
                                                    <?= $i + 1 ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($a['nombre']) ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($a['apellido_paterno']) ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($a['apellido_materno']) ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($a['email']) ?>
                                                </td>

                                                <td>
                                                    <?= htmlspecialchars($a['nota']) ?>
                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>


                <div class="mt-3">

                    <button class="btn btn-primary">

                        Matricular alumnos seleccionados

                    </button>

                    <a href="index.php?page=importar_alumnos" class="btn btn-secondary">

                        Volver

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>


<script>

    function toggleGrupo(source) {

        let table = source.closest("table");

        let checkboxes = table.querySelectorAll("tbody input[type=checkbox]");

        checkboxes.forEach(cb => cb.checked = source.checked);

    }

</script>