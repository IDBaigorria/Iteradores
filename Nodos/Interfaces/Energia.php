<?php
namespace Iteradores\Nodos\Interfaces;
/**
 * @package Iteradores\Nodos\Interfaces
 * @since V1.2.3
 */
interface Energia{
	// ================== GETTERS BÁSICOS ==================
    /**
     * Devuelve la capacidad máxima del nodo (unidades de energía).
     *
     * @return int
     */
    public function capacidad(): int;

    /**
     * Devuelve la fuga del nodo (unidades por ciclo de tiempo).
     *
     * @return float
     */
    public function fuga(): float;

    /**
     * Devuelve la energía actual del nodo en la fase activa,
     * aplicando previamente las fugas pendientes.
     *
     * @return int
     */
    public function energia(): int;

    // ================== MÉTODOS DE ENERGÍA ==================
    /**
     * Añade energía al nodo en la fase activa.
	 * 	 
	 * Primero aplica las fugas pendientes, luego suma la cantidad,
     * verifica saturación/agotamiento y ejecuta los callbacks correspondientes
     * según el modo de cada uno.
     *
     * @param int $cantidad_energia
     * @return void
     */
    public function _energia(int $cantidad_energia);


    //──────────────────────────────────────────────
    // Métodos de configuración de callbacks en Instancia
    //──────────────────────────────────────────────


    /**
     * Registra un callback para cuando el nodo se agota (por instancia).
     *
     * @param callable $funcion Callback que recibe (NodoElectrico $nodo)
     * @param bool $reemplazar Si es true (por defecto), este callback reemplaza al de fase.
     *                         Si es false, se ejecutan ambos: primero este, luego el de fase.
     * @return void
     */
    public function _ejecutar_cuando_agota(callable $funcion, bool $reemplazar = true);

    /**
     * Devuelve el callback de agotamiento de la instancia (fase actual) y si reemplaza o complementa.
     *
     * @return array{0: callable|null, 1: bool}
     */
    public function ejecutar_cuando_agota(): array;

    /**
     * Registra un callback para cuando el nodo se satura (por instancia).
     *
     * @param callable $funcion Callback que recibe (NodoElectrico $nodo)
     * @param bool $reemplazar Si es true (por defecto), este callback reemplaza al de fase.
     *                         Si es false, se ejecutan ambos: primero este, luego el de fase.
     * @return void
     */
    public function _ejecutar_cuando_satura(callable $funcion, bool $reemplazar = true);


    /**
     * Devuelve el callback de saturación de la instancia (fase actual) y si reemplaza o complementa.
     *
     * @return array{0: callable|null, 1: bool} [$callback, $reemplazar]
     */
    public function ejecutar_cuando_satura(): array;

	//──────────────────────────────────────────────
    // Métodos de configuración de callbacks en Clase 
    //──────────────────────────────────────────────
	// ================== CALLBACKS POR FASE (ESTÁTICOS) ==================
	
    /**
     * Registra un callback por defecto para saturación en una fase.
     *
     * @param callable $funcion
     * @param string|null $fase Si es null, se usa la fase actual.
     * @return void
     */
    public static function _ejecutar_cuando_satura_por_fase(callable $funcion, ?string $fase = null);

    /**
     * Obtiene el callback por defecto de saturación para una fase.
     *
     * @param string|null $fase
     * @return callable|null
     */
    public static function ejecutar_cuando_satura_por_fase(?string $fase = null);

    /**
     * Registra un callback por defecto para agotamiento en una fase.
     *
     * @param callable $funcion
     * @param string|null $fase
     * @return void
     */
    public static function _ejecutar_cuando_agota_por_fase(callable $funcion, ?string $fase = null);

    /**
     * Obtiene el callback por defecto de agotamiento para una fase.
     *
     * @param string|null $fase
     * @return callable|null
     */
    public static function ejecutar_cuando_agota_por_fase(?string $fase = null);

    // ================== CALLBACK GLOBAL (TODAS LAS FASES) ==================

    /**
     * Registra un callback para cuando **todas** las fases del nodo se quedan sin energía.
     *
     * @param callable $funcion
     * @return void
     */
    public static function _ejecutar_cuando_agota_global(callable $funcion);

    /**
     * Devuelve el callback global de agotamiento (si está registrado).
     *
     * @return callable|null
     */
    public static function ejecutar_cuando_agota_global();

}