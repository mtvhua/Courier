<?php
session_start();
include("conectar.php");

// Restringir acceso solo a usuarios con sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: ingresar.php");
    exit();
}

$mensaje = "";
$error = "";

// Cargar las ciudades destino desde PostgreSQL
$ciudades = [];
if (isset($conn) && $conn) {
    $res_ciudades = pg_query($conn, "SELECT codigo_postal, nombre FROM Ciudad ORDER BY nombre ASC");
    if ($res_ciudades) {
        $ciudades = pg_fetch_all($res_ciudades) ?: [];
    }
}

// Procesar la creación del paquete
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo_destino = trim($_POST['codigo_destino']);
    $tamano = trim($_POST['tamano']);
    $peso = trim($_POST['peso']);
    $numero_orden = trim($_POST['numero_orden']);

    if (empty($codigo_destino) || empty($tamano) || empty($peso)) {
        $error = "Por favor, completa todos los campos obligatorios.";
    } else {
        // 1. Obtener la Ruta correspondiente al destino (Origen fijo 01001)
        $res_ruta = pg_query_params($conn, "SELECT id_ruta FROM Ruta WHERE codigo_origen = '01001' AND codigo_destino = $1", [$codigo_destino]);
        $ruta_data = pg_fetch_assoc($res_ruta);

        // 2. Obtener el id_usuario del usuario en sesión
        $nombre_usuario = $_SESSION['usuario'];
        $res_user = pg_query_params($conn, "SELECT id_usuario FROM Usuario WHERE nombre = $1", [$nombre_usuario]);
        $user_data = pg_fetch_assoc($res_user);

        if (!$ruta_data) {
            $error = "La ruta hacia el destino seleccionado no está configurada.";
        } else if (!$user_data) {
            $error = "No se encontró la cuenta de usuario activa en la base de datos.";
        } else {
            $id_ruta = $ruta_data['id_ruta'];
            $id_usuario = $user_data['id_usuario'];

            // Generar un tracking_id único (ej. PKG20260920123)
            $tracking_id = 'PKG' . date('Ymd') . substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
            $estado = 'orden nueva';

            // Insertar en la tabla Paquete
            $query = "INSERT INTO Paquete (tracking_id, numero_orden, estado, peso, tamano, id_usuario, id_ruta) 
                      VALUES ($1, $2, $3, $4, $5, $6, $7)";

            $result = pg_query_params($conn, $query, [
                $tracking_id,
                $numero_orden ?: null,
                $estado,
                $peso,
                $tamano,
                $id_usuario,
                $id_ruta
            ]);

            if ($result) {
                $mensaje = "¡Envío registrado con éxito! Tu número de rastreo (Tracking ID) es: <strong>$tracking_id</strong>";
            } else {
                $error = "Error al registrar el paquete: " . pg_last_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear Nuevo Envío - Envíos Expresso</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            espresso: '#3A2819',
            'espresso-hover': '#2C1E12',
            gold: '#CFA737',
            'gold-hover': '#B8932C',
            cream: '#EFF2F0',
          }
        }
      }
    }
  </script>
</head>
<body class="bg-cream font-sans">

<header class="bg-espresso text-white px-8 py-4 shadow-md">
  <div class="max-w-7xl mx-auto flex justify-between items-center">
    <div class="flex items-center gap-3">
      <img src="Logo.png" alt="Logo" class="h-10 w-auto bg-white p-1 rounded">
      <div>
        <div class="text-2xl font-bold uppercase leading-none">Envíos Expresso</div>
        <div class="text-xs text-gray-300">Como un shot de café</div>
      </div>
    </div>
    <nav class="flex items-center gap-4 text-sm font-medium">
      <a href="index.php" class="text-gray-200 hover:text-gold transition-colors">Inicio</a>
      <a href="RastrearPedido.php" class="bg-gold hover:bg-gold-hover text-espresso font-semibold px-4 py-1.5 rounded transition-colors">Rastrear pedido</a>
      <a href="cerrarSesion.php" class="border border-white/40 px-3 py-1.5 rounded hover:border-white transition-colors">Cerrar Sesión</a>
    </nav>
  </div>
</header>

<main class="max-w-3xl mx-auto my-10 p-8 bg-white rounded-xl shadow-lg">
  <h1 class="text-3xl font-bold text-espresso mb-2">Crear Nuevo Envío</h1>
  <p class="text-gray-600 mb-6">Ingresa los detalles del paquete para registrar la orden de transporte.</p>

  <?php if ($mensaje): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
      <?php echo $mensaje; ?>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
      <?php echo $error; ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="enviar.php" class="space-y-5">
    
    <!-- Selección de Destino -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad Destino *</label>
      <select name="codigo_destino" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-gold focus:outline-none">
        <option value="">Selecciona una ciudad destino</option>
        <?php foreach ($ciudades as $c): ?>
          <option value="<?php echo htmlspecialchars($c['codigo_postal']); ?>">
            <?php echo htmlspecialchars($c['nombre']); ?> (<?php echo htmlspecialchars($c['codigo_postal']); ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Tamaño y Peso -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tamaño del Paquete *</label>
        <select name="tamano" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-gold focus:outline-none">
          <option value="Pequeño">Pequeño</option>
          <option value="Mediano">Mediano</option>
          <option value="Grande">Grande</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Peso en Libras (lbs) *</label>
        <input type="number" step="0.01" name="peso" required placeholder="Ej. 2.50" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-gold focus:outline-none">
      </div>
    </div>

    <!-- Número de Orden -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Número de Orden de Tienda (Opcional)</label>
      <input type="text" name="numero_orden" placeholder="Ej. ORD-99823" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-gold focus:outline-none">
    </div>

    <div class="pt-4 flex justify-between items-center">
      <a href="index.php" class="text-gray-500 hover:underline text-sm">Volver al inicio</a>
      <button type="submit" class="bg-espresso hover:bg-espresso-hover text-white font-semibold px-6 py-2.5 rounded-lg transition-colors">
        Registrar Envío
      </button>
    </div>
  </form>
</main>

</body>
</html>