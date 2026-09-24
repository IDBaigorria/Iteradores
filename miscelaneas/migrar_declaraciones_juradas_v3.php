<?php
/**
 * Migración v1.5piloto.62d — Destino autocompletado y leyenda en Anexo II.
 *
 * Reemplaza en los textos guardados de las declaraciones juradas:
 *   - "a realizarse en [puntos]," por "a realizarse en {{DESTINO_VIAJE}},"
 *   - Agrega la leyenda "(tildar o marcar con una X)" al bloque de
 *     checkboxes padre/madre/tutor del Anexo II, si no la tiene.
 *
 * Solo actúa si encuentra el patrón exacto. Si el dueño personalizó
 * esas partes, no las toca. Es idempotente.
 *
 * @package   Iteradores
 * @version   1.5piloto.62d
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");

function migrar_declaraciones_juradas_v3(): array {
    $res = [
        'duenos_procesados' => 0,
        'viajes_procesados' => 0,
        'mayor_migrados' => 0,
        'mayor_sin_cambio' => 0,
        'menor_migrados' => 0,
        'menor_sin_cambio' => 0,
    ];

    // Fragmento de "a realizarse en" con los puntos del default.
    $buscar_destino_puntos = "a realizarse en ....................................................................................................................................................................................,";
    $reemplazo_destino_placeholder = "a realizarse en {{DESTINO_VIAJE}},";

    // Bloque de checkboxes padre/madre/tutor en el Anexo II.
    $buscar_checkboxes_menor = "☐ Padre  ☐ Madre  ☐ Tutor/a  ☐ Responsable\n\nautorizo al/la menor";
    $reemplazo_checkboxes_menor = "☐ Padre  ☐ Madre  ☐ Tutor/a  ☐ Responsable\n   (tildar o marcar con una X)\n\nautorizo al/la menor";

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return $res;

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $res['duenos_procesados']++;

        $nodo_viajes = $nodo_dueno->adyacente('viajes');
        if (!$nodo_viajes) continue;

        foreach ($nodo_viajes->adyacentes() as $nombre_viaje => $nodo_viaje) {
            $res['viajes_procesados']++;

            // --- Anexo I (mayor) ---
            $nodo_mayor = $nodo_viaje->adyacente('declaracion_jurada_mayor');
            if ($nodo_mayor) {
                $contenido = $nodo_mayor->dato();
                if (strpos($contenido, $buscar_destino_puntos) !== false) {
                    $contenido = str_replace($buscar_destino_puntos, $reemplazo_destino_placeholder, $contenido);
                    $nodo_mayor->_dato($contenido);
                    $res['mayor_migrados']++;
                } else {
                    $res['mayor_sin_cambio']++;
                }
            }

            // --- Anexo II (menor) ---
            $nodo_menor = $nodo_viaje->adyacente('declaracion_jurada_menor');
            if ($nodo_menor) {
                $contenido = $nodo_menor->dato();
                $cambio = false;

                if (strpos($contenido, $buscar_destino_puntos) !== false) {
                    $contenido = str_replace($buscar_destino_puntos, $reemplazo_destino_placeholder, $contenido);
                    $cambio = true;
                }
                if (strpos($contenido, $buscar_checkboxes_menor) !== false) {
                    $contenido = str_replace($buscar_checkboxes_menor, $reemplazo_checkboxes_menor, $contenido);
                    $cambio = true;
                }

                if ($cambio) {
                    $nodo_menor->_dato($contenido);
                    $res['menor_migrados']++;
                } else {
                    $res['menor_sin_cambio']++;
                }
            }
        }
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return $res;
}