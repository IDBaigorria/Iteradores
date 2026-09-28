<?php
/**
 * Migración v1.5piloto.68 — Eliminación de fichas médicas del grafo.
 *
 * La ficha de salud se reemplazó por la declaración jurada adjunta en
 * v1.5piloto.66/v67. Este script limpia los nodos `ficha_salud` que
 * hayan quedado huérfanos en los pasajeros, junto con sus hijos, y
 * elimina el enlace `mostrar_ficha_medica` de las opciones avanzadas
 * de los viajes.
 *
 * Es idempotente: si se corre dos veces, la segunda no hace nada.
 *
 * @package   Iteradores
 * @version   1.5piloto.68
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./miscelaneas/Arbol.php");

/**
 * Elimina un nodo ficha_salud con todos sus hijos.
 *
 * Los hijos de tipo string simple se eliminan directo. Las categorías
 * que en versiones antiguas eran listas (enfermedades, medicamentos,
 * impedimentos, alergias) pueden tener hijos en estructura árbol.
 *
 * @param Nodo $nodo_ficha
 * @return void
 */
function _eliminar_nodo_ficha_salud(Nodo $nodo_ficha): void {
    // Categorías que pueden tener estructura árbol (listas antiguas).
    $categorias_arbol = ['enfermedades', 'medicamentos', 'impedimentos', 'alergias'];
    foreach ($categorias_arbol as $cat) {
        $raiz_cat = $nodo_ficha->adyacente($cat);
        if (!$raiz_cat) continue;

        // Eliminar todos los hijos de la categoría.
        while ($hijo = eliminar_hmi($raiz_cat)) {
            Nodo::eliminar($hijo);
        }

        // Desenlazar y eliminar la raíz de la categoría.
        $nodo_ficha->eliminar_adyacente($cat);
        Nodo::eliminar($raiz_cat);
    }

    // Campos string simples.
    $campos_simples = ['grupo_sanguineo', 'obra_social', 'regimenes_comida', 'observaciones'];
    foreach ($campos_simples as $campo) {
        $nodo_campo = $nodo_ficha->adyacente($campo);
        if (!$nodo_campo) continue;
        $nodo_ficha->eliminar_adyacente($campo);
        Nodo::eliminar($nodo_campo);
    }

    // Otros campos por si hubiera alguno no previsto (defensivo).
    $adyacentes = (array) $nodo_ficha->adyacentes();
    foreach ($adyacentes as $nombre_enlace => $nodo_hijo) {
        $nodo_ficha->eliminar_adyacente($nombre_enlace);
        Nodo::eliminar($nodo_hijo);
    }
}

function migrar_fichas_medicas(): array {
    $res = [
        'duenos_procesados' => 0,
        'pasajeros_procesados' => 0,
        'fichas_eliminadas' => 0,
        'sin_ficha' => 0,
        'viajes_procesados' => 0,
        'mostrar_ficha_eliminados' => 0,
        'sin_mostrar_ficha' => 0,
    ];

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return $res;

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $res['duenos_procesados']++;

        // --- Pasajeros: eliminar ficha_salud ---
        $contenedor_pasajeros = $nodo_dueno->adyacente('pasajeros');
        if ($contenedor_pasajeros) {
            foreach ($contenedor_pasajeros->adyacentes() as $dni => $nodo_pasajero) {
                $res['pasajeros_procesados']++;

                $nodo_ficha = $nodo_pasajero->adyacente('ficha_salud');
                if (!$nodo_ficha) {
                    $res['sin_ficha']++;
                    continue;
                }

                _eliminar_nodo_ficha_salud($nodo_ficha);
                $nodo_pasajero->eliminar_adyacente('ficha_salud');
                Nodo::eliminar($nodo_ficha);
                $res['fichas_eliminadas']++;
            }
        }

        // --- Viajes: eliminar enlace mostrar_ficha_medica ---
        $contenedor_viajes = $nodo_dueno->adyacente('viajes');
        if ($contenedor_viajes) {
            foreach ($contenedor_viajes->adyacentes() as $nombre_viaje => $nodo_viaje) {
                $res['viajes_procesados']++;

                $nodo_opciones = $nodo_viaje->adyacente('opciones_avanzadas');
                if (!$nodo_opciones) {
                    $res['sin_mostrar_ficha']++;
                    continue;
                }

                $nodo_mfm = $nodo_opciones->adyacente('mostrar_ficha_medica');
                if (!$nodo_mfm) {
                    $res['sin_mostrar_ficha']++;
                    continue;
                }

                $nodo_opciones->eliminar_adyacente('mostrar_ficha_medica');
                Nodo::eliminar($nodo_mfm);
                $res['mostrar_ficha_eliminados']++;
            }
        }
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return $res;
}