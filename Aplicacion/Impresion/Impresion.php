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
    // Obtener datos del viaje y micro
    $viaje = $venta['viaje'] ?? '';
    $nombre_viaje = $venta['nombre_viaje_visible'] ?? $viaje;
    $fecha = $venta['fecha'] ?? '';
    $hora = $venta['hora'] ?? '';
    $origen = $venta['origen'] ?? '';
    $destino = $venta['destino'] ?? '';
    $empresa = $venta['empresa'] ?? '';
    $patente = $venta['patente'] ?? '';

    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head><meta charset="UTF-8"><title>Pasajes</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; }
        .pasaje { border: 1px solid #000; padding: 15px; margin-bottom: 20px; page-break-after: always; }
        h2 { margin-top: 0; }
        .info { margin-bottom: 5px; }
        @media print { .no-print { display: none; } }
    </style>';
    echo '</head><body>';

    foreach ($venta['asientos'] as $asiento) {
        $pasajero = $asiento['pasajero'] ?? null;
        echo '<div class="pasaje">';
        echo '<h2>Pasaje de viaje</h2>';
        echo '<div class="info"><strong>Viaje:</strong> ' . htmlspecialchars($nombre_viaje) . '</div>';
        echo '<div class="info"><strong>Fecha:</strong> ' . htmlspecialchars($fecha) . ' <strong>Hora:</strong> ' . htmlspecialchars($hora) . '</div>';
        echo '<div class="info"><strong>Origen:</strong> ' . htmlspecialchars($origen) . ' <strong>Destino:</strong> ' . htmlspecialchars($destino) . '</div>';
        echo '<div class="info"><strong>Empresa:</strong> ' . htmlspecialchars($empresa) . ' <strong>Patente:</strong> ' . htmlspecialchars($patente) . '</div>';
        echo '<div class="info"><strong>Asiento:</strong> ' . htmlspecialchars($asiento['numero']) . ' (Fila ' . htmlspecialchars($asiento['fila']) . ', Col ' . htmlspecialchars($asiento['columna']) . ')</div>';
        if ($pasajero) {
            echo '<div class="info"><strong>Pasajero:</strong> ' . htmlspecialchars($pasajero['nombre']) . '</div>';
            echo '<div class="info"><strong>DNI:</strong> ' . htmlspecialchars($pasajero['dni']) . '</div>';
        }
        echo '</div>';
    }

    echo '<script>window.onload = function() { window.print(); }</script>';
    echo '</body></html>';
}

function imprimir_cupon(array $venta): void {
    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head><meta charset="UTF-8"><title>Cupón de pago</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; }
        .cupon { border: 1px solid #000; padding: 20px; max-width: 400px; margin: 0 auto; }
        h2 { text-align: center; }
        .info { margin-bottom: 8px; }
        @media print { .no-print { display: none; } }
    </style>';
    echo '</head><body>';
    echo '<div class="cupon">';
    echo '<h2>Cupón de pago</h2>';
    echo '<div class="info"><strong>Venta:</strong> ' . htmlspecialchars($venta['id_venta']) . '</div>';
    echo '<div class="info"><strong>Fecha:</strong> ' . htmlspecialchars($venta['fecha']) . '</div>';
    echo '<div class="info"><strong>Viaje:</strong> ' . htmlspecialchars($venta['nombre_viaje_visible'] ?? $venta['viaje']) . '</div>';
    echo '<div class="info"><strong>Método de pago:</strong> ' . htmlspecialchars($venta['metodo_pago']) . '</div>';
    echo '<div class="info"><strong>Total:</strong> $' . htmlspecialchars($venta['total']) . '</div>';
    echo '<div class="info"><strong>Abonado:</strong> $' . htmlspecialchars($venta['pagado']) . '</div>';
    echo '<div class="info"><strong>Cuotas restantes:</strong> ' . htmlspecialchars($venta['cuotas_restantes']) . '</div>';
    if (!empty($venta['comprador'])) {
        echo '<div class="info"><strong>Comprador:</strong> ' . htmlspecialchars($venta['comprador']['nombre']) . '</div>';
        echo '<div class="info"><strong>DNI:</strong> ' . htmlspecialchars($venta['comprador']['dni']) . '</div>';
    }
    echo '</div>';
    echo '<script>window.onload = function() { window.print(); }</script>';
    echo '</body></html>';
}