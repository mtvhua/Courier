<?php
/* =====================================================================
 *  Lógica del negocio: rutas, paquetes y estados.
 *  La usan tanto las páginas como el WebService, así no se repite SQL.
 * ===================================================================== */

require_once __DIR__ . '/db.php';

const ESTADOS = [
    'orden nueva' => 'Orden nueva',
    'surtiendose' => 'Surtiéndose',
    'empacandose' => 'Empacándose',
    'en ruta'     => 'En ruta',
    'entregada'   => 'Entregada',
];

function estado_numero(string $estado): int
{
    $i = array_search($estado, array_keys(ESTADOS), true);
    return $i === false ? 0 : $i + 1;
}

function estado_siguiente(string $estado): ?string
{
    $claves = array_keys(ESTADOS);
    $i = array_search($estado, $claves, true);
    return ($i !== false && isset($claves[$i + 1])) ? $claves[$i + 1] : null;
}

/* ---------- Rutas / tarifas ---------- */

function tarifas(): array
{
    return db_todos(
        "SELECT r.id_ruta, c.codigo_postal, c.nombre AS destino, r.precio
           FROM Ruta r JOIN Ciudad c ON c.codigo_postal = r.codigo_destino
          WHERE r.codigo_origen = $1
          ORDER BY c.codigo_postal",
        [CODIGO_ORIGEN]
    );
}

/** Ruta desde el origen hacia $codigo_destino, o null si no hay cobertura. */
function ruta_hacia(string $codigo_destino): ?array
{
    return db_uno(
        "SELECT r.id_ruta, r.precio, c.nombre
           FROM Ruta r JOIN Ciudad c ON c.codigo_postal = r.codigo_destino
          WHERE r.codigo_origen = $1 AND r.codigo_destino = $2",
        [CODIGO_ORIGEN, $codigo_destino]
    );
}

/* ---------- Paquetes ---------- */

function generar_tracking(): string
{
    $letras = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $sufijo = '';
        for ($i = 0; $i < 5; $i++) {
            $sufijo .= $letras[random_int(0, strlen($letras) - 1)];
        }
        $tracking = 'EXP' . date('Ymd') . $sufijo;   // ej. EXP20261003K7Q2M
    } while (db_uno('SELECT 1 FROM Paquete WHERE tracking_id = $1', [$tracking]));

    return $tracking;
}

/**
 * Crea un paquete (estado inicial: 'orden nueva').
 * $d: destino, destinatario, direccion y opcionales numero_orden, id_tienda, id_usuario, peso, tamano
 */
function crear_paquete(array $d): array
{
    $ruta = ruta_hacia($d['destino']);
    if (!$ruta) {
        throw new InvalidArgumentException('No hay cobertura hacia el destino ' . $d['destino']);
    }

    return db_uno(
        "INSERT INTO Paquete (tracking_id, numero_orden, destinatario, direccion, peso, tamano,
                              id_ruta, id_usuario, id_tienda)
         VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
         RETURNING *",
        [
            generar_tracking(),
            ($d['numero_orden'] ?? '') !== '' ? $d['numero_orden'] : null,
            $d['destinatario'],
            $d['direccion'],
            ($d['peso'] ?? '') !== '' ? $d['peso'] : null,
            ($d['tamano'] ?? '') !== '' ? $d['tamano'] : null,
            $ruta['id_ruta'],
            $d['id_usuario'] ?? null,
            ($d['id_tienda'] ?? '') !== '' ? $d['id_tienda'] : null,
        ]
    );
}


function cambiar_estado(int $id_paquete, string $estado): bool
{
    if (!isset(ESTADOS[$estado])) {
        throw new InvalidArgumentException('Estado inválido');
    }
    return db_ejecutar(
        'UPDATE Paquete SET estado = $1 WHERE id_paquete = $2 AND estado <> $1',
        [$estado, $id_paquete]
    ) > 0;
}


function paquete_detalle(string $campo, string $valor, ?string $tienda = null): ?array
{
    $campos = ['tracking' => 'p.tracking_id', 'id' => 'p.id_paquete', 'orden' => 'p.numero_orden'];
    $where  = $campos[$campo] . ' = $1';
    $params = [$valor];
    if ($tienda !== null) {
        $where   .= ' AND p.id_tienda = $2';
        $params[] = $tienda;
    }
    return db_uno(
        "SELECT p.*, c.nombre AS ciudad_destino, c.codigo_postal AS codigo_destino, r.precio
           FROM Paquete p
           JOIN Ruta r   ON r.id_ruta = p.id_ruta
           JOIN Ciudad c ON c.codigo_postal = r.codigo_destino
          WHERE $where",
        $params
    );
}


function chip_estado(string $estado): string
{
    $colores = [
        'orden nueva' => 'bg-gray-100 text-gray-700',
        'surtiendose' => 'bg-blue-100 text-blue-800',
        'empacandose' => 'bg-purple-100 text-purple-800',
        'en ruta'     => 'bg-amber-100 text-amber-800',
        'entregada'   => 'bg-green-100 text-green-800',
    ];
    $clase = $colores[$estado] ?? 'bg-gray-100 text-gray-700';
    $texto = estado_numero($estado) . '. ' . (ESTADOS[$estado] ?? $estado);
    return '<span class="chip ' . $clase . '">' . htmlspecialchars($texto) . '</span>';
}
