<?php
/**
 * Aplicador de cambios automáticos — Administrador de Viajes.
 *
 * Tanda C — v1.5piloto.71: rate limiting y tiempos constantes.
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

    // ==========================================================
    // Configuracion.php — constantes de la Tanda C
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Configuracion/Configuracion.php',
        'descripcion' => 'Configuracion.php: bump de version',
        'buscar' => [
            ' * @version 1.5piloto.70c',
        ],
        'reemplazar' => [
            ' * @version 1.5piloto.71',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Configuracion/Configuracion.php',
        'descripcion' => 'Configuracion.php: constantes de autenticacion',
        'buscar' => [
            '    public const NOMBRE_APP_CREDENCIALES = self::NOMBRE_APP . "_credenciales";',
        ],
        'reemplazar' => [
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
        ],
    ],

    // ==========================================================
    // Usuario.php — campos nuevos al crear y actualizar credenciales
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: bump de version',
        'buscar' => [
            ' * @version   1.5piloto.70',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.71',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: intentos_fallidos al crear en credenciales',
        'buscar' => [
            '        $nodo_cred = Nodo::crear_con_dato($nombre_usuario);',
            '        if ($codigo_acceso !== \'\') {',
            '            $nodo_cred->_adyacente_en(Nodo::crear_con_dato(password_hash($codigo_acceso, PASSWORD_DEFAULT)), \'codigo_hash\');',
            '        }',
            '        if ($contrasena !== \'\') {',
            '            $nodo_cred->_adyacente_en(Nodo::crear_con_dato(password_hash($contrasena, PASSWORD_DEFAULT)), \'contrasena\');',
            '        }',
            '        $raiz_cred->_adyacente_en($nodo_cred, $nombre_usuario);',
            '    });',
        ],
        'reemplazar' => [
            '        $nodo_cred = Nodo::crear_con_dato($nombre_usuario);',
            '        if ($codigo_acceso !== \'\') {',
            '            $nodo_cred->_adyacente_en(Nodo::crear_con_dato(password_hash($codigo_acceso, PASSWORD_DEFAULT)), \'codigo_hash\');',
            '        }',
            '        if ($contrasena !== \'\') {',
            '            $nodo_cred->_adyacente_en(Nodo::crear_con_dato(password_hash($contrasena, PASSWORD_DEFAULT)), \'contrasena\');',
            '        }',
            '        $nodo_cred->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'intentos_fallidos\');',
            '        $raiz_cred->_adyacente_en($nodo_cred, $nombre_usuario);',
            '    });',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Usuarios/Usuario.php',
        'descripcion' => 'Usuario.php: resetear intentos al cambiar credenciales',
        'buscar' => [
            '            if ($codigo_acceso !== \'\') {',
            '                $hash_nuevo = password_hash($codigo_acceso, PASSWORD_DEFAULT);',
            '                $nodo_hash = $nodo_cred->adyacente(\'codigo_hash\');',
            '                if ($nodo_hash) $nodo_hash->_dato($hash_nuevo);',
            '                else $nodo_cred->_adyacente_en(Nodo::crear_con_dato($hash_nuevo), \'codigo_hash\');',
            '            }',
            '            if ($contrasena !== \'\') {',
            '                $hash = password_hash($contrasena, PASSWORD_DEFAULT);',
            '                $nodo_pass = $nodo_cred->adyacente(\'contrasena\');',
            '                if ($nodo_pass) $nodo_pass->_dato($hash);',
            '                else $nodo_cred->_adyacente_en(Nodo::crear_con_dato($hash), \'contrasena\');',
            '            }',
            '        });',
            '    }',
        ],
        'reemplazar' => [
            '            if ($codigo_acceso !== \'\') {',
            '                $hash_nuevo = password_hash($codigo_acceso, PASSWORD_DEFAULT);',
            '                $nodo_hash = $nodo_cred->adyacente(\'codigo_hash\');',
            '                if ($nodo_hash) $nodo_hash->_dato($hash_nuevo);',
            '                else $nodo_cred->_adyacente_en(Nodo::crear_con_dato($hash_nuevo), \'codigo_hash\');',
            '            }',
            '            if ($contrasena !== \'\') {',
            '                $hash = password_hash($contrasena, PASSWORD_DEFAULT);',
            '                $nodo_pass = $nodo_cred->adyacente(\'contrasena\');',
            '                if ($nodo_pass) $nodo_pass->_dato($hash);',
            '                else $nodo_cred->_adyacente_en(Nodo::crear_con_dato($hash), \'contrasena\');',
            '            }',
            '            // Al cambiar credenciales, resetear el estado de bloqueo.',
            '            $nodo_intentos = $nodo_cred->adyacente(\'intentos_fallidos\');',
            '            if ($nodo_intentos) $nodo_intentos->_dato(\'0\');',
            '            else $nodo_cred->_adyacente_en(Nodo::crear_con_dato(\'0\'), \'intentos_fallidos\');',
            '            $nodo_cred->eliminar_adyacente(\'bloqueado_hasta\');',
            '        });',
            '    }',
        ],
    ],

    // ==========================================================
    // aplicacion_POST.php — documentar los campos nuevos
    // ==========================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST.php: bump de version',
        'buscar' => [
            ' * @version   1.5piloto.70d',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.71',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST.php: campos nuevos en credenciales',
        'buscar' => [
            ' * - Grafo de credenciales (`Conf::NOMBRE_APP_CREDENCIALES`): usuarios',
            ' *   con `codigo_hash` y `contrasena` únicamente, más las sesiones activas.',
            ' *   El nombre de usuario (clave del enlace en `usuarios`) es el punto de',
            ' *   unión entre ambos grafos.',
        ],
        'reemplazar' => [
            ' * - Grafo de credenciales (`Conf::NOMBRE_APP_CREDENCIALES`): usuarios',
            ' *   con `codigo_hash`, `contrasena` y los campos de rate limiting',
            ' *   (`intentos_fallidos`, `bloqueado_hasta`, `ultimo_acceso`,',
            ' *   `ip_ultimo_acceso`), más las sesiones activas.',
            ' *   El nombre de usuario (clave del enlace en `usuarios`) es el punto de',
            ' *   unión entre ambos grafos.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST.php: seccion rate limiting',
        'buscar' => [
            ' * | `codigo_hash`   | **(en el grafo de credenciales)** Nodo con dato string: hash bcrypt del código de acceso. |',
            ' * | `contrasena`    | **(en el grafo de credenciales)** Nodo con dato string: hash de contraseña (opcional).    |',
        ],
        'reemplazar' => [
            ' * | `codigo_hash`   | **(en el grafo de credenciales)** Nodo con dato string: hash bcrypt del código de acceso. |',
            ' * | `contrasena`    | **(en el grafo de credenciales)** Nodo con dato string: hash de contraseña (opcional).    |',
            ' * | `intentos_fallidos` | **(en el grafo de credenciales)** Nodo con dato string numérico: cantidad de intentos fallidos consecutivos. Se resetea a `"0"` con un login exitoso o al cambiar credenciales. |',
            ' * | `bloqueado_hasta`   | **(en el grafo de credenciales)** Nodo con dato string: timestamp Unix hasta el cual el usuario está bloqueado. Solo existe si hubo bloqueo. Al expirar, se elimina y se resetean los intentos. |',
            ' * | `ultimo_acceso`     | **(en el grafo de credenciales)** Nodo con dato string `"DD/MM/YYYY HH:MM"`: fecha del último login exitoso. |',
            ' * | `ip_ultimo_acceso`  | **(en el grafo de credenciales)** Nodo con dato string: dirección IP del último login exitoso. |',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'aplicacion_POST.php',
        'descripcion' => 'aplicacion_POST.php: nota de rate limiting',
        'buscar' => [
            ' * **Nota (migración v1.5piloto.68):** Los códigos de acceso existentes se hashearon',
            ' * con `password_hash` y se guardaron como `codigo_hash`. El admin principal ya no se',
            ' * crea con `codigo_acceso`: se crea con `codigo_hash` y se chequea por nombre en',
            ' * `index.php`.',
        ],
        'reemplazar' => [
            ' * **Nota (migración v1.5piloto.68):** Los códigos de acceso existentes se hashearon',
            ' * con `password_hash` y se guardaron como `codigo_hash`. El admin principal ya no se',
            ' * crea con `codigo_acceso`: se crea con `codigo_hash` y se chequea por nombre en',
            ' * `index.php`.',
            ' *',
            ' * **Nota (rate limiting, v1.5piloto.71):** Después de',
            ' * `Conf::INTENTOS_MAXIMOS_AUTENTICACION` intentos fallidos consecutivos, el usuario',
            ' * queda bloqueado durante `Conf::BLOQUEO_AUTENTICACION_SEGUNDOS` segundos. Mientras',
            ' * esté bloqueado, el login devuelve el mismo error genérico que un fallo normal, y',
            ' * el tiempo de respuesta se mantiene similar usando `Conf::HASH_DUMMY_AUTENTICACION`.',
        ],
    ],

    // ==========================================================
    // Autenticacion.php — reescritura completa
    // ==========================================================

    [
        'tipo' => 'crear',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Autenticacion.php: reescribir con rate limiting',
        'contenido' => [
            '<?php',
            '/**',
            ' * Autenticación de usuarios.',
            ' *',
            ' * Cada intento de login (por código o por usuario+contraseña) aplica:',
            ' * - Rate limiting: tras N intentos fallidos, el usuario queda bloqueado.',
            ' * - Tiempo constante: todos los fallos verifican contra un hash dummy.',
            ' * - Auditoría: se registra el último acceso y su IP.',
            ' * - Rehash automático: si el hash quedó desactualizado, se regenera.',
            ' *',
            ' * @package   Iteradores',
            ' * @since     1.5piloto.1',
            ' * @version   1.5piloto.71',
            ' */',
            '',
            'use Iteradores\\Configuracion\\Conf;',
            'use Iteradores\\Nodos\\Nodo;',
            'use Iteradores\\Controlador\\Controlador;',
            'include_once("./Configuracion/Configuracion.php");',
            'include_once("./Nodos/Nodo.php");',
            'include_once("./Controlador/Controlador.php");',
            '',
            '/**',
            ' * Autentica a un usuario mediante su código de acceso.',
            ' *',
            ' * @param string $codigo Código de acceso.',
            ' * @return array|null Datos del usuario autenticado o null.',
            ' */',
            'function autenticar_por_codigo(string $codigo): ?array {',
            '    $nombre_usuario = null;',
            '    $ip_cliente = _ip_cliente();',
            '',
            '    en_grafo_credenciales(function() use ($codigo, $ip_cliente, &$nombre_usuario) {',
            '        $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '        if (!$raiz_usuarios) {',
            '            _verificacion_dummy($codigo);',
            '            return;',
            '        }',
            '',
            '        $encontrado = null;',
            '        $encontrado_nodo = null;',
            '',
            '        foreach ($raiz_usuarios->adyacentes() as $nombre => $nodo_usuario) {',
            '            $nodo_hash = $nodo_usuario->adyacente(\'codigo_hash\');',
            '            if ($nodo_hash && password_verify($codigo, $nodo_hash->dato())) {',
            '                $encontrado = (string)$nombre;',
            '                $encontrado_nodo = $nodo_usuario;',
            '                break;',
            '            }',
            '        }',
            '',
            '        // Si no hay match en ningún usuario, dummy verify y fin.',
            '        if ($encontrado === null) {',
            '            _verificacion_dummy($codigo);',
            '            return;',
            '        }',
            '',
            '        // ¿Está bloqueado el usuario?',
            '        if (_esta_bloqueado($encontrado_nodo)) {',
            '            _verificacion_dummy($codigo);',
            '            return;',
            '        }',
            '',
            '        // Login exitoso: resetear intentos, registrar acceso, rehash.',
            '        _registrar_login_exitoso($encontrado_nodo, $ip_cliente);',
            '        $nombre_usuario = $encontrado;',
            '    });',
            '',
            '    if ($nombre_usuario === null) return null;',
            '',
            '    return _construir_respuesta_login($nombre_usuario);',
            '}',
            '',
            '/**',
            ' * Autentica a un usuario mediante su nombre y contraseña.',
            ' *',
            ' * @param string $nombre_usuario Nombre de usuario.',
            ' * @param string $contrasena Contraseña en texto plano.',
            ' * @return array|null Datos del usuario autenticado o null.',
            ' */',
            'function autenticar_por_usuario(string $nombre_usuario, string $contrasena): ?array {',
            '    $verificado = false;',
            '    $ip_cliente = _ip_cliente();',
            '',
            '    en_grafo_credenciales(function() use ($nombre_usuario, $contrasena, $ip_cliente, &$verificado) {',
            '        $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '        if (!$raiz_usuarios) {',
            '            _verificacion_dummy($contrasena);',
            '            return;',
            '        }',
            '',
            '        $nodo_usuario = $raiz_usuarios->adyacente($nombre_usuario);',
            '        if (!$nodo_usuario) {',
            '            _verificacion_dummy($contrasena);',
            '            return;',
            '        }',
            '',
            '        $nodo_contrasena = $nodo_usuario->adyacente(\'contrasena\');',
            '        if (!$nodo_contrasena) {',
            '            _verificacion_dummy($contrasena);',
            '            return;',
            '        }',
            '',
            '        // ¿Está bloqueado el usuario?',
            '        if (_esta_bloqueado($nodo_usuario)) {',
            '            _verificacion_dummy($contrasena);',
            '            return;',
            '        }',
            '',
            '        // Verificar contraseña.',
            '        if (!password_verify($contrasena, $nodo_contrasena->dato())) {',
            '            _registrar_intento_fallido($nodo_usuario);',
            '            return;',
            '        }',
            '',
            '        // Login exitoso: resetear intentos, registrar acceso, rehash.',
            '        _registrar_login_exitoso($nodo_usuario, $ip_cliente);',
            '        $verificado = true;',
            '    });',
            '',
            '    if (!$verificado) return null;',
            '',
            '    return _construir_respuesta_login($nombre_usuario);',
            '}',
            '',
            '/**',
            ' * Valida un token de sesión y devuelve el nombre de usuario y su nodo.',
            ' *',
            ' * @param string $token Token de sesión.',
            ' * @return array|null Array con \'nombre_usuario\' y \'nodo\', o null si no es válido.',
            ' */',
            'function validar_token_sesion(string $token): ?array {',
            '    $nombre_usuario = null;',
            '    en_grafo_credenciales(function() use ($token, &$nombre_usuario) {',
            '        $raiz = Nodo::nodo_por_id(\'sesiones\');',
            '        if (!$raiz) return;',
            '',
            '        $nodo_sesion = $raiz->adyacente($token);',
            '        if (!$nodo_sesion) return;',
            '',
            '        $nodo_usuario = $nodo_sesion->adyacente(\'usuario\');',
            '        if (!$nodo_usuario) return;',
            '',
            '        $nombre_usuario = $nodo_usuario->dato();',
            '    });',
            '',
            '    if ($nombre_usuario === null) return null;',
            '',
            '    $raiz_usuarios = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_usuarios) return null;',
            '',
            '    $nodo_usuario_real = $raiz_usuarios->adyacente($nombre_usuario);',
            '    if (!$nodo_usuario_real) return null;',
            '',
            '    return [',
            '        \'nombre_usuario\' => $nombre_usuario,',
            '        \'nodo\' => $nodo_usuario_real,',
            '    ];',
            '}',
            '',
            '// ============================================================',
            '// Funciones auxiliares de autenticación',
            '// ============================================================',
            '',
            '/**',
            ' * Devuelve la IP del cliente o un marcador si no está disponible.',
            ' *',
            ' * @return string',
            ' */',
            'function _ip_cliente(): string {',
            '    return isset($_SERVER[\'REMOTE_ADDR\']) ? (string)$_SERVER[\'REMOTE_ADDR\'] : \'desconocida\';',
            '}',
            '',
            '/**',
            ' * Ejecuta una verificación dummy para igualar tiempos.',
            ' *',
            ' * @param string $valor Valor a verificar (nunca coincide).',
            ' * @return void',
            ' */',
            'function _verificacion_dummy(string $valor): void {',
            '    password_verify($valor, Conf::HASH_DUMMY_AUTENTICACION);',
            '}',
            '',
            '/**',
            ' * Comprueba si un usuario está bloqueado. Si el bloqueo expiró, lo limpia.',
            ' *',
            ' * @param Nodo $nodo_usuario Nodo del usuario en el grafo de credenciales.',
            ' * @return bool True si sigue bloqueado.',
            ' */',
            'function _esta_bloqueado(Nodo $nodo_usuario): bool {',
            '    $nodo_bloqueo = $nodo_usuario->adyacente(\'bloqueado_hasta\');',
            '    if (!$nodo_bloqueo) return false;',
            '',
            '    $hasta = (int)$nodo_bloqueo->dato();',
            '    if ($hasta > time()) {',
            '        return true;',
            '    }',
            '',
            '    // El bloqueo expiró: limpiar y resetear intentos.',
            '    $nodo_usuario->eliminar_adyacente(\'bloqueado_hasta\');',
            '    $nodo_intentos = $nodo_usuario->adyacente(\'intentos_fallidos\');',
            '    if ($nodo_intentos) $nodo_intentos->_dato(\'0\');',
            '    return false;',
            '}',
            '',
            '/**',
            ' * Registra un intento fallido. Si llega al máximo, bloquea al usuario.',
            ' *',
            ' * @param Nodo $nodo_usuario Nodo del usuario en el grafo de credenciales.',
            ' * @return void',
            ' */',
            'function _registrar_intento_fallido(Nodo $nodo_usuario): void {',
            '    $nodo_intentos = $nodo_usuario->adyacente(\'intentos_fallidos\');',
            '    $intentos = $nodo_intentos ? (int)$nodo_intentos->dato() : 0;',
            '    $intentos++;',
            '',
            '    if ($nodo_intentos) $nodo_intentos->_dato((string)$intentos);',
            '    else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato((string)$intentos), \'intentos_fallidos\');',
            '',
            '    if ($intentos >= Conf::INTENTOS_MAXIMOS_AUTENTICACION) {',
            '        $hasta = time() + Conf::BLOQUEO_AUTENTICACION_SEGUNDOS;',
            '        $nodo_bloqueo = $nodo_usuario->adyacente(\'bloqueado_hasta\');',
            '        if ($nodo_bloqueo) $nodo_bloqueo->_dato((string)$hasta);',
            '        else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato((string)$hasta), \'bloqueado_hasta\');',
            '    }',
            '}',
            '',
            '/**',
            ' * Registra un login exitoso: resetea intentos, guarda auditoría, rehash.',
            ' *',
            ' * @param Nodo $nodo_usuario Nodo del usuario en el grafo de credenciales.',
            ' * @param string $ip_cliente IP del cliente.',
            ' * @return void',
            ' */',
            'function _registrar_login_exitoso(Nodo $nodo_usuario, string $ip_cliente): void {',
            '    // Resetear intentos y bloqueo.',
            '    $nodo_intentos = $nodo_usuario->adyacente(\'intentos_fallidos\');',
            '    if ($nodo_intentos) $nodo_intentos->_dato(\'0\');',
            '    $nodo_usuario->eliminar_adyacente(\'bloqueado_hasta\');',
            '',
            '    // Auditoría.',
            '    $ahora = date(\'d/m/Y H:i\');',
            '    $nodo_ultimo = $nodo_usuario->adyacente(\'ultimo_acceso\');',
            '    if ($nodo_ultimo) $nodo_ultimo->_dato($ahora);',
            '    else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($ahora), \'ultimo_acceso\');',
            '',
            '    $nodo_ip = $nodo_usuario->adyacente(\'ip_ultimo_acceso\');',
            '    if ($nodo_ip) $nodo_ip->_dato($ip_cliente);',
            '    else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($ip_cliente), \'ip_ultimo_acceso\');',
            '}',
            '',
            '/**',
            ' * Construye la respuesta de login leyendo datos visibles de la app.',
            ' *',
            ' * También crea la sesión en el grafo de credenciales.',
            ' *',
            ' * @param string $nombre_usuario Nombre de usuario.',
            ' * @return array|null Datos del usuario autenticado o null.',
            ' */',
            'function _construir_respuesta_login(string $nombre_usuario): ?array {',
            '    $raiz_app = Nodo::nodo_por_id(\'usuarios\');',
            '    if (!$raiz_app) return null;',
            '    $nodo_app = $raiz_app->adyacente($nombre_usuario);',
            '    if (!$nodo_app) return null;',
            '',
            '    $nodo_nivel = $nodo_app->adyacente(\'nivel\');',
            '    $nodo_nombre_real = $nodo_app->adyacente(\'nombre_real\');',
            '',
            '    $usuario = [',
            '        \'nombre_usuario\' => $nombre_usuario,',
            '        \'nombre_real\' => $nodo_nombre_real ? $nodo_nombre_real->dato() : $nombre_usuario,',
            '        \'nivel\' => $nodo_nivel ? $nodo_nivel->dato() : \'terminal\',',
            '    ];',
            '',
            '    $token = crear_sesion($nombre_usuario);',
            '    $usuario[\'token_sesion\'] = $token;',
            '',
            '    return $usuario;',
            '}',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios (Tanda C) ===\n\n";

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
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado (faltan campos).\n";
        exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }

echo "[INFO] " . count($creaciones) . " archivo(s) a crear/sobrescribir, "
    . $total_reemplazos . " reemplazo(s) en "
    . count($reemplazos_por_archivo) . " archivo(s).\n\n";

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

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";