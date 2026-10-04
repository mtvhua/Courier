<?php
/* =====================================================================
 *  API interna: detalle de un paquete por tracking ID
 *  (la usa rastrear.php con fetch)
 *  GET /api/rastreo.php?tracking=EXP20261003ABCDE
 * ===================================================================== */
require_once __DIR__ . '/../lib/formato.php';
require_once __DIR__ . '/../lib/paquetes.php';

$tracking = strtoupper((string) parametro('tracking', ''));
$p = $tracking !== '' ? paquete_detalle('tracking', $tracking) : null;

if (!$p) {
    responder('rastreo', ['encontrado' => false, 'tracking' => $tracking], null, 404);
}

responder('rastreo', [
    'encontrado'   => true,
    'tracking'     => $p['tracking_id'],
    'orden'        => (string) $p['numero_orden'],
    'destinatario' => $p['destinatario'],
    'destino'      => $p['ciudad_destino'],
    'codigo'       => $p['codigo_destino'],
    'status'       => $p['estado'],
    'paso'         => estado_numero($p['estado']), // 1..5
]);
