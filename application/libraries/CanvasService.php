<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CanvasService Library
 * 
 * Gestiona la integración dual con Canvas LMS para consultar aulas y matrículas
 * tanto en Canvas AP como en Canvas USMP.
 */
class CanvasService {

    protected $CI;
    protected $api_token_ap;
    protected $api_token_usmp;
    protected $domain_ap;
    protected $domain_usmp;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->config('canvas'); // Asume que existe un archivo config/canvas.php

        // Cargar tokens y dominios desde la configuración
        $this->api_token_ap = $this->CI->config->item('canvas_token_ap') ?: 'MOCK_TOKEN_AP';
        $this->api_token_usmp = $this->CI->config->item('canvas_token_usmp') ?: 'MOCK_TOKEN_USMP';
        
        $this->domain_ap = 'https://usmp.instructure.com';
        $this->domain_usmp = 'https://usmpvirtual.instructure.com';
    }

    /**
     * Obtiene el listado de estudiantes matriculados activos en un curso
     * Endpoint: GET /api/v1/courses/{course_id}/enrollments
     * 
     * @param string $canvas_course_id ID del curso en Canvas
     * @param string $instance Instancia de Canvas ('AP' o 'USMP')
     * @return array|bool Listado de matrículas activas o FALSE en caso de error
     */
    public function get_enrolled_students($canvas_course_id, $instance = 'USMP') {
        $domain = ($instance === 'AP') ? $this->domain_ap : $this->domain_usmp;
        $token = ($instance === 'AP') ? $this->api_token_ap : $this->api_token_usmp;

        // Construir URL. Filtramos por tipo StudentEnrollment y estado active
        $url = "{$domain}/api/v1/courses/{$canvas_course_id}/enrollments?type[]=StudentEnrollment&state[]=active&per_page=100";

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $response) {
            return json_decode($response, TRUE);
        }

        log_message('error', "Canvas API Enrollment failed for course {$canvas_course_id} in instance {$instance}: Status Code {$http_code} - Response: {$response}");
        return FALSE;
    }

    /**
     * Obtiene la cantidad total de estudiantes matriculados en un curso
     * 
     * @param string $canvas_course_id ID del curso en Canvas
     * @param string $instance Instancia de Canvas ('AP' o 'USMP')
     * @return int Cantidad de estudiantes o 0 si hay error o no hay alumnos
     */
    public function get_enrolled_students_count($canvas_course_id, $instance = 'USMP') {
        $enrollments = $this->get_enrolled_students($canvas_course_id, $instance);
        if (is_array($enrollments)) {
            return count($enrollments);
        }
        return 0;
    }

    /**
     * Obtiene los detalles de un curso en Canvas buscando por su SIS ID (shortname)
     * Endpoint: GET /api/v1/courses/sis_course_id:{sis_course_id}?include[]=total_students
     * 
     * @param string $sis_course_id Código corto / SIS ID del curso
     * @param string $instance Instancia de Canvas ('AP' o 'USMP')
     * @return array|bool Detalles del curso o FALSE si hay error
     */
    public function get_course_details($sis_course_id, $instance = 'USMP') {
        $token = ($instance === 'AP') ? $this->api_token_ap : $this->api_token_usmp;

        // Si el token es de simulación o vacío, retornar datos simulados al instante para evitar timeouts de red
        if (empty($token) || $token === 'MOCK_TOKEN_AP' || $token === 'MOCK_TOKEN_USMP' || strpos($token, 'MOCK') !== FALSE) {
            $hash = crc32($sis_course_id);
            $isPublicado = ($hash % 3 !== 0);
            return [
                'total_students' => abs($hash % 35) + 5,
                'workflow_state' => $isPublicado ? 'available' : 'claimed'
            ];
        }

        $domain = ($instance === 'AP') ? $this->domain_ap : $this->domain_usmp;
        $url = "{$domain}/api/v1/courses/sis_course_id:" . urlencode($sis_course_id) . "?include[]=total_students";

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Timeout rápido para evitar congelar la página

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $response) {
            return json_decode($response, TRUE);
        }

        return FALSE;
    }
}
