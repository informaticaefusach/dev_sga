<?php

session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        if (!isset($_FILES['archivo'])) {
            throw new Exception("No se subió archivo");
        }

        $archivo = $_FILES['archivo']['tmp_name'];

        $spreadsheet = IOFactory::load($archivo);

        $sheet = $spreadsheet->getActiveSheet();

        /* =====================================
           FILA DONDE ESTÁN LOS ENCABEZADOS
        ===================================== */

        $fila_encabezado = 4;

        /* LEER ENCABEZADOS (B5:F5) */

        $encabezados = $sheet->rangeToArray(
            'A' . $fila_encabezado . ':G' . $fila_encabezado
        )[0];

        /* MAPEAR COLUMNAS */

        $map = [];

        foreach ($encabezados as $i => $col) {

            $col = strtolower(trim($col));
            if ($col == 'nombre programa/ curso' || $col == 'nombre programa' || $col == 'curso') {
                $map['curso'] = $i;
            }

            if ($col == 'nombre') {
                $map['nombre'] = $i;
            }

            if ($col == 'ap_paterno') {
                $map['apellido_paterno'] = $i;
            }

            if ($col == 'ap_materno') {
                $map['apellido_materno'] = $i;
            }

            if ($col == 'email') {
                $map['email'] = $i;
            }

            if ($col == 'nota') {
                $map['nota'] = $i;
            }

        }

        if (!isset($map['nombre'])) {
            throw new Exception("No se encontró la columna NOMBRE en la fila 5");
        }
        if (!isset($map['curso'])) {
            throw new Exception("No se encontró la columna CURSO en el Excel");
        }

        /* =====================================
           EXTRAER ALUMNOS
        ===================================== */

        $fila = $fila_encabezado + 1;

        $alumnos = [];

        while (true) {

            $row = $sheet->rangeToArray(
                'A' . $fila . ':G' . $fila
            )[0];

            $curso = $row[$map['curso']] ?? '';
            $nombre = $row[$map['nombre']] ?? '';
            $apellido_p = $row[$map['apellido_paterno']] ?? '';
            $apellido_m = $row[$map['apellido_materno']] ?? '';
            $email = $row[$map['email']] ?? '';
            $nota = $row[$map['nota']] ?? '';

            /* si no hay nombre, detener lectura */

            if ($nombre == '' || $nombre == null) {
                break;
            }

            $alumnos[] = [
                'curso_excel' => $curso,
                'nombre' => $nombre,
                'apellido_paterno' => $apellido_p,
                'apellido_materno' => $apellido_m,
                'email' => $email,
                'nota' => $nota
            ];

            $fila++;

        }

        if (count($alumnos) == 0) {
            throw new Exception("No se encontraron alumnos válidos");
        }


        /* =====================================
           GUARDAR EN SESSION
        ===================================== */

        $_SESSION['excel_alumnos'] = $alumnos;

        echo "<script>window.location='index.php?page=revisar_importacion';</script>";
        exit;

    } catch (Exception $e) {

        $mensaje = "Error: " . $e->getMessage();

    }

}

?>

<div class="container-fluid">

    <h2>Importar alumnos desde Excel</h2>

    <?php if ($mensaje): ?>

        <div class="alert alert-danger">

            <?= $mensaje ?>

        </div>

    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="card shadow">

            <div class="card-body">

                <div class="mb-3">

                    <label>Archivo Excel</label>

                    <input type="file" name="archivo" class="form-control" required>

                </div>

                <button class="btn btn-success">

                    Subir Excel

                </button>

            </div>

        </div>

    </form>

</div>