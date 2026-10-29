<?php
/**
 * Funciones de gestión de usuarios.
 *
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.83n
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./Aplicacion/FuncionesAuxiliares.php");
include_once("./miscelaneas/Arbol.php");

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
    $con_codigo = en_grafo_credenciales_solo_lectura(function() {
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
    // Fase 1c.2: si el solicitante es soporte, navegar directo por
    // `us_<soporte> → duenos`. Con carga parcial, `usuarios` no
    // está en memoria (solo el admin tiene el enlace) y
    // `listar_usuarios` devolvería vacío.
    if ($nombre_solicitante !== '' && $nombre_solicitante !== ConfiguracionApli::NOMBRE_ADMIN) {
        $nodo_sol = Nodo::nodo_por_id('us_' . $nombre_solicitante);
        if ($nodo_sol) {
            $nodo_nivel_sol = $nodo_sol->adyacente('nivel');
            $nivel_sol = $nodo_nivel_sol ? $nodo_nivel_sol->dato() : '';
            if ($nivel_sol === 'soporte') {
                $nodo_duenos_sol = $nodo_sol->adyacente('duenos');
                $duenos = [];
                if ($nodo_duenos_sol) {
                    foreach ($nodo_duenos_sol->adyacentes() as $nombre_d => $nodo_d) {
                        $nodo_nombre_real = $nodo_d->adyacente('nombre_real');
                        $duenos[] = [
                            'nombre_usuario' => (string)$nombre_d,
                            'nombre_real' => $nodo_nombre_real ? $nodo_nombre_real->dato() : '',
                        ];
                    }
                }
                return $duenos;
            }
        }
    }

    // Admin (o cualquier otro caso): listar todos los dueños.
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
    // Fase B3.1: ID especial directo.
    $nodo_soporte = Nodo::nodo_por_id('us_' . $nombre_soporte);
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
    // Fase B3.1: ID especial directo.
    $nodo_soporte = Nodo::nodo_por_id('us_' . $nombre_soporte);
    if (!$nodo_soporte) return [];

    $nodo_duenos = $nodo_soporte->adyacente('duenos');
    if (!$nodo_duenos) return [];

    // Cargar el mapa de usuarios con código (para codigo_asignado).
    $con_codigo = en_grafo_credenciales_solo_lectura(function() {
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

    // Fase B3.1: ID especial directo.
    $nodo_soporte = Nodo::nodo_por_id('us_' . $nombre_soporte);
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
    if ($nombre_solicitante === ConfiguracionApli::NOMBRE_ADMIN) return true;

    // Fase B3.1: ID especial directo.
    $nodo_sol = Nodo::nodo_por_id('us_' . $nombre_solicitante);
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
    if ($nombre_solicitante === ConfiguracionApli::NOMBRE_ADMIN) return true;

    // Leer el usuario de la sesión desde credenciales.
    $usuario_de_sesion = en_grafo_credenciales_solo_lectura(function() use ($token) {
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

    // Fase B3.1: ID especial directo.
    $nodo_sol = Nodo::nodo_por_id('us_' . $nombre_solicitante);
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
 * bidireccional (soporte→dueno y dueno→soportes).
 *
 * v1.5piloto.81l (rediseño): el dueño ahora tiene un contenedor
 * `soportes → publico_<soporte1>, publico_<soporte2>, ...` en
 * lugar del enlace singular `soporte → publico_<soporte>`.
 * Permite varios soportes por dueño y elimina el fallback al
 * nodo real del soporte (causa de la fuga cruzada entre dueños).
 *
 * Idempotente:
 *   - Si el dueño ya estaba, se asegura el enlace inverso.
 *   - Si el dueño tenía el enlace singular `soporte` legacy,
 *     se elimina.
 *
 * @param Nodo $nodo_soporte Nodo del soporte.
 * @param array $nuevos_duenos Lista de nombres de dueños.
 * @return void
 */
function _asignar_duenos_a_soporte(Nodo $nodo_soporte, array $nuevos_duenos): void {
    // Asegurar el contenedor `duenos` del soporte.
    $nodo_duenos = $nodo_soporte->adyacente('duenos');
    if (!$nodo_duenos) {
        $nodo_duenos = Nodo::crear_con_dato('');
        $nodo_soporte->_adyacente_en($nodo_duenos, 'duenos');
    }

    // Asegurar el `publico` del soporte (lo usa el dueño para
    // exponer solo datos públicos). Normalmente ya existe si el
    // llamador siguió el orden correcto (`_asegurar_niveles_usuario`
    // antes), pero por las dudas se crea si falta.
    $publico_soporte = $nodo_soporte->adyacente('publico');
    if (!$publico_soporte) {
        $nombre_soporte = (string)$nodo_soporte->dato();
        $publico_soporte = Nodo::crear_con_dato($nombre_soporte);
        $nodo_soporte->_adyacente_en($publico_soporte, 'publico');
        foreach (['nivel', 'nombre_real', 'email'] as $campo) {
            $nodo_campo = $nodo_soporte->adyacente($campo);
            if ($nodo_campo) $publico_soporte->_adyacente_en($nodo_campo, $campo);
        }
    }
    $nombre_soporte = (string)$nodo_soporte->dato();

    // Normalizar la lista de nuevos (solo strings).
    $lista_nuevos = [];
    foreach ($nuevos_duenos as $n) {
        $n = (string)$n;
        if ($n !== '') $lista_nuevos[$n] = true;
    }

    // Recolectar el estado actual.
    $actuales = [];
    foreach ($nodo_duenos->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $actuales[(string)$nombre_dueno] = $nodo_dueno;
    }

    // Quitar los que ya no van.
    foreach ($actuales as $nombre_dueno => $nodo_dueno) {
        if (!isset($lista_nuevos[$nombre_dueno])) {
            $nodo_duenos->eliminar_adyacente($nombre_dueno);
            _quitar_soporte_del_dueno($nodo_dueno, $nombre_soporte);
        }
    }

    // Agregar los nuevos y asegurar el enlace inverso en los
    // que ya estaban (idempotente: cubre el caso de re-guardar
    // sin cambios, migra el enlace `soporte` legacy al
    // contenedor `soportes` si es la primera vez que corre
    // después del rediseño).
    foreach ($lista_nuevos as $nombre_dueno => $_) {
        $nodo_dueno = isset($actuales[$nombre_dueno])
            ? $actuales[$nombre_dueno]
            : Nodo::nodo_por_id('us_' . $nombre_dueno);
        if (!$nodo_dueno) continue;

        $nodo_nivel_d = $nodo_dueno->adyacente('nivel');
        if (!$nodo_nivel_d || $nodo_nivel_d->dato() !== 'dueno') continue;

        // Insertar en `duenos` del soporte (si no estaba).
        if (!isset($actuales[$nombre_dueno])) {
            $nodo_duenos->_adyacente_en($nodo_dueno, $nombre_dueno);
        }

        // Asegurar el enlace inverso en el dueño.
        _asegurar_soporte_en_dueno($nodo_dueno, $publico_soporte, $nombre_soporte);
    }
}

/**
 * Asegura que el dueño tenga el `publico` del soporte en su
 * contenedor `soportes`. Elimina el enlace `soporte` singular
 * si quedó del diseño viejo.
 *
 * v1.5piloto.81l.
 *
 * @param Nodo   $nodo_dueno
 * @param Nodo   $publico_soporte
 * @param string $nombre_soporte
 * @return void
 */
function _asegurar_soporte_en_dueno(Nodo $nodo_dueno, Nodo $publico_soporte, string $nombre_soporte): void {
    // Eliminar el enlace `soporte` singular si quedó del diseño viejo.
    if ($nodo_dueno->adyacente('soporte')) {
        $nodo_dueno->eliminar_adyacente('soporte');
    }

    $contenedor = $nodo_dueno->adyacente('soportes');
    if (!$contenedor) {
        $contenedor = Nodo::crear_con_dato('');
        $nodo_dueno->_adyacente_en($contenedor, 'soportes');
    }
    if (!$contenedor->adyacente($nombre_soporte)) {
        $contenedor->_adyacente_en($publico_soporte, $nombre_soporte);
    }
}

/**
 * Quita el `publico` de un soporte del contenedor `soportes`
 * del dueño. Si el contenedor queda vacío, lo destruye.
 * Contempla también el enlace `soporte` singular legacy.
 *
 * v1.5piloto.81l.
 *
 * @param Nodo   $nodo_dueno
 * @param string $nombre_soporte
 * @return void
 */
function _quitar_soporte_del_dueno(Nodo $nodo_dueno, string $nombre_soporte): void {
    // Limpiar enlace `soporte` singular legacy si apuntaba a este soporte.
    $nodo_soporte_legacy = $nodo_dueno->adyacente('soporte');
    if ($nodo_soporte_legacy && (string)$nodo_soporte_legacy->dato() === $nombre_soporte) {
        $nodo_dueno->eliminar_adyacente('soporte');
    }

    // Contenedor `soportes` plural.
    $contenedor = $nodo_dueno->adyacente('soportes');
    if (!$contenedor) return;
    $contenedor->eliminar_adyacente($nombre_soporte);
    if (empty($contenedor->adyacentes())) {
        $nodo_dueno->eliminar_adyacente('soportes');
        _destruir_campos_simples($contenedor);
        Nodo::eliminar($contenedor);
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
    // Fase B3.1: ID especial directo.
    $nodo = Nodo::nodo_por_id('us_' . $nombre_dueno);
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
        $codigo_duplicado = en_grafo_credenciales_solo_lectura(function() use ($codigo_acceso) {
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

    // Fase B3.1: la raíz `usuarios` la crea `index.php` al
    // arrancar. Acá no la autocreamos. El chequeo de duplicado
    // se hace por ID especial, sin depender de la raíz.
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) {
        return ['exito' => false, 'error' => 'No existe la raíz `usuarios`.'];
    }

    $id_especial = 'us_' . $nombre_usuario;
    if (Nodo::existe($id_especial)) {
        return ['exito' => false, 'error' => 'El nombre de usuario ya existe'];
    }
    $nodo_usuario = Nodo::crear_con_dato_e_id($nombre_usuario, $id_especial);
    if (!$nodo_usuario) {
        return ['exito' => false, 'error' => 'No se pudo crear el nodo del usuario'];
    }

    $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nivel), 'nivel');
    if ($nombre_real !== '') $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($nombre_real), 'nombre_real');
    if ($email !== '') $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($email), 'email');

    if ($nivel === 'terminal') {
        $nodo_usuario->_adyacente_en(Nodo::crear_con_dato($efectivo), 'efectivo');

        $nodo_banco = Nodo::crear_con_dato('0');
        $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_nombre), 'nombre');
        $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_cuenta), 'cuenta');
        $nodo_usuario->_adyacente_en($nodo_banco, 'banco');

        // Fase B3.1: ID especial directo.
        $nodo_dueno = Nodo::nodo_por_id('us_' . $dueno);
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

        // Fase B2.3.5c.2: asegurar el compartido del nuevo terminal
        // en el dueño y repuntar el enlace `dueno`. Sin esto, un
        // terminal creado después del repuntado queda apuntando
        // al nodo del dueño real y rompe el aislamiento topológico
        // en silencio.
        _asegurar_compartido_y_repuntar($nodo_dueno, $nodo_usuario, $dueno, $nombre_usuario);
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

    // Fase B1: asegurar los contenedores `publico` y
    // `privado`. Mismo cierre de agujero que v77l hizo
    // para compartidos: sin esto, un usuario creado
    // después de la migración B1 queda sin contenedores,
    // y la detección de la migración da `false`
    // permanentemente.
    //
    // v1.5piloto.81l: se llama ANTES de asignar dueños a
    // un soporte. `_asignar_duenos_a_soporte` necesita el
    // `publico` del soporte para enlazarlo en el contenedor
    // `soportes` del dueño. Antes se llamaba después y el
    // enlace `dueno → soporte` caía al fallback (nodo real
    // del soporte), causando la fuga cruzada entre dueños.
    _asegurar_niveles_usuario($nodo_usuario);

    // Si es soporte, asignar dueños.
    if ($nivel === 'soporte' && !empty($duenos_asignados)) {
        _asignar_duenos_a_soporte($nodo_usuario, $duenos_asignados);
    }

    // Fix 1.5piloto.83n: registrar si el guardado falla. NO se hace
    // return temprano porque este flujo continúa con el guardado de
    // credenciales, y un return acá dejaría el usuario sin credenciales.
    // El return temprano correcto requiere revisar el formato exacto
    // de la respuesta; queda como deuda técnica en plan_actual.md.
    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    if (!$ok_guardar) {
        log_persistencia("agregar_usuario: guardar_ambos falló", 'error');
        Controlador::_error("agregar_usuario: guardar_ambos falló");
    }

    // Escribir credenciales en su grafo aparte.
    en_grafo_credenciales(function() use ($nombre_usuario, $codigo_acceso, $contrasena) {
        $raiz_cred = Nodo::nodo_por_id('usuarios');
        if (!$raiz_cred) {
            Nodo::crear_con_id('usuarios');
            $raiz_cred = Nodo::nodo_por_id('usuarios');
        }
        // Fase A (v76k): el nodo credencial también con ID
        // especial us_<nombre>. El enlace desde `usuarios`
        // (en credenciales) sigue llamándose <nombre>.
        $id_especial_cred = 'us_' . $nombre_usuario;
        $nodo_cred = Nodo::crear_con_dato_e_id($nombre_usuario, $id_especial_cred);
        if (!$nodo_cred) return;
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

    // Fase B3.1: ID especial directo.
    $nodo_usuario = Nodo::nodo_por_id('us_' . $nombre_usuario);
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
        $codigo_duplicado = en_grafo_credenciales_solo_lectura(function() use ($codigo_acceso, $nombre_usuario) {
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
            // Fase 2, v75: destruir efectivo (hoja) y banco
            // (contenedor con nombre/cuenta) en lugar de solo
            // desenlazarlos. `dueno` es una referencia externa,
            // solo se desenlaza.
            $nodo_ef = $nodo_usuario->adyacente('efectivo');
            if ($nodo_ef) {
                $nodo_usuario->eliminar_adyacente('efectivo');
                Nodo::eliminar($nodo_ef);
            }
            $nodo_banco = $nodo_usuario->adyacente('banco');
            if ($nodo_banco) {
                $nodo_usuario->eliminar_adyacente('banco');
                _destruir_banco_usuario($nodo_banco);
            }
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

    // v1.5piloto.81l: asegurar `publico`/`privado` (idempotente)
    // antes de tocar los enlaces de soporte. `_asignar_duenos_a_soporte`
    // necesita el `publico` del soporte para el contenedor `soportes`
    // del dueño.
    _asegurar_niveles_usuario($nodo_usuario);

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
            // Fase 2, v75: destruir la hoja `nombre` en lugar
            // de solo desenlazarla.
            $nodo_banco_nombre = $nodo_banco->adyacente('nombre');
            if ($nodo_banco_nombre) {
                $nodo_banco->eliminar_adyacente('nombre');
                Nodo::eliminar($nodo_banco_nombre);
            }
        }

        if ($banco_cuenta !== '') {
            $nodo_banco_cuenta = $nodo_banco->adyacente('cuenta');
            if ($nodo_banco_cuenta) $nodo_banco_cuenta->_dato($banco_cuenta);
            else $nodo_banco->_adyacente_en(Nodo::crear_con_dato($banco_cuenta), 'cuenta');
        } else if ($nivel === 'dueno') {
            // Fase 2, v75: destruir la hoja `cuenta`.
            $nodo_banco_cuenta = $nodo_banco->adyacente('cuenta');
            if ($nodo_banco_cuenta) {
                $nodo_banco->eliminar_adyacente('cuenta');
                Nodo::eliminar($nodo_banco_cuenta);
            }
        }

        if ($nivel === 'terminal') {
            $dueno = trim($datos['dueno'] ?? '');
            if (empty($dueno)) return ['exito' => false, 'error' => 'Debe seleccionar un dueño'];
            // Fase B3.1: ID especial directo.
            $nodo_dueno = Nodo::nodo_por_id('us_' . $dueno);
            if (!$nodo_dueno) return ['exito' => false, 'error' => 'Dueño no existe'];
            $nodo_nivel_dueno = $nodo_dueno->adyacente('nivel');
            if (!$nodo_nivel_dueno || $nodo_nivel_dueno->dato() !== 'dueno') return ['exito' => false, 'error' => 'El usuario indicado no es un dueño'];

            $nodo_usuario->eliminar_adyacente('dueno');
            $nodo_usuario->_adyacente_en($nodo_dueno, 'dueno');

            // Fase B2.3.5c.2: si el dueño cambió, asegurar el
            // compartido del terminal en el dueño nuevo y
            // repuntar el enlace. El compartido del dueño viejo
            // queda huérfano hasta la próxima limpieza de
            // huérfanos (grafo:eliminar_huerfanos).
            _asegurar_compartido_y_repuntar($nodo_dueno, $nodo_usuario, $dueno, $nombre_usuario);
        }
    }

    // Fix 1.5piloto.83n: registrar si el guardado falla. NO se hace
    // return temprano porque este flujo continúa con el guardado de
    // credenciales. Ver nota en agregar_usuario.
    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    if (!$ok_guardar) {
        log_persistencia("actualizar_usuario: guardar_ambos falló", 'error');
        Controlador::_error("actualizar_usuario: guardar_ambos falló");
    }

    if ($codigo_acceso !== '' || $contrasena !== '') {
        en_grafo_credenciales(function() use ($nombre_usuario, $codigo_acceso, $contrasena) {
            $raiz_cred = Nodo::nodo_por_id('usuarios');
            if (!$raiz_cred) {
                Nodo::crear_con_id('usuarios');
                $raiz_cred = Nodo::nodo_por_id('usuarios');
            }
            $nodo_cred = $raiz_cred->adyacente($nombre_usuario);
            if (!$nodo_cred) {
                $id_especial_cred = 'us_' . $nombre_usuario;
                $nodo_cred = Nodo::crear_con_dato_e_id($nombre_usuario, $id_especial_cred);
                if ($nodo_cred) {
                    $raiz_cred->_adyacente_en($nodo_cred, $nombre_usuario);
                }
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
            // Fase 2, v75: destruir `bloqueado_hasta` en lugar
            // de solo desenlazarlo.
            $nodo_bloqueo = $nodo_cred->adyacente('bloqueado_hasta');
            if ($nodo_bloqueo) {
                $nodo_cred->eliminar_adyacente('bloqueado_hasta');
                Nodo::eliminar($nodo_bloqueo);
            }
        });
    }

    $resultado = ['exito' => true];
    if ($codigo_acceso !== '') {
        $resultado['codigo_asignado'] = $codigo_acceso;
    }
    return $resultado;
}

/**
 * Destruye el nodo banco de un usuario: sus hojas (nombre,
 * cuenta) y el propio nodo banco.
 *
 * El nodo banco es un contenedor cuyo dato es el monto
 * bancarizado. Sus hijos son `nombre` y `cuenta`, ambos
 * hojas. `_destruir_campos_simples` recorre los adyacentes
 * y destruye los que no tienen hijos; después se destruye
 * el contenedor.
 *
 * @param Nodo $nodo_banco
 * @return void
 */
function _destruir_banco_usuario(Nodo $nodo_banco): void {
    _destruir_campos_simples($nodo_banco);
    Nodo::eliminar($nodo_banco);
}

/**
 * Elimina un usuario existente.
 *
 * A partir de v1.5piloto.75 (Fase 2 del plan de optimización
 * del grafo): destruye los campos del nodo usuario (nivel,
 * nombre_real, email, efectivo, banco con sus hijos), el
 * nodo credencial con sus campos (codigo_hash, contrasena,
 * intentos_fallidos, bloqueado_hasta, ultimo_acceso,
 * ip_ultimo_acceso), y las sesiones activas del usuario.
 * Antes quedaban ~15-20 nodos huérfanos por usuario.
 *
 * @param string $nombre_usuario Nombre del usuario a eliminar.
 * @return array Resultado de la operación.
 */
function eliminar_usuario(string $nombre_usuario): array {
    if (empty($nombre_usuario)) {
        return ['exito' => false, 'error' => 'Nombre de usuario no proporcionado'];
    }

    if ($nombre_usuario === ConfiguracionApli::NOMBRE_ADMIN) {
        return ['exito' => false, 'error' => 'No se puede eliminar al administrador principal'];
    }

    // Fase B3.1: ID especial directo.
    $nodo_usuario = Nodo::nodo_por_id('us_' . $nombre_usuario);
    if (!$nodo_usuario) return ['exito' => false, 'error' => 'Usuario no encontrado'];

    $nodo_terminales = $nodo_usuario->adyacente('terminales');
    if ($nodo_terminales && $nodo_terminales->adyacentes()) {
        return ['exito' => false, 'error' => 'No se puede eliminar un dueño con terminales asociadas'];
    }

    // === 1. Desenlazar referencias cruzadas entre usuarios ===

    // Terminal: desenlazar del contenedor `terminales` del dueño.
    $nodo_dueno = $nodo_usuario->adyacente('dueno');
    if ($nodo_dueno) {
        $dueno_nombre = $nodo_dueno->dato();
        // Fase B3.1: ID especial directo.
        $nodo_dueno_real = Nodo::nodo_por_id('us_' . $dueno_nombre);
        if ($nodo_dueno_real) {
            $nodo_contenedor_terminales = $nodo_dueno_real->adyacente('terminales');
            if ($nodo_contenedor_terminales) {
                $nodo_contenedor_terminales->eliminar_adyacente($nombre_usuario);
            }
        }
    }

    // Soporte: limpiar los enlaces `soporte` en sus dueños.
    $nodo_nivel_actual = $nodo_usuario->adyacente('nivel');
    $nivel_actual_del = $nodo_nivel_actual ? $nodo_nivel_actual->dato() : '';
    if ($nivel_actual_del === 'soporte') {
        $nombre_soporte_del = (string)$nodo_usuario->dato();
        $nodo_duenos_del = $nodo_usuario->adyacente('duenos');
        if ($nodo_duenos_del) {
            foreach ($nodo_duenos_del->adyacentes() as $nombre_d => $nodo_d) {
                // v1.5piloto.81l: quitar el `publico` del
                // soporte del contenedor `soportes` del dueño.
                _quitar_soporte_del_dueno($nodo_d, $nombre_soporte_del);
            }
        }
    }

    // Dueño: limpiar la referencia en sus soportes.
    // v1.5piloto.81l: el contenedor `soportes` permite varios
    // soportes por dueño. Se limpia el `duenos` de cada uno.
    // También se contempla el enlace `soporte` singular legacy.
    if ($nivel_actual_del === 'dueno') {
        // Enlace singular legacy.
        $nodo_soporte_legacy = $nodo_usuario->adyacente('soporte');
        if ($nodo_soporte_legacy) {
            $nombre_soporte_legacy = (string)$nodo_soporte_legacy->dato();
            $nodo_soporte_real_legacy = Nodo::nodo_por_id('us_' . $nombre_soporte_legacy);
            if ($nodo_soporte_real_legacy) {
                $nodo_duenos_sop_legacy = $nodo_soporte_real_legacy->adyacente('duenos');
                if ($nodo_duenos_sop_legacy) {
                    $nodo_duenos_sop_legacy->eliminar_adyacente($nombre_usuario);
                }
            }
            $nodo_usuario->eliminar_adyacente('soporte');
        }

        // Contenedor plural.
        $nodo_soportes = $nodo_usuario->adyacente('soportes');
        if ($nodo_soportes) {
            $adyacentes_sop = (array)$nodo_soportes->adyacentes();
            foreach ($adyacentes_sop as $nombre_sop => $nodo_pub) {
                $nombre_sop = (string)$nombre_sop;
                $nodo_sop_real = Nodo::nodo_por_id('us_' . $nombre_sop);
                if ($nodo_sop_real) {
                    $nodo_duenos_sop = $nodo_sop_real->adyacente('duenos');
                    if ($nodo_duenos_sop) {
                        $nodo_duenos_sop->eliminar_adyacente($nombre_usuario);
                    }
                }
                $nodo_soportes->eliminar_adyacente($nombre_sop);
            }
            $nodo_usuario->eliminar_adyacente('soportes');
            _destruir_campos_simples($nodo_soportes);
            Nodo::eliminar($nodo_soportes);
        }
    }

    // === 2. Destruir el banco (contenedor con nombre/cuenta) ===
    $nodo_banco = $nodo_usuario->adyacente('banco');
    if ($nodo_banco) {
        $nodo_usuario->eliminar_adyacente('banco');
        _destruir_banco_usuario($nodo_banco);
    }

    // === 3. Destruir el contenedor `duenos` (si es soporte) ===
    $nodo_duenos_del = $nodo_usuario->adyacente('duenos');
    if ($nodo_duenos_del) {
        // Vaciar el contenedor antes de destruirlo.
        $adyacentes_duenos = (array) $nodo_duenos_del->adyacentes();
        foreach ($adyacentes_duenos as $nombre_d => $nodo_d) {
            $nodo_duenos_del->eliminar_adyacente((string)$nombre_d);
        }
        $nodo_usuario->eliminar_adyacente('duenos');
        _destruir_campos_simples($nodo_duenos_del);
        Nodo::eliminar($nodo_duenos_del);
    }

    // === 4. Desenlazar referencias externas ===
    $nodo_usuario->eliminar_adyacente('dueno');
    $nodo_usuario->eliminar_adyacente('soporte');

    // === 5. Destruir campos simples del propio usuario ===
    // (nivel, nombre_real, email, efectivo).
    _destruir_campos_simples($nodo_usuario);

    // === 6. Desenlazar del contenedor raíz y destruir el usuario ===
    // Fase B3.1: aunque el nodo se resuelve por ID especial,
    // el enlace desde `usuarios` sigue existiendo y hay que
    // sacarlo antes de destruir el nodo (si no, queda huérfano
    // colgado de la raíz). Se resuelve la raíz solo acá.
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if ($raiz_usuarios) {
        $raiz_usuarios->eliminar_adyacente($nombre_usuario);
    }
    Nodo::eliminar($nodo_usuario);

    // Fix 1.5piloto.83n: registrar si el guardado falla. NO se hace
    // return temprano porque este flujo continúa con el guardado de
    // credenciales. Ver nota en agregar_usuario.
    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    if (!$ok_guardar) {
        log_persistencia("eliminar_usuario: guardar_ambos falló", 'error');
        Controlador::_error("eliminar_usuario: guardar_ambos falló");
    }

    // === 7. Destruir en credenciales: sesiones + nodo + campos ===
    en_grafo_credenciales(function() use ($nombre_usuario) {
        // Eliminar sesiones activas del usuario (destruye campos).
        eliminar_sesiones_de_usuario($nombre_usuario);

        // Destruir el nodo credencial y sus campos.
        $raiz_cred = Nodo::nodo_por_id('usuarios');
        if (!$raiz_cred) return;
        $nodo_cred = $raiz_cred->adyacente($nombre_usuario);
        if ($nodo_cred) {
            $raiz_cred->eliminar_adyacente($nombre_usuario);
            _destruir_campos_simples($nodo_cred);
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
    // Fase B3.1: ID especial directo.
    $nodo_dueno = Nodo::nodo_por_id('us_' . $nombre_dueno);
    if (!$nodo_dueno) return [];

    $nodo_nivel = $nodo_dueno->adyacente('nivel');
    if (!$nodo_nivel || $nodo_nivel->dato() !== 'dueno') return [];

    $nodo_terminales = $nodo_dueno->adyacente('terminales');
    if (!$nodo_terminales) return [];

    $adyacentes = $nodo_terminales->adyacentes();
    if (!$adyacentes) return [];

    // Pre-cargar el mapa de usuarios con código desde credenciales.
    $con_codigo = en_grafo_credenciales_solo_lectura(function() {
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

    // Fase B3.1: ID especial directo.
    $nodo_terminal = Nodo::nodo_por_id('us_' . $nombre_usuario);
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
    // Fase B3.1: ID especial directo.
    $nodo_terminal = Nodo::nodo_por_id('us_' . $nombre_usuario);
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
    // Fase B3.1: ID especial directo.
    $nodo_usuario = Nodo::nodo_por_id('us_' . $nombre_usuario);
    if (!$nodo_usuario) return null;

    // Mapa de usuarios con código (desde credenciales).
    $con_codigo = en_grafo_credenciales_solo_lectura(function() {
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

// ============================================================
// Fase B1: aseguramiento de los contenedores `publico` y
// `privado`. Se llaman desde `agregar_usuario` para que
// todo usuario nuevo salga con la estructura completa.
// Idempotente. No elimina los enlaces viejos (viven en
// la raíz del usuario, en paralelo a los contenedores).
// ============================================================

/**
 * Asegura que un nodo usuario tenga los contenedores
 * `publico` y `privado`, con los alias a los nodos ya
 * existentes. Idempotente.
 *
 * Distribución:
 *  - `publico` (dato = nombre_usuario): `nivel`,
 *    `nombre_real`, `email`.
 *  - `privado` (dato = ``): `efectivo`, `banco`,
 *    `terminales`, `empresas`, `viajes`, `pasajeros`,
 *    `ventas`, `rendiciones`, `liquidaciones`,
 *    `cancelaciones`.
 *  - Enlaces "de permiso" (`dueno`, `soporte`, `duenos`)
 *    quedan en la raíz, no se mueven.
 *
 * Los enlaces viejos en la raíz NO se eliminan: el
 * contenedor es un alias, apunta a los mismos nodos
 * físicos. Todo el código actual sigue funcionando.
 *
 * @param Nodo $nodo_usuario
 * @return void
 */
function _asegurar_niveles_usuario(Nodo $nodo_usuario): void {
    if (!$nodo_usuario) return;

    $nombre = (string)$nodo_usuario->dato();

    // === publico ===
    $publico = $nodo_usuario->adyacente('publico');
    if (!$publico) {
        $publico = Nodo::crear_con_dato($nombre);
        $nodo_usuario->_adyacente_en($publico, 'publico');
    } elseif ((string)$publico->dato() !== $nombre) {
        $publico->_dato($nombre);
    }
    foreach (['nivel', 'nombre_real', 'email'] as $campo) {
        $nodo_campo = $nodo_usuario->adyacente($campo);
        if ($nodo_campo && !$publico->adyacente($campo)) {
            $publico->_adyacente_en($nodo_campo, $campo);
        }
    }

    // === privado ===
    $privado = $nodo_usuario->adyacente('privado');
    if (!$privado) {
        $privado = Nodo::crear_con_dato('');
        $nodo_usuario->_adyacente_en($privado, 'privado');
    }
    $campos_privados = [
        'efectivo', 'banco', 'terminales', 'empresas',
        'viajes', 'pasajeros', 'ventas', 'rendiciones',
        'liquidaciones', 'cancelaciones',
    ];
    foreach ($campos_privados as $campo) {
        $nodo_campo = $nodo_usuario->adyacente($campo);
        if ($nodo_campo && !$privado->adyacente($campo)) {
            $privado->_adyacente_en($nodo_campo, $campo);
        }
    }
}

// ============================================================
// Fase B2.3.5c.2: aseguramiento del compartido de un terminal
//
// Cuando se crea un terminal (o se cambia su dueño), hay que
// crear el contenedor `compartido_con_<terminal>` en el dueño
// y repuntar el enlace `dueno` del terminal hacia él. Sin eso,
// el terminal queda apuntando al nodo del dueño real y rompe
// el aislamiento topológico en silencio.
//
// Antes esto solo lo hacía el comando `app:crear_compartidos_terminal`
// + `app:construir_arboles_compartidos` + `app:repuntar_terminales_compartido`
// corridos a mano. Ahora se hace al vuelo al crear/editar.
// ============================================================

/**
 * Asegura que exista el compartido de un terminal en su dueño,
 * con su marcador `_es_compartido` y su `dato`, y que tenga
 * los sub-contenedores y árboles que corresponden. Idempotente.
 *
 * No repunta el enlace `dueno` del terminal: de eso se encarga
 * `_asegurar_compartido_y_repuntar`, que es el que se llama
 * desde los flujos públicos.
 *
 * @param Nodo   $nodo_dueno
 * @param string $nombre_dueno
 * @param string $nombre_terminal
 * @return Nodo|null El compartido, o null si no se pudo crear.
 */
function _asegurar_compartido_terminal(Nodo $nodo_dueno, string $nombre_dueno, string $nombre_terminal): ?Nodo {
    $enlace = 'compartido_con_' . $nombre_terminal;
    $compartido = $nodo_dueno->adyacente($enlace);
    if (!$compartido) {
        $compartido = Nodo::crear_con_dato($nombre_dueno);
        if (!$compartido) return null;
        $nodo_dueno->_adyacente_en($compartido, $enlace);
    }

    // Dato (opción A).
    if ($compartido->dato() !== $nombre_dueno) {
        $compartido->_dato($nombre_dueno);
    }

    // Marcador `_es_compartido`.
    if (!$compartido->adyacente('_es_compartido')) {
        $compartido->_adyacente_en(Nodo::crear_con_dato('1'), '_es_compartido');
    }

    // Sub-contenedores.
    foreach (['viajes', 'empresas', 'ventas', 'cancelaciones', 'terminales'] as $sub) {
        if (!$compartido->adyacente($sub)) {
            $c = Nodo::crear_con_dato('');
            $compartido->_adyacente_en($c, $sub);
        }
    }

    // Alias al contenedor de pasajeros del dueño.
    if (!$compartido->adyacente('pasajeros')) {
        $cont_pas = $nodo_dueno->adyacente('pasajeros');
        if (!$cont_pas) {
            $priv = $nodo_dueno->adyacente('privado');
            if ($priv) $cont_pas = $priv->adyacente('pasajeros');
        }
        if ($cont_pas) {
            $compartido->_adyacente_en($cont_pas, 'pasajeros');
        }
    }

    // Viajes autorizados + empresas referenciadas.
    $cont_viajes = $nodo_dueno->adyacente('viajes');
    if (!$cont_viajes) {
        $priv = $nodo_dueno->adyacente('privado');
        if ($priv) $cont_viajes = $priv->adyacente('viajes');
    }
    $c_viajes = $compartido->adyacente('viajes');
    $c_empresas = $compartido->adyacente('empresas');
    if ($cont_viajes && $c_viajes && $c_empresas) {
        $empresas_ref = [];
        foreach ((array)$cont_viajes->adyacentes() as $nombre_v => $nodo_v) {
            $nombre_v = (string)$nombre_v;
            $tas = $nodo_v->adyacente('terminales_autorizadas');
            if (!$tas || !$tas->adyacente($nombre_terminal)) continue;
            if (!$c_viajes->adyacente($nombre_v)) {
                $c_viajes->_adyacente_en($nodo_v, $nombre_v);
            }
            $micros = $nodo_v->adyacente('micros');
            if ($micros) {
                foreach ((array)$micros->adyacentes() as $nodo_micro) {
                    $emp = $nodo_micro->adyacente('empresa');
                    if ($emp) $empresas_ref[(string)$emp->dato()] = $emp;
                }
            }
        }
        foreach ($empresas_ref as $ne => $nodo_e) {
            if (!$c_empresas->adyacente((string)$ne)) {
                $c_empresas->_adyacente_en($nodo_e, (string)$ne);
            }
        }
    }

    // Terminales: solo el propio.
    $c_term = $compartido->adyacente('terminales');
    if ($c_term && !$c_term->adyacente($nombre_terminal)) {
        $nodo_term = Nodo::nodo_por_id('us_' . $nombre_terminal);
        if ($nodo_term) {
            $c_term->_adyacente_en($nodo_term, $nombre_terminal);
        }
    }

    return $compartido;
}

/**
 * Asegura que el árbol de un sub-contenedor (ventas o
 * cancelaciones) tenga todos los nodos del terminal. Inserta
 * los que falten en el árbol del compartido con nombres
 * parametrizados (`hmi_<term>`, `hd_<term>`, `p_<term>`).
 * Idempotente.
 *
 * @param Nodo   $nodo_dueno
 * @param string $sub             'ventas' o 'cancelaciones'.
 * @param string $enlace_filtro   'terminal' (todos los nodos venta
 *                                y cancelación tienen este enlace).
 * @param string $valor_filtro    Nombre del terminal.
 * @param Nodo   $compartido
 * @param array  $nombres         Nombres parametrizados del árbol.
 * @return void
 */
function _asegurar_arbol_compartido(Nodo $nodo_dueno, string $sub, string $enlace_filtro, string $valor_filtro, Nodo $compartido, array $nombres): void {
    $cont_privado = $nodo_dueno->adyacente($sub);
    if (!$cont_privado) {
        $priv = $nodo_dueno->adyacente('privado');
        if ($priv) $cont_privado = $priv->adyacente($sub);
    }
    if (!$cont_privado) return;

    $c_compartido = $compartido->adyacente($sub);
    if (!$c_compartido) return;

    // Recolectar del árbol del dueño los nodos que matchean el filtro.
    $filtrados = [];
    $actual = hmi($cont_privado);
    $seg = 0;
    while ($actual && $seg < 3000) {
        $nodo_f = $actual->adyacente($enlace_filtro);
        if ($nodo_f && $nodo_f->dato() === $valor_filtro) {
            $filtrados[] = $actual;
        }
        $actual = hd($actual);
        $seg++;
    }

    // Para cada uno, insertar en el árbol del compartido si no está.
    foreach ($filtrados as $nodo) {
        $ya = false;
        $c_actual = hmi($c_compartido, $nombres);
        $c_seg = 0;
        while ($c_actual && $c_seg < 3000) {
            if ($c_actual->id() === $nodo->id()) {
                $ya = true;
                break;
            }
            $c_actual = hd($c_actual, $nombres);
            $c_seg++;
        }
        if (!$ya) {
            _hmi($c_compartido, $nodo, $nombres);
        }
    }
}

/**
 * Orquestador: asegura el compartido del terminal en el dueño
 * (incluyendo árboles de ventas y cancelaciones) y repunta el
 * enlace `dueno` del terminal al compartido. Idempotente.
 *
 * Se llama desde `agregar_usuario` (al crear un terminal) y
 * desde `actualizar_usuario` (al cambiar el dueño de un
 * terminal).
 *
 * @param Nodo   $nodo_dueno
 * @param Nodo   $nodo_terminal
 * @param string $nombre_dueno
 * @param string $nombre_terminal
 * @return void
 */
function _asegurar_compartido_y_repuntar(Nodo $nodo_dueno, Nodo $nodo_terminal, string $nombre_dueno, string $nombre_terminal): void {
    // 1. Asegurar el compartido.
    $compartido = _asegurar_compartido_terminal($nodo_dueno, $nombre_dueno, $nombre_terminal);
    if (!$compartido) return;

    // 2. Llenar los árboles de ventas y cancelaciones.
    $nombres = [
        'p'   => 'p_'   . $nombre_terminal,
        'hd'  => 'hd_'  . $nombre_terminal,
        'hmi' => 'hmi_' . $nombre_terminal,
    ];
    _asegurar_arbol_compartido($nodo_dueno, 'ventas',       'terminal', $nombre_terminal, $compartido, $nombres);
    _asegurar_arbol_compartido($nodo_dueno, 'cancelaciones', 'terminal', $nombre_terminal, $compartido, $nombres);

    // 3. Repuntar el enlace `dueno` del terminal al compartido.
    $nodo_dueno_actual = $nodo_terminal->adyacente('dueno');
    if (!$nodo_dueno_actual || $nodo_dueno_actual->id() !== $compartido->id()) {
        $nodo_terminal->_adyacente_en($compartido, 'dueno', true);
    }
}

// ============================================================
// Limpieza de datos de prueba (Fase 1 de §3 del plan actual).
// ============================================================

/**
 * Elimina los usuarios "de prueba".
 *
 * Criterio D1: nombre_usuario contiene "prueba", "test" o
 * "temp" (case-insensitive).
 *
 * Saltea (con motivo en `salteados`):
 *   - el admin principal;
 *   - usuarios con terminales asociadas (D1b);
 *   - usuarios con contenido propio: ventas, pasajeros,
 *     viajes, empresas, rendiciones, liquidaciones,
 *     cancelaciones, dueños (si es soporte), o venta
 *     en curso (D1c).
 *
 * No usa listar_usuarios: navega directo por la raíz
 * `usuarios`, sin tocar credenciales (D9).
 *
 * Disponible solo en modo pruebas.
 *
 * @return array
 * @since 1.5piloto.80a
 */
function limpiar_usuarios_de_prueba(): array {
    if (!\Iteradores\Configuracion\Entorno::es_pruebas()) {
        return ['exito' => false, 'error' => 'Disponible solo en modo pruebas'];
    }

    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) {
        return ['exito' => false, 'error' => 'No existe la raíz usuarios'];
    }

    $adyacentes = (array) $raiz->adyacentes();
    $candidatos = [];
    foreach ($adyacentes as $nombre_usuario => $nodo_usuario) {
        $nombre_usuario = (string)$nombre_usuario;
        $nombre_lower = strtolower($nombre_usuario);
        $es_candidato = (
            strpos($nombre_lower, 'prueba') !== false
            || strpos($nombre_lower, 'test') !== false
            || strpos($nombre_lower, 'temp') !== false
        );
        if ($es_candidato) {
            $candidatos[$nombre_usuario] = $nodo_usuario;
        }
    }

    $borrados = [];
    $salteados = [];
    foreach ($candidatos as $nombre_usuario => $nodo_usuario) {
        if ($nombre_usuario === ConfiguracionApli::NOMBRE_ADMIN) {
            $salteados[] = ['nombre' => $nombre_usuario, 'motivo' => 'Es el administrador'];
            continue;
        }

        // D1b: ¿tiene terminales asociadas?
        $nodo_terminales = $nodo_usuario->adyacente('terminales');
        if ($nodo_terminales && !empty($nodo_terminales->adyacentes())) {
            $salteados[] = ['nombre' => $nombre_usuario, 'motivo' => 'Tiene terminales asociadas'];
            continue;
        }

        // ¿Tiene una venta en curso colgada?
        if ($nodo_usuario->adyacente('venta_actual')) {
            $salteados[] = ['nombre' => $nombre_usuario, 'motivo' => 'Tiene una venta en curso'];
            continue;
        }

        // D1c: ¿tiene contenido asociado (ventas, pasajeros,
        // viajes, empresas, rendiciones, liquidaciones,
        // cancelaciones, o dueños asignados si es soporte)?
        $contenedores = [
            'ventas', 'pasajeros', 'viajes', 'empresas',
            'rendiciones', 'liquidaciones', 'cancelaciones',
            'duenos',
        ];
        $tiene_contenido = false;
        foreach ($contenedores as $c) {
            $nodo_c = $nodo_usuario->adyacente($c);
            if ($nodo_c && !empty($nodo_c->adyacentes())) {
                $tiene_contenido = true;
                break;
            }
        }
        if ($tiene_contenido) {
            $salteados[] = ['nombre' => $nombre_usuario, 'motivo' => 'Tiene datos asociados'];
            continue;
        }

        $res = eliminar_usuario($nombre_usuario);
        if (is_array($res) && !empty($res['exito'])) {
            $borrados[] = $nombre_usuario;
        } else {
            $motivo = (is_array($res) && isset($res['error'])) ? $res['error'] : 'Error desconocido';
            $salteados[] = ['nombre' => $nombre_usuario, 'motivo' => $motivo];
        }
    }

    return [
        'exito' => true,
        'borrados' => $borrados,
        'cantidad_borrados' => count($borrados),
        'salteados' => $salteados,
        'cantidad_salteados' => count($salteados),
    ];
}

/**
 * Elimina las terminales "de prueba" de un dueño.
 *
 * Criterio D3: nombre_usuario empieza con "termprueba".
 *
 * Reusa eliminar_terminal, que verifica permiso y llama
 * a eliminar_usuario (que hace todo el cleanup del nodo
 * usuario, credencial y sesiones).
 *
 * Disponible solo en modo pruebas.
 *
 * @param string $nombre_dueno
 * @return array
 * @since 1.5piloto.80a
 */
function limpiar_terminales_de_prueba(string $nombre_dueno): array {
    if (!\Iteradores\Configuracion\Entorno::es_pruebas()) {
        return ['exito' => false, 'error' => 'Disponible solo en modo pruebas'];
    }

    $nodo_dueno = Nodo::nodo_por_id('us_' . $nombre_dueno);
    if (!$nodo_dueno) {
        return ['exito' => false, 'error' => 'Dueño no encontrado'];
    }

    $nodo_terminales = $nodo_dueno->adyacente('terminales');
    if (!$nodo_terminales) {
        return [
            'exito' => true,
            'borrados' => [],
            'cantidad_borrados' => 0,
            'salteados' => [],
            'cantidad_salteados' => 0,
        ];
    }

    $prefijo = 'termprueba';
    $a_borrar = [];
    $adyacentes = (array) $nodo_terminales->adyacentes();
    foreach ($adyacentes as $nombre_terminal => $nodo_terminal) {
        $nombre_terminal = (string)$nombre_terminal;
        if (stripos($nombre_terminal, $prefijo) === 0) {
            $a_borrar[] = $nombre_terminal;
        }
    }

    $borrados = [];
    $salteados = [];
    foreach ($a_borrar as $nombre_terminal) {
        $res = eliminar_terminal($nombre_terminal, $nombre_dueno);
        if (is_array($res) && !empty($res['exito'])) {
            $borrados[] = $nombre_terminal;
        } else {
            $motivo = (is_array($res) && isset($res['error'])) ? $res['error'] : 'Error desconocido';
            $salteados[] = ['nombre' => $nombre_terminal, 'motivo' => $motivo];
        }
    }

    return [
        'exito' => true,
        'borrados' => $borrados,
        'cantidad_borrados' => count($borrados),
        'salteados' => $salteados,
        'cantidad_salteados' => count($salteados),
    ];
}