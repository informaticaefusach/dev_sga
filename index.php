<?php

session_start();
require_once 'db.php';

/* ===============================
   CONSULTA DASHBOARD
=============================== */

try {

   $stmt = $pdo->query("SELECT COUNT(*) as total FROM dir_cursos_catalogo");
   $total_cursos = $stmt->fetch()['total'];

   $stmt = $pdo->query("SELECT COUNT(*) as total FROM dir_cursos_ediciones");
   $total_ediciones = $stmt->fetch()['total'];

   $stmt = $pdo->query("SELECT COUNT(*) as total FROM dir_cursos_matriculas");
   $total_matriculas = $stmt->fetch()['total'];

} catch (PDOException $e) {

   $total_cursos = 0;
   $total_ediciones = 0;
   $total_matriculas = 0;

}

/* ===============================
   ROUTER
=============================== */

$page = isset($_GET['page']) ? strtolower($_GET['page']) : '';
$page = preg_replace('/[^a-z_]/', '', $page);

$pageTitle = "Administrador de Cursos";

$included = false;
$public = false; // 👈 valor por defecto

if ($page) {

   $routes = [

      'dashboard' => 'dashboard',

      'cursos' => 'cursos',
      'curso' => 'cursos',
      'nuevo_curso' => 'nuevo_curso',
      'editar_curso' => 'editar_curso',
      'importar_cursos' => 'importar_cursos',

      'objetivos' => 'objetivos',
      'metodologia' => 'metodologia',
      'modulos' => 'modulos',
      'requisitos' => 'requisitos',

      'ediciones' => 'ediciones',
      'nueva_edicion' => 'nueva_edicion',
      'editar_edicion' => 'editar_edicion',

      'relatores' => 'relatores',

      'matriculas' => 'matriculas',
      'nueva_matricula' => 'nueva_matricula',

      'asistencia' => 'asistencia',
      'notas' => 'notas',

      'alumnos' => 'alumnos',
      'importar_alumnos' => 'importar_alumnos',
      'nuevo_alumno' => 'nuevo_alumno',
      'revisar_importacion' => 'revisar_importacion',
      'procesar_matriculas' => 'procesar_matriculas',

      'generar_certificados' => 'generar_certificados',
      'verificar_certificado' => 'verificar_certificado'

   ];

   if (isset($routes[$page])) {

      $view = $routes[$page];
      $target = __DIR__ . '/views/' . $view . '.php';

      if (file_exists($target)) {

         ob_start(); // 👈 capturar salida

         include $target;

         $content = ob_get_clean(); // 👈 guardar contenido

         $included = true;

      }

   }

}

/* ===============================
   HEADER / SIDEBAR SOLO SI NO ES PUBLICO
=============================== */

if (!isset($public) || $public !== true) {

   include 'includes/header.php';
   include 'includes/sidebar.php';

}

/* ===============================
   MOSTRAR CONTENIDO
=============================== */

if ($included) {

   echo $content;

} else {

   include __DIR__ . '/views/dashboard.php';

}

/* ===============================
   FOOTER
=============================== */

if (!isset($public) || $public !== true) {

   include 'includes/footer.php';

}