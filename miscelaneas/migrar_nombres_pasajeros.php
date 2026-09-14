<?php
/**
 * Migración v1.5piloto.36: separar el campo `nombre` del Nodo Pasajero
 * en dos enlaces: `nombres` y `apellido`.
 *
 * Estructura ANTES:
 *   Nodo Pasajero
 *   └─ nombre → "Juan Pérez"
 *
 * Estructura DESPUÉS:
 *   Nodo Pasajero
 *   ├─ nombres  → "Juan"
 *   └─ apellido → "Pérez"
 *
 * Algoritmo: se toma la última palabra como apellido y el resto como nombres.
 * Si el valor original tiene una sola palabra, se guarda como `nombres` y
 * `apellido` queda vacío (se corrige después editando el pasajero).
 *
 * Es idempotente: si el pasajero ya tiene `nombres` y `apellido`, se saltea.
 * No rompe si se ejecuta dos veces.
 *
 * @package   Iteradores
 * @since     1.5piloto.36
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;

/**
 * Ejecuta la migración de nombres de pasajeros.
 *
 * @return array Contadores de la operación.
 */
function migrar_nombres_pasajeros(): array {
    $contadores = [
        'duenos_procesados'    => 0,
        'pasajeros_procesados' => 0,
        'migrados'             => 0,
        'sin_cambio'           => 0,
        'sin_nombre'           => 0,
        'sin_apellido'         => 0,
    ];

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) {
        return $contadores;
    }

    $usuarios = (array) $raiz_usuarios->adyacentes();
    if (!$usuarios) {
        return $contadores;
    }

    foreach ($usuarios as $nombre_dueno => $nodo_dueno) {
        // Solo procesamos dueños
        $nodo_nivel = $nodo_dueno->adyacente('nivel');
        if (!$nodo_nivel || $nodo_nivel->dato() !== 'dueno') {
            continue;
        }

        $nodo_pasajeros = $nodo_dueno->adyacente('pasajeros');
        if (!$nodo_pasajeros) {
            continue;
        }

        $pasajeros = (array) $nodo_pasajeros->adyacentes();
        if (!$pasajeros) {
            continue;
        }

        $contadores['duenos_procesados']++;

        foreach ($pasajeros as $dni => $nodo_pasajero) {
            // Forzar string para evitar el bug de claves numéricas de PHP
            $dni = (string) $dni;
            $contadores['pasajeros_procesados']++;

            // Caso 1: ya migrado (tiene `nombres` y `apellido`)
            if ($nodo_pasajero->adyacente('nombres') && $nodo_pasajero->adyacente('apellido')) {
                $contadores['sin_cambio']++;
                continue;
            }

            // Caso 2: tiene `nombre` (formato viejo)
            $nodo_nombre = $nodo_pasajero->adyacente('nombre');
            if (!$nodo_nombre) {
                // Caso 3: sin `nombre` ni nuevos. Nada que hacer.
                $contadores['sin_nombre']++;
                continue;
            }

            $valor = trim((string) $nodo_nombre->dato());

            if ($valor === '') {
                // Nombre vacío: no hay datos útiles
                $nombres = '';
                $apellido = '';
            } else {
                $partes = preg_split('/\s+/', $valor);
                $cantidad = count($partes);

                if ($cantidad === 1) {
                    $nombres = $partes[0];
                    $apellido = '';
                    $contadores['sin_apellido']++;
                } else {
                    $apellido = array_pop($partes);
                    $nombres = implode(' ', $partes);
                }
            }

            // Escribir los dos enlaces nuevos
            $nodo_nombres = $nodo_pasajero->adyacente('nombres');
            if ($nodo_nombres) {
                $nodo_nombres->_dato($nombres);
            } else {
                $nodo_pasajero->_adyacente_en(Nodo::crear_con_dato($nombres), 'nombres');
            }

            $nodo_apellido = $nodo_pasajero->adyacente('apellido');
            if ($nodo_apellido) {
                $nodo_apellido->_dato($apellido);
            } else {
                $nodo_pasajero->_adyacente_en(Nodo::crear_con_dato($apellido), 'apellido');
            }

            // Eliminar el enlace viejo
            $nodo_pasajero->eliminar_adyacente('nombre');

            $contadores['migrados']++;
        }
    }

    // Persistir solo si hubo cambios reales
    if ($contadores['migrados'] > 0) {
        Controlador::guardar(Conf::NOMBRE_APP);
    }

    return $contadores;
}