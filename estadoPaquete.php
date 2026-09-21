<?php
session_start();
include("conectar.php"); // Conexión a la base de datos

$paquete_info = null;
$error_mensaje = "";
$admin_mensaje = "";
$tipo_admin_msj = "exito";

// Verificar si el usuario tiene privilegios de administrador
$es_admin = isset($_SESSION['administrador']) && $_SESSION['administrador'] === true;

if (!$es_admin) {
    header("Location: index.php");
    exit();
}

// 1. BUSCAR PAQUETE POR NÚMERO DE ORDEN (GET)
if ($_SERVER["REQUEST_METHOD"] == "GET" && !empty($_GET['numero_orden'])) {
    $orden = pg_escape_string($conn, trim($_GET['numero_orden']));
    
    $query = "SELECT tracking_id, numero_orden, estado, peso, tamano, id_usuario 
              FROM Paquete 
              WHERE numero_orden = '$orden'";
    $resultado = pg_query($conn, $query);
    
    if ($resultado && pg_num_rows($resultado) > 0) {
        $paquete_info = pg_fetch_assoc($resultado);
    } else {
        $error_mensaje = "No se encontró ningún paquete asociado al número de orden: " . htmlspecialchars($orden);
    }
}

// 2. ACTUALIZAR ÚNICAMENTE EL ESTADO DEL PAQUETE (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_estado') {
    $orden = pg_escape_string($conn, trim($_POST['numero_orden']));
    $nuevo_estado = pg_escape_string($conn, $_POST['estado']);

    $sql = "UPDATE Paquete SET estado = '$nuevo_estado' WHERE numero_orden = '$orden'";
    $res = pg_query($conn, $sql);

    if ($res) {
        if (pg_affected_rows($res) > 0) {
            $admin_mensaje = "El estado de la orden #$orden ha sido actualizado exitosamente a: " . strtoupper($nuevo_estado);
            // Recargar la información actualizada del paquete
            $query_reload = "SELECT tracking_id, numero_orden, estado, peso, tamano, id_usuario 
                            FROM Paquete 
                            WHERE numero_orden = '$orden'";
            $res_reload = pg_query($conn, $query_reload);
            if ($res_reload && pg_num_rows($res_reload) > 0) {
                $paquete_info = pg_fetch_assoc($res_reload);
            }
        } else {
            $admin_mensaje = "No se realizaron cambios o el paquete especificado no existe.";
            $tipo_admin_msj = "error";
        }
    } else {
        $admin_mensaje = "Error al actualizar el estado: " . pg_last_error($conn);
        $tipo_admin_msj = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestionar Estado de Paquete - Envíos Expresso</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <style>
    .admin-box {
      background: rgba(244,246,244,0.03); 
      padding: 24px; 
      border-radius: 8px; 
      border: 1px solid rgba(244,246,244,0.1);
      margin-top: 20px;
    }
    .form-group {
      margin-bottom: 16px;
    }
    .form-group label {
      display: block;
      margin-bottom: 6px;
      font-weight: 500;
      color: #F4F6F4;
    }
    .form-control {
      width: 100%;
      padding: 10px;
      border: 1px solid rgba(255,255,255,0.3);
      border-radius: 4px;
      background: rgba(244,246,244,0.05);
      color: #F4F6F4;
      outline: none;
      box-sizing: border-box;
    }
    .form-control:focus {
      border-color: var(--marigold);
    }
    select.form-control option {
      background: var(--ink);
      color: #F4F6F4;
    }
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 16px;
      background: rgba(255,255,255,0.05);
      padding: 16px;
      border-radius: 6px;
      margin-bottom: 20px;
      border-left: 4px solid var(--marigold);
    }
    .info-item span {
      display: block;
      font-size: 0.8rem;
      color: rgba(244,246,244,0.6);
      text-transform: uppercase;
    }
    .info-item strong {
      font-size: 1rem;
      color: #F4F6F4;
    }
  </style>
</head>
<body>

<header>
  <div class="header-row">
    <div class="brand">
      <a href="index.php" style="text-decoration: none; display: flex; align-items: center; gap: 12px; color: inherit;">
        <img src="Logo.png" alt="Logo Envíos Expresso" class="logo-img">
        <div>
          <div class="brand-name">Envíos Expresso</div>
          <div class="brand-tag">Como un shot de cafe</div>
        </div>
      </a>
    </div>
    <nav aria-label="Cuenta y seguimiento">
      <a href="RastrearPedido.php">← Panel de Rastreo</a>
    </nav>
  </div>
</header>

<main class="wrap" style="padding-top: 30px; padding-bottom: 60px;">
  <section class="board-section">
    <div class="board-head">
      <div>
        <h2 style="color: var(--marigold);">Modificar Estado de Paquete</h2>
        <span>Panel exclusivo para administradores</span>
      </div>
    </div>

    <div class="board" style="padding: 24px; color: #F4F6F4;">
      
      <!-- MENSAJES DE NOTIFICACIÓN -->
      <?php if($admin_mensaje != ""): ?>
        <div style="background-color: <?php echo ($tipo_admin_msj == 'error') ? 'rgba(229, 62, 62, 0.2)' : 'rgba(72, 187, 120, 0.2)'; ?>; border: 1px solid <?php echo ($tipo_admin_msj == 'error') ? '#e53e3e' : '#48bb78'; ?>; padding: 12px; border-radius: 4px; margin-bottom: 24px; font-weight: 500; color: <?php echo ($tipo_admin_msj == 'error') ? '#ffa4a4' : '#9ae6b4'; ?>;">
          <?php echo $admin_mensaje; ?>
        </div>
      <?php endif; ?>

      <?php if($error_mensaje != ""): ?>
        <div style="background-color: rgba(229, 62, 62, 0.2); color: #ffa4a4; padding: 12px; border: 1px solid #e53e3e; border-radius: 4px; margin-bottom: 24px;">
          <?php echo $error_mensaje; ?>
        </div>
      <?php endif; ?>

      <!-- FORMULARIO 1: BUSCAR POR NÚMERO DE ORDEN -->
      <div class="admin-box">
        <h3 style="margin-top: 0; color: var(--marigold); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; font-size: 1.1rem;">1. Buscar Orden</h3>
        <form action="estadoPaquete.php" method="GET" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
          <div style="flex: 1; min-width: 250px;">
            <label for="numero_orden" style="display: block; margin-bottom: 6px; font-weight: 500;">Número de Orden:</label>
            <input type="text" id="numero_orden" name="numero_orden" class="form-control" value="<?php echo isset($_GET['numero_orden']) ? htmlspecialchars($_GET['numero_orden']) : ''; ?>" placeholder="Ej. 0002" required>
          </div>
          <button type="submit" class="primary" style="padding: 11px 24px; cursor: pointer; border: none; border-radius: 4px; font-weight: 600;">Cargar Paquete</button>
        </form>
      </div>

      <!-- FORMULARIO 2: DETALLES Y CAMBIO DE ESTADO -->
      <?php if($paquete_info): ?>
      <div class="admin-box">
        <h3 style="margin-top: 0; color: var(--marigold); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; font-size: 1.1rem;">2. Actualizar Estado</h3>
        
        <div class="info-grid">
          <div class="info-item">
            <span>N. Orden</span>
            <strong><?php echo htmlspecialchars($paquete_info['numero_orden']); ?></strong>
          </div>
          <div class="info-item">
            <span>Tracking ID</span>
            <strong><?php echo htmlspecialchars($paquete_info['tracking_id']); ?></strong>
          </div>
          <div class="info-item">
            <span>ID Usuario</span>
            <strong><?php echo htmlspecialchars($paquete_info['id_usuario']); ?></strong>
          </div>
          <div class="info-item">
            <span>Estado Actual</span>
            <strong style="color: var(--marigold);"><?php echo strtoupper(htmlspecialchars($paquete_info['estado'])); ?></strong>
          </div>
        </div>

        <form action="estadoPaquete.php" method="POST">
          <input type="hidden" name="accion" value="actualizar_estado">
          <input type="hidden" name="numero_orden" value="<?php echo htmlspecialchars($paquete_info['numero_orden']); ?>">
          
          <div class="form-group">
            <label for="estado">Seleccionar Nuevo Estado:</label>
            <select name="estado" id="estado" class="form-control" required>
              <option value="orden nueva" <?php echo ($paquete_info['estado'] == 'orden nueva') ? 'selected' : ''; ?>>Orden Nueva</option>
              <option value="surtiendose" <?php echo ($paquete_info['estado'] == 'surtiendose') ? 'selected' : ''; ?>>Surtiéndose</option>
              <option value="empacandose" <?php echo ($paquete_info['estado'] == 'empacandose') ? 'selected' : ''; ?>>Empacándose</option>
              <option value="en ruta" <?php echo ($paquete_info['estado'] == 'en ruta') ? 'selected' : ''; ?>>En Ruta</option>
              <option value="entregada" <?php echo ($paquete_info['estado'] == 'entregada') ? 'selected' : ''; ?>>Entregada</option>
            </select>
          </div>

          <button type="submit" class="primary" style="width: 100%; padding: 12px; cursor: pointer; border: none; border-radius: 4px; font-weight: 600;">Guardar Nuevo Estado</button>
        </form>
      </div>
      <?php endif; ?>

    </div>
  </section>
</main>

</body>
</html>