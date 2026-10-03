<?php
/**
 * Aplicador de cambios automáticos — Piloto agencia de viajes.
 *
 * Tanda v1.5piloto.74c — solo documentación.
 *
 * Registra en los prompts el arranque formal del proyecto plugin
 * (manifest en la raíz de iteradoresJS/, estructura A, decisiones
 * sobre MV3, nombre de versión v1.5plugin.0). No toca código.
 *
 * Uso:
 *   php aplicar_cambios.php
 *
 * Si PHP no está en el PATH del sistema:
 *   C:\xampp\php\php.exe aplicar_cambios.php
 */

// ============================================================
// Configuración
// ============================================================

$modo_estricto = true;
$raiz_proyecto = __DIR__;

// ============================================================
// Cambios a aplicar
// ============================================================

$cambios = [

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: ultima actualizacion a v74c',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74b. Se dejó',
            'asentado el arranque de un segundo piloto: un plugin de Chrome',
            '(manifest v3) que usa el framework Iteradores JS y corre pruebas',
            'automatizadas sobre la página del piloto PHP. Vive dentro del',
            'proyecto `iteradoresJS/`, en una carpeta `Aplicacion/`. Tiene su',
            'propia carpeta `prompts/` (con un único archivo por ahora,',
            '`prompt_plugin_piloto.md`). Los scripts de aplicación de cambios',
            'se ejecutan con el mismo flujo, parados en el directorio del',
            'proyecto `iteradoresJS/`. El prompt del framework Iteradores y',
            'este prompt siguen viviendo en el proyecto PHP.',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74c. Se',
            'terminó de consensuar el diseño del plugin de Chrome: manifest',
            'en la raíz de `iteradoresJS/`, código en `Aplicacion/`,',
            'persistencia con el framework Iteradores JS vía IndexedDB,',
            'pruebas declarativas que el popup lista y dispara. La versión',
            'del plugin arranca en v1.5plugin.0 (prefijo distinto al del',
            'piloto PHP). El asistente ya leyó el framework JS: `Objeto`,',
            '`Nodo`, `Iterador`, `Controlador`, `Entorno`, `Conf`,',
            '`PerdurarSuperestructuraStringIndexedDB` y `Comando`.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: agregar v74c al historial',
        'buscar' => [
            '  al guardar opciones: con la config vieja si no se tildó el',
            '  check, con la nueva si se tildó. Backend y frontend.',
        ],
        'reemplazar' => [
            '  al guardar opciones: con la config vieja si no se tildó el',
            '  check, con la nueva si se tildó. Backend y frontend.',
            '- **v74c**: solo documentación. Se formalizó el arranque del',
            '  proyecto plugin (segundo piloto). Decisiones tomadas:',
            '  manifest en la raíz de `iteradoresJS/` y código en',
            '  `Aplicacion/` (opción A), versión `v1.5plugin.0` para el',
            '  plugin, persistencia con `PerdurarSuperestructuraStringIndexedDB`,',
            '  salida en modo consola dentro del service worker (evita los',
            '  caminos que tocan `document`), content script clásico',
            '  (sin imports) comunicado por `chrome.runtime.sendMessage`,',
            '  pruebas declarativas con objeto `{id, nombre, ejecutar(ctx)}`.',
            '  El asistente leyó el framework JS (`Objeto`, `Nodo`,',
            '  `Iterador`, `Controlador`, `Entorno`, `Conf`, persistencia,',
            '  `Comando`). Se aclaró que `MOTOR_MAX_CICLOS` son ciclos',
            '  totales del motor, no comandos por ciclo ni ciclos por',
            '  minuto. El token de seguridad no lo maneja el plugin:',
            '  todo pasa por `Controlador.ejecutar_prueba(cb)`.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado de la conversacion v74c',
        'buscar' => [
            '- Pendiente: ver los archivos del framework JS para diseñar el',
            '  plugin (estructura, pruebas iniciales, sistema de persistencia).',
            '- No hay tandas de código en curso.',
        ],
        'reemplazar' => [
            '- Se leyeron los archivos clave del framework JS: `Objeto`,',
            '  `Nodo`, `Iterador`, `Controlador`, `Entorno`, `Conf`,',
            '  `PerdurarSuperestructuraStringIndexedDB` y `Comando`.',
            '  El framework está más avanzado que el espejo PHP: tiene',
            '  sistema de comandos, motor con péndulo, dominios, reloj',
            '  astronómico, tálamo y señal. El plugin no los usa en su',
            '  primera versión, pero quedan disponibles.',
            '- Cerramos en v74c el diseño del plugin. Decisiones: manifest',
            '  en la raíz de `iteradoresJS/`, código en `Aplicacion/`,',
            '  persistencia IndexedDB, content script clásico, salida en',
            '  modo consola en el service worker, nombre de versión',
            '  `v1.5plugin.0`. La próxima tanda es la creación del',
            '  esqueleto del plugin (manifest + SW + content + popup +',
            '  bootstrap + ConfPlugin + GrafoPlugin + primera prueba',
            '  smoke).',
            '- Pendiente: escribir el código del plugin. El prompt del',
            '  plugin vive en `iteradoresJS/prompts/prompt_plugin_piloto.md`',
            '  y se creó en esta misma tanda.',
            '- No hay tandas de código en curso en este proyecto.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt piloto: estado del proyecto al cierre v74c',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74b (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. No hay bugs de prioridad',
            'alta pendientes. Arranca el diseño del segundo piloto: plugin de',
            'Chrome sobre el framework Iteradores JS, para automatizar pruebas.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74c (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. No hay bugs de prioridad',
            'alta pendientes. El segundo piloto (plugin de Chrome sobre el',
            'framework Iteradores JS) tiene el diseño cerrado; el código del',
            'plugin se escribe en la próxima tanda, en el proyecto',
            '`iteradoresJS/`.',
        ],
    ],

    // ============================================================
    // prompts/prompt_sistema_scripts.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'prompt scripts: ampliar nota del proyecto plugin',
        'buscar' => [
            '**Proyecto nuevo: plugin de Chrome sobre Iteradores JS.**',
            '',
            'A partir de v1.5piloto.74b arranca un segundo piloto: un plugin',
            'de Chrome (manifest v3) que usa el framework Iteradores JS para',
            'persistir su propia info y corre pruebas automatizadas sobre la',
            'página del piloto PHP. Vive dentro del proyecto `iteradoresJS/`,',
            'en una carpeta `Aplicacion/`. Tiene su propia carpeta `prompts/`',
            'con un único archivo por ahora: `prompt_plugin_piloto.md`.',
            '',
            'Los scripts de aplicación de cambios se ejecutan con el mismo',
            'flujo y el mismo runner, pero parados en el directorio del',
            'proyecto `iteradoresJS/`. Los prompts del framework y del',
            'sistema de scripts siguen viviendo en el proyecto PHP.',
        ],
        'reemplazar' => [
            '**Proyecto nuevo: plugin de Chrome sobre Iteradores JS.**',
            '',
            'A partir de v1.5piloto.74b arranca un segundo piloto: un plugin',
            'de Chrome (manifest v3) que usa el framework Iteradores JS para',
            'persistir su propia info y corre pruebas automatizadas sobre la',
            'página del piloto PHP. Vive dentro del proyecto `iteradoresJS/`,',
            'en una carpeta `Aplicacion/`. Tiene su propia carpeta `prompts/`',
            'con un único archivo por ahora: `prompt_plugin_piloto.md`.',
            '',
            '**Estructura y decisiones (v1.5piloto.74c):**',
            '',
            '- **Manifest en la raíz de `iteradoresJS/`** (no en',
            '  `Aplicacion/`): MV3 exige que `manifest.json` esté en la',
            '  raíz del directorio cargado como extensión. El código del',
            '  plugin vive en `Aplicacion/` y usa paths relativos a la raíz.',
            '  Esto permite importar `../Nodos/Nodo.js` y el resto del',
            '  framework sin duplicar archivos.',
            '- **Nombre de versión del plugin:** `v1.5plugin.0` para el',
            '  arranque, y luego `v1.5plugin.1`, `.2`, `74a`, etc. con el',
            '  mismo criterio que el piloto PHP. Prefijo distinto para no',
            '  confundir con `v1.5piloto.*`.',
            '- **Persistencia:** `PerdurarSuperestructuraStringIndexedDB`.',
            '  El plugin no maneja el token: usa `Controlador.guardar`,',
            '  `Controlador.cargar`, `Controlador.existe` (todas async) y',
            '  `Controlador.ejecutar_prueba(cb)` cuando necesita el token.',
            '- **Service worker con salida en modo consola.** Los caminos',
            '  HTML del framework (`_imprimir_errores_html`, `html_errores`,',
            '  `_imprimir_alertas_html`, `html_alertas`,',
            '  `Nodo._imprimir_html`, `Controlador.imprimir_superestructura`)',
            '  tocan `document`, que no existe en el service worker. Se',
            '  evitan asegurando que `Entorno.es_consola()` devuelva `true`',
            '  al arrancar el SW.',
            '- **Content script clásico.** Los content scripts de MV3 no',
            '  pueden importar módulos ES. Se comunican con el SW por',
            '  `chrome.runtime.sendMessage` / `chrome.tabs.sendMessage`.',
            '  El SW (module) importa el framework y las pruebas.',
            '- **Formato de prueba:** objeto `{id, nombre, descripcion,',
            '  ejecutar(ctx)}`. `ctx` incluye helpers para hablar con la',
            '  página (`click`, `esperar`, `fetch`) y un `assert`. El',
            '  resultado se persiste en el grafo del plugin.',
            '- **Motor (comandos + péndulo):** no se usa en la primera',
            '  versión. `MOTOR_MAX_CICLOS` son ciclos totales del motor,',
            '  no comandos por ciclo ni ciclos por minuto. Con los defaults',
            '  (`MOTOR_MAX_CICLOS=2`, `MOTOR_QUANTUM=20`,',
            '  `MOTOR_CICLOS_POR_MINUTO=20`), el motor corre 2 ciclos de',
            '  20 comandos cada 3 segundos y se detiene. Se puede usar',
            '  más adelante si hace falta ejecución por fases.',
            '',
            'Los scripts de aplicación de cambios se ejecutan con el mismo',
            'flujo y el mismo runner, pero parados en el directorio del',
            'proyecto `iteradoresJS/`. Los prompts del framework y del',
            'sistema de scripts siguen viviendo en el proyecto PHP.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'prompt scripts: ultima actualizacion a v74c',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73v. Se ajustó',
            'la regla de vigencia de archivos: durante la conversación, el',
            'asistente debe asumir que los archivos ya pasados están vigentes',
            'y no volver a pedirlos. Solo se piden archivos que nunca',
            'aparecieron en el hilo. La sección "APRENDIZAJES A LA FUERZA',
            '(SISTEMA DE SCRIPTS)" se mantiene tal cual.',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74c. Se',
            'amplió la nota del proyecto plugin con las decisiones de',
            'estructura (manifest en la raíz de `iteradoresJS/`, versión',
            '`v1.5plugin.0`, salida consola en el SW, content script',
            'clásico, formato de prueba). Sigue vigente la regla de',
            'vigencia de archivos de v74b: el asistente asume que los',
            'archivos ya pasados están vigentes.',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios ===\n\n";

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

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado (faltan campos).\n";
        exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }

echo "[INFO] " . count($creaciones) . " archivo(s) a crear, "
    . $total_reemplazos . " reemplazo(s) en "
    . count($reemplazos_por_archivo) . " archivo(s), "
    . count($eliminaciones) . " archivo(s) a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}";
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
            $bloques_fallidos[] = "$archivo_rel: bloque ambiguo ($ocurrencias ocurrencias) - {$cambio['descripcion']}";
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
    echo "Se detectaron " . count($bloques_fallidos) . " problema(s). No se escribió ningún archivo.\n\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    echo "\nSugerencia: revisá que el bloque a buscar coincida exactamente con el archivo actual.\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

foreach ($creaciones as $creacion) {
    $ruta_abs = $raiz_proyecto . '/' . $creacion['archivo'];
    $dir_destino = dirname($ruta_abs);
    if (!is_dir($dir_destino)) mkdir($dir_destino, 0777, true);
    $contenido_nuevo = implode("\n", $creacion['contenido']);
    $ya_existia = file_exists($ruta_abs);
    if (file_put_contents($ruta_abs, $contenido_nuevo) === false) {
        echo "[FALLO] No se pudo crear: {$creacion['archivo']}\n"; continue;
    }
    $accion = $ya_existia ? 'sobrescrito' : 'creado';
    echo "[OK] {$creacion['archivo']} ($accion)\n";
}

foreach ($eliminaciones as $elim) {
    $ruta_abs = $raiz_proyecto . '/' . $elim['archivo'];
    if (!file_exists($ruta_abs)) {
        echo "[INFO] " . $elim['archivo'] . " no existía (nada que eliminar).\n";
        continue;
    }
    if (unlink($ruta_abs)) {
        echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    } else {
        echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
    }
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";