<?php
/**
 * Enrutador central de peticiones POST.
 *
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.77i
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
/**
 * Envía una respuesta JSON y termina la ejecución.
 *
 * @param array $datos Datos a codificar.
 * @return void
 */
function responder_json(array $datos): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Devuelve el nodo del compartido del solicitante si es
 * terminal, o null en otros casos.
 *
 * Fase B2.3.5b.1. Después del repuntado (B2.3.5a), el enlace
 * `dueno` del terminal apunta al compartido. Las funciones
 * de navegación que aceptan un `?Nodo $nodo_contexto` lo
 * usan para navegar por el subgrafo del terminal.
 *
 * Dueño, admin y soporte devuelven null: navegan por el
 * camino default (`usuarios → dueño`).
 *
 * @param string $nombre_solicitante
 * @return Nodo|null
 */
function _contexto_solicitante(string $nombre_solicitante): ?Nodo {
    if ($nombre_solicitante === '') return null;
    $nodo_sol = Nodo::nodo_por_id('us_' . $nombre_solicitante);
    if (!$nodo_sol) return null;
    $nivel = $nodo_sol->adyacente('nivel');
    if (!$nivel) {
        $publico = $nodo_sol->adyacente('publico');
        if ($publico) $nivel = $publico->adyacente('nivel');
    }
    if (!$nivel || $nivel->dato() !== 'terminal') return null;
    return $nodo_sol->adyacente('dueno');
}

/**
 * Devuelve el nombre del solicitante si es terminal, o null
 * en otros casos. Se usa como filtro opcional en las
 * funciones que reciben `?string $nombre_terminal`.
 *
 * Fase B2.3.5b.1.
 *
 * @param string $nombre_solicitante
 * @return string|null
 */
function _nombre_terminal_solicitante(string $nombre_solicitante): ?string {
    if ($nombre_solicitante === '') return null;
    $nodo_sol = Nodo::nodo_por_id('us_' . $nombre_solicitante);
    if (!$nodo_sol) return null;
    $nivel = $nodo_sol->adyacente('nivel');
    if (!$nivel) {
        $publico = $nodo_sol->adyacente('publico');
        if ($publico) $nivel = $publico->adyacente('nivel');
    }
    if (!$nivel || $nivel->dato() !== 'terminal') return null;
    return $nombre_solicitante;
}

/**
 * Enruta una petición POST según la acción indicada.
 *
 * @param string $accion Acción en formato "modulo/subaccion".
 * @param array $post Datos recibidos por POST.
 * @return void
 */
function enrutar_peticion_post(string $accion, array $post): void {
    $partes = explode('/', $accion);
    $modulo = $partes[0] ?? '';
    $subaccion = $partes[1] ?? '';

    // Chequeo global: si viene nombre_solicitante y nombre_dueno,
    // verificar que el solicitante tenga permiso sobre ese dueño.
    $nombre_solicitante = $post['nombre_solicitante'] ?? '';
    $nombre_dueno_post = $post['nombre_dueno'] ?? '';
    if ($nombre_solicitante !== '' && $nombre_dueno_post !== '') {
        if (!_verificar_permiso_dueno($nombre_solicitante, $nombre_dueno_post)) {
            responder_json(['exito' => false, 'error' => 'Permiso denegado sobre el dueño solicitado']);
        }
    }

    switch ($modulo) {
        case 'autenticar':
            switch ($subaccion) {
                case 'verificar':
                    $codigo = $post['codigo'] ?? '';
                    $usuario_ingresado = $post['usuario'] ?? '';
                    $contrasena_ingresada = $post['contrasena'] ?? '';

                    if ($usuario_ingresado !== '' && $contrasena_ingresada !== '') {
                        $usuario = autenticar_por_usuario($usuario_ingresado, $contrasena_ingresada);
                    } else {
                        $usuario = autenticar_por_codigo($codigo);
                    }

                    if ($usuario) {
                        // Si es terminal, incluir el nombre del dueño
                        if ($usuario['nivel'] === 'terminal') {
                            $raiz_usuarios = Nodo::nodo_por_id('usuarios');
                            $nodo_usuario = $raiz_usuarios ? $raiz_usuarios->adyacente($usuario['nombre_usuario']) : null;
                            $nodo_dueno = $nodo_usuario ? $nodo_usuario->adyacente('dueno') : null;
                            $usuario['dueno'] = $nodo_dueno ? $nodo_dueno->dato() : '';
                        }
                        responder_json(['exito' => true, 'usuario' => $usuario]);
                    } else {
                        responder_json(['exito' => false, 'error' => 'Credenciales incorrectas']);
                    }
                    break;
                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de autenticación no válida']);
            }
            break;

        case 'administrador':
            // Determinar el nivel del solicitante (excepto verificar).
            $nombre_sol_admin = $post['nombre_solicitante'] ?? '';
            $raiz_admin = Nodo::nodo_por_id('usuarios');
            $nodo_sol_admin = ($raiz_admin && $nombre_sol_admin !== '') ? $raiz_admin->adyacente($nombre_sol_admin) : null;
            $nodo_nivel_sol = $nodo_sol_admin ? $nodo_sol_admin->adyacente('nivel') : null;
            $nivel_sol = $nodo_nivel_sol ? $nodo_nivel_sol->dato() : '';
            if ($subaccion !== 'verificar' && !in_array($nivel_sol, ['admin', 'soporte'], true)) {
                responder_json(['exito' => false, 'error' => 'Permiso denegado']);
            }

            switch ($subaccion) {
                case 'verificar':
                    $codigo = $post['codigo'] ?? '';
                    if (verificar_codigo_admin($codigo)) {
                        responder_json(['exito' => true]);
                    } else {
                        responder_json(['exito' => false, 'error' => 'Código incorrecto']);
                    }
                    break;

                case 'listar_usuarios':
                    if ($nivel_sol === 'soporte') {
                        responder_json(['exito' => true, 'usuarios' => listar_usuarios_de_soporte($nombre_sol_admin)]);
                    } else {
                        responder_json(['exito' => true, 'usuarios' => listar_usuarios()]);
                    }
                    break;

                case 'listar_duenos':
                    responder_json(['exito' => true, 'duenos' => listar_duenos($nombre_sol_admin)]);
                    break;

                case 'listar_sesiones':
                    if ($nivel_sol === 'soporte') {
                        $nombre_dueno_filtro = $post['nombre_dueno'] ?? '';
                        $nombres = listar_nombres_usuarios_para_soporte($nombre_sol_admin, $nombre_dueno_filtro);
                        responder_json(['exito' => true, 'sesiones' => listar_sesiones_de_usuarios($nombres)]);
                    } else {
                        responder_json(['exito' => true, 'sesiones' => listar_sesiones()]);
                    }
                    break;

                case 'agregar_usuario':
                    $resultado = agregar_usuario($post);
                    responder_json($resultado);
                    break;

                case 'actualizar_usuario':
                    $resultado = actualizar_usuario($post);
                    responder_json($resultado);
                    break;

                case 'eliminar_usuario':
                    $nombre_usuario = $post['nombre_usuario'] ?? '';
                    $resultado = eliminar_usuario($nombre_usuario);
                    responder_json($resultado);
                    break;

                case 'listar_soportes':
                    if ($nivel_sol !== 'admin') {
                        responder_json(['exito' => false, 'error' => 'Solo el administrador puede listar soportes']);
                    }
                    responder_json(['exito' => true, 'soportes' => listar_soportes()]);
                    break;

                case 'listar_duenos_de_soporte':
                    if ($nivel_sol !== 'admin') {
                        responder_json(['exito' => false, 'error' => 'Solo el administrador puede listar los dueños de un soporte']);
                    }
                    $nombre_soporte = $post['nombre_soporte'] ?? '';
                    if (empty($nombre_soporte)) {
                        responder_json(['exito' => false, 'error' => 'Soporte no especificado']);
                    }
                    responder_json(['exito' => true, 'duenos' => listar_duenos_de_soporte($nombre_soporte)]);
                    break;

                case 'listar_duenos_disponibles':
                    if ($nivel_sol !== 'admin') {
                        responder_json(['exito' => false, 'error' => 'Solo el administrador puede listar todos los dueños']);
                    }
                    responder_json(['exito' => true, 'duenos' => listar_duenos()]);
                    break;

                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de administrador no válida']);
            }
            break;

        case 'dueno':
            switch ($subaccion) {
                case 'listar_terminales':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $terminales = listar_terminales_de_dueno($nombre_dueno);
                    responder_json(['exito' => true, 'terminales' => $terminales]);
                    break;

                case 'agregar_terminal':
                    // Forzar nivel y dueño
                    $post['nivel'] = 'terminal';
                    $post['dueno'] = $post['nombre_dueno'] ?? '';
                    if (empty($post['dueno'])) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $resultado = agregar_usuario($post);
                    responder_json($resultado);
                    break;

                case 'actualizar_terminal':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $resultado = actualizar_terminal($post, $nombre_dueno);
                    responder_json($resultado);
                    break;

                case 'eliminar_terminal':
                    $nombre_usuario = $post['nombre_usuario'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $resultado = eliminar_terminal($nombre_usuario, $nombre_dueno);
                    responder_json($resultado);
                    break;

                case 'listar_sesiones_terminales':
                    // Chequeo de nivel: solo admin, soporte y dueño.
                    $nombre_sol_dueno = $post['nombre_solicitante'] ?? '';
                    $raiz_sol_dueno = Nodo::nodo_por_id('usuarios');
                    $nodo_sol_dueno = ($raiz_sol_dueno && $nombre_sol_dueno !== '') ? $raiz_sol_dueno->adyacente($nombre_sol_dueno) : null;
                    $nodo_nivel_dueno = $nodo_sol_dueno ? $nodo_sol_dueno->adyacente('nivel') : null;
                    $nivel_sol_dueno = $nodo_nivel_dueno ? $nodo_nivel_dueno->dato() : '';
                    if (!in_array($nivel_sol_dueno, ['admin', 'soporte', 'dueno'], true)) {
                        responder_json(['exito' => false, 'error' => 'Permiso denegado']);
                    }

                    // No se confía en la lista de terminales del cliente:
                    // se leen las terminales reales del dueño.
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $terminales_datos = listar_terminales_de_dueno($nombre_dueno);
                    $nombres_terminales = array_map(function($t) { return (string)$t['nombre_usuario']; }, $terminales_datos);
                    $sesiones = listar_sesiones_de_usuarios($nombres_terminales);
                    responder_json(['exito' => true, 'sesiones' => $sesiones]);
                    break;

                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de dueño no válida']);
            }
            break;

        case 'sesiones':
            if ($subaccion === 'cerrar') {
                $token = $post['token'] ?? '';
                if (empty($token)) {
                    responder_json(['exito' => false, 'error' => 'Token no válido']);
                }
                $nombre_solicitante_cerrar = $post['nombre_solicitante'] ?? '';
                if (!_puede_cerrar_sesion($nombre_solicitante_cerrar, $token)) {
                    responder_json(['exito' => false, 'error' => 'Permiso denegado']);
                }
                if (cerrar_sesion($token)) {
                    responder_json(['exito' => true]);
                } else {
                    responder_json(['exito' => false, 'error' => 'Token no válido']);
                }
            } elseif ($subaccion === 'validar') {
                $token = $post['token'] ?? '';
                $resultado = validar_token_sesion($token);
                if ($resultado) {
                    $nodo_usuario = $resultado['nodo'];
                    $nombre_usuario = $resultado['nombre_usuario'];
                    $nodo_nivel = $nodo_usuario->adyacente('nivel');
                    $nodo_nombre_real = $nodo_usuario->adyacente('nombre_real');
                    $usuario = [
                        'nombre_usuario' => $nombre_usuario,
                        'nombre_real' => $nodo_nombre_real ? $nodo_nombre_real->dato() : $nombre_usuario,
                        'nivel' => $nodo_nivel ? $nodo_nivel->dato() : 'terminal',
                    ];
                    // Si es terminal, incluir dueño
                    if ($usuario['nivel'] === 'terminal') {
                        $nodo_dueno = $nodo_usuario->adyacente('dueno');
                        $usuario['dueno'] = $nodo_dueno ? $nodo_dueno->dato() : '';
                    }
                    // Si es soporte, incluir la lista de dueños asignados.
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
                    responder_json(['exito' => true, 'usuario' => $usuario]);
                } else {
                    responder_json(['exito' => false, 'error' => 'Sesión no válida']);
                }
            }else {
                responder_json(['exito' => false, 'error' => 'Subacción de sesiones no válida']);
            }
            break;
        case 'empresas':
            switch ($subaccion) {
                case 'listar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    // Fase B2.3.5b.3: contexto del solicitante.
                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);
                    $empresas = listar_empresas_de_dueno($nombre_dueno, $nodo_contexto);
                    responder_json(['exito' => true, 'empresas' => $empresas]);
                    break;

                case 'agregar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $nombre_empresa = $post['nombre_empresa'] ?? '';
                    $nombre_real = $post['nombre_real'] ?? '';
                    $resultado = agregar_empresa($nombre_dueno, $nombre_empresa, $nombre_real);
                    responder_json($resultado);
                    break;
                case 'editar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $nombre_empresa = $post['nombre_empresa'] ?? '';
                    $nuevo_nombre = $post['nuevo_nombre'] ?? '';
                    if (empty($nombre_dueno) || empty($nombre_empresa)) {
                        responder_json(['exito' => false, 'error' => 'Dueño y empresa son obligatorios']);
                    }
                    $resultado = editar_empresa($nombre_dueno, $nombre_empresa, $nuevo_nombre);
                    responder_json($resultado);
                    break;
                case 'eliminar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $nombre_empresa = $post['nombre_empresa'] ?? '';
                    if (empty($nombre_dueno) || empty($nombre_empresa)) {
                        responder_json(['exito' => false, 'error' => 'Dueño y empresa son obligatorios']);
                    }
                    $resultado = eliminar_empresa($nombre_dueno, $nombre_empresa);
                    responder_json($resultado);
                    break;
                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de empresas no válida']);
            }
            break;
        case 'vehiculos':
            switch ($subaccion) {
                case 'listar':
                    $nombre_empresa = $post['nombre_empresa'] ?? '';
                    if (empty($nombre_empresa)) {
                        responder_json(['exito' => false, 'error' => 'Empresa no especificada']);
                    }
                    // Fase B2.3.5b.3: contexto del solicitante.
                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);
                    $vehiculos = listar_vehiculos_de_empresa($nombre_empresa, $nodo_contexto);
                    responder_json(['exito' => true, 'vehiculos' => $vehiculos]);
                    break;

                case 'agregar':
                    $nombre_empresa = $post['nombre_empresa'] ?? '';
                    $nombre_vehiculo = $post['nombre_vehiculo'] ?? '';
                    $nombre_real = $post['nombre_real'] ?? '';
                    $resultado = agregar_vehiculo($nombre_empresa, $nombre_vehiculo, $nombre_real);
                    responder_json($resultado);
                    break;
                case 'actualizar':
                    $nombre_empresa = $post['nombre_empresa'] ?? '';
                    $nombre_vehiculo = $post['nombre_vehiculo'] ?? '';
                    $nombre_real = $post['nombre_real'] ?? null;
                    $asientos = $post['asientos'] ?? null;

                    if (empty($nombre_empresa) || empty($nombre_vehiculo)) {
                        responder_json(['exito' => false, 'error' => 'Empresa y vehículo son obligatorios']);
                    }

                    $datos = [];
                    if ($nombre_real !== null) {
                        $datos['nombre_real'] = $nombre_real;
                    }
                    if ($asientos !== null) {
                        $datos['asientos'] = (int)$asientos;
                    }

                    $resultado = actualizar_vehiculo($nombre_empresa, $nombre_vehiculo, $datos);
                    responder_json($resultado);
                    break;
                case 'actualizar_configuracion':
                    $nombre_empresa = $post['nombre_empresa'] ?? '';
                    $nombre_vehiculo = $post['nombre_vehiculo'] ?? '';
                    $configuracion_json = $post['configuracion'] ?? '';

                    if (empty($nombre_empresa) || empty($nombre_vehiculo)) {
                        responder_json(['exito' => false, 'error' => 'Empresa y vehículo son obligatorios']);
                    }

                    $configuracion = json_decode($configuracion_json, true);
                    if (!is_array($configuracion)) {
                        responder_json(['exito' => false, 'error' => 'Configuración inválida']);
                    }

                    $resultado = actualizar_configuracion_vehiculo($nombre_empresa, $nombre_vehiculo, $configuracion);
                    responder_json($resultado);
                    break;
                case 'eliminar':
                    $nombre_empresa = $post['nombre_empresa'] ?? '';
                    $nombre_vehiculo = $post['nombre_vehiculo'] ?? '';
                    if (empty($nombre_empresa) || empty($nombre_vehiculo)) {
                        responder_json(['exito' => false, 'error' => 'Empresa y vehículo son obligatorios']);
                    }
                    $resultado = eliminar_vehiculo($nombre_empresa, $nombre_vehiculo);
                    responder_json($resultado);
                    break;
                case 'subir_foto':
                    $nombre_empresa = $post['nombre_empresa'] ?? '';
                    $nombre_vehiculo = $post['nombre_vehiculo'] ?? '';
                    if (empty($nombre_empresa) || empty($nombre_vehiculo)) {
                        responder_json(['exito' => false, 'error' => 'Empresa y vehículo son obligatorios']);
                    }
                    if (!isset($_FILES['foto'])) {
                        responder_json(['exito' => false, 'error' => 'Archivo no enviado']);
                    }
                    $resultado = subir_foto_vehiculo($nombre_empresa, $nombre_vehiculo, $_FILES['foto']);
                    responder_json($resultado);
                    break;
                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de vehículos no válida']);
            }
            break;
        case 'viajes':
            switch ($subaccion) {
                case 'listar_por_dueno':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $viajes = listar_viajes_de_dueno($nombre_dueno);
                    responder_json(['exito' => true, 'viajes' => $viajes]);
                    break;

                case 'listar_por_terminal':
                    $nombre_terminal = $post['nombre_terminal'] ?? '';
                    if (empty($nombre_terminal)) {
                        responder_json(['exito' => false, 'error' => 'Terminal no especificada']);
                    }
                    $viajes = listar_viajes_de_terminal($nombre_terminal);
                    responder_json(['exito' => true, 'viajes' => $viajes]);
                    break;

                case 'limpiar_prueba':
                    // Solo admin.
                    $nombre_sol_lp = $post['nombre_solicitante'] ?? '';
                    $raiz_sol_lp = Nodo::nodo_por_id('usuarios');
                    $nodo_sol_lp = ($raiz_sol_lp && $nombre_sol_lp !== '') ? $raiz_sol_lp->adyacente($nombre_sol_lp) : null;
                    $nodo_nivel_lp = $nodo_sol_lp ? $nodo_sol_lp->adyacente('nivel') : null;
                    $nivel_sol_lp = $nodo_nivel_lp ? $nodo_nivel_lp->dato() : '';
                    if ($nivel_sol_lp !== 'admin') {
                        responder_json(['exito' => false, 'error' => 'Solo el administrador puede ejecutar esta acción']);
                    }
                    $nombre_dueno_lp = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno_lp)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $resultado_lp = limpiar_viajes_de_prueba($nombre_dueno_lp);
                    responder_json($resultado_lp);
                    break;

                case 'guardar':
                    // Alta o edición unificada, incluyendo opciones avanzadas
                    $resultado = guardar_viaje_completo($post);
                    responder_json($resultado);
                    break;

                case 'obtener_declaracion':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $tipo_dj = $post['tipo_dj'] ?? 'mayor';
                    $forzar_default = ($post['forzar_default'] ?? '0') === '1';
                    if (empty($nombre_dueno) || empty($nombre_viaje)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $resultado = obtener_declaracion_jurada($nombre_dueno, $nombre_viaje, $tipo_dj, $forzar_default);
                    responder_json($resultado);
                    break;

                case 'guardar_declaracion':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $tipo_dj = $post['tipo_dj'] ?? 'mayor';
                    $contenido = $post['contenido'] ?? '';
                    if (empty($nombre_dueno) || empty($nombre_viaje)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $resultado = guardar_declaracion_jurada($nombre_dueno, $nombre_viaje, $tipo_dj, $contenido);
                    responder_json($resultado);
                    break;

                case 'eliminar':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $resultado = eliminar_viaje($nombre_viaje, $nombre_dueno);
                    responder_json($resultado);
                    break;

                case 'agregar_micro':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_empresa = $post['nombre_empresa'] ?? '';
                    $nombre_vehiculo = $post['nombre_vehiculo'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $monto = $post['monto'] ?? '0';
                    $resultado = agregar_micro_a_viaje($nombre_viaje, $nombre_empresa, $nombre_vehiculo, $nombre_dueno, $monto);
                    responder_json($resultado);
                    break;

                case 'eliminar_micro':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_micro = $post['nombre_micro'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $resultado = eliminar_micro_de_viaje($nombre_viaje, $nombre_micro, $nombre_dueno);
                    responder_json($resultado);
                    break;

                case 'agregar_terminal':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_terminal = $post['nombre_terminal'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_terminal) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $resultado = agregar_terminal_autorizada($nombre_viaje, $nombre_terminal, $nombre_dueno);
                    responder_json($resultado);
                    break;

                case 'eliminar_terminal':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_terminal = $post['nombre_terminal'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_terminal) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $resultado = eliminar_terminal_autorizada($nombre_viaje, $nombre_terminal, $nombre_dueno);
                    responder_json($resultado);
                    break;

                case 'obtener_micro':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_micro = $post['nombre_micro'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $resultado = obtener_micro_de_viaje($nombre_viaje, $nombre_micro, $nombre_dueno);
                    responder_json($resultado);
                    break;

                case 'actualizar_monto_micro':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_micro = $post['nombre_micro'] ?? '';
                    $monto = $post['monto'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $resultado = actualizar_monto_micro($nombre_viaje, $nombre_micro, $monto, $nombre_dueno);
                    responder_json($resultado);
                    break;

                case 'estado_asientos':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_micro = $post['nombre_micro'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    // Fase B2.3.5b.1: si el solicitante es terminal,
                    // navegar por su subgrafo compartido.
                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);
                    $resultado = obtener_estados_asientos_micro($nombre_viaje, $nombre_micro, $nombre_dueno, $nodo_contexto);
                    responder_json($resultado);
                    break;

                case 'seleccionar_asiento':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_micro = $post['nombre_micro'] ?? '';
                    $fila = $post['fila'] ?? '';
                    $columna = $post['columna'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $nombre_terminal = $post['nombre_terminal'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($fila) || empty($columna) || empty($nombre_dueno) || empty($nombre_terminal)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $resultado = seleccionar_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $nombre_terminal);
                    responder_json($resultado);
                    break;

                case 'deseleccionar_asiento':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_micro = $post['nombre_micro'] ?? '';
                    $fila = $post['fila'] ?? '';
                    $columna = $post['columna'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $nombre_terminal = $post['nombre_terminal'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($fila) || empty($columna) || empty($nombre_dueno) || empty($nombre_terminal)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $resultado = deseleccionar_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $nombre_terminal);
                    responder_json($resultado);
                    break;

                case 'reservar_asiento':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_micro = $post['nombre_micro'] ?? '';
                    $fila = $post['fila'] ?? '';
                    $columna = $post['columna'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($fila) || empty($columna) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    // Datos del pasajero opcionales: si vienen, se asigna en el mismo paso.
                    $datos_pasajero = [];
                    if (isset($post['datos_pasajero']) && $post['datos_pasajero'] !== '') {
                        $decodificado = json_decode($post['datos_pasajero'], true);
                        if (is_array($decodificado)) {
                            $datos_pasajero = $decodificado;
                        }
                    }
                    // Fase B2.3.5b.2: contexto del solicitante.
                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);
                    $resultado = reservar_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $datos_pasajero, $nodo_contexto);
                    responder_json($resultado);
                    break;
                    
                case 'asignar_pasajero_reserva':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_micro = $post['nombre_micro'] ?? '';
                    $fila = $post['fila'] ?? '';
                    $columna = $post['columna'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $datos_pasajero_json = $post['datos_pasajero'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($fila) || empty($columna) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $datos_pasajero = json_decode($datos_pasajero_json, true);
                    if (!is_array($datos_pasajero)) {
                        responder_json(['exito' => false, 'error' => 'Datos del pasajero inválidos']);
                    }
                    // Fase B2.3.5b.2: contexto del solicitante.
                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);
                    $resultado = asignar_pasajero_a_reserva($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $datos_pasajero, $nodo_contexto);
                    responder_json($resultado);
                    break;

                case 'liberar_reserva_asiento':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_micro = $post['nombre_micro'] ?? '';
                    $fila = $post['fila'] ?? '';
                    $columna = $post['columna'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_micro) || empty($fila) || empty($columna) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    // Fase B2.3.5b.2: contexto del solicitante.
                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);
                    $resultado = liberar_reserva_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $nodo_contexto);
                    responder_json($resultado);
                    break;

                case 'cambiar_asiento':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_micro = $post['nombre_micro'] ?? '';
                    $fila_vieja = $post['fila_vieja'] ?? '';
                    $columna_vieja = $post['columna_vieja'] ?? '';
                    $fila_nueva = $post['fila_nueva'] ?? '';
                    $columna_nueva = $post['columna_nueva'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $nombre_solicitante = $post['nombre_solicitante'] ?? '';
                    $dejar_reservado_viejo = (($post['dejar_reservado_viejo'] ?? '0') === '1');
                    if (empty($nombre_viaje) || empty($nombre_micro) || $fila_vieja === '' || $columna_vieja === '' || $fila_nueva === '' || $columna_nueva === '' || empty($nombre_dueno) || empty($nombre_solicitante)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    // Fase B2.3.5b.2: contexto del solicitante.
                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);
                    $resultado = cambiar_asiento_pasaje(
                        $nombre_viaje, $nombre_micro,
                        $fila_vieja, $columna_vieja,
                        $fila_nueva, $columna_nueva,
                        $nombre_dueno, $nombre_solicitante,
                        $dejar_reservado_viejo,
                        $nodo_contexto
                    );
                    responder_json($resultado);
                    break;

                case 'obtener_opciones_avanzadas':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $opciones = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);
                    responder_json(['exito' => true, 'opciones' => $opciones]);
                    break;

                case 'guardar_opciones_avanzadas':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $opciones = [
                        'restriccion_edad' => $post['restriccion_edad'] ?? '0',
                        'edad_minima' => $post['edad_minima'] ?? '18',
                        'edad_maxima' => $post['edad_maxima'] ?? '80',
                        'mostrar_dj_en_terminales' => $post['mostrar_dj_en_terminales'] ?? '0',
                    ];
                    if (empty($nombre_viaje) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $resultado = guardar_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje, $opciones);
                    responder_json($resultado);
                    break;
                case 'obtener_opciones_terminal':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_terminal = $post['nombre_terminal'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_terminal) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $opciones = obtener_opciones_terminal_viaje($nombre_dueno, $nombre_viaje, $nombre_terminal);
                    responder_json(['exito' => true, 'opciones' => $opciones]);
                    break;

                case 'guardar_opciones_terminal':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_terminal = $post['nombre_terminal'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_viaje) || empty($nombre_terminal) || empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    $opciones = [
                        'cambiar_punto_predeterminado' => $post['cambiar_punto_predeterminado'] ?? '0',
                        'punto_subida_bajada' => $post['punto_subida_bajada'] ?? '',
                        'permite_efectivo' => $post['permite_efectivo'] ?? '',
                        'cuotas_efectivo_max' => $post['cuotas_efectivo_max'] ?? '',
                        'permite_transferencia' => $post['permite_transferencia'] ?? '',
                        'cuotas_transferencia_max' => $post['cuotas_transferencia_max'] ?? '',
                    ];
                    $aplicar_retroactivo = (($post['aplicar_retroactivo'] ?? '') === '1');
                    $resultado = guardar_opciones_terminal_viaje($nombre_dueno, $nombre_viaje, $nombre_terminal, $opciones, $aplicar_retroactivo);
                    responder_json($resultado);
                    break;

                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de viajes no válida']);
            }
            break;
        case 'ventas':
            switch ($subaccion) {
                case 'confirmar':
                    $nombre_terminal = $post['nombre_terminal'] ?? '';
                    $metodo_pago = $post['metodo_pago'] ?? 'efectivo';
                    $cuotas = (int)($post['cuotas'] ?? 1);
                    $monto_pagado = (float)($post['monto_pagado'] ?? 0);
                    $comprador_dni = $post['comprador_dni'] ?? '';
                    $comprador_apellido = $post['comprador_apellido'] ?? '';
                    $comprador_nombres = $post['comprador_nombres'] ?? '';
                    $comprador_email = $post['comprador_email'] ?? '';
                    $comprador_celular = $post['comprador_celular'] ?? '';
                    $pasajeros_json = $post['pasajeros'] ?? '[]';
                    $pasajeros_por_asiento = json_decode($pasajeros_json, true);
                    if (!is_array($pasajeros_por_asiento)) $pasajeros_por_asiento = [];

                    // Leer fechas enviadas desde frontend
                    $fecha_hora = $post['fecha_hora'] ?? '';
                    $fecha_pago = $post['fecha_pago'] ?? '';

                    if (empty($nombre_terminal)) {
                        responder_json(['exito' => false, 'error' => 'Terminal no especificada']);
                    }

                    $resultado = confirmar_venta_actual(
                        $nombre_terminal,
                        $metodo_pago,
                        $cuotas,
                        $monto_pagado,
                        $comprador_dni,
                        $comprador_apellido,
                        $comprador_nombres,
                        $comprador_email,
                        $comprador_celular,
                        $pasajeros_por_asiento,
                        $fecha_hora,   // string
                        $fecha_pago    // string
                    );
                    responder_json($resultado);
                    break;
                case 'obtener':
                    $id_venta = $post['id_venta'] ?? '';
                    if (empty($id_venta)) {
                        responder_json(['exito' => false, 'error' => 'ID de venta no especificado']);
                    }
                    // Fase B2.3.5b.1: si el solicitante es terminal,
                    // restringir la búsqueda a su contexto.
                    $nombre_terminal_sol = _nombre_terminal_solicitante($nombre_solicitante);
                    $venta = obtener_venta_por_id($id_venta, $nombre_terminal_sol);
                    if ($venta) {
                        responder_json(['exito' => true, 'venta' => $venta]);
                    } else {
                        responder_json(['exito' => false, 'error' => 'Venta no encontrada']);
                    }
                    break;

                case 'cancelar':
                    $id_venta = $post['id_venta'] ?? '';
                    $motivo = $post['motivo'] ?? '';
                    if (empty($id_venta)) {
                        responder_json(['exito' => false, 'error' => 'ID de venta no especificado']);
                    }
                    // Fase B2.3.5b.1: filtro opcional por terminal.
                    $nombre_terminal_sol = _nombre_terminal_solicitante($nombre_solicitante);
                    $resultado = cancelar_venta($id_venta, $motivo, $nombre_terminal_sol);
                    responder_json($resultado);
                    break;
                case 'pagar_cupon':
                    $id_venta = $post['id_venta'] ?? '';
                    $numero_cupon = $post['numero_cupon'] ?? '';
                    $monto = $post['monto'] ?? '';
                    $metodo_pago = $post['metodo_pago'] ?? '';
                    if (empty($id_venta) || empty($numero_cupon) || empty($monto) || empty($metodo_pago)) {
                        responder_json(['exito' => false, 'error' => 'Parámetros incompletos']);
                    }
                    // Fase B2.3.5b.1: filtro opcional por terminal.
                    $nombre_terminal_sol = _nombre_terminal_solicitante($nombre_solicitante);
                    $resultado = pagar_cupon_venta($id_venta, $numero_cupon, $monto, $metodo_pago, $nombre_terminal_sol);
                    responder_json($resultado);
                    break;
                case 'info_cancelacion':
                    $id_venta = $post['id_venta'] ?? '';
                    if (empty($id_venta)) {
                        responder_json(['exito' => false, 'error' => 'ID de venta no especificado']);
                    }
                    // Fase B2.3.5b.1: filtro opcional por terminal.
                    $nombre_terminal_sol = _nombre_terminal_solicitante($nombre_solicitante);
                    $resultado = obtener_info_cancelacion($id_venta, $nombre_terminal_sol);
                    responder_json($resultado);
                    break;
                case 'listar':
                    $tipo = $post['tipo'] ?? 'dueno';
                    $nombre = $post['nombre'] ?? '';
                    if (empty($nombre)) {
                        responder_json(['exito' => false, 'error' => 'Nombre no especificado']);
                    }
                    if ($tipo === 'dueno') {
                        $ventas = listar_ventas_por_dueno($nombre);
                    } else {
                        $ventas = listar_ventas_por_terminal($nombre);
                    }
                    responder_json(['exito' => true, 'ventas' => $ventas]);
                    break;
                case 'guardar':
                    $resultado = guardar_viaje_completo($post);
                    responder_json($resultado);
                    break;
                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de ventas no válida']);
            }
            break;

        case 'pasajeros':
            switch ($subaccion) {
                case 'crear':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueno no especificado']);
                    }
                    $datos = [
                        'dni' => $post['dni'] ?? '',
                        'nombres' => $post['nombres'] ?? '',
                        'apellido' => $post['apellido'] ?? '',
                        'email' => $post['email'] ?? '',
                        'celular' => $post['celular'] ?? '',
                        'celular_emergencia' => $post['celular_emergencia'] ?? '',
                        'fecha_nacimiento' => $post['fecha_nacimiento'] ?? '',
                        'direccion' => $post['direccion'] ?? '',
                        'localidad' => $post['localidad'] ?? '',
                    ];
                    // Fase B2.3.5b.3: contexto del solicitante.
                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);
                    $resultado = crear_pasajero($nombre_dueno, $datos, $nodo_contexto);
                    responder_json($resultado);
                    break;

                case 'subir_declaracion':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $dni = $post['dni'] ?? '';
                    if (empty($nombre_dueno) || empty($dni)) {
                        responder_json(['exito' => false, 'error' => 'Dueño y DNI son obligatorios']);
                    }
                    if (!isset($_FILES['archivo'])) {
                        responder_json(['exito' => false, 'error' => 'Archivo no enviado']);
                    }
                    $resultado = subir_declaracion_jurada_pasajero($nombre_dueno, $dni, $_FILES['archivo']);
                    responder_json($resultado);
                    break;

                case 'eliminar_declaracion':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $dni = $post['dni'] ?? '';
                    if (empty($nombre_dueno) || empty($dni)) {
                        responder_json(['exito' => false, 'error' => 'Dueño y DNI son obligatorios']);
                    }
                    $resultado = eliminar_declaracion_jurada_pasajero($nombre_dueno, $dni);
                    responder_json($resultado);
                    break;

                case 'listar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    // Fase B2.3.5b.3: contexto del solicitante.
                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);
                    responder_json(['exito' => true, 'pasajeros' => listar_pasajeros($nombre_dueno, $nodo_contexto)]);
                    break;

                case 'buscar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $termino = $post['termino'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    // Fase B2.3.5b.3: contexto del solicitante.
                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);
                    responder_json(['exito' => true, 'pasajeros' => buscar_pasajeros($nombre_dueno, $termino, $nodo_contexto)]);
                    break;

                case 'obtener':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $dni = $post['dni'] ?? '';
                    if (empty($nombre_dueno) || empty($dni)) {
                        responder_json(['exito' => false, 'error' => 'Dueño y DNI son obligatorios']);
                    }
                    // Fase B2.3.5b.3: contexto del solicitante.
                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);
                    $pasajero = obtener_pasajero_por_dni($nombre_dueno, $dni, $nodo_contexto);
                    if ($pasajero) {
                        responder_json(['exito' => true, 'pasajero' => $pasajero]);
                    } else {
                        responder_json(['exito' => false, 'error' => 'Pasajero no encontrado']);
                    }
                    break;

                case 'actualizar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $dni = $post['dni'] ?? '';
                    if (empty($nombre_dueno) || empty($dni)) {
                        responder_json(['exito' => false, 'error' => 'Dueño y DNI son obligatorios']);
                    }
                    $datos = [
                        'nombres' => $post['nombres'] ?? '',
                        'apellido' => $post['apellido'] ?? '',
                        'email' => $post['email'] ?? '',
                        'celular' => $post['celular'] ?? '',
                        'celular_emergencia' => $post['celular_emergencia'] ?? '',
                        'direccion' => $post['direccion'] ?? '',
                        'localidad' => $post['localidad'] ?? '',
                    ];
                    // Fase B2.3.5b.3: contexto del solicitante.
                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);
                    $resultado = actualizar_pasajero($nombre_dueno, $dni, $datos, $nodo_contexto);
                    responder_json($resultado);
                    break;

                case 'eliminar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $dni = $post['dni'] ?? '';
                    if (empty($nombre_dueno) || empty($dni)) {
                        responder_json(['exito' => false, 'error' => 'Dueño y DNI son obligatorios']);
                    }
                    $resultado = eliminar_pasajero($nombre_dueno, $dni);
                    responder_json($resultado);
                    break;

                case 'limpiar_prueba':
                    // Solo admin y solo en modo pruebas.
                    if (!\Iteradores\Configuracion\Entorno::es_pruebas()) {
                        responder_json(['exito' => false, 'error' => 'Disponible solo en modo pruebas']);
                    }
                    $nombre_sol_lpp = $post['nombre_solicitante'] ?? '';
                    $raiz_sol_lpp = Nodo::nodo_por_id('usuarios');
                    $nodo_sol_lpp = ($raiz_sol_lpp && $nombre_sol_lpp !== '') ? $raiz_sol_lpp->adyacente($nombre_sol_lpp) : null;
                    $nodo_nivel_lpp = $nodo_sol_lpp ? $nodo_sol_lpp->adyacente('nivel') : null;
                    $nivel_sol_lpp = $nodo_nivel_lpp ? $nodo_nivel_lpp->dato() : '';
                    if ($nivel_sol_lpp !== 'admin') {
                        responder_json(['exito' => false, 'error' => 'Solo el administrador puede ejecutar esta acción']);
                    }
                    $nombre_dueno_lpp = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno_lpp)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $resultado_lpp = limpiar_pasajeros_de_prueba($nombre_dueno_lpp);
                    responder_json($resultado_lpp);
                    break;

                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de pasajeros no válida']);
            }
            break;
        case 'rendiciones':
            switch ($subaccion) {
                case 'previsualizar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $filtros = [
                        'viaje' => $post['viaje'] ?? 'todos',
                        'vendedor' => $post['vendedor'] ?? 'Todos',
                        'estado' => $post['estado'] ?? 'todos',
                        'codigo' => $post['codigo'] ?? '',
                        'comprador' => $post['comprador'] ?? '',
                        'fecha_desde' => $post['fecha_desde'] ?? '',
                        'fecha_hasta' => $post['fecha_hasta'] ?? '',
                    ];
                    $resultado = previsualizar_rendicion($nombre_dueno, $filtros);
                    responder_json($resultado);
                    break;

                case 'confirmar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $ventas_json = $post['ventas_seleccionadas'] ?? '[]';
                    $ventas_sel = json_decode($ventas_json, true);
                    if (!is_array($ventas_sel)) $ventas_sel = [];
                    $filtros = [
                        'viaje' => $post['viaje'] ?? 'todos',
                        'vendedor' => $post['vendedor'] ?? 'Todos',
                        'estado' => $post['estado'] ?? 'todos',
                        'codigo' => $post['codigo'] ?? '',
                        'comprador' => $post['comprador'] ?? '',
                        'fecha_desde' => $post['fecha_desde'] ?? '',
                        'fecha_hasta' => $post['fecha_hasta'] ?? '',
                    ];
                    $resultado = confirmar_rendicion($nombre_dueno, $ventas_sel, $filtros);
                    responder_json($resultado);
                    break;

                case 'listar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $filtros = [
                        'codigo' => $post['codigo'] ?? '',
                        'fecha_desde' => $post['fecha_desde'] ?? '',
                        'fecha_hasta' => $post['fecha_hasta'] ?? '',
                        'terminal' => $post['terminal'] ?? '',
                    ];
                    $rendiciones = listar_rendiciones_de_dueno($nombre_dueno, $filtros);
                    $saldos = obtener_saldos_dueno($nombre_dueno);
                    responder_json(['exito' => true, 'rendiciones' => $rendiciones, 'saldos_dueno' => $saldos]);
                    break;

                case 'obtener':
                    $id_rendicion = $post['id_rendicion'] ?? '';
                    if (empty($id_rendicion)) {
                        responder_json(['exito' => false, 'error' => 'ID de rendición no especificado']);
                    }
                    $rendicion = obtener_rendicion_por_id($id_rendicion);
                    if ($rendicion) {
                        responder_json(['exito' => true, 'rendicion' => $rendicion]);
                    } else {
                        responder_json(['exito' => false, 'error' => 'Rendición no encontrada']);
                    }
                    break;

                case 'aceptar_ajuste':
                    $id_rendicion = $post['id_rendicion'] ?? '';
                    $id_cancelacion = $post['id_cancelacion'] ?? '';
                    $resultado = aceptar_ajuste_rendicion($id_rendicion, $id_cancelacion);
                    responder_json($resultado);
                    break;

                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de rendiciones no válida']);
            }
            break;

        case 'liquidaciones':
            switch ($subaccion) {
                case 'previsualizar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $resultado = previsualizar_liquidacion($nombre_dueno);
                    responder_json($resultado);
                    break;

                case 'confirmar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $monto_efectivo = $post['monto_efectivo'] ?? '0';
                    $monto_banco = $post['monto_banco'] ?? '0';
                    $observaciones = $post['observaciones'] ?? '';
                    $resultado = confirmar_liquidacion($nombre_dueno, $monto_efectivo, $monto_banco, $observaciones);
                    responder_json($resultado);
                    break;

                case 'listar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $filtros = [
                        'codigo' => $post['codigo'] ?? '',
                        'fecha_desde' => $post['fecha_desde'] ?? '',
                        'fecha_hasta' => $post['fecha_hasta'] ?? '',
                    ];
                    $liquidaciones = listar_liquidaciones_de_dueno($nombre_dueno, $filtros);
                    $saldos = obtener_saldos_dueno($nombre_dueno);
                    responder_json(['exito' => true, 'liquidaciones' => $liquidaciones, 'saldos_dueno' => $saldos]);
                    break;

                case 'obtener':
                    $id_liquidacion = $post['id_liquidacion'] ?? '';
                    if (empty($id_liquidacion)) {
                        responder_json(['exito' => false, 'error' => 'ID de liquidación no especificado']);
                    }
                    $liq = obtener_liquidacion_por_id($id_liquidacion);
                    if ($liq) {
                        responder_json(['exito' => true, 'liquidacion' => $liq]);
                    } else {
                        responder_json(['exito' => false, 'error' => 'Liquidación no encontrada']);
                    }
                    break;

                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de liquidaciones no válida']);
            }
            break;

        case 'cancelaciones':
            switch ($subaccion) {
                case 'listar':
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    if (empty($nombre_dueno)) {
                        responder_json(['exito' => false, 'error' => 'Dueño no especificado']);
                    }
                    $filtros = [
                        'terminal' => $post['terminal'] ?? '',
                    ];
                    $cancelaciones = listar_cancelaciones_de_dueno($nombre_dueno, $filtros);
                    responder_json(['exito' => true, 'cancelaciones' => $cancelaciones]);
                    break;

                case 'obtener':
                    $id_cancelacion = $post['id_cancelacion'] ?? '';
                    if (empty($id_cancelacion)) {
                        responder_json(['exito' => false, 'error' => 'ID de cancelación no especificado']);
                    }
                    $canc = obtener_cancelacion_por_id($id_cancelacion);
                    if ($canc) {
                        responder_json(['exito' => true, 'cancelacion' => $canc]);
                    } else {
                        responder_json(['exito' => false, 'error' => 'Cancelación no encontrada']);
                    }
                    break;

                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de cancelaciones no válida']);
            }
            break;

        case 'usuarios':
            switch ($subaccion) {
                case 'mi_perfil':
                    if ($nombre_solicitante === '') {
                        responder_json(['exito' => false, 'error' => 'Sin sesión activa']);
                    }
                    $perfil = obtener_perfil_usuario($nombre_solicitante);
                    if ($perfil) {
                        responder_json(['exito' => true, 'perfil' => $perfil]);
                    } else {
                        responder_json(['exito' => false, 'error' => 'Usuario no encontrado']);
                    }
                    break;

                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de usuarios no válida']);
            }
            break;

        case 'grafo':
            // Solo admin y soporte.
            $nombre_sol_gr = $post['nombre_solicitante'] ?? '';
            $raiz_sol_gr = Nodo::nodo_por_id('usuarios');
            $nodo_sol_gr = ($raiz_sol_gr && $nombre_sol_gr !== '') ? $raiz_sol_gr->adyacente($nombre_sol_gr) : null;
            $nodo_nivel_gr = $nodo_sol_gr ? $nodo_sol_gr->adyacente('nivel') : null;
            $nivel_sol_gr = $nodo_nivel_gr ? $nodo_nivel_gr->dato() : '';
            if (!in_array($nivel_sol_gr, ['admin', 'soporte'], true)) {
                responder_json(['exito' => false, 'error' => 'Permiso denegado']);
            }

            switch ($subaccion) {
                case 'resumen':
                    $resumen_gr = Controlador::ejecutar_comando('grafo:resumen');
                    responder_json(['exito' => true, 'resumen' => $resumen_gr]);
                    break;

                case 'resumen_credenciales':
                    // Solo en modo pruebas. El chequeo es del lado
                    // del servidor: en producción el endpoint no
                    // ejecuta nada, sin importar quién lo llame.
                    // Además hereda el chequeo de nivel del módulo
                    // `grafo` (admin o soporte).
                    if (!\Iteradores\Configuracion\Entorno::es_pruebas()) {
                        responder_json(['exito' => false, 'error' => 'Disponible solo en modo pruebas']);
                    }
                    $resumen_cred = en_grafo_credenciales_solo_lectura(function() {
                        return Controlador::ejecutar_comando('grafo:resumen');
                    });
                    if ($resumen_cred === null) {
                        responder_json(['exito' => false, 'error' => 'No se pudo cargar el grafo de credenciales']);
                    }
                    responder_json(['exito' => true, 'resumen' => $resumen_cred]);
                    break;

                case 'eliminar_huerfanos':
                    // Solo admin/soporte (chequeo heredado del módulo).
                    // Sin chequeo de es_pruebas: se usa en producción
                    // para limpiar la acumulación de nodos basura.
                    $resultado_huerfanos = Controlador::ejecutar_comando('grafo:eliminar_huerfanos');
                    if ($resultado_huerfanos === null) {
                        responder_json(['exito' => false, 'error' => 'No se pudo ejecutar la eliminación']);
                    }
                    guardar_ambos(ConfiguracionApli::NOMBRE_APP);
                    responder_json([
                        'exito' => true,
                        'eliminados' => $resultado_huerfanos['eliminados'],
                        'total_huerfanos' => $resultado_huerfanos['total_huerfanos'],
                    ]);
                    break;

                case 'listar':
                    $opciones_gr = [
                        'filtro' => $post['filtro'] ?? 'todos',
                        'enlace' => $post['enlace'] ?? '',
                        'texto' => $post['texto'] ?? '',
                        'offset' => (int)($post['offset'] ?? 0),
                        'limite' => (int)($post['limite'] ?? 50),
                    ];
                    $lista_gr = Controlador::ejecutar_comando('grafo:listar', $opciones_gr);
                    responder_json(['exito' => true, 'lista' => $lista_gr]);
                    break;

                case 'nodo':
                    $id_gr = $post['id'] ?? '';
                    if ($id_gr === '') {
                        responder_json(['exito' => false, 'error' => 'ID no especificado']);
                    }
                    $nodo_gr = Controlador::ejecutar_comando('grafo:nodo', $id_gr);
                    if (!$nodo_gr) {
                        responder_json(['exito' => false, 'error' => 'Nodo no encontrado']);
                    }
                    responder_json(['exito' => true, 'nodo' => $nodo_gr]);
                    break;

                case 'raices':
                    // Lista los nodos raíz (IDs especiales) con sus
                    // adyacentes directos.
                    $raices = Controlador::ejecutar_comando('grafo:raices');
                    responder_json(['exito' => true, 'raices' => $raices ?: []]);
                    break;

                case 'migraciones_listar':
                    // Lista el registro de migraciones con su estado.
                    if (!function_exists('registrar_comandos_migraciones')) {
                        responder_json(['exito' => false, 'error' => 'Módulo de migraciones no cargado.']);
                    }
                    $lista = Controlador::ejecutar_comando('app:migracion_listar');
                    if (!is_array($lista) || empty($lista['exito'])) {
                        $err = is_array($lista) && isset($lista['error']) ? $lista['error'] : 'Error al listar migraciones.';
                        responder_json(['exito' => false, 'error' => $err]);
                    }
                    // Si hubo auto-marcado, guardar.
                    if (!empty($lista['auto_marcadas'])) {
                        guardar_ambos(ConfiguracionApli::NOMBRE_APP);
                    }
                    responder_json(['exito' => true, 'migraciones' => $lista['migraciones']]);
                    break;

                case 'migracion_limpiar':
                    // Limpia el testigo persistente de una migración
                    // (o de todas) para que la auto-detección vuelva
                    // a correr. Fix v76y.
                    if (!function_exists('registrar_comandos_migraciones')) {
                        responder_json(['exito' => false, 'error' => 'Módulo de migraciones no cargado.']);
                    }
                    $id_lim = $post['id'] ?? '';
                    $res_lim = Controlador::ejecutar_comando('app:migracion_limpiar_marcadores', ['id' => $id_lim]);
                    if (!is_array($res_lim) || empty($res_lim['exito'])) {
                        $err = is_array($res_lim) && isset($res_lim['error']) ? $res_lim['error'] : 'Error al limpiar.';
                        responder_json(['exito' => false, 'error' => $err]);
                    }
                    guardar_ambos(ConfiguracionApli::NOMBRE_APP);
                    responder_json(['exito' => true, 'limpiados' => $res_lim['limpiados'] ?? 0]);
                    break;

                case 'migraciones_aplicar':
                    // Aplica una migración y guarda.
                    if (!function_exists('registrar_comandos_migraciones')) {
                        responder_json(['exito' => false, 'error' => 'Módulo de migraciones no cargado.']);
                    }
                    $id_mig = $post['id'] ?? '';
                    if ($id_mig === '') {
                        responder_json(['exito' => false, 'error' => 'Falta el id de la migración.']);
                    }
                    $res_mig = Controlador::ejecutar_comando('app:migracion_aplicar', ['id' => $id_mig]);
                    if (!is_array($res_mig) || empty($res_mig['exito'])) {
                        $err = is_array($res_mig) && isset($res_mig['error']) ? $res_mig['error'] : 'Error al aplicar.';
                        responder_json(['exito' => false, 'error' => $err]);
                    }
                    guardar_ambos(ConfiguracionApli::NOMBRE_APP);
                    responder_json(['exito' => true, 'detalles' => $res_mig['detalles'] ?? []]);
                    break;

                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de grafo no válida']);
            }
            break;

        case 'entorno':
            switch ($subaccion) {
                case 'info':
                    responder_json([
                        'exito' => true,
                        'modo' => \Iteradores\Configuracion\Entorno::modo(),
                        'es_pruebas' => \Iteradores\Configuracion\Entorno::es_pruebas(),
                    ]);
                    break;
                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de entorno no válida']);
            }
            break;

        default:
            responder_json(['exito' => false, 'error' => 'Módulo no reconocido']);
    }
}