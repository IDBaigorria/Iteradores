# Runner de `aplicar_cambios.php` — versión 2 (bumps automáticos)

Este archivo es autocontenido. Vive en
`prompts/prompt_runner_aplicar_cambios.md` y complementa a
`prompt_sistema_scripts.md`. Cuando haya que entregar un
`aplicar_cambios.php`, se copia el runner de acá.

A partir de **v1.5piloto.79n**, cada script declara la versión
nueva en la cabecera y el runner hace los bumps solo. **No hay
que escribir bloques de bump a mano.**

---

## 1. Cabecera del script

Todo `aplicar_cambios.php` arranca así:

```php
<?php
/**
 * Aplicador — <descripción corta>.
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.79n';   // opcional: si está, bumpea solo

// HTMLs donde buscar ?v= de los .js y .css afectados.
// Si no se declara, se usa aplicacion_GET.html por defecto.
$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [ ... ];

// ============================================================
// Runner
// ============================================================
// ... (copiar textual de acá abajo, sección 4)
```

**Reglas de la cabecera:**

- `$version_nueva` es **opcional**. Si está vacía o no se
  declara, el runner no bumpea nada. Útil para scripts de
  diagnóstico que no modifican archivos versionados.
- `$htmls_a_bumpear` es **opcional**. Por defecto
  `['aplicacion_GET.html']`. Si el proyecto tiene varios HTMLs
  (por ejemplo, `admin_GET.html`, `plugin_GET.html`), se listan
  todos.
- `$cambios` **no lleva bloques de bump**. El runner los hace.

---

## 2. Bumps automáticos

El runner bumpea automáticamente:

### Para cada archivo afectado (reemplazado o creado)

**Si termina en `.php` o `.js`:**
- Busca el primer `* @version ...` con regex
  `/(\*\s+@version\s+)(\S+)/` y reemplaza solo el número.
- No importa la versión vieja. No importa el espaciado.
- Si no lo encuentra: `[INFO] archivo (sin @version)`. No falla.
- Si hay más de uno: bumpea el primero y avisa.

**Si termina en `.js` o `.css`:**
- Busca en cada HTML configurado el patrón
  `nombre_base\?v=[^"'\s>]+` y reemplaza la versión.
- Ejemplo: `<script src="Aplicacion/Viajes/viajes-nucleo.js?v=1.5piloto.79k"></script>`
  → `?v=1.5piloto.79n`.
- Si no se encuentra el HTML: `[INFO] (HTML no encontrado)`. No falla.
- Si no se encuentra el `?v=` en el HTML: `[INFO] (sin ?v=)`. No falla.

### Reglas de matching

- Se usa el **basename** del archivo para buscar en el HTML, así
  funciona sin importar la ruta relativa ni el subdirectorio.
- El `@version` se busca en el contenido del archivo **después**
  de aplicarle los reemplazos. Así funciona si la tanda agrega
  el `@version` por primera vez (queda con la versión nueva).
- Los bumps de `?v=` corren **después** de los reemplazos, así
  funciona si la tanda también modifica el HTML directamente.

---

## 3. Reporte esperado

```
=== Aplicador — <descripción> ===

[INFO] 3 archivo(s) a reemplazar, 1 archivo(s) a crear.

[OK] Aplicacion/Viajes/Viaje.php
     └─ @version 1.5piloto.79m → 1.5piloto.79n
[OK] Aplicacion/Ventas/Venta.php
     └─ @version 1.5piloto.79b → 1.5piloto.79n
[OK] Aplicacion/Ventas/ventas.js
     └─ @version 1.5piloto.79a → 1.5piloto.79n
[OK] aplicacion_GET.html
     └─ ventas.js ?v=1.5piloto.79a → 1.5piloto.79n

=== Resumen ===
Bloques aplicados: 5
Archivos escritos: 4
Bumps @version:    3
Bumps ?v=:         1

Listo.
```

Los archivos sin `@version` (por ejemplo, un CSS) se muestran
con `[INFO]` al final. No rompen la tanda.

---

## 4. Runner (copiar textual)

```php
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

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear) || empty($htmls_a_bumpear)) {
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
// Recolectar los archivos afectados para bumpear: los reemplazados
// más los creados (los creados también se versionan si tienen
// @version en el contenido).
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
$info_bumps = [];

if ($bumpear) {
    // 2a. @version de .php y .js
    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
        $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
        if (!file_exists($ruta_abs)) continue;
        // v79o: si el archivo ya tiene reemplazos pendientes en
        // memoria, bumpear SOBRE ellos. Antes se releía del disco
        // y se pisaban los reemplazos (bug detectado aplicando
        // P55 v6x). Si no está en memoria, leer del disco.
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
        // v79o: mismo criterio que los .php/.js. Si el HTML ya
        // tiene reemplazos pendientes en memoria, bumpear sobre
        // ellos. Si no, leer del disco.
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
            $patron = '/(' . $base_esc . '\?v=)([^"'\s>]+)/';
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
    if (!file_exists($ruta_abs)) {
        echo "[INFO] " . $elim['archivo'] . " no existía.\n";
        continue;
    }
    if (unlink($ruta_abs)) {
        echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    } else {
        echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
    }
}

// --- Reporte de bumps ---
if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    if (!empty($bumps_detalle)) {
        foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    }
    if (!empty($info_bumps)) {
        foreach ($info_bumps as $i) echo "[INFO] $i\n";
    }
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
```

---

## 5. Ejemplo mínimo completo

```php
<?php
/**
 * Aplicador — Fix del contador de viajes.
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.80a';

$cambios = [
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'No contar dos veces el mismo micro',
        'buscar' => [ ... ],
        'reemplazar' => [ ... ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/viajes-nucleo.js',
        'descripcion' => 'Refrescar el contador al cerrar el modal',
        'buscar' => [ ... ],
        'reemplazar' => [ ... ],
    ],
];

// Runner v2 (copiar textual)
```

Con eso alcanza. El runner:

- Aplica los 2 reemplazos.
- Bumpea `@version` en `Viaje.php` y `viajes-nucleo.js` a
  `1.5piloto.80a`.
- Bumpea `viajes-nucleo.js?v=...` en `aplicacion_GET.html`.

Sin bloques de bump manuales.

---

## 6. Casos raros

**Archivo sin `@version` (por ejemplo, un CSS):**
El runner lo saltea con `[INFO] archivo.css (sin @version)`. No
falla.

**`?v=` con query string complejo:**
El regex `[^"'\s>]+` captura todo lo que no sea comilla,
apóstrofe, espacio o `>`. Si el query es
`?v=1.5piloto.79n&foo=bar`, el regex solo reemplaza hasta el
`&`. Es una limitación conocida: si el HTML usa queries con `&`
después del `?v=`, avisar antes de entregar el script.

**Varios `@version` en el mismo archivo (por ejemplo, dos
clases en un mismo .php):**
El runner bumpea el primero y avisa con `[BUMP]` normal. Si en
algún archivo hay que bumpear el segundo, se usa un bloque
manual:

```php
[
    'tipo' => 'reemplazar',
    'archivo' => 'ruta/al/archivo.php',
    'descripcion' => 'Bump del segundo @version',
    'buscar' => [' * @version 1.5piloto.79m'],
    'reemplazar' => [' * @version 1.5piloto.80a'],
]
```

Es raro. Se justifica caso por caso.

**Forzar un bump aunque `$version_nueva` no esté seteado:**
Se usa un bloque manual con la versión específica. Es el caso
del script de diagnóstico que no versiona nada pero necesita
bumpear un solo archivo.

**Tanda sin `$version_nueva`:**
El runner no bumpea nada. El script se ejecuta igual. Útil para
migraciones, pruebas, diagnósticos.

---

## 7. Cómo se ve un `[INFO]` de archivo sin `@version`

En la sección "Bumps" del reporte final:

```
--- Bumps ---
[BUMP] Aplicacion/Viajes/Viaje.php  @version 1.5piloto.79m → 1.5piloto.79n
[BUMP] aplicacion_GET.html  viajes-nucleo.js ?v= 1.5piloto.79k → 1.5piloto.79n
[INFO] estilos-botones-admin.css (sin @version)
```

Es informativo. No requiere acción.

---

## FIN DEL RUNNER
