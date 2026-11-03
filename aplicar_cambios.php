<?php
/**
 * Aplicador — Documentación Fase 1 de seguridad (sesión PHP).
 *
 * Solo toca prompts. Sin cambios de código, sin bumps.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$cambios = [
    // ============================================================
    // plan_actual.md
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1: version y estado',
        'buscar' => [
            '**Última actualización:** v1.5piloto.83s + framework 1.5i.7x',
            '(**Validado en producción:** una semana en pruebas y',
            'una semana en producción sin errores visibles ni',
            '`[error]` en el log de persistencia. Todos los guardados',
            'son `[info]`. Bug §4.7 cerrado y validado. Tanda 4',
            '(lock, usuario en log, cobertura de `guardar_ambos`,',
            'returns tempranos, login optimizado) confirmada en',
            'producción. Grupo A de limpieza aplicado (v83q). §8',
            '(nota de cierre de v82d) eliminado en v83s: cumplió su',
            'función, los pendientes reales viven en §4.1-4.4 y',
            '§4.8. Ver §2 y §4.8).',
        ],
        'reemplazar' => [
            '**Última actualización:** v1.5piloto.84a + framework 1.5i.7x',
            '(**Validado en producción:** una semana en pruebas y',
            'una semana en producción sin errores visibles ni',
            '`[error]` en el log de persistencia. Todos los guardados',
            'son `[info]`. Bug §4.7 cerrado y validado. Tanda 4',
            '(lock, usuario en log, cobertura de `guardar_ambos`,',
            'returns tempranos, login optimizado) confirmada en',
            'producción. Grupo A de limpieza aplicado (v83q).',
            '**Fase 1 de seguridad aplicada (v84a):** sesión PHP con',
            'cookie PHPSESSID + `$_SESSION`; login, logout local y',
            'cierre remoto funcionando. Próxima fase: impresión',
            'cerrada. Ver §2 y §8).',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §1: version del piloto',
        'buscar' => [
            '- **Piloto:** `1.5piloto.83q` (validado en producción;',
            '  una semana en pruebas y una en producción sin errores).',
        ],
        'reemplazar' => [
            '- **Piloto:** `1.5piloto.84a` (validado en producción',
            '  hasta v83q; v84a agrega sesión PHP, sin cambios en',
            '  persistencia).',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §2: arcos nuevos al inicio',
        'buscar' => [
            '## 2. ARCOS CERRADOS RECIENTES',
            '',
            'Resumen de 1-2 líneas por arco. El detalle completo vive en',
            'los prompts correspondientes o en los commits.',
            '',
            '- **Bug §4.7 cerrado (v83j-v83l).** El bug no era el leak',
        ],
        'reemplazar' => [
            '## 2. ARCOS CERRADOS RECIENTES',
            '',
            'Resumen de 1-2 líneas por arco. El detalle completo vive en',
            'los prompts correspondientes o en los commits.',
            '',
            '- **Fase 1 de seguridad: sesión PHP (v1.5piloto.84a).**',
            '  Login, logout local y cierre remoto funcionando con',
            '  `$_SESSION` sobre la cookie PHPSESSID. Nuevos helpers en',
            '  `Sesion.php`. `_construir_respuesta_login` guarda la',
            '  identidad completa (con `dueno` resuelto) en `$_SESSION`.',
            '  Sin cambios en `aplicacion.js`. Sin carga ni guardado',
            '  total nuevo. Ver §8.',
            '',
            '- **Migración de pasajeros entre micros: completa',
            '  (v1.5piloto.82e-82f + Tanda 3).** Backend, frontend,',
            '  tests del plugin y documentación. Probada en local,',
            '  pruebas y producción. Sale de §4.5.',
            '',
            '- **Bug §4.7 cerrado (v83j-v83l).** El bug no era el leak',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §4.5: migracion de pasajeros cerrada',
        'buscar' => [
            '**Arco en curso: migración de pasajeros entre micros.**',
            '**ESTADO: DESBLOQUEADA.** El bug §4.7 está cerrado',
            '(v1.5piloto.83l). El `id_fila` es estable entre',
            'requests, el grafo no se corrompe. Se puede retomar',
            'la Tanda 3.',
            '',
            'Plan completo en `prompts/plan_migrar_micro.md`.',
        ],
        'reemplazar' => [
            '**Arco de migración de pasajeros: COMPLETO.**',
            '',
            'Backend (v82e), frontend (v82f), tests del plugin y',
            'documentación (Tanda 3). Probado en local, pruebas y',
            'producción. El plan original en',
            '`prompts/plan_migrar_micro.md` queda como histórico.',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual §4.5: Tanda 3 cerrada',
        'buscar' => [
            '- **Tanda 3 (tests + docs): pendiente.** 7 tests del plugin',
            '  + actualización de `aplicacion_POST.php`,',
            '  `prompt_piloto.md` §5.8 y §4.9, y este archivo.',
            '',
            '**La migración YA PUEDE VOLVER A PRODUCCIÓN.** El bug',
            '§4.7 está cerrado (v1.5piloto.83l). Antes de pasarla,',
            'correr la Tanda 3 en local y validar el ciclo completo',
            'en un viaje de prueba.',
        ],
        'reemplazar' => [
            '- **Tanda 3 (tests + docs): cerrada.** 7 tests del plugin',
            '  + actualización de `aplicacion_POST.php` y',
            '  documentación. Validada en local, pruebas y producción.',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'plan_actual: insertar §8 seguridad y renumerar §9 historica',
        'buscar' => [
            '## 8. NOTA HISTÓRICA',
            '',
            'La nota de cierre de v1.5piloto.82d (arco "cancelaciones',
            'en compartidos + persistencia robusta en hostings',
            'compartidos") fue eliminada en v83s. Cumplió su función:',
            'los pendientes reales están consolidados en §4.1-4.4 y',
            '§4.8, y los aprendizajes en `prompt_seguridad.md` §8.10',
            'y en el historial de `prompt_piloto.md`.',
        ],
        'reemplazar' => [
            '## 8. SEGURIDAD DE SESIONES E IDENTIDAD',
            '',
            'Estado al cierre de v1.5piloto.84a. La Fase 1 está aplicada',
            'y funcionando. Las Fases 2, 3 y 4 son el plan pendiente.',
            '',
            '### 8.1. Estado antes de v84a',
            '',
            '- La "sesión" vivía en `localStorage.token_sesion` (cliente)',
            '  más un nodo `sesiones/<token>` en el grafo de credenciales.',
            '- En cada request, el frontend mandaba `nombre_solicitante`',
            '  con el nombre del usuario. El enrutador lo usaba para',
            '  permisos y carga parcial **sin validarlo server-side**.',
            '- Cualquiera podía hacer `POST accion=administrador/listar_usuarios`',
            '  con `nombre_solicitante=Administrador` y ver datos del admin.',
            '- Las URLs `?imprimir=1&tipo=...` no validaban sesión ni',
            '  permisos. Cualquiera con la URL veía datos de pasajeros',
            '  (DNI, teléfonos, domicilios), montos, cancelaciones, etc.',
            '- `session_start()` corría en `index.php` (cookie PHPSESSID',
            '  emitida), pero nadie escribía en `$_SESSION`. Estaba vacía.',
            '',
            '### 8.2. Fase 1 — sesión PHP (v1.5piloto.84a, aplicada)',
            '',
            'La cookie PHPSESSID ya existía, solo estaba vacía. La',
            'Fase 1 la usa como punto de verdad server-side:',
            '',
            '- **Login** (`_construir_respuesta_login`): resuelve el',
            '  dueño de terminal adentro (para que el array esté',
            '  completo antes de guardar) y llama a',
            '  `guardar_sesion_php($usuario)`.',
            '  `$_SESSION[prefijo . \'usuario\']` guarda',
            '  `{nombre_usuario, nivel, dueno, duenos, token_sesion}`.',
            '- **Rehidratación** (`sesiones/validar`): al recargar la',
            '  página, el JS lee el token del `localStorage` y manda',
            '  este POST. Si el token es válido, `guardar_sesion_php`',
            '  conecta la cookie con la sesión del grafo. Si no,',
            '  `limpiar_sesion_php()`.',
            '- **Logout local** (`sesiones/cerrar` con el propio token):',
            '  borra el nodo `sesiones/<token>` y limpia `$_SESSION`.',
            '- **Cierre remoto** (dueño cerrando sesión de un terminal,',
            '  admin cerrando un usuario): borra el nodo',
            '  `sesiones/<token>` del grafo. NO toca `$_SESSION` del',
            '  solicitante (porque `token_sesion_actual() != token_cerrado`).',
            '  El terminal se entera en el próximo page load.',
            '',
            '**Helpers nuevos** (`Aplicacion/Sesiones/Sesion.php`):',
            '- `guardar_sesion_php(array $usuario)`',
            '- `usuario_sesion_actual(): ?array`',
            '- `limpiar_sesion_php(): void`',
            '- `token_sesion_actual(): ?string`',
            '',
            '**Trade-off aceptado.** Si el dueño cierra la sesión de',
            'un terminal en medio de una sesión activa, el terminal',
            'no se entera hasta el próximo page load. Se puede',
            'endurecer en Fase 2/3.',
            '',
            '**No se tocó `aplicacion.js`.** La cookie PHPSESSID viaja',
            'sola en cada `fetch` a `index.php` y en cada `window.open`.',
            '',
            '**No se introdujo carga ni guardado total nuevo.**',
            '',
            '### 8.3. Fase 2 — impresión cerrada (pendiente)',
            '',
            'Objetivo: cerrar el agujero de `?imprimir=1&tipo=...`.',
            'Hoy cualquiera con la URL ve datos sin sesión.',
            '',
            'Plan:',
            '',
            '- **`index.php`, bloque `?imprimir=1`**:',
            '  - Validar `$_SESSION`. Sin sesión → 403 sin cargar grafo.',
            '  - Validar permisos por `tipo`. Sin permiso → 403 sin cargar.',
            '  - Exigir `dueno` en el GET para los `tipo` que hoy caen',
            '    a carga total (los 6-7 helpers de B3.3c). Sin `dueno`',
            '    → 400. Esto **elimina** la carga total en impresión.',
            '- **`_contextos_para_operacion` Caso 2**: usar los',
            '  contextos del usuario de la sesión (no del `dueno` del',
            '  GET). Validar que el `dueno` del GET sea accesible',
            '  desde ese contexto.',
            '- **`Impresion.php`**: sin cambios. Solo que ahora el',
            '  bloque `?imprimir=1` ya validó sesión y permisos antes',
            '  de llamarlo.',
            '',
            '**Sin carga total nueva, sin guardado nuevo.**',
            '',
            '### 8.4. Fase 3 — validación en POST (pendiente)',
            '',
            'Objetivo: cerrar el agujero de "el enrutador confía en',
            '`nombre_solicitante` del cliente".',
            '',
            '- **Enrutador**: derivar `nombre_solicitante` de',
            '  `$_SESSION`, no del POST. El POST puede seguir',
            '  mandándolo (compatibilidad), pero el backend lo ignora.',
            '- **Todos los endpoints POST** validan sesión y permisos',
            '  contra el `nombre_solicitante` de la sesión.',
            '- **Chequeo de cierre remoto en cada request** (opcional):',
            '  comparar el token de `$_SESSION` contra el grafo',
            '  (`sesiones/<token>`). Costo: una carga parcial de',
            '  `[\'sesiones\']` por request (~100 nodos). Cacheable',
            '  con un TTL corto.',
            '',
            '**Deuda asociada**: el login por código hoy es O(N) con',
            '`password_verify` por cada usuario. Solución: índice',
            'HMAC determinístico (`HMAC(secret, codigo) → us_<nombre>`).',
            '',
            '### 8.5. Fase 4 — tercer grafo de identidad (pendiente)',
            '',
            'Objetivo: sacar el esqueleto `usuarios` de la app y de',
            'credenciales. Hoy `usuarios → us_<nombre>` está duplicado',
            'en ambos grafos, y al admin/soporte la carga "parcial"',
            'le trae todo (desde `usuarios` se alcanza a cada',
            '`us_<nombre>` con su subgrafo).',
            '',
            '**Diseño propuesto:** tercer grafo `AdministradorDeViajes_identidad`.',
            '',
            '```',
            'Grafo identidad:',
            '  usuarios → <nombre> → perfil_<nombre>   (esqueleto liviano)',
            '  indices  → codigos_hmac → <hmac> → perfil_<nombre>',
            '',
            'Grafo app:',
            '  us_<nombre> (raíz propia) → publico, privado, ventas, ...',
            '',
            'Grafo credenciales:',
            '  us_<nombre> (raíz propia) → codigo_hash, contrasena, ...',
            '  sesiones → <token> → sesion → usuario',
            '```',
            '',
            '- `usuarios` sale del grafo de la app y del de credenciales.',
            '- `perfil_<nombre>` tiene `nivel`, `nombre_real`, `email`,',
            '  `dueno`/`duenos` (strings). Liviano (~5 nodos).',
            '- El admin/soporte carga `[\'usuarios\']` desde identidad',
            '  (solo perfiles) y, cuando opera sobre un usuario',
            '  específico, `[\'us_<nombre>\']` desde app o credenciales.',
            '- El índice HMAC (login por código O(1)) vive en identidad.',
            '- `en_grafo_identidad` helper nuevo (o generalizar',
            '  `en_grafo_credenciales`).',
            '',
            '**Esfuerzo**: 3-4 tandas. Bloqueado por Fases 2 y 3.',
            '',
            '**Deuda de diseño**: dejar el Nodo limpio antes de agregar',
            'más métodos de indexación (256 bits, HMAC, primos). Ver',
            '`prompt_framework_iteradores.md` §11.4.',
            '',
            '### 8.6. Lo que sigue funcionando (no romper)',
            '',
            '- **Cierre remoto**: dueño cierra sesión de terminal',
            '  (borra nodo en grafo). El terminal se entera en el',
            '  próximo page load.',
            '- **Rehidratación**: al recargar, el JS lee el token del',
            '  `localStorage` y lo manda a `sesiones/validar`, que',
            '  rehidrata `$_SESSION`.',
            '- **Inactividad**: la cookie PHPSESSID sin `expires` es',
            '  una session cookie (se borra al cerrar el navegador).',
            '  El token en `localStorage` persiste y permite',
            '  rehidratar al reabrir. Si se quiere forzar re-login por',
            '  inactividad, chequear',
            '  `$_SESSION[prefijo . \'ultima_actividad\']` contra',
            '  `time()`.',
            '',
            '### 8.7. Referencias',
            '',
            '- `prompt_seguridad.md` §8.11 (diseño de la sesión PHP).',
            '- `prompt_framework_iteradores.md` §11.4 (Nodo limpio).',
            '',
            '---',
            '',
            '## 9. NOTA HISTÓRICA',
            '',
            'La nota de cierre de v1.5piloto.82d (arco "cancelaciones',
            'en compartidos + persistencia robusta en hostings',
            'compartidos") fue eliminada en v83s. Cumplió su función:',
            'los pendientes reales están consolidados en §4.1-4.4 y',
            '§4.8, y los aprendizajes en `prompt_seguridad.md` §8.10',
            'y en el historial de `prompt_piloto.md`.',
        ],
    ],

    // ============================================================
    // prompt_piloto.md
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto §5.4: helpers de sesion PHP',
        'buscar' => [
            '### 5.4 `Aplicacion/Sesiones/Sesion.php`',
            '',
            '- `crear_sesion`, `listar_sesiones`, `cerrar_sesion`,',
            '  `listar_sesiones_de_usuarios`, `eliminar_sesiones_de_usuario`.',
        ],
        'reemplazar' => [
            '### 5.4 `Aplicacion/Sesiones/Sesion.php`',
            '',
            'Grafo de credenciales:',
            '- `crear_sesion`, `listar_sesiones`, `cerrar_sesion`,',
            '  `listar_sesiones_de_usuarios`, `eliminar_sesiones_de_usuario`.',
            '',
            'Sesión PHP (v1.5piloto.84a):',
            '- `guardar_sesion_php($usuario)`: escribe la identidad',
            '  en `$_SESSION[prefijo . \'usuario\']`.',
            '- `usuario_sesion_actual(): ?array`: lee el usuario de',
            '  `$_SESSION`.',
            '- `limpiar_sesion_php()`: borra la identidad de',
            '  `$_SESSION`.',
            '- `token_sesion_actual(): ?string`: devuelve el token',
            '  guardado, para distinguir logout local de cierre',
            '  remoto.',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'prompt_piloto §7: entrada v84a',
        'buscar' => [
            '## 7. HISTORIAL DE VERSIONES (resumen)',
            '',
            '- **v83q** (Grupo A: limpieza de deuda técnica del',
        ],
        'reemplazar' => [
            '## 7. HISTORIAL DE VERSIONES (resumen)',
            '',
            '- **v84a** (Fase 1 de seguridad: sesión PHP). Login,',
            '  logout y cierre remoto funcionando con `$_SESSION` +',
            '  cookie PHPSESSID. `_construir_respuesta_login` guarda',
            '  la identidad completa (con `dueno` resuelto) en',
            '  `$_SESSION`. `sesiones/validar` rehidrata `$_SESSION`',
            '  al recargar. `sesiones/cerrar` distingue logout local',
            '  (limpia `$_SESSION`) de cierre remoto (no la toca).',
            '  Nuevos helpers en `Sesion.php`. Sin cambios en',
            '  `aplicacion.js` (la cookie viaja sola). Sin carga ni',
            '  guardado total nuevo. Próxima fase: impresión cerrada.',
            '',
            '- **v83q** (Grupo A: limpieza de deuda técnica del',
        ],
    ],

    // ============================================================
    // prompt_seguridad.md
    // ============================================================
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_seguridad.md',
        'descripcion' => 'prompt_seguridad: agregar §8.11 sesion PHP',
        'buscar' => [
            'Ver también: `prompt_framework_iteradores.md` §6.7 y §11.6.',
            '',
            '---',
            '',
            '## 9. PRÓXIMOS PASOS: REPLICAR EL PATRÓN',
        ],
        'reemplazar' => [
            'Ver también: `prompt_framework_iteradores.md` §6.7 y §11.6.',
            '',
            '### 8.11 Sesión PHP del lado del servidor (v1.5piloto.84a)',
            '',
            '**Problema.** Antes de v84a, la "sesión" vivía en',
            '`localStorage.token_sesion` (cliente) más un nodo',
            '`sesiones/<token>` en el grafo de credenciales. El',
            '`nombre_solicitante` que mandaba el cliente era la',
            'identidad que el backend aceptaba, sin validación',
            'server-side. Cualquiera podía hacer POST como cualquier',
            'usuario. Las URLs `?imprimir=1&tipo=...` no validaban',
            'sesión ni permisos.',
            '',
            '**Solución.** La cookie PHPSESSID ya existía (por',
            '`session_start()` en `index.php`), solo estaba vacía.',
            'La Fase 1 la usa como punto de verdad server-side:',
            '',
            '- Login: `guardar_sesion_php($usuario)` guarda la',
            '  identidad en `$_SESSION[prefijo . \'usuario\']`.',
            '- Rehidratación: `sesiones/validar` rehidrata al recargar.',
            '- Logout local: `sesiones/cerrar` limpia `$_SESSION`',
            '  (cuando el token cerrado es el propio).',
            '- Cierre remoto: `sesiones/cerrar` NO toca `$_SESSION`',
            '  del solicitante (cuando el token cerrado es de otro).',
            '',
            '**Diseño del cierre remoto.** El token en el grafo sigue',
            'siendo la fuente de verdad para invalidar sesiones',
            'remotamente. Cuando el dueño cierra la sesión de un',
            'terminal:',
            '',
            '1. El backend borra el nodo `sesiones/<token_term>` del',
            '   grafo.',
            '2. NO toca `$_SESSION` del dueño (porque',
            '   `token_sesion_actual() != token_term`).',
            '3. El terminal se entera en el próximo page load:',
            '   `sesiones/validar` falla → `limpiar_sesion_php()` →',
            '   JS pide login.',
            '',
            '**Trade-off.** El terminal no se entera del cierre hasta',
            'que recargue la página. En la práctica, un terminal',
            'vendiendo no recarga, así que "sigue funcionando" hasta',
            'el próximo page load. Se puede endurecer en Fase 2/3.',
            '',
            '**Helpers nuevos** en `Sesion.php`: `guardar_sesion_php`,',
            '`usuario_sesion_actual`, `limpiar_sesion_php`,',
            '`token_sesion_actual`. Todos leen el prefijo de',
            '`Entorno::prefijo_sesion()`.',
            '',
            '**No se tocó `aplicacion.js`.** La cookie PHPSESSID',
            'viaja sola en cada `fetch` a `index.php` y en cada',
            '`window.open`.',
            '',
            '**No se introdujo carga ni guardado total nuevo.**',
            '`guardar_sesion_php` escribe en memoria. `sesiones/validar`',
            'sigue haciendo la misma carga total que antes.',
            '',
            'Ver `prompts/plan_actual.md` §8 para el plan completo',
            '(Fases 2, 3 y 4).',
            '',
            '---',
            '',
            '## 9. PRÓXIMOS PASOS: REPLICAR EL PATRÓN',
        ],
    ],
];

// ============================================================
// Runner v2 (textual de §14 del prompt_sistema_scripts_v2.md)
// ============================================================

echo "=== Aplicador ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) {
    $htmls_a_bumpear = ['aplicacion_GET.html'];
}
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $l) $total_reemplazos += count($l);
echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $n = contar_ocurrencias($contenido, $buscar_str);
        if ($n === 0) { $bloques_fallidos[] = "$archivo_rel: no encontrado - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($n > 1) { $bloques_fallidos[] = "$archivo_rel: ambiguo ($n) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = [$contenido, $eol];
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

$archivos_bump_php_js = [];
$archivos_bump_js_css = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $rel;
}
foreach ($creaciones as $cr) {
    $ext = strtolower(pathinfo($cr['archivo'], PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $cr['archivo'];
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $cr['archivo'];
}

$bumps_atversion = 0;
$bumps_qv = 0;
$bumps_detalle = [];
$info_bumps = [];

if ($bumpear) {
    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
        $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
        if (!file_exists($ruta_abs)) continue;
        if (isset($archivos_a_escribir[$ruta_abs])) {
            list($contenido, $eol) = $archivos_a_escribir[$ruta_abs];
        } else {
            $contenido_original = file_get_contents($ruta_abs);
            $eol = detectar_eol($contenido_original);
            $contenido = normalizar_a_unix($contenido_original);
        }
        $patron = '/(\*\s+@version\s+)(\S+)/';
        $viejo = '';
        $nuevo = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo) {
            $viejo = $m[2];
            return $m[1] . $version_nueva;
        }, $contenido, 1, $count);
        if ($count === 0) { $info_bumps[] = "$archivo_rel (sin @version)"; continue; }
        if ($viejo === $version_nueva) { continue; }
        $archivos_a_escribir[$ruta_abs] = [normalizar_a_unix($nuevo), $eol];
        $bumps_atversion++;
        $bumps_detalle[] = "$archivo_rel  @version $viejo → $version_nueva";
    }

    foreach ($htmls_a_bumpear as $html_rel) {
        $html_abs = $raiz_proyecto . '/' . $html_rel;
        if (!file_exists($html_abs)) { $info_bumps[] = "$html_rel (no encontrado)"; continue; }
        if (isset($archivos_a_escribir[$html_abs])) {
            list($html, $eol) = $archivos_a_escribir[$html_abs];
        } else {
            $html_orig = file_get_contents($html_abs);
            $eol = detectar_eol($html_orig);
            $html = normalizar_a_unix($html_orig);
        }
        $html_antes = $html;
        foreach (array_unique($archivos_bump_js_css) as $archivo_rel) {
            $base = basename($archivo_rel);
            $base_esc = preg_quote($base, '/');
            $patron = '/(' . $base_esc . '\?v=)([^"\'\s>]+)/';
            $viejo_qv = '';
            $html = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo_qv) {
                $viejo_qv = $m[2];
                return $m[1] . $version_nueva;
            }, $html, 1, $c_qv);
            if ($c_qv > 0 && $viejo_qv !== $version_nueva) {
                $bumps_qv++;
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv → $version_nueva";
            }
        }
        if ($html !== $html_antes) {
            $archivos_a_escribir[$html_abs] = [normalizar_a_unix($html), $eol];
        }
    }
}

$archivos_escritos = 0;
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    list($contenido_unix, $eol) = $info;
    $contenido_final = normalizar_a_original($contenido_unix, $eol);
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    $archivos_escritos++;
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
    if (!file_exists($ruta_abs)) { echo "[INFO] " . $elim['archivo'] . " no existía.\n"; continue; }
    if (unlink($ruta_abs)) echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    else echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
}

if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    foreach ($info_bumps as $i) echo "[INFO] $i\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($bumpear) {
    echo "Bumps @version:    $bumps_atversion\n";
    echo "Bumps ?v=:         $bumps_qv\n";
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";