<?php
/**
 * Aplicador de cambios automáticos — Administrador de Viajes.
 *
 * v1.5piloto.73i: modal "Mis datos" al tocar el nombre del usuario.
 *
 * Uso:
 *   php aplicar_cambios.php
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

    // ==========================================================
    // Usuario.php — bump y nueva función obtener_perfil_usuario
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: bump de version',
        'buscar' => [
            ' * @version   1.5piloto.73b',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.73i',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: agregar obtener_perfil_usuario al final',
        'buscar' => [
            '    return eliminar_usuario($nombre_usuario);',
            '}',
        ],
        'reemplazar' => [
            '    return eliminar_usuario($nombre_usuario);',
            '}',
            '',
            '/**',
            ' * Devuelve los datos del propio usuario (perfil).',
            ' *',
            ' * Reutiliza `_formatear_usuario_para_admin` para no duplicar la',
            ' * lógica de armado. Solo lee, no modifica nada. Sirve para que',
            ' * cualquier rol vea sus propios datos sin necesitar permisos',
            ' * de administrador.',
            ' *',
            ' * @param string $nombre_usuario Nombre del usuario.',
            ' * @return array|null Datos del perfil, o null si no existe.',
            ' */',
            'function obtener_perfil_usuario(string $nombre_usuario): ?array {',
            '    $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz) return null;',
            '    $nodo_usuario = $raiz->adyacente($nombre_usuario);',
            '    if (!$nodo_usuario) return null;',
            '',
            '    // Mapa de usuarios con código (desde credenciales).',
            '    $con_codigo = en_grafo_credenciales(function() {',
            '        $raiz_cred = Nodo::nodo_por_id(\'usuarios\');',
            '        if (!$raiz_cred) return [];',
            '        $mapa = [];',
            '        foreach ($raiz_cred->adyacentes() as $nombre => $nodo) {',
            '            $mapa[(string)$nombre] = $nodo->adyacente(\'codigo_hash\') ? true : false;',
            '        }',
            '        return $mapa;',
            '    });',
            '',
            '    return _formatear_usuario_para_admin($nombre_usuario, $nodo_usuario, $con_codigo);',
            '}',
        ],
    ],

    // ==========================================================
    // Enrutador.php — bump y nuevo módulo usuarios/mi_perfil
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: bump de version',
        'buscar' => [
            ' * @version   1.5piloto.73',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.73i',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Enrutador.php: modulo usuarios/mi_perfil',
        'buscar' => [
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de cancelaciones no válida\']);',
            '            }',
            '            break;',
            '',
            '        default:',
            '            responder_json([\'exito\' => false, \'error\' => \'Módulo no reconocido\']);',
            '    }',
            '}',
        ],
        'reemplazar' => [
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de cancelaciones no válida\']);',
            '            }',
            '            break;',
            '',
            '        case \'usuarios\':',
            '            switch ($subaccion) {',
            '                case \'mi_perfil\':',
            '                    if ($nombre_solicitante === \'\') {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Sin sesión activa\']);',
            '                    }',
            '                    $perfil = obtener_perfil_usuario($nombre_solicitante);',
            '                    if ($perfil) {',
            '                        responder_json([\'exito\' => true, \'perfil\' => $perfil]);',
            '                    } else {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Usuario no encontrado\']);',
            '                    }',
            '                    break;',
            '',
            '                default:',
            '                    responder_json([\'exito\' => false, \'error\' => \'Subacción de usuarios no válida\']);',
            '            }',
            '            break;',
            '',
            '        default:',
            '            responder_json([\'exito\' => false, \'error\' => \'Módulo no reconocido\']);',
            '    }',
            '}',
        ],
    ],

    // ==========================================================
    // aplicacion.js — bump, listener y funciones del modal
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: bump de version',
        'buscar' => [
            ' * @version 1.5piloto.73h',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.73i',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: listener del nombre del usuario',
        'buscar' => [
            '$("#boton_salir").addEventListener("click", salir);',
        ],
        'reemplazar' => [
            '$("#boton_salir").addEventListener("click", salir);',
            '',
            '// Click en el nombre de usuario: abre el modal "Mis datos".',
            '$("#nombre_usuario_actual")?.addEventListener("click", abrir_modal_mi_perfil);',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion.js',
        'descripcion' => 'aplicacion.js: funciones del modal Mi datos',
        'buscar' => [
            '    } else {',
            '        mostrar_aviso(resultado.error || "Error al crear usuario", \'error\');',
            '    }',
            '}',
        ],
        'reemplazar' => [
            '    } else {',
            '        mostrar_aviso(resultado.error || "Error al crear usuario", \'error\');',
            '    }',
            '}',
            '',
            '// ============================================================',
            '// ====== MODAL "MIS DATOS" ===================================',
            '// ============================================================',
            '',
            '/**',
            ' * Abre el modal con los datos del usuario actual. Lo puede usar',
            ' * cualquier rol. Solo lectura.',
            ' */',
            'function abrir_modal_mi_perfil() {',
            '    fetch("index.php", {',
            '        method: "POST",',
            '        headers: { "Content-Type": "application/x-www-form-urlencoded" },',
            '        body: new URLSearchParams({ accion: "usuarios/mi_perfil" })',
            '    })',
            '    .then(r => r.json())',
            '    .then(datos => {',
            '        if (!datos.exito) {',
            '            mostrar_aviso(datos.error || "No se pudo cargar el perfil", \'error\');',
            '            return;',
            '        }',
            '        _renderizar_modal_mi_perfil(datos.perfil);',
            '    })',
            '    .catch(e => {',
            '        console.error("Error al cargar mi perfil:", e);',
            '        mostrar_aviso("Error de comunicación", \'error\');',
            '    });',
            '}',
            '',
            '/**',
            ' * Arma y muestra el HTML del modal "Mis datos" a partir del',
            ' * perfil recibido del backend.',
            ' *',
            ' * @param {object} p Datos del perfil.',
            ' */',
            'function _renderizar_modal_mi_perfil(p) {',
            '    const filas = [];',
            '    filas.push([\'Usuario\', p.nombre_usuario || \'—\']);',
            '    filas.push([\'Nivel\', p.nivel || \'—\']);',
            '    if (p.nombre_real) filas.push([\'Nombre real\', p.nombre_real]);',
            '    if (p.email) filas.push([\'Email\', p.email]);',
            '    filas.push([\'Código de acceso\', p.codigo_asignado ? \'Asignado\' : \'—\']);',
            '    filas.push([\'Efectivo\', \'$\' + (p.efectivo || \'0\')]);',
            '    filas.push([\'Bancarizado\', \'$\' + (p.bancarizado || \'0\')]);',
            '    if (p.banco && p.banco.nombre) filas.push([\'Banco\', p.banco.nombre]);',
            '    if (p.banco && p.banco.cuenta) filas.push([\'Cuenta bancaria\', p.banco.cuenta]);',
            '    if (p.nivel === \'terminal\' && p.dueno) filas.push([\'Dueño\', p.dueno]);',
            '    if (p.nivel === \'soporte\' && Array.isArray(p.duenos)) {',
            '        filas.push([\'Dueños asignados\', p.duenos.length > 0 ? p.duenos.join(\', \') : \'—\']);',
            '    }',
            '',
            '    const filas_html = filas.map(f => `<tr><th>${f[0]}</th><td>${f[1]}</td></tr>`).join(\'\');',
            '',
            '    const html = `',
            '        <table class="perfil-tabla">',
            '            <tbody>',
            '                ${filas_html}',
            '            </tbody>',
            '        </table>',
            '        <div class="actions" style="margin-top:15px">',
            '            <button class="btn" id="perfil_cerrar_btn">Cerrar</button>',
            '        </div>',
            '    `;',
            '',
            '    abrir_modal_generico(\'Mis datos\', html);',
            '',
            '    document.getElementById(\'perfil_cerrar_btn\')?.addEventListener(\'click\', cerrar_modal_generico);',
            '}',
        ],
    ],

    // ==========================================================
    // estilos.css — estilos para el nombre y la tabla del modal
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'estilos.css',
        'descripcion' => 'estilos.css: nombre clickeable y tabla de perfil',
        'buscar' => [
            '/* Header del modal genérico: botón Volver + título a la izquierda, X a la derecha */',
            '.modal-header-izquierda {',
            '    display: flex;',
            '    align-items: center;',
            '    gap: 12px;',
            '    min-width: 0;',
            '}',
            '',
            '.modal-header-izquierda h3 {',
            '    margin: 0;',
            '}',
        ],
        'reemplazar' => [
            '/* Header del modal genérico: botón Volver + título a la izquierda, X a la derecha */',
            '.modal-header-izquierda {',
            '    display: flex;',
            '    align-items: center;',
            '    gap: 12px;',
            '    min-width: 0;',
            '}',
            '',
            '.modal-header-izquierda h3 {',
            '    margin: 0;',
            '}',
            '',
            '/* Nombre de usuario en el header: clickeable para ver "Mis datos". */',
            '#nombre_usuario_actual {',
            '    cursor: pointer;',
            '    text-decoration: underline dotted;',
            '    text-underline-offset: 3px;',
            '    transition: color var(--transition);',
            '}',
            '#nombre_usuario_actual:hover { color: var(--primary); }',
            '',
            '/* Tabla del modal "Mis datos". */',
            '.perfil-tabla {',
            '    width: 100%;',
            '    border-collapse: collapse;',
            '}',
            '.perfil-tabla th,',
            '.perfil-tabla td {',
            '    padding: 10px 12px;',
            '    text-align: left;',
            '    border-bottom: 1px solid var(--border);',
            '    vertical-align: top;',
            '}',
            '.perfil-tabla th {',
            '    font-weight: 700;',
            '    color: var(--primary-dark);',
            '    width: 180px;',
            '    white-space: nowrap;',
            '}',
            '.perfil-tabla tr:last-child th,',
            '.perfil-tabla tr:last-child td { border-bottom: 0; }',
        ],
    ],

    // ==========================================================
    // aplicacion_GET.html — bumps de version
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de estilos.css',
        'buscar' => [
            '<link rel="stylesheet" href="estilos.css?v=1.5piloto.65">',
        ],
        'reemplazar' => [
            '<link rel="stylesheet" href="estilos.css?v=1.5piloto.73i">',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_GET.html',
        'descripcion' => 'HTML: bump de aplicacion.js',
        'buscar' => [
            '<script src="aplicacion.js?v=1.5piloto.73h"></script>',
        ],
        'reemplazar' => [
            '<script src="aplicacion.js?v=1.5piloto.73i"></script>',
        ],
    ],

    // ==========================================================
    // prompts/prompt_piloto.md — bump y nota
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: bump discusion actual',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73h (limpieza',
            'de contenido dinámico al cambiar de sesión).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73i (modal',
            '"Mis datos" al tocar el nombre del usuario en el header).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v73i al historial',
        'buscar' => [
            '- **v73h**: fix de fuga de datos al cambiar de sesión. Se limpia',
            '  todo el contenido dinámico al salir y al ingresar. Además, el',
            '  soporte ya no recibe la lista completa de usuarios: la tabla se',
            '  llena solo con lo que dejó el selector de dueños.',
        ],
        'reemplazar' => [
            '- **v73h**: fix de fuga de datos al cambiar de sesión. Se limpia',
            '  todo el contenido dinámico al salir y al ingresar. Además, el',
            '  soporte ya no recibe la lista completa de usuarios: la tabla se',
            '  llena solo con lo que dejó el selector de dueños.',
            '- **v73i**: modal "Mis datos". Se puede tocar el nombre del usuario',
            '  en el header para ver el perfil propio. Nuevo endpoint',
            '  `usuarios/mi_perfil` (sin permisos especiales, devuelve los',
            '  datos del solicitante). Solo lectura.',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios (modal Mis datos) ===\n\n";

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
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
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
    . count($reemplazos_por_archivo) . " archivo(s).\n\n";

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
        $permitir_multiples = !empty($cambio['permitir_multiples']);

        if ($ocurrencias === 0) {
            $bloques_fallidos[] = "$archivo_rel: bloque no encontrado - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        if ($ocurrencias > 1 && !$permitir_multiples) {
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

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";