<?php
/**
 * Aplicador de cambios automáticos — AdministradorDeViajes.
 *
 * Tanda V1.5piloto.73m / Framework 1.5i.7a:
 * robustez de persistencia y rehash de credenciales.
 *
 * - PerdurarSuperestructuraStringSQL.php: `cargar` chequea la query
 *   de adyacentes, `real_escape_string` en `cargar`/`existe`/`eliminar`,
 *   cierra conexión en early-returns. Version del framework 1.5i.7a.
 * - PerdurarSuperestructuraStringJSON.php: escritura atómica
 *   (.tmp + rename), `cargar` valida `nodos` y no vacía dos veces.
 *   Version del JSON 1.0.3.
 * - Controlador.php: `cargar` devuelve `bool|null`.
 * - Autenticacion.php: rehash automático de credenciales.
 *   Version del piloto 1.5piloto.73m.
 * - prompts/prompt_framework_iteradores.md: secciones 4.2, 6.3, 6.5,
 *   8 y 10 actualizadas.
 * - prompts/prompt_piloto.md: historial v73m, Discusión actual,
 *   CHECK/OPTIMIZE documentado como hecho.
 *
 * Uso:
 *   php aplicar_cambios.php
 *
 * Si PHP no está en el PATH del sistema:
 *   C:\xampp\php\php.exe aplicar_cambios.php
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
    // Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php',
        'descripcion' => 'Bump de version a 1.5i.7a',
        'buscar' => [
            ' * @version 1.5i.7 ',
        ],
        'reemplazar' => [
            ' * @version 1.5i.7a',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php',
        'descripcion' => 'eliminar: escape y chequeo de conexion',
        'buscar' => [
            '		if (!$sql = self::crear_conexion_sql()) {',
            '			self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) no se pudo crear la conexion");',
            '			return null;',
            '		}',
            '		$sql = self::crear_conexion_sql();',
            '		if (!$rcontar = $sql->query("SELECT COUNT(*) FROM `nodo` WHERE `idsuperestructura`=\'" . $nombre . "\';")) {',
            '			self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) error intentado ver si la superestructura existe");',
            '			$sql->close();',
            '			return null;',
            '		}',
        ],
        'reemplazar' => [
            '		$sql = self::crear_conexion_sql();',
            '		if (!$sql) {',
            '			self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) no se pudo crear la conexion");',
            '			return null;',
            '		}',
            '		$nombre_escapado = $sql->real_escape_string((string)$nombre);',
            '		if (!$rcontar = $sql->query("SELECT COUNT(*) FROM `nodo` WHERE `idsuperestructura`=\'" . $nombre_escapado . "\';")) {',
            '			self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) error intentado ver si la superestructura existe");',
            '			$sql->close();',
            '			return null;',
            '		}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php',
        'descripcion' => 'eliminar: chequeo del resultado de los DELETE',
        'buscar' => [
            '		if ($cant > 0) {',
            '			$sql->query("DELETE FROM `nodo` WHERE `idsuperestructura`=\'" . $nombre . "\';");',
            '			$sql->query("DELETE FROM `adyacente` WHERE `idsuperestructura`=\'" . $nombre . "\';");',
            '			$r = true;',
            '		} else {',
        ],
        'reemplazar' => [
            '		if ($cant > 0) {',
            '			if (!$sql->query("DELETE FROM `nodo` WHERE `idsuperestructura`=\'" . $nombre_escapado . "\';")) {',
            '				self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) fallo DELETE en nodo: " . $sql->error);',
            '				$sql->close();',
            '				return null;',
            '			}',
            '			if (!$sql->query("DELETE FROM `adyacente` WHERE `idsuperestructura`=\'" . $nombre_escapado . "\';")) {',
            '				self::_error("PerdurarSuperestructuraString::eliminar_sql(nombre) fallo DELETE en adyacente: " . $sql->error);',
            '				$sql->close();',
            '				return null;',
            '			}',
            '			$r = true;',
            '		} else {',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php',
        'descripcion' => 'cargar: escape y close en error de query de nodos',
        'buscar' => [
            '		if (!$sql = self::crear_conexion_sql()) {',
            '			self::_error("PerdurarSuperestructuraString::cargar(nombre) no se pudo crear la conexion");',
            '			return null;',
            '		}',
            '',
            '		if (!$nodos = $sql->query("SELECT * FROM `nodo` WHERE `idsuperestructura`=\'" . $nombre . "\';")) {',
            '			self::_error("PerdurarSuperestructuraString::cargar(nombre) no se pudo cargar, no cargo nada");',
            '			return null;',
            '		}',
        ],
        'reemplazar' => [
            '		$sql = self::crear_conexion_sql();',
            '		if (!$sql) {',
            '			self::_error("PerdurarSuperestructuraString::cargar(nombre) no se pudo crear la conexion");',
            '			return null;',
            '		}',
            '		$nombre_escapado = $sql->real_escape_string((string)$nombre);',
            '',
            '		if (!$nodos = $sql->query("SELECT * FROM `nodo` WHERE `idsuperestructura`=\'" . $nombre_escapado . "\';")) {',
            '			self::_error("PerdurarSuperestructuraString::cargar(nombre) no se pudo cargar, no cargo nada");',
            '			$sql->close();',
            '			return null;',
            '		}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php',
        'descripcion' => 'cargar: cierra conexion en early-return de nodo vacio',
        'buscar' => [
            '		$nodo = $nodos->fetch_assoc();',
            '		if (!$nodo) {',
            '			self::_alerta("alerta al cargar, no existe superestructura con el identificador pasado como parametro");',
            '			return false;',
            '		}',
        ],
        'reemplazar' => [
            '		$nodo = $nodos->fetch_assoc();',
            '		if (!$nodo) {',
            '			self::_alerta("alerta al cargar, no existe superestructura con el identificador pasado como parametro");',
            '			$sql->close();',
            '			return false;',
            '		}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php',
        'descripcion' => 'cargar: chequea la query de adyacentes',
        'buscar' => [
            '		$adyacentes = $sql->query("SELECT * FROM `adyacente` WHERE `idsuperestructura`=\'" . $nombre . "\';");',
            '',
            '		$adyacente = $adyacentes->fetch_assoc();',
        ],
        'reemplazar' => [
            '		if (!$adyacentes = $sql->query("SELECT * FROM `adyacente` WHERE `idsuperestructura`=\'" . $nombre_escapado . "\';")) {',
            '			self::_error("PerdurarSuperestructuraString::cargar(nombre) no se pudieron cargar los adyacentes: " . $sql->error);',
            '			$sql->close();',
            '			return null;',
            '		}',
            '',
            '		$adyacente = $adyacentes->fetch_assoc();',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringSQL.php',
        'descripcion' => 'existe: escape y close en error',
        'buscar' => [
            '		if (!$sql = self::crear_conexion_sql()) {',
            '			self::_error("PerdurarSuperestructuraString::existe_sql(nombre) no se pudo crear la conexion");',
            '			return null;',
            '		}',
            '',
            '		if (!$rcontar = $sql->query("SELECT COUNT(*) FROM `nodo` WHERE `idsuperestructura`=\'" . $nombre . "\';")) {',
            '			self::_error("PerdurarSuperestructuraString::existe_sql(nombre) no se pudo contar");',
            '			return null;',
            '		}',
        ],
        'reemplazar' => [
            '		$sql = self::crear_conexion_sql();',
            '		if (!$sql) {',
            '			self::_error("PerdurarSuperestructuraString::existe_sql(nombre) no se pudo crear la conexion");',
            '			return null;',
            '		}',
            '		$nombre_escapado = $sql->real_escape_string((string)$nombre);',
            '',
            '		if (!$rcontar = $sql->query("SELECT COUNT(*) FROM `nodo` WHERE `idsuperestructura`=\'" . $nombre_escapado . "\';")) {',
            '			self::_error("PerdurarSuperestructuraString::existe_sql(nombre) no se pudo contar");',
            '			$sql->close();',
            '			return null;',
            '		}',
        ],
    ],

    // ============================================================
    // Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringJSON.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringJSON.php',
        'descripcion' => 'Bump de version a 1.0.3',
        'buscar' => [
            ' * @version 1.0.2 (Última revisión: 29/09/2026)',
        ],
        'reemplazar' => [
            ' * @version 1.0.3 (Última revisión: 30/09/2026)',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringJSON.php',
        'descripcion' => 'guardar: escritura atomica',
        'buscar' => [
            '        if (file_put_contents($ruta_archivo, $json) === false) {',
            '            self::_error("No se pudo guardar el archivo JSON: " . $ruta_archivo);',
            '            return false;',
            '        }',
            '',
            '        return true;',
            '    }',
        ],
        'reemplazar' => [
            '        // Escritura atomica: escribir a .tmp y renombrar. En la mayoria',
            '        // de los filesystems, rename() es atomico. Si el proceso muere a',
            '        // mitad de la escritura, el .json original queda intacto.',
            '        $ruta_temporal = $ruta_archivo . \'.tmp\';',
            '        if (file_put_contents($ruta_temporal, $json) === false) {',
            '            self::_error("No se pudo guardar el archivo JSON temporal: " . $ruta_temporal);',
            '            return false;',
            '        }',
            '        if (!rename($ruta_temporal, $ruta_archivo)) {',
            '            self::_error("No se pudo renombrar el archivo JSON temporal: " . $ruta_temporal);',
            '            @unlink($ruta_temporal);',
            '            return false;',
            '        }',
            '',
            '        return true;',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/PerdurarSuperestructura/PerdurarSuperestructuraStringJSON.php',
        'descripcion' => 'cargar: valida estructura y no vacia dos veces',
        'buscar' => [
            '        $estructura = json_decode($contenido, true);',
            '        if ($estructura === null) {',
            '            self::_error("Error al decodificar el archivo JSON: " . $ruta_archivo);',
            '            return null;',
            '        }',
            '',
            '        // Limpiar la superestructura actual antes de cargar',
            '        Nodo::vaciar_superestructura(static::$token);',
            '',
            '        $equivalencias = [];',
        ],
        'reemplazar' => [
            '        $estructura = json_decode($contenido, true);',
            '        if ($estructura === null || !is_array($estructura)) {',
            '            self::_error("Error al decodificar el archivo JSON: " . $ruta_archivo);',
            '            return null;',
            '        }',
            '        if (!isset($estructura[\'nodos\']) || !is_array($estructura[\'nodos\'])) {',
            '            self::_error("El JSON no tiene la clave \\"nodos\\" esperada: " . $ruta_archivo);',
            '            return null;',
            '        }',
            '',
            '        // La superestructura ya fue vaciada por Controlador::cargar.',
            '        // No vaciar de nuevo: si algo fallara entre las dos limpiezas,',
            '        // quedaria una superestructura vacia sin que nadie lo note.',
            '',
            '        $equivalencias = [];',
        ],
    ],

    // ============================================================
    // Controlador/Controlador.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Controlador/Controlador.php',
        'descripcion' => 'cargar devuelve bool|null',
        'buscar' => [
            '    /** @return bool ',
            '     * @version 1.5i.4',
            '    */',
            '    public static function cargar($nombre): bool {',
            '        Nodo::vaciar_superestructura(static::$token);',
            '        return (bool) static::delegar(\'cargar\', $nombre);',
            '    }',
        ],
        'reemplazar' => [
            '    /**',
            '     * Carga una superestructura por nombre.',
            '     *',
            '     * Devuelve `true` si se cargo, `false` si no existe, `null` si hubo',
            '     * un error (conexion, query, etc.). No castear a bool: la diferencia',
            '     * entre "no existe" y "error" es importante para decidir si se crea',
            '     * una nueva o se muere con un mensaje claro.',
            '     *',
            '     * @return bool|null',
            '     * @version 1.5i.7a',
            '     */',
            '    public static function cargar($nombre): bool|null {',
            '        Nodo::vaciar_superestructura(static::$token);',
            '        return static::delegar(\'cargar\', $nombre);',
            '    }',
        ],
    ],

    // ============================================================
    // Aplicacion/Autenticacion/Autenticacion.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'Bump de version a 1.5piloto.73m',
        'buscar' => [
            ' * @version   1.5piloto.73',
        ],
        'reemplazar' => [
            ' * @version   1.5piloto.73m',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => '_registrar_login_exitoso con rehash',
        'buscar' => [
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
        ],
        'reemplazar' => [
            'function _registrar_login_exitoso(Nodo $nodo_usuario, string $ip_cliente, string $campo_rehash = \'\', string $valor_plano = \'\'): void {',
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
            '',
            '    // Rehash: si el hash quedo desactualizado (por ejemplo, cambio el',
            '    // algoritmo por defecto en una version nueva de PHP), se regenera',
            '    // con el mismo valor plano que acabamos de verificar.',
            '    if ($campo_rehash !== \'\' && $valor_plano !== \'\') {',
            '        $nodo_hash = $nodo_usuario->adyacente($campo_rehash);',
            '        if ($nodo_hash && password_needs_rehash($nodo_hash->dato(), PASSWORD_DEFAULT)) {',
            '            $nodo_hash->_dato(password_hash($valor_plano, PASSWORD_DEFAULT));',
            '        }',
            '    }',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'autenticar_por_codigo pasa el codigo para rehash',
        'buscar' => [
            '        // Login exitoso: resetear intentos, registrar acceso, rehash.',
            '        _registrar_login_exitoso($encontrado_nodo, $ip_cliente);',
            '        $nombre_usuario = $encontrado;',
        ],
        'reemplazar' => [
            '        // Login exitoso: resetear intentos, registrar acceso, rehash.',
            '        _registrar_login_exitoso($encontrado_nodo, $ip_cliente, \'codigo_hash\', $codigo);',
            '        $nombre_usuario = $encontrado;',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Autenticacion/Autenticacion.php',
        'descripcion' => 'autenticar_por_usuario pasa la contrasena para rehash',
        'buscar' => [
            '        // Login exitoso: resetear intentos, registrar acceso, rehash.',
            '        _registrar_login_exitoso($nodo_usuario, $ip_cliente);',
            '        $verificado = true;',
        ],
        'reemplazar' => [
            '        // Login exitoso: resetear intentos, registrar acceso, rehash.',
            '        _registrar_login_exitoso($nodo_usuario, $ip_cliente, \'contrasena\', $contrasena);',
            '        $verificado = true;',
        ],
    ],

    // ============================================================
    // prompts/prompt_framework_iteradores.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Framework 4.2: cargar devuelve bool|null',
        'buscar' => [
            '- `Controlador::cargar($nombre)`: vacía la superestructura y carga la',
            '  pedida. Si el nombre no existe, deja la superestructura vacía y',
            '  devuelve `false`.',
        ],
        'reemplazar' => [
            '- `Controlador::cargar($nombre)`: vacía la superestructura y carga la',
            '  pedida. Devuelve `true` si se cargó, `false` si no existe, `null`',
            '  si hubo error (conexión, query). Los llamadores deben distinguir',
            '  "no existe" de "error".',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Framework 6.3: cargar con chequeos',
        'buscar' => [
            '### 6.3 Cómo funciona `cargar`',
            '',
            '- Vacía la superestructura actual.',
            '- Lee los nodos de la base. Los que tienen ID especial se recrean con',
            '  `crear_con_dato_e_id`. Los que no, se crean con `crear_con_dato` y se',
            '  arma un mapa de equivalencias entre IDs viejos y nuevos.',
            '- Después lee los adyacentes y reconstruye los enlaces usando las',
            '  equivalencias.',
        ],
        'reemplazar' => [
            '### 6.3 Cómo funciona `cargar`',
            '',
            '- Vacía la superestructura actual.',
            '- Lee los nodos de la base. Los que tienen ID especial se recrean con',
            '  `crear_con_dato_e_id`. Los que no, se crean con `crear_con_dato` y se',
            '  arma un mapa de equivalencias entre IDs viejos y nuevos.',
            '- Después lee los adyacentes y reconstruye los enlaces usando las',
            '  equivalencias.',
            '- **Desde 1.5i.7a:** la query de adyacentes se chequea (no se llama',
            '  `fetch_assoc()` sobre `false`). Si falla, devuelve `null`.',
            '- **Desde 1.5i.7a:** el nombre se escapa con `real_escape_string` en',
            '  `cargar`, `existe` y `eliminar`.',
            '- **Desde 1.5i.7a:** la conexión SQL se cierra en todos los',
            '  early-returns (antes quedaban conexiones abiertas en algunos casos).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Framework 6.5: JSON atomico',
        'buscar' => [
            '### 6.5 JSON con consistencia de tipos',
            '',
            '- Al guardar se fuerza string en `id` y referencias de adyacentes.',
            '- Al cargar se castea `id` y referencias a string para archivos viejos.',
            '- Se escribe con rutas absolutas basadas en `__DIR__`.',
        ],
        'reemplazar' => [
            '### 6.5 JSON con consistencia de tipos',
            '',
            '- Al guardar se fuerza string en `id` y referencias de adyacentes.',
            '- Al cargar se castea `id` y referencias a string para archivos viejos.',
            '- Se escribe con rutas absolutas basadas en `__DIR__`.',
            '- **Escritura atómica desde 1.5i.7a:** se escribe a `.tmp` y después',
            '  se renombra. Si el proceso muere a mitad, el `.json` original queda',
            '  intacto.',
            '- **Validación desde 1.5i.7a:** al cargar se chequea que exista la',
            '  clave `nodos`. Si no, se devuelve `null` y se loguea error.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Framework 8: error comun de cargar bool|null',
        'buscar' => [
            '- **Guardar con un nodo ocupado.** `Controlador::guardar` falla. Hay que',
            '  desocupar antes.',
            '- **Los IDs de nodos sin ID especial cambian entre cargas.** Si',
            '  necesitás referenciarlos desde otro nodo persistido, usá ID especial.',
        ],
        'reemplazar' => [
            '- **Guardar con un nodo ocupado.** `Controlador::guardar` falla. Hay que',
            '  desocupar antes.',
            '- **`Controlador::cargar` devuelve `bool|null`.** `true` = cargó,',
            '  `false` = no existe, `null` = error. No castear a bool sin',
            '  distinguir los dos últimos casos.',
            '- **Los IDs de nodos sin ID especial cambian entre cargas.** Si',
            '  necesitás referenciarlos desde otro nodo persistido, usá ID especial.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_framework_iteradores.md',
        'descripcion' => 'Framework historial: agregar 1.5i.7a',
        'buscar' => [
            '- **1.5i.7**: `guardar` usa transacción y divide los INSERT en chunks',
            '  de ~200 KB. Chequea el resultado de cada query. Fix del bug que',
            '  crasheaba la tabla cuando el grafo superaba `max_allowed_packet`.',
        ],
        'reemplazar' => [
            '- **1.5i.7**: `guardar` usa transacción y divide los INSERT en chunks',
            '  de ~200 KB. Chequea el resultado de cada query. Fix del bug que',
            '  crasheaba la tabla cuando el grafo superaba `max_allowed_packet`.',
            '- **1.5i.7a**: `cargar` chequea el resultado de la query de adyacentes',
            '  (no llama `fetch_assoc()` sobre `false`). `real_escape_string` en',
            '  `cargar`, `existe` y `eliminar`. Conexiones SQL se cierran en',
            '  todos los early-returns. JSON con escritura atómica (`.tmp` +',
            '  `rename`) y validación de la clave `nodos`. `Controlador::cargar`',
            '  devuelve `bool|null` para distinguir "no existe" de "error".',
        ],
    ],

    // ============================================================
    // prompts/prompt_piloto.md
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto historial: agregar v73m',
        'buscar' => [
            '  `Aplicacion/FuncionesAuxiliares.php`, y el bloque de',
            '  inicialización de la persistencia se reordenó después de los',
            '  `require_once` para que `guardar_ambos` esté definida antes',
            '  de usarse. Eliminado `Aplicacion/GuardarAmbos.php`.',
        ],
        'reemplazar' => [
            '  `Aplicacion/FuncionesAuxiliares.php`, y el bloque de',
            '  inicialización de la persistencia se reordenó después de los',
            '  `require_once` para que `guardar_ambos` esté definida antes',
            '  de usarse. Eliminado `Aplicacion/GuardarAmbos.php`.',
            '- **v73m**: fix del framework 1.5i.7a aplicado al piloto.',
            '  `Controlador::cargar` devuelve `bool|null` para distinguir',
            '  "no existe" de "error". SQL cierra conexiones en los',
            '  early-returns y escapa el nombre en todas las queries.',
            '  JSON escribe atómicamente y valida la estructura. Rehash',
            '  automático de credenciales en login exitoso: si el hash',
            '  quedó desactualizado, se regenera con la contraseña o',
            '  código recién verificado. Las tablas SQL fueron verificadas',
            '  con `CHECK`/`OPTIMIZE` en local y en producción el',
            '  30/09/2026, ambas OK.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto 8.2: CHECK/OPTIMIZE hecho',
        'buscar' => [
            '### 8.2 Limpieza pendiente',
            '',
            'Bloques y archivos de migración que se pueden eliminar cuando se',
            'confirmen en los 3 entornos:',
        ],
        'reemplazar' => [
            '### 8.2 Limpieza pendiente',
            '',
            '**Integridad de las tablas SQL:** verificada con `CHECK TABLE` y',
            '`OPTIMIZE TABLE` en local y en producción el 30/09/2026, ambas OK.',
            'No queda pendiente.',
            '',
            'Bloques y archivos de migración que se pueden eliminar cuando se',
            'confirmen en los 3 entornos:',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto Discusion actual: bump a v73m',
        'buscar' => [
            '**Última actualización de este prompt:** v1.5piloto.73l (fix del',
            'guardado SQL: transacción + chunks para no superar `max_allowed_packet`;',
            '`guardar_ambos` no guarda vacío; `en_grafo_credenciales` y `index.php`',
            'más defensivos).',
        ],
        'reemplazar' => [
            '**Última actualización de este prompt:** v1.5piloto.73m (framework',
            '1.5i.7a: chequeos y escape en SQL, JSON atómico, `Controlador::cargar`',
            'con `bool|null`. Rehash automático de credenciales. Tablas SQL',
            'revisadas con `CHECK`/`OPTIMIZE` en los 3 entornos).',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto Estado de la conversacion: agregar v73m',
        'buscar' => [
            '- Cerramos en v73l el fix del guardado SQL (framework 1.5i.7):',
            '  transacción + chunks. Era la causa raíz de las tablas corruptas',
            '  y del riesgo de que el grafo quede vacío.',
            '- No hay tandas en curso.',
        ],
        'reemplazar' => [
            '- Cerramos en v73l el fix del guardado SQL (framework 1.5i.7):',
            '  transacción + chunks. Era la causa raíz de las tablas corruptas',
            '  y del riesgo de que el grafo quede vacío.',
            '- Cerramos en v73m el fix del framework 1.5i.7a: chequeos y escape',
            '  en `cargar`/`existe`/`eliminar` de SQL, escritura atómica y',
            '  validación en JSON, `Controlador::cargar` distingue "no existe"',
            '  de "error", y rehash automático de credenciales en login exitoso.',
            '- No hay tandas en curso.',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'prompts/prompt_piloto.md',
        'descripcion' => 'Piloto estado al cierre: bump a v73m',
        'buscar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73l (framework 1.5i.7).',
            'Todo funcional. Listo para arrancar la diversificación por tipo de',
            'aplicación.',
        ],
        'reemplazar' => [
            '**Estado del proyecto al cierre:** v1.5piloto.73m (framework 1.5i.7a).',
            'Todo funcional. Listo para arrancar la diversificación por tipo de',
            'aplicación.',
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