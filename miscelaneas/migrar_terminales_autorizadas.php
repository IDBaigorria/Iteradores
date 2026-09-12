<?php
/**
 * Migración v1.5piloto.31: convertir los enlaces de `terminales_autorizadas`
 * (que pueden ser nodos sueltos viejos con dato string, o Nodos Usuario
 * terminales directos) en nodos intermedios "TerminalViaje".
 *
 * Estructuras de entrada posibles:
 *
 *   A) Ya migrado:
 *      terminales_autorizadas
 *      └─ nombre_terminal → Nodo TerminalViaje
 *         └─ terminal → Nodo Usuario
 *
 *   B) Nodo Usuario terminal directo:
 *      terminales_autorizadas
 *      └─ nombre_terminal → Nodo Usuario (con enlace `nivel` = "terminal")
 *
 *   C) Nodo suelto viejo (formato del monolito v1.5piloto.12):
 *      terminales_autorizadas
 *      └─ nombre_terminal → Nodo con dato string = nombre de la terminal
 *
 * Salida (todas las estructuras válidas quedan como A):
 *   terminales_autorizadas
 *   └─ nombre_terminal → Nodo TerminalViaje (dato vacío)
 *      ├─ terminal → Nodo Usuario
 *      └─ cambiar_punto_predeterminado → "0"
 *
 * Idempotente. No rompe si se ejecuta varias veces.
 *
 * @package   Iteradores
 * @since     1.5piloto.31
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;

/**
 * Ejecuta la migración de terminales autorizadas.
 *
 * @return array Contadores de la operación.
 */
function migrar_terminales_autorizadas(): array {
    $contadores = [
        'duenos_procesados'          => 0,
        'viajes_procesados'          => 0,
        'terminales_migradas'        => 0,
        'terminales_sin_cambio'      => 0,
        'terminales_sin_nodo_usuario'=> 0,
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
        $contadores['duenos_procesados']++;

        $nodo_viajes = $nodo_dueno->adyacente('viajes');
        if (!$nodo_viajes) {
            continue;
        }

        $viajes = (array) $nodo_viajes->adyacentes();
        if (!$viajes) {
            continue;
        }

        foreach ($viajes as $nombre_viaje => $nodo_viaje) {
            $nodo_terminales = $nodo_viaje->adyacente('terminales_autorizadas');
            if (!$nodo_terminales) {
                continue;
            }

            $terminales = (array) $nodo_terminales->adyacentes();
            if (!$terminales) {
                continue;
            }

            $contadores['viajes_procesados']++;

            foreach ($terminales as $nombre_terminal => $nodo_terminal) {
                // Forzar string (bug de claves numéricas de PHP)
                $nombre_terminal = (string) $nombre_terminal;

                // Caso A: ya migrado
                if ($nodo_terminal->adyacente('terminal')) {
                    $contadores['terminales_sin_cambio']++;
                    continue;
                }

                // Resolver el Nodo Usuario terminal
                $nodo_usuario_terminal = null;

                // Caso B: el propio nodo es un Nodo Usuario terminal
                $nodo_nivel_nodo = $nodo_terminal->adyacente('nivel');
                if ($nodo_nivel_nodo && $nodo_nivel_nodo->dato() === 'terminal') {
                    $nodo_usuario_terminal = $nodo_terminal;
                } else {
                    // Caso C: nodo suelto viejo. Buscar el Nodo Usuario terminal
                    // por el nombre de la clave del contenedor.
                    $nodo_usuario_terminal = $raiz_usuarios->adyacente($nombre_terminal);
                }

                if (!$nodo_usuario_terminal) {
                    // No existe el usuario terminal: contar y saltear
                    $contadores['terminales_sin_nodo_usuario']++;
                    continue;
                }

                // Crear nodo intermedio TerminalViaje
                $nodo_terminal_viaje = Nodo::crear_con_dato('');
                $nodo_terminal_viaje->_adyacente_en($nodo_usuario_terminal, 'terminal');
                $nodo_terminal_viaje->_adyacente_en(Nodo::crear_con_dato('0'), 'cambiar_punto_predeterminado');

                // Reemplazar el enlace en el contenedor
                $nodo_terminales->_adyacente_en($nodo_terminal_viaje, $nombre_terminal, true);

                $contadores['terminales_migradas']++;
            }
        }
    }

    // Persistir solo si hubo cambios reales
    if ($contadores['terminales_migradas'] > 0) {
        Controlador::guardar(Conf::NOMBRE_APP);
    }

    return $contadores;
}