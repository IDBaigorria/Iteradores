<?php
/**
 * Generación de HTML imprimible para pasajes y cupones de pago.
 *
 * @package   Iteradores
 * @since     1.5piloto.16
 */

function generar_impresion(string $tipo, string $id_venta): void {
    // Obtener datos completos de la venta
    $venta = obtener_venta_por_id($id_venta);
    if (!$venta) {
        echo "Venta no encontrada";
        return;
    }

    if ($tipo === 'pasajes') {
        imprimir_pasajes($venta);
    } elseif ($tipo === 'cupon') {
        imprimir_cupon($venta);
    } else {
        echo "Tipo de impresión no válido";
    }
}

function imprimir_pasajes(array $venta): void {
    // Datos generales
    $nombre_viaje = $venta['nombre_viaje_visible'] ?? $venta['viaje'] ?? '';
    $fecha = $venta['fecha'] ?? '';
    $hora = $venta['hora'] ?? '';
    $origen = $venta['origen'] ?? '';
    $destino = $venta['destino'] ?? '';
    $micro_nombre_visible = $venta['micro_nombre_visible'] ?? '';
    $codigo_venta = $venta['id_venta'] ?? '';
    $logo_ruta = './Aplicacion/Logo.png';

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

    $total_asientos = count($venta['asientos']);
    $indice = 0;

    foreach ($venta['asientos'] as $asiento) {
        $pasajero = $asiento['pasajero'] ?? null;
        echo '<div class="pasaje">';
        echo '<div class="logo-col"><img src="' . htmlspecialchars($logo_ruta) . '" alt="Logo"></div>';
        echo '<div class="datos-col">';
        echo '<h2>Pasaje de viaje</h2>';

        echo '<div class="campo"><strong>Viaje:</strong> <span>' . htmlspecialchars($nombre_viaje) . '</span></div>';
        echo '<div class="campo"><strong>Fecha:</strong> <span>' . htmlspecialchars($fecha) . '</span> <strong>Hora:</strong> <span>' . htmlspecialchars($hora) . '</span></div>';
        echo '<div class="campo"><strong>Origen:</strong> <span>' . htmlspecialchars($origen) . '</span> <strong>Destino:</strong> <span>' . htmlspecialchars($destino) . '</span></div>';
        echo '<div class="campo"><strong>Micro:</strong> <span>' . htmlspecialchars($micro_nombre_visible) . '</span></div>';
        echo '<div class="campo"><strong>Cod. de venta:</strong> <span>' . htmlspecialchars($codigo_venta) . '</span></div>';

        echo '<div class="asiento">Asiento ' . htmlspecialchars($asiento['numero']) . '</div>';

        if ($pasajero) {
            echo '<div class="campo"><strong>Pasajero:</strong> <span>' . htmlspecialchars($pasajero['nombre']) . '</span></div>';
            echo '<div class="campo"><strong>DNI:</strong> <span>' . htmlspecialchars($pasajero['dni']) . '</span></div>';
        }

        echo '<div class="frase-final">✨ ¡Compartamos en Comunidad! ✨</div>';
        echo '</div></div>';

        if ($indice < $total_asientos - 1) {
            echo '<div class="separador-corte">✂ - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - ✂</div>';
        }

        $indice++;
    }

    echo '<script>window.onload = function() { window.print(); }</script>';
    echo '</body></html>';
}

function imprimir_cupon(array $venta): void {
    $codigo_venta = $venta['id_venta'] ?? '';
    // Usar nombre visible del viaje si está disponible, si no el identificador
    $nombre_viaje = $venta['viaje_visible'] ?? $venta['viaje'] ?? '';
    $metodo_pago = $venta['metodo_pago'] ?? '';
    $total = $venta['total'] ?? '0';
    $pagado = $venta['pagado'] ?? '0';
    $cuotas_restantes = $venta['cuotas_restantes'] ?? '0';
    $pendiente = number_format((float)$total - (float)$pagado, 2, '.', '');
    // Ahora $venta['fecha'] es la fecha de venta (ya formateada)
    $fecha_venta = $venta['fecha'] ?? '';
    $fecha_pago = $venta['fecha_pago'] ?? '';
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
    if (!empty($fecha_venta)) {
        echo '<div class="fila"><strong>Fecha de venta:</strong> <span>' . htmlspecialchars($fecha_venta) . '</span></div>';
    }
    if (!empty($venta['comprador'])) {
        echo '<div class="fila"><strong>Comprador:</strong> <span>' . htmlspecialchars($venta['comprador']['nombre']) . '</span></div>';
        echo '<div class="fila"><strong>DNI:</strong> <span>' . htmlspecialchars($venta['comprador']['dni']) . '</span></div>';
    }
    echo '</div>';

    // Sección 2: Datos del pago
    echo '<div class="seccion">';
    echo '<h3>Datos del pago</h3>';
    echo '<div class="fila"><strong>Fecha de pago:</strong> <span>' . htmlspecialchars($fecha_pago) . '</span></div>';
    echo '<div class="fila"><strong>Método de pago:</strong> <span>' . htmlspecialchars($metodo_pago) . '</span></div>';
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