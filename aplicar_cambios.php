<?php
/**
 * Aplicador de cambios automáticos — Framework Iteradores (PHP).
 *
 * Tanda V1.5piloto.73r / Framework 1.5i.7f:
 * - Fix del bug latente `if ($elemento)` en Iterador.php.
 * - Limpieza de migraciones (index.php + miscelaneas/migrar_*.php).
 * - Prompts actualizados (regla de espejo JS, prompts solo en PHP).
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

    // ============================================================
    // Iteradores/Iterador.php — fix del bug if ($elemento)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Iteradores/Iterador.php',
        'descripcion' => 'Bump de version a 1.5i.7f',
        'buscar' => [
            ' * @version 1.5i.4 (inicio de refactorización)',
        ],
        'reemplazar' => [
            ' * @version 1.5i.7f (fix bug if($elemento) -> if($elemento!==null))',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Iteradores/Iterador.php',
        'descripcion' => 'crear_interno: if ($elemento) -> if ($elemento !== null)',
        'buscar' => [
            '        // asigno el actual en el caso de que sea valido',
            '        $nodo = null;',
            '        if ($elemento) {',
            '            if (!$nodo = $iterador->nodo($elemento, $es_nodo)) {',
        ],
        'reemplazar' => [
            '        // asigno el actual en el caso de que sea valido',
            '        // NOTA: el chequeo es `!== null` para no descartar falsy (0, \'\', false).',
            '        $nodo = null;',
            '        if ($elemento !== null) {',
            '            if (!$nodo = $iterador->nodo($elemento, $es_nodo)) {',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Iteradores/Iterador.php',
        'descripcion' => 'cargar_interno: if ($elemento) -> if ($elemento !== null)',
        'buscar' => [
            '        // verifico que el elemento de entrada sea valido',
            '        if ($elemento) {',
            '            $nodo = null;',
            '            if (!$nodo = $iterador->nodo($elemento, $es_nodo)) {',
        ],
        'reemplazar' => [
            '        // verifico que el elemento de entrada sea valido',
            '        // NOTA: el chequeo es `!== null` para no descartar falsy (0, \'\', false).',
            '        if ($elemento !== null) {',
            '            $nodo = null;',
            '            if (!$nodo = $iterador->nodo($elemento, $es_nodo)) {',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Iteradores/Iterador.php',
        'descripcion' => 'iterador_interno rama carga: if ($elemento) -> if ($elemento !== null)',
        'buscar' => [
            '            // elemento actual',
            '            if ($elemento) {',
            '                $nodo = null;',
            '                if (!$nodo = $iterador->nodo($elemento, $es_nodo)) {',
            '                    Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el elemento que intenta asignar con la carga de " . $nombre . " no es valido");',
        ],
        'reemplazar' => [
            '            // elemento actual',
            '            // NOTA: el chequeo es `!== null` para no descartar falsy (0, \'\', false).',
            '            if ($elemento !== null) {',
            '                $nodo = null;',
            '                if (!$nodo = $iterador->nodo($elemento, $es_nodo)) {',
            '                    Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el elemento que intenta asignar con la carga de " . $nombre . " no es valido");',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Iteradores/Iterador.php',
        'descripcion' => 'iterador_interno rama creacion: if ($elemento) -> if ($elemento !== null)',
        'buscar' => [
            '            if ($elemento) {',
            '                if (!$nodo = $iterador->nodo($elemento, $es_nodo)) {',
            '                    Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el elemento que intenta asignar con la creacion de " . $nombre . " no es valido");',
        ],
        'reemplazar' => [
            '            // NOTA: el chequeo es `!== null` para no descartar falsy (0, \'\', false).',
            '            if ($elemento !== null) {',
            '                if (!$nodo = $iterador->nodo($elemento, $es_nodo)) {',
            '                    Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el elemento que intenta asignar con la creacion de " . $nombre . " no es valido");',
        ],
    ],

    // ============================================================
    // index.php — eliminar bloques de migración
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Eliminar bloque ?migrar_micros',
        'buscar' => [
            '// ==== Bloque temporal para migración de micros (versión 1.5piloto.27) ====',
            'if (isset($_GET[\'migrar_micros\'])) {',
            '    require_once __DIR__ . \'/miscelaneas/migrar_micros.php\';',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    $res = migrar_micros_empresa();',
            '    echo "Migración completada.\\n";',
            '    echo "Dueños procesados: {$res[\'duenos_procesados\']}\\n";',
            '    echo "Micros procesados: {$res[\'micros_procesados\']}\\n";',
            '    echo "Micros migrados:   {$res[\'micros_migrados\']}\\n";',
            '    echo "Sin cambios:       {$res[\'micros_sin_cambio\']}\\n";',
            '    echo "Sin empresa:       {$res[\'micros_sin_empresa\']}\\n";',
            '    echo "Sin match:         {$res[\'micros_sin_match\']}\\n";',
            '    exit;',
            '}',
        ],
        'reemplazar' => [
            '// (bloque ?migrar_micros eliminado en v73r)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Eliminar bloque ?migrar_micros_patente',
        'buscar' => [
            '// ==== Bloque temporal para migración de patente en micros (v1.5piloto.27) ====',
            'if (isset($_GET[\'migrar_micros_patente\'])) {',
            '    require_once __DIR__ . \'/miscelaneas/migrar_micros_patente.php\';',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    $res = migrar_micros_patente();',
            '    echo "Migración de patente completada.\\n";',
            '    echo "Micros procesados:  {$res[\'micros_procesados\']}\\n";',
            '    echo "Micros limpiados:   {$res[\'micros_limpiados\']}\\n";',
            '    echo "Sin enlace previo:  {$res[\'micros_sin_enlace\']}\\n";',
            '    echo "Sin copia:          {$res[\'micros_sin_copia\']}\\n";',
            '    exit;',
            '}',
        ],
        'reemplazar' => [
            '// (bloque ?migrar_micros_patente eliminado en v73r)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Eliminar bloque ?migrar_terminales_autorizadas (cuerpo)',
        'buscar' => [
            '// ==== Bloque temporal para migración de terminales autorizadas (v1.5piloto.31) ====',
            '// Convierte los enlaces directos al Nodo Usuario terminal en nodos intermedios',
            '// "TerminalViaje", que cuelgan del contenedor `terminales_autorizadas`.',
            '// Es idempotente: si un enlace ya apunta a un TerminalViaje, lo saltea.',
            'if (isset($_GET[\'migrar_terminales_autorizadas\'])) {',
            '    require_once __DIR__ . \'/miscelaneas/migrar_terminales_autorizadas.php\';',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    $res = migrar_terminales_autorizadas();',
            '    echo "Migración de terminales autorizadas completada.\\n";',
            '    echo "Dueños procesados:         {$res[\'duenos_procesados\']}\\n";',
            '    echo "Viajes procesados:         {$res[\'viajes_procesados\']}\\n";',
            '    echo "Terminales migradas:       {$res[\'terminales_migradas\']}\\n";',
            '    echo "Terminales sin cambio:     {$res[\'terminales_sin_cambio\']}\\n";',
            '    echo "Terminales sin nodo usuario: {$res[\'terminales_sin_nodo_usuario\']}\\n";',
            '    exit;',
            '}',
        ],
        'reemplazar' => [
            '// (bloque ?migrar_terminales_autorizadas eliminado en v73r)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Eliminar bloque ?migrar_cupones',
        'buscar' => [
            '// ==== Bloque temporal para migración de cupones (v1.5piloto.44) ====',
            '// Crea el contenedor `cupones` en cada venta existente que no lo tenga.',
            '// Es idempotente: si una venta ya tiene cupones, la saltea.',
            'if (isset($_GET[\'migrar_cupones\'])) {',
            '    require_once __DIR__ . \'/miscelaneas/migrar_cupones.php\';',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    $res = migrar_cupones();',
            '    echo "Migración de cupones completada.\\n";',
            '    echo "Dueños procesados:    {$res[\'duenos_procesados\']}\\n";',
            '    echo "Ventas procesadas:    {$res[\'ventas_procesadas\']}\\n";',
            '    echo "Ventas migradas:      {$res[\'ventas_migradas\']}\\n";',
            '    echo "Ventas ya migradas:   {$res[\'ventas_ya_migradas\']}\\n";',
            '    echo "Ventas sin datos:     {$res[\'ventas_sin_datos\']}\\n";',
            '    exit;',
            '}',
        ],
        'reemplazar' => [
            '// (bloque ?migrar_cupones eliminado en v73r)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Eliminar bloque ?migrar_nombres_pasajeros',
        'buscar' => [
            '// ==== Bloque temporal para migración de nombres de pasajeros (v1.5piloto.36) ====',
            '// Separa el campo `nombre` de cada pasajero en dos enlaces: `nombres` y `apellido`.',
            '// Toma la última palabra como apellido y el resto como nombres. Si el nombre',
            '// original tiene una sola palabra, el apellido queda vacío.',
            '// Es idempotente: si un pasajero ya está migrado, lo saltea.',
            'if (isset($_GET[\'migrar_nombres_pasajeros\'])) {',
            '    require_once __DIR__ . \'/miscelaneas/migrar_nombres_pasajeros.php\';',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    $res = migrar_nombres_pasajeros();',
            '    echo "Migración de nombres de pasajeros completada.\\n";',
            '    echo "Dueños procesados:    {$res[\'duenos_procesados\']}\\n";',
            '    echo "Pasajeros procesados: {$res[\'pasajeros_procesados\']}\\n";',
            '    echo "Migrados:             {$res[\'migrados\']}\\n";',
            '    echo "Sin cambio:           {$res[\'sin_cambio\']}\\n";',
            '    echo "Sin nombre:           {$res[\'sin_nombre\']}\\n";',
            '    echo "Sin apellido:         {$res[\'sin_apellido\']}\\n";',
            '    exit;',
            '}',
        ],
        'reemplazar' => [
            '// (bloque ?migrar_nombres_pasajeros eliminado en v73r)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Eliminar bloque ?migrar_fecha_ultima_modificacion_pasajeros',
        'buscar' => [
            '// ==== Bloque temporal para migración de fecha_ultima_modificacion de pasajeros (v1.5piloto.58) ====',
            '// Siembra el enlace `fecha_ultima_modificacion` en los pasajeros existentes.',
            '// Los que aparecen en alguna venta toman la fecha de la venta más reciente',
            '// (formato ISO). Los que no, quedan con "2000-01-01".',
            '// Es idempotente: si un pasajero ya tiene el enlace, lo saltea.',
            'if (isset($_GET[\'migrar_fecha_ultima_modificacion_pasajeros\'])) {',
            '    require_once __DIR__ . \'/miscelaneas/migrar_fecha_ultima_modificacion_pasajeros.php\';',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    $res = migrar_fecha_ultima_modificacion_pasajeros();',
            '    echo "Migración de fecha_ultima_modificacion de pasajeros completada.\\n";',
            '    echo "Dueños procesados:      {$res[\'duenos_procesados\']}\\n";',
            '    echo "Pasajeros procesados:   {$res[\'pasajeros_procesados\']}\\n";',
            '    echo "Migrados con venta:     {$res[\'migrados_con_venta\']}\\n";',
            '    echo "Migrados fecha vieja:   {$res[\'migrados_con_fecha_vieja\']}\\n";',
            '    echo "Ya migrados:            {$res[\'ya_migrados\']}\\n";',
            '    exit;',
            '}',
        ],
        'reemplazar' => [
            '// (bloque ?migrar_fecha_ultima_modificacion_pasajeros eliminado en v73r)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Eliminar bloque ?migrar_declaraciones_juradas_v2',
        'buscar' => [
            '// ==== Bloque temporal para migración de declaraciones juradas v2 (v1.5piloto.62b) ====',
            '// Actualiza los fragmentos de texto por defecto del consentimiento de las',
            '// declaraciones juradas, SOLO si el contenido actual coincide con el texto',
            '// por defecto anterior. Si el dueño ya editó el texto, no lo toca.',
            '// Es idempotente: si se corre dos veces, la segunda no hace nada.',
            'if (isset($_GET[\'migrar_declaraciones_juradas_v2\'])) {',
            '    require_once __DIR__ . \'/miscelaneas/migrar_declaraciones_juradas_v2.php\';',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    $res = migrar_declaraciones_juradas_v2();',
            '    echo "Migración de declaraciones juradas v2 completada.\\n";',
            '    echo "Dueños procesados:         {$res[\'duenos_procesados\']}\\n";',
            '    echo "Viajes procesados:         {$res[\'viajes_procesados\']}\\n";',
            '    echo "--- Anexo I (mayor) ---\\n";',
            '    echo "Migrados:                  {$res[\'mayor_migrados\']}\\n";',
            '    echo "Sin cambio (por defecto):  {$res[\'mayor_sin_cambio\']}\\n";',
            '    echo "Personalizados:            {$res[\'mayor_personalizados\']}\\n";',
            '    echo "--- Anexo II (menor) ---\\n";',
            '    echo "Migrados:                  {$res[\'menor_migrados\']}\\n";',
            '    echo "Sin cambio (por defecto):  {$res[\'menor_sin_cambio\']}\\n";',
            '    echo "Personalizados:            {$res[\'menor_personalizados\']}\\n";',
            '    exit;',
            '}',
        ],
        'reemplazar' => [
            '// (bloque ?migrar_declaraciones_juradas_v2 eliminado en v73r)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Eliminar bloque ?migrar_declaraciones_juradas_v3',
        'buscar' => [
            '// ==== Bloque temporal para migración de declaraciones juradas v3 (v1.5piloto.62d) ====',
            '// Reemplaza "a realizarse en [puntos]," por "a realizarse en {{DESTINO_VIAJE}},"',
            '// en los textos guardados, y agrega la leyenda "(tildar o marcar con una X)"',
            '// al bloque de checkboxes padre/madre/tutor del Anexo II.',
            '// Solo actúa si encuentra el patrón exacto. Si el dueño personalizó esas',
            '// partes, no las toca. Es idempotente.',
            'if (isset($_GET[\'migrar_declaraciones_juradas_v3\'])) {',
            '    require_once __DIR__ . \'/miscelaneas/migrar_declaraciones_juradas_v3.php\';',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    $res = migrar_declaraciones_juradas_v3();',
            '    echo "Migración de declaraciones juradas v3 completada.\\n";',
            '    echo "Dueños procesados:         {$res[\'duenos_procesados\']}\\n";',
            '    echo "Viajes procesados:         {$res[\'viajes_procesados\']}\\n";',
            '    echo "--- Anexo I (mayor) ---\\n";',
            '    echo "Migrados:                  {$res[\'mayor_migrados\']}\\n";',
            '    echo "Sin cambio:                {$res[\'mayor_sin_cambio\']}\\n";',
            '    echo "--- Anexo II (menor) ---\\n";',
            '    echo "Migrados:                  {$res[\'menor_migrados\']}\\n";',
            '    echo "Sin cambio:                {$res[\'menor_sin_cambio\']}\\n";',
            '    exit;',
            '}',
        ],
        'reemplazar' => [
            '// (bloque ?migrar_declaraciones_juradas_v3 eliminado en v73r)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Eliminar bloque ?migrar_fichas_medicas',
        'buscar' => [
            '// ==== Bloque temporal para migración de fichas médicas (v1.5piloto.68) ====',
            '// Recorre todos los pasajeros y elimina el nodo `ficha_salud` (con sus',
            '// hijos) si existe. Recorre todos los viajes y elimina el enlace',
            '// `mostrar_ficha_medica` de sus opciones avanzadas si existe.',
            '// Es idempotente: si se corre dos veces, la segunda no hace nada.',
            'if (isset($_GET[\'migrar_fichas_medicas\'])) {',
            '    require_once __DIR__ . \'/miscelaneas/migrar_fichas_medicas.php\';',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    $res = migrar_fichas_medicas();',
            '    echo "Migración de fichas médicas completada.\\n";',
            '    echo "--- Fichas de salud ---\\n";',
            '    echo "Dueños procesados:              {$res[\'duenos_procesados\']}\\n";',
            '    echo "Pasajeros procesados:           {$res[\'pasajeros_procesados\']}\\n";',
            '    echo "Fichas eliminadas:              {$res[\'fichas_eliminadas\']}\\n";',
            '    echo "Sin ficha (ya limpios):         {$res[\'sin_ficha\']}\\n";',
            '    echo "--- Opciones de viaje ---\\n";',
            '    echo "Viajes procesados:              {$res[\'viajes_procesados\']}\\n";',
            '    echo "Enlaces mostrar_ficha_medica:   {$res[\'mostrar_ficha_eliminados\']}\\n";',
            '    echo "Sin enlace (ya limpios):        {$res[\'sin_mostrar_ficha\']}\\n";',
            '    exit;',
            '}',
        ],
        'reemplazar' => [
            '// (bloque ?migrar_fichas_medicas eliminado en v73r)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Eliminar bloque ?migrar_hashear_credenciales',
        'buscar' => [
            '// ==== Bloque temporal para migración de hasheo de credenciales (v1.5piloto.68) ====',
            '// Recorre todos los usuarios y convierte el `codigo_acceso` en texto plano',
            '// a `codigo_hash` con password_hash(). Elimina el enlace viejo.',
            '// Es idempotente: si un usuario ya tiene codigo_hash, lo saltea.',
            'if (isset($_GET[\'migrar_hashear_credenciales\'])) {',
            '    require_once __DIR__ . \'/miscelaneas/migrar_hashear_credenciales.php\';',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    $res = migrar_hashear_credenciales();',
            '    echo "Migración de hasheo de credenciales completada.\\n";',
            '    echo "Usuarios procesados: {$res[\'usuarios_procesados\']}\\n";',
            '    echo "Migrados:            {$res[\'migrados\']}\\n";',
            '    echo "Ya migrados:         {$res[\'ya_migrados\']}\\n";',
            '    echo "Sin código:          {$res[\'sin_codigo\']}\\n";',
            '    exit;',
            '}',
        ],
        'reemplazar' => [
            '// (bloque ?migrar_hashear_credenciales eliminado en v73r)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'Eliminar bloque ?migrar_separar_grafos',
        'buscar' => [
            '// ==== Bloque temporal para migración de separación de grafos (v1.5piloto.69) ====',
            '// Copia codigo_hash y contrasena de cada usuario al grafo de credenciales',
            '// y los elimina del grafo de la aplicación.',
            '// Es idempotente: si un usuario ya está migrado, lo saltea.',
            'if (isset($_GET[\'migrar_separar_grafos\'])) {',
            '    require_once __DIR__ . \'/miscelaneas/migrar_separar_grafos.php\';',
            '    header(\'Content-Type: text/plain; charset=utf-8\');',
            '    $res = migrar_separar_grafos();',
            '    echo "Migración de separación de grafos completada.\\n";',
            '    echo "Usuarios procesados:    {$res[\'usuarios_procesados\']}\\n";',
            '    echo "Credenciales copiadas:  {$res[\'credenciales_copiadas\']}\\n";',
            '    echo "Códigos copiados:       {$res[\'codigos_copiados\']}\\n";',
            '    echo "Contraseñas copiadas:   {$res[\'contrasenas_copiadas\']}\\n";',
            '    echo "Ya migrados:            {$res[\'ya_migrados\']}\\n";',
            '    echo "Sin credenciales:       {$res[\'sin_credenciales\']}\\n";',
            '    exit;',
            '}',
        ],
        'reemplazar' => [
            '// (bloque ?migrar_separar_grafos eliminado en v73r)',
        ],
    ],

    // ============================================================
    // Eliminar archivos miscelaneas/migrar_*.php
    // ============================================================

    [ 'tipo' => 'eliminar', 'archivo' => 'miscelaneas/migrar_micros.php', 'descripcion' => 'Migración v27' ],
    [ 'tipo' => 'eliminar', 'archivo' => 'miscelaneas/migrar_micros_patente.php', 'descripcion' => 'Migración v27' ],
    [ 'tipo' => 'eliminar', 'archivo' => 'miscelaneas/migrar_terminales_autorizadas.php', 'descripcion' => 'Migración v31' ],
    [ 'tipo' => 'eliminar', 'archivo' => 'miscelaneas/migrar_cupones.php', 'descripcion' => 'Migración v44' ],
    [ 'tipo' => 'eliminar', 'archivo' => 'miscelaneas/migrar_nombres_pasajeros.php', 'descripcion' => 'Migración v36' ],
    [ 'tipo' => 'eliminar', 'archivo' => 'miscelaneas/migrar_fecha_ultima_modificacion_pasajeros.php', 'descripcion' => 'Migración v58' ],
    [ 'tipo' => 'eliminar', 'archivo' => 'miscelaneas/migrar_declaraciones_juradas_v2.php', 'descripcion' => 'Migración v62b' ],
    [ 'tipo' => 'eliminar', 'archivo' => 'miscelaneas/migrar_declaraciones_juradas_v3.php', 'descripcion' => 'Migración v62d' ],
    [ 'tipo' => 'eliminar', 'archivo' => 'miscelaneas/migrar_fichas_medicas.php', 'descripcion' => 'Migración v68' ],
    [ 'tipo' => 'eliminar', 'archivo' => 'miscelaneas/migrar_hashear_credenciales.php', 'descripcion' => 'Migración v68' ],
    [ 'tipo' => 'eliminar', 'archivo' => 'miscelaneas/migrar_separar_grafos.php', 'descripcion' => 'Migración v69' ],

    // ============================================================
    // prompts/prompt_framework_iteradores.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Seccion 12.3: bug if(elemento) resuelto',
        'buscar' => [
            'En todos los casos, si el elemento inicial es `0`, `\'\'` o `false`,',
            'el iterador se crea/carga sin posición actual. Es un bug latente',
            '(nadie inicializa iteradores con esos valores en la práctica), pero',
            'real. El fix es:',
            '',
            '- PHP: `if ($elemento !== null)`',
            '- JS: `if (elemento !== null && elemento !== undefined)`',
            '',
            'Pendiente en ambos espejos.',
        ],
        'reemplazar' => [
            'En todos los casos, si el elemento inicial es `0`, `\'\'` o `false`,',
            'el iterador se crea/carga sin posición actual. Es un bug latente',
            '(nadie inicializa iteradores con esos valores en la práctica), pero',
            'real. El fix es:',
            '',
            '- PHP: `if ($elemento !== null)`',
            '- JS: `if (elemento !== null && elemento !== undefined)`',
            '',
            '**Resuelto** en v73r (PHP) y V1.5i.7f (JS).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Historial framework: agregar 1.5i.7f',
        'buscar' => [
            '- **1.5i.7e**: sin cambios funcionales al framework. Documentación:',
            '  en la sección 12 del prompt se aclaran los cambios del PHP que no',
            '  tienen análogo en JS (libxml, `glob()`, `real_escape_string`), y',
            '  se documenta que el bug latente `if ($elemento)` de `Iterador`',
            '  existe en AMBOS espejos (PHP y JS).',
        ],
        'reemplazar' => [
            '- **1.5i.7e**: sin cambios funcionales al framework. Documentación:',
            '  en la sección 12 del prompt se aclaran los cambios del PHP que no',
            '  tienen análogo en JS (libxml, `glob()`, `real_escape_string`), y',
            '  se documenta que el bug latente `if ($elemento)` de `Iterador`',
            '  existe en AMBOS espejos (PHP y JS).',
            '- **1.5i.7f**: fix del bug latente `if ($elemento)` en `Iterador.php`.',
            '  Los 4 usos (en `crear_interno`, `cargar_interno` y',
            '  `iterador_interno` dos veces) se cambian por `if ($elemento !==',
            '  null)`. Así los valores falsy (`0`, `\'\'`, `false`) ya no se',
            '  descartan. Mismo fix aplicado al espejo JS (V1.5i.7f).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Seccion 12.5: agregar 1.5i.7f',
        'buscar' => [
            '- **1.5i.7**: alineación con PHP (`limpiar_deposito_ids` borra solo',
            '  especiales), test con comparación string/number, silencio de',
            '  alertas en `#crear_datos_insertar_adyacentes`.',
        ],
        'reemplazar' => [
            '- **1.5i.7**: alineación con PHP (`limpiar_deposito_ids` borra solo',
            '  especiales), test con comparación string/number, silencio de',
            '  alertas en `#crear_datos_insertar_adyacentes`.',
            '- **1.5i.7f**: fix del bug latente `if (elemento)` en',
            '  `_crear_interno`, `_cargar_interno` y `_iterador_interno`',
            '  (dos veces). Espejo del fix PHP 1.5i.7f.',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Estructura: quitar migraciones de miscelaneas',
        'buscar' => [
            '**`miscelaneas/`**: `Arbol.php`, `benchmark.php`, `generarUUID.php`, y',
            'scripts de migración (`migrar_*.php`).',
        ],
        'reemplazar' => [
            '**`miscelaneas/`**: `Arbol.php`, `benchmark.php`, `generarUUID.php`.',
            '(Los scripts `migrar_*.php` se eliminaron en v73r.)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Seccion 8.2: limpieza completada',
        'buscar' => [
            '### 8.2 Limpieza pendiente',
            '',
            '**Integridad de las tablas SQL:** verificada con `CHECK TABLE` y',
            '`OPTIMIZE TABLE` en local y en producción el 30/09/2026, ambas OK.',
            'No queda pendiente.',
            '',
            'Bloques y archivos de migración que se pueden eliminar cuando se',
            'confirmen en los 3 entornos:',
            '',
            '- `miscelaneas/migrar_fichas_medicas.php` + bloque',
            '  `?migrar_fichas_medicas=1`.',
            '- `miscelaneas/migrar_hashear_credenciales.php` + bloque',
            '  `?migrar_hashear_credenciales=1`.',
            '- `miscelaneas/migrar_separar_grafos.php` + bloque',
            '  `?migrar_separar_grafos=1`.',
            '',
            'Migraciones más viejas que también se pueden limpiar: `migrar_micros`,',
            '`migrar_micros_patente`, `migrar_terminales_autorizadas`,',
            '`migrar_cupones`, `migrar_nombres_pasajeros`,',
            '`migrar_fecha_ultima_modificacion_pasajeros`,',
            '`migrar_declaraciones_juradas_v2`, `migrar_declaraciones_juradas_v3`.',
            '',
            '`migrar_pasajeros.php` sigue usando `Controlador::guardar($nombre_app)`',
            'directo. Es histórico, no vale la pena migrarlo.',
        ],
        'reemplazar' => [
            '### 8.2 Limpieza de migraciones',
            '',
            '**Integridad de las tablas SQL:** verificada con `CHECK TABLE` y',
            '`OPTIMIZE TABLE` en local y en producción el 30/09/2026, ambas OK.',
            '',
            '**Limpieza de migraciones: completada en v73r.** Se eliminaron los',
            'bloques `?migrar_*=1` de `index.php` y los archivos',
            '`miscelaneas/migrar_*.php`:',
            '',
            '- `migrar_micros`, `migrar_micros_patente`,',
            '  `migrar_terminales_autorizadas`, `migrar_cupones`,',
            '  `migrar_nombres_pasajeros`,',
            '  `migrar_fecha_ultima_modificacion_pasajeros`,',
            '  `migrar_declaraciones_juradas_v2`,',
            '  `migrar_declaraciones_juradas_v3`, `migrar_fichas_medicas`,',
            '  `migrar_hashear_credenciales`, `migrar_separar_grafos`.',
            '',
            '`migrar_pasajeros.php` se conserva: es histórico y no vale la pena',
            'migrarlo. No se toca.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Historial: agregar v73r',
        'buscar' => [
            '- **v73q**: tanda de documentación. Se aclaran en el prompt del',
            '  framework los cambios del PHP que no tienen análogo en JS, y se',
            '  documenta que el bug latente `if ($elemento)` de `Iterador`',
            '  existe en AMBOS espejos.',
        ],
        'reemplazar' => [
            '- **v73q**: tanda de documentación. Se aclaran en el prompt del',
            '  framework los cambios del PHP que no tienen análogo en JS, y se',
            '  documenta que el bug latente `if ($elemento)` de `Iterador`',
            '  existe en AMBOS espejos.',
            '- **v73r**: fix del bug latente `if ($elemento)` en `Iterador.php`',
            '  (framework 1.5i.7f). Limpieza completa de migraciones: se',
            '  eliminaron los bloques `?migrar_*=1` de `index.php` y los',
            '  archivos `miscelaneas/migrar_*.php` (11 en total). Se conserva',
            '  `migrar_pasajeros.php`. Prompts: se documenta que los prompts',
            '  viven únicamente en el proyecto PHP y la regla de espejo JS.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Discusion actual: bump a v73r',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73q (tanda de',
            'documentación: cambios sin análogo en JS; bug latente',
            '`if ($elemento)` en ambos espejos de `Iterador`).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73r (fix del',
            'bug `if ($elemento)` en `Iterador.php`; limpieza de migraciones).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Estado de la conversacion: agregar v73r',
        'buscar' => [
            '- Cerramos en v73q la documentación del espejo JS y del bug',
            '  latente `if ($elemento)` de `Iterador`.',
            '- No hay tandas en curso.',
        ],
        'reemplazar' => [
            '- Cerramos en v73q la documentación del espejo JS y del bug',
            '  latente `if ($elemento)` de `Iterador`.',
            '- Cerramos en v73r el fix del bug `if ($elemento)` (PHP y JS) y la',
            '  limpieza completa de migraciones.',
            '- No hay tandas en curso.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Estado del proyecto al cierre',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73q (framework 1.5i.7e).',
            'Todo funcional. Listo para arrancar la diversificación por tipo de',
            'aplicación.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73r (framework 1.5i.7f).',
            'Todo funcional. Listo para arrancar la diversificación por tipo de',
            'aplicación.',
        ],
    ],

    // ============================================================
    // prompts/prompt_sistema_scripts.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'Nueva seccion sobre el espejo JS',
        'buscar' => [
            '## EL ARCHIVO `aplicar_cambios.php`',
        ],
        'reemplazar' => [
            '## ESPEJO JS DEL FRAMEWORK',
            '',
            'El framework Iteradores tiene **dos implementaciones** que deben',
            'mantenerse espejadas:',
            '',
            '- **PHP** (`iteradores/`): la implementación principal.',
            '- **JS** (`iteradoresJS/`): el espejo para navegador.',
            '',
            '**Regla sin excepción:** cada vez que se toca el framework en',
            'cualquiera de los dos proyectos, hay que reflejar el cambio en el',
            'otro, con:',
            '',
            '- **Dos `aplicar_cambios.php` distintos**, uno por proyecto.',
            '- **Dos commits distintos**, uno por proyecto.',
            '',
            'Los dos scripts se entregan en el mismo mensaje del asistente, pero',
            'se corren por separado y se commitean por separado. El motivo es que',
            'son repositorios independientes con historias independientes.',
            '',
            '**Los prompts viven únicamente en el proyecto PHP** (`prompts/`).',
            'El proyecto JS no tiene su propia copia de los prompts. Cuando se',
            'actualiza un prompt por un cambio en el framework, se hace en el',
            '`aplicar_cambios.php` del proyecto PHP.',
            '',
            '### Cuándo aplica esta regla',
            '',
            '- Cambios a `Nodo`, `Iterador`, `Controlador`, `Objeto`.',
            '- Cambios a las implementaciones de persistencia.',
            '- Cambios a cualquier helper del framework.',
            '',
            '### Cuándo NO aplica',
            '',
            '- Cambios al piloto (solo existen en el proyecto PHP).',
            '- Cambios a los prompts (solo existen en el proyecto PHP).',
            '- Cambios a la UI del piloto (solo existe en el proyecto PHP).',
            '',
            '---',
            '',
            '## EL ARCHIVO `aplicar_cambios.php`',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_sistema_scripts.md',
        'descripcion' => 'Discusion actual: mencionar regla del espejo',
        'buscar' => [
            '## DISCUSIÓN ACTUAL',
            '',
            '**Última actualización de este prompt:** v1.5piloto.73l. Se agregó el',
            'tipo `eliminar` al runner, para poder borrar archivos que ya no se usan',
            'en una tanda.',
        ],
        'reemplazar' => [
            '## DISCUSIÓN ACTUAL',
            '',
            '**Última actualización de este prompt:** v1.5piloto.73r. Se agregó la',
            'sección "ESPEJO JS DEL FRAMEWORK" (regla de mantener PHP y JS',
            'espejados con dos `aplicar_cambios.php` y dos commits distintos;',
            'prompts solo en PHP).',
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