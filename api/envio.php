<?php
/* =====================================================================
 *  WebService: la tienda solicita un envío
 *  GET|POST /envio?orden=___&destinatario=___&destino=___&direccion=___&tienda=___[&formato=json|xml]
 *
 *  El enunciado no define la respuesta; devolvemos:
 *  {"envio": {"courrier": "...", "orden": "...", "tracking": "EXP2026...", "status": "orden nueva", "costo": "20.00"}}
 *  Si falla: status = "ERROR" y "mensaje" con la razón.
 *
 *  Si la misma tienda vuelve a mandar la misma orden, se devuelve el envío
 *  ya existente (no se duplica).
 * ===================================================================== */
require_once __DIR__ . '/../lib/formato.php';
require_once __DIR__ . '/../lib/paquetes.php';

$orden        = (string) parametro('orden', '');
$destinatario = (string) parametro('destinatario', '');
$destino      = (string) parametro('destino', '');
$direccion    = (string) parametro('direccion', '');
$tienda       = (string) parametro('tienda', '');

function error_envio(string $orden, string $mensaje, int $http = 400): void
{
    responder('envio', [
        'courrier' => COURIER_ID,
        'orden'    => $orden,
        'tracking' => '',
        'status'   => 'ERROR',
        'mensaje'  => $mensaje,
    ], null, $http);
}

// --- Validaciones ---------------------------------------------------
$faltan = [];
foreach (['orden', 'destinatario', 'destino', 'direccion', 'tienda'] as $campo) {
    if ($$campo === '') $faltan[] = $campo;
}
if ($faltan) {
    error_envio($orden, 'Faltan parámetros: ' . implode(', ', $faltan));
}
if (!db_uno('SELECT 1 FROM Tienda WHERE id_tienda = $1', [$tienda])) {
    error_envio($orden, "La tienda '$tienda' no está registrada en este courier", 403);
}
$ruta = ruta_hacia($destino);
if (!$ruta) {
    error_envio($orden, "No hay cobertura hacia el destino '$destino'");
}

// --- ¿Ya existía? ---------------------------------------------------
$paquete = paquete_detalle('orden', $orden, $tienda);

if (!$paquete) {
    try {
        $paquete = crear_paquete([
            'numero_orden' => $orden,
            'destinatario' => $destinatario,
            'destino'      => $destino,
            'direccion'    => $direccion,
            'id_tienda'    => $tienda,
        ]);
    } catch (Throwable $e) {
        error_envio($orden, db_mensaje_error($e), 500);
    }
}

responder('envio', [
    'courrier' => COURIER_ID,
    'orden'    => $orden,
    'tracking' => $paquete['tracking_id'],
    'status'   => $paquete['estado'],
    'costo'    => number_format((float) $ruta['precio'], 2, '.', ''),
]);
