<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77o (documentación de cierre).
 *   - prompts/plan_actual.md: actualizar última versión, tanda
 *     actual, y agregar §1.8 con las lecciones aprendidas en
 *     esta sesión.
 *   - prompts/prompt_framework_iteradores.md: agregar 1.5i.7m
 *     al historial y dos lecciones nuevas en §13.
 *   - index.php: bump.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // plan_actual.md — §1.1 última actualización
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: §1.1 última actualización a v77o',
        'buscar' => [
            '**Última actualización de este archivo:** v1.5piloto.77d',
            '(fix del modal de pasajero tras cambiar de asiento).',
        ],
        'reemplazar' => [
            '**Última actualización de este archivo:** v1.5piloto.77o',
            '(cierre de la fase B2.3.5: pruebas del plugin + fixes).',
        ],
    ],

    // ============================================================
    // plan_actual.md — tanda actual
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual a v77o',
        'buscar' => [
            '**Tanda actual:** v77m (fix del DivisionByZeroError).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77o (cierre de B2.3.5 + documentación).',
            '',
            '**Estado al cierre.** La fase B2.3.5 quedó completa:',
            'el repuntado de terminales al compartido está aplicado en',
            'producción, los terminales nuevos se repuntan al vuelo, y',
            'las 3 pruebas del plugin (57, 58, 59) están verdes.',
            '',
            '**Cadena de cierres desde v77f hasta v77o:**',
            '',
            '- **v77f** (B2.3.5a): comando `app:repuntar_terminales_compartido`.',
            '  Cambia el enlace `dueno` de cada terminal para que apunte',
            '  a su compartido. Idempotente. Aplicado en producción.',
            '- **v77g** (B2.3.5b.1): el enrutador pasa contexto del',
            '  solicitante terminal en `estado_asientos`, `ventas/obtener`,',
            '  `ventas/cancelar`, `ventas/pagar_cupon`, `ventas/info_cancelacion`.',
            '- **v77h** (B2.3.5b.2): contexto en `cambiar_asiento_pasaje`',
            '  y en reservas.',
            '- **v77i** (B2.3.5b.3): contexto en empresas, vehículos,',
            '  pasajeros.',
            '- **v77j**: fix de zona horaria (`date_default_timezone_set(`',
            '  \'America/Argentina/Buenos_Aires\')`). Bug latente desde v58,',
            '  hacía que el autocompletado por DNI dijera "Fecha de',
            '  actualización inválida" entre las 21 y 24 hora argentina.',
            '- **v77k** (B2.3.5c.1, framework 1.5i.7m): `grafo:nodo`',
            '  agrega `destino_es_compartido` a cada adyacente. Permite',
            '  verificar topología con una sola consulta.',
            '- **v77l** (B2.3.5c.2 + c.3): fix del flujo de alta de',
            '  terminal. Nuevo helper `_asegurar_compartido_y_repuntar`',
            '  en `Usuario.php` que crea el compartido y repunta el',
            '  enlace `dueno` al vuelo. `app:repuntar_terminales_compartido`',
            '  reescrito para iterar sobre terminales (no sobre',
            '  compartidos existentes).',
            '- **v77m**: fix del `DivisionByZeroError` en',
            '  `_crear_lista_cupones_venta` cuando `cuotas = 1` y',
            '  `monto_pagado < total`. Validación temprana en',
            '  `confirmar_venta_actual` + defensivo en el helper.',
            '- **v77n**: descartado sin aplicar. Se había propuesto',
            '  `grafo:nodo_por_dato` para que las pruebas encontraran',
            '  nodos por dato. Descartado por el usuario: los datos',
            '  pueden repetirse, y todo recorrido debe partir de una',
            '  raíz.',
            '',
            '**Pruebas del plugin (B2.3.5b.4):**',
            '',
            '- **Prueba 57** (`enlace_dueno_apunta_al_compartido`):',
            '  verifica que el enlace `dueno` del terminal apunta a',
            '  un compartido. Usa `grafo:nodo` con el campo',
            '  `destino_es_compartido`.',
            '- **Prueba 58** (`venta_terminal_arbol_dual`): verifica',
            '  que una venta hecha por un terminal aparece en los',
            '  dos listados (`ventas/listar` tipo=dueno y tipo=terminal),',
            '  que navegan por los árboles paralelos.',
            '- **Prueba 59** (`cambiar_asiento_desde_croquis`): flujo',
            '  completo de cambiar de asiento desde el croquis.',
            '',
            '**Pendientes de la línea B:**',
            '',
            '- **B2.4** (próxima): mantener vivos los compartidos en',
            '  cada operación de escritura. Tanda grande, transversal.',
            '  Se puede partir por operación: alta/edición/baja de viaje,',
            '  micro, empresa, confirmar venta, cancelar venta, cambiar',
            '  asiento, reservar/liberar reserva, asignar pasajero.',
            '- **B3**: eliminar los 22 usos de `Nodo::nodo_por_id(\'usuarios\')`',
            '  que quedan. Los 6 del enrutador son los más delicados:',
            '  requieren que el enrutador empiece a pasar contexto',
            '  (B2.3.5. ya lo hace en las acciones del terminal, falta',
            '  en los chequeos de nivel).',
            '- **C** (opcional): tipos como IDs especiales.',
            '- **D**: carga parcial como optimización. Requiere Fase 4',
            '  del framework (`guardar_parcial` real con merge).',
        ],
    ],

    // ============================================================
    // plan_actual.md — §1.8 lecciones aprendidas
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: agregar §1.8 lecciones de la sesión',
        'buscar' => [
            '### 1.7 Deuda técnica',
        ],
        'reemplazar' => [
            '### 1.8 Lecciones aprendidas en esta sesión (v77f a v77o)',
            '',
            '**Sobre el framework y los IDs.**',
            '',
            '1. **Los IDs numéricos son referencias de memoria.**',
            '   Cambian entre cada carga del grafo. Solo los IDs',
            '   especiales (strings no numéricos) son estables entre',
            '   cargas. Nunca usar un ID numérico para "seguir" un',
            '   enlace entre dos llamadas POST distintas.',
            '2. **`grafo:nodo` devuelve IDs numéricos que solo son',
            '   válidos dentro de una misma carga.** Cada POST a',
            '   `grafo/nodo` recarga el grafo y regenera todos los',
            '   IDs numéricos. La tabla de equivalencias del framework',
            '   vive solo dentro de una carga.',
            '3. **Buscar nodos = recorrer desde una raíz. Siempre.**',
            '   No buscar por dato (los datos pueden repetirse),',
            '   no buscar por ID numérico. La única forma robusta',
            '   es navegar desde una raíz dentro de una sola carga.',
            '4. **Un comando del estilo `nodo_por_dato` es frágil.**',
            '   Aunque hoy los datos sean únicos (por ejemplo, los',
            '   `id_venta` tienen timestamp), mañana pueden repetirse.',
            '   Invita a construir cosas frágiles encima. Descartado.',
            '',
            '**Sobre el flujo de alta y los compartidos.**',
            '',
            '5. **Las migraciones que iteran sobre "lo que ya existe"',
            '   no cubren los elementos creados después.**',
            '   `app:repuntar_terminales_compartido` (v77f) iteraba',
            '   sobre los compartidos ya existentes del dueño, no',
            '   sobre los terminales. Un terminal creado después',
            '   quedaba sin compartido y sin repuntar, silenciosamente.',
            '   Fix (v77l): iterar sobre la entidad fuente (terminales),',
            '   no sobre el derivado (compartidos).',
            '6. **Los flujos de alta deben mantener la topología al',
            '   vuelo.** Si la topología se mantiene solo con comandos',
            '   manuales, cada alta que no los corre deja el grafo',
            '   inconsistente. Fix (v77l): `_asegurar_compartido_y_repuntar`',
            '   se llama desde `agregar_usuario` y `actualizar_usuario`.',
            '',
            '**Sobre validaciones en el backend.**',
            '',
            '7. **Los flujos sin sentido de negocio deben rechazarse',
            '   temprano.** El `DivisionByZeroError` en',
            '   `_crear_lista_cupones_venta` con `cuotas=1` y',
            '   `monto_pagado < total` era un caso imposible para el',
            '   usuario (el frontend bloquea el input), pero el',
            '   backend no lo chequeaba. Fix (v77m): validación',
            '   temprana + defensivo en el helper.',
            '',
            '**Sobre zonas horarias.**',
            '',
            '8. **El servidor y el navegador deben compartir zona',
            '   horaria.** Sin `date_default_timezone_set`, PHP usa',
            '   UTC. Entre las 21 y las 24 hora argentina, `date(\'Y-m-d\')`',
            '   queda un día adelantado respecto del navegador y rompe',
            '   comparaciones de fechas del lado del frontend.',
            '   Fix (v77j): `date_default_timezone_set(`',
            '   \'America/Argentina/Buenos_Aires\')` en `index.php`.',
            '',
            '**Sobre pruebas del plugin (JS).**',
            '',
            '9. **Verificar topología entre POSTs requiere navegación,',
            '   no seguimiento de IDs.** Las pruebas 57 y 58 fallaron',
            '   al principio porque seguían IDs numéricos entre',
            '   consultas. La verificación debe ser: una sola llamada',
            '   que navegue desde la raíz y devuelva todo lo necesario',
            '   para comparar.',
            '10. **`ventas/listar` ya navega los árboles paralelos.**',
            '    Con `tipo=dueno` recorre `privado/ventas` con',
            '    `hmi`/`hd`/`p`. Con `tipo=terminal` recorre el',
            '    compartido con `hmi_<term>`/`hd_<term>`/`p_<term>`.',
            '    Verificar pertenencia = buscar el `id_venta` en los',
            '    dos listados.',
            '11. **En modales apilados, acotar selectores a',
            '    `#modal_apilado_contenido`.** El croquis del modal y',
            '    el del viaje de atrás comparten clase `.seat`. Sin',
            '    acotar, `querySelector` devuelve el primero en el DOM',
            '    (el de atrás) y el click no hace nada útil.',
            '12. **Chequear efecto visual (`.seat-elegido`) antes que',
            '    atributo booleano `disabled`.** `getAttribute(\'disabled\')`',
            '    puede devolver `[null]`, `[""]` o `["disabled"]` según',
            '    cómo se serialice. La presencia o ausencia de una',
            '    clase es más confiable.',
            '13. **No pisar el monto pagado con cuotas = 1.** El',
            '    frontend autocompleta el input con el total y lo deja',
            '    `disabled`. Forzarlo a un valor menor dispara el',
            '    rechazo del backend introducido en v77m.',
            '14. **Polling activo en lugar de pausas fijas.** El',
            '    modal de cambiar asiento hace 2 fetches en serie',
            '    (estado + configuración) antes de armar el croquis.',
            '    En un grafo grande puede tardar varios segundos.',
            '',
            '### 1.7 Deuda técnica',
        ],
    ],

    // ============================================================
    // prompt_framework_iteradores.md — historial 1.5i.7m
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'framework: agregar 1.5i.7m al historial',
        'buscar' => [
            '- **1.5i.7l**: comando `grafo:raices` agregado al framework',
        ],
        'reemplazar' => [
            '- **1.5i.7m**: `grafo:nodo` agrega el campo',
            '  `destino_es_compartido` a cada adyacente. Se calcula',
            '  con `$ady->adyacente(\'_es_compartido\') !== null`, que',
            '  es O(1) porque el nodo destino ya está en memoria.',
            '  Permite verificar topología por topología (por ejemplo,',
            '  "el enlace `dueno` del terminal apunta al compartido")',
            '  con una sola consulta, sin seguir IDs numéricos (que',
            '  no son estables entre cargas).',
            '- **1.5i.7l**: comando `grafo:raices` agregado al framework',
        ],
    ],

    // ============================================================
    // prompt_framework_iteradores.md — lecciones §13
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'framework: agregar lecciones 28-29',
        'buscar' => [
            '**Regla de oro:** cualquier cambio al framework PHP se refleja en',
            'JS en la misma tanda, con dos scripts y dos commits.',
        ],
        'reemplazar' => [
            '**Nodos, IDs y navegación:**',
            '',
            '28. **Los IDs numéricos son referencias de memoria.**',
            '    Cambian entre cada carga del grafo. Solo los IDs',
            '    especiales (strings no numéricos) son estables entre',
            '    cargas. La tabla de equivalencias del framework vive',
            '    solo dentro de una carga.',
            '29. **Buscar nodos = navegar desde una raíz. Siempre.**',
            '    No buscar por dato (los datos pueden repetirse),',
            '    no buscar por ID numérico entre consultas. La única',
            '    forma robusta es recorrer desde una raíz dentro de',
            '    una sola carga del grafo. `grafo:nodo_por_dato` fue',
            '    propuesto y descartado por esta razón.',
            '',
            '**Regla de oro:** cualquier cambio al framework PHP se refleja en',
            'JS en la misma tanda, con dos scripts y dos commits.',
        ],
    ],

    // ============================================================
    // index.php — bump
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.77o',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77m',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.77o',
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