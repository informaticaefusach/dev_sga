<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../db.php';

$mensaje = '';

function generarSlug($texto)
{
    $texto = strtolower($texto);

    /* REEMPLAZAR ACENTOS */
    $buscar = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'];
    $reemplazar = ['a', 'e', 'i', 'o', 'u', 'n', 'u'];

    $texto = str_replace($buscar, $reemplazar, $texto);

    /* ELIMINAR CARACTERES RAROS (PERO NO LETRAS) */
    $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);

    /* ESPACIOS A GUIONES */
    $texto = preg_replace('/[\s]+/', '-', $texto);

    /* LIMPIAR GUIONES */
    $texto = trim($texto, '-');

    return $texto;
}

/* =============================
   FUNCION LIMPIAR NUMEROS
============================= */

function limpiarNumero($valor, $tipo = 'int')
{
    if (!isset($valor) || $valor === '') {
        return null;
    }

    return ($tipo === 'int') ? intval($valor) : floatval($valor);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $pdo->beginTransaction();

        /* =============================
           DATOS LIMPIOS
        ============================= */

        $nombre = $_POST['curso_nombre'] ?? '';
        $slug = generarSlug($nombre);

        $horas = limpiarNumero($_POST['horas_cronologicas'] ?? null, 'int');
        $precio = limpiarNumero($_POST['curso_precio'] ?? null, 'float');

        /* =============================
           CURSO BASE
        ============================= */

        $stmt = $pdo->prepare("
            INSERT INTO dir_cursos_catalogo(
                curso_nombre, curso_slug, curso_modalidad,
                horas_cronologicas, curso_precio,
                curso_director, curso_codigo_sence,
                curso_area, curso_area_conocimiento,
                curso_contexto, curso_objetivo_general
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $nombre,
            $slug,
            $_POST['curso_modalidad'] ?? null,
            $_POST['horas_cronologicas'] ?? null,
            $precio,
            $_POST['curso_director'] ?? null,
            $_POST['curso_codigo_sence'] ?? null,
            $_POST['curso_area'] ?? null,
            $_POST['curso_area_conocimiento'] ?? null,
            $_POST['curso_contexto'] ?? null,
            $_POST['curso_objetivo_general'] ?? null
        ]);

        $curso_id = $pdo->lastInsertId();


        /* =============================
           CATEGORIA
        ============================= */

        if (!empty($_POST['categoria'])) {

            $pdo->prepare("
                INSERT INTO dir_cursos_categorias (nombre, descripcion)
                VALUES (?, ?)
            ")->execute([
                        $_POST['categoria'],
                        $_POST['categoria_descripcion']
                    ]);

            $categoria_id = $pdo->lastInsertId();

            $pdo->prepare("
                INSERT INTO dir_cursos_categoria_rel (curso_id, categoria_id)
                VALUES (?, ?)
            ")->execute([$curso_id, $categoria_id]);
        }


        /* =============================
           INSIGNIA
        ============================= */

        if (!empty($_POST['insignia_descripcion'])) {

            $pdo->prepare("
            INSERT INTO dir_cursos_insignias
            (curso_id, descripcion, habilidades, criterio_certificacion, imagen, url_credly)
            VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([
                        $curso_id,
                        $_POST['insignia_descripcion'] ?? null,
                        $_POST['insignia_habilidades'] ?? null,
                        $_POST['insignia_criterio'] ?? null,
                        $_POST['insignia_imagen'] ?? null,
                        $_POST['insignia_url'] ?? null
                    ]);
        }


        /* =============================
           PERFIL EGRESO
        ============================= */

        if (!empty($_POST['perfil_egreso'])) {

            $orden = 1;

            foreach ($_POST['perfil_egreso'] as $item) {

                if (trim($item) == '')
                    continue;

                $pdo->prepare("
                    INSERT INTO dir_cursos_perfil_egreso
                    (curso_id, descripcion, orden)
                    VALUES (?, ?, ?)
                ")->execute([$curso_id, $item, $orden]);

                $orden++;
            }
        }


        /* =============================
           REQUISITOS PREVIOS
        ============================= */

        if (!empty($_POST['requisitos_previos'])) {

            $orden = 1;

            foreach ($_POST['requisitos_previos'] as $item) {

                if (trim($item) == '')
                    continue;

                $pdo->prepare("
                    INSERT INTO dir_cursos_requisitos_previos
                    (curso_id, requisito, orden)
                    VALUES (?, ?, ?)
                ")->execute([$curso_id, $item, $orden]);

                $orden++;
            }
        }


        /* =============================
           CONTINUIDAD
        ============================= */

        if (!empty($_POST['continuidad'])) {

            $orden = 1;

            foreach ($_POST['continuidad'] as $item) {

                if (trim($item) == '')
                    continue;

                $pdo->prepare("
                    INSERT INTO dir_cursos_continuidad
                    (curso_id, curso_relacionado, orden)
                    VALUES (?, ?, ?)
                ")->execute([$curso_id, $item, $orden]);

                $orden++;
            }
        }


        /* =============================
           UNIDADES + CONTENIDOS
        ============================= */

        if (!empty($_POST['unidad_titulo'])) {

            $ordenUnidad = 1;

            foreach ($_POST['unidad_titulo'] as $i => $titulo) {

                if (trim($titulo) == '')
                    continue;

                $pdo->prepare("
                    INSERT INTO dir_cursos_unidades
                    (curso_id, numero_unidad, titulo_unidad, objetivo_unidad, horas_teoricas, horas_practicas, modalidad, orden)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([
                            $curso_id,
                            $ordenUnidad,
                            $titulo,
                            $_POST['unidad_objetivo'][$i] ?? '',
                            limpiarNumero($_POST['horas_teoricas'][$i] ?? '', 'int'),
                            limpiarNumero($_POST['horas_practicas'][$i] ?? '', 'int'),
                            $_POST['modalidad_unidad'][$i] ?? '',
                            $ordenUnidad
                        ]);

                $unidad_id = $pdo->lastInsertId();

                if (!empty($_POST['contenidos'][$i])) {

                    $ordenContenido = 1;

                    foreach ($_POST['contenidos'][$i] as $contenido) {

                        if (trim($contenido) == '')
                            continue;

                        $pdo->prepare("
                            INSERT INTO dir_cursos_unidades_contenidos
                            (unidad_id, contenido, orden)
                            VALUES (?, ?, ?)
                        ")->execute([
                                    $unidad_id,
                                    $contenido,
                                    $ordenContenido
                                ]);

                        $ordenContenido++;
                    }
                }

                $ordenUnidad++;
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

    <h2>Nuevo Curso</h2>


    <form method="POST">

        <!-- =============================
INFORMACIÓN GENERAL
============================= -->

        <h4>Información general</h4>

        <input name="curso_nombre" class="form-control mb-2" placeholder="Nombre del curso">
        <input name="curso_modalidad" class="form-control mb-2" placeholder="Modalidad">
        <input name="curso_director" class="form-control mb-2" placeholder="Director">
        <input name="curso_area_conocimiento" class="form-control mb-2" placeholder="Área conocimiento">
        <input name="curso_codigo_sence" class="form-control mb-2" placeholder="Codigo Sence">
        <input name="horas_cronologicas" class="form-control mb-2" placeholder="Horas Cronologicas">

        <hr>

        <!-- =============================
CONTEXTO
============================= -->

        <h4>Contexto</h4>
        <textarea name="curso_contexto" class="form-control mb-2"></textarea>

        <hr>

        <!-- =============================
OBJETIVO GENERAL
============================= -->

        <h4>Objetivo general</h4>
        <textarea name="curso_objetivo_general" class="form-control mb-2"></textarea>

        <hr>

        <!-- =============================
PERFIL DE EGRESO
============================= -->

        <h4>Perfil de egreso</h4>
        <div id="perfil-egreso-container"></div>
        <button type="button" onclick="agregarCampo('perfil-egreso-container','perfil_egreso[]')"
            class="btn btn-secondary mb-3">
            + Item
        </button>

        <hr>

        <!-- =============================
REQUISITOS PREVIOS
============================= -->

        <h4>Requisitos previos</h4>
        <div id="requisitos-previos-container"></div>
        <button type="button" onclick="agregarCampo('requisitos-previos-container','requisitos_previos[]')"
            class="btn btn-secondary mb-3">
            + Item
        </button>

        <hr>

        <!-- =============================
UNIDADES (CLAVE)
============================= -->

        <h4>Estructura de contenidos</h4>

        <div id="unidades-container"></div>

        <button type="button" onclick="agregarUnidad()" class="btn btn-success mb-3">
            + Unidad
        </button>

        <hr>

        <!-- =============================
INSIGNIA
============================= -->

        <h4>Insignia</h4>
        <textarea name="insignia_descripcion" class="form-control mb-2" placeholder="Descripción"></textarea>
        <input name="insignia_habilidades" class="form-control mb-2" placeholder="Habilidades">
        <input name="insignia_criterio" class="form-control mb-2" placeholder="Criterio">
        <input type="file" name="insignia_imagen" class="form-control mb-2" placeholder="Imagen Insignia">
        <input name="insignia_url" class="form-control mb-2" placeholder="URL">

        <hr>


        <!-- =============================
CONTINUIDAD
============================= -->

        <h4>Continuidad</h4>
        <div id="continuidad-container"></div>
        <button type="button" onclick="agregarCampo('continuidad-container','continuidad[]')"
            class="btn btn-secondary mb-3">
            + Item
        </button>

        <hr>

        <button class="btn btn-primary">Guardar Curso</button>

    </form>

</div>

<script src="assets/js/cursos.js"></script>