<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77p (actualización de prompts).
 *   - prompts/plan_actual.md: §1.1 corregido (Próximo paso: B2.4)
 *     + §1.9 nuevo (auditoría de B2.4/B3).
 *   - prompts/prompt_piloto.md: historial v76v-v77o.
 *   - prompts/prompt_sistema_scripts.md: bump.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // plan_actual.md — §1.1: corregir "Próximo paso"
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: §1.1 Próximo paso a B2.4',
        'buscar' => [
            'Con v76u quedó cerrada la Fase B2.2 completa (Viaje, Venta,',
            'ViajeAsientos, Empresa). Después vinieron B2.3.1, B2.3.2 y',
            'B2.3.3 (árboles paralelos parametrizados, aplicados también',
            'en producción). Hubo un paréntesis de bugs (v76z a v77d) que',
            'incluyó el fix de atadura comprador-pasajero, el modal',
            'post-venta, y la funcionalidad completa de "cambiar de',
            'asiento". Próximo paso: **Fase B2.3.4** (ajustar las',
            'escrituras de venta para que inserten y borren en los dos',
            'árboles paralelos cuando el contexto sea un compartido).',
            'Detalle en §2.3.)',
        ],
        'reemplazar' => [
            'La Fase B2.3 quedó cerrada completa (B2.3.1 a B2.3.5). El',
            'repuntado de terminales al compartido está aplicado en',
            'producción, los terminales nuevos se repuntan al vuelo',
            '(`_asegurar_compartido_y_repuntar`, v77l), y las 3 pruebas',
            'del plugin (57, 58, 59) están verdes. La auditoría completa',
            'de B2.4 ("mantener vivos los compartidos") y B3 ("eliminar',
            'los usos de `Nodo::nodo_por_id(\'usuarios\')`") está en §1.9.',
            'Próximo paso: **Fase B2.4** (los 5 bugs funcionales que',
            'detectó la auditoría), empezando por B2.4.2 + B2.4.5.',
        ],
    ],

    // ============================================================
    // plan_actual.md — agregar §1.9 (auditoría de B2.4/B3)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: agregar §1.9 (auditoría B2.4/B3)',
        'buscar' => [
            '### 1.7 Deuda técnica',
        ],
        'reemplazar' => [
            '### 1.9 Auditoría de B2.4 y B3 (v77o)',
            '',
            'Auditoría completa del estado de la Fase B2.4 ("mantener',
            'vivos los compartidos") y de los usos pendientes de',
            '`Nodo::nodo_por_id(\'usuarios\')` (B3). Se hizo leyendo el',
            'código actual de los módulos del piloto, no de memoria.',
            '',
            '**B2.4 — lo que YA está cubierto:**',
            '',
            '- `confirmar_venta_actual` inserta en ambos árboles',
            '  (dueño + compartido). OK desde B2.3.4.',
            '- `cancelar_venta` desenlaza de ambos árboles. OK desde',
            '  B2.3.4.',
            '- `_asegurar_compartido_y_repuntar` (v77l) crea el',
            '  compartido al vuelo cuando se crea/edita un terminal.',
            '  OK.',
            '- Micros, vehículos y empresas cuelgan de nodos compartidos',
            '  por transitividad (mismo nodo físico). Los flujos',
            '  existentes los ven sin cambios. OK.',
            '',
            '**B2.4 — lo que FALTA (5 bugs funcionales):**',
            '',
            '1. `eliminar_viaje` (Viaje.php) no desenlaza del compartido',
            '   de cada terminal autorizado. Consecuencia:',
            '   `Nodo::eliminar` falla en silencio y el viaje queda',
            '   huérfano. **Bug real: hoy no se puede eliminar un viaje',
            '   con terminales autorizadas.**',
            '2. `agregar_terminal_autorizada` (Viaje.php) no actualiza',
            '   el compartido del terminal autorizado. El terminal no',
            '   ve el viaje hasta que corre una migración manual.',
            '3. `eliminar_terminal_autorizada` (Viaje.php) no limpia',
            '   el compartido. El terminal sigue viendo el viaje aunque',
            '   ya no esté autorizado.',
            '4. `eliminar_empresa` (Empresa.php) no desenlaza del',
            '   `empresas` de los compartidos. Quedan enlaces a un',
            '   nodo destruido.',
            '5. `agregar_micro_a_viaje` (ViajeMicros.php) no agrega la',
            '   empresa al `empresas` del compartido. El terminal no',
            '   ve la nueva empresa en su listado.',
            '',
            '**B3 — usos pendientes de `Nodo::nodo_por_id(\'usuarios\')`:**',
            '',
            'Más de 22 usos. Agrupados por archivo:',
            '',
            '- `Venta.php`: 4 (`obtener_contenedor_ventas_dueno` rama',
            '  sin contexto, `_buscar_venta_por_id` rama sin contexto,',
            '  `obtener_cancelacion_por_id`, `listar_cancelaciones_de_dueno`).',
            '- `Enrutador.php`: 6 (chequeos de nivel del solicitante).',
            '- `Viaje.php`: 5 (`obtener_contenedor_viajes_dueno` rama',
            '  sin contexto, `listar_viajes_de_terminal`,',
            '  `viaje_tiene_ventas`, `agregar_terminal_autorizada`,',
            '  `formatear_viaje`).',
            '- `Usuario.php`: muchos (prácticamente todo el archivo).',
            '- `Empresa.php`: 4 (rama sin contexto + alta/edición/baja).',
            '- `Vehiculo.php`: 6 (rama sin contexto + alta/actualizaciones/',
            '  baja/subir_foto).',
            '- `ViajeMicros.php`: 4 (incluye `agregar_micro_a_viaje`).',
            '- `Pasajero.php`: ~7 (`obtener_pasajero_por_dni`,',
            '  `pasajero_tiene_pasajes`, `obtener_reservas_de_pasajero`,',
            '  `_recorrer_reservas_de_pasajero`).',
            '- `Migraciones/Funciones.php`: 2 (`detectar_usuarios_especiales`,',
            '  `detectar_niveles_usuario`).',
            '',
            'Estrategia de limpieza en 3 fases: B3.1 (reemplazar por',
            '`us_<nombre>` cuando hay nombre), B3.2 (eliminar los',
            'fallbacks "sin contexto"), B3.3 (repensar los helpers',
            'que iteran "sobre todos los dueños": requieren contexto',
            'o índice auxiliar).',
            '',
            '**Pruebas nuevas del plugin que hacen falta (Tanda A):**',
            '',
            '- 60 `cancelar_venta_desaparece_de_ambos_arboles`:',
            '  simétrica de la 58.',
            '- 61 `eliminar_viaje_con_terminal_autorizado`.',
            '- 62 `autorizar_terminal_aparece_en_compartido`.',
            '- 63 `desautorizar_terminal_desaparece_del_compartido`.',
            '- 64 `agregar_micro_actualiza_empresas_compartido`.',
            '',
            '### 1.7 Deuda técnica',
        ],
    ],

    // ============================================================
    // prompt_piloto.md — historial v76v-v77o
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: historial v76v-v77o',
        'buscar' => [
            '- **v76u**: Fase B2.2.4 del modelo topológico (Empresa.php).',
        ],
        'reemplazar' => [
            '- **v77o**: cierre de B2.3.5 + auditoría de B2.4/B3.',
            '  Repuntado aplicado en producción, `_asegurar_compartido_y_repuntar`',
            '  (v77l) repunta terminales nuevos al vuelo, pruebas del',
            '  plugin 57/58/59 verdes. La auditoría completa de B2.4 y B3',
            '  está en `plan_actual.md` §1.9.',
            '- **v77m**: fix del `DivisionByZeroError` en',
            '  `_crear_lista_cupones_venta` con `cuotas=1` y',
            '  `monto_pagado < total`. Validación temprana en',
            '  `confirmar_venta_actual` + defensivo en el helper.',
            '- **v77k**: framework 1.5i.7m. `grafo:nodo` agrega el',
            '  campo `destino_es_compartido` a cada adyacente.',
            '- **v77j**: fix de zona horaria. `date_default_timezone_set(',
            '  \'America/Argentina/Buenos_Aires\')` en `index.php`.',
            '- **v77i** (B2.3.5b.3): contexto del solicitante en empresas,',
            '  vehículos, pasajeros.',
            '- **v77h** (B2.3.5b.2): contexto en `cambiar_asiento_pasaje`',
            '  y en reservas.',
            '- **v77g** (B2.3.5b.1): contexto del solicitante en',
            '  `estado_asientos`, `ventas/obtener`, `ventas/cancelar`,',
            '  `ventas/pagar_cupon`, `ventas/info_cancelacion`.',
            '- **v77f** (B2.3.5a): comando `app:repuntar_terminales_compartido`.',
            '- **v77e** (B2.3.4): `confirmar_venta_actual` y `cancelar_venta`',
            '  mantienen ambos árboles paralelos.',
            '- **v77b/c/d**: funcionalidad "cambiar de asiento" (backend,',
            '  frontend, refresco del modal de pasajero).',
            '- **v77/v77a**: fix del Bug 1 (atadura comprador-pasajero,',
            '  casos restantes).',
            '- **v76z**: fix del modal post-venta.',
            '- **v76y**: fix de auto-detección de migraciones.',
            '- **v76x**: migración `app:construir_arboles_compartidos`.',
            '- **v76w**: helper `_nombres_arbol_para_contexto`.',
            '- **v76v**: reorganización de prompts (nuevo `plan_actual.md`,',
            '  §12 del prompt del piloto congelada, regla de actualización).',
            '- **v76u**: Fase B2.2.4 del modelo topológico (Empresa.php).',
        ],
    ],

    // ============================================================
    // prompt_sistema_scripts.md — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'prompt_sistema_scripts: bump de última actualización',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74q.',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.77o.',
            'Se confirma que el sistema de scripts sigue igual. Recordatorio:',
            'los prompts del proyecto PHP (framework, piloto, sistema de',
            'scripts, plan actual) y del plugin (`iteradoresJS/prompts/`)',
            'se actualizan en la misma tanda que el código.',
            'Antes: v1.5piloto.74q.',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================
echo "=== Aplicador de cambios ===\n\n";
function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { if ($e === "\n") return $c; return str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int { if ($b === '') return 0; $n = 0; $o = 0; while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); } return $n; }
$creaciones = []; $eliminaciones = []; $reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) { echo "[FALLO] Mal formado.\n"; exit(1); }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}
$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s), " . count($creaciones) . " a crear.\n\n";
$archivos_a_escribir = []; $bloques_ok = 0; $bloques_fallidos = [];
foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "No encontrado: $archivo_rel"; foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) { $bloques_fallidos[] = "$archivo_rel: NO ENCONTRADO - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($ocurrencias > 1) { $bloques_fallidos[] = "$archivo_rel: AMBIGUO ($ocurrencias) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
}
if ($modo_estricto && !empty($bloques_fallidos)) { echo "=== ABORTADO ===\n"; foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n"; exit(1); }
foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) { echo "[FALLO] Escribir: " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n"; continue; }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto)+1) . "\n";
}
foreach ($creaciones as $c) { $r = $raiz_proyecto.'/'.$c['archivo']; if (!is_dir(dirname($r))) mkdir(dirname($r), 0777, true); if (file_put_contents($r, implode("\n", $c['contenido']))===false){echo "[FALLO] Crear: {$c['archivo']}\n";continue;} echo "[OK] {$c['archivo']} (creado)\n"; }
echo "\n=== Resumen ===\nBloques aplicados: $bloques_ok\nArchivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) { echo "Fallos: " . count($bloques_fallidos) . "\n"; foreach ($bloques_fallidos as $f) echo "  - $f\n"; }
echo "\nListo.\n";