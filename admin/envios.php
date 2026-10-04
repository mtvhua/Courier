<?php
/* =====================================================================
 *  ADMIN · Envíos (CRUD completo + actualización de cada estado)
 *    ?estado=surtiendose   → pantalla con los envíos en ese estado
 *    ?nuevo=1              → formulario de creación
 *    ?editar=ID            → formulario de edición
 * ===================================================================== */
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/paquetes.php';
requerir_admin();

const TAMANOS = ['', 'Sobre', 'Pequeño', 'Mediano', 'Grande'];

$filtro = $_GET['estado'] ?? '';
if (!isset(ESTADOS[$filtro])) $filtro = '';
$buscar = trim($_GET['q'] ?? '');

/** URL de regreso conservando filtro y búsqueda. */
function volver(): string
{
    $q = array_filter(['estado' => $_POST['_estado'] ?? '', 'q' => $_POST['_q'] ?? '']);
    return 'admin/envios.php' . ($q ? '?' . http_build_query($q) : '');
}

/* ---------------------------------------------------------------------
 *  ACCIONES (POST → Redirect → GET)
 * ------------------------------------------------------------------- */
if (es_post()) {
    $accion = post('accion');
    $id     = (int) post('id_paquete');

    try {
        switch ($accion) {
            case 'avanzar':
                $p = paquete_detalle('id', (string) $id);
                $sig = $p ? estado_siguiente($p['estado']) : null;
                if ($sig && cambiar_estado($id, $sig)) {
                    flash('ok', 'El envío <span class="font-mono">' . e($p['tracking_id']) . '</span> pasó a <strong>' . ESTADOS[$sig] . '</strong>.');
                } else {
                    flash('error', 'Ese envío ya está entregado o no existe.');
                }
                redirigir(volver());

            case 'crear':
                $p = crear_paquete([
                    'numero_orden' => post('numero_orden'),
                    'destinatario' => post('destinatario'),
                    'direccion'    => post('direccion'),
                    'destino'      => post('destino'),
                    'peso'         => post('peso'),
                    'tamano'       => post('tamano'),
                    'id_tienda'    => post('id_tienda'),
                ]);
                flash('ok', 'Envío creado con tracking <strong class="font-mono">' . e($p['tracking_id']) . '</strong>.');
                redirigir('admin/envios.php');

            case 'actualizar':
                $ruta = ruta_hacia(post('destino'));
                if (!$ruta) throw new InvalidArgumentException('Destino sin cobertura.');
                db_ejecutar(
                    'UPDATE Paquete
                        SET numero_orden = $1, destinatario = $2, direccion = $3,
                            peso = $4, tamano = $5, id_tienda = $6, id_ruta = $7
                      WHERE id_paquete = $8',
                    [
                        post('numero_orden') ?: null, post('destinatario'), post('direccion'),
                        post('peso') ?: null, post('tamano') ?: null, post('id_tienda') ?: null,
                        $ruta['id_ruta'], $id,
                    ]
                );
                if (isset(ESTADOS[post('estado')])) {
                    cambiar_estado($id, post('estado'));
                }
                flash('ok', 'Envío actualizado.');
                redirigir('admin/envios.php');

            case 'eliminar':
                $n = db_ejecutar('DELETE FROM Paquete WHERE id_paquete = $1', [$id]);
                flash($n ? 'ok' : 'error', $n ? 'Envío eliminado.' : 'El envío no existe.');
                redirigir(volver());
        }
    } catch (InvalidArgumentException $e) {
        flash('error', e($e->getMessage()));
    } catch (DbError $e) {
        flash('error', e(db_mensaje_error($e)));
    }
    // Si algo falló, regresar al formulario
    redirigir($accion === 'actualizar' ? "admin/envios.php?editar=$id" : ($accion === 'crear' ? 'admin/envios.php?nuevo=1' : volver()));
}

/* ---------------------------------------------------------------------
 *  DATOS PARA LA VISTA
 * ------------------------------------------------------------------- */
$conteo = ['' => 0];
foreach (db_todos('SELECT estado, count(*) AS n FROM Paquete GROUP BY estado') as $f) {
    $conteo[$f['estado']] = (int) $f['n'];
    $conteo[''] += (int) $f['n'];
}

$where  = [];
$params = [];
if ($filtro !== '') {
    $params[] = $filtro;
    $where[]  = 'p.estado = $' . count($params);
}
if ($buscar !== '') {
    $params[] = '%' . $buscar . '%';
    $n = count($params);
    $where[] = "(p.tracking_id ILIKE \$$n OR p.numero_orden ILIKE \$$n OR p.destinatario ILIKE \$$n)";
}

$envios = db_todos(
    "SELECT p.*, c.nombre AS destino, u.nombre AS usuario, t.nombre AS tienda
       FROM Paquete p
       JOIN Ruta r        ON r.id_ruta = p.id_ruta
       JOIN Ciudad c      ON c.codigo_postal = r.codigo_destino
       LEFT JOIN Usuario u ON u.id_usuario = p.id_usuario
       LEFT JOIN Tienda t  ON t.id_tienda = p.id_tienda
     " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
      ORDER BY p.id_paquete DESC",
    $params
);

$editando = isset($_GET['editar']) ? paquete_detalle('id', (string) (int) $_GET['editar']) : null;
$creando  = isset($_GET['nuevo']);
$destinos = tarifas();
$tiendas  = db_todos('SELECT id_tienda, nombre FROM Tienda ORDER BY nombre');

$titulo  = 'Admin · Envíos';
$seccion = 'admin_envios';
require __DIR__ . '/../includes/header.php';
?>

<section class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
  <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
    <div>
      <h1 class="titulo">Envíos</h1>
      <p class="text-gray-600">Registro de envíos contratados y actualización de su estado.</p>
    </div>
    <?php if (!$creando && !$editando): ?>
      <a href="<?= url('admin/envios.php?nuevo=1') ?>" class="btn-dark">+ Nuevo envío</a>
    <?php endif; ?>
  </div>

  <?php if ($creando || $editando): $v = $editando ?: []; ?>
  <!-- ===================== FORMULARIO ===================== -->
  <form method="POST" class="card p-6 mb-8 space-y-4 border-gold/50">
    <h2 class="font-display text-2xl font-bold uppercase text-espresso">
      <?= $editando ? 'Editar envío <span class="font-mono text-base normal-case text-gray-500">' . e($v['tracking_id']) . '</span>' : 'Nuevo envío' ?>
    </h2>
    <input type="hidden" name="accion" value="<?= $editando ? 'actualizar' : 'crear' ?>">
    <input type="hidden" name="id_paquete" value="<?= (int) ($v['id_paquete'] ?? 0) ?>">

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div>
        <label class="label">Destinatario *</label>
        <input name="destinatario" required maxlength="150" value="<?= e($v['destinatario'] ?? '') ?>" class="input">
      </div>
      <div class="lg:col-span-2">
        <label class="label">Dirección *</label>
        <input name="direccion" required maxlength="255" value="<?= e($v['direccion'] ?? '') ?>" class="input">
      </div>
      <div>
        <label class="label">Destino *</label>
        <select name="destino" required class="input">
          <option value="">—</option>
          <?php foreach ($destinos as $t): ?>
            <option value="<?= e($t['codigo_postal']) ?>" <?= ($v['codigo_destino'] ?? '') === $t['codigo_postal'] ? 'selected' : '' ?>>
              <?= e($t['destino']) ?> — <?= dinero($t['precio']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="label">Tienda</label>
        <select name="id_tienda" class="input">
          <option value="">(ninguna — cliente directo)</option>
          <?php foreach ($tiendas as $t): ?>
            <option value="<?= e($t['id_tienda']) ?>" <?= ($v['id_tienda'] ?? '') === $t['id_tienda'] ? 'selected' : '' ?>>
              <?= e($t['nombre']) ?> (<?= e($t['id_tienda']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="label">N.º de orden</label>
        <input name="numero_orden" maxlength="50" value="<?= e($v['numero_orden'] ?? '') ?>" class="input font-mono">
      </div>
      <div>
        <label class="label">Peso (lb)</label>
        <input name="peso" type="number" step="0.01" min="0.01" value="<?= e($v['peso'] ?? '') ?>" class="input">
      </div>
      <div>
        <label class="label">Tamaño</label>
        <select name="tamano" class="input">
          <?php foreach (TAMANOS as $t): ?>
            <option value="<?= e($t) ?>" <?= ($v['tamano'] ?? '') === $t ? 'selected' : '' ?>><?= $t === '' ? '—' : e($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($editando): ?>
      <div>
        <label class="label">Estado</label>
        <select name="estado" class="input">
          <?php foreach (ESTADOS as $clave => $etiqueta): ?>
            <option value="<?= e($clave) ?>" <?= $v['estado'] === $clave ? 'selected' : '' ?>><?= estado_numero($clave) ?>. <?= e($etiqueta) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
    </div>

    <div class="flex gap-3 justify-end pt-2">
      <a href="<?= url('admin/envios.php') ?>" class="btn-line">Cancelar</a>
      <button class="btn-gold"><?= $editando ? 'Guardar cambios' : 'Crear envío' ?></button>
    </div>
  </form>
  <?php endif; ?>

  <!-- ===================== PESTAÑAS POR ESTADO ===================== -->
  <div class="flex flex-wrap gap-2 mb-4">
    <?php
      $tabs = ['' => 'Todos'] + ESTADOS;
      foreach ($tabs as $clave => $etiqueta):
        $activo = $filtro === $clave;
        $href = url('admin/envios.php' . ($clave !== '' ? '?estado=' . urlencode($clave) : ''));
    ?>
      <a href="<?= $href ?>"
         class="px-3 py-1.5 rounded-full text-sm font-semibold border transition-colors
                <?= $activo ? 'bg-espresso text-white border-espresso' : 'bg-white text-espresso border-gray-300 hover:border-espresso' ?>">
        <?= $clave !== '' ? estado_numero($clave) . '. ' : '' ?><?= e($etiqueta) ?>
        <span class="ml-1 <?= $activo ? 'text-gold' : 'text-gray-400' ?>"><?= $conteo[$clave] ?? 0 ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <form method="GET" class="mb-4 flex gap-2 max-w-md">
    <?php if ($filtro): ?><input type="hidden" name="estado" value="<?= e($filtro) ?>"><?php endif; ?>
    <input name="q" value="<?= e($buscar) ?>" placeholder="Buscar tracking, orden o destinatario" class="input">
    <button class="btn-line">Buscar</button>
  </form>

  <!-- ===================== TABLA ===================== -->
  <div class="card overflow-x-auto">
    <table class="tabla w-full text-sm">
      <thead>
        <tr><th>Tracking / Orden</th><th>Origen del pedido</th><th>Destinatario</th><th>Destino</th><th>Estado</th><th class="!text-right">Acciones</th></tr>
      </thead>
      <tbody>
      <?php if (!$envios): ?>
        <tr><td colspan="6" class="text-center text-gray-400 py-10">No hay envíos <?= $filtro ? 'en este estado' : '' ?>.</td></tr>
      <?php endif; ?>
      <?php foreach ($envios as $p): $sig = estado_siguiente($p['estado']); ?>
        <tr>
          <td>
            <div class="font-mono font-medium text-espresso"><?= e($p['tracking_id']) ?></div>
            <div class="text-xs text-gray-500"><?= $p['numero_orden'] ? 'Orden ' . e($p['numero_orden']) : 'Sin n.º de orden' ?></div>
          </td>
          <td class="text-xs">
            <?php if ($p['tienda']): ?>
              <span class="chip bg-gold/20 text-espresso">Tienda</span> <?= e($p['tienda']) ?>
            <?php else: ?>
              <span class="chip bg-gray-100 text-gray-600">Cliente</span> <?= e($p['usuario'] ?? '—') ?>
            <?php endif; ?>
          </td>
          <td>
            <div><?= e($p['destinatario']) ?></div>
            <div class="text-xs text-gray-500 max-w-[220px] truncate" title="<?= e($p['direccion']) ?>"><?= e($p['direccion']) ?></div>
          </td>
          <td><?= e($p['destino']) ?></td>
          <td><?= chip_estado($p['estado']) ?></td>
          <td class="text-right whitespace-nowrap space-x-1">
            <?php if ($sig): ?>
              <form method="POST" class="inline">
                <input type="hidden" name="accion" value="avanzar">
                <input type="hidden" name="id_paquete" value="<?= (int) $p['id_paquete'] ?>">
                <input type="hidden" name="_estado" value="<?= e($filtro) ?>">
                <input type="hidden" name="_q" value="<?= e($buscar) ?>">
                <button class="btn-gold !py-1" title="Pasar a <?= e(ESTADOS[$sig]) ?>">→ <?= e(ESTADOS[$sig]) ?></button>
              </form>
            <?php endif; ?>
            <a href="<?= url('admin/envios.php?editar=' . (int) $p['id_paquete']) ?>" class="btn-line !py-1">Editar</a>
            <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar el envío <?= e($p['tracking_id']) ?> de forma permanente?')">
              <input type="hidden" name="accion" value="eliminar">
              <input type="hidden" name="id_paquete" value="<?= (int) $p['id_paquete'] ?>">
              <input type="hidden" name="_estado" value="<?= e($filtro) ?>">
              <input type="hidden" name="_q" value="<?= e($buscar) ?>">
              <button class="btn-red !py-1">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
