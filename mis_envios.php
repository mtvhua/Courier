<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/paquetes.php';
requerir_login();

$yo = usuario_actual();

// El cliente puede cancelar un envío solo mientras sigue como "orden nueva"
if (es_post() && post('accion') === 'cancelar') {
    $n = db_ejecutar(
        "DELETE FROM Paquete WHERE id_paquete = $1 AND id_usuario = $2 AND estado = 'orden nueva'",
        [(int) post('id_paquete'), $yo['id']]
    );
    $n ? flash('ok', 'Envío cancelado.')
       : flash('error', 'Solo se pueden cancelar envíos que aún están como "orden nueva".');
    redirigir('mis_envios.php');
}

$envios = db_todos(
    "SELECT p.id_paquete, p.tracking_id, p.numero_orden, p.destinatario, p.estado, p.peso, p.tamano,
            c.nombre AS destino, r.precio
       FROM Paquete p
       JOIN Ruta r   ON r.id_ruta = p.id_ruta
       JOIN Ciudad c ON c.codigo_postal = r.codigo_destino
      WHERE p.id_usuario = $1
      ORDER BY p.id_paquete DESC",
    [$yo['id']]
);

$titulo  = 'Mis envíos';
$seccion = 'mis_envios';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
  <div class="flex flex-wrap justify-between items-end gap-4 mb-6">
    <div>
      <h1 class="titulo">Mis envíos</h1>
      <p class="text-gray-600"><?= count($envios) ?> envío(s) registrados</p>
    </div>
    <a href="<?= url('enviar.php') ?>" class="btn-dark">+ Nuevo envío</a>
  </div>

  <?php if (!$envios): ?>
    <div class="card p-10 text-center text-gray-500">
      Todavía no tienes envíos. <a href="<?= url('enviar.php') ?>" class="text-espresso font-semibold underline">Crea el primero</a>.
    </div>
  <?php else: ?>
    <div class="card overflow-x-auto">
      <table class="tabla w-full text-sm">
        <thead>
          <tr><th>Tracking</th><th>Destinatario</th><th>Destino</th><th>Costo</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($envios as $p): ?>
            <tr>
              <td class="font-mono font-medium text-espresso"><?= e($p['tracking_id']) ?></td>
              <td><?= e($p['destinatario']) ?></td>
              <td><?= e($p['destino']) ?></td>
              <td><?= dinero($p['precio']) ?></td>
              <td><?= chip_estado($p['estado']) ?></td>
              <td class="text-right whitespace-nowrap">
                <a href="<?= url('rastrear.php?tracking=' . urlencode($p['tracking_id'])) ?>" class="btn-line !py-1">Rastrear</a>
                <?php if ($p['estado'] === 'orden nueva'): ?>
                  <form method="POST" class="inline" onsubmit="return confirm('¿Cancelar este envío?')">
                    <input type="hidden" name="accion" value="cancelar">
                    <input type="hidden" name="id_paquete" value="<?= (int) $p['id_paquete'] ?>">
                    <button class="btn-red !py-1">Cancelar</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
