<?php
namespace Iteradores\Iteradores;

use Iteradores\Iteradores\Iterador;
use Iteradores\Nodos\NodoElectrico;

/**
 * Itera sobre nodos eléctricos en el grafo.
 *
 * @author Ignacio David Baigorria
 *
 * @package Iteradores
 * @since 1.5.0
 * @version 1.5.0
 */
class IteradorElectrico extends Iterador
{
    //********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- Interfaz de creación de nodos y validacion de elementos >
	//------------------------------------------------------------------------------->
    /**
     * Verifica si un elemento es válido para este iterador eléctrico.
     *
     * Solo acepta instancias de {@link NodoElectrico} (o subclases).
     *
     * @param mixed      $elemento Elemento a verificar.
     * @param bool|null &$es_nodo  Se establece en `true` si el elemento es un nodo eléctrico.
     * @return bool `true` si el elemento es un NodoElectrico, `false` en caso contrario.
     *
     * @since 1.5.0
     */
    public static function es_elemento_valido($elemento, &$es_nodo = null): bool
    {
        if ($elemento instanceof NodoElectrico) {
            $es_nodo = true;
            return true;
        }
        $es_nodo = false;
        return false;
    }

    /**
     * Crea un nodo eléctrico con el dato proporcionado.
     *
     * @param mixed $dato Dato a encapsular.
     * @return NodoElectrico Nueva instancia de NodoElectrico.
     *
     * @since 1.5.0
     */
    protected function crear_nodo_con_dato($dato): NodoElectrico
    {
        return NodoElectrico::nodo($dato);
    }

    /**
     * Constructor.
     *
     * @param NodoElectrico $nodo Nodo eléctrico inicial.
     */
    public function __construct(NodoElectrico $nodo)
    {
      //  parent::__construct($nodo);
    }

    // Métodos de iteración se implementarán aquí.
}