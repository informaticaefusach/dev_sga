<?php

session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$mensaje = '';

function leerFechaExcel($sheet, $columnaIndice, $fila, $valorCrudo)
{
    $columnaExcel = Coordinate::stringFromColumnIndex($columnaIndice + 1);
    $celda = $sheet->getCell($columnaExcel . $fila);
    $valor = $celda->getValue();

    if ($valor === null || $valor === '') {
        return null;
    }

    if (is_numeric($valor)) {
        try {
            return ExcelDate::excelToDateTimeObject($valor)->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    $texto = trim((string) $valorCrudo);

    if ($texto === '') {
        return null;
    }

    $texto = str_replace('/', '-', $texto);

    if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $texto)) {
        $partes = explode('-', $texto);
        return sprintf('%04d-%02d-%02d', $partes[2], $partes[1], $partes[0]);
    }

    $timestamp = strtotime($texto);

    if ($timestamp) {
        return date('Y-m-d', $timestamp);
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['archivo']) || empty($_FILES['archivo']['tmp_name'])) {
            throw new Exception("No se subio ningun archivo.");
        }

        $archivo = $_FILES['archivo']['tmp_name'];

        $spreadsheet = IOFactory::load($archivo);
        $sheet = $spreadsheet->getActiveSheet();

        /* =====================================
           FILA DE ENCABEZADOS REAL DEL EXCEL
        ===================================== */
        $fila_encabezado = 4;

        $encabezados = $sheet->rangeToArray(
            'A' . $fila_encabezado . ':Z' . $fila_encabezado,
            null,
            true,
            true,
            false
        )[0];

        $map = [];

        foreach ($encabezados as $i => $col) {
            $col = trim((string) $col);
            $col_normalizada = mb_strtolower($col, 'UTF-8');

            $col_normalizada = str_replace(["\n", "\r", "\t"], ' ', $col_normalizada);
            $col_normalizada = preg_replace('/\s+/', ' ', $col_normalizada);
            $col_normalizada = str_replace('_', ' ', $col_normalizada);
            $col_normalizada = trim($col_normalizada);

            if (
                $col_normalizada === 'nombre programa/ curso' ||
                $col_normalizada === 'nombre programa / curso' ||
                $col_normalizada === 'nombre programa/curso'
            ) {
                $map['nombre_programa'] = $i;
            }

            if ($col_normalizada === 'nombre') {
                $map['nombre'] = $i;
            }

            if ($col_normalizada === 'ap paterno') {
                $map['apellido_paterno'] = $i;
            }

            if ($col_normalizada === 'ap materno') {
                $map['apellido_materno'] = $i;
            }

            if ($col_normalizada === 'email') {
                $map['email'] = $i;
            }

            if ($col_normalizada === 'rut') {
                $map['rut'] = $i;
            }

            if ($col_normalizada === 'nota' || $col_normalizada === 'nota final') {
                $map['nota'] = $i;
            }

            if ($col_normalizada === 'horas') {
                $map['horas'] = $i;
            }

            if ($col_normalizada === 'f inicio' || $col_normalizada === 'fecha inicio') {
                $map['fecha_inicio'] = $i;
            }

            if (
                $col_normalizada === 'f termino' ||
                $col_normalizada === 'f término' ||
                $col_normalizada === 'fecha termino' ||
                $col_normalizada === 'fecha término'
            ) {
                $map['fecha_termino'] = $i;
            }
        }

        $campos_requeridos = [
            'nombre_programa',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'email',
            'rut',
            'nota',
            'horas',
            'fecha_inicio',
            'fecha_termino'
        ];

        $labels_campos = [
            'nombre_programa' => 'NOMBRE PROGRAMA/ CURSO',
            'nombre' => 'NOMBRE',
            'apellido_paterno' => 'AP_PATERNO',
            'apellido_materno' => 'AP_MATERNO',
            'email' => 'EMAIL',
            'rut' => 'RUT',
            'nota' => 'NOTA',
            'horas' => 'Horas',
            'fecha_inicio' => 'F inicio',
            'fecha_termino' => 'F termino'
        ];

        foreach ($campos_requeridos as $campo) {
            if (!isset($map[$campo])) {
                throw new Exception("No se encontro la columna requerida en el Excel: " . $labels_campos[$campo]);
            }
        }

        /* =====================================
           EXTRAER FILAS
        ===================================== */
        $fila = $fila_encabezado + 1;
        $registros = [];

        while (true) {
            $row = $sheet->rangeToArray(
                'A' . $fila . ':Z' . $fila,
                null,
                true,
                true,
                false
            )[0];

            $nombrePrograma = trim((string) ($row[$map['nombre_programa']] ?? ''));
            $nombre = trim((string) ($row[$map['nombre']] ?? ''));
            $apellidoPaterno = trim((string) ($row[$map['apellido_paterno']] ?? ''));
            $apellidoMaterno = trim((string) ($row[$map['apellido_materno']] ?? ''));
            $email = trim((string) ($row[$map['email']] ?? ''));
            $rut = trim((string) ($row[$map['rut']] ?? ''));
            $notaExcel = trim((string) ($row[$map['nota']] ?? ''));
            $horas = trim((string) ($row[$map['horas']] ?? ''));

            $fechaInicio = leerFechaExcel($sheet, $map['fecha_inicio'], $fila, $row[$map['fecha_inicio']] ?? '');
            $fechaTermino = leerFechaExcel($sheet, $map['fecha_termino'], $fila, $row[$map['fecha_termino']] ?? '');

            if ($nombrePrograma === '' && $nombre === '' && $email === '') {
                break;
            }

            $nombreProgramaNormalizado = mb_strtolower($nombrePrograma, 'UTF-8');

            if (mb_strpos($nombreProgramaNormalizado, 'diplomado') === false) {
                $fila++;
                continue;
            }

            $nota = floatval(str_replace(',', '.', $notaExcel));

            if ($nota >= 10) {
                $nota = $nota / 10;
            }

            if ($nota > 7) {
                $nota = 7;
            }

            if ($nota < 1 && $nota > 0) {
                $nota = 1;
            }

            $nota = round($nota, 1);
            $aprobado = ($nota >= 4) ? 1 : 0;

            $registros[] = [
                'nombre_programa' => $nombrePrograma,
                'nombre' => $nombre,
                'apellido_paterno' => $apellidoPaterno,
                'apellido_materno' => $apellidoMaterno,
                'email' => $email,
                'rut' => $rut,
                'nota' => $nota,
                'aprobado' => $aprobado,
                'horas' => is_numeric($horas) ? (int) $horas : null,
                'fecha_inicio' => $fechaInicio,
                'fecha_termino' => $fechaTermino
            ];

            $fila++;
        }

        if (empty($registros)) {
            throw new Exception("No se encontraron registros de diplomados validos en el Excel.");
        }

        $_SESSION['excel_diplomados'] = $registros;

        header("Location: index.php?page=revisar_importacion_diplomados");
        exit;

    } catch (Exception $e) {
        $mensaje = $e->getMessage();
    }
}

?>

<div class="container-fluid">

    <h2 class="mb-4">Importar diplomados desde Excel</h2>

    <?php if ($mensaje): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Archivo Excel</label>
                    <input type="file" name="archivo" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    Cargar archivo
                </button>
            </form>

        </div>
    </div>

</div>