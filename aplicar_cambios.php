<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.76m (Fase B1: contenedores publico/privado).
 *   - Controlador.php: nuevo comando grafo:crear_niveles_usuario.
 *   - index.php: bloque ?migrar_niveles_usuario=1.
 *   - miscelaneas/migrar_niveles_usuario.php (nuevo): la migración.
 *   - prompts/prompt_piloto.md: documentación.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // Controlador/Controlador.php — nuevo comando
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/Controlador.php',
        'descripcion' => 'Controlador: agregar comando grafo:crear_niveles_usuario',
        'buscar' => [
            '            return [\'reemplazos\' => $reemplazos];',
            '        }, null, false);',
            '    }',
        ],
        'reemplazar' => [
            '            return [\'reemplazos\' => $reemplazos];',
            '        }, null, false);',
            '',
            '        // ─── grafo:crear_niveles_usuario ──────────────────',
            '        //',
            '        // Crea los contenedores `publico` y `privado` como',
            '        // hijos de cada nodo usuario del grafo actualmente',
            '        // cargado, y enlaza desde ahí a los nodos hijos que',
            '        // ya existían. NO toca los enlaces viejos, así el',
            '        // código existente sigue funcionando (los contenedores',
            '        // son alias a los mismos nodos físicos).',
            '        //',
            '        // `publico` tiene como dato el nombre de usuario.',
            '        // Los enlaces que van a `publico`: nivel, nombre_real,',
            '        // email. Los que van a `privado`: el resto de los',
            '        // datos del usuario. Los enlaces "de permiso"',
            '        // (`dueno`, `soporte`, `duenos`) quedan en la raíz.',
            '        //',
            '        // Idempotente: si `publico` ya existe en un usuario,',
            '        // se saltea.',
            '        //',
            '        // Args: [\'usuario\' => nombre | \'todos\']',
            '        // Devuelve: { migrados: int, saltados: int, errores: [] }.',
            '        self::registrar_comando(\'grafo:crear_niveles_usuario\', function(string $token, array $args) {',
            '            $objetivo = (string)($args[0][\'usuario\'] ?? \'todos\');',
            '',
            '            $raiz = Nodo::nodo_por_id(\'usuarios\');',
            '            if (!$raiz) {',
            '                return [\'migrados\' => 0, \'saltados\' => 0, \'errores\' => [\'No existe el nodo usuarios.\']];',
            '            }',
            '            $adyacentes = $raiz->adyacentes();',
            '            if (!$adyacentes) {',
            '                return [\'migrados\' => 0, \'saltados\' => 0, \'errores\' => []];',
            '            }',
            '',
            '            // Enlaces que van a `publico`. El resto (salvo los',
            '            // de permiso) va a `privado`.',
            '            $enlaces_publicos = [\'nivel\', \'nombre_real\', \'email\'];',
            '            $enlaces_de_permiso = [\'dueno\', \'soporte\', \'duenos\'];',
            '',
            '            $migrados = 0;',
            '            $saltados = 0;',
            '            $errores = [];',
            '',
            '            foreach ($adyacentes as $nombre_enlace => $nodo_usuario) {',
            '                $nombre_enlace = (string)$nombre_enlace;',
            '                if ($objetivo !== \'todos\' && $nombre_enlace !== $objetivo) continue;',
            '',
            '                // Idempotencia: si ya tiene `publico`, se saltea.',
            '                if ($nodo_usuario->adyacente(\'publico\')) {',
            '                    $saltados++;',
            '                    continue;',
            '                }',
            '',
            '                // Datos previos: los adyacentes del nodo usuario.',
            '                $hijos = $nodo_usuario->adyacentes();',
            '',
            '                // Crear los dos contenedores.',
            '                $contenedor_publico = Nodo::crear_con_dato($nombre_enlace);',
            '                $contenedor_privado = Nodo::crear_con_dato(\'\');',
            '',
            '                $nodo_usuario->_adyacente_en($contenedor_publico, \'publico\');',
            '                $nodo_usuario->_adyacente_en($contenedor_privado, \'privado\');',
            '',
            '                if ($hijos) {',
            '                    foreach ($hijos as $enlace_hijo => $nodo_hijo) {',
            '                        $enlace_hijo = (string)$enlace_hijo;',
            '                        if ($enlace_hijo === \'publico\' || $enlace_hijo === \'privado\') continue;',
            '                        if (in_array($enlace_hijo, $enlaces_de_permiso, true)) continue;',
            '',
            '                        if (in_array($enlace_hijo, $enlaces_publicos, true)) {',
            '                            $contenedor_publico->_adyacente_en($nodo_hijo, $enlace_hijo);',
            '                        } else {',
            '                            $contenedor_privado->_adyacente_en($nodo_hijo, $enlace_hijo);',
            '                        }',
            '                    }',
            '                }',
            '',
            '                $migrados++;',
            '            }',
            '',
            '            return [\'migrados\' => $migrados, \'saltados\' => $saltados, \'errores\' => $errores];',
            '        }, null, false);',
            '    }',
        ],
    ],

    // ============================================================
    // index.php — bloque ?migrar_niveles_usuario
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump @version a 1.5piloto.76m',
        'buscar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76k',
        ],
        'reemplazar' => [
            ' * @since     1.0.0',
            ' * @version   1.5piloto.76m',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bloque ?migrar_niveles_usuario',
        'buscar' => [
            '// ==== Migración de usuarios a IDs especiales (v76k) ====',
        ],
        'reemplazar' => [
            '// ==== Migración de niveles de usuario (v76m) ====',
            '// Crea los contenedores `publico` y `privado` en cada',
            '// usuario. NO elimina los enlaces viejos: los contenedores',
            '// son alias a los mismos nodos físicos. Idempotente.',
            '// Parámetro opcional `usuario` (default `todos`).',
            'if (isset($_GET[\'migrar_niveles_usuario\'])) {',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    require_once __DIR__ . \'/miscelaneas/migrar_niveles_usuario.php\';',
            '    $usuario_obj = $_GET[\'usuario\'] ?? \'todos\';',
            '    $res = migrar_niveles_usuario($nombre_app, $usuario_obj);',
            '    echo "Migración de niveles de usuario\\n";',
            '    echo "================================\\n\\n";',
            '    echo "Usuario objetivo: $usuario_obj\\n\\n";',
            '    echo "Migrados: " . $res[\'migrados\'] . "\\n";',
            '    echo "Saltados: " . $res[\'saltados\'] . "\\n";',
            '    if (!empty($res[\'errores\'])) {',
            '        echo "Errores:\\n";',
            '        foreach ($res[\'errores\'] as $e) echo "  - $e\\n";',
            '    }',
            '    echo "\\nListo.\\n";',
            '    exit;',
            '}',
            '',
            '// ==== Migración de usuarios a IDs especiales (v76k) ====',
        ],
    ],

    // ============================================================
    // miscelaneas/migrar_niveles_usuario.php (nuevo)
    // ============================================================

    [
        'tipo' => 'crear',
        'archivo' => 'miscelaneas/migrar_niveles_usuario.php',
        'descripcion' => 'Script de migración de niveles de usuario',
        'contenido' => [
            '<?php',
            '/**',
            ' * Migración Fase B1: crear contenedores `publico` y',
            ' * `privado` en cada usuario.',
            ' *',
            ' * Los contenedores son alias: apuntan a los mismos nodos',
            ' * físicos que hoy cuelgan de la raíz del usuario. Los',
            ' * enlaces viejos NO se tocan. Así el código existente',
            ' * sigue funcionando durante toda la Fase B.',
            ' *',
            ' * Idempotente: los usuarios que ya tienen `publico` se',
            ' * saltean.',
            ' *',
            ' * @since 1.5piloto.76m',
            ' */',
            '',
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Controlador\\Controlador;',
            '',
            '/**',
            ' * Ejecuta la migración sobre el grafo de la app.',
            ' *',
            ' * @param string $nombre_app Nombre de la superestructura de la app.',
            ' * @param string $usuario_objetivo Nombre de usuario o "todos".',
            ' * @return array Resumen.',
            ' */',
            'function migrar_niveles_usuario(string $nombre_app, string $usuario_objetivo = \'todos\'): array {',
            '    // Delega en el comando del framework, que tiene el token',
            '    // encapsulado. Devuelve el resumen directo.',
            '    $res = Controlador::ejecutar_comando(',
            '        \'grafo:crear_niveles_usuario\',',
            '        [\'usuario\' => $usuario_objetivo]',
            '    );',
            '',
            '    if (!is_array($res)) {',
            '        return [',
            '            \'migrados\' => 0,',
            '            \'saltados\' => 0,',
            '            \'errores\' => [\'El comando no devolvió un resumen válido.\'],',
            '        ];',
            '    }',
            '',
            '    // Guardar solo si hubo cambios.',
            '    if ($res[\'migrados\'] > 0) {',
            '        guardar_ambos($nombre_app);',
            '    }',
            '',
            '    return $res;',
            '}',
            '?>',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md — documentación
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: "Última actualización"',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.76l',
            '(diseño del modelo topológico por niveles de exposición.',
            'Cada usuario va a tener contenedores `publico`, `privado` y',
            '`compartido_con_X` colgando de su nodo raíz. La seguridad',
            'emerge de la topología. Admin y soporte siguen accediendo',
            'por código, no por topología. Diseño completo en el PHPDoc',
            'de `aplicacion_POST.php`; plan por fases en §8.7. Solo',
            'documentación, no hay cambios de código todavía.).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76m',
            '(Fase B1 del modelo topológico. Crea los contenedores',
            '`publico` y `privado` como alias de los nodos ya existentes.',
            'Los enlaces viejos NO se tocan: los contenedores apuntan a',
            'los mismos nodos físicos, así el código actual sigue',
            'funcionando sin cambios. La migración es idempotente y se',
            'corre con `?migrar_niveles_usuario=1` desde `index.php`',
            '(opcionalmente con `&usuario=carmen1` para un solo usuario).',
            'Nuevo comando `grafo:crear_niveles_usuario` en el',
            '`Controlador`, que opera sobre el grafo cargado usando el',
            'token encapsulado.).',
            'Antes: v1.5piloto.76l',
            '(diseño del modelo topológico por niveles de exposición.',
            'Cada usuario va a tener contenedores `publico`, `privado` y',
            '`compartido_con_X` colgando de su nodo raíz. La seguridad',
            'emerge de la topología. Admin y soporte siguen accediendo',
            'por código, no por topología. Diseño completo en el PHPDoc',
            'de `aplicacion_POST.php`; plan por fases en §8.7. Solo',
            'documentación, no hay cambios de código todavía.).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v76m al historial',
        'buscar' => [
            '- **v76l**: diseño del modelo topológico por niveles de',
        ],
        'reemplazar' => [
            '- **v76m**: Fase B1 del modelo topológico. Crea los',
            '  contenedores `publico` y `privado` como hijos de cada',
            '  nodo usuario, enlazándolos con los nodos que ya existían',
            '  (alias). `publico` tiene como dato el nombre de usuario',
            '  y enlaza a `nivel`, `nombre_real` y `email`. `privado`',
            '  enlaza al resto de los datos del usuario. Los enlaces',
            '  "de permiso" (`dueno`, `soporte`, `duenos`) quedan en',
            '  la raíz. **Los enlaces viejos NO se eliminan**, así el',
            '  código actual sigue funcionando sin cambios. Nuevo',
            '  comando `grafo:crear_niveles_usuario` en el `Controlador`',
            '  (idempotente). Script `miscelaneas/migrar_niveles_usuario.php`',
            '  y bloque `?migrar_niveles_usuario=1` en `index.php`',
            '  (opcional `&usuario=carmen1`). Solo grafo de la app: el',
            '  de credenciales queda plano por ahora.',
            '- **v76l**: diseño del modelo topológico por niveles de',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de la Fase B1',
        'buscar' => [
            '- Cerramos en v76l el diseño del modelo topológico por',
        ],
        'reemplazar' => [
            '- Cerramos en v76m la Fase B1 del modelo topológico:',
            '  los contenedores `publico` y `privado` se crean como',
            '  **alias** de los nodos ya existentes. Los enlaces viejos',
            '  NO se tocan: los contenedores apuntan a los mismos nodos',
            '  físicos, así el código actual sigue funcionando. Nuevo',
            '  comando genérico `grafo:crear_niveles_usuario` en el',
            '  `Controlador` (idempotente), script de migración en',
            '  `miscelaneas/migrar_niveles_usuario.php` y bloque',
            '  `?migrar_niveles_usuario=1` en `index.php` (opcional',
            '  `&usuario=carmen1`). Solo grafo de la app; el de',
            '  credenciales queda plano por ahora.',
            '- Cerramos en v76l el diseño del modelo topológico por',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto al cierre',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76l (framework 1.5i.7k).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76m (framework 1.5i.7k).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar v76m al bloque de estado',
        'buscar' => [
            'v76l: diseño del modelo topológico por niveles de',
            'exposición (`publico`, `privado`, `compartido_con_X`).',
            'Documentado en el PHPDoc de `aplicacion_POST.php`',
            '(sección "Diseño propuesto") y en §8.7. Sin cambios',
            'de código todavía.',
        ],
        'reemplazar' => [
            'v76l: diseño del modelo topológico por niveles de',
            'exposición (`publico`, `privado`, `compartido_con_X`).',
            'Documentado en el PHPDoc de `aplicacion_POST.php`',
            '(sección "Diseño propuesto") y en §8.7. Sin cambios',
            'de código todavía.',
            'v76m: Fase B1. Contenedores `publico` y `privado` creados',
            'como alias de los nodos existentes. Enlaces viejos',
            'intactos. Nuevo comando `grafo:crear_niveles_usuario` y',
            'script `miscelaneas/migrar_niveles_usuario.php` (idempotente).',
            'Bloque `?migrar_niveles_usuario=1` en `index.php`.',
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