<?php
$current_page = basename($_SERVER['PHP_SELF']);

$current_view = isset($_GET['page'])
    ? strtolower($_GET['page'])
    : ($current_page === 'index.php' ? 'dashboard' : str_replace('.php', '', $current_page));


/* ===============================
   SECCIONES ACTIVAS
=============================== */

$isCatalogoActive = in_array($current_view, [
    'cursos',
    'nuevo_curso',
    'editar_curso',
    'objetivos',
    'metodologia',
    'modulos',
    'requisitos'
]);

$isEdicionesActive = in_array($current_view, [
    'ediciones',
    'nueva_edicion',
    'editar_edicion'
]);

$isAcademicoActive = in_array($current_view, [
    'matriculas',
    'nueva_matricula',
    'asistencia',
    'notas'
]);

$isAlumnosActive = in_array($current_view, [
    'alumnos',
    'nuevo_alumno',
    'importar_alumnos'
]);

$isRelatoresActive = in_array($current_view, [
    'relatores',
    'nuevo_relator'
]);

?>

<!-- Sidebar -->
<nav id="sidebar">

    <div class="sidebar-header">
        <h3><i class="fas fa-graduation-cap me-2"></i>Cursos</h3>
    </div>

    <ul class="list-unstyled components">

        <!-- DASHBOARD -->
        <li class="<?php echo $current_view == 'dashboard' ? 'active' : ''; ?>">
            <a href="index.php">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
        </li>



        <!-- CATALOGO CURSOS -->

        <li class="<?php echo $isCatalogoActive ? 'active' : ''; ?>">

            <a class="d-flex align-items-center" data-bs-toggle="collapse" href="#submenuCatalogo" role="button"
                aria-expanded="<?php echo $isCatalogoActive ? 'true' : 'false'; ?>">

                <i class="fas fa-book me-2"></i>
                Catálogo Cursos

                <i class="fas fa-chevron-down ms-auto small"></i>

            </a>

            <ul class="collapse list-unstyled <?php echo $isCatalogoActive ? 'show' : ''; ?>" id="submenuCatalogo">

                <li class="<?php echo $current_view == 'cursos' ? 'active' : ''; ?>">
                    <a class="ps-4" href="index.php?page=cursos">
                        <i class="fas fa-database me-1"></i> Cursos
                    </a>
                </li>

                <li class="<?php echo $current_view == 'nuevo_curso' ? 'active' : ''; ?>">
                    <a class="ps-4" href="index.php?page=nuevo_curso">
                        <i class="fas fa-plus me-1"></i> Nuevo Curso
                    </a>
                </li>

            </ul>

        </li>



        <!-- EDICIONES -->

        <li class="<?php echo $isEdicionesActive ? 'active' : ''; ?>">

            <a class="d-flex align-items-center" data-bs-toggle="collapse" href="#submenuEdiciones" role="button"
                aria-expanded="<?php echo $isEdicionesActive ? 'true' : 'false'; ?>">

                <i class="fas fa-layer-group me-2"></i>
                Ediciones

                <i class="fas fa-chevron-down ms-auto small"></i>

            </a>

            <ul class="collapse list-unstyled <?php echo $isEdicionesActive ? 'show' : ''; ?>" id="submenuEdiciones">

                <li class="<?php echo $current_view == 'ediciones' ? 'active' : ''; ?>">
                    <a class="ps-4" href="index.php?page=ediciones">
                        <i class="fas fa-list me-1"></i> Ediciones del Curso
                    </a>
                </li>

                <li class="<?php echo $current_view == 'nueva_edicion' ? 'active' : ''; ?>">
                    <a class="ps-4" href="index.php?page=nueva_edicion">
                        <i class="fas fa-plus me-1"></i> Nueva Edición
                    </a>
                </li>

            </ul>

        </li>



        <!-- ALUMNOS -->

        <li class="<?php echo $isAlumnosActive ? 'active' : ''; ?>">

            <a class="d-flex align-items-center" data-bs-toggle="collapse" href="#submenuAlumnos" role="button"
                aria-expanded="<?php echo $isAlumnosActive ? 'true' : 'false'; ?>">

                <i class="fas fa-user-graduate me-2"></i>
                Alumnos

                <i class="fas fa-chevron-down ms-auto small"></i>

            </a>

            <ul class="collapse list-unstyled <?php echo $isAlumnosActive ? 'show' : ''; ?>" id="submenuAlumnos">

                <li class="<?php echo $current_view == 'alumnos' ? 'active' : ''; ?>">
                    <a class="ps-4" href="index.php?page=alumnos">
                        <i class="fas fa-users me-1"></i> Lista de alumnos
                    </a>
                </li>

                <li class="<?php echo $current_view == 'nuevo_alumno' ? 'active' : ''; ?>">
                    <a class="ps-4" href="index.php?page=nuevo_alumno">
                        <i class="fas fa-user-plus me-1"></i> Nuevo alumno
                    </a>
                </li>

                <li class="<?php echo $current_view == 'importar_alumnos' ? 'active' : ''; ?>">
                    <a class="ps-4" href="index.php?page=importar_alumnos">
                        <i class="fas fa-file-import me-1"></i> Importar alumnos
                    </a>
                </li>

            </ul>

        </li>



        <!-- MATRICULAS -->

        <li class="<?php echo $current_view == 'matriculas' ? 'active' : ''; ?>">

            <a href="index.php?page=matriculas">
                <i class="fas fa-id-card me-2"></i>
                Matrículas
            </a>

        </li>



        <!-- SEGUIMIENTO ACADEMICO -->

        <li class="<?php echo $isAcademicoActive ? 'active' : ''; ?>">

            <a class="d-flex align-items-center" data-bs-toggle="collapse" href="#submenuAcademico" role="button"
                aria-expanded="<?php echo $isAcademicoActive ? 'true' : 'false'; ?>">

                <i class="fas fa-chart-line me-2"></i>
                Seguimiento

                <i class="fas fa-chevron-down ms-auto small"></i>

            </a>

            <ul class="collapse list-unstyled <?php echo $isAcademicoActive ? 'show' : ''; ?>" id="submenuAcademico">

                <li class="<?php echo $current_view == 'asistencia' ? 'active' : ''; ?>">
                    <a class="ps-4" href="index.php?page=asistencia">
                        <i class="fas fa-calendar-check me-1"></i> Asistencia
                    </a>
                </li>

                <li class="<?php echo $current_view == 'notas' ? 'active' : ''; ?>">
                    <a class="ps-4" href="index.php?page=notas">
                        <i class="fas fa-clipboard-check me-1"></i> Notas
                    </a>
                </li>

            </ul>

        </li>



        <!-- RELATORES -->

        <li class="<?php echo $isRelatoresActive ? 'active' : ''; ?>">

            <a href="index.php?page=relatores">
                <i class="fas fa-chalkboard-teacher me-2"></i>
                Relatores
            </a>

        </li>



        <!-- CONFIG -->

        <li>
            <a href="#">
                <i class="fas fa-cog me-2"></i> Configuración
            </a>
        </li>

    </ul>

</nav>



<!-- Page Content -->
<div id="content">

    <nav class="navbar navbar-expand-lg navbar-light bg-white mb-4 shadow-sm rounded">

        <div class="container-fluid">

            <button type="button" id="sidebarCollapse" class="btn btn-primary">
                <i class="fas fa-align-left"></i>
            </button>

            <div class="ms-auto">
                <span class="text-muted">Administrador de Cursos</span>
            </div>

        </div>

    </nav>