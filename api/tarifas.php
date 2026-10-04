<?php
/* =====================================================================
 *  API interna: todas las tarifas (la usa index.php con fetch)
 *  GET /api/tarifas.php?formato=json|xml
 * ===================================================================== */
require_once __DIR__ . '/../lib/formato.php';
require_once __DIR__ . '/../lib/paquetes.php';

$rutas = [];
foreach (tarifas() as $t) {
    $rutas[] = [
        'destino'       => $t['destino'],
        'codigo_postal' => $t['codigo_postal'],
        'precio'        => (float) $t['precio'],
    ];
}

responder('tarifas', [
    'courrier' => COURIER_ID,
    'origen'   => CODIGO_ORIGEN,
    'rutas'    => $rutas,
]);
