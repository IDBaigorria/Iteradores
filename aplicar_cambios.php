<?php
/**
 * Aplicador — plan_actual.md: §1.13 (cierre del arco del plugin).
 *
 * Solo documentacion. No bumpea @version.
 *
 * Uso (raiz del proyecto piloto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: agregar §1.13',
        'buscar' => [
            '**Próxima tanda sugerida:** depende del paréntesis',
            'urgente del usuario. Al volver, elegir entre la',
            'reorganización del plugin o el siguiente punto de',
            '§1.11.',
            '',
            '---',
            '',
            '## 2. PLAN DE LA LÍNEA DE TANDAS',
        ],
        'reemplazar' => [
            '**Próxima tanda sugerida:** depende del paréntesis',
            'urgente del usuario. Al volver, elegir entre la',
            'reorganización del plugin o el siguiente punto de',
            '§1.11.',
            '',
            '### 1.13 Cierre del arco del plugin (interfaz de usuario)',
            '',
            '**Estado:** cerrada entre v1.5piloto.80h-80i (piloto) y',
            'v1.5plugin.7p-7x (plugin).',
            '',
            '**Motivación.** Después de la Fase 2c, el plugin tenía',
            'una lista sábana con 66 pruebas agrupadas en secciones,',
            'y los botones de limpieza vivían en el piloto. Este arco',
            'reorganizó la UI del plugin y agregó herramientas',
            'de uso manual (copiar tabla, limpiar por pestaña).',
            '',
            '**Qué se hizo.**',
            '',
            '- **v1.5piloto.80f:** ampliar los prefijos que',
            '  reconocen los limpiadores del piloto (`viajelimpia`,',
            '  `viajemicrograf`, `viajetermgraf`, `viajeparadgraf`,',
            '  `viajeparahora`, `viajeborrar`, `viajedesaut`,',
            '  `viajemicrocomp`, `emprevgraf`, `vehgraf`).',
            '',
            '- **v1.5plugin.7p:** nuevo botón "Copiar tabla de',
            '  pasajeros". Lee `viaje_seleccionado` y',
            '  `micro_seleccionado` del page, pide',
            '  `viajes/estado_asientos`, arma una tabla TSV con',
            '  columnas (nombre, apellido, DNI, documento, género,',
            '  franja etaria). Género por heurística (terminación',
            '  en `a`/`o`). Franja etaria calculada desde',
            '  `fecha_nacimiento` contra la fecha de hoy.',
            '  Franjas: 0-15, 16-17, 18-29, 30-44, 45-59, 60-74, 75+.',
            '',
            '- **v1.5piloto.80h:** al arrancar la app (login o',
            '  validación de token guardado), si el usuario es',
            '  terminal, se reinicia su `venta_actual` colgada.',
            '  Cubre el caso "usuario cierra el navegador a mitad',
            '  de una venta". Nuevo helper',
            '  `_reiniciar_seleccion_si_terminal` en `aplicacion.js`.',
            '',
            '- **v1.5plugin.7r:** nueva prueba 66',
            '  `reiniciar_seleccion_en_login`. Verifica el fix',
            '  anterior: login terminal limpia la selección previa.',
            '',
            '- **v1.5plugin.7s-7v:** rediseño de la ventana del',
            '  plugin. De lista sábana a pestañas temáticas que',
            '  siguen la estructura de la app (Base, Administrador,',
            '  Puntos de venta, Empresas/Micros, Viajes, Ventas,',
            '  Rendiciones, Liquidaciones, Pasajeros, Grafo).',
            '  Dentro de cada pestaña, tarjetas agrupadas por rol',
            '  (Admin, Dueño, Terminal, Soporte, Multi-rol) en una',
            '  grilla de 2 columnas. Persistencia de la pestaña',
            '  activa en `localStorage`.',
            '',
            '- **v1.5plugin.7w:** botones por pestaña: "Ejecutar',
            '  todos los roles" (corre todas las pruebas de la',
            '  pestaña) y "Limpiar pruebas" (limpia solo lo',
            '  relevante a la pestaña: Viajes limpia viajes,',
            '  Ventas limpia ventas+cancelaciones, etc.).',
            '  `LimpiezaPrueba.js` v2 acepta `{categorias}`.',
            '',
            '- **v1.5plugin.7x:** botón "Limpiar todas las pruebas"',
            '  arriba, en paralelo con "Copiar tabla de pasajeros".',
            '  Hace lo mismo que el viejo botón de limpieza',
            '  completo.',
            '',
            '**Resultado.** La ventana del plugin ahora tiene:',
            '',
            '- Barra global con 2 botones: Limpiar todo + Copiar tabla.',
            '- Pestañas temáticas (10 en total).',
            '- Cada pestaña con: Ejecutar todos los roles +',
            '  Limpiar pruebas (específico de la pestaña).',
            '- Tarjetas por rol dentro de cada pestaña.',
            '',
            '**Lecciones aprendidas.**',
            '',
            '6. **La UI del plugin creció más de lo previsto.**',
            '   Pasó de una lista plana a pestañas + grupos +',
            '   botones contextuales. Es una señal de que el plugin',
            '   empieza a ser una herramienta en sí misma, no solo',
            '   un runner de pruebas.',
            '7. **La limpieza por categorías reusa el mismo endpoint**',
            '   que la limpieza total. Lo único que cambia es el',
            '   subconjunto de categorías que se pasan.',
            '8. **La prueba 66 cierra el caso real "usuario cierra',
            '   el navegador a mitad de venta".** Antes el usuario',
            '   volvía y los asientos seguían en estado',
            '   "seleccionado".',
            '',
            '**Pendientes que abre este cierre.**',
            '',
            '- **Batería de pruebas exhaustivas.** Falta correr la',
            '  suite completa del plugin (66 pruebas) después de',
            '  todos los cambios de UI y del fix del reinicio.',
            '  Es el último paso para cerrar formalmente el plan',
            '  del plugin. Se hace más adelante.',
            '- **Persistencia de la pestaña activa:** migrar de',
            '  `localStorage` a la persistencia del framework.',
            '  Anotado en el código como `ANOTADO`.',
            '- **Secciones vacías** (Rendiciones, Liquidaciones):',
            '  quedan a la espera de pruebas nuevas.',
            '- **Pruebas 37 y 38 sin `try/finally`** (heredado de',
            '  §1.12).',
            '- El resto de los pendientes de §1.11 y §1.12 sigue',
            '  vigente.',
            '',
            '**Próxima tanda sugerida:** correr la batería completa',
            'de pruebas. Después, elegir el siguiente frente de',
            '§1.11 (adaptar módulos menores, auditar estructuras',
            'nuevas, B3.3c).',
            '',
            '---',
            '',
            '## 2. PLAN DE LA LÍNEA DE TANDAS',
        ],
    ],

];

// ============================================================
// Runner v2
// ============================================================

echo "=== Aplicador ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) {
    $htmls_a_bumpear = ['aplicacion_GET.html'];
}
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $n = contar_ocurrencias($contenido, $buscar_str);
        if ($n === 0) { $bloques_fallidos[] = "$archivo_rel: no encontrado - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($n > 1) { $bloques_fallidos[] = "$archivo_rel: ambiguo ($n) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = [$contenido, $eol];
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

$archivos_escritos = 0;
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    list($contenido_unix, $eol) = $info;
    $contenido_final = normalizar_a_original($contenido_unix, $eol);
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    $archivos_escritos++;
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";