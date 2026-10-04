<?php
require_once __DIR__ . '/lib/auth.php';

if (usuario_actual()) {
    redirigir('index.php');
}

$error  = '';
$nombre = '';

if (es_post()) {
    $nombre     = post('nombre');
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar  = $_POST['confirmar'] ?? '';

    if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
        $error = 'El usuario debe tener entre 3 y 100 caracteres.';
    } elseif (strlen($contrasena) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($contrasena !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        try {
            $u = db_uno(
                'INSERT INTO Usuario (nombre, contrasena) VALUES ($1, $2)
                 RETURNING id_usuario, nombre, es_admin',
                [$nombre, password_hash($contrasena, PASSWORD_BCRYPT)]
            );
            iniciar_sesion($u);
            flash('ok', '¡Cuenta creada! Ya puedes registrar tu primer envío.');
            redirigir('enviar.php');
        } catch (DbError $e) {
            $error = $e->sqlstate === '23505' ? 'Ese usuario ya existe. Elige otro.' : db_mensaje_error($e);
        }
    }
}

$titulo = 'Crear cuenta';
require __DIR__ . '/includes/header.php';
?>

<section class="flex justify-center px-4 py-16">
  <div class="card w-full max-w-md p-8">
    <h1 class="titulo text-center">Crear cuenta</h1>
    <p class="text-center text-gray-500 text-sm mb-6">Regístrate para comenzar a realizar tus envíos</p>

    <?php if ($error): ?>
      <div class="p-3 mb-5 rounded-lg text-sm text-center font-semibold bg-red-50 text-red-700 border border-red-200"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label for="nombre" class="label">Usuario</label>
        <input id="nombre" name="nombre" required minlength="3" maxlength="100" value="<?= e($nombre) ?>" placeholder="Ej. juanperez" class="input">
      </div>
      <div>
        <label for="contrasena" class="label">Contraseña</label>
        <input id="contrasena" name="contrasena" type="password" required minlength="6" placeholder="Mínimo 6 caracteres" class="input">
      </div>
      <div>
        <label for="confirmar" class="label">Confirmar contraseña</label>
        <input id="confirmar" name="confirmar" type="password" required minlength="6" class="input">
      </div>
      <button class="btn-gold w-full py-3 uppercase font-extrabold">Registrarme</button>
    </form>

    <p class="mt-6 pt-6 border-t border-gray-100 text-center text-sm text-gray-600">
      ¿Ya tienes una cuenta?
      <a href="<?= url('login.php') ?>" class="text-espresso font-bold hover:underline">Inicia sesión</a>
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
