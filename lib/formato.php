<?php
/* =====================================================================
 *  JSON y XML hechos a mano
 *
 *    a_json($valor)          arreglo PHP  → texto JSON
 *    desde_json($texto)      texto JSON   → arreglo PHP
 *    a_xml($raiz, $datos)    arreglo PHP  → texto XML
 *    responder($raiz, $datos, $formato)   envía la respuesta del WebService
 *    parametro($nombre)      lee un argumento de GET, POST o de un cuerpo JSON
 * ===================================================================== */

/* ---------------------------------------------------------------------
 *  CODIFICAR JSON
 * ------------------------------------------------------------------- */

function a_json($valor, int $nivel = 0): string
{
    if ($valor === null)   return 'null';
    if ($valor === true)   return 'true';
    if ($valor === false)  return 'false';
    if (is_int($valor))    return (string) $valor;
    if (is_float($valor))  return is_finite($valor) ? rtrim(rtrim(sprintf('%.10F', $valor), '0'), '.') : 'null';
    if (is_string($valor)) return '"' . json_escapar($valor) . '"';

    if (is_array($valor)) {
        if (count($valor) === 0) {
            return '[]';
        }
        $sangria  = str_repeat('  ', $nivel + 1);
        $cierre   = str_repeat('  ', $nivel);
        $es_lista = array_keys($valor) === range(0, count($valor) - 1);
        $partes   = [];

        foreach ($valor as $clave => $v) {
            $partes[] = $es_lista
                ? $sangria . a_json($v, $nivel + 1)
                : $sangria . '"' . json_escapar((string) $clave) . '": ' . a_json($v, $nivel + 1);
        }
        [$abre, $cierra] = $es_lista ? ['[', ']'] : ['{', '}'];
        return $abre . "\n" . implode(",\n", $partes) . "\n" . $cierre . $cierra;
    }

    return '"' . json_escapar((string) $valor) . '"';
}

/** Escapa comillas, barras y caracteres de control. Los acentos se dejan en UTF-8. */
function json_escapar(string $s): string
{
    $s = strtr($s, [
        '\\' => '\\\\', '"' => '\\"', "\n" => '\\n', "\r" => '\\r',
        "\t" => '\\t', "\x08" => '\\b', "\x0C" => '\\f',
    ]);
    // Otros caracteres de control (0x00–0x1F) → \u00XX
    return preg_replace_callback('/[\x00-\x1F]/', function ($m) {
        return sprintf('\\u%04x', ord($m[0]));
    }, $s);
}

/* ---------------------------------------------------------------------
 *  DECODIFICAR JSON  (parser recursivo)
 * ------------------------------------------------------------------- */

function desde_json(string $texto)
{
    $i = 0;
    $valor = _json_valor($texto, $i);
    _json_espacios($texto, $i);
    if ($i !== strlen($texto)) {
        throw new InvalidArgumentException("JSON inválido: texto sobrante en la posición $i");
    }
    return $valor;
}

function _json_espacios(string $t, int &$i): void
{
    $n = strlen($t);
    while ($i < $n && ($t[$i] === ' ' || $t[$i] === "\t" || $t[$i] === "\n" || $t[$i] === "\r")) {
        $i++;
    }
}

function _json_valor(string $t, int &$i)
{
    _json_espacios($t, $i);
    if ($i >= strlen($t)) {
        throw new InvalidArgumentException('JSON inválido: terminó antes de tiempo');
    }

    $c = $t[$i];
    if ($c === '{') return _json_objeto($t, $i);
    if ($c === '[') return _json_arreglo($t, $i);
    if ($c === '"') return _json_cadena($t, $i);

    foreach (['true' => true, 'false' => false, 'null' => null] as $palabra => $v) {
        if (substr($t, $i, strlen($palabra)) === $palabra) {
            $i += strlen($palabra);
            return $v;
        }
    }

    if (preg_match('/-?(0|[1-9]\d*)(\.\d+)?([eE][+-]?\d+)?/A', $t, $m, 0, $i)) {
        $i += strlen($m[0]);
        return (isset($m[2]) && $m[2] !== '') || isset($m[3]) ? (float) $m[0] : (int) $m[0];
    }

    throw new InvalidArgumentException("JSON inválido: carácter inesperado '$c' en la posición $i");
}

function _json_objeto(string $t, int &$i): array
{
    $obj = [];
    $i++; // {
    _json_espacios($t, $i);
    if (($t[$i] ?? '') === '}') { $i++; return $obj; }

    while (true) {
        _json_espacios($t, $i);
        if (($t[$i] ?? '') !== '"') {
            throw new InvalidArgumentException("JSON inválido: se esperaba una clave en la posición $i");
        }
        $clave = _json_cadena($t, $i);
        _json_espacios($t, $i);
        if (($t[$i] ?? '') !== ':') {
            throw new InvalidArgumentException("JSON inválido: se esperaba ':' en la posición $i");
        }
        $i++;
        $obj[$clave] = _json_valor($t, $i);
        _json_espacios($t, $i);

        $c = $t[$i] ?? '';
        $i++;
        if ($c === '}') return $obj;
        if ($c !== ',') throw new InvalidArgumentException("JSON inválido: se esperaba ',' o '}' en la posición " . ($i - 1));
    }
}

function _json_arreglo(string $t, int &$i): array
{
    $arr = [];
    $i++; // [
    _json_espacios($t, $i);
    if (($t[$i] ?? '') === ']') { $i++; return $arr; }

    while (true) {
        $arr[] = _json_valor($t, $i);
        _json_espacios($t, $i);

        $c = $t[$i] ?? '';
        $i++;
        if ($c === ']') return $arr;
        if ($c !== ',') throw new InvalidArgumentException("JSON inválido: se esperaba ',' o ']' en la posición " . ($i - 1));
    }
}

function _json_cadena(string $t, int &$i): string
{
    $i++; // comilla inicial
    $s = '';
    $n = strlen($t);

    while ($i < $n) {
        $c = $t[$i++];
        if ($c === '"') return $s;
        if ($c !== '\\') { $s .= $c; continue; }

        $e = $t[$i++] ?? '';
        switch ($e) {
            case '"':  $s .= '"';  break;
            case '\\': $s .= '\\'; break;
            case '/':  $s .= '/';  break;
            case 'b':  $s .= "\x08"; break;
            case 'f':  $s .= "\x0C"; break;
            case 'n':  $s .= "\n"; break;
            case 'r':  $s .= "\r"; break;
            case 't':  $s .= "\t"; break;
            case 'u':
                $cp = hexdec(substr($t, $i, 4));
                $i += 4;
                // Par sustituto (emojis, etc.)
                if ($cp >= 0xD800 && $cp <= 0xDBFF && substr($t, $i, 2) === '\\u') {
                    $bajo = hexdec(substr($t, $i + 2, 4));
                    $i += 6;
                    $cp = 0x10000 + (($cp - 0xD800) << 10) + ($bajo - 0xDC00);
                }
                $s .= _codepoint_a_utf8($cp);
                break;
            default:
                throw new InvalidArgumentException("JSON inválido: escape '\\$e' desconocido");
        }
    }
    throw new InvalidArgumentException('JSON inválido: cadena sin cerrar');
}

function _codepoint_a_utf8(int $cp): string
{
    if ($cp < 0x80)    return chr($cp);
    if ($cp < 0x800)   return chr(0xC0 | ($cp >> 6)) . chr(0x80 | ($cp & 0x3F));
    if ($cp < 0x10000) return chr(0xE0 | ($cp >> 12)) . chr(0x80 | (($cp >> 6) & 0x3F)) . chr(0x80 | ($cp & 0x3F));
    return chr(0xF0 | ($cp >> 18)) . chr(0x80 | (($cp >> 12) & 0x3F))
         . chr(0x80 | (($cp >> 6) & 0x3F)) . chr(0x80 | ($cp & 0x3F));
}

/* ---------------------------------------------------------------------
 *  XML
 * ------------------------------------------------------------------- */

function a_xml(string $raiz, array $datos): string
{
    return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . _xml_nodo($raiz, $datos, 0);
}

function _xml_nodo(string $etiqueta, $valor, int $nivel): string
{
    $sangria = str_repeat('  ', $nivel);

    if (is_array($valor)) {
        $hijos = '';
        foreach ($valor as $clave => $v) {
            // Las listas repiten la etiqueta en singular: <rutas><item>..</item></rutas>
            $hijos .= _xml_nodo(is_int($clave) ? 'item' : (string) $clave, $v, $nivel + 1);
        }
        return "$sangria<$etiqueta>\n$hijos$sangria</$etiqueta>\n";
    }

    if (is_bool($valor)) $valor = $valor ? 'TRUE' : 'FALSE';
    $texto = htmlspecialchars((string) $valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    return "$sangria<$etiqueta>$texto</$etiqueta>\n";
}

/* ---------------------------------------------------------------------
 *  RESPUESTA DEL WEBSERVICE
 * ------------------------------------------------------------------- */

/**
 * Envía { "$raiz": $datos } en JSON (por defecto) o <$raiz>...</$raiz> en XML.
 */
function responder(string $raiz, array $datos, ?string $formato = null, int $http = 200): void
{
    $formato = strtolower(trim($formato ?? (string) parametro('formato', 'json')));

    http_response_code($http);
    header('Access-Control-Allow-Origin: *');

    if ($formato === 'xml') {
        header('Content-Type: application/xml; charset=utf-8');
        echo a_xml($raiz, $datos);
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo a_json([$raiz => $datos]);
    }
    exit;
}

/**
 * Lee un argumento venga de donde venga:
 *   1) cuerpo JSON  (Content-Type: application/json)
 *   2) POST de formulario
 *   3) query string (?destino=02001&formato=json)
 */
function parametro(string $nombre, $defecto = null)
{
    static $cuerpo_json = null;

    if ($cuerpo_json === null) {
        $cuerpo_json = [];
        $tipo = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($tipo, 'application/json') !== false) {
            $crudo = file_get_contents('php://input');
            if (trim($crudo) !== '') {
                try {
                    $leido = desde_json($crudo);
                    $cuerpo_json = is_array($leido) ? $leido : [];
                } catch (InvalidArgumentException $e) {
                    responder('error', ['mensaje' => $e->getMessage()], 'json', 400);
                }
            }
        }
    }

    $valor = $cuerpo_json[$nombre] ?? $_POST[$nombre] ?? $_GET[$nombre] ?? $defecto;
    return is_string($valor) ? trim($valor) : $valor;
}
