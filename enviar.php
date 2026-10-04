<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/paquetes.php';
requerir_login();

const TAMANOS = ['Sobre', 'Pequeño', 'Mediano', 'Grande'];

$error = '';
$d = ['destinatario' => '', 'direccion' => '', 'destino' => '', 'peso' => '', 'tamano' => 'Pequeño', 'numero_orden' => ''];

if (es_post()) {
    foreach ($d as $campo => $_) {
        $d[$campo] = post($campo);
    }

    if ($d['destinatario'] === '' || $d['direccion'] === '' || $d['destino'] === '') {
        $error = 'Completa destinatario, dirección y destino.';
    } elseif (!is_numeric($d['peso']) || $d['peso'] <= 0 || $d['peso'] > 50) {
        $error = 'El peso debe ser un número entre 0.01 y 50 lb.';
    } elseif (!in_array($d['tamano'], TAMANOS, true)) {
        $error = 'Tamaño inválido.';
    } else {
        try {
            $p = crear_paquete($d + ['id_usuario' => usuario_actual()['id']]);
            flash('ok', 'Envío registrado. Tu número de rastreo es <strong class="font-mono">' . e($p['tracking_id']) . '</strong>');
            redirigir('mis_envios.php');
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (DbError $e) {
            $error = db_mensaje_error($e);
        }
    }
}

$destinos = tarifas();

$titulo  = 'Nuevo envío';
$seccion = 'enviar';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
  <h1 class="titulo">Nuevo envío</h1>
  <p class="text-gray-600 mb-6">Origen: Ciudad de Guatemala (<?= e(CODIGO_ORIGEN) ?>). Llena los datos del paquete.</p>

  <?php if ($error): ?>
    <div class="px-4 py-3 mb-6 rounded-lg text-sm font-medium bg-red-50 text-red-700 border border-red-200"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="POST" class="card p-6 sm:p-8 space-y-5">
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="label" for="destinatario">Destinatario *</label>
        <input id="destinatario" name="destinatario" required maxlength="150" value="<?= e($d['destinatario']) ?>" class="input" placeholder="Nombre de quien recibe">
      </div>
      <div>
        <label class="label" for="destino">Destino *</label>
        <select id="destino" name="destino" required class="input">
          <option value="">Selecciona un departamento</option>
          <?php foreach ($destinos as $t): ?>
            <option value="<?= e($t['codigo_postal']) ?>" <?= $d['destino'] === $t['codigo_postal'] ? 'selected' : '' ?>>
              <?= e($t['destino']) ?> (<?= e($t['codigo_postal']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div>
      <label class="label" for="direccion">Dirección de entrega *</label>
      <input id="direccion" name="direccion" required maxlength="255" value="<?= e($d['direccion']) ?>" class="input" placeholder="Calle, zona, referencia">
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
      <div>
        <label class="label" for="tamano">Tamaño *</label>
        <select id="tamano" name="tamano" class="input">
          <?php foreach (TAMANOS as $t): ?>
            <option <?= $d['tamano'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="label" for="peso">Peso (lb) *</label>
        <input id="peso" name="peso" type="number" step="0.01" min="0.01" max="50" required value="<?= e($d['peso']) ?>" class="input" placeholder="Ej. 2.50">
      </div>
      <div>
        <label class="label" for="numero_orden">N.º de orden (opcional)</label>
        <input id="numero_orden" name="numero_orden" maxlength="50" value="<?= e($d['numero_orden']) ?>" class="input" placeholder="Ej. ORD-99823">
      </div>
    </div>

    <div id="cotizacion" class="hidden rounded-lg bg-cream border border-gold/40 px-4 py-3 flex justify-between items-center">
      <span class="text-sm text-gray-600">Costo de envío hacia <strong id="cot-destino"></strong></span>
      <span id="cot-costo" class="font-display text-3xl font-extrabold text-espresso"></span>
    </div>

    <div class="pt-2 flex justify-between items-center">
      <a href="<?= url('mis_envios.php') ?>" class="text-sm text-gray-500 hover:underline">Ver mis envíos</a>
      <button class="btn-dark px-6 py-2.5">Registrar envío</button>
    </div>
  </form>
</section>

<script>
  const selDestino = document.getElementById('destino');

  function cotizar() {
    const codigo = selDestino.value;
    const caja = document.getElementById('cotizacion');
    if (!codigo) { caja.classList.add('hidden'); return; }

    fetch('<?= url('api/consulta.php') ?>?destino=' + encodeURIComponent(codigo) + '&formato=json')
      .then(res => res.text())
      .then(texto => {
        const r = JSON.parse(texto).consultaprecio;
        document.getElementById('cot-destino').textContent = selDestino.options[selDestino.selectedIndex].text;
        document.getElementById('cot-costo').textContent =
          r.cobertura === 'TRUE' ? 'Q' + Number(r.costo).toFixed(2) : 'Sin cobertura';
        caja.classList.remove('hidden');
      });
  }

  selDestino.addEventListener('change', cotizar);
  cotizar();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
