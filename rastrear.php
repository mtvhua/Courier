<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/paquetes.php';
require_once __DIR__ . '/lib/formato.php';

$titulo  = 'Rastrear pedido';
$seccion = 'rastrear';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-4xl mx-auto px-4 sm:px-6 py-10">
  <h1 class="titulo">Rastrear pedido</h1>
  <p class="text-gray-600 mb-6">Ingresa el número de rastreo que te dimos al registrar el envío.</p>

  <form id="form-rastreo" class="card p-5 flex flex-col sm:flex-row gap-3">
    <input id="tracking" name="tracking" required value="<?= e($_GET['tracking'] ?? '') ?>"
           placeholder="Ej. EXP20261003K7Q2M" class="input font-mono uppercase flex-1">
    <button class="btn-dark px-8">Consultar</button>
  </form>

  <div id="mensaje" class="hidden mt-6 px-4 py-3 rounded-lg text-sm font-medium bg-red-50 text-red-700 border border-red-200"></div>

  <!-- Resultado (lo llena JavaScript con la respuesta JSON) -->
  <div id="resultado" class="hidden mt-6 card p-6 sm:p-8">
    <div class="flex flex-wrap justify-between gap-4 border-b border-gray-100 pb-5">
      <div>
        <div class="text-xs uppercase tracking-wider text-gray-500">Estado actual</div>
        <div id="r-status" class="font-display text-4xl font-extrabold uppercase text-espresso"></div>
      </div>
      <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
        <dt class="text-gray-500">Tracking</dt>     <dd id="r-tracking" class="font-mono font-medium"></dd>
        <dt class="text-gray-500">Destinatario</dt> <dd id="r-destinatario"></dd>
        <dt class="text-gray-500">Destino</dt>      <dd id="r-destino"></dd>
      </dl>
    </div>

    <ol id="linea" class="relative grid grid-cols-5 mt-8"></ol>
  </div>
</section>

<script>
  // Los 5 estados vienen de PHP para no escribirlos dos veces
  const ESTADOS = <?= a_json(ESTADOS) ?>;

  const $ = id => document.getElementById(id);

  function pintarLinea(paso) {
    const claves = Object.keys(ESTADOS);
    $('linea').innerHTML = claves.map((clave, i) => {
      const n = i + 1;
      const hecho = n <= paso;
      const barra = n < claves.length
        ? `<div class="absolute top-5 left-1/2 w-full h-0.5 ${n < paso ? 'bg-gold' : 'bg-gray-200'}"></div>` : '';
      return `
        <li class="relative flex flex-col items-center text-center">
          ${barra}
          <div class="relative z-10 w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm
                      ${hecho ? 'bg-gold text-espresso' : 'bg-white border-2 border-gray-200 text-gray-400'}">${n}</div>
          <div class="mt-2 px-0.5 text-[10px] leading-tight sm:text-sm font-semibold break-words ${hecho ? 'text-espresso' : 'text-gray-400'}">${ESTADOS[clave]}</div>
        </li>`;
    }).join('');
  }

  function consultar(tracking) {
    $('mensaje').classList.add('hidden');
    $('resultado').classList.add('hidden');

    fetch('<?= url('api/rastreo.php') ?>?formato=json&tracking=' + encodeURIComponent(tracking))
      .then(res => res.text())
      .then(texto => {
        const r = JSON.parse(texto).rastreo;
        if (!r.encontrado) {
          $('mensaje').textContent = 'No encontramos ningún envío con el número ' + tracking + '.';
          $('mensaje').classList.remove('hidden');
          return;
        }
        $('r-status').textContent       = ESTADOS[r.status];
        $('r-tracking').textContent     = r.tracking;
        $('r-destinatario').textContent = r.destinatario;
        $('r-destino').textContent      = r.destino + ' (' + r.codigo + ')';
        pintarLinea(r.paso);
        $('resultado').classList.remove('hidden');
        history.replaceState(null, '', '?tracking=' + encodeURIComponent(r.tracking));
      })
      .catch(() => {
        $('mensaje').textContent = 'No se pudo consultar el envío. Intenta de nuevo.';
        $('mensaje').classList.remove('hidden');
      });
  }

  $('form-rastreo').addEventListener('submit', ev => {
    ev.preventDefault();
    const t = $('tracking').value.trim().toUpperCase();
    if (t) consultar(t);
  });

  if ($('tracking').value.trim()) consultar($('tracking').value.trim().toUpperCase());
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
