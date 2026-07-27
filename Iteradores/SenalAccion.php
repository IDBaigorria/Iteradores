<?php
namespace Iteradores\Iteradores;

use Iteradores\Nucleo\Objeto;

/**
 * Señal de Acción – Comunica la intención de un Iterador.
 *
 * A diferencia de la {@link Senal}, que transporta matrices de identidad,
 * una Señal de Acción porta únicamente un **verbo** (número entero constante)
 * y la fase de origen. Este verbo indica al Iterador receptor qué operación
 * debe prepararse para ejecutar (por ejemplo, {@link \Iteradores\Configuracion\Conf::VERBO_APRENDER},
 * {@link \Iteradores\Configuracion\Conf::VERBO_EJECUTAR}, o el cierre
 * {@link \Iteradores\Configuracion\Conf::VERBO_CIERRE}).
 *
 * Las señales de acción son generadas y consumidas exclusivamente por la
 * {@link AntenaAccion}, que mantiene un registro de la acción actual en
 * cada fase. No contienen matrices ni se procesan por las antenas comunes
 * o de marcado.
 *
 * ## Propiedades
 * - `verbo` (int): constante de acción definida en {@link \Iteradores\Configuracion\Conf}.
 * - `fase_origen` (string): fase completa desde la que se emite la señal.
 *
 * @package Iteradores\Iteradores
 * @since 1.4.9
 * @version 1.4.9
 * @see AntenaAccion
 * @see \Iteradores\Configuracion\Conf
 */
class SenalAccion extends Objeto
{
    /**
     * Verbo de acción (constante entera).
     *
     * @var int
     */
    private int $verbo;

    /**
     * Fase completa desde la que fue emitida esta señal
     * (ej. `'Talamo:0'`).
     *
     * @var string
     */
    private string $fase_origen;

    /**
     * Constructor.
     *
     * @param int    $verbo       Verbo de acción (constante definida en {@link \Iteradores\Configuracion\Conf}).
     * @param string $fase_origen Fase desde la que se emite la señal (formato `dominio:numero`).
     */
    public function __construct(int $verbo, string $fase_origen)
    {
        $this->verbo       = $verbo;
        $this->fase_origen = $fase_origen;
    }

    /**
     * Devuelve el verbo de acción.
     *
     * @return int Verbo constante.
     * @since 1.4.9
     */
    public function verbo(): int
    {
        return $this->verbo;
    }

    /**
     * Devuelve la fase de origen de la señal.
     *
     * @return string Fase completa (ej. `'Talamo:0'`).
     * @since 1.4.9
     */
    public function fase_origen(): string
    {
        return $this->fase_origen;
    }
}