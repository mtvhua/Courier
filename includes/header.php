<?php
require_once __DIR__ . '/../lib/auth.php';

$titulo  = $titulo  ?? 'Envíos Expresso';
$seccion = $seccion ?? '';
$yo      = usuario_actual();

/** Clases de un link del menú según si está activo. */
function nav_clase(string $id, string $seccion): string
{
    return $id === $seccion
        ? 'text-gold border-b-2 border-gold'
        : 'text-gray-200 hover:text-gold border-b-2 border-transparent';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo) ?> · Envíos Expresso</title>
  <link rel="icon" href="<?= url('assets/Logo.png') ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

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
            mono: ['"IBM Plex Mono"', 'monospace'],
          }
        }
      }
    }
  </script>
  <!-- Componentes reutilizables -->
  <style type="text/tailwindcss">
    @layer components {
      .card      { @apply bg-white rounded-xl shadow-sm border border-gray-200; }
      .titulo    { @apply font-display text-4xl font-extrabold uppercase tracking-wide text-espresso; }
      .label     { @apply block text-xs font-semibold uppercase tracking-wider text-espresso mb-1; }
      .input     { @apply w-full px-3 py-2.5 rounded-lg border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-gold focus:border-transparent; }
      .btn       { @apply inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg font-semibold text-sm transition-colors cursor-pointer; }
      .btn-gold  { @apply btn bg-gold hover:bg-gold-hover text-espresso; }
      .btn-dark  { @apply btn bg-espresso hover:bg-espresso-hover text-white; }
      .btn-line  { @apply btn border border-gray-300 text-espresso hover:bg-gray-100; }
      .btn-red   { @apply btn border border-red-300 text-red-700 hover:bg-red-600 hover:text-white hover:border-red-600; }
      .tabla th  { @apply text-left text-xs font-semibold uppercase tracking-wider text-gray-500 px-4 py-3 bg-gray-50 border-b; }
      .tabla td  { @apply px-4 py-3 border-b border-gray-100 align-middle; }
      .chip      { @apply inline-block text-xs font-semibold px-2.5 py-1 rounded-full; }
    }
  </style>
</head>
<body class="bg-cream font-sans text-gray-800 min-h-screen flex flex-col">

<header class="bg-espresso text-white shadow-md">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap gap-4 justify-between items-center">

    <a href="<?= url('index.php') ?>" class="flex items-center gap-3">
      <img src="<?= url('assets/Logo.png') ?>" alt="Logo Envíos Expresso" class="h-10 w-auto bg-white p-1 rounded">
      <div>
        <div class="font-display text-2xl font-bold tracking-wide uppercase leading-none">Envíos Expresso</div>
        <!-- <div class="text-xs text-gray-300">Como un shot de café</div> -->
      </div>
    </a>

    <nav class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-medium">
      <a href="<?= url('index.php') ?>#tarifas" class="py-1 <?= nav_clase('inicio', $seccion) ?>">Tarifas</a>

      <?php if ($yo && !$yo['es_admin']): ?>
        <a href="<?= url('enviar.php') ?>"     class="py-1 <?= nav_clase('enviar', $seccion) ?>">Nuevo envío</a>
        <a href="<?= url('mis_envios.php') ?>" class="py-1 <?= nav_clase('mis_envios', $seccion) ?>">Mis envíos</a>
      <?php endif; ?>

      <?php if ($yo && $yo['es_admin']): ?>
        <a href="<?= url('admin/envios.php') ?>"   class="py-1 <?= nav_clase('admin_envios', $seccion) ?>">Envíos</a>
        <a href="<?= url('admin/destinos.php') ?>" class="py-1 <?= nav_clase('admin_destinos', $seccion) ?>">Destinos</a>
        <a href="<?= url('admin/tiendas.php') ?>"  class="py-1 <?= nav_clase('admin_tiendas', $seccion) ?>">Tiendas</a>
      <?php endif; ?>

      <a href="<?= url('rastrear.php') ?>" class="bg-gold hover:bg-gold-hover text-espresso font-semibold px-4 py-1.5 rounded transition-colors">Rastrear pedido</a>

      <?php if ($yo): ?>
        <span class="flex items-center gap-2 text-gray-300 border-l border-white/20 pl-4">
          <?php if ($yo['es_admin']): ?>
            <span class="chip bg-gold/20 text-gold">ADMIN</span>
          <?php endif; ?>
          <?= e($yo['nombre']) ?>
        </span>
        <a href="<?= url('logout.php') ?>" class="border border-white/40 px-3 py-1.5 rounded hover:border-white transition-colors">Salir</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>"    class="border border-white/40 px-3 py-1.5 rounded hover:border-white transition-colors">Iniciar sesión</a>
        <a href="<?= url('registro.php') ?>" class="py-1 text-gray-200 hover:text-gold">Registrarse</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="flex-grow w-full">
<?php foreach (flash_sacar() as $f): ?>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 mt-6">
    <div class="px-4 py-3 rounded-lg border text-sm font-medium <?= $f['tipo'] === 'error'
        ? 'bg-red-50 border-red-200 text-red-700'
        : 'bg-green-50 border-green-200 text-green-800' ?>">
      <?= $f['mensaje'] /* los mensajes los arma el servidor; los datos del usuario ya vienen escapados */ ?>
    </div>
  </div>
<?php endforeach; ?>
