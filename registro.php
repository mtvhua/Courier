<?php
$mensaje = "";
$tipo_mensaje = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include("conectar.php");

    $nombre = pg_escape_string($conn, $_POST['nombre']);
    $contrasena_raw = $_POST['contrasena'];

   
    $check_sql = "SELECT * FROM Usuario WHERE nombre = '$nombre'";
    $check_resultado = pg_query($conn, $check_sql);

    if ($check_resultado && pg_num_rows($check_resultado) > 0) {
        $mensaje = "Ese nombre ya está registrado. Elige otro.";
        $tipo_mensaje = "error";
    } else {
      
        $contrasena_hash = password_hash($contrasena_raw, PASSWORD_BCRYPT);

       
        $insert_sql = "INSERT INTO Usuario (nombre, contrasena) VALUES ('$nombre', '$contrasena_hash')";
        $insert_resultado = pg_query($conn, $insert_sql);

        if ($insert_resultado) {
            $mensaje = "¡Registro exitoso! Ya puedes iniciar sesión.";
            $tipo_mensaje = "exito";
        } else {
            $mensaje = "Hubo un error al registrar. Inténtalo de nuevo.";
            $tipo_mensaje = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro - Envíos Expresso</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;800&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
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
          },
          fontFamily: {
            display: ['"Big Shoulders Display"', 'sans-serif'],
            sans: ['"IBM Plex Sans"', 'sans-serif'],
          }
        }
      }
    }
  </script>
</head>

<body class="bg-cream min-h-screen flex flex-col font-sans text-gray-800">

  <!-- Encabezado -->
  <header class="bg-espresso text-white px-8 py-4 shadow-md">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
      <div class="flex items-center gap-3">
        <img src="Logo.png" alt="Logo Envíos Expresso" class="h-10 w-auto bg-white p-1 rounded">
        <div>
          <div class="font-display text-2xl font-bold tracking-wide uppercase leading-none">Envíos Expresso</div>
          <div class="text-xs text-gray-300">Como un shot de cafe</div>
        </div>
      </div>
      <nav class="flex items-center gap-4 text-sm font-medium">
        <a href="index.php" class="hover:underline">Modo Invitado</a>
        <a href="ingresar.php" class="border border-white/40 px-3 py-1.5 rounded hover:border-white transition-colors">Iniciar Sesión</a>
        <a href="registro.php" class="border border-white/40 px-3 py-1.5 rounded hover:border-white transition-colors">Registrarse</a>
        <a href="RastrearPedido.php" class="bg-gold hover:bg-gold-hover text-espresso font-semibold px-4 py-1.5 rounded transition-colors">Rastrear pedido</a>
      </nav>
    </div>
  </header>

  <!-- Caja de Registro -->
  <main class="flex-grow flex items-center justify-center p-6">
    <div class="w-full max-w-md bg-white p-8 rounded-xl shadow-xl border border-gray-200">
      
      <h1 class="font-display text-4xl font-extrabold text-espresso text-center uppercase tracking-wide mb-2">
        Crear Cuenta
      </h1>
      <p class="text-center text-gray-500 text-sm mb-6">Regístrate para comenzar a realizar tus envíos</p>

      <?php if($mensaje != ""): ?>
        <div class="p-3 mb-5 rounded-lg text-sm text-center font-semibold <?php echo ($tipo_mensaje == 'error') ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-green-100 text-green-700 border border-green-200'; ?>">
          <?php echo $mensaje; ?>
        </div>
      <?php endif; ?>

      <form action="" method="POST" class="space-y-4">
        <div>
          <label for="nombre" class="block text-xs font-semibold uppercase tracking-wider text-espresso mb-1">Nombre / Usuario</label>
          <input 
            type="text" 
            id="nombre" 
            name="nombre" 
            required 
            placeholder="Ej. juanperez"
            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent transition-all">
        </div>

        <div>
          <label for="contrasena" class="block text-xs font-semibold uppercase tracking-wider text-espresso mb-1">Contraseña</label>
          <input 
            type="password" 
            id="contrasena" 
            name="contrasena" 
            required 
            placeholder="••••••••"
            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent transition-all">
        </div>

        <button 
          type="submit" 
          class="w-full bg-gold hover:bg-gold-hover text-espresso font-extrabold uppercase py-3 rounded-lg shadow transition-colors mt-2">
          Registrarme
        </button>
      </form>

      <div class="mt-6 pt-6 border-t border-gray-100 text-center text-sm text-gray-600">
        ¿Ya tienes una cuenta? 
        <a href="ingresar.php" class="text-espresso font-bold hover:underline ml-1">Iniciar Sesión</a>
      </div>

    </div>
  </main>

</body>
</html>