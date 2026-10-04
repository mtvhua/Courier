<?php
require_once __DIR__ . '/lib/paquetes.php';
$titulo  = 'Inicio';
$seccion = 'inicio';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-6xl mx-auto px-4 sm:px-6 pt-12 pb-8">
  <div class="grid md:grid-cols-[1fr_auto] gap-10 items-center">
    <div>
      <h1 class="font-display text-5xl sm:text-6xl font-extrabold uppercase leading-[0.95] text-espresso">
        Tan rápidos como un shot de cafe.
      </h1>
      <p class="mt-5 text-lg text-gray-600 max-w-2xl">
        Enviamos tus paquetes desde la Ciudad de Guatemala hacia cualquier cabecera departamental,
        con un costo fijo según destino y seguimiento del pedido en cada etapa.
      </p>
      <div class="mt-7 flex flex-wrap gap-3">
        <a href="<?= url(usuario_actual() ? 'enviar.php' : 'registro.php') ?>" class="btn-dark px-6 py-3 text-base">Hacer un envío</a>
        <a href="<?= url('rastrear.php') ?>" class="btn-gold px-6 py-3 text-base">Rastrear pedido</a>
      </div>
    </div>
    <img src="<?= url('assets/Logo.png') ?>" alt="Logo Envíos Expresso" class="hidden md:block w-72 bg-white rounded-2xl p-4 shadow-sm">
  </div>

  <div class="mt-10 grid grid-cols-3 gap-4 max-w-2xl">
    <div class="card p-4">
      <div id="meta-destinos" class="font-display text-4xl font-extrabold text-espresso">—</div>
      <div class="text-xs uppercase tracking-wider text-gray-500">destinos cubiertos</div>
    </div>
    <div class="card p-4">
      <div class="font-display text-4xl font-extrabold text-espresso"><?= e(CODIGO_ORIGEN) ?></div>
      <div class="text-xs uppercase tracking-wider text-gray-500">origen fijo</div>
    </div>
    <div class="card p-4">
      <div class="font-display text-4xl font-extrabold text-espresso"><?= count(ESTADOS) ?></div>
      <div class="text-xs uppercase tracking-wider text-gray-500">estados de seguimiento</div>
    </div>
  </div>
</section>

<!-- ===================== TARIFAS ===================== -->
<section id="tarifas" class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
  <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
    <div>
      <h2 class="titulo">Tarifas por destino</h2>
    </div>
    <input id="buscar" type="search" placeholder="Buscar destino o código…" class="input max-w-xs">
  </div>

  <div class="card overflow-x-auto">
    <table class="tabla w-full text-sm">
      <thead>
        <tr><th>Destino</th><th>Código postal</th><th class="!text-right">Precio</th></tr>
      </thead>
      <tbody id="tarifas-body">
        <tr><td colspan="3" class="text-center text-gray-400 py-8">Cargando tarifas…</td></tr>
      </tbody>
    </table>
  </div>
  <p class="text-xs text-gray-500 mt-2">Origen: Ciudad de Guatemala (<?= e(CODIGO_ORIGEN) ?>) — tarifas sujetas a cambio.</p>
</section>

<script>
  let rutas = [];

  function dinero(n) {
    return 'Q' + Number(n).toFixed(2);
  }

  function escapar(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
  }

  function pintar(lista) {
    const body = document.getElementById('tarifas-body');
    if (lista.length === 0) {
      body.innerHTML = '<tr><td colspan="3" class="text-center text-gray-400 py-8">Sin resultados</td></tr>';
      return;
    }
    body.innerHTML = lista.map(r => `
      <tr class="hover:bg-cream/60">
        <td class="font-medium text-espresso">${escapar(r.destino)}</td>
        <td class="font-mono text-gray-500">${escapar(r.codigo_postal)}</td>
        <td class="text-right font-semibold">${dinero(r.precio)}</td>
      </tr>`).join('');
  }

  fetch('<?= url('api/tarifas.php') ?>?formato=json')
    .then(res => res.text())
    .then(texto => {
      const datos = JSON.parse(texto);         
      rutas = datos.tarifas.rutas;
      document.getElementById('meta-destinos').textContent = rutas.length;
      pintar(rutas);
    })
    .catch(() => {
      document.getElementById('tarifas-body').innerHTML =
        '<tr><td colspan="3" class="text-center text-red-600 py-8">No se pudieron cargar las tarifas.</td></tr>';
    });

  document.getElementById('buscar').addEventListener('input', ev => {
    const q = ev.target.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    pintar(rutas.filter(r =>
      (r.destino + ' ' + r.codigo_postal).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').includes(q)
    ));
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
