<?php
/* =====================================================================
 *  Sesión, permisos y utilidades de las páginas HTML.
 * ===================================================================== */

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function es_admin(): bool
{
    return !empty($_SESSION['usuario']['es_admin']);
}

function iniciar_sesion(array $fila): void
{
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id'       => (int) $fila['id_usuario'],
        'nombre'   => $fila['nombre'],
        'es_admin' => $fila['es_admin'] === 't' || $fila['es_admin'] === true,
    ];
}

function requerir_login(): void
{
    if (!usuario_actual()) {
        flash('error', 'Inicia sesión para continuar.');
        redirigir('login.php?volver=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
}

function requerir_admin(): void
{
    requerir_login();
    if (!es_admin()) {
        flash('error', 'Esa sección es solo para administradores.');
        redirigir('index.php');
    }
}


function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function url(string $ruta = ''): string
{
    return BASE_URL . '/' . ltrim($ruta, '/');
}

function redirigir(string $ruta): void
{
    header('Location: ' . url($ruta));
    exit;
}

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flash_sacar(): array
{
    $m = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $m;
}

function es_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post(string $campo): string
{
    return trim((string) ($_POST[$campo] ?? ''));
}

function dinero($n): string
{
    return 'Q' . number_format((float) $n, 2);
}
