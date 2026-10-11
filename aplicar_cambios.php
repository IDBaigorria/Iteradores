<?php
/**
 * Aplicador de cambios — Proyecto iteradores (PHP).
 *
 * Tanda V1.5piloto.77q (Fase B2.4.2 + B2.4.5).
 *   - Crear Aplicacion/Compartidos.php (5 helpers nuevos).
 *   - Viaje.php: eliminar_viaje, agregar_terminal_autorizada,
 *     eliminar_terminal_autorizada ahora sincronizan compartidos.
 *   - ViajeMicros.php: agregar_micro_a_viaje sincroniza empresas.
 *   - Empresa.php: eliminar_empresa desincroniza compartidos.
 *   - aplicacion_POST.php: actualizar PHPDoc.
 *   - index.php: bump.
 *   - prompts: plan_actual.md + prompt_piloto.md.
 *
 * Uso: php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ============================================================
    // 1. Crear Aplicacion/Compartidos.php
    // ============================================================
    [
        'tipo' => 'crear',
        'archivo' => 'Aplicacion/Compartidos.php',
        'descripcion' => 'Nuevo archivo con helpers de compartidos',
        'contenido' => [
            '<?php',
            '/**',
            ' * Helpers para mantener vivos los compartidos por terminal.',
            ' *',
            ' * Fase B2.4 del modelo topológico. Los compartidos de un dueño',
            ' * (`compartido_con_us_termX`) tienen que reflejar en cada momento',
            ' * qué viajes están autorizados a esa terminal, qué empresas usan',
            ' * esos viajes, y qué ventas y cancelaciones son de esa terminal.',
            ' *',
            ' * Los 3 helpers que ya existían (`_asegurar_compartido_terminal`,',
            ' * `_asegurar_arbol_compartido`, `_asegurar_compartido_y_repuntar`)',
            ' * siguen viviendo en `Usuario.php`. Los 5 nuevos (sincronizar /',
            ' * desincronizar viaje, terminal, empresa) viven acá porque los',
            ' * usan también `Viaje.php`, `ViajeMicros.php` y `Empresa.php`.',
            ' *',
            ' * @package   Iteradores',
            ' * @since     1.5piloto.77q',
            ' * @version   1.5piloto.77q',
            ' */',
            '',
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Controlador\\Controlador;',
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./miscelaneas/Arbol.php");',
            'include_once("./Aplicacion/FuncionesAuxiliares.php");',
            '// Los 3 helpers `_asegurar_*` viven en Usuario.php. Este',
            '// require los garantiza para quien use Compartidos.php',
            '// sin haber cargado Usuario.php antes.',
            'require_once __DIR__ . \'/Usuarios/Usuario.php\';',
            '',
            '// ============================================================',
            '// Fase B2.4: sincronización de viajes y empresas',
            '// ============================================================',
            '',
            '/**',
            ' * Sincroniza un viaje en el compartido de una terminal:',
            ' * agrega el viaje al contenedor `viajes` (si no está) y',
            ' * agrega las empresas referenciadas por sus micros al',
            ' * contenedor `empresas` (si no están). Idempotente.',
            ' *',
            ' * No modifica nada si el viaje ya estaba y todas las',
            ' * empresas también.',
            ' *',
            ' * @param Nodo   $compartido',
            ' * @param Nodo   $nodo_viaje',
            ' * @param string $nombre_terminal',
            ' * @param Nodo   $nodo_dueno',
            ' * @return void',
            ' */',
            'function _sincronizar_viaje_en_compartido(Nodo $compartido, Nodo $nodo_viaje, string $nombre_terminal, Nodo $nodo_dueno): void {',
            '    // 1. Asegurar el contenedor `viajes` del compartido.',
            '    $c_viajes = $compartido->adyacente(\'viajes\');',
            '    if (!$c_viajes) {',
            '        $c_viajes = Nodo::crear_con_dato(\'\');',
            '        $compartido->_adyacente_en($c_viajes, \'viajes\');',
            '    }',
            '    $nombre_viaje = (string)$nodo_viaje->dato();',
            '    if ($nombre_viaje !== \'\' && !$c_viajes->adyacente($nombre_viaje)) {',
            '        $c_viajes->_adyacente_en($nodo_viaje, $nombre_viaje);',
            '    }',
            '',
            '    // 2. Asegurar el contenedor `empresas` del compartido.',
            '    $c_empresas = $compartido->adyacente(\'empresas\');',
            '    if (!$c_empresas) {',
            '        $c_empresas = Nodo::crear_con_dato(\'\');',
            '        $compartido->_adyacente_en($c_empresas, \'empresas\');',
            '    }',
            '',
            '    // 3. Recolectar las empresas referenciadas por los micros del viaje.',
            '    $micros = $nodo_viaje->adyacente(\'micros\');',
            '    if ($micros) {',
            '        foreach ((array)$micros->adyacentes() as $nodo_micro) {',
            '            $emp = $nodo_micro->adyacente(\'empresa\');',
            '            if ($emp) {',
            '                $nombre_emp = (string)$emp->dato();',
            '                if ($nombre_emp !== \'\' && !$c_empresas->adyacente($nombre_emp)) {',
            '                    $c_empresas->_adyacente_en($emp, $nombre_emp);',
            '                }',
            '            }',
            '        }',
            '    }',
            '}',
            '',
            '/**',
            ' * Desincroniza un viaje de un compartido: lo saca del',
            ' * contenedor `viajes`. NO toca `empresas` (podrían estar',
            ' * en uso por otro viaje autorizado de la misma terminal).',
            ' *',
            ' * @param Nodo   $compartido',
            ' * @param Nodo   $nodo_viaje',
            ' * @param string $nombre_terminal',
            ' * @return bool True si el viaje estaba y se sacó.',
            ' */',
            'function _desincronizar_viaje_de_compartido(Nodo $compartido, Nodo $nodo_viaje, string $nombre_terminal): bool {',
            '    $c_viajes = $compartido->adyacente(\'viajes\');',
            '    if (!$c_viajes) return false;',
            '    $nombre_viaje = (string)$nodo_viaje->dato();',
            '    if ($nombre_viaje !== \'\' && $c_viajes->adyacente($nombre_viaje)) {',
            '        $c_viajes->eliminar_adyacente($nombre_viaje);',
            '        return true;',
            '    }',
            '    return false;',
            '}',
            '',
            '/**',
            ' * Sincroniza UNA terminal autorizada en UN viaje:',
            ' * asegura el compartido de la terminal en el dueño y',
            ' * agrega el viaje + sus empresas. Idempotente.',
            ' *',
            ' * Se llama desde `agregar_terminal_autorizada` y desde',
            ' * `agregar_micro_a_viaje` (para propagar la empresa nueva).',
            ' *',
            ' * @param Nodo   $nodo_viaje',
            ' * @param string $nombre_terminal',
            ' * @param string $nombre_dueno',
            ' * @return void',
            ' */',
            'function _sincronizar_terminal_en_compartido_del_viaje(Nodo $nodo_viaje, string $nombre_terminal, string $nombre_dueno): void {',
            '    $nodo_dueno = Nodo::nodo_por_id(\'us_\' . $nombre_dueno);',
            '    if (!$nodo_dueno) return;',
            '    $nodo_terminal = Nodo::nodo_por_id(\'us_\' . $nombre_terminal);',
            '    if (!$nodo_terminal) return;',
            '',
            '    // Asegurar el compartido (crea si falta, repunta).',
            '    _asegurar_compartido_y_repuntar($nodo_dueno, $nodo_terminal, $nombre_dueno, $nombre_terminal);',
            '',
            '    // Obtener el compartido y sincronizar el viaje.',
            '    $compartido = $nodo_dueno->adyacente(\'compartido_con_\' . $nombre_terminal);',
            '    if (!$compartido) return;',
            '    _sincronizar_viaje_en_compartido($compartido, $nodo_viaje, $nombre_terminal, $nodo_dueno);',
            '}',
            '',
            '/**',
            ' * Desincroniza UNA terminal autorizada de UN viaje:',
            ' * saca el viaje del compartido del terminal. Idempotente.',
            ' *',
            ' * Se llama desde `eliminar_terminal_autorizada`.',
            ' *',
            ' * @param Nodo   $nodo_viaje',
            ' * @param string $nombre_terminal',
            ' * @return void',
            ' */',
            'function _desincronizar_terminal_de_compartido_del_viaje(Nodo $nodo_viaje, string $nombre_terminal): void {',
            '    $nodo_terminal = Nodo::nodo_por_id(\'us_\' . $nombre_terminal);',
            '    if (!$nodo_terminal) return;',
            '    $nodo_dueno_ctx = $nodo_terminal->adyacente(\'dueno\');',
            '    if (!$nodo_dueno_ctx) return;',
            '    // Solo si el enlace `dueno` apunta a un compartido (post-repuntado).',
            '    if ($nodo_dueno_ctx->adyacente(\'_es_compartido\')) {',
            '        _desincronizar_viaje_de_compartido($nodo_dueno_ctx, $nodo_viaje, $nombre_terminal);',
            '    }',
            '}',
            '',
            '/**',
            ' * Desincroniza un viaje de TODOS los compartidos de sus',
            ' * terminales autorizadas (o de una sola, si se especifica).',
            ' * Idempotente.',
            ' *',
            ' * Se llama desde `eliminar_viaje` (antes de destruir el',
            ' * subárbol, para que no queden referencias entrantes).',
            ' *',
            ' * @param Nodo         $nodo_viaje',
            ' * @param string|null  $nombre_terminal_especifico',
            ' * @return void',
            ' */',
            'function _desincronizar_viaje_de_compartidos(Nodo $nodo_viaje, ?string $nombre_terminal_especifico = null): void {',
            '    $terminales = $nodo_viaje->adyacente(\'terminales_autorizadas\');',
            '    if (!$terminales) return;',
            '    foreach ((array)$terminales->adyacentes() as $nombre_terminal => $nodo_tv) {',
            '        $nombre_terminal = (string)$nombre_terminal;',
            '        if ($nombre_terminal === \'\') continue;',
            '        if ($nombre_terminal_especifico !== null && $nombre_terminal !== $nombre_terminal_especifico) continue;',
            '        _desincronizar_terminal_de_compartido_del_viaje($nodo_viaje, $nombre_terminal);',
            '    }',
            '}',
            '',
            '/**',
            ' * Desincroniza una empresa de TODOS los compartidos del',
            ' * dueño. Saca el enlace del contenedor `empresas` de cada',
            ' * `compartido_con_*`. Idempotente.',
            ' *',
            ' * Se llama desde `eliminar_empresa` antes de destruir el',
            ' * nodo empresa (que todavía tiene referencias entrantes',
            ' * desde los compartidos).',
            ' *',
            ' * @param Nodo   $nodo_dueno',
            ' * @param string $nombre_empresa',
            ' * @return void',
            ' */',
            'function _desincronizar_empresa_de_compartidos(Nodo $nodo_dueno, string $nombre_empresa): void {',
            '    foreach ((array)$nodo_dueno->adyacentes() as $enlace => $hijo) {',
            '        $enlace = (string)$enlace;',
            '        if (strpos($enlace, \'compartido_con_\') !== 0) continue;',
            '        $c_empresas = $hijo->adyacente(\'empresas\');',
            '        if ($c_empresas && $c_empresas->adyacente($nombre_empresa)) {',
            '            $c_empresas->eliminar_adyacente($nombre_empresa);',
            '        }',
            '    }',
            '}',
        ],
    ],

    // ============================================================
    // 2. Viaje.php — eliminar_viaje: desincronizar compartidos
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje: eliminar_viaje desincroniza compartidos',
        'buscar' => [
            '    // Fase 2: destruir el subárbol completo del viaje.',
            '    // Ver §8.6 del prompt del piloto para el contexto y las',
            '    // reglas de destrucción.',
            '    _destruir_viaje_completo($nodo_viaje);',
            '    $nodo_viajes->eliminar_adyacente($nombre_viaje);',
            '    Nodo::eliminar($nodo_viaje);',
        ],
        'reemplazar' => [
            '    // Fase B2.4.2 (v77q): desincronizar el viaje de los',
            '    // compartidos de TODAS sus terminales autorizadas,',
            '    // antes de destruir el subárbol. Si no, el compartido',
            '    // queda con una referencia entrante al viaje y',
            '    // Nodo::eliminar falla en silencio.',
            '    _desincronizar_viaje_de_compartidos($nodo_viaje);',
            '',
            '    // Fase 2: destruir el subárbol completo del viaje.',
            '    // Ver §8.6 del prompt del piloto para el contexto y las',
            '    // reglas de destrucción.',
            '    _destruir_viaje_completo($nodo_viaje);',
            '    $nodo_viajes->eliminar_adyacente($nombre_viaje);',
            '    Nodo::eliminar($nodo_viaje);',
        ],
    ],

    // ============================================================
    // 3. Viaje.php — agregar_terminal_autorizada: sincronizar
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje: agregar_terminal_autorizada sincroniza',
        'buscar' => [
            '    $nodo_terminal_viaje = Nodo::crear_con_dato(\'\');',
            '    $nodo_terminal_viaje->_adyacente_en($nodo_usuario_terminal, \'terminal\');',
            '    $nodo_terminal_viaje->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'cambiar_punto_predeterminado\');',
            '',
            '    $nodo_terminales->_adyacente_en($nodo_terminal_viaje, $nombre_terminal);',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    return [\'exito\' => true];',
        ],
        'reemplazar' => [
            '    $nodo_terminal_viaje = Nodo::crear_con_dato(\'\');',
            '    $nodo_terminal_viaje->_adyacente_en($nodo_usuario_terminal, \'terminal\');',
            '    $nodo_terminal_viaje->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'cambiar_punto_predeterminado\');',
            '',
            '    $nodo_terminales->_adyacente_en($nodo_terminal_viaje, $nombre_terminal);',
            '',
            '    // Fase B2.4.5 (v77q): sincronizar el viaje en el',
            '    // compartido del terminal que se acaba de autorizar.',
            '    // Si el compartido no existe, lo crea; si existe,',
            '    // agrega el viaje + sus empresas. Idempotente.',
            '    _sincronizar_terminal_en_compartido_del_viaje($nodo_viaje, $nombre_terminal, $nombre_dueno);',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    return [\'exito\' => true];',
        ],
    ],

    // ============================================================
    // 4. Viaje.php — eliminar_terminal_autorizada: desincronizar
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje: eliminar_terminal_autorizada desincroniza',
        'buscar' => [
            '    // Fase 2: desenlazar del contenedor y destruir el TerminalViaje',
            '    // completo. _destruir_terminal_viaje se encarga de desenlazar',
            '    // `terminal` (referencia externa) y `punto_subida_bajada`',
            '    // (referencia a un nodo parada del viaje, que sigue vivo).',
            '    $nodo_terminales->eliminar_adyacente($nombre_terminal);',
            '    _destruir_terminal_viaje($nodo_terminal_viaje);',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    return [\'exito\' => true];',
        ],
        'reemplazar' => [
            '    // Fase 2: desenlazar del contenedor y destruir el TerminalViaje',
            '    // completo. _destruir_terminal_viaje se encarga de desenlazar',
            '    // `terminal` (referencia externa) y `punto_subida_bajada`',
            '    // (referencia a un nodo parada del viaje, que sigue vivo).',
            '    $nodo_terminales->eliminar_adyacente($nombre_terminal);',
            '    _destruir_terminal_viaje($nodo_terminal_viaje);',
            '',
            '    // Fase B2.4.5 (v77q): sacar el viaje del compartido del',
            '    // terminal que se acaba de desautorizar. Los demás',
            '    // compartidos no se tocan.',
            '    _desincronizar_terminal_de_compartido_del_viaje($nodo_viaje, $nombre_terminal);',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    return [\'exito\' => true];',
        ],
    ],

    // ============================================================
    // 5. Viaje.php — agregar include de Compartidos.php
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Viaje: agregar include de Compartidos.php',
        'buscar' => [
            '// Incluir submódulos de viajes',
            'require_once __DIR__ . \'/ViajeMicros.php\';',
        ],
        'reemplazar' => [
            '// Incluir helpers de compartidos (Fase B2.4.2 + B2.4.5).',
            'require_once __DIR__ . \'/../Compartidos.php\';',
            '',
            '// Incluir submódulos de viajes',
            'require_once __DIR__ . \'/ViajeMicros.php\';',
        ],
    ],

    // ============================================================
    // 6. ViajeMicros.php — agregar_micro_a_viaje: sincronizar
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
        'descripcion' => 'ViajeMicros: agregar_micro sincroniza empresas',
        'buscar' => [
            '    actualizar_contadores_micro($nodo_micro);',
            '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    return [\'exito\' => true, \'nombre_micro\' => $nombre_micro];',
            '}',
        ],
        'reemplazar' => [
            '    actualizar_contadores_micro($nodo_micro);',
            '    actualizar_contadores_viaje($nombre_viaje, $nombre_dueno);',
            '',
            '    // Fase B2.4.3 (v77q): si el viaje ya estaba autorizado',
            '    // a alguna terminal, la empresa del micro nuevo tiene',
            '    // que aparecer en el `empresas` del compartido. Se',
            '    // sincroniza cada terminal autorizada del viaje.',
            '    // Idempotente: si el viaje y la empresa ya estaban, no',
            '    // hace nada.',
            '    $nodo_tas = $nodo_viaje->adyacente(\'terminales_autorizadas\');',
            '    if ($nodo_tas) {',
            '        foreach ((array)$nodo_tas->adyacentes() as $nombre_terminal => $nodo_tv) {',
            '            $nombre_terminal = (string)$nombre_terminal;',
            '            if ($nombre_terminal === \'\') continue;',
            '            _sincronizar_terminal_en_compartido_del_viaje($nodo_viaje, $nombre_terminal, $nombre_dueno);',
            '        }',
            '    }',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    return [\'exito\' => true, \'nombre_micro\' => $nombre_micro];',
            '}',
        ],
    ],

    // ============================================================
    // 7. ViajeMicros.php — agregar include
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
        'descripcion' => 'ViajeMicros: agregar include de Compartidos.php',
        'buscar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
        ],
        'reemplazar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./Aplicacion/Compartidos.php");',
        ],
    ],

    // ============================================================
    // 8. Empresa.php — eliminar_empresa: desincronizar compartidos
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Empresas/Empresa.php',
        'descripcion' => 'Empresa: eliminar_empresa desincroniza compartidos',
        'buscar' => [
            '    // Desenlazar la empresa del contenedor del dueño y destruirla.',
            '    $nodo_empresas->eliminar_adyacente($nombre_empresa);',
            '    _destruir_campos_simples($nodo_empresa);',
            '    Nodo::eliminar($nodo_empresa);',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
        'reemplazar' => [
            '    // Fase B2.4.4 (v77q): desincronizar la empresa de TODOS',
            '    // los compartidos del dueño. Si no, los compartidos',
            '    // quedan con un enlace entrante al nodo empresa y',
            '    // Nodo::eliminar falla en silencio.',
            '    _desincronizar_empresa_de_compartidos($nodo_dueno, $nombre_empresa);',
            '',
            '    // Desenlazar la empresa del contenedor del dueño y destruirla.',
            '    $nodo_empresas->eliminar_adyacente($nombre_empresa);',
            '    _destruir_campos_simples($nodo_empresa);',
            '    Nodo::eliminar($nodo_empresa);',
            '',
            '    guardar_ambos(ConfiguracionApli::NOMBRE_APP);',
            '    return [\'exito\' => true];',
            '}',
        ],
    ],

    // ============================================================
    // 9. Empresa.php — agregar include
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Empresas/Empresa.php',
        'descripcion' => 'Empresa: agregar include de Compartidos.php',
        'buscar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./Aplicacion/FuncionesAuxiliares.php");',
            '',
            '/**',
            ' * Lista las empresas asociadas a un dueño.',
        ],
        'reemplazar' => [
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            'include_once("./Aplicacion/FuncionesAuxiliares.php");',
            'include_once("./Aplicacion/Compartidos.php");',
            '',
            '/**',
            ' * Lista las empresas asociadas a un dueño.',
        ],
    ],

    // ============================================================
    // 10. aplicacion_POST.php — encabezado
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST: encabezado a v77q',
        'buscar' => [
            ' * ## Estructura de nodos actual (v1.5piloto.58)',
        ],
        'reemplazar' => [
            ' * ## Estructura de nodos actual (v1.5piloto.77q)',
        ],
    ],

    // ============================================================
    // 11. aplicacion_POST.php — persistencia
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST: persistencia SQL+JSON → SQL',
        'buscar' => [
            ' * ### Persistencia (SQL + JSON)',
            ' *',
            ' * A partir de v1.5piloto.70, cada operación de guardado escribe en SQL',
            ' * (fuente de verdad, se lee al arrancar) y en JSON (respaldo, no se lee',
            ' * automáticamente). El helper `guardar_ambos($nombre)` se encarga del doble',
            ' * guardado. Si la escritura JSON falla, se registra con `Controlador::_error()`',
            ' * (el sistema de errores de `Objeto`) y la operación SQL sigue siendo válida.',
        ],
        'reemplazar' => [
            ' * ### Persistencia (solo SQL)',
            ' *',
            ' * A partir de v1.5piloto.74m, `guardar_ambos($nombre)` solo escribe en',
            ' * SQL. El respaldo JSON automático se eliminó porque el `json_encode`',
            ' * de todo el grafo se volvió el cuello de botella cuando el grafo',
            ' * creció (varias terminales, vehículos, ventas). El respaldo en',
            ' * formatos alternativos (JSON, XML) pasa a ser una acción manual del',
            ' * admin, a implementar en el rediseño del panel.',
        ],
    ],

    // ============================================================
    // 12. aplicacion_POST.php — enlace dueno del terminal
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST: enlace dueno apunta al compartido',
        'buscar' => [
            ' * | `dueno`         | Enlace directo al nodo del usuario dueño (solo para `terminal`).                       |',
            ' * |                 | El nodo destino tiene como dato el nombre del dueño (string).                          |',
        ],
        'reemplazar' => [
            ' * | `dueno`         | Enlace directo al **compartido** del terminal en el dueño (solo para `terminal`).      |',
            ' * |                 | El nodo destino tiene como dato el nombre del dueño (string) y el marcador           |',
            ' * |                 | `_es_compartido`. A partir de v1.5piloto.77f (Fase B2.3.5a), el enlace ya no apunta    |',
            ' * |                 | al nodo del dueño real: apunta al contenedor `compartido_con_us_<terminal>` en el      |',
            ' * |                 | dueño. Esa es la base del aislamiento por topología.                                    |',
        ],
    ],

    // ============================================================
    // 13. aplicacion_POST.php — Diseño propuesto → Modelo topológico
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST: diseño → modelo topológico',
        'buscar' => [
            ' * ## Diseño propuesto: contenedores por nivel de exposición (v1.5piloto.76l)',
            ' * ',
            ' * **Estado:** diseño consensuado, todavía no implementado.',
            ' * La migración se hace por fases (ver §8.7 del prompt',
            ' * del piloto). Una vez implementado, esta sección',
            ' * reemplazará a la descripción "actual" de más arriba.',
        ],
        'reemplazar' => [
            ' * ## Modelo topológico: contenedores por nivel de exposición (v1.5piloto.77q)',
            ' * ',
            ' * **Estado:** implementado. Fases B1 (contenedores `publico`/`privado`),',
            ' * B2.1 (compartidos por terminal), B2.2 (contexto opcional en las',
            ' * funciones del piloto) y B2.3 (árboles paralelos parametrizados +',
            ' * repuntado de terminales al compartido) aplicadas. Próximo paso:',
            ' * Fase B2.4 (mantener vivos los compartidos en cada operación de',
            ' * escritura; ver `prompts/plan_actual.md` §1.9).',
        ],
    ],

    // ============================================================
    // 14. aplicacion_POST.php — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST: bump @version a v77q',
        'buscar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.76l',
        ],
        'reemplazar' => [
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.77q',
        ],
    ],

    // ============================================================
    // 15. plan_actual.md — nueva entrada v77q
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: agregar v77q',
        'buscar' => [
            '- **v77d**: refresco del modal de pasajero tras cambiar de',
            '  asiento. `abrir_modal_cambiar_asiento` acepta un callback',
            '  `on_exito`.',
            '',
            '**Tanda actual:** v77o (cierre de B2.3.5 + documentación).',
        ],
        'reemplazar' => [
            '- **v77d**: refresco del modal de pasajero tras cambiar de',
            '  asiento. `abrir_modal_cambiar_asiento` acepta un callback',
            '  `on_exito`.',
            '- **v77q** (Tanda A, B2.4.2 + B2.4.5): fixes del aislamiento',
            '  por topología. `Aplicacion/Compartidos.php` nuevo (5 helpers',
            '  para sincronizar/desincronizar viajes, terminales y',
            '  empresas en los compartidos). `eliminar_viaje` desincroniza',
            '  de los compartidos de sus terminales. `agregar_terminal_autorizada`',
            '  y `eliminar_terminal_autorizada` sincronizan/desincronizan',
            '  el compartido. `agregar_micro_a_viaje` propaga la empresa',
            '  al `empresas` de cada compartido. `eliminar_empresa`',
            '  desincroniza de los compartidos del dueño. `aplicacion_POST.php`',
            '  actualizado al modelo topológico.',
            '',
            '**Tanda actual:** v77q (Tanda A, B2.4.2 + B2.4.5).',
        ],
    ],

    // ============================================================
    // 16. prompt_piloto.md — agregar v77q
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto: agregar v77q',
        'buscar' => [
            '- **v77o**: cierre de B2.3.5 + auditoría de B2.4/B3.',
        ],
        'reemplazar' => [
            '- **v77q** (Tanda A, B2.4.2 + B2.4.5): fixes del',
            '  aislamiento por topología. `Aplicacion/Compartidos.php`',
            '  nuevo (5 helpers para sincronizar/desincronizar viajes,',
            '  terminales y empresas en los compartidos). 5 flujos del',
            '  piloto sincronizan/desincronizan el compartido:',
            '  eliminar_viaje, agregar/eliminar_terminal_autorizada,',
            '  agregar_micro_a_viaje, eliminar_empresa. `aplicacion_POST.php`',
            '  actualizado al modelo topológico actual. Ver',
            '  `plan_actual.md` §1.9.',
            '- **v77o**: cierre de B2.3.5 + auditoría de B2.4/B3.',
        ],
    ],

    // ============================================================
    // 17. index.php — bump @version
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump a 1.5piloto.77q',
        'buscar' => [
            ' * @version   1.5piloto.77o',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.77q',
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
echo "[INFO] " . count($creaciones) . " archivo(s) a crear, $total_reemplazos reemplazo(s) en " . count($reemplazos_por_archivo) . " archivo(s).\n\n";
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