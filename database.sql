CREATE DATABASE IF NOT EXISTS dev_cursos;
USE dev_cursos;

-- =====================================================
-- TABLA CATALOGO DE CURSOS (para landing pages)
-- =====================================================

CREATE TABLE dir_cursos_catalogo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_nombre VARCHAR(255) NOT NULL,
  curso_slug VARCHAR(255) NOT NULL,
  curso_descripcion_corta TEXT,
  curso_descripcion_larga TEXT,
  curso_modalidad VARCHAR(100),
  curso_precio VARCHAR(50),

  curso_director VARCHAR(255),
  curso_codigo_sence VARCHAR(100),
  curso_area VARCHAR(150),
  horas_cronologicas INT,

  curso_estado INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY curso_slug_unique (curso_slug)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- OBJETIVOS DEL CURSO (landing)
-- =====================================================

CREATE TABLE dir_cursos_objetivos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  objetivo TEXT NOT NULL,
  orden INT DEFAULT 1,
  estado INT DEFAULT 1,

  INDEX idx_objetivo_curso (curso_id),

  CONSTRAINT fk_objetivo_curso
  FOREIGN KEY (curso_id)
  REFERENCES dir_cursos_catalogo(id)
  ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- METODOLOGIA DEL CURSO
-- =====================================================

CREATE TABLE dir_cursos_metodologia (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  descripcion TEXT NOT NULL,
  orden INT DEFAULT 1,
  estado INT DEFAULT 1,

  INDEX idx_metodologia_curso (curso_id),

  CONSTRAINT fk_metodologia_curso
  FOREIGN KEY (curso_id)
  REFERENCES dir_cursos_catalogo(id)
  ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- REQUISITOS DEL CURSO
-- =====================================================

CREATE TABLE dir_cursos_requisitos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  requisito TEXT NOT NULL,
  orden INT DEFAULT 1,
  estado INT DEFAULT 1,

  INDEX idx_requisito_curso (curso_id),

  CONSTRAINT fk_requisito_curso
  FOREIGN KEY (curso_id)
  REFERENCES dir_cursos_catalogo(id)
  ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- MODULOS DEL CURSO
-- =====================================================

CREATE TABLE dir_cursos_modulos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  modulo_titulo VARCHAR(255) NOT NULL,
  modulo_descripcion TEXT,
  orden INT DEFAULT 1,
  estado INT DEFAULT 1,

  INDEX idx_modulo_curso (curso_id),

  CONSTRAINT fk_modulo_curso
  FOREIGN KEY (curso_id)
  REFERENCES dir_cursos_catalogo(id)
  ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- RELATORES
-- =====================================================

CREATE TABLE dir_cursos_relatores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  apellido VARCHAR(150),
  email VARCHAR(150),
  telefono VARCHAR(50),
  especialidad VARCHAR(255),
  bio TEXT,
  estado INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- RELACION CURSO - RELATOR
-- =====================================================

CREATE TABLE dir_cursos_relatores_curso (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  relator_id INT NOT NULL,

  INDEX idx_relator_curso (curso_id),
  INDEX idx_relator_relator (relator_id),

  CONSTRAINT fk_relator_curso
  FOREIGN KEY (curso_id)
  REFERENCES dir_cursos_catalogo(id)
  ON DELETE CASCADE,

  CONSTRAINT fk_relator_relator
  FOREIGN KEY (relator_id)
  REFERENCES dir_cursos_relatores(id)
  ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- ALUMNOS
-- =====================================================

CREATE TABLE dir_cursos_alumnos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  apellido_paterno VARCHAR(150),
  apellido_materno VARCHAR(150),
  email VARCHAR(150),
  rut VARCHAR(20),
  telefono VARCHAR(50),
  region VARCHAR(150),
  ciudad VARCHAR(150),
  institucion VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY alumno_email_unique (email)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- EDICIONES DEL CURSO (curso impartido)
-- =====================================================

CREATE TABLE dir_cursos_ediciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT NOT NULL,
  version VARCHAR(50),
  fecha_inicio DATE,
  fecha_fin DATE,
  modalidad VARCHAR(100),
  cupo_maximo INT,
  estado INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_edicion_curso (curso_id),

  CONSTRAINT fk_edicion_curso
  FOREIGN KEY (curso_id)
  REFERENCES dir_cursos_catalogo(id)
  ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- MATRICULAS (alumnos externos)
-- =====================================================

CREATE TABLE dir_cursos_matriculas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  edicion_id INT NOT NULL,
  alumno_id INT NOT NULL,
  fecha_matricula DATE,
  estado VARCHAR(50),

  nota_final DECIMAL(4,2),
  porcentaje_asistencia DECIMAL(5,2),
  aprobado BOOLEAN,

  INDEX idx_matricula_edicion (edicion_id),
  INDEX idx_alumno (alumno_id),

  CONSTRAINT fk_matricula_edicion
  FOREIGN KEY (edicion_id)
  REFERENCES dir_cursos_ediciones(id)
  ON DELETE CASCADE,

  CONSTRAINT fk_matricula_alumno
  FOREIGN KEY (alumno_id)
  REFERENCES dir_cursos_alumnos(id)
  ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- ASISTENCIA
-- =====================================================

CREATE TABLE dir_cursos_asistencia (
  id INT AUTO_INCREMENT PRIMARY KEY,
  matricula_id INT NOT NULL,
  fecha DATE NOT NULL,
  asistio BOOLEAN,

  INDEX idx_asistencia_matricula (matricula_id),

  CONSTRAINT fk_asistencia_matricula
  FOREIGN KEY (matricula_id)
  REFERENCES dir_cursos_matriculas(id)
  ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- NOTAS / EVALUACIONES
-- =====================================================

CREATE TABLE dir_cursos_notas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  matricula_id INT NOT NULL,
  evaluacion VARCHAR(255),
  nota DECIMAL(4,2),

  INDEX idx_notas_matricula (matricula_id),

  CONSTRAINT fk_notas_matricula
  FOREIGN KEY (matricula_id)
  REFERENCES dir_cursos_matriculas(id)
  ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- CERTIFICADOS GENERADOS
-- =====================================================

CREATE TABLE dir_cursos_certificados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  matricula_id INT NOT NULL,
  codigo_certificado VARCHAR(100),
  fecha_emision DATE,
  archivo_pdf VARCHAR(255),

  INDEX idx_certificado_matricula (matricula_id),

  CONSTRAINT fk_certificado_matricula
  FOREIGN KEY (matricula_id)
  REFERENCES dir_cursos_matriculas(id)
  ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;