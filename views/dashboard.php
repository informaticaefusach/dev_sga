<div class="container-fluid">

    <h2 class="mb-4">Dashboard</h2>

    <div class="row">

        <!-- TOTAL CURSOS -->
        <div class="col-md-4 mb-4">
            <div class="card card-counter shadow h-100 py-2">
                <div class="card-body">

                    <div class="row no-gutters align-items-center">

                        <div class="col mr-2">

                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Cursos en Catálogo
                            </div>

                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $total_cursos; ?>
                            </div>

                        </div>

                        <div class="col-auto icon-box">
                            <i class="fas fa-book"></i>
                        </div>

                    </div>

                </div>
            </div>
        </div>


        <!-- TOTAL EDICIONES -->
        <div class="col-md-4 mb-4">
            <div class="card card-counter shadow h-100 py-2" style="border-left-color: #1cc88a;">
                <div class="card-body">

                    <div class="row no-gutters align-items-center">

                        <div class="col mr-2">

                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Ediciones del Curso
                            </div>

                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $total_ediciones; ?>
                            </div>

                        </div>

                        <div class="col-auto icon-box">
                            <i class="fas fa-layer-group"></i>
                        </div>

                    </div>

                </div>
            </div>
        </div>


        <!-- TOTAL MATRICULAS -->
        <div class="col-md-4 mb-4">
            <div class="card card-counter shadow h-100 py-2" style="border-left-color: #f6c23e;">
                <div class="card-body">

                    <div class="row no-gutters align-items-center">

                        <div class="col mr-2">

                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Alumnos Matriculados
                            </div>

                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $total_matriculas; ?>
                            </div>

                        </div>

                        <div class="col-auto icon-box">
                            <i class="fas fa-user-graduate"></i>
                        </div>

                    </div>

                </div>
            </div>
        </div>

    </div>



    <!-- RESUMEN DEL SISTEMA -->

    <div class="row">

        <div class="col-12">

            <div class="card shadow mb-4">

                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Sistema de Administración de Cursos
                    </h6>
                </div>

                <div class="card-body">

                    <p>
                        Bienvenido al sistema de gestión de cursos. Desde aquí podrás administrar el catálogo
                        de cursos, sus ediciones, matrículas, asistencia y evaluaciones.
                    </p>

                    <p>
                        Este sistema también permite generar automáticamente landing pages de cada curso
                        utilizando la información almacenada en la base de datos.
                    </p>

                    <p class="mb-0">
                        Usa el menú lateral para navegar entre las diferentes secciones del sistema.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>