<?php

define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'cc6');        
define('DB_USER', 'postgres');
define('DB_PASS', 'HOLA');       ////////////////// ACA SE CAMBIA LA CONTRA

// --- Datos del Courier (los devuelve el WebService) ------------------
define('COURIER_ID', 'ENVIOS_EXPRESSO');  
define('COURIER_NOMBRE', 'Envíos Expresso');
define('CODIGO_ORIGEN', '01001');     

// --- URL base del sitio ----------------------------------------------
// Se calcula sola (ej. "/Courier"). Si los links salen mal, escríbela a mano:
// define('BASE_URL', '/Courier');
if (!defined('BASE_URL')) {
    $raiz    = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $docroot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    $base    = ($docroot !== '' && stripos($raiz, $docroot) === 0) ? substr($raiz, strlen($docroot)) : '';
    define('BASE_URL', rtrim($base, '/'));
}

date_default_timezone_set('America/Guatemala');
