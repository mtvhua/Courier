<?php
session_start();

$mensaje_error = "";

// Verifica si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    include("conectar.php");

    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    // admin
    
    if($usuario == "PerrY" && $contrasena == "PerrY"){
        $_SESSION['administrador'] = true;
       
        header("Location: index.php"); 
        exit();
    }

    $sql = "SELECT *
            FROM Usuarios
            WHERE usuario='$usuario' AND contrasena='$contrasena'";

    $resultado = pg_query($conn, $sql);
    
    $fila = pg_fetch_assoc($resultado);

    if($fila){
        $_SESSION['usuario'] = $usuario;
        header("Location: index.php"); 
        exit();
    } else {
        $mensaje_error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Envíos Expresso</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <div class="header-row">
    <div class="brand">
      <img src="Logo.png" alt="Logo Envíos Expresso" class="logo-img">
      <div>
        <div class="brand-name">Envíos Expresso</div>
        <div class="brand-tag">Como un shot de cafe</div>
      </div>
    </div>
    <nav aria-label="Cuenta y seguimiento">
      <a href="ingresar.html">Ingresar</a>
      <a href="#crear-cuenta">Crear cuenta</a>
      <a href="RastrearPedido.html" class="primary">Rastrear pedido</a>
    </nav>
  </div>
</header>

<main>
    <div class="flex flex-col justify-center items-center min-h-screen bg-gradient-to-b from-slate-900 to-slate-800 text-white">
        <h1 class="text-4xl font-bold mb-6 drop-shadow-md">
            Iniciar Sesión
        </h1>

        <div class="flex flex-col justify-center items-center gap-4 bg-slate-800 p-8 rounded-xl shadow-2xl border border-slate-600">

            <?php if($mensaje_error != ""): ?>
                <div class="bg-red-500 text-white px-4 py-2 rounded-lg w-full text-center mb-2 font-semibold">
                    <?php echo $mensaje_error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="flex flex-col gap-4 w-64">

                <input 
                    type="text" 
                    name="usuario" 
                    placeholder="Usuario"
                    required
                    class="p-2 rounded-lg text-black focus:outline-none focus:ring-2 focus:ring-blue-500">

                <input 
                    type="password" 
                    name="contrasena" 
                    placeholder="Contraseña"
                    required
                    class="p-2 rounded-lg text-black focus:outline-none focus:ring-2 focus:ring-blue-500">

                <input 
                    type="submit" 
                    value="Entrar"
                    class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded-lg cursor-pointer transition-colors mt-2">

            </form>


        <a href="index.html" class="mt-8 bg-slate-600 hover:bg-slate-500 px-6 py-2 rounded-lg font-bold shadow-md transition-colors">
            Volver al Menú
        </a>
    </div>

</main>


</body>
</html>