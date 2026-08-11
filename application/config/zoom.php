<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| CONFIGURACIÓN DE CREDENCIALES DE ZOOM API (Server-to-Server OAuth)
| -------------------------------------------------------------------
| Credenciales de producción provistas por la Unidad de Virtualización Académica (UVA)
*/

$config['zoom_client_id'] = getenv('ZOOM_CLIENT_ID') ?: 'MOCK_CLIENT_ID';
$config['zoom_client_secret'] = getenv('ZOOM_CLIENT_SECRET') ?: 'MOCK_CLIENT_SECRET';
$config['zoom_account_id'] = getenv('ZOOM_ACCOUNT_ID') ?: 'MOCK_ACCOUNT_ID';
$config['zoom_secret_token'] = getenv('ZOOM_SECRET_TOKEN') ?: 'MOCK_SECRET_TOKEN'; // Opcional, para verificación de webhooks
