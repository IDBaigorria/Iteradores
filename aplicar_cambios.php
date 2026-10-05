<?php
/**
 * Aplicador de cambios automáticos — proyecto Iteradores (piloto PHP).
 *
 * Tanda v1.5piloto.74q: cierre de documentación.
 *
 * - prompts/prompt_framework_iteradores.md: nueva sección 11
 *   "Limitaciones conocidas" + aprendizajes 21-23 en §13.
 * - prompts/prompt_sistema_scripts.md: aprendizajes 15-17.
 * - prompts/prompt_piloto.md: dos pendientes nuevos en §8.5 y
 *   actualización de §12/§13.
 *
 * Uso:
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

$cambios = [

    // ========================================================
    // Framework — nueva sección 11
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'framework: nueva seccion 11 Limitaciones conocidas',
        'buscar' => [
            '---',
            '',
            '## 10. HISTORIAL DEL FRAMEWORK',
        ],
        'reemplazar' => [
            '---',
            '',
            '## 11. LIMITACIONES CONOCIDAS DEL FRAMEWORK',
            '',
            'Esta sección documenta limitaciones estructurales del framework',
            'que no son bugs, pero que condicionan su uso. Son candidatas a',
            'mejora en futuras versiones del framework.',
            '',
            '### 11.1 Carga y guardado del grafo entero',
            '',
            'Toda operación de persistencia (`Controlador::cargar`,',
            '`Controlador::guardar`) procesa el grafo completo. No existe hoy',
            'un mecanismo para cargar o guardar **partes reducidas** del grafo',
            '(por ejemplo, solo la rama de un dueño, solo los nodos de un viaje).',
            '',
            '**Consecuencia:** el tiempo de cada operación crece linealmente con',
            'la cantidad total de nodos. Los listados, las altas y las bajas',
            'también, porque iteran sobre el grafo completo.',
            '',
            '**Caso testigo:** el piloto llegó a un grafo de ~10.000 nodos, con',
            'tiempos de 50-70s por operación. Con ~2.000 nodos, los mismos',
            'tiempos bajaron a 15-18s. La performance depende directamente del',
            'tamaño total del grafo.',
            '',
            '**Posibles direcciones (a discutir):**',
            '',
            '- Guardar en cada nodo un "ID especial de origen" o similar, para',
            '  reconstruir sub-grafos.',
            '  - **Problema:** un nodo puede estar referenciado desde más de un',
            '    lado. No hay un árbol natural de pertenencia. Requiere revisar',
            '    teoría de grafos (componentes conexas, sub-grafos inducidos).',
            '- Persistir por partes usando índices auxiliares.',
            '- Snapshot por rama con marca de "raíz".',
            '',
            'Requiere una sesión del framework, no del piloto.',
            '',
            '### 11.2 Fuga de nodos huérfanos',
            '',
            '`Nodo::eliminar($nodo)` falla si el nodo tiene referencias',
            'entrantes. La forma correcta de eliminarlo es desenlazar todas las',
            'referencias entrantes antes, de a una, empezando por las hojas.',
            '',
            '**Consecuencia:** si el código que elimina entidades no hace este',
            'desenlazado progresivo, los nodos quedan **huérfanos**: ya no se',
            'alcanzan desde ninguna raíz, pero siguen ocupando memoria y disco.',
            'No hay recolección automática de basura.',
            '',
            '**Caso testigo:** el piloto acumuló miles de nodos huérfanos',
            '(asientos de ventas canceladas, cupones, nodos de pasajeros',
            'borrados, etc.). El grafo creció de 2.000 a 10.000 nodos. Afectó la',
            'performance global.',
            '',
            '**Posibles direcciones (a discutir):**',
            '',
            '- Extender el framework con un garbage collector que recorra el',
            '  grafo y libere nodos no alcanzables desde raíces especiales.',
            '- Documentar patrones de "eliminación progresiva" como el de §7.4.',
            '- Proveer helpers de "desenlazado en cascada" para el caso común.',
            '',
            '### 11.3 Iteradores persistentes subutilizados',
            '',
            'El framework tiene Iteradores con posición persistente entre',
            'operaciones. Son una herramienta para reducir recorridos repetidos',
            'sobre el grafo (por ejemplo, mantener un puntero a "última venta',
            'creada" o "último viaje activo").',
            '',
            'En la práctica, el piloto rara vez los usa: la mayoría de las',
            'operaciones abren un nuevo recorrido desde las raíces.',
            '',
            '**Posible mejora:** usar iteradores persistentes en los flujos de',
            'lectura frecuente para reducir el costo O(N) por operación.',
            'Requiere diseñar qué iteradores conviene mantener y dónde',
            'persistirlos.',
            '',
            '---',
            '',
            '## 10. HISTORIAL DEL FRAMEWORK',
        ],
    ],

    // ========================================================
    // Framework — entrada al historial (1.5i.7g)
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'framework: agregar 1.5i.7g al historial',
        'buscar' => [
            '  descartan. Mismo fix aplicado al espejo JS (V1.5i.7f).',
            '',
            'El espejo JS también recibió mejoras en paralelo (ver sección 12).',
        ],
        'reemplazar' => [
            '  descartan. Mismo fix aplicado al espejo JS (V1.5i.7f).',
            '- **1.5i.7g**: sin cambios funcionales al framework. Se agrega la',
            '  sección 11 "Limitaciones conocidas del framework" con tres',
            '  puntos: (a) toda operación procesa el grafo completo (sin',
            '  carga parcial); (b) los nodos huérfanos se acumulan porque',
            '  `Nodo::eliminar` falla con referencias entrantes y no hay',
            '  recolección automática; (c) los iteradores persistentes están',
            '  subutilizados. Estas limitaciones se descubrieron trabajando',
            '  en el piloto: llegó a 10.000 nodos con tiempos de 50-70s por',
            '  operación; con 2.000 nodos, 15-18s.',
            '',
            'El espejo JS también recibió mejoras en paralelo (ver sección 12).',
        ],
    ],

    // ========================================================
    // Framework — aprendizajes 21-23
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'framework: agregar aprendizajes 21-23',
        'buscar' => [
            '20. **`db.close()` en `finally`.** Las conexiones abiertas se',
            '    acumulan hasta que el navegador las recolecte.',
            '',
            '**Regla de oro:** cualquier cambio al framework PHP se refleja en',
            'JS en la misma tanda, con dos scripts y dos commits.',
        ],
        'reemplazar' => [
            '20. **`db.close()` en `finally`.** Las conexiones abiertas se',
            '    acumulan hasta que el navegador las recolecte.',
            '',
            '**Limitaciones estructurales (documentadas en v1.5i.7g, ver §11):**',
            '',
            '21. **Toda operación procesa el grafo completo.** No hay carga',
            '    parcial. El costo crece con N. Caso testigo: piloto con',
            '    10.000 nodos → 50-70s por operación; con 2.000 nodos,',
            '    15-18s.',
            '22. **Los nodos huérfanos se acumulan.** `Nodo::eliminar` falla',
            '    con referencias entrantes. Si el llamador no desenlaza',
            '    progresivamente, los nodos quedan huérfanos sin recolección',
            '    automática. El framework no incluye garbage collector.',
            '23. **Los iteradores persistentes están subutilizados.** Pueden',
            '    reducir recorridos repetidos. En la práctica, la mayoría de',
            '    las operaciones abren un nuevo recorrido desde las raíces.',
            '',
            '**Regla de oro:** cualquier cambio al framework PHP se refleja en',
            'JS en la misma tanda, con dos scripts y dos commits.',
        ],
    ],

    // ========================================================
    // Sistema de scripts — aprendizajes 15-17
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'sistema_scripts: agregar aprendizajes 15-17',
        'buscar' => [
            '14. **Cada cambio al piloto PHP lleva su espejo de pruebas en',
            '    el plugin JS.** Cuando la tanda toca el piloto (backend o',
            '    frontend), se entrega además un `aplicar_cambios.php` para',
            '    `iteradoresJS/` que agregue las pruebas del plugin que',
            '    verifiquen los cambios. Dos scripts, dos commits, dos',
            '    repos. La única excepción es cuando el cambio del piloto',
            '    no es verificable desde el plugin (por ejemplo, cambios',
            '    de estilo visual interno o refactors sin cambio de',
            '    comportamiento). Aun así, avisar al usuario que no se',
            '    agregan pruebas y por qué.',
            '',
            '---',
        ],
        'reemplazar' => [
            '14. **Cada cambio al piloto PHP lleva su espejo de pruebas en',
            '    el plugin JS.** Cuando la tanda toca el piloto (backend o',
            '    frontend), se entrega además un `aplicar_cambios.php` para',
            '    `iteradoresJS/` que agregue las pruebas del plugin que',
            '    verifiquen los cambios. Dos scripts, dos commits, dos',
            '    repos. La única excepción es cuando el cambio del piloto',
            '    no es verificable desde el plugin (por ejemplo, cambios',
            '    de estilo visual interno o refactors sin cambio de',
            '    comportamiento). Aun así, avisar al usuario que no se',
            '    agregan pruebas y por qué.',
            '15. **Verificar `git diff --stat` antes de commitear.** Un',
            '    archivo que no debería haber cambiado puede aparecer con',
            '    cientos de líneas modificadas. Caso real:',
            '    `Aplicacion/Vehiculos/Vehiculo.php` apareció con 983 líneas',
            '    cambiadas porque su contenido fue reemplazado con el output',
            '    del `aplicar_cambios.php` (probablemente un `>` mal',
            '    escrito o un pegado accidental en VSCode). Costó varias',
            '    rondas de diagnóstico. Regla: revisar el `diff --stat`',
            '    después de correr un script y antes de commitear.',
            '16. **Nunca usar `>` en la terminal con `php` sin estar',
            '    seguro.** El operador `>` redirige toda la salida del',
            '    comando al archivo indicado, pisando su contenido. Para',
            '    guardar el log del script, usar `| Out-File -Append` o',
            '    copiar a mano.',
            '17. **Las pruebas que comparten estado acumulan problemas.**',
            '    Si las pruebas del plugin dependen del estado compartido',
            '    del piloto (por ejemplo, la cantidad de viajes o de',
            '    nodos), cuando el estado crece las pruebas se vuelven',
            '    lentas y frágiles. Paliativo: limpieza periódica +',
            '    timeouts holgados. Solución real: independencia de estado',
            '    entre pruebas o setup por prueba.',
            '',
            '---',
        ],
    ],

    // ========================================================
    // Sistema de scripts — actualización de la Discusión actual
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'sistema_scripts: actualizar cabecera de discusion a v74q',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74i. Se',
            'incorporan dos reglas nuevas al método de trabajo:',
            '(13) cada `aplicar_cambios.php` va acompañado de un commit',
            'sugerido; (14) cada cambio al piloto PHP lleva su espejo de',
            'pruebas en el plugin JS de `iteradoresJS/`, con dos scripts y',
            'dos commits.',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74q. Se',
            'agregan tres aprendizajes a la fuerza (15-17): verificar',
            '`git diff --stat` antes de commitear (caso del `Vehiculo.php`',
            'pisado con el output del script); nunca usar `>` con `php` en',
            'la terminal; y las pruebas que comparten estado acumulan',
            'problemas.',
            'Antes: v1.5piloto.74i. Se',
            'incorporan dos reglas nuevas al método de trabajo:',
            '(13) cada `aplicar_cambios.php` va acompañado de un commit',
            'sugerido; (14) cada cambio al piloto PHP lleva su espejo de',
            'pruebas en el plugin JS de `iteradoresJS/`, con dos scripts y',
            'dos commits.',
        ],
    ],

    // ========================================================
    // Piloto — §8.5 Otros (agregar dos pendientes)
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'piloto: agregar dos pendientes en §8.5 Otros',
        'buscar' => [
            '**Otros**:',
            '',
            '- Panel de super admin (incremental al admin actual; alcance a',
            '  consensuar).',
            '- Métricas / reportes adicionales: solo ocupación por viaje y',
            '  consolidado de liquidaciones.',
        ],
        'reemplazar' => [
            '**Otros**:',
            '',
            '- Panel de super admin (incremental al admin actual; alcance a',
            '  consensuar).',
            '- Métricas / reportes adicionales: solo ocupación por viaje y',
            '  consolidado de liquidaciones.',
            '- **Cerrar todos los modales al cerrar sesión** (prioridad',
            '  media). Actualmente el modal chico de post-venta',
            '  (`#opciones_impresion`) y a veces otros quedan abiertos al',
            '  salir. Debería limpiarse todo en `salir()` de `aplicacion.js`.',
            '  El `_limpiar_contenido_dinamico` actual limpia el contenido',
            '  de los contenedores pero no oculta los overlays de modal',
            '  (`#modal_generico`, `#modal_apilado`) ni los modales chicos',
            '  flotantes (`#modal_chico_*`).',
            '- **Convertir en modal la carga de nuevas empresas y micros**',
            '  (prioridad media). Hoy son formularios inline',
            '  (`#formulario_nueva_empresa`, `#formulario_nuevo_vehiculo`).',
            '  Migrar al patrón de modal genérico como se hizo con usuarios',
            '  y terminales (v73e-v73g).',
        ],
    ],

    // ========================================================
    // Piloto — §12 estado de la conversación
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'piloto: §12 agregar cierre v74q al estado',
        'buscar' => [
            '- No hay tandas de código en curso en este proyecto.',
        ],
        'reemplazar' => [
            '- Cerramos en v74q la documentación de cierre: se agregaron',
            '  dos pendientes nuevos al backlog (§8.5): cerrar todos los',
            '  modales al cerrar sesión (prioridad media) y convertir en',
            '  modal la carga de empresas y micros (prioridad media).',
            '  También se documentó la limitación del framework en su',
            '  propio prompt (sección 11 nueva).',
            '- No hay tandas de código en curso en este proyecto.',
        ],
    ],

    // ========================================================
    // Piloto — historial v74q
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'piloto: agregar v74q al historial',
        'buscar' => [
            '- **v74p**: pestaña "Grafo" (Fase 1 del plan de optimización',
        ],
        'reemplazar' => [
            '- **v74q**: solo documentación. Se agregaron dos pendientes',
            '  al backlog (§8.5): cerrar todos los modales al cerrar',
            '  sesión (prioridad media) y convertir en modal la carga de',
            '  empresas y micros (prioridad media). El prompt del',
            '  framework ganó la sección 11 "Limitaciones conocidas"',
            '  (carga parcial, fuga de nodos, iteradores persistentes',
            '  subutilizados). El prompt del sistema de scripts ganó',
            '  tres aprendizajes (15-17).',
            '- **v74p**: pestaña "Grafo" (Fase 1 del plan de optimización',
        ],
    ],

    // ========================================================
    // Piloto — §12 cabecera a v74q
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'piloto: §12 cabecera a v74q',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.74p',
            '(pestaña "Grafo", Fase 1 del plan de optimización del grafo.',
            'Visible solo para admin y soporte. Vista de solo lectura.',
            'Backend: 3 comandos nuevos en el `Controlador`',
            '(`grafo:resumen`, `grafo:listar`, `grafo:nodo`) registrados',
            'desde `registrar_comandos_grafo()`, ejecutados vía',
            '`Controlador::ejecutar_comando()` sin usar el motor. Módulo',
            '`grafo` en el enrutador. Frontend nuevo: `Aplicacion/grafo.js`.',
            'Se agregaron §3.4 (pestaña Grafo), §8.6 (plan de las tres',
            'fases) y §8.6.1 (criterios de eliminación, stub)).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.74q',
            '(solo documentación. Se agregaron dos pendientes al backlog:',
            'cerrar todos los modales al cerrar sesión y convertir en modal',
            'la carga de empresas y micros, ambos de prioridad media).',
            'Antes: v1.5piloto.74p',
            '(pestaña "Grafo", Fase 1 del plan de optimización del grafo.',
            'Visible solo para admin y soporte. Vista de solo lectura.',
            'Backend: 3 comandos nuevos en el `Controlador`',
            '(`grafo:resumen`, `grafo:listar`, `grafo:nodo`) registrados',
            'desde `registrar_comandos_grafo()`, ejecutados vía',
            '`Controlador::ejecutar_comando()` sin usar el motor. Módulo',
            '`grafo` en el enrutador. Frontend nuevo: `Aplicacion/grafo.js`.',
            'Se agregaron §3.4 (pestaña Grafo), §8.6 (plan de las tres',
            'fases) y §8.6.1 (criterios de eliminación, stub)).',
        ],
    ],

    // ========================================================
    // Piloto — §13 cierre a v74q
    // ========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'piloto: §13 estado a v74q',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74p (framework 1.5i.7f).',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.74q (framework 1.5i.7g).',
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