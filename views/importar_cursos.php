<?php $pageTitle = "Importación de Cursos"; ?>

<div class="container-fluid g-0">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">

        <h2 class="h3 mb-0 text-gray-800">Importar Cursos</h2>

        <div class="d-flex gap-2">
            <a href="index.php?page=cursos" class="btn btn-sm btn-outline-secondary">
                Volver a Cursos
            </a>
        </div>

    </div>


    <div class="card shadow mb-4">

        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Subir archivo y mapear columnas
            </h6>
        </div>


        <div class="card-body">

            <form id="importForm" method="post" enctype="multipart/form-data"
                action="views/importar_cursos_procesar.php">

                <div class="mb-3">

                    <label class="form-label">Archivo CSV</label>

                    <input type="file" name="file" id="fileInput" class="form-control" accept=".csv" required>

                    <div class="form-text">
                        Formato soportado: .csv
                    </div>

                </div>


                <div class="mb-4">

                    <h6 class="mb-2">Mapeo de columnas</h6>

                    <div class="table-responsive">

                        <table class="table table-bordered align-middle">

                            <thead class="table-light">
                                <tr>
                                    <th>Campo destino</th>
                                    <th>Columna origen</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php

                                $destinos = [

                                    'curso_nombre' => 'Nombre del Curso',
                                    'curso_descripcion_corta' => 'Descripción corta',
                                    'curso_descripcion_larga' => 'Descripción completa',
                                    'curso_modalidad' => 'Modalidad',
                                    'curso_duracion' => 'Duración',
                                    'curso_precio' => 'Precio'

                                ];

                                $opts = ['' => 'No importar'];

                                foreach (range('A', 'Z') as $c) {
                                    $opts[$c] = $c;
                                }

                                foreach ($destinos as $key => $label):

                                    ?>

                                    <tr>

                                        <td>
                                            <span class="fw-semibold"><?php echo $label; ?></span>
                                            <span class="text-muted">
                                                (<code><?php echo $key; ?></code>)
                                            </span>
                                        </td>

                                        <td>

                                            <select name="map[<?php echo $key; ?>]" class="form-select">

                                                <?php foreach ($opts as $val => $txt): ?>

                                                    <option value="<?php echo $val; ?>">
                                                        <?php echo $txt; ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>


                <div class="d-flex gap-2">

                    <button type="submit" class="btn btn-primary" id="btnProcess">

                        Procesar

                    </button>

                    <button type="reset" class="btn btn-outline-secondary">

                        Limpiar

                    </button>

                </div>

            </form>


            <div id="importResult" class="mt-4 d-none">

                <div class="alert" role="alert"></div>

                <div class="table-responsive mt-3">

                    <table class="table table-sm table-bordered" id="previewTable">

                        <thead class="table-light"></thead>

                        <tbody></tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>



<script src="assets/js/importar_cursos.js"></script>