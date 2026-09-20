<?php
session_start();
include("conectar.php");


if (isset($conn)) {
    pg_set_client_encoding($conn, "utf8");
}


$es_admin = isset($_SESSION['administrador']) && $_SESSION['administrador'] === true;
$usuario_actual = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menú Principal - Envíos Expresso</title>
  

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  

  <link rel="stylesheet" href="styles.css">

  
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
<body class="bg-cream font-sans">


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
      
      <?php if ($es_admin): ?>

        <span class="text-gold font-bold uppercase tracking-wider flex items-center gap-2">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
          Modo Administrador
        </span>
        <a href="cerrarSesion.php" class="border border-red-500 text-red-400 hover:bg-red-500 hover:text-white px-3 py-1.5 rounded transition-colors">Cerrar Sesión</a>
      
      <?php elseif ($usuario_actual): ?>

        <span class="text-gray-200 font-semibold tracking-wide flex items-center gap-2">
          <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
          <?php echo htmlspecialchars($usuario_actual); ?>
        </span>
        <a href="cerrarSesion.php" class="border border-white/40 px-3 py-1.5 rounded hover:border-white transition-colors">Cerrar Sesión</a>
      
      <?php else: ?>

        <span class="text-gray-400 mr-2">Modo Invitado</span>
        <a href="ingresar.php" class="border border-white/40 px-3 py-1.5 rounded hover:border-white transition-colors">Iniciar Sesión</a>
        <a href="registro.php" class="border border-white/40 px-3 py-1.5 rounded hover:border-white transition-colors">Registrarse</a>
      <?php endif; ?>


      <a href="RastrearPedido.php" class="bg-gold hover:bg-gold-hover text-espresso font-semibold px-4 py-1.5 rounded transition-colors ml-2">Rastrear pedido</a>
    </nav>
    
  </div>
</header>

<main>

  <section class="hero wrap">
    <div class="hero-content">
      <div class="hero-text">
        <h1>De Guatemala capital a los 22 departamentos, con tarifa fija.</h1>
        <p>Enviamos tus paquetes desde la Ciudad de Guatemala hacia cualquier cabecera departamental del país, con un costo fijo según destino y seguimiento del pedido en cada etapa del envío.</p>
      </div>
      <div class="hero-image">
        <img src="Logo.png" alt="Logo Envíos Expresso" class="hero-logo-img">
      </div>
    </div>

    <div class="hero-meta">
      <div>
        <div class="num" id="meta-destinos">—</div>
        <div class="lbl">destinos cubiertos</div>
      </div>
      <div>
        <div class="num">01001</div>
        <div class="lbl">origen fijo (Guatemala)</div>
      </div>
      <div>
        <div class="num">5</div>
        <div class="lbl">estados de seguimiento</div>
      </div>
    </div>
  </section>

  <section class="board-section wrap" id="tarifas">
    <div class="board-head">
      <h2>Tarifas por destino</h2>
      <span>precio en quetzales (Q)</span>
    </div>
    <div class="board">
      <table>
        <thead>
          <tr>
            <th>Destino</th>
            <th>Código postal</th>
            <th class="num-col">Precio</th>
          </tr>
        </thead>
        <tbody id="tarifas-body">
          <tr class="loading-row"><td colspan="3">Cargando tarifas…</td></tr>
        </tbody>
      </table>
      <div class="board-foot">Origen: Ciudad de Guatemala (01001) — tarifas sujetas a cambio.</div>
    </div>
  </section>
</main>

<script>
  const tarifasJSON = `[
    {"destino":"El Progreso","codigo_postal":"02001","precio":20.00},
    {"destino":"Sacatepéquez","codigo_postal":"03001","precio":15.00},
    {"destino":"Chimaltenango","codigo_postal":"04001","precio":20.00},
    {"destino":"Escuintla","codigo_postal":"05001","precio":20.00},
    {"destino":"Santa Rosa","codigo_postal":"06001","precio":25.00},
    {"destino":"Sololá","codigo_postal":"07001","precio":30.00},
    {"destino":"Totonicapán","codigo_postal":"08001","precio":35.00},
    {"destino":"Quetzaltenango","codigo_postal":"09001","precio":35.00},
    {"destino":"Suchitepéquez","codigo_postal":"10001","precio":30.00},
    {"destino":"Retalhuleu","codigo_postal":"11001","precio":35.00},
    {"destino":"San Marcos","codigo_postal":"12001","precio":45.00},
    {"destino":"Huehuetenango","codigo_postal":"13001","precio":45.00},
    {"destino":"Quiché","codigo_postal":"14001","precio":40.00},
    {"destino":"Baja Verapaz","codigo_postal":"15001","precio":30.00},
    {"destino":"Alta Verapaz","codigo_postal":"16001","precio":40.00},
    {"destino":"Petén","codigo_postal":"17001","precio":65.00},
    {"destino":"Izabal","codigo_postal":"18001","precio":50.00},
    {"destino":"Zacapa","codigo_postal":"19001","precio":35.00},
    {"destino":"Chiquimula","codigo_postal":"20001","precio":35.00},
    {"destino":"Jalapa","codigo_postal":"21001","precio":25.00},
    {"destino":"Jutiapa","codigo_postal":"22001","precio":30.00}
  ]`;

  function money(n) {
    return 'Q' + Number(n).toFixed(2);
  }

  function renderTarifas(rutas) {
    const body = document.getElementById('tarifas-body');
    body.innerHTML = '';
    rutas.forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${r.destino}</td>
        <td class="code-col">${r.codigo_postal}</td>
        <td class="num-col">${money(r.precio)}</td>
      `;
      body.appendChild(tr);
    });
    document.getElementById('meta-destinos').textContent = rutas.length;
  }

  try {
    const rutas = JSON.parse(tarifasJSON);
    renderTarifas(rutas);
  } catch (e) {
    document.getElementById('tarifas-body').innerHTML =
      '<tr class="loading-row"><td colspan="3">No se pudieron cargar las tarifas.</td></tr>';
  }
</script>

</body>
</html>