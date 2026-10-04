<?php
/* =====================================================================
 *  WebService: costo de envío hacia un destino
 *  GET  /consulta?destino=02001&formato=json|xml
 *
 *  {"consultaprecio": {"courrier": "...", "destino": "02001", "cobertura": "TRUE", "costo": "20.00"}}
 *  Si no hay cobertura → cobertura FALSE y costo 0.
 * ===================================================================== */
require_once __DIR__ . '/../lib/formato.php';
require_once __DIR__ . '/../lib/paquetes.php';

$destino = (string) parametro('destino', '');
$ruta    = strlen($destino) === 5 ? ruta_hacia($destino) : null;

responder('consultaprecio', [
    'courrier'  => COURIER_ID,
    'destino'   => $destino,
    'cobertura' => $ruta ? 'TRUE' : 'FALSE',
    'costo'     => $ruta ? number_format((float) $ruta['precio'], 2, '.', '') : '0',
]);
