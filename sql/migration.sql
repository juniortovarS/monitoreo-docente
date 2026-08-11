-- ==========================================
-- SCRIPT DE MIGRACIÓN: MÓDULO DE SEGUIMIENTO DOCENTE (SIGAV)
-- ==========================================

-- 1. Crear tablas auxiliares existentes si no existen (para asegurar la consistencia de FKs)

CREATE TABLE IF NOT EXISTS av_aula (
    av_aul_id INT AUTO_INCREMENT PRIMARY KEY,
    av_aul_codigo VARCHAR(50) NOT NULL UNIQUE,
    av_aul_descripcion VARCHAR(150) NULL,
    av_aul_alum_min INT DEFAULT 10,
    av_aul_alum_max INT DEFAULT 40,
    sys_reg_fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS av_docente (
    av_doc_id INT AUTO_INCREMENT PRIMARY KEY,
    av_doc_codigo VARCHAR(20) NOT NULL UNIQUE,
    av_doc_nombres VARCHAR(100) NOT NULL,
    av_doc_apellidos VARCHAR(100) NOT NULL,
    av_doc_email VARCHAR(150) NOT NULL,
    sys_reg_fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS av_inscripcion_gestor (
    av_ing_id INT AUTO_INCREMENT PRIMARY KEY,
    av_doc_id INT NOT NULL,
    av_ing_estado VARCHAR(20) DEFAULT 'ACTIVO',
    sys_reg_fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (av_doc_id) REFERENCES av_docente(av_doc_id)
);

CREATE TABLE IF NOT EXISTS av_grupo_horario (
    id_horario INT AUTO_INCREMENT PRIMARY KEY,
    av_aul_id INT NOT NULL,
    dia VARCHAR(20) NOT NULL, -- Lunes, Martes, Miercoles, Jueves, Viernes, Sabado, Domingo
    h_inicio TIME NOT NULL,
    h_fin TIME NOT NULL,
    av_ing_id INT NOT NULL,
    sys_reg_fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (av_aul_id) REFERENCES av_aula(av_aul_id),
    FOREIGN KEY (av_ing_id) REFERENCES av_inscripcion_gestor(av_ing_id)
);

-- 2. Crear tabla principal para el Historial de Seguimiento Docente

CREATE TABLE IF NOT EXISTS av_seguimiento_docente_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    av_aul_id INT NOT NULL,               -- Relación con av_aula
    av_doc_id INT NOT NULL,               -- Relación con av_docente
    id_horario INT NOT NULL,              -- Relación con av_grupo_horario
    fecha_clase DATE NOT NULL,            -- Fecha de la sesión
    horario_inicio_plan TIME NOT NULL,    -- Hora programada de inicio
    horario_fin_plan TIME NOT NULL,       -- Hora programada de fin
    hora_entrada_real DATETIME NULL,      -- Hora real de inicio (Zoom)
    hora_salida_real DATETIME NULL,       -- Hora real de fin (Zoom)
    minutos_tardanza INT DEFAULT 0,       -- Minutos de tardanza
    estado_asistencia VARCHAR(20),        -- PUNTUAL, TARDANZA, FALTO
    alumnos_matriculados INT DEFAULT 0,   -- Alumnos matriculados en Canvas
    alumnos_asistentes INT DEFAULT 0,     -- Alumnos presentes en la videollamada
    capacidad_maxima INT DEFAULT 0,       -- Límite del aula (av_aul_alum_max)
    zoom_meeting_id VARCHAR(100) NULL,    -- ID de la sesión de Zoom
    sys_reg_fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (av_aul_id) REFERENCES av_aula(av_aul_id),
    FOREIGN KEY (av_doc_id) REFERENCES av_docente(av_doc_id),
    FOREIGN KEY (id_horario) REFERENCES av_grupo_horario(id_horario)
);

-- ==========================================
-- DATA DE PRUEBA / MOCK DATA
-- ==========================================

-- Insertar Aulas
INSERT INTO av_aula (av_aul_codigo, av_aul_descripcion, av_aul_alum_min, av_aul_alum_max) VALUES
('AULA_101', 'Desarrollo de Software - Ciclo VI', 15, 30),
('AULA_102', 'Arquitectura de Sistemas - Ciclo VII', 10, 25),
('AULA_103', 'Inteligencia de Negocios - Ciclo VIII', 15, 45), -- Aula con sobre-matrícula potencial
('AULA_104', 'Taller de Tesis - Ciclo X', 5, 15)
ON DUPLICATE KEY UPDATE av_aul_descripcion=av_aul_descripcion;

-- Insertar Docentes
INSERT INTO av_docente (av_doc_codigo, av_doc_nombres, av_doc_apellidos, av_doc_email) VALUES
('DOC001', 'Juan', 'Perez Gomez', 'juan.perez@usmp.pe'),
('DOC002', 'Maria', 'Rodriguez Silva', 'maria.rodriguez@usmp.pe'),
('DOC003', 'Carlos', 'Sanches Mendoza', 'carlos.sanchez@usmp.pe')
ON DUPLICATE KEY UPDATE av_doc_email=av_doc_email;

-- Inscribir Gestores (Asignar Docentes)
INSERT INTO av_inscripcion_gestor (av_ing_id, av_doc_id, av_ing_estado) VALUES
(1, 1, 'ACTIVO'),
(2, 2, 'ACTIVO'),
(3, 3, 'ACTIVO')
ON DUPLICATE KEY UPDATE av_ing_estado=av_ing_estado;

-- Insertar Grupo Horario (Programación)
-- Nota: Los días se registran en formato de texto. El sistema los cruzará dinámicamente.
INSERT INTO av_grupo_horario (id_horario, av_aul_id, dia, h_inicio, h_fin, av_ing_id) VALUES
(1, 1, 'Lunes', '08:00:00', '10:00:00', 1), -- Juan Perez en Aula 101, Lunes 8-10 AM
(2, 2, 'Lunes', '10:00:00', '12:00:00', 2), -- Maria Rodriguez en Aula 102, Lunes 10-12 PM
(3, 3, 'Martes', '14:00:00', '16:00:00', 3), -- Carlos Sanchez en Aula 103, Martes 2-4 PM
(4, 4, 'Miercoles', '18:00:00', '20:00:00', 1), -- Juan Perez en Aula 104, Miércoles 6-8 PM
(5, 1, 'Jueves', '08:00:00', '10:00:00', 1), -- Juan Perez en Aula 101, Jueves 8-10 AM
(6, 2, 'Viernes', '15:00:00', '17:00:00', 2), -- Maria Rodriguez en Aula 102, Viernes 3-5 PM
(7, 3, 'Sabado', '09:00:00', '11:00:00', 3) -- Carlos Sanchez en Aula 103, Sábado 9-11 AM
ON DUPLICATE KEY UPDATE dia=dia;
