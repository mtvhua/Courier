<?php
/* =====================================================================
 *  ADMIN · Destinos cubiertos y costo de manejo/envío (CRUD)
 *  Cada destino = una Ciudad + su Ruta desde el origen.
 * ===================================================================== */
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/paquetes.php';
requerir_admin();

if (es_post()) {
    $accion = post('accion');
    $codigo = post('codigo_postal');
    $nombre = post('nombre');
    $precio = post('precio');

    try {
        if ($accion === 'crear' || $accion === 'actualizar') {
            if (!preg_match('/^[0-9A-Za-z]{5}$/', $codigo)) throw new InvalidArgumentException('El código debe tener exactamente 5 caracteres.');
            if ($nombre === '') throw new InvalidArgumentException('El nombre es obligatorio.');
            if (!is_numeric($precio) || $precio < 0) throw new InvalidArgumentException('El precio debe ser un número mayor o igual a 0.');
            if ($codigo === CODIGO_ORIGEN) throw new InvalidArgumentException('Ese es el código de origen.');
        }

        if ($accion === 'crear') {
            db_transaccion(function () use ($codigo, $nombre, $precio) {
                db_query('INSERT INTO Ciudad (codigo_postal, nombre) VALUES ($1, $2)', [$codigo, $nombre]);
                db_query('INSERT INTO Ruta (codigo_origen, codigo_destino, precio) VALUES ($1, $2, $3)', [CODIGO_ORIGEN, $codigo, $precio]);
            });
            flash('ok', 'Destino <strong>' . e($nombre) . '</strong> agregado.');
        }

        if ($accion === 'actualizar') {
            db_transaccion(function () use ($codigo, $nombre, $precio) {
                db_query('UPDATE Ciudad SET nombre = $1 WHERE codigo_postal = $2', [$nombre, $codigo]);
                db_query('UPDATE Ruta SET precio = $1 WHERE codigo_origen = $2 AND codigo_destino = $3', [$precio, CODIGO_ORIGEN, $codigo]);
            });
            flash('ok', 'Destino <strong>' . e($nombre) . '</strong> actualizado.');
        }

        if ($accion === 'eliminar') {
            db_ejecutar('DELETE FROM Ciudad WHERE codigo_postal = $1', [$codigo]);
            flash('ok', 'Destino eliminado.');
        }
    } catch (InvalidArgumentException $e) {
        flash('error', e($e->getMessage()));
        redirigir('admin/destinos.php' . ($accion === 'actualizar' ? '?editar=' . urlencode($codigo) : ''));
    } catch (DbError $e) {
        $msg = $e->sqlstate === '23503'
            ? 'No se puede eliminar: hay envíos registrados hacia ese destino.'
            : db_mensaje_error($e);
        flash('error', e($msg));
    }
    redirigir('admin/destinos.php');
}

$destinos = db_todos(
    "SELECT c.codigo_postal, c.nombre AS destino, r.precio, count(p.id_paquete) AS envios
       FROM Ciudad c
       JOIN Ruta r ON r.codigo_destino = c.codigo_postal AND r.codigo_origen = $1
       LEFT JOIN Paquete p ON p.id_ruta = r.id_ruta
      GROUP BY c.codigo_postal, c.nombre, r.precio
      ORDER BY c.codigo_postal",
    [CODIGO_ORIGEN]
);

$editando = null;
if (isset($_GET['editar'])) {
    foreach ($destinos as $d) {
        if ($d['codigo_postal'] === $_GET['editar']) $editando = $d;
    }
}

$titulo  = 'Admin · Destinos';
$seccion = 'admin_destinos';
require __DIR__ . '/../includes/header.php';
?>

<section class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
  <h1 class="titulo">Destinos y tarifas</h1>
  <p class="text-gray-600 mb-6">Ciudades cubiertas y costo de manejo y envío desde <?= e(CODIGO_ORIGEN) ?>. Estos precios son los que responde <code class="font-mono">/consulta</code>.</p>

  <div class="grid lg:grid-cols-[320px_1fr] gap-6 items-start">

    <!-- ===================== FORMULARIO ===================== -->
    <form method="POST" class="card p-6 space-y-4 lg:sticky lg:top-6">
      <h2 class="font-display text-2xl font-bold uppercase text-espresso"><?= $editando ? 'Editar destino' : 'Nuevo destino' ?></h2>
      <input type="hidden" name="accion" value="<?= $editando ? 'actualizar' : 'crear' ?>">

      <div>
        <label class="label">Código (5 caracteres)</label>
        <input name="codigo_postal" required maxlength="5" minlength="5" pattern="[0-9A-Za-z]{5}"
               value="<?= e($editando['codigo_postal'] ?? '') ?>" <?= $editando ? 'readonly' : '' ?>
               class="input font-mono <?= $editando ? 'bg-gray-100 text-gray-500' : '' ?>" placeholder="Ej. 23001">
      </div>
      <div>
        <label class="label">Ciudad / departamento</label>
        <input name="nombre" required maxlength="100" value="<?= e($editando['destino'] ?? '') ?>" class="input">
      </div>
      <div>
        <label class="label">Costo (Q)</label>
        <input name="precio" type="number" step="0.01" min="0" required value="<?= e($editando['precio'] ?? '') ?>" class="input">
      </div>

      <div class="flex gap-2">
        <button class="btn-gold flex-1"><?= $editando ? 'Guardar' : 'Agregar' ?></button>
        <?php if ($editando): ?><a href="<?= url('admin/destinos.php') ?>" class="btn-line">Cancelar</a><?php endif; ?>
      </div>
    </form>

    <!-- ===================== TABLA ===================== -->
    <div class="card overflow-x-auto">
      <table class="tabla w-full text-sm">
        <thead><tr><th>Código</th><th>Destino</th><th class="!text-right">Costo</th><th class="!text-right">Envíos</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($destinos as $d): ?>
          <tr class="<?= ($editando['codigo_postal'] ?? '') === $d['codigo_postal'] ? 'bg-gold/10' : '' ?>">
            <td class="font-mono text-gray-500"><?= e($d['codigo_postal']) ?></td>
            <td class="font-medium text-espresso"><?= e($d['destino']) ?></td>
            <td class="text-right font-semibold"><?= dinero($d['precio']) ?></td>
            <td class="text-right text-gray-500"><?= (int) $d['envios'] ?></td>
            <td class="text-right whitespace-nowrap space-x-1">
              <a href="<?= url('admin/destinos.php?editar=' . urlencode($d['codigo_postal'])) ?>" class="btn-line !py-1">Editar</a>
              <?php if ((int) $d['envios'] === 0): ?>
                <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar <?= e($d['destino']) ?>?')">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="codigo_postal" value="<?= e($d['codigo_postal']) ?>">
                  <button class="btn-red !py-1">Eliminar</button>
                </form>
              <?php else: ?>
                <span class="btn !py-1 text-gray-300 cursor-not-allowed" title="Tiene envíos registrados">Eliminar</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
