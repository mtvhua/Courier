<?php
// consultar_pedido.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("conectar.php");

$estado_actual = "";
$error_mensaje = "";
$mis_paquetes = [];

// 1. Obtener pedidos si el usuario ha iniciado sesión
if (isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario'])) {
    $id_usuario_sesion = (int)$_SESSION['id_usuario'];
    
    $query_usuario = "SELECT tracking_id, numero_orden, estado, peso, tamano 
                      FROM Paquete 
                      WHERE id_usuario = $id_usuario_sesion 
                      ORDER BY numero_orden DESC";
    
    $res_usuario = pg_query($conn, $query_usuario);
    if ($res_usuario && pg_num_rows($res_usuario) > 0) {
        while ($fila = pg_fetch_assoc($res_usuario)) {
            $mis_paquetes[] = $fila;
        }
    }
}

// 2. Búsqueda por Orden y Tracking
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['orden'], $_GET['tracking'])) {
    $orden_input = trim($_GET['orden']);
    $tracking_input = trim($_GET['tracking']);

    if (empty($orden_input) || empty($tracking_input)) {
        $error_mensaje = "Por favor, ingresa tanto el Número de Orden como el Tracking ID.";
    } else {
        $orden_clean = pg_escape_string($conn, $orden_input);
        $tracking_clean = pg_escape_string($conn, $tracking_input);

        $query = "SELECT estado 
                  FROM Paquete 
                  WHERE numero_orden ILIKE '$orden_clean' 
                    AND tracking_id ILIKE '$tracking_clean'";

        $resultado = pg_query($conn, $query);

        if ($resultado && pg_num_rows($resultado) > 0) {
            $paquete = pg_fetch_assoc($resultado);
            $estado_actual = trim(strtolower($paquete['estado']));
        } else {
            $error_mensaje = "El Número de Orden y el Tracking ID no coinciden o no existen.";
        }
    }
}
?>