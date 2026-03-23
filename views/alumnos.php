<?php

$stmt = $pdo->query("
SELECT 
id,
nombre,
apellido_paterno,
apellido_materno,
email,
rut,
telefono
FROM dir_cursos_alumnos
ORDER BY id DESC
");

$alumnos = $stmt->fetchAll();

?>

<div class="container-fluid">

    <div class="d-flex justify-content-between mb-4">

        <h2>Alumnos</h2>

        <div>

            <a href="index.php?page=importar_alumnos" class="btn btn-success me-2">
                <i class="fas fa-file-import"></i> Importar Excel
            </a>

            <a href="index.php?page=nuevo_alumno" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Alumno
            </a>

        </div>

    </div>

    <div class="card shadow">

        <div class="card-body">

            <table class="table table-striped">

                <thead class="table-dark">

                    <tr>

                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($alumnos as $a): ?>

                        <tr>

                            <td>
                                <?= $a['id'] ?>
                            </td>

                            <td>
                                <?= $a['nombre'] ?>
                                <?= $a['apellido_paterno'] ?>
                                <?= $a['apellido_materno'] ?>
                            </td>

                            <td>
                                <?= $a['email'] ?>
                            </td>

                            <td>
                                <?= $a['telefono'] ?>
                            </td>

                            <td>

                                <a href="index.php?page=editar_alumno&id=<?= $a['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>