<?php

session_start();

if (!isset($_SESSION['excel_diplomados']) || empty($_SESSION['excel_diplomados'])) {
    header("Location: index.php?page=importar_diplomados");
    exit;
}

$registros = $_SESSION['excel_diplomados'];

?>

<div class="container-fluid">

    <h2 class="mb-4">Revision de importacion de diplomados</h2>

    <div class="card shadow">
        <div class="card-body">

            <div class="mb-3">
                <p class="mb-0">
                    Se detectaron <strong>
                        <?= count($registros) ?>
                    </strong> registros en el archivo cargado.
                </p>
            </div>

            <form method="POST" action="index.php?page=procesar_importacion_diplomados">

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">
                        Guardar registros
                    </button>

                    <a href="index.php?page=importar_diplomados" class="btn btn-secondary">
                        Volver
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Programa</th>
                                <th>Nombre</th>
                                <th>Apellido paterno</th>
                                <th>Apellido materno</th>
                                <th>Email</th>
                                <th>RUT</th>
                                <th>Nota</th>
                                <th>Aprobado</th>
                                <th>Horas</th>
                                <th>Fecha inicio</th>
                                <th>Fecha termino</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros as $i => $r): ?>
                                <tr>
                                    <td>
                                        <?= $i + 1 ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['nombre_programa']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['nombre']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['apellido_paterno']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['apellido_materno']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['email']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['rut']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['nota']) ?>
                                    </td>
                                    <td>
                                        <?= !empty($r['aprobado']) ? 'Si' : 'No' ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['horas']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['fecha_inicio']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['fecha_termino']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </form>

        </div>
    </div>

</div>