<?php
namespace Iteradores\Iteradores;

use Iteradores\Iteradores\IteradorElectrico;
use Iteradores\Nodos\NodoNumerico;

/**
 * Itera sobre nodos numéricos y gestiona el ascenso de patrones.
 *
 * @package Iteradores
 * @since 1.5.0
 * @version 1.5.0
 */
class IteradorNumerico extends IteradorElectrico
{
    /**
     * Constructor.
     *
     * @param NodoNumerico $nodo Nodo numérico inicial.
     */
    public function __construct(NodoNumerico $nodo)
    {
        parent::__construct($nodo);
    }
    //********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- Interfaz de creación de nodos y validacion de elementos >
	//------------------------------------------------------------------------------->
    
    /**
     * Verifica si un elemento es válido para este iterador numérico.
     *
     * Solo acepta instancias de {@link NodoNumerico} (o subclases).
     *
     * @param mixed      $elemento Elemento a verificar.
     * @param bool|null &$es_nodo  Se establece en `true` si el elemento es un nodo numérico.
     * @return bool `true` si el elemento es un NodoNumerico, `false` en caso contrario.
     *
     * @since 1.5.0
     */
    public static function es_elemento_valido($elemento, &$es_nodo = null): bool
    {
        if ($elemento instanceof NodoNumerico) {
            $es_nodo = true;
            return true;
        }
        $es_nodo = false;
        return false;
    }

    /**
     * Crea un nodo numérico con el dato proporcionado.
     *
     * @param mixed $dato Dato a encapsular.
     * @return NodoNumerico Nueva instancia de NodoNumerico.
     *
     * @since 1.5.0
     */
    protected function crear_nodo_con_dato($dato): NodoNumerico
    {
        return NodoNumerico::nodo($dato);
    }
}