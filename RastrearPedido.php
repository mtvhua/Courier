<?php
session_start();
include("conectar.php"); 

$estado_actual = "";
$error_mensaje = "";
$admin_mensaje = "";
$tipo_admin_msj = "exito";

$es_admin = isset($_SESSION['administrador']) &&$_SESSION['administrador'] === true;
$esta_logueado = isset($_SESSION['id_usuario']) || isset($_SESSION['usuario']) || $es_admin;

// LÓGICA DE CONSULTA (Actualizada para numero_orden y tracking_id)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['orden'], $_GET['tracking'])) {
    if (!$esta_logueado) {
        // Bloqueo si no hay sesión
        $error_mensaje = "Debes iniciar sesión en tu cuenta para poder consultar el estado de tus paquetes.";
    } else {
        // Consulta normal si hay sesión
        $orden = pg_escape_string($conn, trim($_GET['orden']));
        $tracking = pg_escape_string($conn, trim($_GET['tracking']));

        if (!empty($orden) && !empty($tracking)) {
            $sql = "SELECT estado FROM Paquete WHERE numero_orden = '$orden' AND tracking_id = '$tracking'";
            $res = pg_query($conn, $sql);

            if ($res && pg_num_rows($res) > 0) {
                $row = pg_fetch_assoc($res);
                $estado_actual = strtolower(trim($row['estado']));
            } else {
                $error_mensaje = "No se encontró ningún paquete que coincida con el Número de Orden y Tracking ID proporcionados.";
            }
        } else {
            $error_mensaje = "Por favor ingresa tanto el Número de Orden como el Tracking ID.";
        }
    }
}

// LÓGICA DE ADMINISTRADOR
if ($es_admin && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {
    $accion =$_POST['accion'];

    if ($accion == "agregar") {
        $tracking = pg_escape_string($conn,$_POST['tracking_id']);
        $orden = pg_escape_string($conn, $_POST['numero_orden']);$peso = (float)$_POST['peso'];$tamano = pg_escape_string($conn,$_POST['tamano']);
        $id_usuario = (int)$_POST['id_usuario'];
        $id_ruta = (int)$_POST['id_ruta'];
        $id_tienda = !empty($_POST['id_tienda']) ? "'" . pg_escape_string($conn,$_POST['id_tienda']) . "'" : "NULL";
        $estado = pg_escape_string($conn,$_POST['estado']);

        $sql = "INSERT INTO Paquete (tracking_id, numero_orden, estado, peso, tamano, id_usuario, id_ruta, id_tienda) 
                VALUES ('$tracking', '$orden', '$estado',$peso, '$tamano',$id_usuario, $id_ruta,$id_tienda)";
        
        if (pg_query($conn, $sql)) {$admin_mensaje = "Paquete agregado exitosamente.";
        } else {
            $admin_mensaje = "Error al agregar paquete: " . pg_last_error($conn);$tipo_admin_msj = "error";
        }
    } 
    elseif ($accion == "editar") {
        $orden = pg_escape_string($conn,$_POST['numero_orden']);
        $estado = pg_escape_string($conn, $_POST['estado']);$peso = (float)$_POST['peso'];$sql = "UPDATE Paquete SET estado = '$estado', peso = $peso WHERE numero_orden = '$orden'";
        $res = pg_query($conn,$sql);
        
        if ($res) {
            if (pg_affected_rows($res) > 0) {$admin_mensaje = "Paquete actualizado exitosamente.";
            } else {
                $admin_mensaje = "No se encontró un paquete con ese número de orden para actualizar.";
                $tipo_admin_msj = "error";
            }
        } else {
            $admin_mensaje = "Error al actualizar: " . pg_last_error($conn);$tipo_admin_msj = "error";
        }
    } 
    elseif ($accion == "eliminar") {
        $orden = pg_escape_string($conn, $_POST['numero_orden']);$sql = "DELETE FROM Paquete WHERE numero_orden = '$orden'";
        $res = pg_query($conn,$sql);
        
        if ($res) {
            if (pg_affected_rows($res) > 0) {$admin_mensaje = "Paquete eliminado exitosamente.";
            } else {
                $admin_mensaje = "No se encontró la orden especificada.";
                $tipo_admin_msj = "error";
            }
        } else {
            $admin_mensaje = "Error al eliminar: " . pg_last_error($conn);$tipo_admin_msj = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rastrear Pedido - Envíos Expresso</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <style>
    .admin-form input, .admin-form select {
      width: 100%; padding: 10px; border: 1px solid rgba(255,255,255,0.3); border-radius: 4px; 
      background: rgba(244,246,244,0.05); color: #F4F6F4; outline: none; margin-bottom: 12px; margin-top: 4px;
    }
    .admin-form input:focus, .admin-form select:focus {
      border-color: var(--marigold);
    }
    .admin-form select option { background: var(--ink); color: #F4F6F4; }
    .admin-box {
      background: rgba(244,246,244,0.03); padding: 20px; border-radius: 8px; border: 1px solid rgba(244,246,244,0.1);
    }
    .btn-admin {
      width: 100%; padding: 11px; cursor: pointer; border: none; border-radius: 4px; font-weight: 600; margin-top: 8px;
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
      <a href="index.php">← Regresar a Inicio</a>
      <a href="RastrearPedido.php" class="primary">Rastrear pedido</a>
    </nav>
  </div>
</header>

<main class="wrap" style="padding-top: 30px; padding-bottom: 60px;">
  
  <!-- VISTA CLIENTE -->
  <section class="board-section" id="section-client">
    <div class="board-head" style="display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h2>Seguimiento de Paquete</h2>
        <span>Consulta el estado de tu envío</span>
      </div>
      <?php if($es_admin): ?>
      <button type="button" onclick="switchRole('admin')" style="background: none; border: 1px solid var(--muted); padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; color: var(--muted); font-weight: 500;">
        Panel Administrador
      </button>
      <?php endif; ?>
    </div>
    
    <div class="board" style="padding: 24px; color: #F4F6F4;">
      <form action="RastrearPedido.php" method="GET" style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 30px; align-items: flex-end;">
        <div style="flex: 1; min-width: 200px;">
          <label for="orden" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem;">Número de Orden:</label>
          <input type="text" id="orden" name="orden" value="<?php echo isset($_GET['orden']) ? htmlspecialchars($_GET['orden']) : ''; ?>" placeholder="Ej. 0002" required style="width: 100%; padding: 10px; border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; background: rgba(255,255,255,0.07); color: #fff;">
        </div>
        
        <div style="flex: 1; min-width: 200px;">
          <label for="tracking" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem;">Tracking ID:</label>
          <input type="text" id="tracking" name="tracking" value="<?php echo isset($_GET['tracking']) ? htmlspecialchars($_GET['tracking']) : ''; ?>" placeholder="Ej. PKG20260921JC3Q" required style="width: 100%; padding: 10px; border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; background: rgba(255,255,255,0.07); color: #fff;">
        </div>

        <div>
          <button type="submit" style="padding: 10px 24px; cursor: pointer; border: none; border-radius: 4px; font-weight: 700; background: #EAEAEA; color: #111; font-size: 0.95rem;">Consultar</button>
        </div>
      </form>

      <?php if($error_mensaje != ""): ?>
        <div style="background-color: rgba(229, 62, 62, 0.2); color: #ffa4a4; padding: 12px; border: 1px solid #e53e3e; border-radius: 4px; margin-bottom: 20px;">
          <?php echo htmlspecialchars($error_mensaje); ?>
        </div>
      <?php endif; ?>

      <!-- Panel de Resultado -->
      <div id="tracking-result" style="display: none; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 24px; margin-top: 10px;">
        <div style="background: rgba(255,255,255,0.03); border-left: 3px solid var(--marigold); padding: 12px 16px; border-radius: 0 4px 4px 0; margin-bottom: 35px;">
          <span style="font-size: 0.75rem; color: rgba(244,246,244,0.6); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">ESTADO ACTUAL</span>
          <h3 id="status-text" style="margin: 4px 0 0 0; color: #F4F6F4; font-size: 1.3rem; font-weight: 800;">—</h3>
        </div>
        
        <div class="timeline-wrapper" style="position: relative; margin: 30px 10px 20px 10px;">
          <!-- Línea base gris -->
          <div style="position: absolute; top: 20px; left: 10%; right: 10%; height: 2px; background: rgba(255,255,255,0.2); z-index: 1;"></div>
          <!-- Línea activa amarilla -->
          <div id="timeline-progress" style="position: absolute; top: 20px; left: 10%; height: 2px; background: var(--marigold); width: 0%; z-index: 2; transition: width 0.4s ease;"></div>

          <div style="display: flex; justify-content: space-between; position: relative; z-index: 3;">
            <div class="step-node" id="step-node-1" style="display: flex; flex-direction: column; align-items: center; flex: 1;">
              <div class="icon-circle" style="width: 40px; height: 40px; border-radius: 50%; background: var(--ink); border: 2px solid rgba(255,255,255,0.3); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease;">1</div>
              <span class="step-title" style="margin-top: 10px; font-size: 0.85rem; color: rgba(255,255,255,0.6); text-align: center;">Orden Nueva</span>
            </div>
            <div class="step-node" id="step-node-2" style="display: flex; flex-direction: column; align-items: center; flex: 1;">
              <div class="icon-circle" style="width: 40px; height: 40px; border-radius: 50%; background: var(--ink); border: 2px solid rgba(255,255,255,0.3); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease;">2</div>
              <span class="step-title" style="margin-top: 10px; font-size: 0.85rem; color: rgba(255,255,255,0.6); text-align: center;">Surtiéndose</span>
            </div>
            <div class="step-node" id="step-node-3" style="display: flex; flex-direction: column; align-items: center; flex: 1;">
              <div class="icon-circle" style="width: 40px; height: 40px; border-radius: 50%; background: var(--ink); border: 2px solid rgba(255,255,255,0.3); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease;">3</div>
              <span class="step-title" style="margin-top: 10px; font-size: 0.85rem; color: rgba(255,255,255,0.6); text-align: center;">Empacándose</span>
            </div>
            <div class="step-node" id="step-node-4" style="display: flex; flex-direction: column; align-items: center; flex: 1;">
              <div class="icon-circle" style="width: 40px; height: 40px; border-radius: 50%; background: var(--ink); border: 2px solid rgba(255,255,255,0.3); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease;">4</div>
              <span class="step-title" style="margin-top: 10px; font-size: 0.85rem; color: rgba(255,255,255,0.6); text-align: center;">En Ruta</span>
            </div>
            <div class="step-node" id="step-node-5" style="display: flex; flex-direction: column; align-items: center; flex: 1;">
              <div class="icon-circle" style="width: 40px; height: 40px; border-radius: 50%; background: var(--ink); border: 2px solid rgba(255,255,255,0.3); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease;">5</div>
              <span class="step-title" style="margin-top: 10px; font-size: 0.85rem; color: rgba(255,255,255,0.6); text-align: center;">Entregada</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  
  <!-- VISTA ADMINISTRADOR -->
  <?php if($es_admin): ?>
  <section class="board-section" id="section-admin" style="display: none;">
    <div class="board-head" style="display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h2 style="color: var(--marigold);">Gestión de Paquetes (Admin)</h2>
        <span>Agregar, actualizar y eliminar paquetes en la base de datos</span>
      </div>
      <button type="button" onclick="switchRole('client')" style="background: none; border: 1px solid var(--muted); padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; color: var(--muted); font-weight: 500;">
        Volver a Rastreo
      </button>
    </div>

    <div class="board" style="padding: 24px; color: #F4F6F4;">
      
      <?php if($admin_mensaje != ""): ?>
        <div style="background-color: <?php echo ($tipo_admin_msj == 'error') ? 'rgba(229, 62, 62, 0.2)' : 'rgba(72, 187, 120, 0.2)'; ?>; border: 1px solid <?php echo ($tipo_admin_msj == 'error') ? '#e53e3e' : '#48bb78'; ?>; padding: 12px; border-radius: 4px; margin-bottom: 24px; font-weight: 500; color: <?php echo ($tipo_admin_msj == 'error') ? '#ffa4a4' : '#9ae6b4'; ?>;">
          <?php echo $admin_mensaje; ?>
        </div>
      <?php endif; ?>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
        
        <!-- AGREGAR -->
        <div class="admin-box">
          <h3 style="margin-top: 0; color: var(--marigold); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; font-size: 1.1rem;">Agregar Nuevo Paquete</h3>
          <form action="RastrearPedido.php" method="POST" class="admin-form">
            <input type="hidden" name="accion" value="agregar">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0 12px;">
              <div>
                <label>Tracking ID</label>
                <input type="text" name="tracking_id" required>
              </div>
              <div>
                <label>N. de Orden</label>
                <input type="text" name="numero_orden" required>
              </div>
              <div>
                <label>Peso (kg)</label>
                <input type="number" step="0.01" name="peso" required>
              </div>
              <div>
                <label>Tamaño</label>
                <input type="text" name="tamano" placeholder="Ej. Mediano" required>
              </div>
              <div>
                <label>ID Usuario</label>
                <input type="number" name="id_usuario" required>
              </div>
              <div>
                <label>ID Ruta</label>
                <input type="number" name="id_ruta" required>
              </div>
            </div>
            <label>ID Tienda (Opcional)</label>
            <input type="text" name="id_tienda">
            
            <label>Estado Inicial</label>
            <select name="estado" required>
              <option value="orden nueva">Orden Nueva</option>
              <option value="surtiendose">Surtiéndose</option>
              <option value="empacandose">Empacándose</option>
              <option value="en ruta">En Ruta</option>
              <option value="entregada">Entregada</option>
            </select>
            
            <button type="submit" class="btn-admin primary">Guardar Paquete</button>
          </form>
        </div>

        <div style="display: flex; flex-direction: column; gap: 24px;">
          <!-- EDITAR -->
          <div class="admin-box">
            <h3 style="margin-top: 0; color: var(--marigold); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; font-size: 1.1rem;">Actualizar Paquete</h3>
            <form action="RastrearPedido.php" method="POST" class="admin-form">
              <input type="hidden" name="accion" value="editar">
              <label>N. de Orden (A modificar)</label>
              <input type="text" name="numero_orden" required>
              
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0 12px;">
                <div>
                  <label>Nuevo Peso</label>
                  <input type="number" step="0.01" name="peso" required>
                </div>
                <div>
                  <label>Nuevo Estado</label>
                  <select name="estado" required>
                    <option value="orden nueva">Orden Nueva</option>
                    <option value="surtiendose">Surtiéndose</option>
                    <option value="empacandose">Empacándose</option>
                    <option value="en ruta">En Ruta</option>
                    <option value="entregada">Entregada</option>
                  </select>
                </div>
              </div>
              <button type="submit" class="btn-admin" style="background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.3);">Actualizar Datos</button>
            </form>
          </div>

          <!-- ELIMINAR -->
          <div class="admin-box" style="border-color: rgba(229, 62, 62, 0.4);">
            <h3 style="margin-top: 0; color: var(--danger); border-bottom: 1px solid rgba(229, 62, 62, 0.2); padding-bottom: 10px; font-size: 1.1rem;">Eliminar Paquete</h3>
            <form action="RastrearPedido.php" method="POST" class="admin-form" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este paquete de forma permanente?');">
              <input type="hidden" name="accion" value="eliminar">
              <label>N. de Orden (A eliminar)</label>
              <input type="text" name="numero_orden" required style="border-color: rgba(229, 62, 62, 0.5);">
              <button type="submit" class="btn-admin" style="background: var(--danger); color: #fff;">Eliminar Definitivamente</button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </section>
  <?php endif; ?>

</main>

<script>
  const ESTADOS_DB = ["orden nueva", "surtiendose", "empacandose", "en ruta", "entregada"];

  function switchRole(role) {
    const clientSec = document.getElementById('section-client');
    const adminSec = document.getElementById('section-admin');
    
    if (clientSec && adminSec) {
      clientSec.style.display = role === 'admin' ? 'none' : 'block';
      adminSec.style.display = role === 'admin' ? 'block' : 'none';
    }
  }

  function actualizarLineaDeTiempo(statusActual) {
    const estadoIndex = ESTADOS_DB.findIndex(e => e === statusActual) + 1;
    const progressBar = document.getElementById('timeline-progress');

    // Calcula el porcentaje exacto entre el centro del nodo 1 y el centro del nodo activo
    if (estadoIndex > 1) {
      const porcentaje = ((estadoIndex - 1) / (ESTADOS_DB.length - 1)) * 80;
      progressBar.style.width = `${porcentaje}%`;
    } else {
      progressBar.style.width = '0%';
    }

    for (let i = 1; i <= 5; i++) {
      const nodeEl = document.getElementById(`step-node-${i}`);
      const circle = nodeEl.querySelector('.icon-circle');
      const title = nodeEl.querySelector('.step-title');

      if (i <= estadoIndex) {
        circle.style.borderColor = 'var(--marigold)';
        circle.style.background = 'var(--marigold)';
        circle.style.color = '#111';
        circle.style.fontWeight = '700';
        title.style.color = '#F4F6F4';
        title.style.fontWeight = '700';
      } else {
        circle.style.borderColor = 'rgba(255,255,255,0.2)';
        circle.style.background = 'transparent';
        circle.style.color = 'rgba(255,255,255,0.4)';
        title.style.color = 'rgba(255,255,255,0.4)';
        title.style.fontWeight = '500';
      }
    }
  }

  <?php if($estado_actual != ""): ?>
    document.getElementById('tracking-result').style.display = 'block';
    document.getElementById('status-text').textContent = "<?php echo strtoupper($estado_actual); ?>";
    actualizarLineaDeTiempo("<?php echo $estado_actual; ?>");
  <?php endif; ?>

  <?php if($es_admin &&$_SERVER["REQUEST_METHOD"] == "POST"): ?>
    switchRole('admin');
  <?php endif; ?>
</script>

</body>
</html>