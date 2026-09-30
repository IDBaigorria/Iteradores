<?php
/**
 * Funciones de gestión de usuarios.
 *
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.73j
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");

/**
 * Busca un usuario por su código de acceso.
 *
 * @param string $codigo Código de acceso.
 * @return array|null Datos del usuario o null si no existe.
 */
function buscar_usuario_por_codigo(string $codigo): ?array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return null;

    foreach ($raiz_usuarios->adyacentes() as $nombre_usuario => $nodo_usuario) {
        $nodo_codigo_hash = $nodo_usuario->adyacente('codigo_hash');
        if ($nodo_codigo_hash && password_verify($codigo, $nodo_codigo_hash->dato())) {
            return [
                'nombre_usuario' => (string)$nombre_usuario,
            ];
        }
    }

    // Dummy verification: igualar tiempos entre "usuario existe" y "no existe".
    password_verify($codigo, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

    return null;
}

/**
 * Lista todos los usuarios registrados con sus datos.
 *
 * @return array Lista de usuarios.
 */
function listar_usuarios(): array {
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return [];

    // Pre-cargar el mapa de usuarios con código desde credenciales.
    $con_codigo = en_grafo_credenciales(function() {
        $raiz_cred = Nodo::nodo_por_id('usuarios');
        if (!$raiz_cred) return [];
        $mapa = [];
        foreach ($raiz_cred->adyacentes() as $nombre => $nodo) {
            $mapa[(string)$nombre] = $nodo->adyacente('codigo_hash') ? true : false;
        }
        return $mapa;
    });

    $adyacentes = $raiz->adyacentes();
    if (!$adyacentes) return [];

    $usuarios = [];
    foreach ($adyacentes as $nombre_usuario => $nodo_usuario) {
        $nodo_contrasena = $nodo_usuario->adyacente('contrasena');
        $nodo_efectivo = $nodo_usuario->adyacente('efectivo');
        $nodo_banco = $nodo_usuario->adyacente('banco');
        $nodo_nivel = $nodo_usuario->adyacente('nivel');
        $nodo_nombre_real = $nodo_usuario->adyacente('nombre_real');
        $nodo_email = $nodo_usuario->adyacente('email');
        $nodo_pasajes = $nodo_usuario->adyacente('pasajes');

        $banco_nombre = '';
        $banco_cuenta = '';
        $monto_banco = '0';
        if ($nodo_banco) {
            $monto_banco = $nodo_banco->dato();
            $nombre = $nodo_banco->adyacente('nombre');
            $cuenta = $nodo_banco->adyacente('cuenta');
            $banco_nombre = $nombre ? $nombre->dato() : '';
            $banco_cuenta = $cuenta ? $cuenta->dato() : '';
        }

        $usuario = [
            'nombre_usuario' => $nombre_usuario,
            'nombre_real' => $nodo_nombre_real ? $nodo_nombre_real->dato() : '',
            'email' => $nodo_email ? $nodo_email->dato() : '',
            'nivel' => $nodo_nivel ? $nodo_nivel->dato() : 'terminal',
            'efectivo' => $nodo_efectivo ? $nodo_efectivo->dato() : '0',
            'bancarizado' => $monto_banco,
            'codigo_asignado' => !empty($con_codigo[(string)$nombre_usuario]),
            'pasajes' => $nodo_pasajes ? $nodo_pasajes->dato() : '0',
            'banco' => [
                'nombre' => $banco_nombre,
                'cuenta' => $banco_cuenta,
            ],
        ];

        if ($usuario['nivel'] === 'terminal') {
            $nodo_dueno = $nodo_usuario->adyacente('dueno');
            $usuario['dueno'] = $nodo_dueno ? $nodo_dueno->dato() : '';
        }

        if ($usuario['nivel'] === 'dueno') {
            $terminales = [];
            $nodo_terminales = $nodo_usuario->adyacente('terminales');
            if ($nodo_terminales) {
                foreach ($nodo_terminales->adyacentes() as $nombre_terminal => $nodo_terminal) {
                    $terminales[] = $nombre_terminal;
                }
            }
            $usuario['terminales'] = $terminales;
        }

        if ($usuario['nivel'] === 'soporte') {
            $duenos = [];
            $nodo_duenos = $nodo_usuario->adyacente('duenos');
            if ($nodo_duenos) {
                foreach ($nodo_duenos->adyacentes() as $nombre_d => $nodo_d) {
                    $duenos[] = (string)$nombre_d;
                }
            }
            $usuario['duenos'] = $duenos;
        }

        $usuarios[] = $usuario;
    }
    return $usuarios;
}

/**
 * Lista los usuarios con nivel 'dueno'.
 *
 * @return array Lista de dueños con nombre de usuario y nombre real.
 */
function listar_duenos(string $nombre_solicitante = ''): array {
    $todos = listar_usuarios();
    $duenos = [];
    foreach ($todos as $usuario) {
        if ($usuario['nivel'] === 'dueno') {
            $duenos[] = [
                'nombre_usuario' => $usuario['nombre_usuario'],
                'nombre_real' => $usuario['nombre_real'],
            ];
        }
    }

    // Si el solicitante es soporte, filtrar a sus dueños asignados.
    if ($nombre_solicitante !== '' && $nombre_solicitante !== Conf::NOMBRE_ADMIN) {
        $raiz = Nodo::nodo_por_id('usuarios');
        if ($raiz) {
            $nodo_sol = $raiz->adyacente($nombre_solicitante);
            if ($nodo_sol) {
                $nodo_nivel_sol = $nodo_sol->adyacente('nivel');
                $nivel_sol = $nodo_nivel_sol ? $nodo_nivel_sol->dato() : '';
                if ($nivel_sol === 'soporte') {
                    $nodo_duenos_sol = $nodo_sol->adyacente('duenos');
                    $permitidos = [];
                    if ($nodo_duenos_sol) {
                        foreach ($nodo_duenos_sol->adyacentes() as $nombre_d => $nodo_d) {
                            $permitidos[(string)$nombre_d] = true;
                        }
                    }
                    $duenos = array_values(array_filter($duenos, function($d) use ($permitidos) {
                        return isset($permitidos[(string)$d['nombre_usuario']]);
                    }));
                }
            }
        }
    }

    return $duenos;
}

/**
 * Lista todos los usuarios con nivel "soporte".
 *
 * Cada uno con sus dueños asignados.
 *
 * @return array Lista de soportes.
 */
function listar_soportes(): array {
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return [];

    $soportes = [];
    foreach ($raiz->adyacentes() as $nombre_usuario => $nodo_usuario) {
        $nodo_nivel = $nodo_usuario->adyacente('nivel');
        $nivel = $nodo_nivel ? $nodo_nivel->dato() : '';
        if ($nivel !== 'soporte') continue;

        $nodo_nombre_real = $nodo_usuario->adyacente('nombre_real');
        $nodo_email = $nodo_usuario->adyacente('email');

        $duenos = [];
        $nodo_duenos = $nodo_usuario->adyacente('duenos');
        if ($nodo_duenos) {
            foreach ($nodo_duenos->adyacentes() as $nombre_dueno => $nodo_d) {
                $duenos[] = (string)$nombre_dueno;
            }
        }

        $soportes[] = [
            'nombre_usuario' => (string)$nombre_usuario,
            'nombre_real' => $nodo_nombre_real ? $nodo_nombre_real->dato() : '',
            'email' => $nodo_email ? $nodo_email->dato() : '',
            'nivel' => 'soporte',
            'duenos' => $duenos,
        ];
    }
    return $soportes;
}

/**
 * Lista los dueños asignados a un soporte.
 *
 * @param string $nombre_soporte Nombre del soporte.
 * @return array Lista de dueños con nombre_usuario y nombre_real.
 */
function listar_duenos_de_soporte(string $nombre_soporte): array {
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return [];
    $nodo_soporte = $raiz->adyacente($nombre_soporte);
    if (!$nodo_soporte) return [];

    $nodo_nivel = $nodo_soporte->adyacente('nivel');
    $nivel = $nodo_nivel ? $nodo_nivel->dato() : '';
    if ($nivel !== 'soporte') return [];

    $nodo_duenos = $nodo_soporte->adyacente('duenos');
    if (!$nodo_duenos) return [];

    $resultado = [];
    foreach ($nodo_duenos->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nodo_nombre_real = $nodo_dueno->adyacente('nombre_real');
        $resultado[] = [
            'nombre_usuario' => (string)$nombre_dueno,
            'nombre_real' => $nodo_nombre_real ? $nodo_nombre_real->dato() : '',
        ];
    }
    return $resultado;
}

/**
 * Lista los usuarios visibles para un soporte: cada dueño asignado
 * más sus terminales.
 *
 * @param string $nombre_soporte Nombre del soporte.
 * @return array Lista de usuarios.
 */
function listar_usuarios_de_soporte(string $nombre_soporte): array {
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return [];
    $nodo_soporte = $raiz->adyacente($nombre_soporte);
    if (!$nodo_soporte) return [];

    $nodo_duenos = $nodo_soporte->adyacente('duenos');
    if (!$nodo_duenos) return [];

    // Cargar el mapa de usuarios con código (para codigo_asignado).
    $con_codigo = en_grafo_credenciales(function() {
        $raiz_cred = Nodo::nodo_por_id('usuarios');
        if (!$raiz_cred) return [];
        $mapa = [];
        foreach ($raiz_cred->adyacentes() as $nombre => $nodo) {
            $mapa[(string)$nombre] = $nodo->adyacente('codigo_hash') ? true : false;
        }
        return $mapa;
    });

    $usuarios = [];
    foreach ($nodo_duenos->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $usuarios[] = _formatear_usuario_para_admin((string)$nombre_dueno, $nodo_dueno, $con_codigo);

        $nodo_terminales = $nodo_dueno->adyacente('terminales');
        if ($nodo_terminales) {
            foreach ($nodo_terminales->adyacentes() as $nombre_terminal => $nodo_terminal) {
                $usuarios[] = _formatear_usuario_para_admin((string)$nombre_terminal, $nodo_terminal, $con_codigo);
            }
        }
    }
    return $usuarios;
}

/**
 * Devuelve los nombres de usuario que un soporte puede ver en la
 * lista de sesiones: él mismo, sus dueños asignados y las terminales
 * de esos dueños. Si se pasa un dueño específico, filtra a ese dueño
 * y sus terminales (manteniendo siempre al propio soporte en la lista).
 *
 * @param string $nombre_soporte Nombre del soporte.
 * @param string $nombre_dueno_filtro Dueño a filtrar (opcional).
 * @return array Lista de nombres de usuario.
 */
function listar_nombres_usuarios_para_soporte(string $nombre_soporte, string $nombre_dueno_filtro = ''): array {
    $nombres = [];
    if ($nombre_soporte === '') return $nombres;

    // Siempre se incluye a sí mismo.
    $nombres[$nombre_soporte] = true;

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return array_keys($nombres);
    $nodo_soporte = $raiz->adyacente($nombre_soporte);
    if (!$nodo_soporte) return array_keys($nombres);

    $nodo_duenos = $nodo_soporte->adyacente('duenos');
    if (!$nodo_duenos) return array_keys($nombres);

    $filtro = (string)$nombre_dueno_filtro;

    foreach ($nodo_duenos->adyacentes() as $nombre_d => $nodo_d) {
        $nombre_d = (string)$nombre_d;
        if ($filtro !== '' && $nombre_d !== $filtro) continue;
        $nombres[$nombre_d] = true;

        $nodo_terms = $nodo_d->adyacente('terminales');
        if ($nodo_terms) {
            foreach ($nodo_terms->adyacentes() as $nombre_t => $nodo_t) {
                $nombres[(string)$nombre_t] = true;
            }
        }
    }

    return array_keys($nombres);
}

/**
 * Formatea un nodo usuario para el listado del panel admin.
 *
 * @param string $nombre_usuario Nombre de usuario.
 * @param Nodo $nodo_usuario Nodo del usuario.
 * @param array $con_codigo Mapa nombre → tiene_codigo.
 * @return array Datos formateados.
 */
function _formatear_usuario_para_admin(string $nombre_usuario, Nodo $nodo_usuario, array $con_codigo): array {
    $nodo_efectivo = $nodo_usuario->adyacente('efectivo');
    $nodo_banco = $nodo_usuario->adyacente('banco');
    $nodo_nivel = $nodo_usuario->adyacente('nivel');
    $nodo_nombre_real = $nodo_usuario->adyacente('nombre_real');
    $nodo_email = $nodo_usuario->adyacente('email');
    $nodo_pasajes = $nodo_usuario->adyacente('pasajes');

    $banco_nombre = '';
    $banco_cuenta = '';
    $monto_banco = '0';
    if ($nodo_banco) {
        $monto_banco = $nodo_banco->dato();
        $nombre = $nodo_banco->adyacente('nombre');
        $cuenta = $nodo_banco->adyacente('cuenta');
        $banco_nombre = $nombre ? $nombre->dato() : '';
        $banco_cuenta = $cuenta ? $cuenta->dato() : '';
    }

    $usuario = [
        'nombre_usuario' => $nombre_usuario,
        'nombre_real' => $nodo_nombre_real ? $nodo_nombre_real->dato() : '',
        'email' => $nodo_email ? $nodo_email->dato() : '',
        'nivel' => $nodo_nivel ? $nodo_nivel->dato() : 'terminal',
        'efectivo' => $nodo_efectivo ? $nodo_efectivo->dato() : '0',
        'bancarizado' => $monto_banco,
        'codigo_asignado' => !empty($con_codigo[$nombre_usuario]),
        'pasajes' => $nodo_pasajes ? $nodo_pasajes->dato() : '0',
        'banco' => [
            'nombre' => $banco_nombre,
            'cuenta' => $banco_cuenta,
        ],
    ];

    $nivel = $usuario['nivel'];
    if ($nivel === 'terminal') {
        $nodo_dueno = $nodo_usuario->adyacente('dueno');
        $usuario['dueno'] = $nodo_dueno ? $nodo_dueno->dato() : '';
    }
    if ($nivel === 'dueno') {
        $terminales = [];
        $nodo_terminales = $nodo_usuario->adyacente('terminales');
        if ($nodo_terminales) {
            foreach ($nodo_terminales->adyacentes() as $nombre_terminal => $nodo_terminal) {
                $terminales[] = (string)$nombre_terminal;
            }
        }
        $usuario['terminales'] = $terminales;
    }
    if ($nivel === 'soporte') {
        $duenos = [];
        $nodo_duenos = $nodo_usuario->adyacente('duenos');
        if ($nodo_duenos) {
            foreach ($nodo_duenos->adyacentes() as $nombre_d => $nodo_d) {
                $duenos[] = (string)$nombre_d;
            }
        }
        $usuario['duenos'] = $duenos;
    }

    return $usuario;
}

/**
 * Verifica si un usuario solicitante tiene permiso sobre un dueño.
 *
 * Reglas:
 * - Admin: permiso sobre todos.
 * - Soporte: permiso solo sobre los dueños asignados.
 * - Dueño: permiso sobre sí mismo.
 * - Terminal: permiso sobre su propio dueño.
 *
 * @param string $nombre_solicitante Nombre del usuario que solicita.
 * @param string $nombre_dueno Nombre del dueño sobre el que se quiere operar.
 * @return bool
 */
function _verificar_permiso_dueno(string $nombre_solicitante, string $nombre_dueno): bool {
    if ($nombre_solicitante === '') return true;
    if ($nombre_solicitante === $nombre_dueno) return true;
    if ($nombre_solicitante === Conf::NOMBRE_ADMIN) return true;

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return false;
    $nodo_sol = $raiz->adyacente($nombre_solicitante);
    if (!$nodo_sol) return false;

    $nodo_nivel = $nodo_sol->adyacente('nivel');
    $nivel = $nodo_nivel ? $nodo_nivel->dato() : '';

    if ($nivel === 'soporte') {
        $nodo_duenos = $nodo_sol->adyacente('duenos');
        if (!$nodo_duenos) return false;
        return $nodo_duenos->adyacente($nombre_dueno) !== null;
    }

    if ($nivel === 'terminal') {
        $nodo_dueno = $nodo_sol->adyacente('dueno');
        return $nodo_dueno && $nodo_dueno->dato() === $nombre_dueno;
    }

    return false;
}

/**
 * Determina si un solicitante puede cerrar una sesión ajena.
 *
 * Reglas:
 * - Admin: siempre.
 * - Cualquier usuario: su propia sesión.
 * - Soporte: las sesiones de sus dueños asignados y sus terminales.
 * - Dueño: las sesiones de sus propias terminales.
 *
 * @param string $nombre_solicitante Nombre del usuario que solicita.
 * @param string $token Token de la sesión a cerrar.
 * @return bool
 */
function _puede_cerrar_sesion(string $nombre_solicitante, string $token): bool {
    if ($nombre_solicitante === '') return false;
    if ($nombre_solicitante === Conf::NOMBRE_ADMIN) return true;

    // Leer el usuario de la sesión desde credenciales.
    $usuario_de_sesion = en_grafo_credenciales(function() use ($token) {
        $raiz = Nodo::nodo_por_id('sesiones');
        if (!$raiz) return null;
        $nodo_sesion = $raiz->adyacente($token);
        if (!$nodo_sesion) return null;
        $nodo_usuario = $nodo_sesion->adyacente('usuario');
        return $nodo_usuario ? $nodo_usuario->dato() : null;
    });

    if ($usuario_de_sesion === null) return false;

    // Es su propia sesión.
    if ($usuario_de_sesion === $nombre_solicitante) return true;

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return false;
    $nodo_sol = $raiz->adyacente($nombre_solicitante);
    if (!$nodo_sol) return false;
    $nodo_nivel = $nodo_sol->adyacente('nivel');
    $nivel = $nodo_nivel ? $nodo_nivel->dato() : '';

    if ($nivel === 'soporte') {
        $nodo_duenos = $nodo_sol->adyacente('duenos');
        if ($nodo_duenos) {
            if ($nodo_duenos->adyacente($usuario_de_sesion)) return true;
            foreach ($nodo_duenos->adyacentes() as $nombre_d => $nodo_d) {
                $nodo_terms = $nodo_d->adyacente('terminales');
                if ($nodo_terms && $nodo_terms->adyacente($usuario_de_sesion)) {
                    return true;
                }
            }
        }
        return false;
    }

    if ($nivel === 'dueno') {
        $nodo_terms = $nodo_sol->adyacente('terminales');
        if ($nodo_terms && $nodo_terms->adyacente($usuario_de_sesion)) {
            return true;
        }
        return false;
    }

    return false;
}

/**
 * Asigna una lista de dueños a un soporte, manteniendo consistencia
 * bidireccional (soporte→dueno y dueno→soporte).
 *
 * @param Nodo $nodo_soporte Nodo del soporte.
 * @param array $nuevos_duenos Lista de nombres de dueños.
 * @return void
 */
function _asignar_duenos_a_soporte(Nodo $nodo_soporte, array $nuevos_duenos): void {
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return;

    // Asegurar que exista el contenedor.
    $nodo_duenos = $nodo_soporte->adyacente('duenos');
    if (!$nodo_duenos) {
        $nodo_duenos = Nodo::crear_con_dato('');
        $nodo_soporte->_adyacente_en($nodo_duenos, 'duenos');
    }

    // Normalizar la lista de nuevos (solo strings).
    $lista_nuevos = [];
    foreach ($nuevos_duenos as $n) {
        $n = (string)$n;
        if ($n !== '') $lista_nuevos[$n] = true;
    }

    // Quitar los que ya no van.
    $actuales = [];
    foreach ($nodo_duenos->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $actuales[(string)$nombre_dueno] = $nodo_dueno;
    }
    foreach ($actuales as $nombre_dueno => $nodo_dueno) {
        if (!isset($lista_nuevos[$nombre_dueno])) {
            $nodo_dueno->eliminar_adyacente('soporte');
            $nodo_duenos->eliminar_adyacente($nombre_dueno);
        }
    }

    // Agregar los nuevos.
    foreach ($lista_nuevos as $nombre_dueno => $_) {
        if (isset($actuales[$nombre_dueno])) continue;
        $nodo_dueno = $raiz->adyacente($nombre_dueno);
        if (!$nodo_dueno) continue;
        $nodo_nivel_d = $nodo_dueno->adyacente('nivel');
        if (!$nodo_nivel_d || $nodo_nivel_d->dato() !== 'dueno') continue;

        $nodo_duenos->_adyacente_en($nodo_dueno, $nombre_dueno);
        $nodo_dueno->eliminar_adyacente('soporte');
        $nodo_dueno->_adyacente_en($nodo_soporte, 'soporte');
    }
}

/**
 * Devuelve los saldos actuales de un dueño (efectivo, banco, total).
 *
 * Se usa tanto en la pestaña Rendiciones como en Liquidaciones para
 * mostrar el saldo disponible antes de mover dinero.
 *
 * @param string $nombre_dueno
 * @return array{efectivo: string, banco: string, total: string}
 */
function obtener_saldos_dueno(string $nombre_dueno): array {
    $defaults = ['efectivo' => '0.00', 'banco' => '0.00', 'total' => '0.00'];
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return $defaults;
    $nodo = $raiz->adyacente($nombre_dueno);
    if (!$nodo) return $defaults;

    $ef = $nodo->adyacente('efectivo');
    $ba = $nodo->adyacente('banco');
    $efectivo = $ef ? (float)$ef->dato() : 0.0;
    $banco = $ba ? (float)$ba->dato() : 0.0;

    return [
        'efectivo' => number_format($efectivo, 2, '.', ''),
        'banco' => number_format($banco, 2, '.', ''),
        'total' => number_format($efectivo + $banco, 2, '.', ''),
    ];
}

/**
 * Agrega un nuevo usuario a la estructura.
 *
 * @param array $datos Datos del formulario.
 * @return array Resultado de la operación.
 */
function agregar_usuario(array $datos): array {
    $nombre_usuario = trim($datos['nombre_usuario'] ?? '');
    $contrasena = trim($datos['contrasena'] ?? '');
    $nivel = trim($datos['nivel'] ?? 'dueno');
    $efectivo = '0';
    $banco_nombre = trim($datos['banco_nombre'] ?? '');
    $banco_cuenta = trim($datos['banco_cuenta'] ?? '');
    $nombre_real = trim($datos['nombre_real'] ?? '');
    $codigo_acceso = trim($datos['codigo_acceso'] ?? '');
    $dueno = trim($datos['dueno'] ?? '');
    $email = trim($datos['email'] ?? '');

    // Dueños asignados, solo para nivel soporte. Vienen como JSON string.
    $duenos_asignados = [];
    if (isset($datos['duenos_asignados']) && $datos['duenos_asignados'] !== '') {
        $decodificado = json_decode($datos['duenos_asignados'], true);
        if (is_array($decodificado)) $duenos_asignados = $decodificado;
    }

    if (empty($nombre_usuario)) {
        return ['exito' => false, 'error' => 'El nombre de usuario es obligatorio'];
    }

    if ($codigo_acceso === '' && $contrasena === '') {
        return ['exito' => false, 'error' => 'Debe asignar al menos un código de acceso o una contraseña'];
    }

    if ($codigo_acceso !== '') {
        $codigo_duplicado = en_grafo_credenciales(function() use ($codigo_acceso) {
            $encontrado = buscar_usuario_por_codigo($codigo_acceso);
            return $encontrado !== null;
        });
        if ($codigo_duplicado) {
            return ['exito' => false, 'error' => 'El código de acceso ya está en uso'];
        }
    }

    if ($nivel === 'terminal' && empty($dueno)) {
        return ['exito' => false, 'error' => 'Debe especificar el dueño para una terminal'];
    }

    if ($nivel === 'terminal' && (empty($banco_nombre) || empty($banco_cuenta))) {
        return ['exito' => false, 'error' => 'Banco y cuenta son obligatorios para terminales'];
    }

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) {
        if (!Nodo::nodo_por_id('usuarios')) Nodo::crear_con_id('usuarios');
        if (!Nodo::nodo_por_id('sesiones')) Nodo::crear_con_id('sesiones');
        $raiz = Nodo::nodo_por_id('usuarios');
    }

    if ($raiz->adyacente($nombre_usuario)) {
        return ['exito' => false, 'error' => 'El nombre de usuario ya existe'];
    }

    $nodo_usuario = Nodo::crear_con_dato($nombre_usuario);

    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nivel), 'nivel');
    if ($nombre_real !== '') $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nombre_real), 'nombre_real');
    if ($email !== '') $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($email), 'email');

    if ($nivel === 'terminal') {
        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($efectivo), 'efectivo');

        $nodo_banco = Nodo::crear_con_dato('0');
        $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_nombre), 'nombre');
        $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_cuenta), 'cuenta');
        $nodo_usuario->_adyacente_en($nodo_banco, 'banco');

        $nodo_dueno = $raiz->adyacente($dueno);
        if (!$nodo_dueno) return ['exito' => false, 'error' => 'El dueño especificado no existe'];
        $nodo_nivel_dueno = $nodo_dueno->adyacente('nivel');
        if (!$nodo_nivel_dueno || $nodo_nivel_dueno->dato() !== 'dueno') return ['exito' => false, 'error' => 'El usuario indicado no es un dueño'];
        $nodo_usuario->_adyacente_en($nodo_dueno, 'dueno');

        $nodo_terminales = $nodo_dueno->adyacente('terminales');
        if (!$nodo_terminales) {
            $nodo_terminales = Nodo::crear_con_dato('');
            $nodo_dueno->_adyacente_en($nodo_terminales, 'terminales');
        }
        $nodo_terminales->_adyacente_en($nodo_usuario, $nombre_usuario);
    }

    if ($nivel === 'dueno') {
        // Crear cuenta de efectivo. El banco se crea solo si el admin
        // lo cargó al momento del alta; si no, se crea más adelante
        // cuando haga falta (por ejemplo, al cerrar una rendición).
        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato('0'), 'efectivo');
        if ($banco_nombre !== '' && $banco_cuenta !== '') {
            $nodo_banco = Nodo::crear_con_dato('0');
            $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_nombre), 'nombre');
            $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_cuenta), 'cuenta');
            $nodo_usuario->_adyacente_en($nodo_banco, 'banco');
        }
    }

    if ($nivel === 'soporte') {
        // Crear contenedor vacío; los enlaces se llenan después.
        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato(''), 'duenos');
    }

    $raiz->_adyacente_en($nodo_usuario, $nombre_usuario);

    // Si es soporte, asignar dueños.
    if ($nivel === 'soporte' && !empty($duenos_asignados)) {
        _asignar_duenos_a_soporte($nodo_usuario, $duenos_asignados);
    }

    guardar_ambos(Conf::NOMBRE_APP);

    // Escribir credenciales en su grafo aparte.
    en_grafo_credenciales(function() use ($nombre_usuario, $codigo_acceso, $contrasena) {
        $raiz_cred = Nodo::nodo_por_id('usuarios');
        if (!$raiz_cred) {
            Nodo::crear_con_id('usuarios');
            $raiz_cred = Nodo::nodo_por_id('usuarios');
        }
        $nodo_cred = Nodo::crear_con_dato($nombre_usuario);
        if ($codigo_acceso !== '') {
            $nodo_cred->_adyacente_en(Nodo::crear_con_dato(password_hash($codigo_acceso, PASSWORD_DEFAULT)), 'codigo_hash');
        }
        if ($contrasena !== '') {
            $nodo_cred->_adyacente_en(Nodo::crear_con_dato(password_hash($contrasena, PASSWORD_DEFAULT)), 'contrasena');
        }
        $nodo_cred->_adyacente_en(Nodo::crear_con_dato('0'), 'intentos_fallidos');
        $raiz_cred->_adyacente_en($nodo_cred, $nombre_usuario);
    });

    $resultado = ['exito' => true];
    if ($codigo_acceso !== '') {
        $resultado['codigo_asignado'] = $codigo_acceso;
    }
    return $resultado;
}

/**
 * Actualiza los datos de un usuario existente.
 *
 * @param array $datos Datos del formulario.
 * @return array Resultado de la operación.
 */
function actualizar_usuario(array $datos): array {
    $nombre_usuario = trim($datos['nombre_usuario'] ?? '');
    if (empty($nombre_usuario)) {
        return ['exito' => false, 'error' => 'Nombre de usuario no proporcionado'];
    }

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    $nodo_usuario = $raiz->adyacente($nombre_usuario);
    if (!$nodo_usuario) return ['exito' => false, 'error' => 'Usuario no encontrado'];

    $nivel_actual = $nodo_usuario->adyacente('nivel');
    $nivel_actual = $nivel_actual ? $nivel_actual->dato() : 'terminal';

    $nivel = trim($datos['nivel'] ?? $nivel_actual);
    $nombre_real = trim($datos['nombre_real'] ?? '');
    $email = trim($datos['email'] ?? '');
    $codigo_acceso = trim($datos['codigo_acceso'] ?? '');
    $contrasena = trim($datos['contrasena'] ?? '');

    // El nivel no se puede cambiar entre "soporte" y otros. El rol
    // se fija en la creación.
    if (($nivel_actual === 'soporte') !== ($nivel === 'soporte')) {
        return ['exito' => false, 'error' => 'No se puede cambiar el nivel desde o hacia soporte'];
    }

    // Dueños asignados, solo para nivel soporte.
    $duenos_asignados = null;
    if ($nivel === 'soporte' && isset($datos['duenos_asignados'])) {
        $decodificado = json_decode($datos['duenos_asignados'], true);
        $duenos_asignados = is_array($decodificado) ? $decodificado : [];
    }

    if (!empty($codigo_acceso)) {
        $codigo_duplicado = en_grafo_credenciales(function() use ($codigo_acceso, $nombre_usuario) {
            $existente = buscar_usuario_por_codigo($codigo_acceso);
            if ($existente === null) return false;
            return $existente['nombre_usuario'] !== $nombre_usuario;
        });
        if ($codigo_duplicado) {
            return ['exito' => false, 'error' => 'El código de acceso ya está en uso por otro usuario'];
        }
    }

    if ($nombre_real !== '') {
        $nodo_nombre_real = $nodo_usuario->adyacente('nombre_real');
        if ($nodo_nombre_real) $nodo_nombre_real->_dato($nombre_real);
        else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nombre_real), 'nombre_real');
    }
    if ($email !== '') {
        $nodo_email = $nodo_usuario->adyacente('email');
        if ($nodo_email) $nodo_email->_dato($email);
        else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($email), 'email');
    }
    $nodo_nivel = $nodo_usuario->adyacente('nivel');
    if ($nodo_nivel) $nodo_nivel->_dato($nivel);
    else $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nivel), 'nivel');

    if ($nivel_actual !== $nivel) {
        if ($nivel === 'admin') {
            $nodo_usuario->eliminar_adyacente('efectivo');
            $nodo_usuario->eliminar_adyacente('banco');
            $nodo_usuario->eliminar_adyacente('dueno');
        }
        if ($nivel === 'dueno') {
            $nodo_usuario->eliminar_adyacente('dueno');
        }
        if ($nivel_actual === 'dueno' && $nivel !== 'dueno') {
            $nodo_usuario->eliminar_adyacente('terminales');
        }
    }

    // Asegurar que el dueño tenga cuenta de efectivo.
    if ($nivel === 'dueno' && !$nodo_usuario->adyacente('efectivo')) {
        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato('0'), 'efectivo');
    }

    // Si es soporte y se pasaron dueños asignados, actualizarlos.
    if ($nivel === 'soporte' && $duenos_asignados !== null) {
        _asignar_duenos_a_soporte($nodo_usuario, $duenos_asignados);
    }

    if ($nivel === 'terminal' || $nivel === 'dueno') {
        $banco_nombre = trim($datos['banco_nombre'] ?? '');
        $banco_cuenta = trim($datos['banco_cuenta'] ?? '');

        if ($nivel === 'terminal') {
            if (empty($banco_nombre) || empty($banco_cuenta)) {
                return ['exito' => false, 'error' => 'Banco y cuenta son obligatorios para terminales'];
            }
        }

        $nodo_banco = $nodo_usuario->adyacente('banco');
        if (!$nodo_banco) {
            $nodo_banco = Nodo::crear_con_dato('0');
            $nodo_usuario->_adyacente_en($nodo_banco, 'banco');
        }

        if ($banco_nombre !== '') {
            $nodo_banco_nombre = $nodo_banco->adyacente('nombre');
            if ($nodo_banco_nombre) $nodo_banco_nombre->_dato($banco_nombre);
            else $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_nombre), 'nombre');
        } else if ($nivel === 'dueno') {
            $nodo_banco->eliminar_adyacente('nombre');
        }

        if ($banco_cuenta !== '') {
            $nodo_banco_cuenta = $nodo_banco->adyacente('cuenta');
            if ($nodo_banco_cuenta) $nodo_banco_cuenta->_dato($banco_cuenta);
            else $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_cuenta), 'cuenta');
        } else if ($nivel === 'dueno') {
            $nodo_banco->eliminar_adyacente('cuenta');
        }

        if ($nivel === 'terminal') {
            $dueno = trim($datos['dueno'] ?? '');
            if (empty($dueno)) return ['exito' => false, 'error' => 'Debe seleccionar un dueño'];
            $nodo_dueno = $raiz->adyacente($dueno);
            if (!$nodo_dueno) return ['exito' => false, 'error' => 'Dueño no existe'];
            $nodo_nivel_dueno = $nodo_dueno->adyacente('nivel');
            if (!$nodo_nivel_dueno || $nodo_nivel_dueno->dato() !== 'dueno') return ['exito' => false, 'error' => 'El usuario indicado no es un dueño'];

            $nodo_usuario->eliminar_adyacente('dueno');
            $nodo_usuario->_adyacente_en($nodo_dueno, 'dueno');
        }
    }

    guardar_ambos(Conf::NOMBRE_APP);

    if ($codigo_acceso !== '' || $contrasena !== '') {
        en_grafo_credenciales(function() use ($nombre_usuario, $codigo_acceso, $contrasena) {
            $raiz_cred = Nodo::nodo_por_id('usuarios');
            if (!$raiz_cred) {
                Nodo::crear_con_id('usuarios');
                $raiz_cred = Nodo::nodo_por_id('usuarios');
            }
            $nodo_cred = $raiz_cred->adyacente($nombre_usuario);
            if (!$nodo_cred) {
                $nodo_cred = Nodo::crear_con_dato($nombre_usuario);
                $raiz_cred->_adyacente_en($nodo_cred, $nombre_usuario);
            }
            if ($codigo_acceso !== '') {
                $hash_nuevo = password_hash($codigo_acceso, PASSWORD_DEFAULT);
                $nodo_hash = $nodo_cred->adyacente('codigo_hash');
                if ($nodo_hash) $nodo_hash->_dato($hash_nuevo);
                else $nodo_cred->_adyacente_en(Nodo::crear_con_dato($hash_nuevo), 'codigo_hash');
            }
            if ($contrasena !== '') {
                $hash = password_hash($contrasena, PASSWORD_DEFAULT);
                $nodo_pass = $nodo_cred->adyacente('contrasena');
                if ($nodo_pass) $nodo_pass->_dato($hash);
                else $nodo_cred->_adyacente_en(Nodo::crear_con_dato($hash), 'contrasena');
            }
            // Al cambiar credenciales, resetear el estado de bloqueo.
            $nodo_intentos = $nodo_cred->adyacente('intentos_fallidos');
            if ($nodo_intentos) $nodo_intentos->_dato('0');
            else $nodo_cred->_adyacente_en(Nodo::crear_con_dato('0'), 'intentos_fallidos');
            $nodo_cred->eliminar_adyacente('bloqueado_hasta');
        });
    }

    $resultado = ['exito' => true];
    if ($codigo_acceso !== '') {
        $resultado['codigo_asignado'] = $codigo_acceso;
    }
    return $resultado;
}

/**
 * Elimina un usuario existente.
 *
 * @param string $nombre_usuario Nombre del usuario a eliminar.
 * @return array Resultado de la operación.
 */
function eliminar_usuario(string $nombre_usuario): array {
    if (empty($nombre_usuario)) {
        return ['exito' => false, 'error' => 'Nombre de usuario no proporcionado'];
    }

    if ($nombre_usuario === Conf::NOMBRE_ADMIN) {
        return ['exito' => false, 'error' => 'No se puede eliminar al administrador principal'];
    }

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    $nodo_usuario = $raiz->adyacente($nombre_usuario);
    if (!$nodo_usuario) return ['exito' => false, 'error' => 'Usuario no encontrado'];

    $nodo_terminales = $nodo_usuario->adyacente('terminales');
    if ($nodo_terminales && $nodo_terminales->adyacentes()) {
        return ['exito' => false, 'error' => 'No se puede eliminar un dueño con terminales asociadas'];
    }

    $nodo_dueno = $nodo_usuario->adyacente('dueno');
    if ($nodo_dueno) {
        $dueno_nombre = $nodo_dueno->dato();
        $nodo_dueno_real = $raiz->adyacente($dueno_nombre);
        if ($nodo_dueno_real) {
            $nodo_contenedor_terminales = $nodo_dueno_real->adyacente('terminales');
            if ($nodo_contenedor_terminales) {
                $nodo_contenedor_terminales->eliminar_adyacente($nombre_usuario);
            }
        }
    }

    // Si es soporte, limpiar los enlaces `soporte` en sus dueños.
    $nodo_nivel_actual = $nodo_usuario->adyacente('nivel');
    $nivel_actual_del = $nodo_nivel_actual ? $nodo_nivel_actual->dato() : '';
    if ($nivel_actual_del === 'soporte') {
        $nodo_duenos_del = $nodo_usuario->adyacente('duenos');
        if ($nodo_duenos_del) {
            foreach ($nodo_duenos_del->adyacentes() as $nombre_d => $nodo_d) {
                $nodo_d->eliminar_adyacente('soporte');
            }
        }
    }

    // Si es dueño, limpiar la referencia en su soporte.
    if ($nivel_actual_del === 'dueno') {
        $nodo_soporte_del = $nodo_usuario->adyacente('soporte');
        if ($nodo_soporte_del) {
            $nombre_soporte_del = $nodo_soporte_del->dato();
            $nodo_soporte_real = $raiz->adyacente($nombre_soporte_del);
            if ($nodo_soporte_real) {
                $nodo_duenos_sop = $nodo_soporte_real->adyacente('duenos');
                if ($nodo_duenos_sop) {
                    $nodo_duenos_sop->eliminar_adyacente($nombre_usuario);
                }
            }
        }
    }

    $raiz->eliminar_adyacente($nombre_usuario);
    Nodo::eliminar($nodo_usuario);
    guardar_ambos(Conf::NOMBRE_APP);

    // Eliminar también en credenciales.
    en_grafo_credenciales(function() use ($nombre_usuario) {
        $raiz_cred = Nodo::nodo_por_id('usuarios');
        if (!$raiz_cred) return;
        $nodo_cred = $raiz_cred->adyacente($nombre_usuario);
        if ($nodo_cred) {
            $raiz_cred->eliminar_adyacente($nombre_usuario);
            Nodo::eliminar($nodo_cred);
        }
    });

    return ['exito' => true];
}

/**
 * Lista las terminales asociadas a un dueño específico.
 *
 * @param string $nombre_dueno Nombre del usuario dueño.
 * @return array Lista de terminales con sus datos.
 */
function listar_terminales_de_dueno(string $nombre_dueno): array {
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return [];

    $nodo_dueno = $raiz->adyacente($nombre_dueno);
    if (!$nodo_dueno) return [];

    $nodo_nivel = $nodo_dueno->adyacente('nivel');
    if (!$nodo_nivel || $nodo_nivel->dato() !== 'dueno') return [];

    $nodo_terminales = $nodo_dueno->adyacente('terminales');
    if (!$nodo_terminales) return [];

    $adyacentes = $nodo_terminales->adyacentes();
    if (!$adyacentes) return [];

    // Pre-cargar el mapa de usuarios con código desde credenciales.
    $con_codigo = en_grafo_credenciales(function() {
        $raiz_cred = Nodo::nodo_por_id('usuarios');
        if (!$raiz_cred) return [];
        $mapa = [];
        foreach ($raiz_cred->adyacentes() as $nombre => $nodo) {
            $mapa[(string)$nombre] = $nodo->adyacente('codigo_hash') ? true : false;
        }
        return $mapa;
    });

    $terminales = [];
    foreach ($adyacentes as $nombre_terminal => $nodo_terminal) {
        $nodo_contrasena = $nodo_terminal->adyacente('contrasena');
        $nodo_efectivo = $nodo_terminal->adyacente('efectivo');
        $nodo_banco = $nodo_terminal->adyacente('banco');
        $nodo_nivel_terminal = $nodo_terminal->adyacente('nivel');
        $nodo_nombre_real = $nodo_terminal->adyacente('nombre_real');
        $nodo_email = $nodo_terminal->adyacente('email');
        $nodo_pasajes = $nodo_terminal->adyacente('pasajes');

        $banco_nombre = '';
        $banco_cuenta = '';
        $monto_banco = '0';
        if ($nodo_banco) {
            $monto_banco = $nodo_banco->dato();
            $nombre = $nodo_banco->adyacente('nombre');
            $cuenta = $nodo_banco->adyacente('cuenta');
            $banco_nombre = $nombre ? $nombre->dato() : '';
            $banco_cuenta = $cuenta ? $cuenta->dato() : '';
        }

        $pasajes_vendidos = $nodo_pasajes ? $nodo_pasajes->dato() : '0';

        $terminales[] = [
            'nombre_usuario' => $nombre_terminal,
            'nombre_real' => $nodo_nombre_real ? $nodo_nombre_real->dato() : '',
            'email' => $nodo_email ? $nodo_email->dato() : '',
            'nivel' => $nodo_nivel_terminal ? $nodo_nivel_terminal->dato() : 'terminal',
            'codigo_asignado' => !empty($con_codigo[(string)$nombre_terminal]),
            'efectivo' => $nodo_efectivo ? $nodo_efectivo->dato() : '0',
            'bancarizado' => $monto_banco,
            'banco' => [
                'nombre' => $banco_nombre,
                'cuenta' => $banco_cuenta,
            ],
            'dueno' => $nombre_dueno,
            'pasajes' => $pasajes_vendidos,
        ];
    }
    return $terminales;
}

/**
 * Actualiza una terminal perteneciente al dueño actual.
 *
 * @param array $datos Datos del formulario.
 * @param string $nombre_dueno_actual Nombre del dueño que realiza la operación.
 * @return array Resultado.
 */
function actualizar_terminal(array $datos, string $nombre_dueno_actual): array {
    $nombre_usuario = trim($datos['nombre_usuario'] ?? '');
    if (empty($nombre_usuario)) {
        return ['exito' => false, 'error' => 'Nombre de usuario no proporcionado'];
    }

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    $nodo_terminal = $raiz->adyacente($nombre_usuario);
    if (!$nodo_terminal) return ['exito' => false, 'error' => 'Terminal no encontrada'];

    $nodo_dueno = $nodo_terminal->adyacente('dueno');
    if (!$nodo_dueno || $nodo_dueno->dato() !== $nombre_dueno_actual) {
        return ['exito' => false, 'error' => 'No tiene permiso para modificar esta terminal'];
    }

    // Forzar nivel terminal y dueño
    $datos['nivel'] = 'terminal';
    $datos['dueno'] = $nombre_dueno_actual;

    return actualizar_usuario($datos);
}

/**
 * Elimina una terminal perteneciente al dueño actual.
 *
 * @param string $nombre_usuario Nombre de la terminal.
 * @param string $nombre_dueno_actual Nombre del dueño que realiza la operación.
 * @return array Resultado.
 */
function eliminar_terminal(string $nombre_usuario, string $nombre_dueno_actual): array {
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    $nodo_terminal = $raiz->adyacente($nombre_usuario);
    if (!$nodo_terminal) return ['exito' => false, 'error' => 'Terminal no encontrada'];

    $nodo_dueno = $nodo_terminal->adyacente('dueno');
    if (!$nodo_dueno || $nodo_dueno->dato() !== $nombre_dueno_actual) {
        return ['exito' => false, 'error' => 'No tiene permiso para eliminar esta terminal'];
    }

    return eliminar_usuario($nombre_usuario);
}

/**
 * Devuelve los datos del propio usuario (perfil).
 *
 * Reutiliza `_formatear_usuario_para_admin` para no duplicar la
 * lógica de armado. Solo lee, no modifica nada. Sirve para que
 * cualquier rol vea sus propios datos sin necesitar permisos
 * de administrador.
 *
 * @param string $nombre_usuario Nombre del usuario.
 * @return array|null Datos del perfil, o null si no existe.
 */
function obtener_perfil_usuario(string $nombre_usuario): ?array {
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return null;
    $nodo_usuario = $raiz->adyacente($nombre_usuario);
    if (!$nodo_usuario) return null;

    // Mapa de usuarios con código (desde credenciales).
    $con_codigo = en_grafo_credenciales(function() {
        $raiz_cred = Nodo::nodo_por_id('usuarios');
        if (!$raiz_cred) return [];
        $mapa = [];
        foreach ($raiz_cred->adyacentes() as $nombre => $nodo) {
            $mapa[(string)$nombre] = $nodo->adyacente('codigo_hash') ? true : false;
        }
        return $mapa;
    });

    return _formatear_usuario_para_admin($nombre_usuario, $nodo_usuario, $con_codigo);
}