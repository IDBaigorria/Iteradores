<?php
/**
 * Detector ampliado de fugas potenciales — Fase 2 del plan de optimización.
 *
 * Busca llamadas a:
 *   - eliminar_adyacente(...)
 *   - eliminar_hmi(...)
 *   - eliminar_hd(...)
 *
 * que NO estén seguidas, en las próximas N líneas, de:
 *   - Nodo::eliminar(...)
 *   - _destruir_*(...)
 *
 * Es heurístico: da candidatos a revisar, no veredictos. Un
 * eliminar_adyacente('empresa') sobre un micro es correcto sin
 * destrucción (la empresa no se destruye con el micro).
 *
 * Solo audita el piloto (Aplicacion/ y raíz). Excluye el framework
 * PHP, sus tests, y miscelaneas/.
 *
 * Es de solo lectura. No modifica nada.
 *
 * Uso:
 *   php miscelaneas/detectar_fugas_eliminar.php
 */

// ============================================================
// Configuración
// ============================================================

$raiz = dirname(__DIR__);
$extensiones = ['php'];

$excluir_dirs = [
    '.git', 'vendor', 'node_modules', 'uploads', 'JSON',
    'iteradores', 'iteradoresJS',
    // Framework PHP:
    'Nodos', 'Iteradores', 'Controlador', 'Persistencia',
    'Configuracion', 'Comandos', 'Comunicadores', 'Nucleo',
    'Tiempo', 'Pruebas', 'miscelaneas',
];

$ventana_destruccion = 10; // líneas siguientes a mirar

$nombres_buscados = ['eliminar_adyacente', 'eliminar_hmi', 'eliminar_hd'];

// ============================================================
// Funciones auxiliares
// ============================================================

function encontrar_cierre_parentesis(string $s, int $pos_abre): ?int {
    $nivel = 0;
    $len = strlen($s);
    for ($i = $pos_abre; $i < $len; $i++) {
        $c = $s[$i];
        if ($c === '(') $nivel++;
        elseif ($c === ')') {
            $nivel--;
            if ($nivel === 0) return $i;
        }
    }
    return null;
}

function posicion_en_comentario(string $linea, int $pos): bool {
    $antes = substr($linea, 0, $pos);
    if (preg_match('/(\/\/|#)/', $antes)) return true;
    $trim = ltrim($linea);
    if (strpos($trim, '*') === 0) return true;
    return false;
}

function encontrar_llamadas(string $contenido, array $nombres): array {
    $llamadas = [];
    $len = strlen($contenido);

    foreach ($nombres as $nombre) {
        $patron = $nombre . '(';
        $offset = 0;

        while (($pos = strpos($contenido, $patron, $offset)) !== false) {
            if ($pos > 0) {
                $antes = $contenido[$pos - 1];
                if (ctype_alnum($antes) || $antes === '_') {
                    $offset = $pos + 1;
                    continue;
                }
            }

            $pos_paren = $pos + strlen($nombre);
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
            $linea = substr_count(substr($contenido, 0, $pos), "\n") + 1;

            $inicio_linea = strrpos(substr($contenido, 0, $pos), "\n");
            $inicio_linea = ($inicio_linea === false) ? 0 : $inicio_linea + 1;
            $fin_linea = strpos($contenido, "\n", $pos);
            if ($fin_linea === false) $fin_linea = $len;
            $linea_contenido = substr($contenido, $inicio_linea, $fin_linea - $inicio_linea);

            $col_match = $pos - $inicio_linea;
            if (posicion_en_comentario($linea_contenido, $col_match)) {
                $offset = $cierre + 1;
                continue;
            }

            // Expresión receptora (lo que está antes del nombre).
            $expr_inicio = $pos - 1;
            while ($expr_inicio > 0) {
                $c = $contenido[$expr_inicio];
                if (ctype_space($c) || $c === "\n" || $c === ';' || $c === '{' || $c === '}') {
                    break;
                }
                $expr_inicio--;
            }
            $receptor = trim(substr($contenido, $expr_inicio + 1, $pos - $expr_inicio - 1));
            if (strlen($receptor) > 80) $receptor = substr($receptor, -80);

            $llamadas[] = [
                'nombre' => $nombre,
                'pos' => $pos,
                'linea' => $linea,
                'receptor' => $receptor,
                'cuerpo' => $cuerpo,
                'linea_completa' => $linea_contenido,
            ];

            $offset = $cierre + 1;
        }
    }

    return $llamadas;
}

function hay_destruccion_posterior(array $lineas, int $linea_match_1indexed, int $ventana): bool {
    $idx = $linea_match_1indexed - 1; // 0-indexed
    $fin = min(count($lineas), $idx + $ventana);
    for ($i = $idx; $i < $fin; $i++) {
        $l = $lineas[$i];
        $trim = ltrim($l);
        if (strpos($trim, '*') === 0 || strpos($trim, '//') === 0 || strpos($trim, '#') === 0) continue;
        if (strpos($l, 'Nodo::eliminar(') !== false) return true;
        if (preg_match('/\b_destruir_\w+\s*\(/', $l)) return true;
    }
    return false;
}

function encontrar_funcion_contenedora(array $lineas, int $linea_actual): string {
    for ($i = $linea_actual - 1; $i >= 0 && $i >= $linea_actual - 800; $i--) {
        if (preg_match('/^\s*(?:public|private|protected|static|final|abstract|\s)*function\s+(\w+)\s*\(/', $lineas[$i], $m)) {
            return $m[1];
        }
    }
    return '(top-level)';
}

// ============================================================
// Recolección de archivos
// ============================================================

echo "=== Detector de fugas potenciales (Fase 2) ===\n\n";
echo "Raíz: $raiz\n\n";

$archivos = [];
$directorio = new RecursiveDirectoryIterator($raiz, RecursiveDirectoryIterator::SKIP_DOTS);
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
    if (!$archivo->isFile()) continue;
    $ext = strtolower($archivo->getExtension());
    if (!in_array($ext, $extensiones, true)) continue;
    $archivos[] = $archivo->getPathname();
}
sort($archivos);

echo "Archivos escaneados: " . count($archivos) . "\n\n";

// ============================================================
// Análisis
// ============================================================

$candidatos = [];
$total_llamadas = 0;
$total_con_destruccion = 0;

foreach ($archivos as $ruta_abs) {
    $contenido = file_get_contents($ruta_abs);
    if ($contenido === false) continue;

    $llamadas = encontrar_llamadas($contenido, $nombres_buscados);
    if (empty($llamadas)) continue;

    $lineas = explode("\n", $contenido);
    $ruta_rel = substr($ruta_abs, strlen($raiz) + 1);
    $ruta_rel = str_replace('\\', '/', $ruta_rel);

    foreach ($llamadas as $ll) {
        $total_llamadas++;

        if (hay_destruccion_posterior($lineas, $ll['linea'], $ventana_destruccion)) {
            $total_con_destruccion++;
            continue;
        }

        // Clasificar.
        $es_de_arbol = in_array($ll['nombre'], ['eliminar_hmi', 'eliminar_hd'], true);
        $retorno_usado = (strpos($ll['linea_completa'], '=') !== false);
        $nota = ($es_de_arbol && !$retorno_usado) ? 'PROBABLE FUGA' : 'REVISAR';

        $funcion = encontrar_funcion_contenedora($lineas, $ll['linea']);

        $ini_ctx = max(0, $ll['linea'] - 4);
        $fin_ctx = min(count($lineas), $ll['linea'] + 6);
        $contexto = [];
        for ($i = $ini_ctx; $i < $fin_ctx; $i++) {
            $marca = ($i + 1 === $ll['linea']) ? ' >>> ' : '     ';
            $contexto[] = $marca . ($i + 1) . ': ' . rtrim($lineas[$i]);
        }

        $candidatos[$ruta_rel][] = [
            'linea' => $ll['linea'],
            'nombre' => $ll['nombre'],
            'receptor' => $ll['receptor'],
            'cuerpo' => $ll['cuerpo'],
            'funcion' => $funcion,
            'nota' => $nota,
            'contexto' => $contexto,
        ];
    }
}

// ============================================================
// Reporte
// ============================================================

echo "Llamadas totales (eliminar_adyacente + eliminar_hmi + eliminar_hd): $total_llamadas\n";
echo "  con destrucción posterior detectada: $total_con_destruccion\n";

$total_candidatos = 0;
foreach ($candidatos as $l) $total_candidatos += count($l);
echo "  candidatos: $total_candidatos\n\n";

if ($total_candidatos === 0) {
    echo "No se encontraron candidatos.\n";
    exit(0);
}

// Ordenar: primero las PROBABLE FUGA, después las REVISAR.
foreach ($candidatos as $archivo => &$lista) {
    usort($lista, function ($a, $b) {
        $orden = ['PROBABLE FUGA' => 0, 'REVISAR' => 1];
        $oa = $orden[$a['nota']] ?? 2;
        $ob = $orden[$b['nota']] ?? 2;
        if ($oa !== $ob) return $oa - $ob;
        return $a['linea'] - $b['linea'];
    });
}
unset($lista);

foreach ($candidatos as $archivo => $lista) {
    echo "=== $archivo ===\n\n";
    foreach ($lista as $c) {
        echo "  [{$c['nota']}] {$c['funcion']}() — línea {$c['linea']}\n";
        echo "    {$c['receptor']}->{$c['nombre']}({$c['cuerpo']})\n\n";
        foreach ($c['contexto'] as $linea) {
            echo "  $linea\n";
        }
        echo "\n";
    }
}

echo "=== Resumen ===\n";
foreach ($candidatos as $archivo => $lista) {
    $probables = 0;
    $revisar = 0;
    foreach ($lista as $c) {
        if ($c['nota'] === 'PROBABLE FUGA') $probables++;
        else $revisar++;
    }
    echo "  $archivo: $probables probable fuga, $revisar a revisar\n";
}
echo "\nListo.\n";