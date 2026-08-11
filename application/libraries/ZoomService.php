<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ZoomService Library
 * 
 * Gestiona la comunicación con la API de Zoom, incluyendo la verificación de firmas
 * de webhooks y la obtención de reportes de asistencia de participantes.
 */
class ZoomService {

    protected $CI;
    protected $client_id;
    protected $client_secret;
    protected $account_id;
    protected $secret_token; // Token de verificación para Webhooks de Zoom

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->config('zoom'); // Asume que existe un archivo config/zoom.php
        
        // Cargar credenciales desde la configuración
        $this->client_id = $this->CI->config->item('zoom_client_id') ?: 'MOCK_CLIENT_ID';
        $this->client_secret = $this->CI->config->item('zoom_client_secret') ?: 'MOCK_CLIENT_SECRET';
        $this->account_id = $this->CI->config->item('zoom_account_id') ?: 'MOCK_ACCOUNT_ID';
        $this->secret_token = $this->CI->config->item('zoom_secret_token') ?: 'MOCK_SECRET_TOKEN';
    }

    /**
     * Valida la firma del Webhook de Zoom para garantizar la autenticidad
     * 
     * @param string $signature Firma provista en el header 'x-zm-signature'
     * @param string $timestamp Timestamp provisto en el header 'x-zm-request-timestamp'
     * @param string $payload Body en texto plano de la petición recibida
     * @return bool
     */
    public function verify_webhook_signature($signature, $timestamp, $payload) {
        if (empty($signature) || empty($timestamp) || empty($payload)) {
            return FALSE;
        }

        // Construir el mensaje para cifrar
        $message = 'v0:' . $timestamp . ':' . $payload;
        
        // Generar hash HMAC-SHA256
        $hash = hash_hmac('sha256', $message, $this->secret_token);
        $expected_signature = 'v0=' . $hash;

        return hash_equals($expected_signature, $signature);
    }

    /**
     * Obtiene el Access Token de Zoom mediante Server-to-Server OAuth
     * 
     * @return string|bool Token de acceso o FALSE en caso de error
     */
    public function get_access_token() {
        $url = 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . $this->account_id;
        
        $headers = [
            'Authorization: Basic ' . base64_encode($this->client_id . ':' . $this->client_secret)
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $response) {
            $data = json_decode($response, TRUE);
            return isset($data['access_token']) ? $data['access_token'] : FALSE;
        }

        log_message('error', 'Zoom API OAuth failed: Status Code ' . $http_code . ' - Response: ' . $response);
        return FALSE;
    }

    /**
     * Obtiene el reporte de participantes de una reunión específica (Zoom Reports API)
     * Endpoint: GET /v2/report/meetings/{meetingId}/participants
     * 
     * @param string $meeting_id ID único de la reunión
     * @return array|bool Listado de participantes o FALSE si ocurre un error
     */
    public function get_meeting_participants($meeting_id) {
        $token = $this->get_access_token();
        if (!$token) {
            return FALSE;
        }

        // Zoom requiere codificar o limpiar el ID de la reunión si tiene barras diagonales
        $clean_meeting_id = urlencode(urlencode($meeting_id));
        $url = "https://api.zoom.us/v2/report/meetings/{$clean_meeting_id}/participants?page_size=300";

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
            $data = json_decode($response, TRUE);
            return isset($data['participants']) ? $data['participants'] : [];
        }

        log_message('error', "Zoom API get_meeting_participants failed for meeting {$meeting_id}: Status Code {$http_code} - Response: {$response}");
        return FALSE;
    }

    /**
     * Obtiene los detalles de una reunión (Zoom Meeting API)
     * Endpoint: GET /v2/meetings/{meetingId}
     * 
     * @param string $meeting_id ID único de la reunión
     * @return array|bool Datos de la reunión o FALSE si ocurre un error
     */
    public function get_meeting_details($meeting_id) {
        $token = $this->get_access_token();
        if (!$token) {
            return FALSE;
        }

        $clean_meeting_id = urlencode(urlencode($meeting_id));
        $url = "https://api.zoom.us/v2/meetings/{$clean_meeting_id}";

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

        log_message('error', "Zoom API get_meeting_details failed for meeting {$meeting_id}: Status Code {$http_code} - Response: {$response}");
        return FALSE;
    }

    /**
     * Obtiene todos los usuarios activos de Zoom paginados y filtra los que tienen licencia (type = 2)
     * Endpoint: GET /v2/users?status=active&page_size=300
     * 
     * @return array|bool Mapa de correos electrónicos de usuarios licenciados [email => true] o FALSE
     */
    public function get_licensed_users() {
        $token = $this->get_access_token();
        if (!$token) {
            return FALSE;
        }

        $licensed_emails = [];
        $next_page_token = '';
        
        do {
            $url = "https://api.zoom.us/v2/users?status=active&page_size=300";
            if (!empty($next_page_token)) {
                $url .= "&next_page_token=" . urlencode($next_page_token);
            }

            $headers = [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 200 && $response) {
                $data = json_decode($response, TRUE);
                if (isset($data['users']) && is_array($data['users'])) {
                    foreach ($data['users'] as $user) {
                        // type = 2 es "Licensed" en Zoom
                        if (isset($user['type']) && (int)$user['type'] === 2) {
                            $licensed_emails[strtolower($user['email'])] = TRUE;
                        }
                    }
                }
                $next_page_token = isset($data['next_page_token']) ? $data['next_page_token'] : '';
            } else {
                log_message('error', "Zoom API get_licensed_users page fetch failed: Status Code {$http_code} - Response: {$response}");
                $next_page_token = ''; // Terminar ciclo en caso de error
            }
        } while (!empty($next_page_token));

        return $licensed_emails;
    }
}
