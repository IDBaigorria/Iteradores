<?php
/**
 * Detector de botones/elementos con id en HTML sin referencia en JS.
 *
 * Herramienta de auditoría. Para cada elemento con id en los .html
 * del proyecto, busca referencias en los .js. Reporta los que no
 * aparecen referenciados en ningún archivo JS.
 *
 * Sirve para detectar código muerto del lado del cliente: botones
 * y campos que existen en el HTML pero que ningún listener usa.
 *
 * Es heurístico: un id puede estar referenciado de forma indirecta
 * (ej. construido por string concatenation) y el script no lo va a
 * ver. El criterio es: si el ID no aparece literal en ningún .js,
 * probablemente no tenga listener.
 *
 * Excluye framework, plugin, vendor, uploads, JSON.
 *
 * Es de solo lectura. No modifica nada.
 *
 * Uso:
 *   php miscelaneas/detectar_botones_sin_listener.php
 */

// ============================================================
// Configuración
// ============================================================

$raiz = dirname(__DIR__);

$excluir_dirs = [
    '.git', 'vendor', 'node_modules', 'uploads', 'JSON',
    'iteradores', 'iteradoresJS',
    // Framework PHP:
    'Nodos', 'Iteradores', 'Controlador', 'Persistencia',
    'Configuracion', 'Comandos', 'Comunicadores', 'Nucleo',
    'Tiempo', 'Pruebas', 'miscelaneas',
];

// ============================================================
// Recolección de archivos
// ============================================================

echo "=== Detector de botones sin listener ===\n\n";
echo "Raíz: $raiz\n\n";

$archivos_html = [];
$archivos_js = [];

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
    if ($ext === 'html') $archivos_html[] = $archivo->getPathname();
    elseif ($ext === 'js') $archivos_js[] = $archivo->getPathname();
}
sort($archivos_html);
sort($archivos_js);

echo "Archivos HTML: " . count($archivos_html) . "\n";
echo "Archivos JS:   " . count($archivos_js) . "\n\n";

// ============================================================
// Paso 1: extraer IDs del HTML
// ============================================================

// Mapa: id => ['archivo' => ..., 'linea' => ..., 'tag' => ..., 'tiene_onclick_inline' => bool]
$ids_html = [];

foreach ($archivos_html as $ruta_abs) {
    $contenido = file_get_contents($ruta_abs);
    if ($contenido === false) continue;

    $lineas = explode("\n", $contenido);
    $ruta_rel = substr($ruta_abs, strlen($raiz) + 1);
    $ruta_rel = str_replace('\\', '/', $ruta_rel);

    // Buscar todos los <tag ... id="X" ...>
    // Cubre: <button id="X">, <input id="X">, <select id="X">, <div id="X">, etc.
    if (preg_match_all('/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*\bid=(["\'])([^"\']+)\2[^>]*>/s', $contenido, $matches, PREG_OFFSET_CAPTURE)) {
        for ($i = 0; $i < count($matches[0]); $i++) {
            $tag = $matches[1][$i][0];
            $id = $matches[3][$i][0];
            $pos = $matches[0][$i][1];
            $linea = substr_count(substr($contenido, 0, $pos), "\n") + 1;
            $tag_completo = $matches[0][$i][0];

            // ¿Tiene onclick inline?
            $tiene_onclick_inline = (stripos($tag_completo, 'onclick') !== false);

            // Si el mismo ID aparece varias veces (raro), conservamos el primero.
            if (!isset($ids_html[$id])) {
                $ids_html[$id] = [
                    'archivo' => $ruta_rel,
                    'linea' => $linea,
                    'tag' => $tag,
                    'onclick_inline' => $tiene_onclick_inline,
                ];
            }
        }
    }
}

echo "IDs únicos encontrados en HTML: " . count($ids_html) . "\n\n";

// ============================================================
// Paso 2: extraer referencias del JS
// ============================================================

// Set de IDs referenciados en algún JS.
$ids_referenciados = [];

foreach ($archivos_js as $ruta_abs) {
    $contenido = file_get_contents($ruta_abs);
    if ($contenido === false) continue;

    // 1. getElementById("X") / getElementById('X')
    if (preg_match_all('/getElementById\s*\(\s*(["\'])([^"\']+)\1\s*\)/', $contenido, $m)) {
        foreach ($m[2] as $id) $ids_referenciados[$id] = true;
    }

    // 2. querySelector("#X") / querySelectorAll("#X")
    //    Cubre también $("#X") y $$("#X") porque matchea el querySelector interno.
    if (preg_match_all('/querySelector(?:All)?\s*\(\s*(["\'])#([^"\']+)\1\s*\)/', $contenido, $m)) {
        foreach ($m[2] as $id) $ids_referenciados[$id] = true;
    }

    // 3. $ ( "#X" ) — patrón típico de jQuery / nuestro helper
    if (preg_match_all('/\$+\s*\(\s*(["\'])#([^"\']+)\1\s*\)/', $contenido, $m)) {
        foreach ($m[2] as $id) $ids_referenciados[$id] = true;
    }

    // 4. [id="X"] — selector de atributo
    if (preg_match_all('/\[\s*id\s*=\s*(["\'])([^"\']+)\1\s*\]/', $contenido, $m)) {
        foreach ($m[2] as $id) $ids_referenciados[$id] = true;
    }
}

echo "IDs referenciados en JS: " . count($ids_referenciados) . "\n\n";

// ============================================================
// Paso 3: reportar huérfanos
// ============================================================

// IDs del HTML que no están referenciados en ningún JS.
// Excluimos los que tienen onclick inline en el HTML (esos sí tienen listener).

$huerfanos = [];
foreach ($ids_html as $id => $info) {
    if (isset($ids_referenciados[$id])) continue;
    if ($info['onclick_inline']) continue;
    $huerfanos[$id] = $info;
}

echo "=== IDs en HTML sin referencia en JS ===\n";
echo "(ni getElementById, ni querySelector #id, ni $(\"#id\"), ni [id=\"id\"].)\n";
echo "Se excluyen los que tienen onclick inline.\n\n";

if (empty($huerfanos)) {
    echo "No se encontraron huérfanos.\n";
    exit(0);
}

// Agrupar por archivo.
$por_archivo = [];
foreach ($huerfanos as $id => $info) {
    $por_archivo[$info['archivo']][] = ['id' => $id, 'linea' => $info['linea'], 'tag' => $info['tag']];
}
ksort($por_archivo);

foreach ($por_archivo as $archivo => $lista) {
    echo "--- $archivo ---\n";
    foreach ($lista as $item) {
        echo "  Línea {$item['linea']}: <{$item['tag']} id=\"{$item['id']}\">\n";
    }
    echo "\n";
}

echo "Total: " . count($huerfanos) . " elementos sin referencia en JS.\n";
echo "\nListo.\n";