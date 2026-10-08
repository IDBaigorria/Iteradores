<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76p (solo documentación).
 *   - prompts/prompt_framework_iteradores.md: nueva sección
 *     §4.4 "Sistema de comandos" con todo el aprendizaje.
 *     Nueva lección en §13.
 *   - prompts/prompt_piloto.md: registro de cómo la app
 *     registra sus comandos.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // prompts/prompt_framework_iteradores.md — nueva §4.4
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'framework: agregar §4.4 Sistema de comandos',
        'buscar' => [
            'El sistema de errores y alertas está en la clase `Objeto`, que es la raíz',
            'de la jerarquía. Guarda cada mensaje con la pila de llamadas. Tiene un',
            'límite de profundidad configurable en `Conf`.',
            '',
            '---',
            '',
            '## 5. HELPERS DE ÁRBOL (`miscelaneas/Arbol.php`)',
        ],
        'reemplazar' => [
            'El sistema de errores y alertas está en la clase `Objeto`, que es la raíz',
            'de la jerarquía. Guarda cada mensaje con la pila de llamadas. Tiene un',
            'límite de profundidad configurable en `Conf`.',
            '',
            '### 4.4 Sistema de comandos',
            '',
            'El framework tiene un sistema de comandos que permite',
            'registrar operaciones por nombre y ejecutarlas de forma',
            'uniforme. Cada comando es una función (o clase) que recibe',
            'el token de seguridad y los argumentos, y devuelve un',
            'resultado.',
            '',
            '**Registro.** Hay dos formas:',
            '',
            '- `Controlador::registrar_comando($nombre, $manejador, $reversa = null, $solo_desarrollo = false)`.',
            '  Registra un comando dinámico con un closure.',
            '- `Controlador::registrar_comando_desde_clase($clase)`.',
            '  Registra un comando que implementa la interfaz `Comando`.',
            '  La clase declara nombre, descripción, parámetros, ejemplos',
            '  y una función de reversa opcional.',
            '',
            '**Ejecución.** `Controlador::ejecutar_comando($nombre, ...$args)`.',
            'Devuelve lo que devuelva el manejador, o `null` si el',
            'comando no existe, no hay permiso, o los argumentos no',
            'validan.',
            '',
            '**Argumentos.** Si el comando tiene clase con método',
            '`parametros()`, los args se parsean y validan antes de',
            'invocar el manejador. La estructura que llega al manejador',
            'es `[\'posicionales\' => [...], \'banderas\' => [...],',
            '\'opciones\' => [...]]`. Si el comando no tiene definición',
            'de parámetros, los args llegan crudos como array numérico.',
            '',
            '**Reversa.** Cada comando puede declarar una función de',
            'reversa que se apila en `Controlador::$historial`. Se',
            'deshace con `Controlador::deshacer_ultimo()`.',
            '',
            '**Cuándo se registran.** El Controlador se autoinicializa',
            'al final de `Controlador.php` (llamada a',
            '`Controlador::inicializar()`). En `index.php`, los',
            '`require_once` de la app van DESPUÉS del de `Controlador.php`.',
            'Por lo tanto, cuando se cargan los archivos de la app, el',
            'Controlador YA está inicializado y se puede registrar',
            'comandos con `registrar_comando` directo.',
            '',
            '**`RegistroGlobal` para autoencolación.** Existe la clase',
            '`RegistroGlobal` con `$comandos_pendientes` y',
            '`$comunicadores_pendientes`, que el Controlador procesa',
            'durante `inicializar()`. Sirve para archivos que se cargan',
            'ANTES del Controlador (por ejemplo, los comandos que vienen',
            'en `Comandos/index.php`, que `index.php` incluye antes de',
            '`Controlador.php`). Los archivos de la app que se cargan',
            'DESPUÉS no deben usar `RegistroGlobal` (no tiene sentido:',
            'el Controlador ya procesó esa cola).',
            '',
            '**Regla práctica:**',
            '',
            '- Comandos del framework → van en `Controlador.php`, en',
            '  una sección `_registrar_comandos_*` invocada desde',
            '  `inicializar()`.',
            '- Comandos de la app → van en archivos de la app,',
            '  registrados con `registrar_comando` directo desde',
            '  `index.php` (después de los `require_once` de la app).',
            '  NO van en el `Controlador` del framework.',
            '- Comandos que se autoencolan → solo si se cargan antes',
            '  del Controlador, vía `RegistroGlobal::encolar_comando()`.',
            '',
            '**Espejo JS.** El `Controlador.js` del plugin tiene la',
            'misma API (`registrar_comando`, `ejecutar_comando`,',
            '`registrar_comando_desde_clase`, `registrar_comando_desde_instancia`).',
            'Se inicializa igual: auto-init al final del módulo. En MV3,',
            'desde la consola del service worker NO se pueden hacer',
            '`import()` dinámicos (prohibido por spec). Para debug',
            'desde el SW, exponer `globalThis.Controlador = Controlador`',
            'en el bootstrap.',
            '',
            '---',
            '',
            '## 5. HELPERS DE ÁRBOL (`miscelaneas/Arbol.php`)',
        ],
    ],

    // ============================================================
    // prompts/prompt_framework_iteradores.md — lección §13
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'framework: lección 24 en §13',
        'buscar' => [
            '**Regla de oro:** cualquier cambio al framework PHP se refleja en',
            'JS en la misma tanda, con dos scripts y dos commits.',
        ],
        'reemplazar' => [
            '**Comandos (lecciones sobre el registro):**',
            '',
            '24. **El Controlador se autoinicializa al final de',
            '    `Controlador.php`.** En `index.php`, los `require_once`',
            '    de la app van después. Consecuencia: los archivos de la',
            '    app pueden registrar comandos con `registrar_comando`',
            '    directo (el Controlador ya está listo). No deben usar',
            '    `RegistroGlobal::encolar_comando` (esa cola ya se',
            '    procesó).',
            '25. **Comandos del framework vs comandos de la app.** Los',
            '    del framework van en el `Controlador`. Los de la app',
            '    van en archivos de la app, registrados al vuelo desde',
            '    `index.php`. Un comando específico del piloto NO debe',
            '    vivir en el `Controlador` del framework: es deuda',
            '    técnica.',
            '26. **`RegistroGlobal` sirve solo para archivos que se',
            '    cargan antes del Controlador.** Por ejemplo, los',
            '    comandos de `Comandos/index.php`. Para archivos',
            '    posteriores, `registrar_comando` directo.',
            '27. **En MV3, `import()` dinámico está prohibido en el',
            '    `ServiceWorkerGlobalScope`.** Error: *"import() is',
            '    disallowed on ServiceWorkerGlobalScope by the HTML',
            '    specification"*. Para debug desde la consola del SW,',
            '    exponer `globalThis.Controlador = Controlador` en el',
            '    bootstrap. Los imports estáticos del bootstrap sí',
            '    funcionan.',
            '',
            '**Regla de oro:** cualquier cambio al framework PHP se refleja en',
            'JS en la misma tanda, con dos scripts y dos commits.',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — comandos de la app
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: §5.15 comandos de la app',
        'buscar' => [
            '### 5.15 `index.php`',
            '',
            '- Carga framework, persistencia (SQL principal), módulos de',
            '  `Aplicacion/`.',
            '- Requiere `Aplicacion/FuncionesAuxiliares.php` (que define',
            '  `guardar_ambos`) antes que todo lo demás.',
            '- Crea admin en ambos grafos si no existe.',
            '- Bloques temporales de migración.',
            '- Enrutado POST con `enrutar_peticion_post`.',
        ],
        'reemplazar' => [
            '### 5.15 `index.php`',
            '',
            '- Carga framework, persistencia (SQL principal), módulos de',
            '  `Aplicacion/`.',
            '- Requiere `Aplicacion/FuncionesAuxiliares.php` (que define',
            '  `guardar_ambos`) antes que todo lo demás.',
            '- Crea admin en ambos grafos si no existe.',
            '- Crea el nodo especial `aplicacion` con su contenedor',
            '  `migraciones` si no existe (v76n).',
            '- Registra los comandos de la app con',
            '  `registrar_comandos_migraciones()` (v76n). El Controlador',
            '  ya está inicializado cuando se cargan los `require_once`',
            '  de la app, así que el registro es directo con',
            '  `Controlador::registrar_comando(...)`.',
            '- Bloques temporales de migración.',
            '- Enrutado POST con `enrutar_peticion_post`.',
            '',
            '### 5.16 Comandos de la app',
            '',
            'Los comandos de la app viven en `Aplicacion/Migraciones/`',
            '(v76n) y se registran al vuelo desde `index.php` con',
            '`Controlador::registrar_comando(...)`, no desde el',
            '`Controlador` del framework.',
            '',
            '**Regla de convivencia:**',
            '',
            '- Comandos del framework (`grafo:*`, `comunicacion:*`,',
            '  `dominio:*`, etc.) → van en `Controlador.php`.',
            '- Comandos de la app (`app:migracion_*`, y en el futuro',
            '  los que correspondan a módulos de negocio) → van en',
            '  archivos bajo `Aplicacion/`, registrados desde',
            '  `index.php` después de los `require_once`.',
            '- Los comandos específicos del piloto que hoy están en el',
            '  `Controlador` del framework (por ejemplo',
            '  `grafo:crear_niveles_usuario`) son deuda técnica: hay',
            '  que moverlos a la app cuando se pueda.',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — historial v76p
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: historial v76p',
        'buscar' => [
            '- **v76o**: solo documentación. Se dejan asentados tres',
        ],
        'reemplazar' => [
            '- **v76p**: solo documentación. Se documenta todo el',
            '  aprendizaje sobre el sistema de comandos del framework:',
            '  nueva sección §4.4 en el prompt del framework (registro,',
            '  ejecución, argumentos, reversa, cuándo se registran,',
            '  `RegistroGlobal` para autoencolación, espejo JS). Cuatro',
            '  lecciones nuevas en §13 (autoinicialización del',
            '  Controlador, comandos del framework vs de la app,',
            '  `RegistroGlobal` solo para archivos previos al',
            '  Controlador, y la limitación de MV3 con `import()`',
            '  dinámico). Nueva §5.16 en el prompt del piloto con la',
            '  regla de convivencia entre comandos del framework y de',
            '  la app.',
            '- **v76o**: solo documentación. Se dejan asentados tres',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: §13 estado al cierre',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76o (framework 1.5i.7l).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76p (framework 1.5i.7l).',
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