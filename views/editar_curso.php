<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';

/* =============================
   ID
============================= */

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID no vÃ¡lido");
}

/* =============================
   FUNCIONES
============================= */

function generarSlug($texto)
{
    $texto = strtolower($texto);
    $buscar = ['Ã¡', 'Ã©', 'Ã­', 'Ã³', 'Ãº', 'Ã±', 'Ã¼'];
    $reemplazar = ['a', 'e', 'i', 'o', 'u', 'n', 'u'];
    $texto = str_replace($buscar, $reemplazar, $texto);
    $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
    $texto = preg_replace('/[\s]+/', '-', $texto);
    return trim($texto, '-');
}

function limpiarNumero($valor, $tipo = 'int')
{
    if (!isset($valor) || $valor === '')
        return null;
    return ($tipo === 'int') ? intval($valor) : floatval($valor);
}

/* =============================
   CARGA DATOS
============================= */

$curso = $pdo->prepare("SELECT * FROM dir_cursos_catalogo WHERE id=?");
$curso->execute([$id]);
$curso = $curso->fetch(PDO::FETCH_ASSOC);
/* =============================
   VER SI EXISTE HEADER
============================= */

$extensiones = ['jpg', 'png', 'webp'];
$header_actual = null;

foreach ($extensiones as $ext) {

    // Ruta física (servidor)
    $ruta_fisica = IMG_PATH . "header{$id}." . $ext;


    if (file_exists($ruta_fisica)) {

        // Ruta pública (navegador)
        $header_actual = base_url() . "/landing/img/header{$id}." . $ext;

        break;
    }
}

$perfil = $pdo->prepare("SELECT * FROM dir_cursos_perfil_egreso WHERE curso_id=? ORDER BY orden");
$perfil->execute([$id]);
$perfil = $perfil->fetchAll();

$requisitos = $pdo->prepare("SELECT * FROM dir_cursos_requisitos_previos WHERE curso_id=? ORDER BY orden");
$requisitos->execute([$id]);
$requisitos = $requisitos->fetchAll();

$continuidad = $pdo->prepare("SELECT * FROM dir_cursos_continuidad WHERE curso_id=? ORDER BY orden");
$continuidad->execute([$id]);
$continuidad = $continuidad->fetchAll();

$insignia = $pdo->prepare("SELECT * FROM dir_cursos_insignias WHERE curso_id=?");
$insignia->execute([$id]);
$insignia = $insignia->fetch();

$unidades = $pdo->prepare("SELECT * FROM dir_cursos_unidades WHERE curso_id=? ORDER BY orden");
$unidades->execute([$id]);
$unidades = $unidades->fetchAll();

$contenidosMap = [];
foreach ($unidades as $u) {
    $c = $pdo->prepare("SELECT * FROM dir_cursos_unidades_contenidos WHERE unidad_id=? ORDER BY orden");
    $c->execute([$u['id']]);
    $contenidosMap[$u['id']] = $c->fetchAll();
}

/* =============================
   GUARDAR
============================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $pdo->beginTransaction();

        $nombre = $_POST['curso_nombre'] ?? '';
        $slug = generarSlug($nombre);

        $precio = limpiarNumero($_POST['curso_precio'] ?? null, 'float');

        /* =============================
           DATOS SEGUROS
        ============================= */

        $curso_modalidad = $_POST['curso_modalidad'] ?? null;
        $curso_codigo_sence = $_POST['curso_codigo_sence'] ?? null;
        $curso_area = null;
        $curso_director = $_POST['curso_director'] ?? null;
        $curso_area_conocimiento = $_POST['curso_area_conocimiento'] ?? null;
        $curso_contexto = $_POST['curso_contexto'] ?? null;
        $curso_objetivo = $_POST['curso_objetivo_general'] ?? null;
        $curso_docente = $_POST['curso_docente'] ?? null;
        $curso_ayudante = $_POST['curso_ayudante'] ?? null;

        /* =============================
           NUMÉRICOS (IMPORTANTE)
        ============================= */

        $horas = $_POST['horas_cronologicas'] ?? null;
        $horas = ($horas === '' || $horas === null) ? null : (int) $horas;

        /* =============================
           UPDATE CURSO
        ============================= */

        $pdo->prepare("
            UPDATE dir_cursos_catalogo SET
            curso_nombre = ?, 
            curso_slug = ?, 
            curso_modalidad = ?,
            horas_cronologicas = ?, 
            curso_precio = ?,
            curso_director = ?, 
            curso_codigo_sence = ?,
            curso_area = ?, 
            curso_area_conocimiento = ?,
            curso_contexto = ?, 
            curso_objetivo_general = ?,
            curso_docente = ?, 
            curso_ayudante = ?
          WHERE id = ?
        ")->execute([
                    $nombre,
                    $slug,
                    $curso_modalidad,
                    $horas,
                    $precio,
                    $curso_director,
                    $curso_codigo_sence,
                    $curso_area,
                    $curso_area_conocimiento,
                    $curso_contexto,
                    $curso_objetivo,
                    $curso_docente,
                    $curso_ayudante,
                    $id
                ]);

        /* LIMPIAR */
        $pdo->prepare("DELETE FROM dir_cursos_perfil_egreso WHERE curso_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM dir_cursos_requisitos_previos WHERE curso_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM dir_cursos_continuidad WHERE curso_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM dir_cursos_insignias WHERE curso_id=?")->execute([$id]);

        $ids = $pdo->prepare("SELECT id FROM dir_cursos_unidades WHERE curso_id=?");
        $ids->execute([$id]);
        foreach ($ids->fetchAll() as $u) {
            $pdo->prepare("DELETE FROM dir_cursos_unidades_contenidos WHERE unidad_id=?")->execute([$u['id']]);
        }
        $pdo->prepare("DELETE FROM dir_cursos_unidades WHERE curso_id=?")->execute([$id]);

        /* PERFIL */
        foreach ($_POST['perfil_egreso'] ?? [] as $i => $p) {
            if (trim($p) != '') {
                $pdo->prepare("INSERT INTO dir_cursos_perfil_egreso (curso_id, descripcion, orden) VALUES (?,?,?)")
                    ->execute([$id, $p, $i + 1]);
            }
        }

        /* REQUISITOS */
        foreach ($_POST['requisitos_previos'] ?? [] as $i => $r) {
            if (trim($r) != '') {
                $pdo->prepare("INSERT INTO dir_cursos_requisitos_previos (curso_id, requisito, orden) VALUES (?,?,?)")
                    ->execute([$id, $r, $i + 1]);
            }
        }

        /* CONTINUIDAD */
        foreach ($_POST['continuidad'] ?? [] as $i => $c) {
            if (trim($c) != '') {
                $pdo->prepare("INSERT INTO dir_cursos_continuidad (curso_id, curso_relacionado, orden) VALUES (?,?,?)")
                    ->execute([$id, $c, $i + 1]);
            }
        }

        /* INSIGNIA */
        if (!empty($_POST['insignia_descripcion'])) {
            $pdo->prepare("
                INSERT INTO dir_cursos_insignias
                (curso_id, descripcion, habilidades, criterio_certificacion, imagen, url_credly)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([
                        $id,
                        $_POST['insignia_descripcion'],
                        $_POST['insignia_habilidades'],
                        $_POST['insignia_criterio'],
                        $_POST['insignia_imagen'],
                        $_POST['insignia_url']
                    ]);
        }

        if (isset($_FILES['header_imagen']) && $_FILES['header_imagen']['error'] === 0) {

            $ruta_destino = IMG_PATH;


            $tmp = $_FILES['header_imagen']['tmp_name'];

            $info = getimagesize($tmp);

            if ($info !== false) {

                $mime = $info['mime'];

                switch ($mime) {
                    case 'image/jpeg':
                        $ext = 'jpg';
                        break;
                    case 'image/png':
                        $ext = 'png';
                        break;
                    case 'image/webp':
                        $ext = 'webp';
                        break;
                    default:
                        throw new Exception("Formato de imagen no permitido");
                }

                // 🔥 BORRAR IMAGEN ANTERIOR (si existe)
                foreach (['jpg', 'png', 'webp'] as $e) {
                    $old = $ruta_destino . "header{$id}." . $e;
                    if (file_exists($old)) {
                        unlink($old);
                    }
                }

                $nombre = "header{$id}." . $ext;

                move_uploaded_file($tmp, $ruta_destino . $nombre);

            } else {
                throw new Exception("Archivo no válido");
            }
        }

        /* UNIDADES */
        if (!empty($_POST['unidad_titulo'])) {

            foreach ($_POST['unidad_titulo'] as $i => $titulo) {

                if (trim($titulo) == '')
                    continue;

                $pdo->prepare("
                    INSERT INTO dir_cursos_unidades
                    (curso_id, numero_unidad, titulo_unidad, objetivo_unidad, horas_teoricas, horas_practicas, modalidad, orden)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([
                            $id,
                            $i + 1,
                            $titulo,
                            $_POST['unidad_objetivo'][$i] ?? '',
                            limpiarNumero($_POST['horas_teoricas'][$i] ?? ''),
                            limpiarNumero($_POST['horas_practicas'][$i] ?? ''),
                            $_POST['modalidad_unidad'][$i] ?? '',
                            $i + 1
                        ]);

                $unidad_id = $pdo->lastInsertId();

                foreach ($_POST['contenidos'][$i] ?? [] as $j => $cont) {
                    if (trim($cont) != '') {
                        $pdo->prepare("
                            INSERT INTO dir_cursos_unidades_contenidos
                            (unidad_id, contenido, orden)
                            VALUES (?, ?, ?)
                        ")->execute([$unidad_id, $cont, $j + 1]);
                    }
                }
            }
        }

        $pdo->commit();
        header("Location: index.php?page=cursos");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("ERROR: " . $e->getMessage());
    }
}
?>

<div class="container-fluid">

    <h2>Editar Curso</h2>

    <form method="POST" enctype="multipart/form-data">

        <h4>Información general</h4>

        <div class="form-group">
            <label>Nombre del curso</label>
            <input name="curso_nombre" class="form-control mb-2" value="<?= $curso['curso_nombre'] ?>">
        </div>

        <div class="form-group">
            <label>Director</label>
            <input name="curso_director" class="form-control mb-2" value="<?= $curso['curso_director'] ?>">
        </div>

        <div class="form-group">
            <label>Área de conocimiento</label>
            <input name="curso_area_conocimiento" class="form-control mb-2"
                value="<?= $curso['curso_area_conocimiento'] ?>">
        </div>

        <div class="form-group">
            <label>Docente</label>
            <input name="curso_docente" class="form-control mb-2" value="<?= $curso['curso_docente'] ?? '' ?>">
        </div>

        <div class="form-group">
            <label>Ayudante</label>
            <input name="curso_ayudante" class="form-control mb-2" value="<?= $curso['curso_ayudante'] ?? '' ?>">
        </div>

        <div class="form-group">
            <label>Horas cronológicas</label>
            <input name="horas_cronologicas" class="form-control mb-2" value="<?= $curso['horas_cronologicas'] ?>">
        </div>

        <div class="form-group">
            <label>Modalidad</label>
            <input name="curso_modalidad" class="form-control mb-2" value="<?= $curso['curso_modalidad'] ?? '' ?>">
        </div>

        <div class="form-group">
            <label>Código SENCE</label>
            <input name="curso_codigo_sence" class="form-control mb-2"
                value="<?= $curso['curso_codigo_sence'] ?? '' ?>">
        </div>

        <h4>Imagen Header</h4>

        <div class="form-group">

            <?php if ($header_actual): ?>
                <p class="text-success">
                    ✔ Este curso YA tiene imagen
                </p>

                <img src="<?= $header_actual ?>" style="max-width:300px; display:block; margin-bottom:10px;">
            <?php else: ?>
                <p class="text-danger">
                    ⚠ Este curso NO tiene imagen
                </p>
            <?php endif; ?>

            <label>Reemplazar / Subir nueva imagen</label>
            <input type="file" name="header_imagen" class="form-control" accept="image/*">

        </div>

        <hr>

        <h4>Contexto</h4>

        <div class="form-group">
            <label>Contexto del curso</label>
            <textarea name="curso_contexto" class="form-control mb-2"><?= $curso['curso_contexto'] ?></textarea>
        </div>

        <hr>

        <h4>Objetivo general</h4>

        <div class="form-group">
            <label>Objetivo general</label>
            <textarea name="curso_objetivo_general"
                class="form-control mb-2"><?= $curso['curso_objetivo_general'] ?></textarea>
        </div>

        <hr>

        <h4>Perfil de egreso</h4>

        <div id="perfil-egreso-container">
            <?php foreach ($perfil as $p): ?>
                <div class="d-flex mb-2 gap-2">
                    <input name="perfil_egreso[]" class="form-control" value="<?= $p['descripcion'] ?>">
                    <button type="button" class="btn btn-danger" onclick="this.parentNode.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" onclick="agregarCampo('perfil-egreso-container','perfil_egreso[]')"
            class="btn btn-secondary mb-3">
            + Item
        </button>

        <hr>

        <h4>Requisitos previos</h4>

        <div id="requisitos-previos-container">
            <?php foreach ($requisitos as $r): ?>
                <div class="d-flex mb-2 gap-2">
                    <input name="requisitos_previos[]" class="form-control" value="<?= $r['requisito'] ?>">
                    <button type="button" class="btn btn-danger" onclick="this.parentNode.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" onclick="agregarCampo('requisitos-previos-container','requisitos_previos[]')"
            class="btn btn-secondary mb-3">
            + Item
        </button>

        <hr>

        <h4>Continuidad</h4>

        <div id="continuidad-container">
            <?php foreach ($continuidad as $c): ?>
                <div class="d-flex mb-2 gap-2">
                    <input name="continuidad[]" class="form-control" value="<?= $c['curso_relacionado'] ?>">
                    <button type="button" class="btn btn-danger" onclick="this.parentNode.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" onclick="agregarCampo('continuidad-container','continuidad[]')"
            class="btn btn-secondary mb-3">
            + Item
        </button>

        <hr>

        <h4>Estructura de contenidos</h4>

        <div id="unidades-container">

            <?php foreach ($unidades as $i => $u): ?>
                <div class="card mb-3 p-3 unidad-item">

                    <div class="d-flex justify-content-between mb-2">
                        <strong>Unidad
                            <?= $i + 1 ?>
                        </strong>
                        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.unidad-item').remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="form-group">
                        <label>Título unidad</label>
                        <input name="unidad_titulo[]" class="form-control mb-2" value="<?= $u['titulo_unidad'] ?>">
                    </div>

                    <div class="form-group">
                        <label>Objetivo unidad</label>
                        <textarea name="unidad_objetivo[]" class="form-control mb-2"><?= $u['objetivo_unidad'] ?></textarea>
                    </div>

                    <div class="row mb-2">
                        <div class="col">
                            <label>Horas teóricas</label>
                            <input name="horas_teoricas[]" class="form-control" value="<?= $u['horas_teoricas'] ?>">
                        </div>
                        <div class="col">
                            <label>Horas prácticas</label>
                            <input name="horas_practicas[]" class="form-control" value="<?= $u['horas_practicas'] ?>">
                        </div>
                        <div class="col">
                            <label>Modalidad</label>
                            <input name="modalidad_unidad[]" class="form-control" value="<?= $u['modalidad'] ?>">
                        </div>
                    </div>

                    <div id="contenidos-<?= $i ?>">
                        <?php foreach ($contenidosMap[$u['id']] ?? [] as $c): ?>
                            <div class="d-flex mb-2 gap-2 contenido-item">
                                <input name="contenidos[<?= $i ?>][]" class="form-control" value="<?= $c['contenido'] ?>">
                                <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" onclick="agregarContenido(<?= $i ?>)" class="btn btn-secondary btn-sm mt-2">
                        + Contenido
                    </button>

                </div>
            <?php endforeach; ?>

        </div>

        <button type="button" onclick="agregarUnidad()" class="btn btn-success mb-3">
            + Unidad
        </button>

        <hr>

        <h4>Insignia</h4>

        <div class="form-group">
            <label>Descripción</label>
            <input name="insignia_descripcion" class="form-control mb-2" value="<?= $insignia['descripcion'] ?? '' ?>">
        </div>

        <div class="form-group">
            <label>Habilidades</label>
            <input name="insignia_habilidades" class="form-control mb-2" value="<?= $insignia['habilidades'] ?? '' ?>">
        </div>

        <div class="form-group">
            <label>Criterio</label>
            <input name="insignia_criterio" class="form-control mb-2"
                value="<?= $insignia['criterio_certificacion'] ?? '' ?>">
        </div>

        <div class="form-group">
            <label>Imagen</label>
            <input name="insignia_imagen" class="form-control mb-2" value="<?= $insignia['imagen'] ?? '' ?>">
        </div>

        <div class="form-group">
            <label>URL</label>
            <input name="insignia_url" class="form-control mb-2" value="<?= $insignia['url_credly'] ?? '' ?>">
        </div>

        <hr>

        <button class="btn btn-primary">Guardar Curso</button>

    </form>

</div>

<script src="assets/js/cursos.js"></script>