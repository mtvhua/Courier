<?php
/* =====================================================================
 *  ADMIN · Tiendas virtuales autorizadas a usar el WebService (CRUD)
 * ===================================================================== */
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/paquetes.php';
requerir_admin();

if (es_post()) {
    $accion = post('accion');
    $id     = post('id_tienda');
    $nombre = post('nombre');
    $host   = post('host');

    try {
        if ($accion === 'crear' || $accion === 'actualizar') {
            if ($id === '' || strlen($id) > 15) throw new InvalidArgumentException('El identificador es obligatorio (máximo 15 caracteres).');
            if ($nombre === '' || $host === '') throw new InvalidArgumentException('Nombre y host son obligatorios.');
        }

        if ($accion === 'crear') {
            db_query('INSERT INTO Tienda (id_tienda, nombre, host) VALUES ($1, $2, $3)', [$id, $nombre, $host]);
            flash('ok', 'Tienda <strong>' . e($nombre) . '</strong> registrada.');
        }
        if ($accion === 'actualizar') {
            db_query('UPDATE Tienda SET nombre = $1, host = $2 WHERE id_tienda = $3', [$nombre, $host, $id]);
            flash('ok', 'Tienda actualizada.');
        }
        if ($accion === 'eliminar') {
            db_query('DELETE FROM Tienda WHERE id_tienda = $1', [$id]);
            flash('ok', 'Tienda eliminada.');
        }
    } catch (InvalidArgumentException $e) {
        flash('error', e($e->getMessage()));
    } catch (DbError $e) {
        flash('error', e($e->sqlstate === '23503' ? 'No se puede eliminar: la tienda tiene envíos registrados.' : db_mensaje_error($e)));
    }
    redirigir('admin/tiendas.php');
}

$tiendas = db_todos(
    'SELECT t.*, count(p.id_paquete) AS envios
       FROM Tienda t LEFT JOIN Paquete p ON p.id_tienda = t.id_tienda
      GROUP BY t.id_tienda ORDER BY t.nombre'
);

$editando = null;
foreach ($tiendas as $t) {
    if (($t['id_tienda'] ?? null) === ($_GET['editar'] ?? '')) $editando = $t;
}

$titulo  = 'Admin · Tiendas';
$seccion = 'admin_tiendas';
require __DIR__ . '/../includes/header.php';
?>

<section class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
  <h1 class="titulo">Tiendas</h1>
  <p class="text-gray-600 mb-6">Solo las tiendas registradas aquí pueden pedir envíos por <code class="font-mono">/envio</code>.</p>

  <div class="grid lg:grid-cols-[320px_1fr] gap-6 items-start">
    <form method="POST" class="card p-6 space-y-4">
      <h2 class="font-display text-2xl font-bold uppercase text-espresso"><?= $editando ? 'Editar tienda' : 'Nueva tienda' ?></h2>
      <input type="hidden" name="accion" value="<?= $editando ? 'actualizar' : 'crear' ?>">
      <div>
        <label class="label">Identificador (máx. 15)</label>
        <input name="id_tienda" required maxlength="15" value="<?= e($editando['id_tienda'] ?? '') ?>" <?= $editando ? 'readonly' : '' ?>
               class="input font-mono <?= $editando ? 'bg-gray-100 text-gray-500' : '' ?>" placeholder="TIENDAGRUPO0001">
      </div>
      <div>
        <label class="label">Nombre</label>
        <input name="nombre" required maxlength="100" value="<?= e($editando['nombre'] ?? '') ?>" class="input">
      </div>
      <div>
        <label class="label">Host</label>
        <input name="host" required maxlength="255" value="<?= e($editando['host'] ?? '') ?>" class="input font-mono" placeholder="http://192.168.1.20/tienda">
      </div>
      <div class="flex gap-2">
        <button class="btn-gold flex-1"><?= $editando ? 'Guardar' : 'Registrar' ?></button>
        <?php if ($editando): ?><a href="<?= url('admin/tiendas.php') ?>" class="btn-line">Cancelar</a><?php endif; ?>
      </div>
    </form>

    <div class="card overflow-x-auto">
      <table class="tabla w-full text-sm">
        <thead><tr><th>ID</th><th>Nombre</th><th>Host</th><th class="!text-right">Envíos</th><th></th></tr></thead>
        <tbody>
        <?php if (!$tiendas): ?>
          <tr><td colspan="5" class="text-center text-gray-400 py-10">No hay tiendas registradas.</td></tr>
        <?php endif; ?>
        <?php foreach ($tiendas as $t): ?>
          <tr>
            <td class="font-mono text-espresso"><?= e($t['id_tienda']) ?></td>
            <td class="font-medium"><?= e($t['nombre']) ?></td>
            <td class="font-mono text-xs text-gray-500"><?= e($t['host']) ?></td>
            <td class="text-right text-gray-500"><?= (int) $t['envios'] ?></td>
            <td class="text-right whitespace-nowrap space-x-1">
              <a href="<?= url('admin/tiendas.php?editar=' . urlencode($t['id_tienda'])) ?>" class="btn-line !py-1">Editar</a>
              <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta tienda?')">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id_tienda" value="<?= e($t['id_tienda']) ?>">
                <button class="btn-red !py-1">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Ayuda rápida del WebService -->
  <div class="card p-6 mt-8">
    <h2 class="font-display text-2xl font-bold uppercase text-espresso mb-3">URLs para las tiendas</h2>
    <?php $base = (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . BASE_URL; ?>
    <ul class="font-mono text-xs space-y-2 text-gray-700 break-all">
      <li><?= e($base) ?>/consulta?destino=02001&amp;formato=json</li>
      <li><?= e($base) ?>/envio?orden=ORD-1&amp;destinatario=Ana&amp;destino=02001&amp;direccion=5a+calle&amp;tienda=TIENDAPRUEBA001</li>
      <li><?= e($base) ?>/status?orden=ORD-1&amp;tienda=TIENDAPRUEBA001&amp;formato=xml</li>
    </ul>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
