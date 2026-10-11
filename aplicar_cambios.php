<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77s (fix de $raiz en eliminar_usuario + plan).
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // 1. Usuario.php — fix del $raiz residual
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario: fix $raiz en eliminar_usuario',
        'buscar' => [
            '    // === 6. Desenlazar del contenedor raíz y destruir el usuario ===',
            '    $raiz->eliminar_adyacente($nombre_usuario);',
            '    Nodo::eliminar($nodo_usuario);',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        ],
        'reemplazar' => [
            '    // === 6. Desenlazar del contenedor raíz y destruir el usuario ===',
            '    // Fase B3.1: aunque el nodo se resuelve por ID especial,',
            '    // el enlace desde `usuarios` sigue existiendo y hay que',
            '    // sacarlo antes de destruir el nodo (si no, queda huérfano',
            '    // colgado de la raíz). Se resuelve la raíz solo acá.',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if ($raiz_usuarios) {',
            '        $raiz_usuarios->eliminar_adyacente($nombre_usuario);',
            '    }',
            '    Nodo::eliminar($nodo_usuario);',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
        ],
    ],

    // ============================================================
    // 2. Usuario.php — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario: bump @version a 1.5piloto.77s',
        'buscar' => [
            ' * @version   1.5piloto.77k',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.77s',
        ],
    ],

    // ============================================================
    // 3. plan_actual.md — actualizar tanda actual y plan de B3.3
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: tanda actual v77s + plan B3.3',
        'buscar' => [
            '**Tanda actual:** v77r (B3.1: aislación de usuarios).',
            '',
            '**B3.1 (v77r).** Reemplazo de `Nodo::nodo_por_id(\'usuarios\')`',
            'por `Nodo::nodo_por_id(\'us_<nombre>\')` en los flujos que',
            'ya tienen el nombre a mano. Alcanza a Venta, Viaje,',
            'Enrutador, Usuario, Empresa, ViajeMicros, Pasajero e',
            'index.php. Los listados que iteran sobre todos los',
            'usuarios y los helpers que iteran sobre todos los dueños',
            'quedan como están (B3.3). `agregar_usuario` ya no',
            'autocrea la raíz `usuarios`: el chequeo de duplicado se',
            'hace por `Nodo::existe(\'us_<nombre>\')`.',
            '',
            '**Anterior:** v77q (Tanda A, B2.4.2 + B2.4.5).',
        ],
        'reemplazar' => [
            '**Tanda actual:** v77s (fix del `$raiz` en eliminar_usuario).',
            '',
            '**B3.1 (v77r + v77s).** Reemplazo de',
            '`Nodo::nodo_por_id(\'usuarios\')` por',
            '`Nodo::nodo_por_id(\'us_<nombre>\')` en los flujos que',
            'ya tienen el nombre a mano. Alcanza a Venta, Viaje,',
            'Enrutador, Usuario, Empresa, ViajeMicros, Pasajero e',
            'index.php. Los listados que iteran sobre todos los',
            'usuarios y los helpers que iteran sobre todos los dueños',
            'quedan como están (B3.3). `agregar_usuario` ya no',
            'autocrea la raíz `usuarios`: el chequeo de duplicado se',
            'hace por `Nodo::existe(\'us_<nombre>\')`.',
            '',
            '**Fix v77s.** `eliminar_usuario` tenía un `$raiz` residual',
            'al final: se había borrado la declaración al cambiar el',
            'bloque por ID especial, pero quedó el',
            '`$raiz->eliminar_adyacente($nombre_usuario)` que desenlaza',
            'al usuario del contenedor `usuarios`. Se resuelve la raíz',
            'solo en ese punto, con comentario que explica por qué.',
            '',
            '**B3.3 — pendiente (helpers que iteran "sobre todos los',
            'dueños").** El inventario actualizado (post v77r) es:',
            '',
            '- `Liquidacion.php` (4): recibe `$nombre_dueno` en la firma.',
            '  Cambio directo a `us_<nombre>`.',
            '- `Rendicion.php` (5): idem, recibe `$nombre_dueno`.',
            '- `Pasajero.php` (2): `obtener_pasajero_por_dni` arma',
            '  historial iterando dueños. Requiere ver el código.',
            '- `ViajeAsientos.php` (3): resuelven terminal/dueño en',
            '  flujos sin contexto.',
            '- `Venta.php` (2): `_buscar_venta_por_id` sin contexto y',
            '  `obtener_cancelacion_por_id`.',
            '- `Impresion.php` (2): resuelven dueño/viaje para armar',
            '  la impresión.',
            '- `Vehiculo.php` (6): las 6 funciones buscan la empresa',
            '  entre todos los dueños. El más grande.',
            '- `Migraciones/Funciones.php` (2): `detectar_*` que pueden',
            '  reescribirse con `Nodo::por_cada_nodo_ejecutar`.',
            '- `Migraciones/Comandos.php` (1): idem.',
            '',
            '**Sub-tandas propuestas (de menor a mayor riesgo):**',
            '',
            '- **B3.3a:** `Liquidacion.php` + `Rendicion.php`. Cambio',
            '  directo a `us_<nombre>` en cada resolución.',
            '- **B3.3b:** `Pasajero.php` + `ViajeAsientos.php`. Casos',
            '  sueltos, requieren ver el código para saber qué',
            '  contexto hay disponible.',
            '- **B3.3c:** `Venta.php` + `Impresion.php`. Requieren',
            '  decisión de diseño: ¿contexto o iteración más acotada?',
            '- **B3.3d:** `Vehiculo.php`. El más grande. Puede requerir',
            '  cambios en el Enrutador para pasar el dueño.',
            '- **B3.3e:** `Migraciones/Funciones.php` + `Comandos.php`.',
            '  Reescribir con `por_cada_nodo_ejecutar`.',
            '',
            '**Usos legítimos que NO se tocan:**',
            '',
            '- `Autenticacion.php` (4): busca por `codigo_hash` en',
            '  credenciales, itera por todos.',
            '- `GrafoCredenciales.php` (2): helper del grafo de',
            '  credenciales.',
            '- `index.php` (4): crea la raíz y el admin al arrancar.',
            '- `Usuario.php`: `listar_usuarios`, `listar_duenos`,',
            '  `listar_soportes`, `buscar_usuario_por_codigo`.',
            '- `Controlador.php` (1): es del framework. Se aborda en',
            '  una tanda del framework, no acá.',
            '- `miscelaneas/migrar_usuarios_especiales.php` (1):',
            '  migración manual que recorre el grafo.',
            '',
            '**Anterior:** v77r (B3.1: aislación de usuarios, sin el fix).',
            'Antes: v77q (Tanda A, B2.4.2 + B2.4.5).',
        ],
    ],

    // ============================================================
    // 4. prompt_piloto.md — agregar v77s
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v77s',
        'buscar' => [
            '- **v77r** (Fase B3.1): aislación de usuarios. Reemplazo',
        ],
        'reemplazar' => [
            '- **v77s**: fix del `$raiz` residual en `eliminar_usuario`',
            '  (Usuario.php). Al cambiar el bloque por ID especial en',
            '  v77r se borró la declaración, pero quedó un',
            '  `$raiz->eliminar_adyacente($nombre_usuario)` al final.',
            '  Se resuelve la raíz solo en ese punto.',
            '- **v77r** (Fase B3.1): aislación de usuarios. Reemplazo',
        ],
    ],

    // ============================================================
    // 5. index.php — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump a 1.5piloto.77s',
        'buscar' => [
            ' * @version   1.5piloto.77r',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.77s',
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
echo "[INFO] $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s).\n\n";
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