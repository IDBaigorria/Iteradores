<?php
/**
 * Detector de usos de _adyacente_en(..., true) — herramienta de auditoría.
 *
 * Fase 2 del plan de optimización del grafo (ver prompt del piloto).
 *
 * Reporta todos los lugares donde se llama a _adyacente_en con el
 * tercer argumento literalmente en true. Cada uno de esos lugares es
 * candidato a revisar: puede estar dejando un nodo huérfano si el
 * nodo reemplazado no tenía otras referencias entrantes.
 *
 * Es de solo lectura. No modifica nada.
 *
 * Uso:
 *   php miscelaneas/detectar_adyacente_en_true.php
 *
 * Si PHP no está en el PATH del sistema:
 *   C:\xampp\php\php.exe miscelaneas\detectar_adyacente_en_true.php
 *
 * Ajustar si hace falta:
 *   - $excluir_dirs: agregar la carpeta del framework si vive con
 *     otro nombre (hoy se excluyen "iteradores" e "iteradoresJS").
 *   - $extensiones: si querés escanear también .html u otros.
 */

// ============================================================
// Configuración
// ============================================================

$raiz = dirname(__DIR__);

$extensiones = ['php', 'js'];

// Carpetas que NO se escanean (comparación por nombre de carpeta).
$excluir_dirs = [
    '.git',
    'vendor',
    'node_modules',
    'uploads',
    'JSON',
    'iteradores',
    'iteradoresJS',
    // Si la carpeta del framework en el piloto tiene otro nombre,
    // agregarla acá.
];

// ============================================================
// Recolección de archivos
// ============================================================

echo "=== Detector de _adyacente_en(..., true) ===\n\n";
echo "Raíz: $raiz\n\n";

$archivos = [];

$directorio = new RecursiveDirectoryIterator(
    $raiz,
    RecursiveDirectoryIterator::SKIP_DOTS
);

$filtro = new RecursiveCallbackFilterIterator(
    $directorio,
    function ($current) use ($excluir_dirs) {
        if ($current->isDir()) {
            return !in_array($current->getFilename(), $excluir_dirs, true);
        }
        return true;
    }
);

$iter = new RecursiveIteratorIterator($filtro);

foreach ($iter as $archivo) {
    if (!$archivo->isFile()) {
        continue;
    }
    $ext = strtolower($archivo->getExtension());
    if (!in_array($ext, $extensiones, true)) {
        continue;
    }
    $archivos[] = $archivo->getPathname();
}

sort($archivos);

echo "Archivos escaneados: " . count($archivos) . "\n";

// ============================================================
// Funciones de parseo
// ============================================================

/**
 * Devuelve la posición del paréntesis de cierre que balancea al
 * paréntesis de apertura en $pos_abre. Devuelve null si no lo
 * encuentra (paréntesis desbalanceado).
 *
 * No maneja strings ni comentarios: para esta auditoría alcanza.
 */
function encontrar_cierre_parentesis(string $s, int $pos_abre): ?int {
    $nivel = 0;
    $len = strlen($s);
    for ($i = $pos_abre; $i < $len; $i++) {
        $c = $s[$i];
        if ($c === '(') {
            $nivel++;
        } elseif ($c === ')') {
            $nivel--;
            if ($nivel === 0) {
                return $i;
            }
        }
    }
    return null;
}

/**
 * Divide el cuerpo de una llamada (lo que va entre los paréntesis)
 * en sus argumentos, respetando paréntesis, corchetes y llaves
 * anidados.
 */
function dividir_argumentos(string $cuerpo): array {
    $args = [];
    $nivel = 0;
    $actual = '';
    $len = strlen($cuerpo);
    for ($i = 0; $i < $len; $i++) {
        $c = $cuerpo[$i];
        if ($c === '(' || $c === '[' || $c === '{') {
            $nivel++;
        } elseif ($c === ')' || $c === ']' || $c === '}') {
            $nivel--;
        }
        if ($c === ',' && $nivel === 0) {
            $args[] = trim($actual);
            $actual = '';
            continue;
        }
        $actual .= $c;
    }
    if (trim($actual) !== '') {
        $args[] = trim($actual);
    }
    return $args;
}

/**
 * Busca todas las llamadas a $nombre_func en $contenido.
 * Devuelve un array de ['pos' => int, 'linea' => int, 'args' => array, 'snippet' => string].
 */
function encontrar_llamadas(string $contenido, string $nombre_func): array {
    $llamadas = [];
    $len = strlen($contenido);
    $offset = 0;
    $patron_len = strlen($nombre_func);

    while (($pos = strpos($contenido, $nombre_func, $offset)) !== false) {
        // Verificar que no sea parte de otro identificador.
        if ($pos > 0) {
            $antes = $contenido[$pos - 1];
            if (ctype_alnum($antes) || $antes === '_') {
                $offset = $pos + 1;
                continue;
            }
        }
        // Verificar que el siguiente carácter sea '('.
        $pos_paren = $pos + $patron_len;
        if (!isset($contenido[$pos_paren]) || $contenido[$pos_paren] !== '(') {
            $offset = $pos + 1;
            continue;
        }
        $cierre = encontrar_cierre_parentesis($contenido, $pos_paren);
        if ($cierre === null) {
            $offset = $pos + 1;
            continue;
        }
        $cuerpo = substr($contenido, $pos_paren + 1, $cierre - $pos_paren - 1);
        $args = dividir_argumentos($cuerpo);
        $linea = substr_count(substr($contenido, 0, $pos), "\n") + 1;
        $llamadas[] = [
            'pos' => $pos,
            'linea' => $linea,
            'args' => $args,
            'cuerpo' => $cuerpo,
        ];
        $offset = $cierre + 1;
    }
    return $llamadas;
}

/**
 * Arma un snippet legible de la llamada, colapsando espacios y
 * saltos de línea, cortado a un ancho razonable.
 */
function armar_snippet(string $nombre_func, string $cuerpo, int $max = 120): string {
    $plano = preg_replace('/\s+/', ' ', $cuerpo);
    $plano = trim($plano);
    $texto = $nombre_func . '(' . $plano . ')';
    if (strlen($texto) > $max) {
        $texto = substr($texto, 0, $max - 3) . '...';
    }
    return $texto;
}

// ============================================================
// Análisis
// ============================================================

$resultados_por_archivo = [];
$total_llamadas = 0;
$total_con_true = 0;
$total_con_3_args = 0;

foreach ($archivos as $ruta_abs) {
    $contenido = file_get_contents($ruta_abs);
    if ($contenido === false) {
        continue;
    }
    $llamadas = encontrar_llamadas($contenido, '_adyacente_en');
    if (empty($llamadas)) {
        continue;
    }

    $ruta_rel = substr($ruta_abs, strlen($raiz) + 1);
    $ruta_rel = str_replace('\\', '/', $ruta_rel);

    $entradas = [];
    foreach ($llamadas as $ll) {
        $total_llamadas++;
        $n = count($ll['args']);
        $tercero = $n >= 3 ? $ll['args'][2] : '';
        $es_true = false;
        if ($n >= 3) {
            $total_con_3_args++;
            $t = strtolower(trim($tercero));
            if ($t === 'true') {
                $es_true = true;
                $total_con_true++;
            }
        }
        $entradas[] = [
            'linea' => $ll['linea'],
            'n_args' => $n,
            'tercero' => $tercero,
            'es_true' => $es_true,
            'snippet' => armar_snippet('_adyacente_en', $ll['cuerpo']),
        ];
    }
    $resultados_por_archivo[$ruta_rel] = $entradas;
}

// ============================================================
// Salida
// ============================================================

echo "Llamadas a _adyacente_en: $total_llamadas\n";
echo "  con 3 argumentos:        $total_con_3_args\n";
echo "  con tercer arg == true:  $total_con_true\n\n";

if ($total_con_true === 0) {
    echo "No se encontraron usos de _adyacente_en(..., true).\n";
    exit(0);
}

echo "=== Lugares con tercer argumento en true ===\n\n";

foreach ($resultados_por_archivo as $ruta_rel => $entradas) {
    $con_true = array_filter($entradas, function ($e) { return $e['es_true']; });
    if (empty($con_true)) {
        continue;
    }
    echo "--- $ruta_rel ---\n";
    foreach ($con_true as $e) {
        echo "  Línea {$e['linea']}: {$e['snippet']}\n";
    }
    echo "\n";
}

// ============================================================
// Anexo: contexto (3 args pero no true)
// ============================================================

$hay_contexto = false;
foreach ($resultados_por_archivo as $ruta_rel => $entradas) {
    foreach ($entradas as $e) {
        if ($e['n_args'] >= 3 && !$e['es_true']) {
            $hay_contexto = true;
            break 2;
        }
    }
}

if ($hay_contexto) {
    echo "=== Anexo: 3 argumentos pero el tercero NO es true ===\n";
    echo "(para descartar a mano; pueden ser variables o false explícito)\n\n";
    foreach ($resultados_por_archivo as $ruta_rel => $entradas) {
        $otras = array_filter($entradas, function ($e) {
            return $e['n_args'] >= 3 && !$e['es_true'];
        });
        if (empty($otras)) {
            continue;
        }
        echo "--- $ruta_rel ---\n";
        foreach ($otras as $e) {
            echo "  Línea {$e['linea']}: {$e['snippet']}\n";
            echo "    (tercer arg: {$e['tercero']})\n";
        }
        echo "\n";
    }
}

echo "Listo.\n";