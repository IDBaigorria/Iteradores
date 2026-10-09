<?php
/**
 * Funciones para manejo de árbol general usando nodos.
 * Utiliza enlaces 'hmi' (hijo más izquierdo), 'hd' (hermano derecho) y 'p' (padre).
 *
 * @package   Iteradores
 * @since     1.5piloto.14
 * @version   1.5piloto.76v
 */

use Iteradores\Nodos\Nodo;

/**
 * Normaliza el array de nombres de enlace para las
 * operaciones de árbol. Si es null, devuelve los nombres
 * default (hmi, hd, p). Si viene, completa las claves
 * faltantes con los defaults.
 *
 * Fase B2.3.1 del modelo topológico (v76v): permite que
 * el mismo nodo participe en varios árboles a la vez,
 * usando nombres de enlace alternativos. Cada terminal
 * tendrá su propio juego (por ejemplo hmi_<terminal>,
 * hd_<terminal>, p_<terminal>).
 *
 * @param array|null $nombres ['p'=>..., 'hd'=>..., 'hmi'=>...]
 * @return array
 */
/**
 * Devuelve el array de nombres de enlace que un terminal
 * debe usar para recorrer el árbol de un contenedor, según
 * el contexto.
 *
 * Fase B2.3.2 del modelo topológico (v76w). Reglas:
 * - Si no hay contexto (dueño o admin), devuelve null
 *   → las funciones de árbol usan los nombres default.
 * - Si el contexto tiene el marcador `_es_compartido`,
 *   devuelve los nombres parametrizados con el sufijo del
 *   terminal (p_<terminal>, hd_<terminal>, hmi_<terminal>).
 * - Si el contexto no es un compartido (todavía), null.
 *
 * Mientras no existan compartidos marcados, esta función
 * siempre devuelve null y el comportamiento es idéntico al
 * actual. Los marcadores los agrega la migración de B2.3.3.
 *
 * @param Nodo|null $nodo_contexto
 * @param string    $nombre_terminal
 * @return array|null
 */
function _nombres_arbol_para_contexto(?Nodo $nodo_contexto, string $nombre_terminal): ?array {
    if ($nodo_contexto === null || $nombre_terminal === '') return null;
    if (!$nodo_contexto->adyacente('_es_compartido')) return null;
    return [
        'p'   => 'p_'   . $nombre_terminal,
        'hd'  => 'hd_'  . $nombre_terminal,
        'hmi' => 'hmi_' . $nombre_terminal,
    ];
}

function _arbol_nombres(?array $nombres): array {
    if ($nombres === null) {
        return ['p' => 'p', 'hd' => 'hd', 'hmi' => 'hmi'];
    }
    return [
        'p'   => isset($nombres['p'])   && $nombres['p']   !== '' ? (string)$nombres['p']   : 'p',
        'hd'  => isset($nombres['hd'])  && $nombres['hd']  !== '' ? (string)$nombres['hd']  : 'hd',
        'hmi' => isset($nombres['hmi']) && $nombres['hmi'] !== '' ? (string)$nombres['hmi'] : 'hmi',
    ];
}

/**
 * Agrega un hijo como hijo más izquierdo del nodo padre.
 *
 * @param Nodo $padre Nodo padre.
 * @param Nodo $hijo  Nodo hijo a agregar.
 * @return void
 */
function _hmi(Nodo $padre, Nodo $hijo, ?array $nombres = null): void {
    $n = _arbol_nombres($nombres);
    $antiguo_hmi = $padre->adyacente($n['hmi']);
    if ($antiguo_hmi) {
        // El nuevo hijo toma al antiguo hmi como su hermano derecho (reemplaza si existía)
        $hijo->_adyacente_en($antiguo_hmi, $n['hd'], true);
    }
    // Reemplazar el hmi del padre con el nuevo hijo
    $padre->_adyacente_en($hijo, $n['hmi'], true);
    // Establecer el padre del hijo (reemplaza si ya tenía)
    $hijo->_adyacente_en($padre, $n['p'], true);
}

/**
 * Agrega un hermano derecho inmediato al nodo actual.
 *
 * @param Nodo $nodo_actual    Nodo al que se le agregará el hermano.
 * @param Nodo $nuevo_hermano  Nodo hermano a agregar.
 * @return void
 */
function _hd(Nodo $nodo_actual, Nodo $nuevo_hermano, ?array $nombres = null): void {
    $n = _arbol_nombres($nombres);
    $padre = $nodo_actual->adyacente($n['p']);
    if (!$padre) return;

    $hermano_derecho_actual = $nodo_actual->adyacente($n['hd']);
    if ($hermano_derecho_actual) {
        // El nuevo hermano apunta su hd al antiguo hermano derecho (reemplaza)
        $nuevo_hermano->_adyacente_en($hermano_derecho_actual, $n['hd'], true);
    }
    // El nodo actual apunta su hd al nuevo hermano (reemplaza)
    $nodo_actual->_adyacente_en($nuevo_hermano, $n['hd'], true);
    // El padre del nuevo hermano es el mismo padre del nodo actual (reemplaza)
    $nuevo_hermano->_adyacente_en($padre, $n['p'], true);
}

/**
 * Obtiene el hijo más izquierdo de un nodo.
 *
 * @param Nodo $nodo Nodo del que se obtiene el hijo.
 * @return Nodo|null Nodo hijo más izquierdo o null si no tiene.
 */
function hmi(Nodo $nodo, ?array $nombres = null): ?Nodo {
    $n = _arbol_nombres($nombres);
    return $nodo->adyacente($n['hmi']);
}

/**
 * Obtiene el hermano derecho de un nodo.
 *
 * @param Nodo $nodo Nodo del que se obtiene el hermano.
 * @return Nodo|null Nodo hermano derecho o null si no tiene.
 */
function hd(Nodo $nodo, ?array $nombres = null): ?Nodo {
    $n = _arbol_nombres($nombres);
    return $nodo->adyacente($n['hd']);
}

/**
 * Obtiene el padre de un nodo.
 *
 * @param Nodo $nodo Nodo del que se obtiene el padre.
 * @return Nodo|null Nodo padre o null si no tiene.
 */
function p(Nodo $nodo, ?array $nombres = null): ?Nodo {
    $n = _arbol_nombres($nombres);
    return $nodo->adyacente($n['p']);
}

/**
 * Elimina el hijo más izquierdo de un nodo padre.
 * Si el hijo tiene hermano derecho, ese hermano se convierte en el nuevo hmi.
 *
 * @param Nodo $padre Nodo padre.
 * @return Nodo|null El nodo eliminado, o null si no había hijo.
 */
function eliminar_hmi(Nodo $padre, ?array $nombres = null): ?Nodo {
    $n = _arbol_nombres($nombres);
    $hijo = $padre->adyacente($n['hmi']);
    if (!$hijo) return null;

    $hermano = $hijo->adyacente($n['hd']);

    // Desligar el hijo de su padre y de su hermano
    $hijo->eliminar_adyacente($n['p']);
    $hijo->eliminar_adyacente($n['hd']);

    if ($hermano) {
        // El hermano pasa a ser el nuevo hmi del padre
        $padre->_adyacente_en($hermano, $n['hmi'], true);
        // El padre del hermano ya es el padre, no hace falta cambiarlo
    } else {
        // No hay más hijos, eliminar el enlace hmi del padre
        $padre->eliminar_adyacente($n['hmi']);
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
function eliminar_hd(Nodo $nodo_actual, ?array $nombres = null): ?Nodo {
    $n = _arbol_nombres($nombres);
    $hermano = $nodo_actual->adyacente($n['hd']);
    if (!$hermano) return null;

    $siguiente_hermano = $hermano->adyacente($n['hd']);

    // Desligar el hermano de su padre y de su siguiente hermano
    $hermano->eliminar_adyacente($n['p']);
    $hermano->eliminar_adyacente($n['hd']);

    if ($siguiente_hermano) {
        // El nodo actual apunta ahora al siguiente hermano
        $nodo_actual->_adyacente_en($siguiente_hermano, $n['hd'], true);
    } else {
        // No hay más hermanos, eliminar el enlace hd del nodo actual
        $nodo_actual->eliminar_adyacente($n['hd']);
    }

    return $hermano;
}