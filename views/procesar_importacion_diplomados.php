<?php

session_start();

require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['excel_diplomados']) || empty($_SESSION['excel_diplomados'])) {
    header("Location: index.php?page=importar_diplomados");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?page=importar_diplomados");
    exit;
}

$registros = $_SESSION['excel_diplomados'];

$insertados = 0;
$actualizados = 0;
$omitidos = 0;

function normalizarFechaDiplomado($fecha)
{
    if (!$fecha) {
        return null;
    }

    $fecha = trim((string) $fecha);

    if ($fecha === '') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return $fecha;
    }

    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $fecha)) {
        $partes = explode('-', $fecha);
        return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
    }

    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha)) {
        $partes = explode('/', $fecha);
        return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
    }

    $timestamp = strtotime($fecha);

    if ($timestamp) {
        return date('Y-m-d', $timestamp);
    }

    return null;
}

function valorComparable($valor)
{
    if ($valor === null) {
        return '';
    }

    if (is_bool($valor)) {
        return $valor ? '1' : '0';
    }

    return trim((string) $valor);
}

function registroCambio($existente, $nuevo)
{
    $campos = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'email',
        'rut',
        'nota',
        'aprobado',
        'horas',
        'fecha_inicio',
        'fecha_termino'
    ];

    foreach ($campos as $campo) {
        if (valorComparable($existente[$campo] ?? null) !== valorComparable($nuevo[$campo] ?? null)) {
            return true;
        }
    }

    return false;
}

try {
    $pdo->beginTransaction();

    $stmtBuscarPorRut = $pdo->prepare("
        SELECT *
        FROM dir_diplomados_registros
        WHERE nombre_programa = ?
          AND rut = ?
        LIMIT 1
    ");

    $stmtBuscarPorEmail = $pdo->prepare("
        SELECT *
        FROM dir_diplomados_registros
        WHERE nombre_programa = ?
          AND email = ?
        LIMIT 1
    ");

    $stmtBuscarPorNombre = $pdo->prepare("
        SELECT *
        FROM dir_diplomados_registros
        WHERE nombre_programa = ?
          AND nombre = ?
          AND apellido_paterno = ?
          AND apellido_materno = ?
        LIMIT 1
    ");

    $stmtInsert = $pdo->prepare("
        INSERT INTO dir_diplomados_registros (
            nombre_programa,
            nombre,
            apellido_paterno,
            apellido_materno,
            email,
            rut,
            nota,
            aprobado,
            horas,
            fecha_inicio,
            fecha_termino
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtUpdate = $pdo->prepare("
        UPDATE dir_diplomados_registros
        SET
            nombre = ?,
            apellido_paterno = ?,
            apellido_materno = ?,
            email = ?,
            rut = ?,
            nota = ?,
            aprobado = ?,
            horas = ?,
            fecha_inicio = ?,
            fecha_termino = ?
        WHERE id = ?
    ");

    foreach ($registros as $r) {
        $nombrePrograma = trim((string) ($r['nombre_programa'] ?? ''));
        $nombre = trim((string) ($r['nombre'] ?? ''));
        $apellidoPaterno = trim((string) ($r['apellido_paterno'] ?? ''));
        $apellidoMaterno = trim((string) ($r['apellido_materno'] ?? ''));
        $email = trim((string) ($r['email'] ?? ''));
        $rut = trim((string) ($r['rut'] ?? ''));

        if ($nombrePrograma === '' || $nombre === '') {
            $omitidos++;
            continue;
        }

        $nuevoRegistro = [
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'email' => $email !== '' ? $email : null,
            'rut' => $rut !== '' ? $rut : null,
            'nota' => $r['nota'] ?? null,
            'aprobado' => $r['aprobado'] ?? null,
            'horas' => $r['horas'] ?? null,
            'fecha_inicio' => normalizarFechaDiplomado($r['fecha_inicio'] ?? null),
            'fecha_termino' => normalizarFechaDiplomado($r['fecha_termino'] ?? null),
        ];

        $existente = null;

        if ($rut !== '') {
            $stmtBuscarPorRut->execute([$nombrePrograma, $rut]);
            $existente = $stmtBuscarPorRut->fetch();
        }

        if (!$existente && $email !== '') {
            $stmtBuscarPorEmail->execute([$nombrePrograma, $email]);
            $existente = $stmtBuscarPorEmail->fetch();
        }

        if (!$existente) {
            $stmtBuscarPorNombre->execute([
                $nombrePrograma,
                $nombre,
                $apellidoPaterno,
                $apellidoMaterno
            ]);
            $existente = $stmtBuscarPorNombre->fetch();
        }

        if (!$existente) {
            $stmtInsert->execute([
                $nombrePrograma,
                $nuevoRegistro['nombre'],
                $nuevoRegistro['apellido_paterno'],
                $nuevoRegistro['apellido_materno'],
                $nuevoRegistro['email'],
                $nuevoRegistro['rut'],
                $nuevoRegistro['nota'],
                $nuevoRegistro['aprobado'],
                $nuevoRegistro['horas'],
                $nuevoRegistro['fecha_inicio'],
                $nuevoRegistro['fecha_termino']
            ]);

            $insertados++;
            continue;
        }

        if (registroCambio($existente, $nuevoRegistro)) {
            $stmtUpdate->execute([
                $nuevoRegistro['nombre'],
                $nuevoRegistro['apellido_paterno'],
                $nuevoRegistro['apellido_materno'],
                $nuevoRegistro['email'],
                $nuevoRegistro['rut'],
                $nuevoRegistro['nota'],
                $nuevoRegistro['aprobado'],
                $nuevoRegistro['horas'],
                $nuevoRegistro['fecha_inicio'],
                $nuevoRegistro['fecha_termino'],
                $existente['id']
            ]);

            $actualizados++;
        } else {
            $omitidos++;
        }
    }

    $pdo->commit();

    unset($_SESSION['excel_diplomados']);

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error al procesar la importacion: " . $e->getMessage());
}

?>

<div class="container-fluid">

    <h2 class="mb-4">Importacion completada</h2>

    <div class="alert alert-success">
        <strong>Registros insertados:</strong>
        <?= $insertados ?><br>
        <strong>Registros actualizados:</strong>
        <?= $actualizados ?><br>
        <strong>Registros omitidos:</strong>
        <?= $omitidos ?>
    </div>

    <a href="index.php?page=importar_diplomados" class="btn btn-secondary">
        Importar otro archivo
    </a>

    <a href="index.php?page=listado_diplomados" class="btn btn-primary">
        Ver registros importados
    </a>

</div>