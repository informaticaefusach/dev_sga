<?php

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $stmt = $pdo->prepare("
        INSERT INTO dir_cursos_alumnos
        (
        nombre,
        apellido_paterno,
        apellido_materno,
        rut,
        email,
        telefono
        )
        VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['nombre'],
            $_POST['apellido_paterno'],
            $_POST['apellido_materno'],
            $_POST['rut'],
            $_POST['email'],
            $_POST['telefono']
        ]);

        header("Location: index.php?page=alumnos");
        exit;

    } catch (PDOException $e) {

        $mensaje = "Error al crear alumno";

    }

}

?>

<div class="container-fluid">

    <h2 class="mb-4">Nuevo Alumno</h2>

    <?php if ($mensaje): ?>

        <div class="alert alert-danger">

            <?= $mensaje ?>

        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="row">

            <div class="col-md-4 mb-3">

                <label>Nombre</label>
                <input type="text" name="nombre" class="form-control" required>

            </div>

            <div class="col-md-4 mb-3">

                <label>Apellido Paterno</label>
                <input type="text" name="apellido_paterno" class="form-control">

            </div>

            <div class="col-md-4 mb-3">

                <label>Apellido Materno</label>
                <input type="text" name="apellido_materno" class="form-control">

            </div>

            <div class="col-md-4 mb-3">

                <label>RUT</label>
                <input type="text" name="rut" class="form-control">

            </div>

            <div class="col-md-4 mb-3">

                <label>Email</label>
                <input type="email" name="email" class="form-control">

            </div>

            <div class="col-md-4 mb-3">

                <label>Teléfono</label>
                <input type="text" name="telefono" class="form-control">

            </div>

        </div>

        <button class="btn btn-primary">
            Guardar alumno
        </button>

    </form>

</div>