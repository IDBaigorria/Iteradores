<?php
namespace Iteradores\Iteradores;

use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Matriz2x2;

/**
 * Señal – Portadora mínima de matrices de identidad.
 *
 * Encapsula una secuencia de {@link Matriz2x2} e indica la fase de origen
 * y el **tipo de antena** que la emitió. Esta información basta para que
 * cualquier antena receptora determine si la señal le pertenece
 * (común vs. marcado) y desde qué fase fue enviada.
 *
 * ## Campo `marcado`
 *
 * - `true`  : la señal fue emitida por una {@link AntenaDeMarcado}.
 * - `false` : la señal fue emitida por una {@link AntenaComun}.
 *
 * Las antenas receptoras comparan este valor con su propio tipo antes de
 * procesar la señal.
 *
 * ## Campo `fase_origen`
 *
 * Formato `dominio:numero_fase` (ej. `'Talamo:0'`). Es la fase completa
 * desde la que se emitió la señal. En recepción, este valor se utiliza
 * directamente como **par** para indexar los dipolos de la antena.
 *
 * ## Conversiones desde/hacia bytes
 *
 * Las traducciones byte ↔ matriz son responsabilidad exclusiva del
 * {@link \Iteradores\Controlador\Controlador} (a través de sus antenas
 * de traducción) y del Tálamo.
 *
 * @author Ignacio David Baigorria
 *
 * @package Iteradores\Iteradores
 * @since 1.4.5
 * @version 1.4.8
 */
class Senal extends Objeto
{
    /**
     * Lista de matrices que componen la señal.
     *
     * @var Matriz2x2[]
     */
    private array $matrices;

    /**
     * Fase completa desde la que fue emitida esta señal
     * (ej. `'Talamo:0'`).
     *
     * @var string
     * @since 1.4.8
     */
    private string $fase_origen;

    /**
     * Indica si la señal fue emitida por una Antena de Marcado.
     *
     * - `true`  → {@link AntenaDeMarcado}
     * - `false` → {@link AntenaComun}
     *
     * @var bool
     * @since 1.4.8
     */
    private bool $marcado;

    /**
     * Constructor.
     *
     * @param Matriz2x2[] $matrices    Matrices que transporta la señal.
     * @param string      $fase_origen Fase desde la que se emite (formato `dominio:numero`).
     * @param bool        $marcado     `true` si fue emitida por una Antena de Marcado,
     *                                 `false` si fue emitida por una Antena Común.
     */
    public function __construct(
        array $matrices = [],
        string $fase_origen = '',
        bool $marcado = false
    ) {
        $this->matrices    = $matrices;
        $this->fase_origen = $fase_origen;
        $this->marcado     = $marcado;
    }

    /**
     * Devuelve la cantidad de matrices contenidas.
     *
     * @return int
     * @since 1.4.5
     */
    public function longitud(): int
    {
        return count($this->matrices);
    }

    /**
     * Devuelve todas las matrices de la señal.
     *
     * @return Matriz2x2[]
     * @since 1.4.5
     */
    public function matrices(): array
    {
        return $this->matrices;
    }

    /**
     * Devuelve la fase de origen de la señal.
     *
     * @return string Fase completa (ej. `'Talamo:0'`).
     * @since 1.4.8
     */
    public function fase_origen(): string
    {
        return $this->fase_origen;
    }

    /**
     * Indica si la señal fue emitida por una Antena de Marcado.
     *
     * @return bool `true` para marcado, `false` para común.
     * @since 1.4.8
     */
    public function marcado(): bool
    {
        return $this->marcado;
    }
}