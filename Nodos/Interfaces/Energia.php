<?php
namespace Iteradores\Nodos\Interfaces;
/**
 * @package Iteradores\Nodos\Interfaces
 * @since V1.2.3
 */
interface Energia{
	/**
	 * Da energia al nodo para que este la sume a su energia
	 * 
	 * 
	 * @param int $cantidad_energia
	 * @return void
	 */
	public function _energia(int $cantidad_energia);

    /**
	 * Retorna el nivel de energia del nodo
	 * 
	 * @return int
	 */
	public function energia():int;

    //──────────────────────────────────────────────
    // Métodos de configuración de callbacks en Instancia
    //──────────────────────────────────────────────
    /**
	 * Asigna la funcion a ejecutar cuando el nodo se satura de energia
	 * @param callable $funcion
	 * @return void
	 */
	function _ejecutar_cuando_agota(callable $funcion);

    /**
	 * devuelve la funcion a ejecutar cuando se satura el nodo de energia
	 * @return callable|mixed
	 */
	function ejecutar_cuando_agota();
	/**
	 * Asigna la funcion a ejecutar cuando el nodo se satura de energia
	 * @param callable $funcion
	 * @return void
	 */
	function _ejecutar_cuando_satura(callable $funcion);

	/**
	 * devuelve la funcion a ejecutar cuando se satura el nodo de energia
	 * @return callable|mixed
	 */
	function ejecutar_cuando_satura();

	//──────────────────────────────────────────────
    // Métodos de configuración de callbacks en Clase 
    //──────────────────────────────────────────────
	/**
	 * Asigna la funcion a ejecutar cuando el nodo se satura de energia (por defecto para toda
	 * la fase)
	 * 
	 * esta funcion se va a ejecutar cuando se satura la energia de un nodo en una de las fases.
	 * si dicho nodo no tiene definida una funcion individual en la instancia, se buscara ejecutar
	 * ésta que está compartida por todos los nodos en la misma fase.
	 * @param callable $funcion
	 * @param string $fase (opcional) si no se pasa devuelve el de la fase actual
	 * @return void
	 */
	public static function _ejecutar_cuando_agota_por_fase(callable $funcion, string|null $fase): void;
		/**
	 * devuelve la funcion a ejecutar cuando el nodo se agota de energia (por defecto para toda
	 * la fase)
	 * 
	 * esta funcion se va a ejecutar cuando se agota la energia de un nodo en una de las fases.
	 * si dicho nodo no tiene definida una funcion individual en la instancia, se buscara ejecutar
	 * ésta que está compartida por todos los nodos en la misma fase.
	 * @param string $fase (opcional) si no se pasa devuelve el de la fase actual
	 * @return callable|null
	 */
	public static function ejecutar_cuando_agota_por_fase(string|null $fase): callable|null;
	
	/**
	 * Asigna la funcion a ejecutar cuando el nodo se satura de energia (por defecto para toda
	 * la fase)
	 * 
	 * esta funcion se va a ejecutar cuando se satura la energia de un nodo en una de las fases.
	 * si dicho nodo no tiene definida una funcion individual en la instancia, se buscara ejecutar
	 * ésta que está compartida por todos los nodos en la misma fase.
	 * @param callable $funcion
	 * @param string $fase (opcional) si no se pasa toma la fase actual
	 * @return void
	 */
	public static function _ejecutar_cuando_satura_por_fase(callable $funcion, string|null $fase): void;
	/**
	 * devuelve la funcion a ejecutar cuando el nodo se satura de energia (por defecto para toda
	 * la fase)
	 * 
	 * esta funcion se va a ejecutar cuando se satura la energia de un nodo en una de las fases.
	 * si dicho nodo no tiene definida una funcion individual en la instancia, se buscara ejecutar
	 * ésta que está compartida por todos los nodos en la misma fase.
	 * @param string $fase (opcional) si no se pasa devuelve el de la fase actual
	 * @return callable|null
	 */
	public static function ejecutar_cuando_satura_por_fase(string|null $fase): callable|null;

}