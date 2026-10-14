<?php
/**
 * Aplicador — Documentar plan de mudanza de botones al plugin.
 *
 * Agrega una sección nueva al final de plan_actual.md con el
 * plan completo: mudar los botones de limpieza de la app piloto
 * al plugin de pruebas. Tres fases. Decisiones pendientes.
 *
 * Uso (raíz del proyecto PHP): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '';
$htmls_a_bumpear = [];

$seccion_nueva = <<<'PLAN'

---

## 3. PLAN: MUDANZA DE BOTONES DE LIMPIEZA AL PLUGIN

Anotado al cierre de v1.5piloto.79o. No implementado todavía.
Objetivo: mover los botones de limpieza de datos de prueba de
la app piloto al plugin de pruebas. La app piloto solo mantiene
los endpoints backend.

### 3.1 Motivo

Los botones de limpieza son herramientas de testing, no de
producción. Hoy viven en la UI del piloto (pestaña Viajes,
pestaña Clientes, pestaña Grafo), visibles solo para admin en
modo pruebas. Pero el usuario que corre pruebas ya está en el
plugin: es natural que los botones de limpieza estén al lado
de las pruebas, en la misma ventana.

**Ventajas:**

1. Un solo lugar para correr pruebas y limpiar.
2. La app queda sin código de testing. Los endpoints son APIs
   limpias, reusables a futuro.
3. Cuando agreguemos un tipo nuevo de prueba, el botón va al
   plugin; la app no se toca.
4. `body.rol-admin` + `body.modo-pruebas` + el CSS defensivo
   de v79j quedan obsoletos. Se eliminan.

**Trade-off aceptado:** si alguien quiere limpiar sin el
plugin, tiene que llamar al endpoint a mano. Es aceptable —
son herramientas de admin + modo pruebas.

### 3.2 Qué se elimina del piloto (Fase 3)

- `estilos-botones-admin.css` (creado en v79j).
- Link al CSS en `aplicacion_GET.html`.
- `_actualizar_visibilidad_boton_limpiar_viajes` y el
  listener del botón en `Aplicacion/Viajes/viajes-nucleo.js`.
- `_aplicar_clases_de_rol` y sus llamadas en `aplicacion.js`
  (ya no hay CSS que dependa de las clases `rol-admin` y
  `modo-pruebas`).
- Botón "Limpiar viajes de prueba" del HTML.
- Botón "Limpiar pasajeros de prueba" del HTML.
- Lógica JS de esos botones.

**No se elimina:**

- Los endpoints backend (`viajes/limpiar_prueba`,
  `pasajeros/limpiar_prueba`, `grafo/eliminar_huerfanos`,
  y los que se agreguen en Fase 1).
- El chequeo `Entorno::es_pruebas()` en el backend.
- El chequeo de nivel admin en el backend.

### 3.3 Endpoints backend del piloto (Fase 1)

**Ya existen:**

- `viajes/limpiar_prueba` — `limpiar_viajes_de_prueba`. Amplía
  prefijos según v79j-v79m. Limpia viajes de prueba, con regla
  de ventas de prueba (v79l: solo `@test.local` marca prueba).
- `pasajeros/limpiar_prueba` — `limpiar_pasajeros_de_prueba`.
  Email `@test.local`.
- `grafo/eliminar_huerfanos` — comando
  `grafo:eliminar_huerfanos`.

**A agregar en Fase 1:**

1. `ventas/limpiar_prueba` — nuevo helper
   `limpiar_ventas_cancelaciones_de_prueba($nombre_dueno)`.
   Limpia:
   - Ventas cuyo comprador tenga email `@test.local`.
   - Nodos Cancelación cuyo motivo contenga "prueba" o "test"
     (case-insensitive), o cuyo `comprador_dni` empiece con
     prefijos conocidos (`cancelar_`, `auto_`, `regres_`,
     `dj_`, `foto`).
2. `empresas/limpiar_prueba` — nuevo helper
   `limpiar_empresas_vehiculos_de_prueba($nombre_dueno)`.
   Limpia empresas y vehículos con prefijos conocidos
   (`fotoauto`, `viajecambiomicro_emp_`, `viajecambiomicro_veh_`,
   y los que se agreguen).
3. `administrador/limpiar_usuarios_prueba` — nuevo helper
   `limpiar_usuarios_de_prueba()`. Criterio a consensuar:
   - `nombre_usuario` contiene `prueba`, `test` o `temp`
     (case-insensitive).
   - O `nombre_real` contiene `Prueba` o `Test`.
   - O `email` termina en `@test.local`.
4. `dueno/limpiar_terminales_prueba` — nuevo helper
   `limpiar_terminales_de_prueba($nombre_dueno)`. Prefijo
   `termprueba`.

**Todos:** chequeo de nivel admin + `Entorno::es_pruebas()`.

**Formato de respuesta:** `{exito, borrados: [...], cantidad_borrados: N, ...}`. Mismo patrón que `limpiar_viajes_de_prueba`.

### 3.4 Reorganización del plugin en pestañas (Fase 2)

Cambio de estructura de `catalogo.js`: pasa de `SECCIONES` a
`PESTAÑAS` (con secciones adentro). Cada pestaña tiene su botón
"Correr todas" y su botón de limpieza.

**Mapeo propuesto:**
Plugin
├── Base [ ] Limpiar todo lo de prueba
├── Administrador [ ] Limpiar usuarios de prueba
│ [ ] Limpiar terminales de prueba
├── Empresas/Micros [ ] Limpiar empresas y vehículos de prueba
├── Viajes [ ] Limpiar viajes de prueba
├── Vendidos [ ] Limpiar ventas y cancelaciones de prueba
├── Clientes [ ] Limpiar pasajeros de prueba
└── Grafo [ ] Eliminar nodos basura

text

**Bonus:** botón "Limpiar todo lo de prueba" en Base que llama
a todos los endpoints en orden. Útil al empezar una sesión con
el grafo sucio.

**Cambios técnicos:**

- `catalogo.js`: array `PESTAÑAS` con `{id, nombre, secciones, boton_limpieza}`.
- `ventana.html`: div para las pestañas + un contenedor de
  secciones que cambia según la pestaña activa.
- `ventana.js`: renderizar pestañas + secciones + botones.
- Agregar un helper `ctx.limpiar(categoria)` que llame al
  endpoint correspondiente y muestre el resultado.

**Las pruebas no cambian.** Solo cambia cómo se agrupan en el
catálogo y cómo se renderizan en la ventana.

**Fase previa a Fase 2:** confirmar el esquema de pestañas
(¿agregar/quitar alguna?) y la ubicación exacta de cada
prueba en el nuevo esquema.

### 3.5 Fase 3: limpieza de la app

Una vez que los botones del plugin funcionan, eliminar del
piloto todo lo que sobra. Ver §3.2 para el listado.

### 3.6 Decisiones pendientes

Antes de arrancar Fase 1:

1. **Criterio de "usuario de prueba".** ¿Solo nombre_usuario
   con `prueba`/`test`/`temp`? ¿O también nombre_real con
   "Prueba"? ¿O email `@test.local`? Propuesta: los tres
   criterios en OR.

2. **Un solo endpoint para ventas + cancelaciones o dos
   separados.** Propuesta: uno solo
   (`ventas/limpiar_prueba`). Las cancelaciones son
   consecuencia de las ventas de prueba — se limpian juntas.

3. **Prefijos de terminales de prueba.** ¿Solo `termprueba`?
   ¿Algún otro?

4. **¿"Limpiar todo lo de prueba" en Base llama a todos los
   endpoints, o es un endpoint nuevo del piloto que hace todo
   de una?** Propuesta: el plugin llama a los endpoints
   individuales en secuencia. No hay endpoint "todo" en el
   backend, es una composición del lado del plugin.

5. **¿El botón "Limpiar todo" es uno solo, o hay uno por
   pestaña y un botón global extra?** Propuesta: uno global en
   Base + uno por pestaña.

### 3.7 Orden de implementación

1. **Fase 1**: agregar los 4 endpoints nuevos al backend del
   piloto. Tanda mediana (4 helpers + 4 subacciones).
2. **Fase 1.5**: probar cada endpoint desde el navegador a
   mano (llamada POST) para verificar que funciona.
3. **Fase 2**: refactor del plugin. Cambio de `SECCIONES` a
   `PESTAÑAS` + botones de limpieza + helper `ctx.limpiar`.
4. **Fase 3**: eliminar del piloto todo lo que sobra (§3.2).

Cada fase es una tanda. La Fase 1 lleva su script en el
proyecto PHP, la Fase 2 lleva su script en `iteradoresJS/`,
la Fase 3 lleva su script en el proyecto PHP.

PLAN;

$cambios = [
    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/plan_actual.md',
        'descripcion' => 'Agregar §3 con el plan de mudanza',
        'buscar' => [
            '---',
            '',
            '**FIN DEL PLAN ACTUAL**',
        ],
        'reemplazar' => array_merge(
            explode("\n", $seccion_nueva),
            [
                '',
                '---',
                '',
                '**FIN DEL PLAN ACTUAL**',
            ]
        ),
    ],
];

// ============================================================
// Runner v2
// ============================================================

echo "=== Aplicador — Documentar plan de mudanza de botones ===\n\n";

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

$reemplazos_por_archivo = [];
foreach ($cambios as $cambio) {
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar.\n\n";

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

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";