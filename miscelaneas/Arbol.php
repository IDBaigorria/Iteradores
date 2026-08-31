<?php
/**
 * Funciones para manejo de árbol general usando nodos.
 * Utiliza enlaces 'hmi' (hijo más izquierdo), 'hd' (hermano derecho) y 'p' (padre).
 *
 * @package   Iteradores
 * @since     1.5piloto.14
 */

use Iteradores\Nodos\Nodo;

/**
 * Agrega un hijo como hijo más izquierdo del nodo padre.
 *
 * @param Nodo $padre Nodo padre.
 * @param Nodo $hijo  Nodo hijo a agregar.
 * @return void
 */
function _hmi(Nodo $padre, Nodo $hijo): void {
    $antiguo_hmi = $padre->adyacente('hmi');
    if ($antiguo_hmi) {
        // El nuevo hijo toma al antiguo hmi como su hermano derecho (reemplaza si existía)
        $hijo->_adyacente_en($antiguo_hmi, 'hd', true);
    }
    // Reemplazar el hmi del padre con el nuevo hijo
    $padre->_adyacente_en($hijo, 'hmi', true);
    // Establecer el padre del hijo (reemplaza si ya tenía)
    $hijo->_adyacente_en($padre, 'p', true);
}

/**
 * Agrega un hermano derecho inmediato al nodo actual.
 *
 * @param Nodo $nodo_actual    Nodo al que se le agregará el hermano.
 * @param Nodo $nuevo_hermano  Nodo hermano a agregar.
 * @return void
 */
function _hd(Nodo $nodo_actual, Nodo $nuevo_hermano): void {
    $padre = $nodo_actual->adyacente('p');
    if (!$padre) return;

    $hermano_derecho_actual = $nodo_actual->adyacente('hd');
    if ($hermano_derecho_actual) {
        // El nuevo hermano apunta su hd al antiguo hermano derecho (reemplaza)
        $nuevo_hermano->_adyacente_en($hermano_derecho_actual, 'hd', true);
    }
    // El nodo actual apunta su hd al nuevo hermano (reemplaza)
    $nodo_actual->_adyacente_en($nuevo_hermano, 'hd', true);
    // El padre del nuevo hermano es el mismo padre del nodo actual (reemplaza)
    $nuevo_hermano->_adyacente_en($padre, 'p', true);
}

/**
 * Obtiene el hijo más izquierdo de un nodo.
 *
 * @param Nodo $nodo Nodo del que se obtiene el hijo.
 * @return Nodo|null Nodo hijo más izquierdo o null si no tiene.
 */
function hmi(Nodo $nodo): ?Nodo {
    return $nodo->adyacente('hmi');
}

/**
 * Obtiene el hermano derecho de un nodo.
 *
 * @param Nodo $nodo Nodo del que se obtiene el hermano.
 * @return Nodo|null Nodo hermano derecho o null si no tiene.
 */
function hd(Nodo $nodo): ?Nodo {
    return $nodo->adyacente('hd');
}

/**
 * Obtiene el padre de un nodo.
 *
 * @param Nodo $nodo Nodo del que se obtiene el padre.
 * @return Nodo|null Nodo padre o null si no tiene.
 */
function p(Nodo $nodo): ?Nodo {
    return $nodo->adyacente('p');
}

/**
 * Elimina el hijo más izquierdo de un nodo padre.
 * Si el hijo tiene hermano derecho, ese hermano se convierte en el nuevo hmi.
 *
 * @param Nodo $padre Nodo padre.
 * @return Nodo|null El nodo eliminado, o null si no había hijo.
 */
function eliminar_hmi(Nodo $padre): ?Nodo {
    $hijo = $padre->adyacente('hmi');
    if (!$hijo) return null;

    $hermano = $hijo->adyacente('hd');

    // Desligar el hijo de su padre y de su hermano
    $hijo->eliminar_adyacente('p');
    $hijo->eliminar_adyacente('hd');

    if ($hermano) {
        // El hermano pasa a ser el nuevo hmi del padre
        $padre->_adyacente_en($hermano, 'hmi', true);
        // El padre del hermano ya es el padre, no hace falta cambiarlo
    } else {
        // No hay más hijos, eliminar el enlace hmi del padre
        $padre->eliminar_adyacente('hmi');
    }

    return $hijo;
}

/**
 * Elimina el hermano derecho inmediato de un nodo.
 * Si el hermano derecho tiene a su vez hermano derecho, se enlaza correctamente.
 *
 * @param Nodo $nodo_actual Nodo cuyo hermano derecho se eliminará.
 * @return Nodo|null El nodo eliminado, o null si no tenía hermano derecho.
 */
function eliminar_hd(Nodo $nodo_actual): ?Nodo {
    $hermano = $nodo_actual->adyacente('hd');
    if (!$hermano) return null;

    $siguiente_hermano = $hermano->adyacente('hd');

    // Desligar el hermano de su padre y de su siguiente hermano
    $hermano->eliminar_adyacente('p');
    $hermano->eliminar_adyacente('hd');

    if ($siguiente_hermano) {
        // El nodo actual apunta ahora al siguiente hermano
        $nodo_actual->_adyacente_en($siguiente_hermano, 'hd', true);
    } else {
        // No hay más hermanos, eliminar el enlace hd del nodo actual
        $nodo_actual->eliminar_adyacente('hd');
    }

    return $hermano;
}