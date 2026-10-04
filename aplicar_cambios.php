<?php
/**
 * Aplicador de cambios automáticos — proyecto Iteradores (piloto PHP).
 *
 * Tanda v1.5piloto.74h: cierre de auditoría.
 *
 * - Fix del autocompletado por DNI cuando el usuario es terminal y
 *   no hay viaje seleccionado (modal de alta de pasajero desde la
 *   pestaña Clientes). El dueño se resuelve desde `usuario_actual.dueno`
 *   como fallback.
 * - Bump de `?v=` de `ventas.js` en `aplicacion_GET.html` (estaba en
 *   `.73x`, corresponde `.74e`).
 * - Correcciones al `prompts/prompt_piloto.md`: eliminar referencias
 *   a archivos que ya no existen, marcar como implementados varios
 *   ítems que figuraban como pendientes, y agregar entrada al
 *   historial.
 *
 * Uso:
 *   php aplicar_cambios.php
 *
 * Si PHP no está en el PATH del sistema:
 *   C:\xampp8\php\php.exe aplicar_cambios.php
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

    // --------------------------------------------------------
    // Aplicacion/ventas.js — fix del autocompletado por DNI
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'Fix: resolver dueno en _buscar_pasajero_por_dni con fallback a usuario_actual.dueno',
        'buscar' => [
            "    _mostrar_aviso_en_formulario(index, 'Buscando...', 'gris');",
            "",
            "    const nombre_dueno = (usuario_actual.nivel === 'terminal')",
            "        ? viaje_seleccionado.dueno",
            "        : obtener_nombre_dueno_actual();",
        ],
        'reemplazar' => [
            "    _mostrar_aviso_en_formulario(index, 'Buscando...', 'gris');",
            "",
            "    // Resolver el dueno del pasajero. Desde el modal de venta el",
            "    // viaje esta seleccionado; desde el modal de alta de Clientes",
            "    // (que no siempre tiene viaje) se usa usuario_actual.dueno.",
            "    // Sin este fallback, la busqueda fallaba cuando un terminal",
            "    // abria el modal desde la pestana Clientes sin haber",
            "    // seleccionado un viaje.",
            "    const nombre_dueno = (usuario_actual.nivel === 'terminal')",
            "        ? ((viaje_seleccionado && viaje_seleccionado.dueno) ? viaje_seleccionado.dueno : (usuario_actual.dueno || ''))",
            "        : obtener_nombre_dueno_actual();",
        ],
    ],

    // --------------------------------------------------------
    // Aplicacion/ventas.js — bump de version
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/ventas.js',
        'descripcion' => 'Bump @version a 1.5piloto.74e',
        'buscar' => [
            " * Funciones de venta, confirmación, listado y cancelación.",
            " * @version 1.5piloto.74d",
        ],
        'reemplazar' => [
            " * Funciones de venta, confirmación, listado y cancelación.",
            " * @version 1.5piloto.74e",
        ],
    ],

    // --------------------------------------------------------
    // aplicacion_GET.html — bump del ?v= de ventas.js
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'Bump ?v= de ventas.js a 1.5piloto.74e',
        'buscar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.73x"></script>',
        ],
        'reemplazar' => [
            '<script src="Aplicacion/ventas.js?v=1.5piloto.74e"></script>',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md — §5.15: GuardarAmbos → FuncionesAuxiliares
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§5.15: eliminar referencia a GuardarAmbos.php',
        'buscar' => [
            '- Requiere `Aplicacion/GuardarAmbos.php` antes que todo lo demás.',
        ],
        'reemplazar' => [
            '- Requiere `Aplicacion/FuncionesAuxiliares.php` (que define',
            '  `guardar_ambos`) antes que todo lo demás.',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md — §8.2: eliminar migrar_pasajeros
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.2: eliminar linea sobre migrar_pasajeros.php',
        'buscar' => [
            '`migrar_pasajeros.php` se conserva: es histórico y no vale la pena',
            'migrarlo. No se toca.',
        ],
        'reemplazar' => [
            '(No quedan archivos `migrar_*.php` en el proyecto.)',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md — §8.5: actualizar lista de "Otros"
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.5: reescribir lista de Otros + agregar Implementados',
        'buscar' => [
            '**Otros**:',
            '',
            '- Panel de super admin.',
            '- Autocompletado de pasajeros por DNI en el alta desde la pestaña',
            '  Clientes.',
            '- `boton_reiniciar_numeracion`: agregarlo al HTML.',
            '- `ver_compra_asiento`: modal con el detalle completo.',
            '- Métricas / reportes adicionales.',
        ],
        'reemplazar' => [
            '**Otros**:',
            '',
            '- Panel de super admin (incremental al admin actual; alcance a',
            '  consensuar).',
            '- Métricas / reportes adicionales: solo ocupación por viaje y',
            '  consolidado de liquidaciones.',
            '',
            '**Implementados (ya no son pendientes):**',
            '',
            '- Autocompletado de pasajeros por DNI en el alta desde la pestaña',
            '  Clientes: los listeners de `conectar_listeners_formulario_pasajero`',
            '  ya autocompletan. Fix v74h: cuando el usuario es terminal y no',
            '  hay viaje seleccionado, el dueño se resuelve desde',
            '  `usuario_actual.dueno`.',
            '- `boton_reiniciar_numeracion`: existe como botón dinámico dentro',
            '  del modal de "números de asiento duplicados"',
            '  (`mostrar_aviso_numeros_duplicados`). No se agrega al HTML',
            '  estático.',
            '- `ver_compra_asiento`: implementado como navegación a la pestaña',
            '  Vendidos con resaltado (vía `ir_a_venta_en_vendidos`). No es',
            '  modal.',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md — §11: quitar GuardarAmbos
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§11: quitar GuardarAmbos.php de archivos pasables',
        'buscar' => [
            '- `Aplicacion/GrafoCredenciales.php`, `Aplicacion/GuardarAmbos.php`.',
        ],
        'reemplazar' => [
            '- `Aplicacion/GrafoCredenciales.php`.',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md — §7: agregar entrada v74h
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§7: agregar v74h al historial',
        'buscar' => [
            '- **v74g**: solo documentación. Se registran los bugs del',
            '  piloto detectados por las pruebas automáticas del plugin',
            '  (`iteradoresJS/`): v74d (refresco del croquis tras cancelar',
            '  venta), v74e (condición de carrera entre el polling de',
            '  asientos y el clic), v74f (modal del viaje abierto al',
            '  cambiar de pestaña). Se aclara que el plugin es un',
            '  proyecto independiente en `iteradoresJS/` con su propio',
            '  prompt en `iteradoresJS/prompts/prompt_plugin_piloto.md`.',
        ],
        'reemplazar' => [
            '- **v74h**: cierre de auditoría de pendientes. Fix del',
            '  autocompletado por DNI cuando el usuario es terminal y no',
            '  hay viaje seleccionado (modal de alta de pasajero desde',
            '  la pestaña Clientes): el dueño se resuelve desde',
            '  `usuario_actual.dueno` como fallback. Bump de `?v=` de',
            '  `ventas.js` en `aplicacion_GET.html` (estaba en `.73x`).',
            '  Correcciones al prompt: rehash automático y limpieza de',
            '  migraciones ya estaban implementados;',
            '  `boton_reiniciar_numeracion` y `ver_compra_asiento`',
            '  también; el autocompletado por DNI desde Clientes ya',
            '  funciona; `migrar_pasajeros.php` ya no existe;',
            '  `GuardarAmbos.php` fue eliminado en v73k.',
            '- **v74g**: solo documentación. Se registran los bugs del',
            '  piloto detectados por las pruebas automáticas del plugin',
            '  (`iteradoresJS/`): v74d (refresco del croquis tras cancelar',
            '  venta), v74e (condición de carrera entre el polling de',
            '  asientos y el clic), v74f (modal del viaje abierto al',
            '  cambiar de pestaña). Se aclara que el plugin es un',
            '  proyecto independiente en `iteradoresJS/` con su propio',
            '  prompt en `iteradoresJS/prompts/prompt_plugin_piloto.md`.',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md — §12: bloque "Última actualización"
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: actualizar bloque Ultima actualizacion del prompt',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74g (solo',
            'documentación: se registran los bugs del piloto detectados',
            'por las pruebas automáticas del plugin en `iteradoresJS/`,',
            'y se aclara la relación con el proyecto plugin).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74h (fix del',
            'autocompletado por DNI cuando el usuario es terminal y no hay viaje',
            'seleccionado; el dueño se resuelve desde `usuario_actual.dueno`.',
            'Bump de `?v=` de `ventas.js` en `aplicacion_GET.html`. Corrección',
            'de documentación: rehash automático y limpieza de migraciones',
            'ya están implementados, `boton_reiniciar_numeracion` y',
            '`ver_compra_asiento` también; `migrar_pasajeros.php` ya no existe;',
            '`GuardarAmbos.php` fue eliminado en v73k).',
            'Antes: v1.5piloto.74g (solo',
            'documentación: se registran los bugs del piloto detectados',
            'por las pruebas automáticas del plugin en `iteradoresJS/`,',
            'y se aclara la relación con el proyecto plugin).',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md — §12: eliminar rehash y migraciones
    //                        de "Decisiones abiertas"
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: quitar rehash y migraciones de Decisiones abiertas',
        'buscar' => [
            '**Decisiones abiertas / temas pendientes sin consensuar:**',
            '',
            '- **Rehash automático**: lo mencionamos como parte de la Tanda C pero',
            '  quedó fuera de la implementación. No se agregó el chequeo',
            '  `password_needs_rehash`. Se puede agregar en un bloque chico dentro',
            '  de `_registrar_login_exitoso`.',
            '- **Limpieza de migraciones**: hay varias tandas de migración que se',
            '  pueden eliminar cuando se confirmen en los 3 entornos. Ver sección',
            '  8.2.',
            '- **Diversificación por tipo de aplicación**: próximo gran frente. Ya',
            '  hay un diseño inicial consensuado (nodo `tipos_de_aplicacion`, enlace',
            '  `tipo_app` en el dueño). Falta ver el código antes de arrancar.',
        ],
        'reemplazar' => [
            '**Decisiones abiertas / temas pendientes sin consensuar:**',
            '',
            '- **Diversificación por tipo de aplicación**: próximo gran frente. Ya',
            '  hay un diseño inicial consensuado (nodo `tipos_de_aplicacion`, enlace',
            '  `tipo_app` en el dueño). Falta ver el código antes de arrancar.',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md — §12: agregar cierre de v74h en
    //                        "Estado de la conversación"
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar cierre de v74h a Estado de la conversacion',
        'buscar' => [
            '- No hay tandas de código en curso en este proyecto.',
        ],
        'reemplazar' => [
            '- Cerramos en v74h la tanda chica de cierre: fix del autocompletado',
            '  por DNI para terminal sin viaje seleccionado, bump de `?v=` de',
            '  `ventas.js` en `aplicacion_GET.html`, y corrección de contradicciones',
            '  en este prompt (rehash, migraciones, botones, autocompletado,',
            '  `GuardarAmbos.php`, `migrar_pasajeros.php`).',
            '- No hay tandas de código en curso en este proyecto.',
        ],
    ],

    // --------------------------------------------------------
    // prompts/prompt_piloto.md — §13: actualizar estado al cierre
    // --------------------------------------------------------

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: actualizar estado del proyecto al cierre',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74g (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. No hay bugs de prioridad',
            'alta pendientes. El segundo piloto (plugin de Chrome sobre el',
            'framework Iteradores JS) tiene el diseño cerrado; el código del',
            'plugin se escribe en la próxima tanda, en el proyecto',
            '`iteradoresJS/`.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74h (framework 1.5i.7f).',
            'Todo funcional. Bug 1 y Bug 2 resueltos. Auditoría de pendientes',
            'cerrada: los ítems listados como "abiertos" en prompts anteriores',
            '(rehash automático, limpieza de migraciones,',
            '`boton_reiniciar_numeracion`, `ver_compra_asiento`, autocompletado',
            'por DNI desde Clientes) ya estaban implementados o fueron',
            'corregidos. Fix v74h: el autocompletado por DNI usa',
            '`usuario_actual.dueno` como fallback cuando el usuario es terminal',
            'y no hay viaje seleccionado. El segundo piloto (plugin de Chrome',
            'sobre el framework Iteradores JS) tiene el esqueleto armado y',
            'funcional, con 17 pruebas corriendo.',
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