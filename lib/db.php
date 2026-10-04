<?php
/* =====================================================================
 *  Acceso a PostgreSQL.
 *  Todas las consultas usan parámetros ($1, $2, ...) → sin inyección SQL.
 * ===================================================================== */

require_once __DIR__ . '/../config/config.php';

class DbError extends Exception
{
    public string $sqlstate;

    public function __construct(string $mensaje, string $sqlstate = '')
    {
        parent::__construct($mensaje);
        $this->sqlstate = $sqlstate;
    }
}

/** Devuelve la conexión (se abre una sola vez por petición). */
function db()
{
    static $conn = null;
    if ($conn === null) {
        $cadena = sprintf(
            "host=%s port=%s dbname=%s user=%s password='%s'",
            DB_HOST, DB_PORT, DB_NAME, DB_USER, addslashes(DB_PASS)
        );
        $conn = @pg_connect($cadena);
        if (!$conn) {
            http_response_code(500);
            die('No se pudo conectar a la base de datos. Revisa config/config.php');
        }
        pg_set_client_encoding($conn, 'UTF8');
    }
    return $conn;
}

/** Ejecuta una consulta y lanza DbError (con el código SQLSTATE) si falla. */
function db_query(string $sql, array $params = [])
{
    $c = db();
    if (!pg_send_query_params($c, $sql, $params)) {
        throw new DbError(pg_last_error($c));
    }
    $res = pg_get_result($c);
    while (pg_get_result($c) !== false) { /* vaciar resultados extra */ }

    $sqlstate = pg_result_error_field($res, PGSQL_DIAG_SQLSTATE);
    if ($sqlstate) {
        throw new DbError(pg_result_error($res), $sqlstate);
    }
    return $res;
}

function db_todos(string $sql, array $params = []): array
{
    return pg_fetch_all(db_query($sql, $params)) ?: [];
}

function db_uno(string $sql, array $params = []): ?array
{
    $fila = pg_fetch_assoc(db_query($sql, $params));
    return $fila === false ? null : $fila;
}

function db_ejecutar(string $sql, array $params = []): int
{
    return pg_affected_rows(db_query($sql, $params));
}

function db_transaccion(callable $fn)
{
    db_query('BEGIN');
    try {
        $r = $fn();
        db_query('COMMIT');
        return $r;
    } catch (Throwable $e) {
        db_query('ROLLBACK');
        throw $e;
    }
}

/** Traduce los errores más comunes de Postgres a un mensaje entendible. */
function db_mensaje_error(Throwable $e): string
{
    $codigo = $e instanceof DbError ? $e->sqlstate : '';
    switch ($codigo) {
        case '23505': return 'Ya existe un registro con ese identificador.';
        case '23503': return 'No se puede completar: el registro está relacionado con otros datos (por ejemplo, envíos).';
        case '23514': return 'Alguno de los valores no es válido (revisa números y longitudes).';
        case '22P02':
        case '22003': return 'Alguno de los valores tiene un formato incorrecto.';
        case '22001': return 'Alguno de los textos es demasiado largo.';
        default:      return 'Error en la base de datos: ' . $e->getMessage();
    }
}
