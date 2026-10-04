<?php
/* =====================================================================
 *  WebService: estado de una orden
 *  GET /status?orden=___&tienda=___&formato=json|xml
 *
 *  {"orden": {"courrier": "...", "orden": "ORD-1", "status": "en ruta"}}
 *  Si no existe → status "NO ENCONTRADA".
 * ===================================================================== */
require_once __DIR__ . '/../lib/formato.php';
require_once __DIR__ . '/../lib/paquetes.php';

$orden  = (string) parametro('orden', '');
$tienda = (string) parametro('tienda', '');

$paquete = ($orden !== '' && $tienda !== '') ? paquete_detalle('orden', $orden, $tienda) : null;

responder('orden', [
    'courrier' => COURIER_ID,
    'orden'    => $orden,
    'status'   => $paquete ? $paquete['estado'] : 'NO ENCONTRADA',
]);
