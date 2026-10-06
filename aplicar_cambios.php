<?php
/**
 * Aplicador de cambios automáticos — Piloto (agencia de viajes).
 *
 * Tanda V1.5piloto.76d (documentación pura):
 *   - Escribir §8.6.1 "Criterios de eliminación" con el criterio
 *     concreto por entidad, extraído de los 19 flujos arreglados
 *     en Fase 2 + el cierre del Grupo B.
 *   - Actualizar historial, discusión actual y estado del proyecto.
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

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6.1: reemplazar stub por criterio completo',
        'buscar' => [
            '### 8.6.1 Criterios de eliminación',
            '',
            '**Stub. A completar en Fase 2.**',
            '',
            'Cuando se cancela/elimina una entidad, no siempre hay que',
            'destruir los nodos asociados. Algunos se conservan a',
            'propósito (datos del cliente). Otros deben destruirse',
            '(viaje, micro, terminal). Esta sección documenta el criterio',
            'por tipo de entidad.',
            '',
            '**Criterio general (a validar caso por caso):**',
            '',
            '- **Datos del cliente (pasajero, comprador):** se conservan',
            '  aunque la venta se cancele. El cliente puede volver a',
            '  comprar.',
            '- **Entidades operativas (viaje, micro, venta, cupón,',
            '  terminal):** al eliminarse, sus nodos deberían destruirse.',
            '  Los nodos que cuelgan de ellas también (asientos de copia,',
            '  cupones, etc.) salvo que estén referenciados desde otro',
            '  lado.',
            '- **Nodos "hijos" (asientos, cupones, etc.):** se destruyen',
            '  junto con su padre, salvo que tengan referencias',
            '  entrantes (en cuyo caso primero se desenlazan las',
            '  referencias).',
            '',
            '**Pendiente:** documentar el criterio por entidad concreta',
            'después de la auditoría.',
        ],
        'reemplazar' => [
            '### 8.6.1 Criterios de eliminación',
            '',
            'Documentado al cerrar Fase 2 (v76d). El criterio se extrajo',
            'de los 19 flujos arreglados en v74r-v75a + el Grupo B.',
            '',
            '**Reglas generales (aplican siempre):**',
            '',
            '1. **Sub-árbol interno vs referencia externa.** Al destruir',
            '   una entidad, hay que distinguir qué nodos viven solo',
            '   como parte de ella (sub-árbol interno) y qué nodos',
            '   tienen vida propia o son compartidos (referencias',
            '   externas).',
            '   - **Sub-árbol interno:** se destruye recursivamente.',
            '     Ejemplos: campos string, contenedores, cupones de una',
            '     venta, asientos de una copia de vehículo, TerminalViaje,',
            '     opciones avanzadas, opciones_cobro.',
            '   - **Referencia externa:** solo se desenlaza, no se',
            '     destruye. Ejemplos: pasajero/comprador (reutilizable),',
            '     empresa (pertenece al dueño), terminal (usuario),',
            '     asiento real del micro (vive en el micro, no en la',
            '     venta), parada (vive en el viaje), rendición (vive en',
            '     el dueño), sesión (vive en credenciales).',
            '',
            '2. **Orden: hojas a raíz.** Antes de `Nodo::eliminar($n)`,',
            '   hay que haber desenlazado todas sus referencias',
            '   entrantes. El patrón recursivo es: primero desenlazar',
            '   al hijo del padre, después destruir el hijo. Si no,',
            '   `Nodo::eliminar` falla silenciosamente y el nodo queda',
            '   huérfano.',
            '',
            '3. **El contenedor padre se vacía antes de destruirse.**',
            '   Si la entidad tiene un contenedor propio (ej. `micros`',
            '   de un viaje), hay que vaciarlo (destruyendo cada hijo)',
            '   antes de destruir el contenedor.',
            '',
            '4. **Desenlazar del contenedor de arriba.** Después de',
            '   destruir el sub-árbol, desenlazar el nodo principal',
            '   del contenedor que lo contenía (el contenedor del',
            '   dueño, por ejemplo).',
            '',
            '5. **Los campos hoja se destruyen con su padre.** Un nodo',
            '   con dato string y sin adyacentes propios es un "campo',
            '   hoja". Se destruye junto con el nodo que lo contiene.',
            '   El helper `_destruir_campos_simples($padre, $excluir)`',
            '   hace esto automáticamente, dejando intactos los',
            '   enlaces que apuntan a sub-árboles o a referencias',
            '   externas.',
            '',
            '**Anti-patrones (lo que causaba las fugas pre-Fase 2):**',
            '',
            '- **Desenlazar sin destruir.** `$padre->eliminar_adyacente($enlace)`',
            '  solo desconecta el nodo. Si el nodo no tiene otra',
            '  referencia entrante, queda huérfano. Fue la causa',
            '  principal de las ~10.000 fugas del piloto.',
            '- **Destruir sin desenlazar.** `Nodo::eliminar($n)` con',
            '  referencias entrantes falla silenciosamente. El nodo',
            '  sigue vivo con sus referencias rotas. Fue el bug del',
            '  orden de destrucción (v74t).',
            '- **Usar `eliminar_hmi` sobre listas simples.** Las listas',
            '  con `primer`/`siguiente` no son árboles `hmi`/`hd`. El',
            '  `eliminar_hmi` no las ve. Bug de `cancelar_venta`',
            '  (v74v).',
            '',
            '**Criterio por entidad concreta:**',
            '',
            '**Usuario (`eliminar_usuario`, `actualizar_usuario`):**',
            '- Destruir: campos del nodo usuario (`nivel`, `nombre_real`,',
            '  `email`, `efectivo`), banco (contenedor con `nombre`,',
            '  `cuenta`), contenedor `duenos` si es soporte, nodo',
            '  credencial en credenciales (con `codigo_hash`,',
            '  `contrasena`, `intentos_fallidos`, `bloqueado_hasta`,',
            '  `ultimo_acceso`, `ip_ultimo_acceso`), y las sesiones',
            '  activas del usuario.',
            '- Desenlazar: referencias cruzadas entre usuarios (`soporte`',
            '  en dueños, `dueno` en terminales, terminales del dueño).',
            '- No destruir: pasajeros asociados, terminales (impiden la',
            '  eliminación si las tiene), ventas.',
            '- Helper: `_destruir_banco_usuario`.',
            '',
            '**Pasajero (`eliminar_pasajero`, `limpiar_pasajeros_de_prueba`):**',
            '- Destruir: campos personales (`nombres`, `apellido`,',
            '  `email`, `celular`, `celular_emergencia`,',
            '  `fecha_nacimiento`, `localidad`, `direccion`,',
            '  `fecha_ultima_modificacion`), declaración jurada adjunta',
            '  con sus 4 sub-campos (`nombre_original`, `tipo`, `tamano`,',
            '  `fecha_subida`).',
            '- No eliminar si tiene pasajes comprados (regla del negocio:',
            '  no se puede borrar un cliente que viajó).',
            '- Helpers: `_destruir_declaracion_jurada_pasajero`,',
            '  `_destruir_pasajero_completo`.',
            '',
            '**Declaración jurada del pasajero (subir / reemplazar /',
            'eliminar):**',
            '- Destruir: el nodo DJ con sus 4 sub-campos.',
            '- Helper: `_destruir_declaracion_jurada_pasajero`.',
            '',
            '**Empresa (`eliminar_empresa`):**',
            '- Destruir: cada vehículo completo (con asientos, pisos,',
            '  listas circulares, campos), el contenedor `vehiculos`, y',
            '  la propia empresa (con sus campos).',
            '- No destruir: los micros de viajes que apunten a esta',
            '  empresa. La referencia es solo por identificador de',
            '  empresa, no rompe al destruir la empresa.',
            '- Helper: `_destruir_vehiculo_completo`.',
            '',
            '**Vehículo (`eliminar_vehiculo`, `actualizar_configuracion_vehiculo`):**',
            '- Destruir: asientos, pisos, listas circulares de asientos,',
            '  campos (`nombre`, `foto`).',
            '- No destruir: copias del vehículo en micros. Son nodos',
            '  independientes.',
            '- Helper: `_destruir_vehiculo_completo`, `_destruir_piso`,',
            '  `_destruir_lista_circular_asientos`.',
            '',
            '**Viaje (`eliminar_viaje`, `limpiar_viajes_de_prueba`):**',
            '- Destruir, en orden: micros (cada uno con su copia de',
            '  vehículo y asientos), contenedor `micros`, TerminalViaje',
            '  de cada terminal autorizada, contenedor',
            '  `terminales_autorizadas`, paradas intermedias, DJs',
            '  mayor y menor, opciones avanzadas, campos del viaje.',
            '- Desenlazar: `dueno` (referencia externa al usuario).',
            '- Helper: `_destruir_viaje_completo` (que usa',
            '  `_destruir_micro`, `_destruir_terminal_viaje`,',
            '  `_destruir_parada`).',
            '',
            '**Micro de viaje (`eliminar_micro_de_viaje`):**',
            '- Destruir: copia de vehículo (con pisos y asientos),',
            '  campos del micro (`monto`, contadores).',
            '- Desenlazar: `empresa` (externa), `viaje` (circular).',
            '- Helper: `_destruir_micro`.',
            '',
            '**Terminal autorizada de un viaje',
            '(`eliminar_terminal_autorizada`):**',
            '- Destruir: el TerminalViaje con sus campos override.',
            '- Desenlazar: `terminal` (usuario externo),',
            '  `punto_subida_bajada` (parada del viaje, sigue vivo).',
            '- Helper: `_destruir_terminal_viaje`.',
            '',
            '**Paradas intermedias (`_guardar_paradas_intermedias`):**',
            '- Reutilizadas: se conservan (su identidad importa para',
            '  los TerminalViaje que las referencian por',
            '  `punto_subida_bajada`).',
            '- No reutilizadas: se destruyen.',
            '- Helper: `_destruir_parada`.',
            '',
            '**Venta persistente (`cancelar_venta`):**',
            '- Destruir: asientos-en-venta (con sus campos',
            '  `punto_subida_bajada`, `hora_subida_bajada`), cupones',
            '  (con campos), contenedor de cupones, sub-nodo',
            '  `opciones_cobro`, campos del nodo venta.',
            '- Desenlazar: `comprador` (pasajero reutilizable),',
            '  `asiento` real (vuelve a libre en el micro),',
            '  `viaje`/`micro`/`terminal` (referencias externas),',
            '  `rendido` del cupón (nodo Rendición que sigue vivo).',
            '- Helper: `_destruir_asiento_en_venta` (usado por',
            '  `cancelar_venta`, `_destruir_venta_actual` y',
            '  `ViajeAsientos.php`).',
            '',
            '**Venta actual (`confirmar_venta_actual`):**',
            '- Destruir: venta_actual, lista de asientos-en-venta',
            '  temporales.',
            '- Desenlazar: `terminal` (usuario externo), `asiento` real.',
            '- Helper: `_destruir_venta_actual`.',
            '',
            '**Asiento-en-venta (`seleccionar_asiento_micro`,',
            '`deseleccionar_asiento_micro`):**',
            '- Al cambiar de micro a mitad de selección, o al',
            '  deseleccionar un asiento, el nodo asiento-en-venta se',
            '  destruye.',
            '- Helper: `_destruir_asiento_en_venta`.',
            '',
            '**Cupón (`pagar_cupon_venta`):**',
            '- Al eliminar cupones sobrantes tras pagar el saldo, se',
            '  destruyen con sus campos.',
            '- Helper: `_eliminar_cupon_del_contenedor` (implícito).',
            '',
            '**Sesión (`cerrar_sesion`, `eliminar_sesiones_de_usuario`):**',
            '- Destruir: el nodo sesión con sus campos (`usuario`,',
            '  `creado_en`).',
            '',
            '**Campos hoja específicos:**',
            '- `bloqueado_hasta` en credenciales: al expirar el bloqueo',
            '  y al registrar login exitoso (`Autenticacion.php`).',
            '- `metodo_pago` del cupón: al coincidir con el método de la',
            '  venta (`Venta.php`).',
            '- `foto` del vehículo: al reemplazarla (`Vehiculo.php`).',
            '- `hora_estimada` de una parada: al quitarle la hora',
            '  (`Viaje.php`).',
            '- Campos al limpiar un valor en `actualizar_pasajero` y',
            '  `actualizar_usuario`.',
            '',
            '**Regla de oro:** si un nodo se desenlaza sin destruirse,',
            'primero preguntar "¿tiene otra referencia entrante?". Si la',
            'respuesta es no, hay que destruirlo.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v76d',
        'buscar' => [
            '- **v76c**: cierre del pendiente "convertir en modal la',
        ],
        'reemplazar' => [
            '- **v76d**: solo documentación. Se escribe completa la',
            '  sección §8.6.1 "Criterios de eliminación" con el',
            '  criterio concreto por entidad, extraído de los 19',
            '  flujos arreglados en Fase 2 (v74r-v75a) + el cierre',
            '  del Grupo B (v75a). Incluye reglas generales,',
            '  anti-patrones, criterio por entidad y referencia a',
            '  los helpers `_destruir_*` correspondientes.',
            '- **v76c**: cierre del pendiente "convertir en modal la',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: Última actualización a v76d',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.76c',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.76d',
            '(solo documentación. Se escribe completa la sección',
            '§8.6.1 "Criterios de eliminación" con el criterio por',
            'entidad extraído de toda la Fase 2.).',
            'Antes: v1.5piloto.76c',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§12: agregar bullet de cierre v76d',
        'buscar' => [
            '- Cerramos en v76c el pendiente "convertir en modal la',
        ],
        'reemplazar' => [
            '- Cerramos en v76d la sección §8.6.1 "Criterios de',
            '  eliminación": reemplazado el stub por el criterio',
            '  completo. Reglas generales, anti-patrones, criterio',
            '  por entidad, referencia a los helpers `_destruir_*`.',
            '- Cerramos en v76c el pendiente "convertir en modal la',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: estado del proyecto a v76d',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76c (framework 1.5i.7g).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.76d (framework 1.5i.7g).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§13: agregar línea de v76d',
        'buscar' => [
            'v76c: alta de empresa y vehículo a',
            'modal + eliminación de código muerto del HTML.',
        ],
        'reemplazar' => [
            'v76c: alta de empresa y vehículo a',
            'modal + eliminación de código muerto del HTML.',
            'v76d: §8.6.1 completado (documentación).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => '§8.6: marcar la sección de criterios como escrita',
        'buscar' => [
            '4. Documentar los criterios en §8.6.1.',
        ],
        'reemplazar' => [
            '4. Documentar los criterios en §8.6.1. **Hecho en v76d**',
            '   (ver §8.6.1).',
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