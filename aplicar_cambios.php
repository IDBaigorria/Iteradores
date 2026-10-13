<?php
/**
 * Aplicador de cambios — Prompts del proyecto PHP.
 *
 * Cierre del bug §1.5 (RESUELTO en v78v).
 * Actualiza plan_actual.md, prompt_piloto.md y
 * prompt_framework_iteradores.md.
 *
 * Uso (raíz del proyecto PHP):
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [
    // ============================================================
    // plan_actual.md — §1.1 encabezado
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.1: bug resuelto, próximo paso pruebas por rol',
        'buscar' => [
            'Cierre de la línea de carga parcial (v78b-v78s). Toda la',
            'infraestructura de contextos está aplicada: SQL64 como',
            'método de persistencia, `guardar_parcial` en PHP,',
            '`Controlador::$contextos_cargados`, `_contextos_para_operacion`',
            'en el Enrutador, adaptación del contexto del solicitante en',
            'los módulos principales (Viajes, Venta, Pasajeros,',
            'Impresión) y reparación topológica.',
            '',
            '**BUG PERSISTENTE ACTIVO (§1.5).** El bug de "el viaje viejo',
            'desaparece cuando el terminal hace una operación" **no está',
            'resuelto**. Los fixes v78r (regla de borrado de aristas) y',
            'v78s (preservar bits de máscara) no alcanzaron. Se sigue',
            'investigando. Ver §1.5 para el diagnóstico completo y las',
            'hipótesis descartadas.',
            '',
            'Próximo paso sugerido: retomar el bug §1.5 con logs de',
            'diagnóstico en el `_guardar_con_filtro` (qué máscaras tenía',
            'cada nodo antes y después de cada guardado parcial del',
            'terminal). Ver §1.5.4.',
        ],
        'reemplazar' => [
            'Cierre de la línea de carga parcial (v78b-v78v). Toda la',
            'infraestructura de contextos está aplicada: SQL64 como',
            'método de persistencia (con el esquema `id_fila` de v78v),',
            '`guardar_parcial` en PHP, `Controlador::$contextos_cargados`,',
            '`_contextos_para_operacion` en el Enrutador (neutralizado',
            'en v78v), adaptación del contexto del solicitante en los',
            'módulos principales (Viajes, Venta, Pasajeros, Impresión)',
            'y reparación topológica.',
            '',
            '**BUG §1.5 RESUELTO en v78v.** El viaje viejo ya no',
            'desaparece tras la operación del terminal. Causa raíz:',
            'los IDs numéricos del framework son efímeros, y el',
            'guardado parcial intentaba mapear "ID del framework" ↔',
            '"fila de la DB" entre requests. Solución: la DB ahora',
            'usa `id_fila` (estable) como identidad de los nodos y un',
            'mapa estático resuelve la correspondencia al guardar',
            'parcial. Ver §1.5.6.',
            '',
            'Próximo paso sugerido: batería completa de pruebas con el',
            'plugin, por rol. Ver §2.3.',
        ],
    ],

    // ============================================================
    // plan_actual.md — §1.5 título
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.5: marcar como RESUELTO',
        'buscar' => [
            '### 1.5 BUG PERSISTENTE — El viaje viejo desaparece',
        ],
        'reemplazar' => [
            '### 1.5 BUG RESUELTO — El viaje viejo desaparecía',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.5: nota de cierre',
        'buscar' => [
            '**RESUELTO en framework 1.5i.8a.** Ver §1.5.6 abajo.',
        ],
        'reemplazar' => [
            '**RESUELTO en framework 1.5i.8a/b (v1.5piloto.78v).**',
            'Ver §1.5.6 abajo. El resto de la sección queda como',
            'referencia histórica: síntoma, diagnóstico y fixes que',
            'no alcanzaron.',
        ],
    ],

    // ============================================================
    // plan_actual.md — §1.6 pendientes
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.6: pendientes actualizados',
        'buscar' => [
            '### 1.6 Pendientes de la línea',
            '',
            '- **BUG §1.5.** Bloqueante funcional. Requiere logs de',
            '  diagnóstico.',
            '- **Espejo JS del fix v78r.** `PerdurarSuperestructuraStringIndexedDB64.js`',
            '  tiene el mismo bug de borrado de aristas. Pendiente.',
            '- **Espejo JS del fix v78s.** Idem: preservar bits de máscara.',
            '  Pendiente.',
            '- **B3.3c** (7 helpers que iteran sobre todos los dueños).',
            '  Postergado hasta que aparezca dolor real o implementemos',
            '  iteradores con ID especial. Ver §1.10.',
            '- **Módulos menores sin contexto:** `Liquidacion.php`,',
            '  `Rendicion.php`, `Empresa.php`, `Vehiculo.php`. Mismo',
            '  patrón que los módulos ya adaptados. Sub-tandas cortas',
            '  cuando aparezcan errores.',
            '- **Chequeos defensivos revertidos.** Si aparecen otros',
            '  casos donde el chequeo ayuda, se reintroduce selectivo.',
        ],
        'reemplazar' => [
            '### 1.6 Pendientes de la línea',
            '',
            '- **Espejo JS del fix `id_fila`.** HECHO en',
            '  V1.5plugin.7a (framework JS V1.5i.8b).',
            '  `PerdurarSuperestructuraStringIndexedDB64.js`',
            '  reescrito con el mismo esquema y mismo mapa estático.',
            '- **Reactivar `_contextos_para_operacion`.** Está',
            '  neutralizado (devuelve `null`) desde v78v. Reactivar',
            '  cuando la batería completa de pruebas pase en verde.',
            '- **Batería completa de pruebas con el plugin, por rol.**',
            '  Ver la idea de las pestañas del plugin más abajo.',
            '- **B3.3c** (7 helpers que iteran sobre todos los dueños).',
            '  Postergado hasta que aparezca dolor real o implementemos',
            '  iteradores con ID especial. Ver §1.10.',
            '- **Módulos menores sin contexto:** `Liquidacion.php`,',
            '  `Rendicion.php`, `Empresa.php`, `Vehiculo.php`. Mismo',
            '  patrón que los módulos ya adaptados. Sub-tandas cortas',
            '  cuando aparezcan errores. Sin apuro: la carga parcial',
            '  está desactivada por ahora.',
            '- **Chequeos defensivos revertidos.** Si aparecen otros',
            '  casos donde el chequeo ayuda, se reintroduce selectivo.',
            '- **Plugin: dividir la ventana en pestañas, una por rol.**',
            '  Idea anotada al cierre de v78v. Objetivo: cubrir la',
            '  batería completa de pruebas (base + admin + dueño +',
            '  terminal + soporte + multi-rol) con vistas organizadas.',
            '  Diseño a discutir en la próxima tanda del plugin.',
        ],
    ],

    // ============================================================
    // plan_actual.md — §1.8 espejo JS
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.8: espejo JS alineado',
        'buscar' => [
            '### 1.8 Espejo JS del framework',
            '',
            'El framework PHP y el espejo JS deben ir en sincronía. Esta',
            'sesión tocó:',
            '',
            '- `PerdurarSuperestructuraStringSQL64.php` (PHP): fix',
            '  v78r (doble extremo en borrado de aristas) y v78s',
            '  (preservar bits de máscara).',
            '- **Pendiente:** los mismos fixes en',
            '  `PerdurarSuperestructuraStringIndexedDB64.js`.',
            '',
            'Los fixes del espejo se hacen en la próxima tanda larga del',
            'plugin, junto con las pruebas correspondientes.',
        ],
        'reemplazar' => [
            '### 1.8 Espejo JS del framework',
            '',
            'El framework PHP y el espejo JS van en sincronía.',
            '',
            '- **PHP:** `PerdurarSuperestructuraStringSQL64.php`',
            '  en versión 1.5i.8b. Esquema con `id_fila`',
            '  (`AUTO_INCREMENT`, PK), `id_especial VARCHAR(50)',
            '  NULL`, `idnodo_fila`/`idadyacente_fila` en',
            '  `adyacente_contexto`. Mapa estático',
            '  `$_mapa_id_a_fila`. Guardado parcial por `id_fila`.',
            '- **JS:** `PerdurarSuperestructuraStringIndexedDB64.js`',
            '  espejado en V1.5plugin.7a. Mismo esquema (con',
            '  `autoIncrement` y `id_especial`), mismo mapa',
            '  estático, misma lógica de guardado parcial.',
            '',
            'Ambos frameworks quedan alineados al cierre de v78v.',
        ],
    ],

    // ============================================================
    // plan_actual.md — §1.9 título + lección nueva
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.9: título + lección de IDs efímeros',
        'buscar' => [
            '### 1.9 Lecciones de la sesión v78b-v78s',
        ],
        'reemplazar' => [
            '### 1.9 Lecciones de la sesión v78b-v78v',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1.9: agregar lección 3',
        'buscar' => [
            '2. **Las reparaciones topológicas son necesarias pero no',
            '   suficientes.** Recuperan enlaces faltantes, pero no',
            '   previenen que se vuelvan a perder.',
        ],
        'reemplazar' => [
            '2. **Las reparaciones topológicas son necesarias pero no',
            '   suficientes.** Recuperan enlaces faltantes, pero no',
            '   previenen que se vuelvan a perder.',
            '3. **Los IDs numéricos del framework son efímeros.** No',
            '   sirven como clave de nada que cruce requests. El',
            '   guardado parcial tiene que basarse en una clave',
            '   estable de la DB (`id_fila`). Ver §1.5.6. El diseño',
            '   con ID persistente en el framework está postergado',
            '   (§1.10).',
        ],
    ],

    // ============================================================
    // plan_actual.md — §2.3 próximos pasos
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §2.3: próximos pasos',
        'buscar' => [
            '### 2.3 Próximos pasos sugeridos',
            '',
            '1. **Retomar el bug §1.5 con logs de diagnóstico** en',
            '   `_guardar_con_filtro`. Ver §1.5.4.',
            '2. **Espejar v78r/v78s en el framework JS.**',
            '3. **Adaptar los 4 módulos menores** (Liquidacion, Rendicion,',
            '   Empresa, Vehiculo) con el mismo patrón de contexto.',
            '4. **B3.3c** cuando aparezca dolor real o cuando lleguen',
            '   los iteradores con ID especial.',
        ],
        'reemplazar' => [
            '### 2.3 Próximos pasos sugeridos',
            '',
            '1. **Batería completa de pruebas con el plugin,**',
            '   organizadas por rol. Reorganizar la ventana del plugin',
            '   en pestañas (base + admin + dueño + terminal +',
            '   soporte + multi-rol). Idea anotada en v78v. Es el',
            '   próximo foco.',
            '2. **Reactivar `_contextos_para_operacion`** cuando la',
            '   batería pase en verde. Entonces la carga parcial',
            '   vuelve a estar activa con el fix del bug §1.5.',
            '3. **Adaptar los 4 módulos menores** (Liquidacion,',
            '   Rendicion, Empresa, Vehiculo) con el mismo patrón',
            '   de contexto.',
            '4. **B3.3c** cuando aparezca dolor real o cuando lleguen',
            '   los iteradores con ID especial.',
        ],
    ],

    // ============================================================
    // prompt_piloto.md — historial v78v
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: historial v78v',
        'buscar' => [
            '- **v78b-v78s** (línea de carga parcial): ver',
            '  `prompts/plan_actual.md` §1.2 para la cadena completa',
            '  (Etapa 0, 1b, 1c.1a/b, 1c.2a-f, reparación topológica,',
            '  fixes del framework v78r/v78s). El prompt del piloto',
            '  no se actualiza por cada tanda de la línea; la fuente',
            '  viva es `plan_actual.md`.',
        ],
        'reemplazar' => [
            '- **v78b-v78v** (línea de carga parcial): ver',
            '  `prompts/plan_actual.md` §1.2 para la cadena completa',
            '  (Etapa 0, 1b, 1c.1a/b, 1c.2a-f, reparación topológica,',
            '  fixes del framework v78r/v78s, fix `id_fila` v78v).',
            '  El prompt del piloto no se actualiza por cada tanda',
            '  de la línea; la fuente viva es `plan_actual.md`.',
            '- **v78v** (fix del bug §1.5): cambio de esquema en',
            '  SQL64. `nodo_contexto` usa `id_fila` AUTO_INCREMENT',
            '  como PK e `id_especial` para el ID especial del',
            '  framework. `adyacente_contexto` referencia sus',
            '  extremos por `idnodo_fila`/`idadyacente_fila`. Mapa',
            '  estático `$_mapa_id_a_fila`. Guardado parcial por',
            '  `id_fila`. `_contextos_para_operacion` neutralizado',
            '  (carga total hasta que la batería de pruebas pase).',
            '  Espejo JS en V1.5plugin.7a. Ver `plan_actual.md`',
            '  §1.5.6.',
        ],
    ],

    // ============================================================
    // prompt_framework_iteradores.md — nota al pie del esquema
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'prompt_framework: nota del cambio de esquema',
        'buscar' => [
            'La tabla `contexto` mapea bit ↔ nombre del ID especial.',
            'Los bits se asignan por orden alfabético de los IDs',
            'especiales en el primer `guardar`; los contextos nuevos',
            'van al próximo bit libre. Los contextos que desaparecen',
            'no liberan su bit (histórico).',
        ],
        'reemplazar' => [
            '**Actualización v1.5i.8a/b.** El esquema de',
            '`nodo_contexto` y `adyacente_contexto` cambió.',
            '`nodo_contexto` ahora usa `id_fila BIGINT',
            'AUTO_INCREMENT` como PK y `id_especial VARCHAR(50)',
            'NULL` para el ID especial del framework (NULL para',
            'los numéricos, que son efímeros).',
            '`adyacente_contexto` referencia sus extremos por',
            '`idnodo_fila`/`idadyacente_fila`, no por los IDs del',
            'framework. El guardado parcial usa un mapa estático',
            '`$_mapa_id_a_fila` (id_nodo_framework => id_fila).',
            'Ver historial 1.5i.8a y 1.5i.8b.',
            '',
            'La tabla `contexto` mapea bit ↔ nombre del ID especial.',
            'Los bits se asignan por orden alfabético de los IDs',
            'especiales en el primer `guardar`; los contextos nuevos',
            'van al próximo bit libre. Los contextos que desaparecen',
            'no liberan su bit (histórico).',
        ],
    ],
];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios — prompts del proyecto PHP ===\n\n";

function detectar_eol(string $contenido): string {
    return (strpos($contenido, "\r\n") !== false) ? "\r\n" : "\n";
}
function normalizar_a_unix(string $contenido): string {
    return str_replace("\r\n", "\n", $contenido);
}
function normalizar_a_original(string $contenido, string $eol): string {
    if ($eol === "\n") return $contenido;
    return str_replace("\n", "\r\n", $contenido);
}
function contar_ocurrencias(string $contenido, string $bloque): int {
    if ($bloque === '') return 0;
    $count = 0;
    $offset = 0;
    while (($pos = strpos($contenido, $bloque, $offset)) !== false) {
        $count++;
        $offset = $pos + strlen($bloque);
    }
    return $count;
}

$reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado (faltan campos).\n";
        exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        continue;
    }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }

    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;

    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) {
            $bloques_fallidos[] = "$archivo_rel: bloque no encontrado - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        if ($ocurrencias > 1) {
            $bloques_fallidos[] = "$archivo_rel: bloque ambiguo ($ocurrencias) - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
if (!empty($bloques_fallidos)) {
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";