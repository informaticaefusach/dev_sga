<?php

try {

    $stmt = $pdo->query("
        SELECT 
            id,
            curso_nombre,
            curso_slug,
            curso_modalidad,
            curso_area,
            curso_codigo_sence,
            curso_director,
            horas_cronologicas,
            curso_estado
        FROM dir_cursos_catalogo
        ORDER BY id DESC
    ");

    $cursos = $stmt->fetchAll();

} catch (PDOException $e) {

    $cursos = [];

}

?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2>Catálogo de Cursos</h2>

        <div>

            <a href="index.php?page=importar_cursos" class="btn btn-success me-2">
                <i class="fas fa-file-import"></i> Importar Excel / CSV
            </a>

            <a href="index.php?page=nuevo_curso" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Curso
            </a>

        </div>

    </div>


    <div class="card shadow">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover">

                    <thead class="table-dark">

                        <tr>

                            <th>ID</th>
                            <th>Curso</th>
                            <th>Área</th>
                            <th>Código SENCE</th>
                            <th>Director</th>
                            <th>Modalidad</th>
                            <th>Horas</th>
                            <th>Estado</th>
                            <th width="220">Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($cursos)): ?>

                            <tr>
                                <td colspan="9" class="text-center">
                                    No hay cursos registrados
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($cursos as $curso): ?>

                                <tr>

                                    <td>
                                        <?php echo $curso['id']; ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?php echo htmlspecialchars($curso['curso_nombre']); ?>
                                        </strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo $curso['curso_slug']; ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($curso['curso_area']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($curso['curso_codigo_sence']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($curso['curso_director']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($curso['curso_modalidad']); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($curso['horas_cronologicas']); ?> hrs
                                    </td>

                                    <td>

                                        <?php if ($curso['curso_estado'] == 1): ?>

                                            <span class="badge bg-success">
                                                Activo
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-secondary">
                                                Inactivo
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <a href="index.php?page=editar_curso&id=<?php echo $curso['id']; ?>"
                                            class="btn btn-sm btn-warning"
                                            title="Editar curso">

                                            <i class="fas fa-edit"></i>

                                        </a>

                                        <a href="index.php?page=ediciones&curso_id=<?php echo $curso['id']; ?>"
                                            class="btn btn-sm btn-info"
                                            title="Ver ediciones">

                                            <i class="fas fa-layer-group"></i>

                                        </a>

                                        <a href="../landing/curso/<?php echo $curso['curso_slug']; ?>"
                                            target="_blank"
                                            class="btn btn-sm btn-success"
                                            title="Ver landing">

                                            <i class="fas fa-external-link-alt"></i>

                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>