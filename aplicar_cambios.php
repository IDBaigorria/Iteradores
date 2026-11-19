<?php
/**
 * Aplicador — A.0: plan de Fase A (refactor de usuarios + grafo identidad).
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.84i';
$htmls_a_bumpear = [];

$cambios = [

    // ────────────────────────────────────────────────────────────
    // B1: §5.5 — tabla de Fase A refinada
    // ────────────────────────────────────────────────────────────
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§5.5 tabla Fase A refinada (A.1b, columna Destructiva, objetivo de fondo)',
        'buscar' => [
            '**Fase A — Refactor de usuarios + identidad (ver §8.5):**',
            '',
            '| Tanda | Qué hace | Estado |',
            '|---|---|---|',
            '| **A.1** | Crear grafo `identidad` + `perfil_*` (con `app`) + migración. | Pendiente |',
            '| **A.2** | HMAC + login O(1) con fallback O(N). | Pendiente |',
            '| **A.3** | Carga parcial por grafo + ruteo por `app`. | Pendiente |',
            '| **A.4** | Mover `Usuarios/`, `Sesiones/`, `Autenticacion/`, `GrafoCredenciales.php` al framework. | Pendiente |',
            '| **A.5** | Limpieza: remover `usuarios` de app y credenciales (destructiva). | Pendiente |',
        ],
        'reemplazar' => [
            '**Fase A — Refactor de usuarios + identidad (ver §8.5):**',
            '',
            '**Objetivo de fondo:** sacar el nodo raíz `usuarios` de los',
            'grafos app y credenciales. Hoy `us_Administrador → usuarios`',
            'hace que la "carga parcial" del admin arrastre los subgrafos',
            'de todos los usuarios (carga total encubierta). El grafo',
            '`identidad` reemplaza esa función con un registro liviano:',
            'solo `app`, `nivel`, `nombre_real`, `email`, `dueno` y',
            '`duenos` por usuario. Los selects del admin y el ruteo por',
            'app se sirven desde identidad; los datos de negocio se',
            'cargan por subgrafo específico.',
            '',
            '**Estrategia de ruteo (B):** el `app` de cada usuario se',
            'cachea en `$_SESSION` al login. En cada request se lee de',
            'ahí, sin cargar identidad. Identidad se carga solo cuando',
            'el admin abre un select o cuando hace falta listar usuarios.',
            'Cero carga de identidad en el 99% de los requests.',
            '',
            '**Naming:** `us_<nombre>` en los 3 grafos (app,',
            'credenciales, identidad). Consistencia de nomenclatura;',
            'el código que navega `usuarios → <nombre> → us_<nombre>`',
            'funciona igual en los 3.',
            '',
            '| Tanda | Qué hace | Estado |',
            '|---|---|---|',
            '| **A.1** | Crear grafo `identidad` + nodo raíz `usuarios` + `us_<nombre>` por usuario (con `app`, `nivel`, `nombre_real`, `email`, `dueno`, `duenos`). Comando `app:migrar_a_identidad`. No toca app ni credenciales. | **En curso** |',
            '| **A.1b** | Migrar los listados que solo necesitan identidad (`listar_usuarios` para selects, `listar_duenos`, `listar_soportes`, `listar_duenos_de_soporte`, `listar_nombres_usuarios_para_soporte`) a leer del grafo identidad. Los listados con datos de negocio (efectivo, banco, pasajes, codigo_asignado) siguen leyendo del app. | Pendiente |',
            '| **A.2** | HMAC + login O(1) con fallback O(N). | Pendiente |',
            '| **A.3** | Ruteo por `app` en `index.php` y Enrutador + caché de `app` en `$_SESSION`. Carga parcial por grafo. | Pendiente |',
            '| **A.4** | Mover `Usuarios/`, `Sesiones/`, `Autenticacion/`, `GrafoCredenciales.php` al framework. | Pendiente |',
            '| **A.5** | Limpieza: remover `usuarios` de app y credenciales. **Destructiva. Backup previo obligatorio.** | Pendiente |',
        ],
    ],

    // ────────────────────────────────────────────────────────────
    // B2: §8.5 — Estado + Motivación + Diseño + Estructura del perfil
    // ────────────────────────────────────────────────────────────
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§8.5 Estado + Objetivo + Diseño + Estructura del perfil',
        'buscar' => [
            '**Estado.** Plan aprobado, no implementado. La app sigue',
            'funcionando con el modelo actual. La migración es no',
            'destructiva por fases, así que se puede correr con la',
            'app andando. Se retoma cuando haya una ventana de',
            'trabajo dedicada.',
            '',
            '**Motivación.** Tres objetivos:',
            '',
            '1. **Escalar.** El admin/soporte no debe cargar los',
            '   subgrafos de todos los usuarios. Hoy su "carga',
            '   parcial" es total en la práctica (desde `usuarios`',
            '   se alcanza a cada `us_<nombre>` con todo su',
            '   subgrafo).',
            '2. **Multi-app.** Los usuarios deben poder acceder a',
            '   varias apps (viajes, juego, tienda) con el mismo',
            '   login. El campo `app` indica la app activa.',
            '3. **Reutilización.** El sistema de usuarios sale de',
            '   `Aplicacion/` y se mueve al framework, disponible',
            '   para todas las apps.',
            '',
            '**Diseño:** tercer grafo `AdministradorDeViajes_identidad`',
            'como "enganche" entre credenciales y los grafos de',
            'aplicación. El punto de unión es el `nombre_usuario`',
            '(string), no un enlace de grafo.',
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
            '**Estructura de `perfil_<nombre>`:**',
            '',
            '```',
            'perfil_<nombre_usuario>',
            '  ├─ nivel           "admin" | "dueno" | "terminal" | "soporte"',
            '  ├─ nombre_real     string',
            '  ├─ email           string (opcional)',
            '  ├─ dueno           (solo terminal: nombre del dueño)',
            '  ├─ duenos          contenedor (solo soporte: un hijo por dueño)',
            '  └─ app             "viajes" | "juego" | "tienda" | "global"',
            '```',
        ],
        'reemplazar' => [
            '**Estado.** A.1 en curso. La app sigue funcionando con el',
            'modelo actual. La migración es no destructiva por fases,',
            'así que se puede correr con la app andando.',
            '',
            '**Objetivo de fondo.** Sacar el nodo raíz `usuarios` de',
            'los grafos app y credenciales. Hoy `us_Administrador →',
            'usuarios` hace que la "carga parcial" del admin arrastre',
            'los subgrafos de todos los usuarios (carga total',
            'encubierta). Con el grafo `identidad`, el admin carga',
            'solo perfiles livianos para los selects, y los datos de',
            'negocio se cargan por subgrafo específico cuando hacen',
            'falta.',
            '',
            '**Tres objetivos:**',
            '',
            '1. **Escalar.** Eliminar la carga total encubierta del',
            '   admin. Ningún request debe arrastrar subgrafos',
            '   ajenos.',
            '2. **Multi-app.** Los usuarios acceden a varias apps',
            '   (viajes, juego, tienda) con el mismo login. El',
            '   campo `app` en identidad indica la app activa.',
            '3. **Reutilización.** El sistema de usuarios sale de',
            '   `Aplicacion/` y se mueve al framework, disponible',
            '   para todas las apps.',
            '',
            '**Diseño:** tercer grafo `AdministradorDeViajes_identidad`',
            'como registro liviano de usuarios. El punto de unión',
            'con los otros grafos es el `nombre_usuario` (string),',
            'no un enlace de grafo.',
            '',
            '```',
            'Grafo identidad:',
            '  usuarios → <nombre> → us_<nombre>   (perfil liviano)',
            '  indices  → codigos_hmac → <hmac> → us_<nombre>',
            '',
            'Grafo app:',
            '  us_<nombre> (raíz propia) → publico, privado, ventas, ...',
            '  (el nodo raíz `usuarios` se elimina en A.5)',
            '',
            'Grafo credenciales:',
            '  us_<nombre> (raíz propia) → codigo_hash, contrasena, ...',
            '  sesiones → <token> → sesion → usuario',
            '  (el nodo raíz `usuarios` se elimina en A.5)',
            '```',
            '',
            '**Estructura del perfil `us_<nombre>` en identidad:**',
            '',
            '```',
            'us_<nombre_usuario>',
            '  ├─ nivel           "admin" | "dueno" | "terminal" | "soporte"',
            '  ├─ nombre_real     string',
            '  ├─ email           string (opcional)',
            '  ├─ dueno           (solo terminal: nombre del dueño)',
            '  ├─ duenos          contenedor (solo soporte: un hijo por dueño)',
            '  └─ app             "viajes" | "juego" | "tienda" | "global"',
            '```',
            '',
            '**Naming:** `us_<nombre>` en los 3 grafos (app,',
            'credenciales, identidad). Consistencia de nomenclatura;',
            'el código que navega `usuarios → <nombre> → us_<nombre>`',
            'funciona igual en los 3.',
        ],
    ],

    // ────────────────────────────────────────────────────────────
    // B3: §8.5 — Decisión sobre app + Carga parcial + Ruteo por app
    // ────────────────────────────────────────────────────────────
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§8.5 Estrategia B + carga parcial por grafo + ruteo por app',
        'buscar' => [
            '**Decisión sobre `app`:** va en **identidad**, no en',
            'credenciales. Credenciales se carga on-demand (login,',
            'validar token); identidad se carga al arranque de cada',
            'request. El `app` es dato de perfil (ruteo), no de',
            'autenticación. Default en migración: `"viajes"`. Admin',
            'global: `"global"` (ve todas las apps, elige activa',
            'desde un selector en el header).',
            '',
            '**Carga parcial por grafo.** `_contextos_para_operacion`',
            'devuelve un mapa por grafo, no un solo valor:',
            '',
            '- **Admin:** `identidad=[\'usuarios\']` (perfiles',
            '  livianos) + `app=[\'us_Administrador\']` (solo el admin).',
            '- **Soporte:** `identidad=[\'usuarios\']` +',
            '  `app=[\'us_<soporte>\']`.',
            '- **Dueño:** `identidad=null` (no necesita la lista) +',
            '  `app=[\'us_<dueno>\']`.',
            '- **Terminal:** `identidad=null` +',
            '  `app=[\'us_<terminal>\']`.',
            '',
            '**Ruteo por app.** `index.php` lee',
            '`$_SESSION.usuario.app`, resuelve el nombre del grafo',
            'con un mapa (`app → ConfiguracionApli::NOMBRE_APP_*`) y',
            'carga ese grafo. El Enrutador hace lo mismo en cada',
            'POST. Exactamente el mismo patrón que',
            '`nombre_solicitante` desde v84e.',
        ],
        'reemplazar' => [
            '**Estrategia de ruteo (B): `app` en `$_SESSION`.**',
            '',
            '- Al login, `_construir_respuesta_login` guarda `app` en',
            '  `$_SESSION` junto con `nivel`, `dueno`, `duenos` y',
            '  `token_sesion` (extiende lo que ya hace v84a).',
            '- En cada request, `index.php` lee `$_SESSION[\'app\']` y',
            '  carga el grafo correspondiente. No carga identidad.',
            '- Identidad se carga **solo** cuando el admin abre un',
            '  select o cuando hace falta listar usuarios.',
            '- Trade-off: `app` en sesión puede quedar stale hasta',
            '  el próximo refresco. Mismo trade-off que el token',
            '  (TTL 30s). Aceptable: un cambio de `app` es raro y',
            '  no pierde datos.',
            '- Se implementa en **A.3** (junto con el ruteo). A.1',
            '  solo crea el grafo; A.2 solo el HMAC.',
            '',
            '**Carga parcial por grafo (A.3).** `_contextos_para_operacion`',
            'devuelve un mapa por grafo, no un solo valor:',
            '',
            '- **Admin:** `identidad=[\'usuarios\']` (perfiles livianos)',
            '  + `app=[<subgrafo del objetivo>]` (solo lo que necesita).',
            '- **Soporte:** `identidad=[\'usuarios\']` (perfiles) +',
            '  `app=[\'us_<soporte>\']`.',
            '- **Dueño:** `identidad=null` (no necesita la lista) +',
            '  `app=[\'us_<dueno>\']`.',
            '- **Terminal:** `identidad=null` +',
            '  `app=[\'us_<terminal>\']`.',
            '',
            'Con `usuarios` eliminado de app y credenciales (A.5),',
            'ningún request arrastra subgrafos ajenos.',
            '',
            '**Ruteo por app (A.3).** `index.php` lee',
            '`$_SESSION[\'app\']`, resuelve el nombre del grafo',
            'con un mapa (`app → ConfiguracionApli::NOMBRE_APP_*`) y',
            'carga ese grafo. El Enrutador hace lo mismo en cada',
            'POST. Exactamente el mismo patrón que',
            '`nombre_solicitante` desde v84e.',
        ],
    ],

    // ────────────────────────────────────────────────────────────
    // B4: §8.5 — Login O(1) con HMAC (solo `perfil_<nombre>` → `us_<nombre>`)
    // ────────────────────────────────────────────────────────────
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§8.5 Login O(1): perfil_<nombre> → us_<nombre>',
        'buscar' => [
            '**Login O(1) con HMAC.**',
            '',
            '- Al crear/editar código: `h = HMAC(secret, codigo)`,',
            '  se guarda `indices/codigos_hmac/<h> → perfil_<nombre>`.',
        ],
        'reemplazar' => [
            '**Login O(1) con HMAC.**',
            '',
            '- Al crear/editar código: `h = HMAC(secret, codigo)`,',
            '  se guarda `indices/codigos_hmac/<h> → us_<nombre>`.',
        ],
    ],

    // ────────────────────────────────────────────────────────────
    // B5: §8.5 — Tandas + Riesgo + Estimación
    // ────────────────────────────────────────────────────────────
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§8.5 Tandas de Fase A (A.1b, columna Destructiva)',
        'buscar' => [
            '**Tandas (Fase A):**',
            '',
            '| Tanda | Qué hace |',
            '|---|---|',
            '| **A.1** | Crear grafo `identidad` + `perfil_*` (con `app`) + migración. Sin tocar login ni carga parcial. |',
            '| **A.2** | HMAC + login O(1) con fallback O(N). Script de migración del índice. |',
            '| **A.3** | Carga parcial por grafo + ruteo por `app` en `index.php` y Enrutador. |',
            '| **A.4** | Mover `Aplicacion/Usuarios/`, `Sesiones/`, `Autenticacion/`, `GrafoCredenciales.php` al framework (namespace + referencias). |',
            '| **A.5** | Limpieza: remover `usuarios` de app y credenciales. Última tanda, destructiva. Backup previo obligatorio. |',
            '',
            '**Riesgo de A.5.** Es la más delicada (elimina datos).',
            'Si falla, A.1-A.4 siguen funcionando (convivencia con',
            'el modelo viejo). Backup previo obligatorio.',
            '',
            '**Estimación:** 3-4 sesiones de trabajo. A.1 y A.2 son',
            'las más pesadas (migración + HMAC + script).',
        ],
        'reemplazar' => [
            '**Tandas (Fase A):**',
            '',
            '| Tanda | Qué hace | Toca | Destructiva |',
            '|---|---|---|---|',
            '| **A.1** | Crea grafo `identidad` + `us_<nombre>` por usuario + nodo `indices` vacío. Comando `app:migrar_a_identidad`. | `ConfiguracionApli.php`, `index.php`, `Migraciones/` | No |',
            '| **A.1b** | Migra los listados que solo necesitan identidad a leer del grafo identidad. Los listados con datos de negocio siguen leyendo del app. | `Usuario.php`, `index.php` | No |',
            '| **A.2** | HMAC + login O(1) con fallback O(N). Script de migración del índice. | `Autenticacion.php`, `Migraciones/` | No |',
            '| **A.3** | Ruteo por `app` en `index.php` y Enrutador + caché de `app` en `$_SESSION`. Carga parcial por grafo. | `index.php`, `Enrutador.php`, `Sesion.php` | No |',
            '| **A.4** | Mover `Aplicacion/Usuarios/`, `Sesiones/`, `Autenticacion/`, `GrafoCredenciales.php` al framework (namespace + referencias). | muchos | No |',
            '| **A.5** | Limpieza: remover `usuarios` de app y credenciales. **Backup previo obligatorio.** Si falla, A.1-A.4 siguen funcionando (convivencia con el modelo viejo). | muchos | **Sí** |',
            '',
            '**Deuda de diseño (heredada):** dejar el Nodo limpio antes',
            'de agregar más métodos de indexación (256 bits, HMAC,',
            'primos). Ver `prompt_framework_iteradores.md` §11.4.',
        ],
    ],

    // ────────────────────────────────────────────────────────────
    // B6: §1 — agregar párrafo "A.1 en curso"
    // ────────────────────────────────────────────────────────────
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => '§1 agregar estado A.1 en curso',
        'buscar' => [
            '`viajes-micros.js`). **Plan de Fase A (refactor de',
            'usuarios en framework) y Fase B (juego en 2 modos)',
            'agregado:** ver §8.5 y §9. Ver §2 y §8).',
        ],
        'reemplazar' => [
            '`viajes-micros.js`). **Plan de Fase A (refactor de',
            'usuarios en framework) y Fase B (juego en 2 modos)',
            'agregado:** ver §8.5 y §9. Ver §2 y §8).',
            '',
            '**Nuevo (A.1 en curso):** plan de Fase A (refactor de',
            'usuarios + grafo `identidad`) refinado. Objetivo de fondo:',
            'sacar el nodo raíz `usuarios` de app y credenciales para',
            'eliminar la carga total encubierta del admin. Estrategia B',
            '(`app` en `$_SESSION`, identidad on-demand). Naming',
            '`us_<nombre>` en los 3 grafos. Migración como comando',
            '`app:migrar_a_identidad`. Ver §8.5.',
        ],
    ],

];

// ============================================================
// Runner v2
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

// --- Fase 1: aplicar reemplazos ---
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

// --- Fase 2: bumps automáticos ---
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
    // 2a. @version de .php y .js
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

    // 2b. ?v= en los HTMLs
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

// --- Fase 3: escribir ---
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

// --- Reporte de bumps ---
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