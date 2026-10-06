<?php
/**
 * Aplicador de cambios automáticos — Proyecto PHP (framework + piloto).
 *
 * Tanda V1.5i.7h (framework) / V1.5piloto.76e (piloto):
 *   - Separar la configuración del framework (`Conf`) de la
 *     configuración del piloto (`ConfiguracionApli`).
 *   - Mover 9 constantes propias del piloto a un archivo nuevo
 *     `Aplicacion/ConfiguracionApli.php`.
 *   - Desacoplar `Entorno` del `PREFIJO_SESSION` del piloto via
 *     un setter público.
 *   - Agregar métodos conmutados de auth (`_PRUEBAS`) en
 *     `ConfiguracionApli` para el bloqueo conmutable.
 *   - Refactor masivo: `Conf::X` -> `ConfiguracionApli::X` en
 *     12 archivos del piloto.
 *
 * Extensión del runner: bloque `reemplazar` acepta flag
 * `'todos' => true` para str_replace sin validar unicidad.
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
    // FRAMEWORK — Configuracion/Configuracion.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Configuracion/Configuracion.php',
        'descripcion' => 'Configuracion.php: bump a 1.5i.7h',
        'buscar' => [' * @version 1.5piloto.72'],
        'reemplazar' => [' * @version 1.5i.7h'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Configuracion/Configuracion.php',
        'descripcion' => 'Configuracion.php: quitar constantes del piloto',
        'buscar' => [
            '    // Sobre la aplicación',
            '    public const NOMBRE_APP = "AdministradorDeViajes";',
            '    public const VERSION_APP = "0.0.0";',
            '    public const AUTOR_APP = "Ignacio David Baigorria";',
            '',
            '    /**',
            '     * Nombre del grafo de credenciales.',
            '     *',
            '     * Es un grafo separado del principal que contiene únicamente:',
            '     * - los nodos usuarios con codigo_hash y contrasena',
            '     * - los nodos de sesiones activas',
            '     *',
            '     * El nombre de usuario (clave del enlace en `usuarios`) es el punto de',
            '     * unión entre ambos grafos.',
            '     *',
            '     * @var string',
            '     * @since 1.5piloto.69',
            '     */',
            '    public const NOMBRE_APP_CREDENCIALES = self::NOMBRE_APP . "_credenciales";',
            '',
            '    // --- Rate limiting de autenticación (v1.5piloto.71) ---',
            '',
            '    /**',
            '     * Cantidad de intentos fallidos consecutivos antes de bloquear a un usuario.',
            '     *',
            '     * @var int',
            '     * @since 1.5piloto.71',
            '     */',
            '    public const INTENTOS_MAXIMOS_AUTENTICACION = 5;',
            '',
            '    /**',
            '     * Duración del bloqueo por intentos fallidos, en segundos.',
            '     *',
            '     * 900 segundos = 15 minutos.',
            '     *',
            '     * @var int',
            '     * @since 1.5piloto.71',
            '     */',
            '    public const BLOQUEO_AUTENTICACION_SEGUNDOS = 900;',
            '',
            '    /**',
            '     * Hash bcrypt válido usado como señuelo para igualar tiempos de respuesta.',
            '     *',
            '     * Cuando un usuario no existe, no tiene credencial, o está bloqueado, se',
            '     * ejecuta password_verify() contra este hash para que el tiempo total del',
            '     * intento sea similar al de un login exitoso. Evita ataques de temporización.',
            '     *',
            '     * @var string',
            '     * @since 1.5piloto.71',
            '     */',
            '    public const HASH_DUMMY_AUTENTICACION = \'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi\';',
            '',
            '    public const NOMBRE_ADMIN = \'Administrador\';',
            '    // Sobre las sesiones',
            '    public const PREFIJO_SESSION = self::NOMBRE_APP . "_";',
        ],
        'reemplazar' => [
            '    // Nota (v1.5piloto.76e): las constantes propias de la',
            '    // aplicación (NOMBRE_APP, NOMBRE_APP_CREDENCIALES, VERSION_APP,',
            '    // AUTOR_APP, PREFIJO_SESSION, INTENTOS_MAXIMOS_AUTENTICACION,',
            '    // BLOQUEO_AUTENTICACION_SEGUNDOS, HASH_DUMMY_AUTENTICACION,',
            '    // NOMBRE_ADMIN) se movieron a `Aplicacion/ConfiguracionApli.php`.',
            '    // `Conf` (este archivo) contiene solo constantes del framework.',
        ],
    ],

    // ============================================================
    // FRAMEWORK — Configuracion/Entorno.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Configuracion/Entorno.php',
        'descripcion' => 'Entorno.php: bump a 1.3.7',
        'buscar' => [' * @version 1.3.6'],
        'reemplazar' => [' * @version 1.3.7'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Configuracion/Entorno.php',
        'descripcion' => 'Entorno.php: agregar setter de prefijo de sesión',
        'buscar' => [
            '    /**',
            '     * Verifica si el método de persistencia es XML.',
            '     *',
            '     * @return bool',
            '     */',
            '    public static function es_persistencia_xml(): bool',
            '    {',
            '        return self::$persistencia === self::PERSISTENCIA_XML;',
            '    }',
            '',
            '    // ═══════════════════════════════════════════════════════════',
            '    // UBICACIÓN GEOGRÁFICA (v1.3.6)',
            '    // ═══════════════════════════════════════════════════════════',
        ],
        'reemplazar' => [
            '    /**',
            '     * Verifica si el método de persistencia es XML.',
            '     *',
            '     * @return bool',
            '     */',
            '    public static function es_persistencia_xml(): bool',
            '    {',
            '        return self::$persistencia === self::PERSISTENCIA_XML;',
            '    }',
            '',
            '    // ══════════════════════════════════════════════',
            '    // PREFIJO DE SESIÓN (v1.5i.7h)',
            '    // ══════════════════════════════════════════════',
            '',
            '    /**',
            '     * Prefijo usado para las claves de sesión que el framework',
            '     * guarda (por ejemplo, las coordenadas geográficas).',
            '     *',
            '     * El framework no conoce el nombre de la aplicación. El',
            '     * piloto llama a `establecer_prefijo_sesion()` al arrancar',
            '     * (con `ConfiguracionApli::PREFIJO_SESSION`) para alinear',
            '     * las claves de sesión con su propio nombre.',
            '     *',
            '     * @var string',
            '     * @since 1.5i.7h',
            '     */',
            '    private static string $prefijo_sesion = \'iteradores_\';',
            '',
            '    /**',
            '     * Define el prefijo de sesión que el framework usará.',
            '     *',
            '     * @param string $prefijo',
            '     * @return void',
            '     * @since 1.5i.7h',
            '     */',
            '    public static function establecer_prefijo_sesion(string $prefijo): void',
            '    {',
            '        self::$prefijo_sesion = $prefijo;',
            '    }',
            '',
            '    /**',
            '     * Devuelve el prefijo de sesión actual.',
            '     *',
            '     * @return string',
            '     * @since 1.5i.7h',
            '     */',
            '    public static function prefijo_sesion(): string',
            '    {',
            '        return self::$prefijo_sesion;',
            '    }',
            '',
            '    // ═══════════════════════════════════════════════════════════',
            '    // UBICACIÓN GEOGRÁFICA (v1.3.6)',
            '    // ═══════════════════════════════════════════════════════════',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Configuracion/Entorno.php',
        'descripcion' => 'Entorno.php: usar self::$prefijo_sesion en coordenadas()',
        'buscar' => [
            '        $clave_sesion = Conf::PREFIJO_SESSION . \'coordenadas\';',
        ],
        'reemplazar' => [
            '        $clave_sesion = self::$prefijo_sesion . \'coordenadas\';',
        ],
    ],

    // ============================================================
    // PILOTO — Aplicacion/ConfiguracionApli.php (nuevo)
    // ============================================================

    [
        'tipo' => 'crear',
        'archivo' => 'Aplicacion/ConfiguracionApli.php',
        'descripcion' => 'ConfiguracionApli.php nuevo (constantes del piloto)',
        'contenido' => [
            '<?php',
            '/**',
            ' * Configuración propia de la aplicación piloto.',
            ' *',
            ' * Contiene las constantes que describen a la aplicación concreta',
            ' * (nombre, credenciales, rate limiting de autenticación, prefijo',
            ' * de sesión). Hereda de {@link \Iteradores\Configuracion\Conf}',
            ' * para tener acceso a los defaults del framework.',
            ' *',
            ' * Los valores de producción son los defaults. Los valores',
            ' * reducidos para modo pruebas se eligen en tiempo de ejecución',
            ' * vía `Entorno::es_pruebas()`.',
            ' *',
            ' * @author Ignacio David Baigorria',
            ' * @version 1.5piloto.76e',
            ' * @since   1.5piloto.76e',
            ' */',
            '',
            'require_once __DIR__ . \'/../Configuracion/Configuracion.php\';',
            'require_once __DIR__ . \'/../Configuracion/Entorno.php\';',
            '',
            'use Iteradores\\Configuracion\\Conf;',
            'use Iteradores\\Configuracion\\Entorno;',
            '',
            'class ConfiguracionApli extends Conf {',
            '',
            '    // --- Sobre la aplicación ---',
            '',
            '    /**',
            '     * Nombre del grafo principal de la aplicación.',
            '     *',
            '     * @var string',
            '     */',
            '    public const NOMBRE_APP = "AdministradorDeViajes";',
            '',
            '    /**',
            '     * Versión de la aplicación piloto.',
            '     *',
            '     * @var string',
            '     */',
            '    public const VERSION_APP = "0.0.0";',
            '',
            '    /**',
            '     * Autor de la aplicación piloto.',
            '     *',
            '     * @var string',
            '     */',
            '    public const AUTOR_APP = "Ignacio David Baigorria";',
            '',
            '    /**',
            '     * Nombre del grafo de credenciales.',
            '     *',
            '     * Es un grafo separado del principal que contiene únicamente:',
            '     * - los nodos usuarios con codigo_hash y contrasena',
            '     * - los nodos de sesiones activas',
            '     *',
            '     * El nombre de usuario (clave del enlace en `usuarios`) es el',
            '     * punto de unión entre ambos grafos.',
            '     *',
            '     * @var string',
            '     * @since 1.5piloto.69',
            '     */',
            '    public const NOMBRE_APP_CREDENCIALES = self::NOMBRE_APP . "_credenciales";',
            '',
            '    // --- Rate limiting de autenticación ---',
            '',
            '    /**',
            '     * Cantidad de intentos fallidos consecutivos antes de bloquear',
            '     * a un usuario, en modo producción.',
            '     *',
            '     * @var int',
            '     * @since 1.5piloto.71',
            '     */',
            '    public const INTENTOS_MAXIMOS_AUTENTICACION = 5;',
            '',
            '    /**',
            '     * Cantidad de intentos fallidos consecutivos antes de bloquear',
            '     * a un usuario, en modo pruebas.',
            '     *',
            '     * Se deja igual al valor de producción para no cambiar la',
            '     * semántica del rate limiting: solo cambia la duración del',
            '     * bloqueo (ver {@link BLOQUEO_AUTENTICACION_SEGUNDOS_PRUEBAS}).',
            '     *',
            '     * @var int',
            '     * @since 1.5piloto.76e',
            '     */',
            '    public const INTENTOS_MAXIMOS_AUTENTICACION_PRUEBAS = 5;',
            '',
            '    /**',
            '     * Duración del bloqueo por intentos fallidos, en segundos,',
            '     * en modo producción. 900 segundos = 15 minutos.',
            '     *',
            '     * @var int',
            '     * @since 1.5piloto.71',
            '     */',
            '    public const BLOQUEO_AUTENTICACION_SEGUNDOS = 900;',
            '',
            '    /**',
            '     * Duración del bloqueo por intentos fallidos, en segundos,',
            '     * en modo pruebas. Reducido para que las pruebas del plugin',
            '     * puedan esperar la expiración del bloqueo.',
            '     *',
            '     * @var int',
            '     * @since 1.5piloto.76e',
            '     */',
            '    public const BLOQUEO_AUTENTICACION_SEGUNDOS_PRUEBAS = 2;',
            '',
            '    /**',
            '     * Hash bcrypt válido usado como señuelo para igualar tiempos',
            '     * de respuesta. Cuando un usuario no existe, no tiene',
            '     * credencial, o está bloqueado, se ejecuta password_verify()',
            '     * contra este hash para que el tiempo total del intento sea',
            '     * similar al de un login exitoso. Evita ataques de',
            '     * temporización.',
            '     *',
            '     * @var string',
            '     * @since 1.5piloto.71',
            '     */',
            '    public const HASH_DUMMY_AUTENTICACION = \'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi\';',
            '',
            '    /**',
            '     * Nombre de usuario del administrador creado automáticamente',
            '     * en el primer arranque.',
            '     *',
            '     * @var string',
            '     */',
            '    public const NOMBRE_ADMIN = \'Administrador\';',
            '',
            '    /**',
            '     * Prefijo de sesión basado en el nombre de la app.',
            '     *',
            '     * @var string',
            '     */',
            '    public const PREFIJO_SESSION = self::NOMBRE_APP . "_";',
            '',
            '    // --- Métodos conmutados por modo de ejecución ---',
            '',
            '    /**',
            '     * Devuelve el máximo de intentos fallidos según el modo actual.',
            '     *',
            '     * @return int',
            '     * @since 1.5piloto.76e',
            '     */',
            '    public static function intentos_maximos_autenticacion(): int',
            '    {',
            '        return Entorno::es_pruebas()',
            '            ? self::INTENTOS_MAXIMOS_AUTENTICACION_PRUEBAS',
            '            : self::INTENTOS_MAXIMOS_AUTENTICACION;',
            '    }',
            '',
            '    /**',
            '     * Devuelve la duración del bloqueo (segundos) según el modo',
            '     * actual.',
            '     *',
            '     * @return int',
            '     * @since 1.5piloto.76e',
            '     */',
            '    public static function bloqueo_autenticacion_segundos(): int',
            '    {',
            '        return Entorno::es_pruebas()',
            '            ? self::BLOQUEO_AUTENTICACION_SEGUNDOS_PRUEBAS',
            '            : self::BLOQUEO_AUTENTICACION_SEGUNDOS;',
            '    }',
            '}',
            '',
            '?>',
        ],
    ],

    // ============================================================
    // PILOTO — index.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: bump a 1.5piloto.76e',
        'buscar' => [' * @version   1.5piloto.73k'],
        'reemplazar' => [' * @version   1.5piloto.76e'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: require_once de ConfiguracionApli',
        'buscar' => [
            '// Incluir módulos de la aplicación.',
            '// Primero `FuncionesAuxiliares.php`, que define `guardar_ambos`.',
            'require_once __DIR__ . \'/Aplicacion/FuncionesAuxiliares.php\';',
        ],
        'reemplazar' => [
            '// Incluir módulos de la aplicación.',
            '// Primero `ConfiguracionApli.php`, que define las constantes',
            '// propias del piloto (hereda del `Conf` del framework).',
            'require_once __DIR__ . \'/Aplicacion/ConfiguracionApli.php\';',
            '// `FuncionesAuxiliares.php` define `guardar_ambos`.',
            'require_once __DIR__ . \'/Aplicacion/FuncionesAuxiliares.php\';',
        ],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'descripcion' => 'index.php: establecer prefijo de sesión del piloto',
        'buscar' => [
            'Entorno::establecer_modo(Conf::LOCAL ? Entorno::MODO_PRUEBAS : Entorno::MODO_PRODUCCION);',
        ],
        'reemplazar' => [
            'Entorno::establecer_modo(Conf::LOCAL ? Entorno::MODO_PRUEBAS : Entorno::MODO_PRODUCCION);',
            '// Alinear el prefijo de sesión del framework con el del piloto',
            '// (el framework no conoce el nombre de la app).',
            'Entorno::establecer_prefijo_sesion(ConfiguracionApli::PREFIJO_SESSION);',
        ],
    ],

    // ============================================================
    // PILOTO — Aplicacion/Autenticacion/Autenticacion.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Autenticacion.php: bump a 1.5piloto.76e',
        'buscar' => [' * @version   1.5piloto.75a'],
        'reemplazar' => [' * @version   1.5piloto.76e'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Autenticacion.php: usar método conmutado para intentos',
        'buscar' => [
            '    if ($intentos >= Conf::INTENTOS_MAXIMOS_AUTENTICACION) {',
            '        $hasta = time() + Conf::BLOQUEO_AUTENTICACION_SEGUNDOS;',
        ],
        'reemplazar' => [
            '    if ($intentos >= ConfiguracionApli::intentos_maximos_autenticacion()) {',
            '        $hasta = time() + ConfiguracionApli::bloqueo_autenticacion_segundos();',
        ],
    ],

    // ============================================================
    // REEMPLAZOS GLOBALES — NOMBRE_APP_CREDENCIALES (antes que NOMBRE_APP)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/GrafoCredenciales.php',
        'todos' => true,
        'descripcion' => 'GrafoCredenciales: NOMBRE_APP_CREDENCIALES',
        'buscar' => ['Conf::NOMBRE_APP_CREDENCIALES'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP_CREDENCIALES'],
    ],

    // ============================================================
    // REEMPLAZOS GLOBALES — NOMBRE_APP (12 archivos)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/GrafoCredenciales.php',
        'todos' => true,
        'descripcion' => 'GrafoCredenciales: NOMBRE_APP',
        'buscar' => ['Conf::NOMBRE_APP'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Empresas/Empresa.php',
        'todos' => true,
        'descripcion' => 'Empresa: NOMBRE_APP',
        'buscar' => ['Conf::NOMBRE_APP'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Liquidaciones/Liquidacion.php',
        'todos' => true,
        'descripcion' => 'Liquidacion: NOMBRE_APP',
        'buscar' => ['Conf::NOMBRE_APP'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'todos' => true,
        'descripcion' => 'Pasajero: NOMBRE_APP',
        'buscar' => ['Conf::NOMBRE_APP'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Rendiciones/Rendicion.php',
        'todos' => true,
        'descripcion' => 'Rendicion: NOMBRE_APP',
        'buscar' => ['Conf::NOMBRE_APP'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'todos' => true,
        'descripcion' => 'Usuario: NOMBRE_APP',
        'buscar' => ['Conf::NOMBRE_APP'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Vehiculos/Vehiculo.php',
        'todos' => true,
        'descripcion' => 'Vehiculo: NOMBRE_APP',
        'buscar' => ['Conf::NOMBRE_APP'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'todos' => true,
        'descripcion' => 'Venta: NOMBRE_APP',
        'buscar' => ['Conf::NOMBRE_APP'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'todos' => true,
        'descripcion' => 'Viaje: NOMBRE_APP',
        'buscar' => ['Conf::NOMBRE_APP'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'todos' => true,
        'descripcion' => 'ViajeAsientos: NOMBRE_APP',
        'buscar' => ['Conf::NOMBRE_APP'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeMicros.php',
        'todos' => true,
        'descripcion' => 'ViajeMicros: NOMBRE_APP',
        'buscar' => ['Conf::NOMBRE_APP'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
        'todos' => true,
        'descripcion' => 'ViajeOpciones: NOMBRE_APP',
        'buscar' => ['Conf::NOMBRE_APP'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'todos' => true,
        'descripcion' => 'index.php: NOMBRE_APP',
        'buscar' => ['Conf::NOMBRE_APP'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_APP'],
    ],

    // ============================================================
    // REEMPLAZOS GLOBALES — NOMBRE_ADMIN (2 archivos)
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'todos' => true,
        'descripcion' => 'Usuario: NOMBRE_ADMIN',
        'buscar' => ['Conf::NOMBRE_ADMIN'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_ADMIN'],
    ],
    [
        'tipo' => 'reemplazar',
        'archivo' => 'index.php',
        'todos' => true,
        'descripcion' => 'index.php: NOMBRE_ADMIN',
        'buscar' => ['Conf::NOMBRE_ADMIN'],
        'reemplazar' => ['ConfiguracionApli::NOMBRE_ADMIN'],
    ],

    // ============================================================
    // REEMPLAZOS GLOBALES — HASH_DUMMY_AUTENTICACION
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'todos' => true,
        'descripcion' => 'Autenticacion: HASH_DUMMY_AUTENTICACION',
        'buscar' => ['Conf::HASH_DUMMY_AUTENTICACION'],
        'reemplazar' => ['ConfiguracionApli::HASH_DUMMY_AUTENTICACION'],
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
        $es_todos = !empty($cambio['todos']);

        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) {
            $bloques_fallidos[] = "$archivo_rel: bloque no encontrado - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        if (!$es_todos && $ocurrencias > 1) {
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