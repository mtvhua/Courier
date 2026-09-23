<?php
// api_status.php
header("Access-Control-Allow-Origin: *");
include("conectar.php");

// Configuración general del Courier
$nombre_courrier = "Envíos Expresso";

// Captura y sanitización de parámetros de la consulta WebService
$orden = isset($_GET['orden']) ? pg_escape_string($conn, trim($_GET['orden'])) : '';
$tienda = isset($_GET['tienda']) ? pg_escape_string($conn, trim($_GET['tienda'])) : '';
$formato = isset($_GET['formato']) ? strtolower(trim($_GET['formato'])) : 'json'; // Formato por defecto JSON

$status_respuesta = "NO_ENCONTRADO";

if (!empty($orden) && !empty($tienda)) {
    // Consulta en la BD del Courier combinando orden e id_tienda/nombre tienda
    $query = "SELECT estado FROM Paquete WHERE numero_orden = '$orden' AND id_tienda = '$tienda'";
    $resultado = pg_query($conn, $query);

    if ($resultado && pg_num_rows($resultado) > 0) {
        $paquete = pg_fetch_assoc($resultado);
        // Formato exacto de estado: orden nueva, surtiéndose, empacándose, en ruta, entregada
        $status_respuesta = strtolower(trim($paquete['estado']));
    }
}

// GENERACIÓN DE RESPUESTA SEGÚN EL FORMATO SOLICITADO
if ($formato === 'xml') {
    // Respuesta en XML
    header("Content-Type: application/xml; charset=utf-8");
    
    $xml = new SimpleXMLElement('<orden/>');
    $xml->addChild('courrier', $nombre_courrier);
    $xml->addChild('orden', $orden);
    $xml->addChild('status', $status_respuesta);
    
    echo $xml->asXML();
} else {
    // Respuesta en JSON (por defecto o si formato=json)
    header("Content-Type: application/json; charset=utf-8");
    
    $respuesta_json = [
        "orden" => [
            "courrier" => $nombre_courrier,
            "orden" => $orden,
            "status" => $status_respuesta
        ]
    ];
    
    echo json_encode($respuesta_json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>