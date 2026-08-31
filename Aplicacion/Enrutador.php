<?php
/**
 * Enrutador central de peticiones POST.
 *
 * @package   Iteradores
 * @since     1.5piloto.1
 * @version   1.5piloto.4
 */

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

    switch ($modulo) {
        case 'autenticar':
            switch ($subaccion) {
                case 'verificar':
                    $codigo = $post['codigo'] ?? '';
                    $usuario = autenticar_por_codigo($codigo);
                    if ($usuario) {
                        responder_json(['exito' => true, 'usuario' => $usuario]);
                    } else {
                        responder_json(['exito' => false, 'error' => 'Código incorrecto']);
                    }
                    break;
                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de autenticación no válida']);
            }
            break;

        case 'administrador':
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
                    responder_json(['exito' => true, 'usuarios' => listar_usuarios()]);
                    break;

                case 'listar_duenos':
                    responder_json(['exito' => true, 'duenos' => listar_duenos()]);
                    break;

                case 'listar_sesiones':
                    responder_json(['exito' => true, 'sesiones' => listar_sesiones()]);
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
                    $terminales = json_decode($post['terminales'] ?? '[]', true);
                    if (!is_array($terminales)) $terminales = [];
                    $sesiones = listar_sesiones_de_usuarios($terminales);
                    responder_json(['exito' => true, 'sesiones' => $sesiones]);
                    break;

                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de dueño no válida']);
            }
            break;

        case 'sesiones':
            if ($subaccion === 'cerrar') {
                $token = $post['token'] ?? '';
                if ($token && cerrar_sesion($token)) {
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
                    responder_json(['exito' => true, 'usuario' => $usuario]);
                } else {
                    responder_json(['exito' => false, 'error' => 'Sesión no válida']);
                }
            } else {
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
                    $empresas = listar_empresas_de_dueno($nombre_dueno);
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
                    $vehiculos = listar_vehiculos_de_empresa($nombre_empresa);
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

                case 'agregar':
                    $resultado = agregar_viaje($post);
                    responder_json($resultado);
                    break;

                case 'editar':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $resultado = editar_viaje($nombre_viaje, $post);
                    responder_json($resultado);
                    break;

                case 'eliminar':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
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
                    $resultado = eliminar_micro_de_viaje($nombre_viaje, $nombre_micro, $nombre_dueno);
                    responder_json($resultado);
                    break;

                case 'agregar_terminal':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_terminal = $post['nombre_terminal'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
                    $resultado = agregar_terminal_autorizada($nombre_viaje, $nombre_terminal, $nombre_dueno);
                    responder_json($resultado);
                    break;

                case 'eliminar_terminal':
                    $nombre_viaje = $post['nombre_viaje'] ?? '';
                    $nombre_terminal = $post['nombre_terminal'] ?? '';
                    $nombre_dueno = $post['nombre_dueno'] ?? '';
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
                    $resultado = obtener_estados_asientos_micro($nombre_viaje, $nombre_micro, $nombre_dueno);
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
                    $pago_inicial = (int)($post['pago_inicial'] ?? 0);
                    $comprador_dni = $post['comprador_dni'] ?? '';
                    $pasajeros_json = $post['pasajeros'] ?? '[]';
                    $pasajeros_por_asiento = json_decode($pasajeros_json, true);
                    if (!is_array($pasajeros_por_asiento)) $pasajeros_por_asiento = [];

                    if (empty($nombre_terminal)) {
                        responder_json(['exito' => false, 'error' => 'Terminal no especificada']);
                    }
                    $resultado = confirmar_venta_actual($nombre_terminal, $metodo_pago, $cuotas, $pago_inicial, $comprador_dni, $pasajeros_por_asiento);
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

                case 'obtener':
                    $id_venta = $post['id_venta'] ?? '';
                    if (empty($id_venta)) {
                        responder_json(['exito' => false, 'error' => 'ID de venta no especificado']);
                    }
                    $venta = obtener_venta_por_id($id_venta);
                    if ($venta) {
                        responder_json(['exito' => true, 'venta' => $venta]);
                    } else {
                        responder_json(['exito' => false, 'error' => 'Venta no encontrada']);
                    }
                    break;

                case 'cancelar':
                    $id_venta = $post['id_venta'] ?? '';
                    if (empty($id_venta)) {
                        responder_json(['exito' => false, 'error' => 'ID de venta no especificado']);
                    }
                    $resultado = cancelar_venta($id_venta);
                    responder_json($resultado);
                    break;

                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de ventas no válida']);
            }
            break;

        case 'pasajeros':
            switch ($subaccion) {
                case 'listar':
                    responder_json(['exito' => true, 'pasajeros' => listar_pasajeros()]);
                    break;

                case 'buscar':
                    $termino = $post['termino'] ?? '';
                    responder_json(['exito' => true, 'pasajeros' => buscar_pasajeros($termino)]);
                    break;

                case 'obtener':
                    $dni = $post['dni'] ?? '';
                    if (empty($dni)) {
                        responder_json(['exito' => false, 'error' => 'DNI no especificado']);
                    }
                    $pasajero = obtener_pasajero_por_dni($dni);
                    if ($pasajero) {
                        responder_json(['exito' => true, 'pasajero' => $pasajero]);
                    } else {
                        responder_json(['exito' => false, 'error' => 'Pasajero no encontrado']);
                    }
                    break;

                case 'actualizar':
                    $dni = $post['dni'] ?? '';
                    if (empty($dni)) {
                        responder_json(['exito' => false, 'error' => 'DNI no especificado']);
                    }
                    $datos = [
                        'nombre' => $post['nombre'] ?? '',
                        'email' => $post['email'] ?? '',
                        'celular' => $post['celular'] ?? '',
                        'celular_emergencia' => $post['celular_emergencia'] ?? '',
                    ];
                    $resultado = actualizar_pasajero($dni, $datos);
                    responder_json($resultado);
                    break;

                default:
                    responder_json(['exito' => false, 'error' => 'Subacción de pasajeros no válida']);
            }
            break;
        default:
            responder_json(['exito' => false, 'error' => 'Módulo no reconocido']);
    }
}