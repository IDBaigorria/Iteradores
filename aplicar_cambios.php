<?php
/**
 * Aplicador — Tanda Y: cierre documental del arco (prompt_seguridad.md).
 *
 * Uso (raíz de iteradores/): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
// Los .md no tienen @version: no hay bumps.
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // A) Cabecera: "Estado al cierre".
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'Cabecera: estado al cierre de v81i',
        'buscar' => [
            '**Estado al cierre de v1.5piloto.78z:**',
            '',
            '- La topología es la única fuente de verdad de la seguridad. Los',
            '  chequeos por código (nivel, dueño asignado) siguen existiendo',
            '  como respaldo, pero la garantía fuerte viene del grafo.',
            '- El bug §1.5 (viaje viejo que desaparecía tras una operación',
            '  del terminal) está **resuelto**. Ver §8.1.',
            '- El bug §1.5.7/§1.5.8 (nodos compartidos entre contextos) está',
            '  **resuelto en ventas** con soft delete coordinado. Falta',
            '  replicarlo a los otros árboles.',
            '- La **carga parcial está desactivada** desde v78v:',
            '  `_contextos_para_operacion` devuelve `null`. Todo carga total.',
            '  Se reactiva cuando la batería completa de pruebas pase.',
            '- El espejo JS del framework está alineado a V1.5i.8b',
            '  (V1.5plugin.7a).',
        ],
        'reemplazar' => [
            '**Estado al cierre de v1.5piloto.81i:**',
            '',
            '- La topología es la única fuente de verdad de la seguridad. Los',
            '  chequeos por código (nivel, dueño asignado) siguen existiendo',
            '  como respaldo, pero la garantía fuerte viene del grafo.',
            '- Los bugs estructurales del framework (§1.5, §1.5.7, §1.5.8,',
            '  §1.5.10, §1.5.15, §1.5.16) están **resueltos**. Ver §8.',
            '- El soft delete coordinado está aplicado en **ventas** y',
            '  **cancelaciones**. Rendiciones y liquidaciones solo las crea',
            '  el dueño, no aplica.',
            '- La **carga parcial está activa** desde v78g. Toda operación',
            '  POST con `nombre_solicitante` carga parcial con `[us_<sol>]`.',
            '  Excepciones puntuales documentadas en',
            '  `_contextos_para_operacion`. Ver §8.9.',
            '- **B3.3c reducido a 4 helpers** (antes 7). Liquidación y',
            '  rendición se adaptaron en v81h. Ver §8.6.',
            '- El espejo JS del framework está alineado a **V1.5i.8c**',
            '  (V1.5plugin.7b).',
            '- Los **módulos menores** ya están todos adaptados. Ver §8.7.',
        ],
    ],

    // ============================================================
    // B) §6.10 + nota: agregar anti-patrón 6.12.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => '§6: nuevo anti-patrón 6.12',
        'buscar' => [
            '### 6.10 Filtrar compartidos con el prefijo `us_` de más',
            '',
            'Ver §5.10. Los compartidos se llaman',
            '`compartido_con_<nombre_usuario>`, no',
            '`compartido_con_us_<nombre>`. Un filtro con el `us_` de',
            'más nunca matchea y falla silenciosamente.',
            '',
            '**Caso real:** v79b. `_procesar_pendientes_del_dueno`',
            'tenía el filtro mal. El buzón de ventas nunca se',
            'drenaba y el dueño no veía las ventas nuevas. El',
            'mismo bug latente en `reparar_arboles.php`.',
            '',
            '---',
            '',
            '## 7. CÓMO VERIFICAR EL AISLAMIENTO',
        ],
        'reemplazar' => [
            '### 6.10 Filtrar compartidos con el prefijo `us_` de más',
            '',
            'Ver §5.10. Los compartidos se llaman',
            '`compartido_con_<nombre_usuario>`, no',
            '`compartido_con_us_<nombre>`. Un filtro con el `us_` de',
            'más nunca matchea y falla silenciosamente.',
            '',
            '**Caso real:** v79b. `_procesar_pendientes_del_dueno`',
            'tenía el filtro mal. El buzón de ventas nunca se',
            'drenaba y el dueño no veía las ventas nuevas. El',
            'mismo bug latente en `reparar_arboles.php`.',
            '',
            '### 6.12 Confiar en el POST del backend sin probar el flujo del usuario',
            '',
            'Un test que solo hace POST al backend puede pasar aunque',
            'el flujo del usuario esté roto. Si el frontend no manda',
            'los parámetros que el backend espera, el usuario ve un',
            'error aunque el endpoint funcione con los parámetros',
            'correctos.',
            '',
            '**Caso real:** v81i. Los POST `rendiciones/obtener`,',
            '`rendiciones/aceptar_ajuste` y `liquidaciones/obtener`',
            'existían y funcionaban si se les pasaba `nombre_dueno`,',
            'pero el frontend del piloto no lo mandaba, y el soporte',
            'veía "no encontrada". Un test que solo hace POST con el',
            '`nombre_dueno` explícito NO detecta el bug.',
            '',
            '**Patrón correcto:** las pruebas que verifican flujos',
            'del usuario deben hacer el clic del usuario y verificar',
            'el efecto en el DOM (modal abierto con contenido). El',
            'POST es un detalle de implementación del frontend, no',
            'el contrato.',
            '',
            '---',
            '',
            '## 7. CÓMO VERIFICAR EL AISLAMIENTO',
        ],
    ],

    // ============================================================
    // C) §7.2: sumar pruebas 67-69.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => '§7.2: sumar pruebas 67-69',
        'buscar' => [
            '- Prueba 60 (`cancelar_venta_arbol_dual`): simétrica. Cancelar',
            '  una venta la quita de los dos árboles.',
        ],
        'reemplazar' => [
            '- Prueba 60 (`cancelar_venta_arbol_dual`): simétrica. Cancelar',
            '  una venta la quita de los dos árboles.',
            '- Pruebas 67, 68, 69 (v1.5plugin.8f): verifican el flujo del',
            '  usuario completo (login → pestaña → elegir dueño → click en',
            '  "Ver detalle") para rendiciones y liquidaciones con',
            '  solicitante soporte y dueño. Detectan bugs del frontend que',
            '  un POST directo no vería (ver §6.12).',
        ],
    ],

    // ============================================================
    // D) §7.5: carga parcial activa.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => '§7.5: carga parcial activa',
        'buscar' => [
            '### 7.5 Verificación de carga parcial',
            '',
            '**Importante:** la carga parcial está desactivada desde v78v.',
            'Cuando se reactive, esta verificación aplica.',
            '',
            'Después de un `cargar_parcial([us_X])`:',
        ],
        'reemplazar' => [
            '### 7.5 Verificación de carga parcial',
            '',
            'La carga parcial está **activa desde v78g**. Las',
            'verificaciones de abajo aplican al código actual, con las',
            'excepciones documentadas en `_contextos_para_operacion`',
            '(gestión de usuarios, informes de cancelación/liquidación/',
            'rendición).',
            '',
            'Después de un `cargar_parcial([us_X])`:',
        ],
    ],

    // ============================================================
    // E) §8.2: resuelto ventas + cancelaciones.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => '§8.2: resuelto ventas + cancelaciones',
        'buscar' => [
            '### 8.2 Nodos compartidos entre contextos — RESUELTO en ventas',
            '',
            '**Estado:** resuelto para ventas (v78w-v78z). Pendiente para',
            'rendiciones, liquidaciones, cancelaciones. Ver §9.',
        ],
        'reemplazar' => [
            '### 8.2 Nodos compartidos entre contextos — RESUELTO',
            '',
            '**Estado:** resuelto para ventas (v78w-v78z) y cancelaciones',
            '(v79a con buzón). Rendiciones y liquidaciones solo las crea el',
            'dueño, no aplica. Ver §9.',
        ],
    ],

    // ============================================================
    // F) §8.5: espejo JS alineado a V1.5i.8c.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => '§8.5: espejo JS a V1.5i.8c',
        'buscar' => [
            '### 8.5 Espejo JS',
            '',
            'Alineado a V1.5i.8b en V1.5plugin.7a. El espejo JS implementa',
            'el mismo esquema `id_fila` + `id_especial` + mapa estático.',
        ],
        'reemplazar' => [
            '### 8.5 Espejo JS',
            '',
            'Alineado a **V1.5i.8c** en V1.5plugin.7b. El espejo JS',
            'implementa el mismo esquema `id_fila` + `id_especial` + mapa',
            'estático, y la misma propagación de máscaras a nodos nuevos',
            'que el PHP (fix v1.5i.8c).',
        ],
    ],

    // ============================================================
    // G) §8.6: B3.3c reducido a 4 helpers.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => '§8.6: B3.3c reducido a 4 helpers',
        'buscar' => [
            '### 8.6 Helpers que iteran sobre todos los dueños (B3.3c)',
            '',
            '7 funciones siguen iterando sobre la raíz `usuarios` para',
            'buscar por id:',
            '',
            '- `Venta.php`: `_buscar_venta_por_id` (fallback),',
            '  `obtener_cancelacion_por_id`.',
            '- `Liquidacion.php`: `obtener_liquidacion_por_id`.',
            '- `Rendicion.php`: `aceptar_ajuste_rendicion`,',
            '  `obtener_rendicion_por_id`.',
            '- `Pasajero.php`: `obtener_pasajero_por_dni` (rama del',
            '  historial de ventas).',
            '',
            '**Consecuencia:** con carga parcial, esas funciones devuelven',
            'null o vacío. Se resuelven cuando aparezca dolor real o cuando',
            'implementemos iteradores con ID especial.',
        ],
        'reemplazar' => [
            '### 8.6 Helpers que iteran sobre todos los dueños (B3.3c)',
            '',
            '**Reducido a 4 funciones** (v81h; antes eran 7). Las',
            'funciones de liquidación y rendición se adaptaron al patrón',
            '`?string $nombre_dueno`:',
            '',
            '- `Venta.php`: `_buscar_venta_por_id` (fallback),',
            '  `obtener_cancelacion_por_id`.',
            '- `Pasajero.php`: `obtener_pasajero_por_dni` (rama del',
            '  historial de ventas).',
            '',
            '**Ya adaptadas en v81h:**',
            '- `Liquidacion.php`: `obtener_liquidacion_por_id(?string',
            '  $nombre_dueno = null)`. Si viene, navega directo desde',
            '  `us_<dueño>`. Si no, fallback a recorrer todos los dueños',
            '  desde la raíz (carga total, compatibilidad con',
            '  `Impresion.php`).',
            '- `Rendicion.php`: `obtener_rendicion_por_id(...)` y',
            '  `aceptar_ajuste_rendicion(...)` con el mismo patrón.',
            '',
            '**Consecuencia:** con carga parcial, las 4 restantes',
            'devuelven null o vacío si el solicitante no tiene `usuarios`',
            'en memoria (dueño y soporte). Se resuelven cuando aparezca',
            'dolor real o cuando implementemos iteradores con ID especial.',
            '',
            '**Bug de frontend asociado (v81i).** El backend ya navegaba',
            'correctamente con `nombre_dueno`, pero el frontend del piloto',
            'no lo mandaba en `rendiciones/obtener`,',
            '`rendiciones/aceptar_ajuste` ni `liquidaciones/obtener`. Solo',
            'fallaba con soporte (dueño y admin lo resolvían por otros',
            'caminos). Fix en `rendiciones.js` y `liquidaciones.js`. Ver',
            '§6.12.',
        ],
    ],

    // ============================================================
    // H) §8.7: módulos menores cerrados.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => '§8.7: módulos menores cerrados',
        'buscar' => [
            '### 8.7 Módulos menores sin contexto',
            '',
            '`Liquidacion.php`, `Rendicion.php`, `Empresa.php`,',
            '`Vehiculo.php` no fueron adaptados al contexto del solicitante.',
            'Cuando un terminal los invoca (raro), cargan total o fallan.',
            'Sub-tandas cortas cuando aparezcan errores.',
        ],
        'reemplazar' => [
            '### 8.7 Módulos menores sin contexto — CERRADO en v81h',
            '',
            'Los cuatro módulos ya están adaptados al patrón `us_<dueño>`:',
            '',
            '- `Empresa.php`: adaptado en v76u-v77r.',
            '- `Vehiculo.php`: adaptado en v77v-v77x.',
            '- `Liquidacion.php`: adaptado en v81h.',
            '- `Rendicion.php`: adaptado en v81h.',
            '',
            'Solo quedan los 4 helpers de §8.6, que son funciones que',
            'buscan por id y no módulos completos.',
        ],
    ],

    // ============================================================
    // I) §8.9: carga parcial activa.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => '§8.9: carga parcial activa',
        'buscar' => [
            '### 8.9 Carga parcial desactivada',
            '',
            'Desde v78v, `_contextos_para_operacion` devuelve `null`. Todo',
            'carga total. Se reactiva cuando la batería completa de pruebas',
            'pase en verde.',
            '',
            '**Por qué:** el bug §1.5 y sus vecinos (§1.5.7, §1.5.8)',
            'aparecían solo con carga parcial activa. Mientras se validan',
            'los fixes, la carga total es correcta (aunque lenta).',
        ],
        'reemplazar' => [
            '### 8.9 Carga parcial: activa y en producción',
            '',
            'La carga parcial está **activa desde v78g**. Toda operación',
            'POST con `nombre_solicitante` carga parcial con `[us_<sol>]`.',
            'Sin solicitante, carga total.',
            '',
            'Las excepciones (acciones que siguen usando carga total) están',
            'documentadas en `_contextos_para_operacion`:',
            '',
            '- Gestión de usuarios (crear, editar, eliminar usuario y',
            '  terminal).',
            '- Informes de cancelación, liquidación y rendición (usan los',
            '  helpers de §8.6).',
            '',
            'Los bugs §1.5 y §1.5.10, que aparecían solo con carga parcial',
            'activa, están resueltos en el framework 1.5i.8a-c.',
        ],
    ],

    // ============================================================
    // J) §9.1 y §9.2: no aplica.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => '§9.1 y §9.2: no aplica',
        'buscar' => [
            '### 9.1 Rendiciones',
            '',
            'Solo el dueño las crea (no hay terminal). El contenedor vive en',
            '`us_dueno/rendiciones`. Con la carga parcial del dueño, el',
            'contenedor está en `$filas_cargadas` y `hmi` sobrevive. Pero',
            'el guardado parcial del terminal podría, en teoría, afectarlo',
            'si `rendiciones` estuviera alcanzable desde el compartido (no',
            'lo está hoy). Igual: aplicar la reparación preventiva.',
            '',
            '### 9.2 Liquidaciones',
            '',
            'Igual que rendiciones. Solo el dueño.',
        ],
        'reemplazar' => [
            '### 9.1 Rendiciones — no aplica',
            '',
            'Solo el dueño las crea. El contenedor vive en',
            '`us_dueno/rendiciones`. No es alcanzable desde ningún',
            'compartido. No tiene el problema de nodos multi-contexto. Los',
            'listados y detalles funcionan con carga parcial desde v81h.',
            '',
            '### 9.2 Liquidaciones — no aplica',
            '',
            'Igual que rendiciones. Solo el dueño.',
        ],
    ],

    // ============================================================
    // K) §9.3: cancelaciones resueltas con buzón.
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => '§9.3: cancelaciones resueltas con buzón',
        'buscar' => [
            '### 9.3 Cancelaciones',
            '',
            'Pueden crearse desde el dueño o desde un terminal. Si es desde',
            'el terminal, hoy se insertan **solo en el contenedor del',
            'compartido** (`_crear_nodo_cancelacion` con `$nodo_contexto`).',
            'El dueño no las ve.',
            '',
            'Hay que decidir el diseño:',
            '',
            '- **Opción A:** insertar en ambos árboles (dueño real +',
            '  compartido), como las ventas.',
            '- **Opción B:** consolidar todo en el dueño y que el compartido',
            '  sea solo un puntero.',
            '',
            'Pendiente de discusión.',
        ],
        'reemplazar' => [
            '### 9.3 Cancelaciones — resuelto en v79a (buzón)',
            '',
            'Pueden crearse desde el dueño o desde un terminal. Se resolvió',
            'con el mismo patrón que las ventas: **inserción diferida con',
            'buzón**. El terminal deposita la cancelación en',
            '`compartido_con_us_termX/pendientes_cancelaciones`; el dueño',
            'lo drena en su próxima carga. Ver §5.8.',
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

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $l) $total_reemplazos += count($l);
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