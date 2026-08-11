<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SeguimientoDocenteModel
 * 
 * Gestiona el acceso a la base de datos de SIGAV para el control
 * de asistencia docente, cruce de horarios y reportes analíticos.
 */
class SeguimientoDocenteModel extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Inserta un nuevo registro en el historial de seguimiento
     * 
     * @param array $data Datos de la sesión
     * @return int ID insertado
     */
    public function insert_historial($data) {
        $this->db->insert('av_seguimiento_docente_historial', $data);
        return $this->db->insert_id();
    }

    /**
     * Actualiza un registro existente en el historial por ID
     * 
     * @param int $id ID del historial
     * @param array $data Datos a actualizar
     * @return bool
     */
    public function update_historial($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('av_seguimiento_docente_historial', $data);
    }

    /**
     * Busca un registro de historial por el ID de la reunión de Zoom
     * 
     * @param string $zoom_meeting_id ID de Zoom
     * @return array|bool Datos del registro o FALSE
     */
    public function get_historial_by_zoom_id($zoom_meeting_id) {
        $query = $this->db->get_where('av_seguimiento_docente_historial', [
            'zoom_meeting_id' => $zoom_meeting_id
        ]);
        return $query->row_array();
    }

    /**
     * Obtiene el listado de docentes por su email institucional
     * 
     * @param string $email Correo del docente
     * @return array|bool Datos del docente o FALSE
     */
    public function get_docente_by_email($email) {
        $query = $this->db->get_where('av_docente', ['av_doc_email' => $email]);
        return $query->row_array();
    }

    /**
     * Busca la programación horaria correspondiente para un docente, en un día y hora específicos
     * Esto cruza la información horaria con un margen de tolerancia (ej: 30 minutos antes/después de la sesión)
     * 
     * @param int $docente_id ID del docente
     * @param string $dia Nombre del día en español (Lunes, Martes, etc.)
     * @param string $hora Hora en formato 'HH:MM:SS'
     * @return array|bool Fila del horario correspondiente o FALSE
     */
    public function find_schedule($docente_id, $dia, $hora) {
        $sql = "SELECT gh.id_horario, gh.av_aul_id, gh.h_inicio, gh.h_fin, 
                       a.av_aul_alum_max, a.av_aul_codigo, a.av_aul_descripcion
                FROM av_grupo_horario gh
                JOIN av_aula a ON gh.av_aul_id = a.av_aul_id
                JOIN av_inscripcion_gestor ig ON gh.av_ing_id = ig.av_ing_id
                WHERE ig.av_doc_id = ? 
                  AND gh.dia = ?
                  AND ? BETWEEN SUBTIME(gh.h_inicio, '00:45:00') AND ADDTIME(gh.h_fin, '00:45:00')
                LIMIT 1";

        $query = $this->db->query($sql, [$docente_id, $dia, $hora]);
        return $query->row_array();
    }

    /**
     * Obtiene todos los horarios programados para un día de la semana específico
     * 
     * @param string $dia Nombre del día
     * @return array Listado de horarios
     */
    public function get_horarios_by_dia($dia) {
        $this->db->select('gh.*, a.av_aul_codigo, a.av_aul_descripcion, a.av_aul_alum_max, d.av_doc_codigo, d.av_doc_nombres, d.av_doc_apellidos, d.av_doc_email');
        $this->db->from('av_grupo_horario gh');
        $this->db->join('av_aula a', 'gh.av_aul_id = a.av_aul_id');
        $this->db->join('av_inscripcion_gestor ig', 'gh.av_ing_id = ig.av_ing_id');
        $this->db->join('av_docente d', 'ig.av_doc_id = d.av_doc_id');
        $this->db->where('gh.dia', $dia);
        
        return $this->db->get()->result_array();
    }

    /**
     * Obtiene el reporte del historial académico con filtros avanzados y KPIs
     * 
     * @param array $filters Filtros (docente_id, aula_id, fecha_inicio, fecha_fin, estado)
     * @return array Resumen analítico y lista de registros
     */
    public function get_historial_reporte($filters = []) {
        // 1. Obtener listado de registros
        $this->db->select('h.*, a.av_aul_codigo, a.av_aul_descripcion, d.av_doc_codigo, d.av_doc_nombres, d.av_doc_apellidos, d.av_doc_email');
        $this->db->from('av_seguimiento_docente_historial h');
        $this->db->join('av_aula a', 'h.av_aul_id = a.av_aul_id');
        $this->db->join('av_docente d', 'h.av_doc_id = d.av_doc_id');

        if (!empty($filters['docente_id'])) {
            $this->db->where('h.av_doc_id', $filters['docente_id']);
        }
        if (!empty($filters['aula_id'])) {
            $this->db->where('h.av_aul_id', $filters['aula_id']);
        }
        if (!empty($filters['periodo'])) {
            if ($this->db->field_exists('periodo', 'av_seguimiento_docente_historial')) {
                $this->db->where('h.periodo', $filters['periodo']);
            } else {
                $this->db->like('a.av_aul_codigo', $filters['periodo']);
            }
        }
        if (!empty($filters['plataforma'])) {
            if ($this->db->field_exists('plataforma', 'av_aula')) {
                $this->db->where('a.plataforma', $filters['plataforma']);
            } else {
                if ($filters['plataforma'] === 'AP') {
                    $this->db->like('a.av_aul_codigo', 'AP');
                } else {
                    $this->db->not_like('a.av_aul_codigo', 'AP');
                }
            }
        }
        if (!empty($filters['fecha_inicio'])) {
            $this->db->where('h.fecha_clase >=', $filters['fecha_inicio']);
        }
        if (!empty($filters['fecha_fin'])) {
            $this->db->where('h.fecha_clase <=', $filters['fecha_fin']);
        }
        if (!empty($filters['estado'])) {
            $this->db->where('h.estado_asistencia', $filters['estado']);
        }

        $this->db->order_by('h.fecha_clase', 'DESC');
        $this->db->order_by('h.horario_inicio_plan', 'DESC');
        
        $result = $this->db->get()->result_array();

        // 2. Calcular KPIs agregados para el dashboard
        $total_clases = count($result);
        $puntuales = 0;
        $tardanzas = 0;
        $faltas = 0;
        $exceso_aforo = 0;
        $total_matriculados = 0;
        $total_asistentes = 0;

        foreach ($result as $row) {
            if ($row['estado_asistencia'] === 'PUNTUAL') $puntuales++;
            elseif ($row['estado_asistencia'] === 'TARDANZA') $tardanzas++;
            elseif ($row['estado_asistencia'] === 'FALTO') $faltas++;

            if ($row['alumnos_matriculados'] > $row['capacidad_maxima']) {
                $exceso_aforo++;
            }
            
            $total_matriculados += $row['alumnos_matriculados'];
            $total_asistentes += $row['alumnos_asistentes'];
        }

        $porcentaje_cumplimiento = $total_clases > 0 ? round((($puntuales + $tardanzas) / $total_clases) * 100, 2) : 100;
        $porcentaje_asistencia = $total_matriculados > 0 ? round(($total_asistentes / $total_matriculados) * 100, 2) : 0;

        return [
            'data' => $result,
            'kpis' => [
                'total_clases' => $total_clases,
                'puntuales' => $puntuales,
                'tardanzas' => $tardanzas,
                'faltas' => $faltas,
                'exceso_aforo' => $exceso_aforo,
                'porcentaje_cumplimiento' => $porcentaje_cumplimiento,
                'porcentaje_asistencia_estudiantes' => $porcentaje_asistencia
            ]
        ];
    }

    /**
     * Obtiene los docentes únicos por username y cruzados con su licencia de Zoom
     */
    public function get_docentes_selector($licensed_emails = NULL) {
        if ($this->db->table_exists('av_persona')) {
            $this->db->select('d.av_doc_id, d.av_doc_username, d.av_doc_correo, 
                               p.av_per_nombre_pri, p.av_per_apepat, p.av_per_apemat');
            $this->db->from('av_docente d');
            $this->db->join('av_persona p', 'd.av_per_id = p.av_per_id');
        } else {
            // Fallback local mock schema
            $this->db->select('av_doc_id, av_doc_username, av_doc_nombres as av_per_nombre_pri, av_doc_apellidos as av_per_apepat, "" as av_per_apemat, av_doc_email as av_doc_correo');
            $this->db->from('av_docente');
        }
        
        $rows = $this->db->get()->result_array();
        
        // Agrupar en memoria para evitar el cuello de botella de MySQL GROUP BY
        $docentes_map = [];
        foreach ($rows as $row) {
            $username = $row['av_doc_username'];
            if (!isset($docentes_map[$username])) {
                // Mapear nombres completo
                $row['av_doc_nombres'] = isset($row['av_per_nombre_pri']) ? trim($row['av_per_nombre_pri']) : '';
                $row['av_doc_apellidos'] = isset($row['av_per_apepat']) ? trim($row['av_per_apepat'] . ' ' . ($row['av_per_apemat'] ?: '')) : '';
                
                $email = strtolower($row['av_doc_correo'] ?? '');
                $row['has_zoom_license'] = FALSE;
                if (is_array($licensed_emails) && isset($licensed_emails[$email])) {
                    $row['has_zoom_license'] = TRUE;
                }
                $docentes_map[$username] = $row;
            }
        }
        
        $docentes = array_values($docentes_map);
        
        // Ordenar en memoria por apellidos
        usort($docentes, function($a, $b) {
            return strcasecmp($a['av_doc_apellidos'], $b['av_doc_apellidos']);
        });
        
        return $docentes;
    }

    public function get_aulas_selector() {
        return $this->db->select('av_aul_id, av_aul_codigo, av_aul_descripcion')
                        ->from('av_aula')
                        ->order_by('av_aul_codigo', 'ASC')
                        ->get()->result_array();
    }

    /**
     * Obtiene aulas filtradas por periodo y plataforma (con detección dinámica de columnas)
     */
    public function get_aulas_por_periodo_plataforma($periodo = NULL, $plataforma = NULL) {
        $this->db->select('av_aul_id, av_aul_nombre_corto as av_aul_codigo, av_aul_nombre as av_aul_descripcion, av_aul_alum_max');
        $this->db->from('av_aula');
        
        if (!empty($periodo)) {
            if ($this->db->field_exists('av_periodo', 'av_aula')) {
                $this->db->where('av_periodo', $periodo);
            } else {
                $this->db->like('av_aul_nombre_corto', $periodo);
            }
        }
        
        if (!empty($plataforma)) {
            if ($this->db->field_exists('cod_plat_edu_det', 'av_aula')) {
                $this->db->where('cod_plat_edu', '01');
                if ($plataforma === 'AP') {
                    $this->db->where('cod_plat_edu_det', '01');
                } else {
                    $this->db->where('cod_plat_edu_det', '02');
                }
            } else {
                if ($plataforma === 'AP') {
                    if ($this->db->field_exists('av_aul_nombre_corto', 'av_aula')) {
                        $this->db->like('av_aul_nombre_corto', 'AP');
                    } else {
                        $this->db->like('av_aul_codigo', 'AP');
                    }
                } else {
                    if ($this->db->field_exists('av_aul_nombre_corto', 'av_aula')) {
                        $this->db->not_like('av_aul_nombre_corto', 'AP');
                    } else {
                        $this->db->not_like('av_aul_codigo', 'AP');
                    }
                }
            }
        }
        
        return $this->db->get()->result_array();
    }

    /**
     * Obtiene el listado de aulas de av_aula filtradas por periodo, plataforma y docente asignado,
     * incluyendo el docente asignado desde av_docente + av_persona.
     */
    public function get_aulas_reporte($periodo = NULL, $plataforma = NULL, $docente_id = NULL) {
        if ($this->db->table_exists('av_persona')) {
            $this->db->select('a.av_aul_id, a.av_aul_nombre as av_aul_descripcion, a.av_aul_nombre_corto as av_aul_codigo, 
                               a.av_aul_alum_min, a.av_aul_alum_max, a.av_periodo,
                               p.av_per_nombre_pri, p.av_per_apepat, p.av_per_apemat, d.av_doc_correo, d.av_doc_id');
            $this->db->from('av_aula a');
            $this->db->join('av_inscripcion_gestor ig', "a.av_aul_id = ig.av_aul_id AND ig.av_ing_estado = 'A'", 'left');
            $this->db->join('av_docente d', 'ig.av_doc_id = d.av_doc_id', 'left');
            $this->db->join('av_persona p', 'd.av_per_id = p.av_per_id', 'left');
        } else {
            // Fallback local schema para pruebas
            $this->db->select('a.av_aul_id, a.av_aul_descripcion, a.av_aul_codigo, 
                               a.av_aul_alum_min, a.av_aul_alum_max, a.av_periodo,
                               d.av_doc_nombres as av_per_nombre_pri, d.av_doc_apellidos as av_per_apepat, "" as av_per_apemat, d.av_doc_email as av_doc_correo, d.av_doc_id');
            $this->db->from('av_aula a');
            $this->db->join('av_inscripcion_gestor ig', 'a.av_aul_id = ig.av_aul_id', 'left');
            $this->db->join('av_docente d', 'ig.av_doc_id = d.av_doc_id', 'left');
        }

        if (!empty($periodo)) {
            if ($this->db->field_exists('av_periodo', 'av_aula')) {
                $this->db->where('a.av_periodo', $periodo);
            } else {
                $this->db->like('a.av_aul_codigo', $periodo);
            }
        }

        if (!empty($plataforma)) {
            if ($this->db->field_exists('cod_plat_edu_det', 'av_aula')) {
                $this->db->where('a.cod_plat_edu', '01');
                if ($plataforma === 'AP') {
                    $this->db->where('a.cod_plat_edu_det', '01');
                } else {
                    $this->db->where('a.cod_plat_edu_det', '02');
                }
            } else {
                if ($plataforma === 'AP') {
                    if ($this->db->field_exists('av_aul_nombre_corto', 'av_aula')) {
                        $this->db->like('a.av_aul_nombre_corto', 'AP');
                    } else {
                        $this->db->like('a.av_aul_codigo', 'AP');
                    }
                } else {
                    if ($this->db->field_exists('av_aul_nombre_corto', 'av_aula')) {
                        $this->db->not_like('a.av_aul_nombre_corto', 'AP');
                    } else {
                        $this->db->not_like('a.av_aul_codigo', 'AP');
                    }
                }
            }
        }

        if (!empty($docente_id)) {
            $this->db->where('d.av_doc_id', $docente_id);
        }

        $this->db->order_by('a.av_aul_id', 'DESC');
        return $this->db->get()->result_array();
    }
}
