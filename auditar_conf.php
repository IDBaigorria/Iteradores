<?php
/**
 * Auditor de uso de constantes de Conf / ConfiguracionApli.
 *
 * Busca en los archivos .php y .js del proyecto dónde se usa cada
 * constante, para planificar qué se puede mover a ConfiguracionApli
 * sin romper nada.
 *
 * Uso:
 *   php auditar_conf.php                    # audita el directorio actual
 *   php auditar_conf.php ../iteradoresJS    # audita otro directorio
 *   php auditar_conf.php . ../iteradoresJS  # audita ambos
 *
 * No modifica nada. Solo lee y reporta.
 */

// ============================================================
// Configuración
// ============================================================

$rutas = array_slice($argv, 1);
if (empty($rutas)) {
    $rutas = [__DIR__];
}

// Constantes del PILOTO (candidatas a mudarse a ConfiguracionApli).
$constantes_piloto = [
    'NOMBRE_APP',
    'NOMBRE_APP_CREDENCIALES',
    'VERSION_APP',
    'AUTOR_APP',
    'PREFIJO_SESSION',
    'INTENTOS_MAXIMOS_AUTENTICACION',
    'BLOQUEO_AUTENTICACION_SEGUNDOS',
    'HASH_DUMMY_AUTENTICACION',
    'NOMBRE_ADMIN',
];

// Constantes del FRAMEWORK (deberían quedarse en Conf).
$constantes_framework = [
    'ACTIVAR_ERRORES',
    'ACTIVAR_ALERTAS',
    'ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE',
    'ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS',
    'ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS',
    'CAPACIDAD_NODO_ELECTRICO',
    'FUGA_NODO_ELECTRICO',
    'TIEMPO_CICLO',
    'ERRORES_COLORES',
    'ALERTAS_COLORES',
    'NODOS_COLORES',
    'LATITUD_PREDETERMINADA',
    'LONGITUD_PREDETERMINADA',
    'GEOLOCALIZACION_URL',
    'MOTOR_MAX_CICLOS',
    'MOTOR_CICLOS_POR_MINUTO',
    'MOTOR_INTERVALO_MS',
    'MOTOR_QUANTUM',
    'MOTOR_PAUSA_URGENTE_TIMEOUT_S',
    'MATRIZ_MARCA_CONJUNTO',
    'PRIMOS_PRECARGADOS',
    'VERBO_CIERRE',
    'VERBO_APRENDER',
    'VERBO_EJECUTAR',
    'VERBO_CONTROLAR',
    'VERBO_CORREGIR',
    'VERBO_PREDECIR',
    'VERBO_IMAGINAR',
    'VERBO_SUPERVISAR',
    'SUPERESTRUCTURA_METODO_PERDURAR',
    'SUPERESTRUCTURA_CARPETA_GUARDAR_JSON',
    'SUPERESTRUCTURA_CARPETA_GUARDAR_XML',
];

$extensiones   = ['php', 'js'];
$excluir_dirs  = ['.git', 'node_modules', 'vendor', 'JSON', 'XML', 'uploads', '.vscode', 'Pruebas', 'tests'];

// ============================================================
// Utilidades
// ============================================================

function recorrer(string $dir, array $excluir, array $exts): array {
    $archivos = [];
    $items = @scandir($dir);
    if ($items === false) return [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $ruta = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($ruta)) {
            if (in_array($item, $excluir, true)) continue;
            $archivos = array_merge($archivos, recorrer($ruta, $excluir, $exts));
        } else {
            $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
            if (in_array($ext, $exts, true)) {
                $archivos[] = $ruta;
            }
        }
    }
    return $archivos;
}

function linea_matchea(string $linea, string $constante): bool {
    // Word boundary real: la constante no puede estar rodeada de
    // [A-Za-z0-9_]. Esto evita que NOMBRE_APP matchee dentro de
    // NOMBRE_APP_CREDENCIALES.
    $patron = '/(?<![A-Za-z0-9_])' . preg_quote($constante, '/') . '(?![A-Za-z0-9_])/';
    return (bool) preg_match($patron, $linea);
}

// ============================================================
// Auditoría
// ============================================================

function auditar(string $raiz, array $grupos, array $excluir, array $exts): void {
    $raiz_real = realpath($raiz) ?: $raiz;

    echo "\n";
    echo "============================================================\n";
    echo " AUDITANDO: $raiz_real\n";
    echo "============================================================\n";

    if (!is_dir($raiz_real)) {
        echo "[FALLO] No es un directorio: $raiz_real\n";
        return;
    }

    $archivos = recorrer($raiz_real, $excluir, $exts);
    echo "[INFO] " . count($archivos) . " archivo(s) .php/.js a analizar.\n";

    $resultados = [];
    foreach ($grupos as $grupo => $lista) {
        foreach ($lista as $c) {
            $resultados[$c] = ['grupo' => $grupo, 'hits' => []];
        }
    }

    foreach ($archivos as $archivo) {
        $contenido = @file_get_contents($archivo);
        if ($contenido === false) continue;
        $lineas = explode("\n", $contenido);
        $ruta_rel = substr($archivo, strlen($raiz_real) + 1);

        foreach ($resultados as $c => $datos) {
            foreach ($lineas as $n => $linea) {
                if (linea_matchea($linea, $c)) {
                    $resultados[$c]['hits'][] = [
                        'archivo' => $ruta_rel,
                        'linea'   => $n + 1,
                        'texto'   => trim($linea),
                    ];
                }
            }
        }
    }

    // Resumen: constante → cantidad de archivos distintos / total usos
    echo "\n------------------------------------------------------------\n";
    echo " RESUMEN\n";
    echo "------------------------------------------------------------\n";

    $por_grupo = ['PILOTO' => [], 'FRAMEWORK' => []];

    foreach ($resultados as $c => $datos) {
        $archivos_distintos = [];
        foreach ($datos['hits'] as $h) {
            $archivos_distintos[$h['archivo']] = true;
        }
        $cant_arch = count($archivos_distintos);
        $cant_hits = count($datos['hits']);
        $por_grupo[$datos['grupo']][$c] = [
            'archivos' => $cant_arch,
            'hits'     => $cant_hits,
            'lista_archivos' => array_keys($archivos_distintos),
        ];
    }

    foreach (['PILOTO', 'FRAMEWORK'] as $grupo) {
        echo "\n  === Grupo $grupo ===\n";
        foreach ($por_grupo[$grupo] as $c => $info) {
            $marca = $info['hits'] === 0 ? '  ' : '->';
            printf("  %s %-50s  %d archivo(s)  %d uso(s)\n",
                $marca, $c, $info['archivos'], $info['hits']);
        }
    }

    // Detalle: solo constantes con usos
    echo "\n------------------------------------------------------------\n";
    echo " DETALLE\n";
    echo "------------------------------------------------------------\n";

    foreach ($resultados as $c => $datos) {
        if (empty($datos['hits'])) continue;
        echo "\n  [$c] ({$datos['grupo']}) — " . count($datos['hits']) . " uso(s):\n";
        foreach ($datos['hits'] as $h) {
            echo "      {$h['archivo']}:{$h['linea']}\n";
            echo "          {$h['texto']}\n";
        }
    }

    echo "\n  (Constantes sin usos fuera del archivo de definición no se listan.)\n";
}

// ============================================================
// Main
// ============================================================

echo "=== Auditor de constantes Conf / ConfiguracionApli ===\n";

$grupos = [
    'PILOTO'    => $constantes_piloto,
    'FRAMEWORK' => $constantes_framework,
];

foreach ($rutas as $ruta) {
    auditar($ruta, $grupos, $excluir_dirs, $extensiones);
}

echo "\n=== FIN ===\n";