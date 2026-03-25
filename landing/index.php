<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

/* =========================
   OBTENER CURSOS
========================= */

$stmt = $pdo->prepare("
SELECT
    id,
    curso_nombre,
    curso_slug,
    curso_area,
    curso_descripcion_corta,
    curso_modalidad,
    curso_precio,
    curso_director,
    horas_cronologicas,
    curso_imagen_portada
FROM dir_cursos_catalogo
WHERE curso_estado = 1 OR curso_estado IS NULL
ORDER BY created_at DESC
");

$stmt->execute();

$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">

   <title>Catálogo de cursos</title>

   <!-- Google Fonts -->
   <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">

   <!-- Bootstrap -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

   <!-- FontAwesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

   <!-- Estilos -->
   <link rel="stylesheet" href="assets/css/styles.css">

   <style>
      body {
         font-family: 'Montserrat', sans-serif;
         background: #f5f6fa;
      }

      .course-card {
         background: #fff;
         border-radius: 12px;
         overflow: hidden;
         margin-bottom: 25px;
         box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
         transition: 0.3s;
      }

      .course-card:hover {
         transform: translateY(-5px);
      }

      .course-media img {
         width: 100%;
         height: 180px;
         object-fit: cover;
      }

      .course-body {
         padding: 20px;
      }

      .course-title {
         font-size: 18px;
         font-weight: 700;
         margin-bottom: 10px;
      }

      .course-desc {
         font-size: 14px;
         color: #666;
         margin-bottom: 15px;
      }

      .course-meta {
         font-size: 13px;
         margin-bottom: 15px;
      }

      .course-meta div {
         margin-bottom: 5px;
      }

      .course-footer {
         display: flex;
         justify-content: space-between;
         align-items: center;
      }

      .course-price {
         font-weight: 700;
         color: #28a745;
      }
   </style>
</head>

<body>

   <div class="container mt-5">

      <h2 class="mb-4 text-center">Catálogo de cursos</h2>

      <div class="row">

         <?php if (!empty($cursos)): ?>

            <?php foreach ($cursos as $curso): ?>

               <div class="col-lg-4 col-md-6">

                  <div class="course-card">

                     <div class="course-media">

                        <?php if (!empty($curso['curso_imagen_portada'])): ?>
                           <img src="uploads/<?php echo htmlspecialchars($curso['curso_imagen_portada']); ?>">
                        <?php else: ?>
                           <img src="https://via.placeholder.com/400x200?text=Curso">
                        <?php endif; ?>

                     </div>

                     <div class="course-body">

                        <div class="course-title">
                           <?php echo htmlspecialchars($curso['curso_nombre'] ?? 'Sin nombre'); ?>
                        </div>

                        <div class="course-desc">
                           <?php echo htmlspecialchars($curso['curso_descripcion_corta'] ?? 'Sin descripción'); ?>
                        </div>

                        <div class="course-meta">

                           <div>
                              <i class="fas fa-tag"></i>
                              <?php echo htmlspecialchars($curso['curso_area'] ?? 'Sin área'); ?>
                           </div>

                           <div>
                              <i class="fas fa-user"></i>
                              <?php echo htmlspecialchars($curso['curso_director'] ?? 'Sin información'); ?>
                           </div>

                           <div>
                              <i class="fas fa-clock"></i>
                              <?php echo htmlspecialchars($curso['horas_cronologicas'] ?? '0'); ?> horas
                           </div>

                           <div>
                              <i class="fas fa-laptop"></i>
                              <?php echo htmlspecialchars($curso['curso_modalidad'] ?? 'No definida'); ?>
                           </div>

                        </div>

                        <div class="course-footer">

                           <div class="course-price">
                              <?php
                              echo ($curso['curso_precio'] !== null && $curso['curso_precio'] !== '')
                                 ? '$' . number_format($curso['curso_precio'], 0, ',', '.')
                                 : 'Gratis';
                              ?>
                           </div>

                           <a href="curso.php?slug=<?php echo urlencode($curso['curso_slug'] ?? '#'); ?>"
                              class="btn btn-primary btn-sm">
                              Ver curso
                           </a>

                        </div>

                     </div>

                  </div>

               </div>

            <?php endforeach; ?>

         <?php else: ?>

            <div class="col-12 text-center">
               <p>No hay cursos disponibles.</p>
            </div>

         <?php endif; ?>

      </div>

   </div>

   <!-- Scripts -->
   <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

</body>

</html>