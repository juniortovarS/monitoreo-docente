<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| CONFIGURACIÓN DE CANVAS LMS API
| -------------------------------------------------------------------
| Token de acceso personal generado en:
| Canvas AP → Cuenta → Configuración → Tokens de acceso aprobados → Nuevo token de acceso
|
| IMPORTANTE: Reemplaza los valores MOCK por los tokens reales.
*/

// Canvas AP (usmp.instructure.com)
$config['canvas_token_ap']   = 'MOCK_TOKEN_AP';
$config['canvas_domain_ap']  = 'https://usmp.instructure.com';

// Canvas USMP (usmpvirtual.instructure.com)  
$config['canvas_token_usmp']  = 'MOCK_TOKEN_USMP';
$config['canvas_domain_usmp'] = 'https://usmpvirtual.instructure.com';
