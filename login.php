<?php
require_once __DIR__ . '/lib/auth.php';

if (usuario_actual()) {
    redirigir(es_admin() ? 'admin/envios.php' : 'index.php');
}

$error  = '';
$nombre = '';

if (es_post()) {
    $nombre     = post('nombre');
    $contrasena = $_POST['contrasena'] ?? '';

    $u = db_uno('SELECT id_usuario, nombre, contrasena, es_admin FROM Usuario WHERE nombre = $1', [$nombre]);

    if ($u && password_verify($contrasena, $u['contrasena'])) {
        iniciar_sesion($u);
        flash('ok', 'Bienvenido, ' . e($u['nombre']) . '.');

        $volver = $_GET['volver'] ?? '';
        if ($volver !== '' && $volver[0] === '/' && strpos($volver, '//') !== 0) {
            header('Location: ' . $volver);
            exit;
        }
        redirigir(es_admin() ? 'admin/envios.php' : 'index.php');
    }
    $error = 'Usuario o contraseña incorrectos.';
}

$titulo = 'Iniciar sesión';
require __DIR__ . '/includes/header.php';
?>

<section class="flex justify-center px-4 py-16">
  <div class="card w-full max-w-md p-8">
    <h1 class="titulo text-center">Iniciar sesión</h1>
    <p class="text-center text-gray-500 text-sm mb-6">Ingresa tus credenciales para gestionar tus envíos</p>

    <?php if ($error): ?>
      <div class="p-3 mb-5 rounded-lg text-sm text-center font-semibold bg-red-50 text-red-700 border border-red-200"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label for="nombre" class="label">Usuario</label>
        <input id="nombre" name="nombre" required autofocus value="<?= e($nombre) ?>" placeholder="Ej. juanperez" class="input">
      </div>
      <div>
        <label for="contrasena" class="label">Contraseña</label>
        <input id="contrasena" name="contrasena" type="password" required placeholder="••••••••" class="input">
      </div>
      <button class="btn-gold w-full py-3 uppercase font-extrabold">Ingresar</button>
    </form>

    <p class="mt-6 pt-6 border-t border-gray-100 text-center text-sm text-gray-600">
      ¿Aún no tienes cuenta?
      <a href="<?= url('registro.php') ?>" class="text-espresso font-bold hover:underline">Regístrate aquí</a>
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
