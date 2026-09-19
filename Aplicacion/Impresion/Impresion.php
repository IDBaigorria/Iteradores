<?php
/**
 * Generación de HTML imprimible para pasajes y cupones de pago.
 *
 * @package   Iteradores
 * @since     1.5piloto.16
 * @version   1.5piloto.53
 */
use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./miscelaneas/Arbol.php");
include_once("./Aplicacion/Ventas/Venta.php");
include_once("./Aplicacion/Rendiciones/Rendicion.php");
function generar_impresion(string $tipo, string $id_venta, string $dni_filtro = '', string $numero_cupon = ''): void {
    if ($tipo === 'ficha_salud') {
        // Para ficha de salud, id_venta contendrá el nombre_dueno y dni_filtro el dni
        imprimir_ficha_salud($id_venta, $dni_filtro);
        return;
    }
    $venta = obtener_venta_por_id($id_venta);
    if (!$venta) {
        echo "Venta no encontrada";
        return;
    }
    if ($tipo === 'pasajes') {
        imprimir_pasajes($venta, $dni_filtro);
    } elseif ($tipo === 'cupon') {
        imprimir_cupon($venta, $numero_cupon);
    } else {
        echo "Tipo de impresión no válido";
    }
}

/**
 * Imprime el pasaje de un asiento reservado para el equipo (sin venta).
 *
 * A partir de v1.5piloto.38. Arma un array compatible con imprimir_pasajes
 * a partir del grafo (viaje, micro, asiento, pasajero) y lo imprime con el
 * flag es_reserva = true para que en lugar del código de venta aparezca
 * el bloque "EQUIPO".
 *
 * @param string $nombre_dueno
 * @param string $nombre_viaje
 * @param string $nombre_micro
 * @param string $fila
 * @param string $columna
 */
function imprimir_pasaje_reserva(string $nombre_dueno, string $nombre_viaje, string $nombre_micro, string $fila, string $columna): void {
    $nodo_viajes = obtener_contenedor_viajes_dueno($nombre_dueno);
    if (!$nodo_viajes) { echo "Dueño no encontrado"; return; }

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) { echo "Viaje no encontrado"; return; }

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) { echo "No hay micros en el viaje"; return; }

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) { echo "Micro no encontrado"; return; }

    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) { echo "No existe copia del vehículo"; return; }

    // Buscar el asiento por fila/columna
    $nodo_asiento = null;
    $nodo_asientos = $nodo_copia->adyacente('asientos');
    if ($nodo_asientos) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos->adyacente("piso_$i");
            if (!$piso) continue;
            $cabeza = $piso->adyacente('asientos');
            if (!$cabeza) continue;
            $actual = $cabeza->adyacente('primer');
            while ($actual && $actual->id() !== $cabeza->id()) {
                $f = $actual->adyacente('fila');
                $c = $actual->adyacente('columna');
                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {
                    $nodo_asiento = $actual;
                    break 2;
                }
                $actual = $actual->adyacente('siguiente');
            }
        }
    }

    if (!$nodo_asiento) { echo "Asiento no encontrado"; return; }

    $estado = $nodo_asiento->adyacente('estado');
    if (!$estado || $estado->dato() !== 'reservado') {
        echo "El asiento no está reservado para el equipo";
        return;
    }

    $nodo_pasajero = $nodo_asiento->adyacente('pasajero');
    if (!$nodo_pasajero) {
        echo "El asiento reservado no tiene pasajero asignado";
        return;
    }

    // Datos del pasajero
    $p_dni = $nodo_pasajero->dato();
    $p_apellido = $nodo_pasajero->adyacente('apellido') ? $nodo_pasajero->adyacente('apellido')->dato() : '';
    $p_nombres = $nodo_pasajero->adyacente('nombres') ? $nodo_pasajero->adyacente('nombres')->dato() : '';

    // Punto de subida/bajada
    $nodo_punto_sb = $nodo_asiento->adyacente('punto_subida_bajada');
    $nodo_hora_sb = $nodo_asiento->adyacente('hora_subida_bajada');

    // Datos del viaje
    $nombre_viaje_visible = $nodo_viaje->adyacente('nombre') ? $nodo_viaje->adyacente('nombre')->dato() : $nombre_viaje;
    $fecha_viaje_iso = $nodo_viaje->adyacente('fecha') ? $nodo_viaje->adyacente('fecha')->dato() : '';
    $hora_viaje = $nodo_viaje->adyacente('hora') ? $nodo_viaje->adyacente('hora')->dato() : '';
    $origen = $nodo_viaje->adyacente('origen') ? $nodo_viaje->adyacente('origen')->dato() : '';
    $destino = $nodo_viaje->adyacente('destino') ? $nodo_viaje->adyacente('destino')->dato() : '';

    // Datos del micro
    $micro_nombre_visible = $nodo_copia->adyacente('nombre') ? $nodo_copia->adyacente('nombre')->dato() : '';

    // Armar el array compatible con imprimir_pasajes
    $venta_simulada = [
        'nombre_viaje_visible' => $nombre_viaje_visible,
        'fecha' => formatear_fecha_visible($fecha_viaje_iso),
        'hora' => $hora_viaje,
        'origen' => $origen,
        'destino' => $destino,
        'micro_nombre_visible' => $micro_nombre_visible,
        'id_venta' => '',
        'asientos' => [
            [
                'numero' => $nodo_asiento->dato(),
                'pasajero' => [
                    'dni' => $p_dni,
                    'nombre_completo' => formatear_nombre_completo($p_apellido, $p_nombres),
                ],
                'punto_subida_bajada' => $nodo_punto_sb ? $nodo_punto_sb->dato() : null,
                'hora_subida_bajada' => $nodo_hora_sb ? $nodo_hora_sb->dato() : null,
            ]
        ]
    ];

    imprimir_pasajes($venta_simulada, '', true);
}

/**
 * Imprime los pasajes de una venta.
 *
 * Envoltorio de la API pública: abre el HTML, emite el cuerpo de la venta
 * y cierra el HTML. Si se necesita imprimir varias ventas en un solo HTML,
 * usar las funciones auxiliares _pasajes_html_inicio, _pasajes_venta_cuerpo
 * y _pasajes_html_fin.
 */
function imprimir_pasajes(array $venta, string $dni_filtro = '', bool $es_reserva = false): void {
    _pasajes_html_inicio();
    _pasajes_venta_cuerpo($venta, $dni_filtro, $es_reserva, false);
    _pasajes_html_fin(true);
}

/**
 * Emite el inicio del HTML de impresión de pasajes.
 *
 * Abre el doctype, el head con el CSS común y el body. La contraparte es
 * _pasajes_html_fin.
 */
function _pasajes_html_inicio(): void {
    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head><meta charset="UTF-8"><title>Pasajes</title>';
    echo '<style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            padding: 15px;
            background: white;
            color: black;
            font-size: 12px;
        }
        .pasaje {
            display: flex;
            align-items: stretch;
            background: white;
            border: 1px solid black;
            border-radius: 6px;
            overflow: hidden;
            break-inside: avoid;
            margin-bottom: 6px;
            width: 100%;
        }
        .logo-col {
            width: 225px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            border-right: 1px solid black;
        }
        .logo-col img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            filter: grayscale(100%);
        }
        .datos-col {
            flex: 1;
            padding: 10px 14px;
        }
        .datos-col h2 {
            color: black;
            margin: 0 0 6px 0;
            border-bottom: 1px solid black;
            padding-bottom: 4px;
            text-align: center;
            font-size: 17px;
        }
        .campo {
            background: white;
            border-radius: 4px;
            padding: 4px 8px;
            margin-bottom: 3px;
            font-size: 13px;
            border: 1px solid black;
        }
        .campo strong {
            display: inline-block;
            min-width: 80px;
            color: black;
        }
        .campo span {
            font-weight: 600;
            color: black;
        }
        .asiento {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: black;
            background: white;
            border-radius: 4px;
            padding: 6px;
            margin-top: 4px;
            border: 1px solid black;
        }
        .equipo-bloque {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: black;
            background: #f0f0f0;
            border-radius: 4px;
            padding: 8px;
            margin-top: 6px;
            margin-bottom: 6px;
            border: 2px solid black;
            letter-spacing: 3px;
        }
        .frase-final {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            letter-spacing: 1px;
            /*border-top: 1px dashed black;*/
            padding-top: 5px;
        }
        .separador-corte {
            display: block;
            text-align: center;
            margin: 8px 0;
            font-size: 14px;
            letter-spacing: 3px;
            color: black;
            break-inside: avoid;
            white-space: nowrap;
            overflow: hidden;
        }
        @media print {
            body { padding: 10px; background: white; }
            .pasaje { border: 1px solid black; box-shadow: none; margin-bottom: 5px; }
            .separador-corte { margin: 6px 0; }
        }
    </style>';
    echo '</head><body>';
}

/**
 * Emite el cierre del HTML de impresión de pasajes.
 *
 * @param bool $imprimir_al_cargar Si es true, agrega el script que dispara
 *                                 window.print() automáticamente al cargar.
 */
function _pasajes_html_fin(bool $imprimir_al_cargar = true): void {
    if ($imprimir_al_cargar) {
        echo '<script>window.onload = function() { window.print(); }</script>';
    }
    echo '</body></html>';
}

/**
 * Emite el cuerpo de los pasajes de UNA venta.
 *
 * @param array  $venta           Datos de la venta (mismo formato que usa imprimir_pasajes).
 * @param string $dni_filtro      Si no es vacío, solo imprime los asientos de ese DNI.
 * @param bool   $es_reserva      Si es true, muestra el bloque "EQUIPO" en lugar del código de venta.
 * @param bool   $separador_final Si es true, agrega un separador de corte después del último asiento
 *                                (para conectar con una venta siguiente cuando se imprimen varias juntas).
 */
function _pasajes_venta_cuerpo(array $venta, string $dni_filtro, bool $es_reserva, bool $separador_final = false): void {
    // Datos generales
    $nombre_viaje = $venta['nombre_viaje_visible'] ?? $venta['viaje'] ?? '';
    $fecha = $venta['fecha'] ?? '';
    $hora = $venta['hora'] ?? '';
    $origen = $venta['origen'] ?? '';
    $destino = $venta['destino'] ?? '';
    $micro_nombre_visible = $venta['micro_nombre_visible'] ?? '';
    $codigo_venta = $venta['id_venta'] ?? '';
    $logo_ruta = './Aplicacion/Logo.png';

    // Pre-filtrar los asientos que se van a imprimir. Así el conteo de "último"
    // y el separador de corte son coherentes cuando se filtra por DNI.
    $asientos_a_imprimir = [];
    foreach ($venta['asientos'] as $asiento) {
        $pasajero = $asiento['pasajero'] ?? null;
        if ($dni_filtro !== '' && (!$pasajero || $pasajero['dni'] !== $dni_filtro)) {
            continue;
        }
        $asientos_a_imprimir[] = $asiento;
    }

    $total_asientos = count($asientos_a_imprimir);
    $indice = 0;

    foreach ($asientos_a_imprimir as $asiento) {
        $pasajero = $asiento['pasajero'] ?? null;

        echo '<div class="pasaje">';
        echo '<div class="logo-col"><img src="' . htmlspecialchars($logo_ruta) . '" alt="Logo"></div>';
        echo '<div class="datos-col">';
        echo '<h2>Pasaje de viaje</h2>';

        echo '<div class="campo"><strong>Viaje:</strong> <span>' . htmlspecialchars($nombre_viaje) . '</span></div>';
        echo '<div class="campo"><strong>Fecha:</strong> <span>' . htmlspecialchars($fecha) . '</span> <strong>Hora:</strong> <span>' . htmlspecialchars($hora) . '</span></div>';
        echo '<div class="campo"><strong>Origen:</strong> <span>' . htmlspecialchars($origen) . '</span> <strong>Destino:</strong> <span>' . htmlspecialchars($destino) . '</span></div>';
        echo '<div class="campo"><strong>Micro:</strong> <span>' . htmlspecialchars($micro_nombre_visible) . '</span></div>';

        // Cod. de venta para pasajes vendidos; EQUIPO para reservas del equipo.
        if ($es_reserva) {
            echo '<div class="equipo-bloque">EQUIPO</div>';
        } else {
            echo '<div class="campo"><strong>Cod. de venta:</strong> <span>' . htmlspecialchars($codigo_venta) . '</span></div>';
        }

        echo '<div class="asiento">Asiento ' . htmlspecialchars($asiento['numero']) . '</div>';

        // Punto de subida/bajada: solo si existe y difiere del origen del viaje.
        // Si el pasajero sube/baja en el origen, no se muestra (el campo Origen ya lo cubre).
        // La hora estimada se muestra entre paréntesis si existe; si no, se indica
        // que está a confirmar.
        $punto_sb = $asiento['punto_subida_bajada'] ?? null;
        $hora_sb = $asiento['hora_subida_bajada'] ?? null;
        if ($punto_sb !== null && trim((string)$punto_sb) !== '' && trim((string)$punto_sb) !== trim((string)$origen)) {
            $texto_punto = $punto_sb;
            if ($hora_sb !== null && trim((string)$hora_sb) !== '') {
                $texto_punto .= ' (' . $hora_sb . ')';
            } else {
                $texto_punto .= ' (hora estimada a confirmar)';
            }
            echo '<div class="campo"><strong>Sube/baja en:</strong> <span>' . htmlspecialchars($texto_punto) . '</span></div>';
        }

        if ($pasajero) {
            $nombre_completo = $pasajero['nombre_completo']
                ?? formatear_nombre_completo($pasajero['apellido'] ?? '', $pasajero['nombres'] ?? '');
            echo '<div class="campo"><strong>Pasajero:</strong> <span>' . htmlspecialchars($nombre_completo) . '</span></div>';
            echo '<div class="campo"><strong>DNI:</strong> <span>' . htmlspecialchars(formatear_dni_con_puntos($pasajero['dni'])) . '</span></div>';
        }

        echo '<div class="frase-final">✨ ¡Compartamos en Comunidad! ✨</div>';
        echo '</div></div>';

        if ($indice < $total_asientos - 1 || $separador_final) {
            echo '<div class="separador-corte">✂ - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - ✂</div>';
        }

        $indice++;
    }
}

/**
 * Imprime los pasajes propios de un pasajero en todos sus viajes activos.
 *
 * A partir de v1.5piloto.39. Recorre las ventas del dueño, se queda con
 * aquellas donde:
 *   - El DNI aparece como pasajero en algún asiento-en-venta persistente
 *     (no cuenta si es solo comprador y no viaja).
 *   - El viaje asociado tiene fecha activa (posterior a hoy, o "a confirmar").
 *
 * Emite todos los pasajes propios en un solo HTML, con separadores de corte
 * entre asientos (incluso entre ventas).
 *
 * @param string $nombre_dueno
 * @param string $dni
 */
function imprimir_pasajes_actualizados(string $nombre_dueno, string $dni): void {
    $dni_norm = normalizar_dni($dni);
    if ($dni_norm === '') {
        echo "DNI inválido";
        return;
    }

    $ventas = _recolectar_ventas_con_pasajero_activo($nombre_dueno, $dni_norm);
    if (empty($ventas)) {
        echo "No hay pasajes activos para este pasajero";
        return;
    }

    _pasajes_html_inicio();

    $total_ventas = count($ventas);
    foreach ($ventas as $idx => $venta_arr) {
        $es_ultima = ($idx === $total_ventas - 1);
        // El separador de corte va después de cada asiento, salvo después del
        // último asiento de la última venta (ahí no tiene sentido).
        _pasajes_venta_cuerpo($venta_arr, $dni_norm, false, !$es_ultima);
    }

    _pasajes_html_fin(true);
}

/**
 * Recolecta las ventas activas de un dueño donde el DNI figura como pasajero.
 *
 * Devuelve un array de arrays, cada uno con el mismo formato que espera
 * _pasajes_venta_cuerpo (compatible con imprimir_pasajes). No incluye ventas
 * donde el DNI es solo comprador.
 *
 * @param string $nombre_dueno
 * @param string $dni_normalizado
 * @return array
 */
function _recolectar_ventas_con_pasajero_activo(string $nombre_dueno, string $dni_normalizado): array {
    $resultado = [];

    $contenedor_ventas = obtener_contenedor_ventas_dueno($nombre_dueno);
    if (!$contenedor_ventas) return $resultado;

    $actual = hmi($contenedor_ventas);
    while ($actual) {
        $id_venta = $actual->dato();
        $nodo_viaje = $actual->adyacente('viaje');
        $nodo_micro = $actual->adyacente('micro');

        // Chequear que el viaje sea activo
        $fecha_viaje_iso = ($nodo_viaje && $nodo_viaje->adyacente('fecha'))
            ? $nodo_viaje->adyacente('fecha')->dato()
            : '';
        if (!_fecha_viaje_es_activa($fecha_viaje_iso)) {
            $actual = hd($actual);
            continue;
        }

        // Recolectar asientos donde el DNI es pasajero
        $asientos_pasajero = [];
        $cabeza_asientos = $actual->adyacente('asientos');
        if ($cabeza_asientos) {
            $asiento_venta = $cabeza_asientos->adyacente('primer');
            $seguridad = 0;
            while ($asiento_venta && $seguridad < 200) {
                $nodo_pasajero = $asiento_venta->adyacente('pasajero');
                if ($nodo_pasajero && normalizar_dni($nodo_pasajero->dato()) === $dni_normalizado) {
                    $nodo_asiento_real = $asiento_venta->adyacente('asiento');
                    $numero_asiento = $nodo_asiento_real ? $nodo_asiento_real->dato() : '';
                    $p_apellido = $nodo_pasajero->adyacente('apellido') ? $nodo_pasajero->adyacente('apellido')->dato() : '';
                    $p_nombres = $nodo_pasajero->adyacente('nombres') ? $nodo_pasajero->adyacente('nombres')->dato() : '';
                    $nodo_punto = $asiento_venta->adyacente('punto_subida_bajada');
                    $nodo_hora = $asiento_venta->adyacente('hora_subida_bajada');

                    $asientos_pasajero[] = [
                        'numero' => $numero_asiento,
                        'pasajero' => [
                            'dni' => $nodo_pasajero->dato(),
                            'nombre_completo' => formatear_nombre_completo($p_apellido, $p_nombres),
                        ],
                        'punto_subida_bajada' => $nodo_punto ? $nodo_punto->dato() : null,
                        'hora_subida_bajada' => $nodo_hora ? $nodo_hora->dato() : null,
                    ];
                }
                $asiento_venta = $asiento_venta->adyacente('siguiente');
                $seguridad++;
            }
        }

        if (!empty($asientos_pasajero)) {
            // Datos visibles del viaje
            $nombre_viaje_visible = ($nodo_viaje && $nodo_viaje->adyacente('nombre'))
                ? $nodo_viaje->adyacente('nombre')->dato()
                : ($nodo_viaje ? $nodo_viaje->dato() : '');
            $hora_viaje = ($nodo_viaje && $nodo_viaje->adyacente('hora'))
                ? $nodo_viaje->adyacente('hora')->dato()
                : '';
            $origen = ($nodo_viaje && $nodo_viaje->adyacente('origen'))
                ? $nodo_viaje->adyacente('origen')->dato()
                : '';
            $destino = ($nodo_viaje && $nodo_viaje->adyacente('destino'))
                ? $nodo_viaje->adyacente('destino')->dato()
                : '';

            // Nombre visible del micro (desde la copia del vehículo)
            $micro_nombre_visible = '';
            if ($nodo_micro) {
                $copia = $nodo_micro->adyacente('vehiculo_copia');
                if ($copia && $copia->adyacente('nombre')) {
                    $micro_nombre_visible = $copia->adyacente('nombre')->dato();
                }
            }

            $resultado[] = [
                'nombre_viaje_visible' => $nombre_viaje_visible,
                'fecha' => formatear_fecha_visible($fecha_viaje_iso),
                'hora' => $hora_viaje,
                'origen' => $origen,
                'destino' => $destino,
                'micro_nombre_visible' => $micro_nombre_visible,
                'id_venta' => $id_venta,
                'asientos' => $asientos_pasajero,
            ];
        }

        $actual = hd($actual);
    }

    return $resultado;
}

function imprimir_cupon(array $venta, string $numero_cupon = ''): void {
    $codigo_venta = $venta['id_venta'] ?? '';
    // Usar nombre visible del viaje si está disponible, si no el identificador
    $nombre_viaje = $venta['viaje_visible'] ?? $venta['viaje'] ?? '';
    $metodo_pago = $venta['metodo_pago'] ?? '';
    $total = $venta['total'] ?? '0';
    $pagado = $venta['pagado'] ?? '0';
    $cuotas_restantes = $venta['cuotas_restantes'] ?? '0';
    $cuotas = $venta['cuotas'] ?? '1';
    $pendiente = number_format((float)$total - (float)$pagado, 2, '.', '');
    // Ahora $venta['fecha'] es la fecha de venta (ya formateada)
    $fecha_venta = $venta['fecha'] ?? '';
    $fecha_pago = $venta['fecha_pago'] ?? '';
    // Vendedor: nombre visible con fallback al nombre de usuario.
    $vendedor = $venta['terminal_nombre_real'] ?? $venta['terminal'] ?? '';
    $logo_ruta = './Aplicacion/LogoPeque.png';

    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head><meta charset="UTF-8"><title>Cupón de pago</title>';
    echo '<style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
            color: black;
            font-size: 12px;
        }
        .cupon {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid black;
            border-radius: 8px;
            background: white;
            overflow: hidden;
        }
        .membrete {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            border-bottom: 2px solid black;
            padding: 10px 15px;
        }
        .membrete img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            filter: grayscale(100%);
            margin-right: 15px;
        }
        .membrete-texto {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .contenido {
            padding: 20px;
        }
        .encabezado-cupon {
            text-align: center;
            margin-bottom: 20px;
        }
        .encabezado-cupon .titulo-cupon {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }
        .encabezado-cupon .nombre-viaje {
            font-size: 16px;
            font-weight: 600;
            margin-top: 5px;
        }
        .seccion {
            background: white;
            border: 1px solid black;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .seccion h3 {
            margin-top: 0;
            margin-bottom: 10px;
            border-bottom: 1px solid black;
            padding-bottom: 5px;
            font-size: 15px;
        }
        .fila {
            margin-bottom: 5px;
            font-size: 14px;
        }
        .fila strong {
            display: inline-block;
            min-width: 140px;
            color: black;
        }
        .fila span {
            font-weight: 600;
            color: black;
        }
        @media print {
            body { padding: 0; background: white; }
            .cupon { border: 2px solid black; box-shadow: none; }
            .seccion { border: 1px solid black; }
        }
    </style>';
    echo '</head><body>';

    echo '<div class="cupon">';
    echo '<div class="membrete">';
    echo '<img src="' . htmlspecialchars($logo_ruta) . '" alt="Logo">';
    echo '<div class="membrete-texto">Parroquia Nuestra Señora del Carmen - Tres Arroyos</div>';
    echo '</div>';

    echo '<div class="contenido">';

    // Encabezado con "Cupón de pago" y nombre del viaje
    echo '<div class="encabezado-cupon">';
    echo '<div class="titulo-cupon">Cupón de pago</div>';
    echo '<div class="nombre-viaje">' . htmlspecialchars($nombre_viaje) . '</div>';
    echo '</div>';

    // Sección 1: Datos de la venta
    echo '<div class="seccion">';
    echo '<h3>Datos de la venta</h3>';
    echo '<div class="fila"><strong>Cod. de Venta:</strong> <span>' . htmlspecialchars($codigo_venta) . '</span></div>';
    if (!empty($vendedor)) {
        echo '<div class="fila"><strong>Vendedor:</strong> <span>' . htmlspecialchars($vendedor) . '</span></div>';
    }
    if (!empty($fecha_venta)) {
        echo '<div class="fila"><strong>Fecha de venta:</strong> <span>' . htmlspecialchars($fecha_venta) . '</span></div>';
    }
    if (!empty($venta['comprador'])) {
        $comprador_nombre_completo = $venta['comprador']['nombre_completo']
            ?? formatear_nombre_completo($venta['comprador']['apellido'] ?? '', $venta['comprador']['nombres'] ?? '');
        echo '<div class="fila"><strong>Comprador:</strong> <span>' . htmlspecialchars($comprador_nombre_completo) . '</span></div>';
        echo '<div class="fila"><strong>DNI:</strong> <span>' . htmlspecialchars(formatear_dni_con_puntos($venta['comprador']['dni'])) . '</span></div>';
    }
    echo '</div>';

    // Sección 2: Datos del pago
    echo '<div class="seccion">';
    echo '<h3>Datos del pago</h3>';
    echo '<div class="fila"><strong>Fecha de pago:</strong> <span>' . htmlspecialchars($fecha_pago) . '</span></div>';
    echo '<div class="fila"><strong>Método de pago:</strong> <span>' . htmlspecialchars($metodo_pago) . '</span></div>';
    if (!empty($cuotas) && (int)$cuotas > 1) {
        echo '<div class="fila"><strong>Cuotas pactadas:</strong> <span>' . htmlspecialchars($cuotas) . '</span></div>';
    }
    if ($numero_cupon !== '') {
        $texto_cupon = 'Cuota ' . htmlspecialchars($numero_cupon);
        if (!empty($cuotas) && (int)$cuotas > 1) {
            $texto_cupon .= ' de ' . htmlspecialchars($cuotas);
        }
        echo '<div class="fila"><strong>Cuota:</strong> <span>' . $texto_cupon . '</span></div>';
    }
    echo '<div class="fila"><strong>Cantidad:</strong> <span>$' . htmlspecialchars(number_format((float)$pagado, 2, '.', '')) . '</span></div>';
    echo '<div class="fila"><strong>Total:</strong> <span>$' . htmlspecialchars(number_format((float)$total, 2, '.', '')) . '</span></div>';
    echo '<div class="fila"><strong>Total abonado:</strong> <span>$' . htmlspecialchars(number_format((float)$pagado, 2, '.', '')) . '</span></div>';
    echo '<div class="fila"><strong>Pendiente:</strong> <span>$' . htmlspecialchars($pendiente) . '</span></div>';
    echo '<div class="fila"><strong>Cuotas restantes:</strong> <span>' . htmlspecialchars($cuotas_restantes) . '</span></div>';
    echo '</div>';

    echo '</div>'; // cierre contenido
    echo '</div>'; // cierre cupon

    echo '<script>window.onload = function() { window.print(); }</script>';
    echo '</body></html>';
}

/**
 * Imprime la ficha de salud completa de un pasajero.
 */
function imprimir_ficha_salud(string $nombre_dueno, string $dni): void {
    $pasajero = obtener_pasajero_por_dni($nombre_dueno, $dni);
    if (!$pasajero) {
        echo "Pasajero no encontrado";
        return;
    }

    $ficha = $pasajero['ficha_salud'] ?? null;
    $logo_ruta = './Aplicacion/LogoPeque.png';

    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head><meta charset="UTF-8"><title>Ficha de salud</title>';
    echo '<style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
            color: black;
            font-size: 12px;
        }
        .ficha {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid black;
            border-radius: 8px;
            background: white;
            overflow: hidden;
        }
        .membrete {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            border-bottom: 2px solid black;
            padding: 10px 15px;
        }
        .membrete img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            filter: grayscale(100%);
            margin-right: 15px;
        }
        .membrete-texto {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .contenido {
            padding: 20px;
        }
        .encabezado {
            text-align: center;
            margin-bottom: 20px;
        }
        .encabezado h1 {
            margin: 0;
            font-size: 20px;
        }
        .seccion {
            border: 1px solid black;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .seccion h3 {
            margin-top: 0;
            margin-bottom: 10px;
            border-bottom: 1px solid black;
            padding-bottom: 5px;
            font-size: 15px;
        }
        .fila {
            margin-bottom: 5px;
            font-size: 14px;
        }
        .fila strong {
            display: inline-block;
            min-width: 140px;
        }
        .lista {
            margin-left: 20px;
            list-style-type: disc;
        }
        @media print {
            body { padding: 0; background: white; }
            .ficha { border: 2px solid black; box-shadow: none; }
        }
    </style>';
    echo '</head><body>';

    echo '<div class="ficha">';
    echo '<div class="membrete">';
    echo '<img src="' . htmlspecialchars($logo_ruta) . '" alt="Logo">';
    echo '<div class="membrete-texto">Parroquia Nuestra Señora del Carmen - Tres Arroyos</div>';
    echo '</div>';

    echo '<div class="contenido">';
    echo '<div class="encabezado"><h1>Ficha de salud</h1></div>';

    // Datos personales
    echo '<div class="seccion">';
    echo '<h3>Datos del pasajero</h3>';
    $nombre_completo = $pasajero['nombre_completo']
        ?? formatear_nombre_completo($pasajero['apellido'] ?? '', $pasajero['nombres'] ?? '');
    echo '<div class="fila"><strong>Nombre completo:</strong> ' . htmlspecialchars($nombre_completo) . '</div>';
    echo '<div class="fila"><strong>DNI:</strong> ' . htmlspecialchars(formatear_dni_con_puntos($pasajero['dni'])) . '</div>';
    if (!empty($pasajero['celular'])) echo '<div class="fila"><strong>Celular:</strong> ' . htmlspecialchars($pasajero['celular']) . '</div>';
    if (!empty($pasajero['celular_emergencia'])) echo '<div class="fila"><strong>Emergencia:</strong> ' . htmlspecialchars($pasajero['celular_emergencia']) . '</div>';
    if (!empty($pasajero['email'])) echo '<div class="fila"><strong>Email:</strong> ' . htmlspecialchars($pasajero['email']) . '</div>';
    if (!empty($pasajero['fecha_nacimiento'])) echo '<div class="fila"><strong>Fecha nacimiento:</strong> ' . htmlspecialchars(formatear_fecha_visible($pasajero['fecha_nacimiento'])) . '</div>';
    $dir = trim(($pasajero['direccion'] ?? '') . ', ' . ($pasajero['localidad'] ?? ''));
    if (!empty($dir)) echo '<div class="fila"><strong>Dirección:</strong> ' . htmlspecialchars($dir) . '</div>';
    echo '</div>';

    if ($ficha) {
        echo '<div class="seccion">';
        echo '<h3>Datos de salud</h3>';

        // Grupo sanguíneo
        if (!empty($ficha['grupo_sanguineo'])) {
            echo '<div class="fila"><strong>Grupo sanguíneo:</strong> ' . htmlspecialchars($ficha['grupo_sanguineo']) . '</div>';
        }
        // Obra social
        if (!empty($ficha['obra_social'])) {
            echo '<div class="fila"><strong>Obra social o prepaga (incluya numero de emergencias si corresponde):</strong> ' . htmlspecialchars($ficha['obra_social']) . '</div>';
        }
        // Alergias
        if (!empty($ficha['alergias'])) {
            echo '<div class="fila"><strong>Alergias:</strong> ' . htmlspecialchars($ficha['alergias']) . '</div>';
        }
        // Enfermedades
        if (!empty($ficha['enfermedades'])) {
            echo '<div class="fila"><strong>Enfermedades:</strong> ' . htmlspecialchars($ficha['enfermedades']) . '</div>';
        }
        // Medicamentos
        if (!empty($ficha['medicamentos'])) {
            echo '<div class="fila"><strong>Medicamentos:</strong> ' . htmlspecialchars($ficha['medicamentos']) . '</div>';
        }
        // Impedimentos
        if (!empty($ficha['impedimentos'])) {
            echo '<div class="fila"><strong>Impedimentos:</strong> ' . htmlspecialchars($ficha['impedimentos']) . '</div>';
        }
        // Regímenes especiales de comida
        if (!empty($ficha['regimenes_comida'])) {
            echo '<div class="fila"><strong>¿Sigue algún regimen especial de comida?:</strong> ' . htmlspecialchars($ficha['regimenes_comida']) . '</div>';
        }
        // Algún otro dato
        if (!empty($ficha['observaciones'])) {
            echo '<div class="fila"><strong>Algún otro dato que considere importante:</strong> ' . htmlspecialchars($ficha['observaciones']) . '</div>';
        }

        echo '</div>';
    } else {
        echo '<p>No hay ficha de salud registrada.</p>';
    }

    echo '</div>'; // cierre contenido
    echo '</div>'; // cierre ficha

    echo '<script>window.onload = function() { window.print(); }</script>';
    echo '</body></html>';
}

/**
 * Devuelve el nombre real de un usuario con fallback al nombre de
 * usuario si no tiene `nombre_real` cargado o no existe.
 *
 * @param string $nombre_usuario
 * @return string
 */
function _nombre_real_usuario(string $nombre_usuario): string {
    $raiz = Nodo::nodo_por_id('usuarios');
    if (!$raiz) return $nombre_usuario;
    $nodo = $raiz->adyacente($nombre_usuario);
    if (!$nodo) return $nombre_usuario;
    if ($nodo->adyacente('nombre_real')) {
        return $nodo->adyacente('nombre_real')->dato();
    }
    return $nombre_usuario;
}

/**
 * Recolecta las ventas que corresponden a un informe, con el formato
 * completo (formatear_venta_completa). Si el tipo es `terminal`,
 * filtra solo las ventas de esa terminal; si es `dueno`, todas las
 * del dueño.
 *
 * @param string $tipo `dueno` o `terminal`
 * @param string $nombre Nombre de usuario del dueño o terminal
 * @return array
 */
function _recolectar_ventas_para_informe(string $tipo, string $nombre): array {
    $ventas = [];
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return [];

    if ($tipo === 'terminal') {
        $nodo_terminal = $raiz_usuarios->adyacente($nombre);
        if (!$nodo_terminal) return [];
        $nodo_dueno = $nodo_terminal->adyacente('dueno');
        if (!$nodo_dueno) return [];
        $nombre_dueno = $nodo_dueno->dato();
        $contenedor = obtener_contenedor_ventas_dueno($nombre_dueno);
        if (!$contenedor) return [];
        $actual = hmi($contenedor);
        $seg = 0;
        while ($actual && $seg < 1000) {
            $venta = formatear_venta_completa($actual);
            if (($venta['terminal'] ?? '') === $nombre) {
                $ventas[] = $venta;
            }
            $actual = hd($actual);
            $seg++;
        }
    } else {
        $contenedor = obtener_contenedor_ventas_dueno($nombre);
        if (!$contenedor) return [];
        $actual = hmi($contenedor);
        $seg = 0;
        while ($actual && $seg < 1000) {
            $ventas[] = formatear_venta_completa($actual);
            $actual = hd($actual);
            $seg++;
        }
    }
    return $ventas;
}

/**
 * Imprime un informe de las ventas de la pestaña Vendidos, según los
 * filtros aplicados por el usuario. Incluye membrete, datos del
 * solicitante, filtros usados, tabla de saldos y detalle de ventas.
 *
 * Parámetros esperados en $params (todos por GET):
 *   - tipo_ventas: `dueno` o `terminal`.
 *   - nombre: nombre de usuario del dueño o terminal consultado.
 *   - viaje, vendedor, estado: filtros aplicados.
 *   - solicitante: nombre de usuario del que pide el informe.
 *   - solicitante_nombre: nombre visible del que pide el informe.
 *
 * @param array $params
 */
function imprimir_informe_ventas(array $params): void {
    $tipo_ventas = $params['tipo_ventas'] ?? 'dueno';
    $nombre = $params['nombre'] ?? '';
    $filtro_viaje = $params['viaje'] ?? 'todos';
    $filtro_vendedor = $params['vendedor'] ?? 'Todos';
    $filtro_estado = $params['estado'] ?? 'todos';
    $filtro_codigo = $params['codigo'] ?? '';
    $filtro_comprador = $params['comprador'] ?? '';
    $filtro_fecha_desde = $params['fecha_desde'] ?? '';
    $filtro_fecha_hasta = $params['fecha_hasta'] ?? '';
    $filtro_rendido = $params['rendido'] ?? 'todos';
    $solicitante = $params['solicitante'] ?? '';
    $solicitante_nombre = $params['solicitante_nombre'] ?? $solicitante;

    if ($nombre === '') { echo "Falta el nombre del dueño o terminal"; return; }

    $ventas = _recolectar_ventas_para_informe($tipo_ventas, $nombre);

    // Aplicar filtros.
    if ($filtro_viaje !== 'todos') {
        $ventas = array_filter($ventas, function($v) use ($filtro_viaje) {
            return ($v['viaje'] ?? '') === $filtro_viaje;
        });
    }
    if ($filtro_vendedor !== 'Todos') {
        $ventas = array_filter($ventas, function($v) use ($filtro_vendedor) {
            return ($v['terminal'] ?? '') === $filtro_vendedor;
        });
    }
    if ($filtro_estado !== 'todos') {
        $ventas = array_filter($ventas, function($v) use ($filtro_estado) {
            return ($v['estado_pago'] ?? '') === $filtro_estado;
        });
    }
    if ($filtro_codigo !== '') {
        $buscar_cod = strtolower(trim($filtro_codigo));
        $ventas = array_filter($ventas, function($v) use ($buscar_cod) {
            return strpos(strtolower($v['id_venta'] ?? ''), $buscar_cod) !== false;
        });
    }
    if ($filtro_comprador !== '') {
        $t = strtolower(trim($filtro_comprador));
        $t_dni = preg_replace('/\D+/', '', $filtro_comprador);
        $ventas = array_filter($ventas, function($v) use ($t, $t_dni) {
            $c = $v['comprador'] ?? null;
            if (!$c) return false;
            $nombre = strtolower(trim(
                ($c['nombre_completo'] ?? '') . ' ' .
                ($c['apellido'] ?? '') . ' ' .
                ($c['nombres'] ?? '')
            ));
            if ($nombre !== '' && $t !== '' && strpos($nombre, $t) !== false) return true;
            if ($t_dni !== '') {
                $dni = preg_replace('/\D+/', '', (string)($c['dni'] ?? ''));
                if ($dni !== '' && strpos($dni, $t_dni) !== false) return true;
            }
            return false;
        });
    }
    if ($filtro_fecha_desde !== '') {
        $ventas = array_filter($ventas, function($v) use ($filtro_fecha_desde) {
            $fi = $v['fecha_iso'] ?? '';
            return $fi !== '' && $fi >= $filtro_fecha_desde;
        });
    }
    if ($filtro_fecha_hasta !== '') {
        $ventas = array_filter($ventas, function($v) use ($filtro_fecha_hasta) {
            $fi = $v['fecha_iso'] ?? '';
            return $fi !== '' && $fi <= $filtro_fecha_hasta;
        });
    }
    if ($filtro_rendido === 'rendido') {
        $ventas = array_filter($ventas, function($v) {
            return (int)($v['cupones_sin_rendir'] ?? 0) === 0;
        });
    } elseif ($filtro_rendido === 'falta_rendir') {
        $ventas = array_filter($ventas, function($v) {
            return (int)($v['cupones_sin_rendir'] ?? 0) > 0;
        });
    }
    $ventas = array_values($ventas);

    // Nombres visibles de los filtros.
    $nombre_dueno_visible = _nombre_real_usuario($nombre);
    $nombre_vendedor_visible = ($filtro_vendedor === 'Todos') ? 'Todos' : _nombre_real_usuario($filtro_vendedor);
    $viaje_visible = ($filtro_viaje === 'todos') ? 'Todos' : $filtro_viaje;
    switch ($filtro_estado) {
        case 'pagado': $estado_visible = 'Pagado'; break;
        case 'cuotas_pendientes': $estado_visible = 'Cuotas pendientes'; break;
        default: $estado_visible = 'Todos';
    }

    $fecha_informe = date('d/m/Y H:i');
    $logo_ruta = './Aplicacion/LogoPeque.png';

    // Armar la tabla de saldos (misma lógica que el frontend).
    $por_terminal = [];
    foreach ($ventas as $v) {
        $t = $v['terminal'] ?? '';
        if (!isset($por_terminal[$t])) {
            $por_terminal[$t] = [
                'nombre' => $v['terminal_nombre_real'] ?? $t,
                'cantidad' => 0,
                'valor' => 0.0,
                'efectivo' => 0.0,
                'banco' => 0.0,
                'adeudan' => 0.0,
                'a_rendir' => 0.0,
            ];
        }
        $por_terminal[$t]['cantidad']++;
        $por_terminal[$t]['valor']     += (float)($v['total'] ?? 0);
        $por_terminal[$t]['efectivo']  += (float)($v['pagado_efectivo'] ?? 0);
        $por_terminal[$t]['banco']     += (float)($v['pagado_banco'] ?? 0);
        $por_terminal[$t]['adeudan']   += (float)($v['pendiente'] ?? 0);
        $por_terminal[$t]['a_rendir']  += (float)($v['a_rendir'] ?? 0);
    }
    usort($por_terminal, function($a, $b) {
        return strcasecmp($a['nombre'], $b['nombre']);
    });

    // HTML.
    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head><meta charset="UTF-8"><title>Informe de ventas</title>';
    echo '<style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
            color: black;
            font-size: 12px;
        }
        .informe {
            max-width: 1100px;
            margin: 0 auto;
        }
        .membrete {
            display: flex;
            align-items: center;
            border-bottom: 2px solid black;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .membrete img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            filter: grayscale(100%);
            margin-right: 15px;
        }
        .membrete-texto {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .titulo {
            text-align: center;
            margin-bottom: 20px;
        }
        .titulo h1 {
            margin: 0 0 6px 0;
            font-size: 22px;
        }
        .titulo .meta {
            font-size: 13px;
            color: #333;
        }
        .titulo .meta b {
            color: black;
        }
        .seccion {
            border: 1px solid black;
            border-radius: 6px;
            padding: 14px 16px;
            margin-bottom: 18px;
        }
        .seccion h2 {
            margin: 0 0 12px 0;
            border-bottom: 1px solid black;
            padding-bottom: 6px;
            font-size: 16px;
        }
        .filtros-lista {
            margin: 0;
            padding-left: 20px;
            font-size: 13px;
            line-height: 1.6;
        }
        .filtros-lista b {
            display: inline-block;
            min-width: 90px;
        }
        table.saldos {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        table.saldos th, table.saldos td {
            border: 1px solid black;
            padding: 6px 8px;
            text-align: left;
        }
        table.saldos th {
            background: #f0f0f0;
            font-weight: 700;
        }
        table.saldos td.num {
            text-align: right;
        }
        table.saldos tr.total {
            background: #f8f8f8;
            font-weight: 700;
        }
        .venta-card {
            border: 1px solid black;
            border-radius: 6px;
            margin-bottom: 12px;
            break-inside: avoid;
        }
        .venta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            border-bottom: 1px solid black;
            background: #f0f0f0;
        }
        .venta-header .id {
            font-weight: 700;
            font-size: 14px;
        }
        .venta-header .vendedor {
            font-size: 12px;
            font-weight: 600;
        }
        .venta-body {
            padding: 10px 14px;
            font-size: 12px;
            line-height: 1.5;
        }
        .venta-body .linea {
            margin-bottom: 3px;
        }
        .venta-body .linea b {
            display: inline-block;
            min-width: 120px;
        }
        .venta-body .totales {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px dashed #999;
        }
        .sin-datos {
            font-size: 13px;
            color: #666;
            text-align: center;
            padding: 12px;
        }
        @media print {
            body { padding: 10px; }
            .informe { max-width: 100%; }
            .venta-card { border: 1px solid black; box-shadow: none; }
        }
    </style>';
    echo '</head><body>';

    echo '<div class="informe">';

    // Membrete.
    echo '<div class="membrete">';
    echo '<img src="' . htmlspecialchars($logo_ruta) . '" alt="Logo">';
    echo '<div class="membrete-texto">Parroquia Nuestra Señora del Carmen - Tres Arroyos</div>';
    echo '</div>';

    // Título y datos.
    echo '<div class="titulo">';
    echo '<h1>Informe de ventas</h1>';
    echo '<div class="meta"><b>Solicitado por:</b> ' . htmlspecialchars($solicitante_nombre) . ' · <b>Fecha:</b> ' . htmlspecialchars($fecha_informe) . '</div>';
    echo '</div>';

    // Filtros.
    echo '<div class="seccion">';
    echo '<h2>Filtros aplicados</h2>';
    echo '<ul class="filtros-lista">';
    echo '<li><b>Dueño:</b> ' . htmlspecialchars($nombre_dueno_visible) . '</li>';
    echo '<li><b>Viaje:</b> ' . htmlspecialchars($viaje_visible) . '</li>';
    echo '<li><b>Vendedor:</b> ' . htmlspecialchars($nombre_vendedor_visible) . '</li>';
    echo '<li><b>Estado:</b> ' . htmlspecialchars($estado_visible) . '</li>';
    if ($filtro_codigo !== '') {
        echo '<li><b>Código:</b> ' . htmlspecialchars($filtro_codigo) . '</li>';
    }
    if ($filtro_comprador !== '') {
        echo '<li><b>Comprador:</b> ' . htmlspecialchars($filtro_comprador) . '</li>';
    }
    if ($filtro_fecha_desde !== '' || $filtro_fecha_hasta !== '') {
        $desde_txt = $filtro_fecha_desde !== '' ? formatear_fecha_visible($filtro_fecha_desde) : 'sin límite';
        $hasta_txt = $filtro_fecha_hasta !== '' ? formatear_fecha_visible($filtro_fecha_hasta) : 'sin límite';
        echo '<li><b>Fecha de compra:</b> ' . htmlspecialchars($desde_txt . ' a ' . $hasta_txt) . '</li>';
    }
    if ($filtro_rendido !== 'todos') {
        $rendido_txt = ($filtro_rendido === 'rendido') ? 'Rendido' : 'Falta rendir';
        echo '<li><b>Rendición:</b> ' . htmlspecialchars($rendido_txt) . '</li>';
    }
    echo '</ul>';
    echo '</div>';

    // Tabla de saldos.
    echo '<div class="seccion">';
    echo '<h2>Saldos</h2>';
    if (count($por_terminal) === 0) {
        echo '<p class="sin-datos">Sin ventas para los filtros seleccionados.</p>';
    } else {
        echo '<table class="saldos">';
        echo '<thead><tr>';
        echo '<th>Terminal</th>';
        echo '<th>Ventas</th>';
        echo '<th>Valor</th>';
        echo '<th>Efectivo</th>';
        echo '<th>Banco</th>';
        echo '<th>Le adeudan</th>';
        echo '<th>A rendir</th>';
        echo '<th>Total</th>';
        echo '</tr></thead><tbody>';

        $tot = ['cantidad'=>0, 'valor'=>0, 'efectivo'=>0, 'banco'=>0, 'adeudan'=>0, 'a_rendir'=>0];
        foreach ($por_terminal as $t) {
            $tot['cantidad'] += $t['cantidad'];
            $tot['valor'] += $t['valor'];
            $tot['efectivo'] += $t['efectivo'];
            $tot['banco'] += $t['banco'];
            $tot['adeudan'] += $t['adeudan'];
            $tot['a_rendir'] += $t['a_rendir'];
            $total_linea = $t['efectivo'] + $t['banco'];
            echo '<tr>';
            echo '<td>' . htmlspecialchars($t['nombre']) . '</td>';
            echo '<td class="num">' . $t['cantidad'] . '</td>';
            echo '<td class="num">$' . number_format($t['valor'], 2, '.', '') . '</td>';
            echo '<td class="num">$' . number_format($t['efectivo'], 2, '.', '') . '</td>';
            echo '<td class="num">$' . number_format($t['banco'], 2, '.', '') . '</td>';
            echo '<td class="num">$' . number_format($t['adeudan'], 2, '.', '') . '</td>';
            echo '<td class="num">$' . number_format($t['a_rendir'], 2, '.', '') . '</td>';
            echo '<td class="num">$' . number_format($total_linea, 2, '.', '') . '</td>';
            echo '</tr>';
        }

        // Fila de total: solo para admin/dueño.
        if ($tipo_ventas === 'dueno') {
            $total_general = $tot['efectivo'] + $tot['banco'];
            echo '<tr class="total">';
            echo '<td>TOTAL</td>';
            echo '<td class="num">' . $tot['cantidad'] . '</td>';
            echo '<td class="num">$' . number_format($tot['valor'], 2, '.', '') . '</td>';
            echo '<td class="num">$' . number_format($tot['efectivo'], 2, '.', '') . '</td>';
            echo '<td class="num">$' . number_format($tot['banco'], 2, '.', '') . '</td>';
            echo '<td class="num">$' . number_format($tot['adeudan'], 2, '.', '') . '</td>';
            echo '<td class="num">$' . number_format($tot['a_rendir'], 2, '.', '') . '</td>';
            echo '<td class="num">$' . number_format($total_general, 2, '.', '') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
    echo '</div>';

    // Detalle de ventas.
    echo '<div class="seccion">';
    echo '<h2>Detalle de ventas</h2>';
    if (count($ventas) === 0) {
        echo '<p class="sin-datos">Sin ventas para los filtros seleccionados.</p>';
    } else {
        foreach ($ventas as $v) {
            $vendedor_nombre = $v['terminal_nombre_real'] ?? $v['terminal'] ?? '';
            $comprador = $v['comprador'] ?? null;
            $viaje_txt = $v['viaje_visible'] ?? $v['viaje'] ?? '';
            $micro_txt = $v['micro_nombre_visible'] ?? $v['micro'] ?? '';
            $metodo_txt = ($v['metodo_pago'] ?? '') === 'transferencia' ? 'Transferencia' : 'Efectivo';
            $cuotas_txt = $v['cuotas'] ?? '1';
            $cuotas_restantes_txt = $v['cuotas_restantes'] ?? '0';

            $asientos_nums = [];
            foreach (($v['asientos'] ?? []) as $a) {
                $asientos_nums[] = $a['numero'] ?? '';
            }
            $asientos_txt = implode(', ', $asientos_nums);

            echo '<div class="venta-card">';
            echo '<div class="venta-header">';
            echo '<div class="id">Venta ' . htmlspecialchars($v['id_venta'] ?? '') . '</div>';
            echo '<div class="vendedor">' . htmlspecialchars($vendedor_nombre) . '</div>';
            echo '</div>';
            echo '<div class="venta-body">';

            echo '<div class="linea"><b>Viaje:</b> ' . htmlspecialchars($viaje_txt) . '</div>';
            echo '<div class="linea"><b>Micro:</b> ' . htmlspecialchars($micro_txt) . '</div>';
            echo '<div class="linea"><b>Fecha de compra:</b> ' . htmlspecialchars($v['fecha'] ?? '') . '</div>';

            if ($comprador) {
                $nombre_completo = $comprador['nombre_completo'] ?? '';
                $celular = $comprador['celular'] ?? '';
                $email = $comprador['email'] ?? '';
                $dir = trim((($comprador['direccion'] ?? '') . ', ' . ($comprador['localidad'] ?? '')), ', ');
                echo '<div class="linea"><b>Comprador:</b> ' . htmlspecialchars($nombre_completo) . '</div>';
                if ($celular !== '') echo '<div class="linea"><b>Celular:</b> ' . htmlspecialchars($celular) . '</div>';
                if ($email !== '') echo '<div class="linea"><b>Email:</b> ' . htmlspecialchars($email) . '</div>';
                if ($dir !== '') echo '<div class="linea"><b>Dirección:</b> ' . htmlspecialchars($dir) . '</div>';
            }

            if ($asientos_txt !== '') echo '<div class="linea"><b>Asientos:</b> ' . htmlspecialchars($asientos_txt) . '</div>';

            echo '<div class="totales">';
            echo '<div class="linea"><b>Total:</b> $' . htmlspecialchars($v['total'] ?? '0') . ' &nbsp;|&nbsp; <b>Abonado:</b> $' . htmlspecialchars($v['pagado'] ?? '0') . ' &nbsp;|&nbsp; <b>Pendiente:</b> $' . htmlspecialchars($v['pendiente'] ?? '0') . '</div>';
            echo '<div class="linea"><b>Método:</b> ' . htmlspecialchars($metodo_txt) . ' &nbsp;|&nbsp; <b>Cuotas:</b> ' . htmlspecialchars($cuotas_txt) . ' (' . htmlspecialchars($cuotas_restantes_txt) . ' restantes)</div>';
            echo '</div>';

            echo '</div>'; // cierre venta-body
            echo '</div>'; // cierre venta-card
        }
    }
    echo '</div>';

    echo '</div>'; // cierre informe

    echo '<script>window.onload = function() { window.print(); }</script>';
    echo '</body></html>';
}

/**
 * Imprime el informe completo de una rendición.
 *
 * Incluye membrete, datos generales, resumen, tabla por punto de
 * venta y detalle de los cupones rendidos. Si la rendición está
 * marcada como desactualizada, se agrega un aviso.
 *
 * @param string $id_rendicion
 */
function imprimir_informe_rendicion(string $id_rendicion): void {
    if ($id_rendicion === '') {
        echo "ID de rendición no especificado";
        return;
    }

    $r = obtener_rendicion_por_id($id_rendicion);
    if (!$r) {
        echo "Rendición no encontrada";
        return;
    }

    $nombre_dueno = $r['dueno'] ?? '';
    $nombre_dueno_visible = $nombre_dueno !== '' ? _nombre_real_usuario($nombre_dueno) : '—';
    $fecha_informe = date('d/m/Y H:i');
    $logo_ruta = './Aplicacion/LogoPeque.png';

    // Cartel de desactualizada.
    $aviso_desact = '';
    if (!empty($r['desactualizada'])) {
        $aviso_desact = '<div class="aviso-desactualizada"><strong>Rendición desactualizada.</strong> '
            . htmlspecialchars($r['motivo_desactualizada'] ?? '') . '</div>';
    }

    // Tabla por terminal.
    $terminales = $r['detalle_terminales'] ?? [];
    $terminales_html = '';
    foreach ($terminales as $t) {
        $nombre_term = $t['terminal_nombre_real'] !== '' ? $t['terminal_nombre_real'] : $t['terminal'];
        $terminales_html .= '<tr>';
        $terminales_html .= '<td>' . htmlspecialchars($nombre_term) . '</td>';
        $terminales_html .= '<td class="num">' . htmlspecialchars($t['cantidad_cupones']) . '</td>';
        $terminales_html .= '<td class="num">$' . htmlspecialchars($t['efectivo']) . '</td>';
        $terminales_html .= '<td class="num">$' . htmlspecialchars($t['banco']) . '</td>';
        $terminales_html .= '<td class="num"><b>$' . htmlspecialchars($t['total']) . '</b></td>';
        $terminales_html .= '</tr>';
    }
    if ($terminales_html === '') {
        $terminales_html = '<tr><td colspan="5" style="text-align:center;color:#666;">Sin datos</td></tr>';
    }

    // Tabla de cupones.
    $cupones = $r['detalle_cupones'] ?? [];
    $cupones_html = '';
    foreach ($cupones as $c) {
        $metodo = ($c['metodo_pago'] === 'transferencia') ? 'Transferencia' : 'Efectivo';
        $nombre_term_c = $c['terminal_nombre_real'] !== '' ? $c['terminal_nombre_real'] : $c['terminal'];
        $cupones_html .= '<tr>';
        $cupones_html .= '<td>' . htmlspecialchars($c['venta_id']) . '</td>';
        $cupones_html .= '<td class="num">' . htmlspecialchars($c['numero_cupon']) . '</td>';
        $cupones_html .= '<td class="num">$' . htmlspecialchars($c['monto']) . '</td>';
        $cupones_html .= '<td>' . htmlspecialchars($metodo) . '</td>';
        $cupones_html .= '<td>' . htmlspecialchars($nombre_term_c) . '</td>';
        $cupones_html .= '</tr>';
    }
    if ($cupones_html === '') {
        $cupones_html = '<tr><td colspan="5" style="text-align:center;color:#666;">Sin datos</td></tr>';
    }

    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head><meta charset="UTF-8"><title>Informe de rendición</title>';
    echo '<style>
        body { font-family: "Segoe UI", Arial, sans-serif; margin: 0; padding: 20px; background: white; color: black; font-size: 12px; }
        .informe { max-width: 1000px; margin: 0 auto; }
        .membrete { display: flex; align-items: center; border-bottom: 2px solid black; padding-bottom: 10px; margin-bottom: 20px; }
        .membrete img { width: 60px; height: 60px; object-fit: contain; filter: grayscale(100%); margin-right: 15px; }
        .membrete-texto { font-size: 18px; font-weight: bold; letter-spacing: 1px; }
        .titulo { text-align: center; margin-bottom: 20px; }
        .titulo h1 { margin: 0 0 6px 0; font-size: 22px; }
        .titulo .meta { font-size: 13px; color: #333; }
        .titulo .meta b { color: black; }
        .seccion { border: 1px solid black; border-radius: 6px; padding: 14px 16px; margin-bottom: 18px; }
        .seccion h2 { margin: 0 0 12px 0; border-bottom: 1px solid black; padding-bottom: 6px; font-size: 16px; }
        .datos-lista { margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.7; }
        .datos-lista b { display: inline-block; min-width: 110px; }
        .resumen-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
        .resumen-item { border: 1px solid #ccc; border-radius: 6px; padding: 10px; text-align: center; }
        .resumen-item span { display: block; font-size: 11px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .resumen-item b { display: block; font-size: 18px; }
        table.tabla { width: 100%; border-collapse: collapse; font-size: 12px; }
        table.tabla th, table.tabla td { border: 1px solid black; padding: 6px 8px; text-align: left; }
        table.tabla th { background: #f0f0f0; font-weight: 700; }
        table.tabla td.num { text-align: right; }
        .aviso-desactualizada { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; font-size: 13px; }
        .aviso-desactualizada strong { display: block; margin-bottom: 4px; }
        @media print { body { padding: 10px; } .informe { max-width: 100%; } }
    </style>';
    echo '</head><body>';

    echo '<div class="informe">';

    echo '<div class="membrete">';
    echo '<img src="' . htmlspecialchars($logo_ruta) . '" alt="Logo">';
    echo '<div class="membrete-texto">Parroquia Nuestra Señora del Carmen - Tres Arroyos</div>';
    echo '</div>';

    echo '<div class="titulo">';
    echo '<h1>Informe de rendición</h1>';
    echo '<div class="meta"><b>Código:</b> ' . htmlspecialchars($r['id_rendicion']) . ' · <b>Fecha:</b> ' . htmlspecialchars($r['fecha_hora']) . ' · <b>Impreso:</b> ' . htmlspecialchars($fecha_informe) . '</div>';
    echo '</div>';

    echo $aviso_desact;

    echo '<div class="seccion">';
    echo '<h2>Datos generales</h2>';
    echo '<ul class="datos-lista">';
    echo '<li><b>Dueño:</b> ' . htmlspecialchars($nombre_dueno_visible) . '</li>';
    echo '<li><b>Cupones rendidos:</b> ' . htmlspecialchars($r['cantidad_cupones']) . '</li>';
    echo '<li><b>Ventas incluidas:</b> ' . htmlspecialchars($r['cantidad_ventas']) . '</li>';
    echo '</ul>';
    echo '</div>';

    echo '<div class="seccion">';
    echo '<h2>Resumen</h2>';
    echo '<div class="resumen-grid">';
    echo '<div class="resumen-item"><span>Total</span><b>$' . htmlspecialchars($r['total']) . '</b></div>';
    echo '<div class="resumen-item"><span>Efectivo</span><b>$' . htmlspecialchars($r['total_efectivo']) . '</b></div>';
    echo '<div class="resumen-item"><span>Banco</span><b>$' . htmlspecialchars($r['total_banco']) . '</b></div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="seccion">';
    echo '<h2>Por punto de venta</h2>';
    echo '<table class="tabla">';
    echo '<thead><tr><th>Terminal</th><th>Cupones</th><th>Efectivo</th><th>Banco</th><th>Total</th></tr></thead>';
    echo '<tbody>' . $terminales_html . '</tbody>';
    echo '</table>';
    echo '</div>';

    echo '<div class="seccion">';
    echo '<h2>Cupones rendidos</h2>';
    echo '<table class="tabla">';
    echo '<thead><tr><th>Venta</th><th>Cupón</th><th>Monto</th><th>Método</th><th>Terminal</th></tr></thead>';
    echo '<tbody>' . $cupones_html . '</tbody>';
    echo '</table>';
    echo '</div>';

    echo '</div>'; // cierre informe

    echo '<script>window.onload = function() { window.print(); }</script>';
    echo '</body></html>';
}