<?php
namespace Iteradores\Controlador;

use Iteradores\Nucleo\Objeto;
use Iteradores\Iteradores\SenalAccion;

/**
 * Antena de Traducción de Acción – Convierte verbos en señales de acción y viceversa.
 *
 * ## Responsabilidad
 * Actúa como puente entre el {@link \Iteradores\Controlador\Controlador} y el
 * sistema interno de {@link \Iteradores\Iteradores\SenalAccion}. No es multifase
 * ni mantiene estado; simplemente traduce valores enteros de verbo a objetos
 * {@link SenalAccion} y extrae el verbo de señales recibidas.
 *
 * Pertenece al ámbito del Controlador (namespace `Iteradores\Controlador`),
 * de forma análoga a {@link AntenaTraduccion} para señales matriciales.
 *
 * ## Constructor
 * Recibe un `origen` que se asignará como {@link SenalAccion::fase_origen}
 * en todas las señales que emita.
 *
 * ## Métodos
 * - {@link traducir_a_senal}: crea una {@link SenalAccion} a partir de un verbo.
 * - {@link traducir_a_verbo}: extrae el verbo de una {@link SenalAccion}.
 *
 * @package Iteradores\Controlador
 * @since 1.4.9
 * @version 1.4.9
 * @see SenalAccion
 * @see AntenaTraduccion
 */
class AntenaTraduccionAccion extends Objeto
{
    /**
     * Identificador de origen para las señales emitidas.
     *
     * @var string
     */
    private string $origen;

    /**
     * Constructor.
     *
     * @param string $origen Valor para {@link SenalAccion::fase_origen} de las señales emitidas.
     */
    public function __construct(string $origen)
    {
        $this->origen = $origen;
    }

    /**
     * Traduce un verbo entero a una señal de acción.
     *
     * @param int $verbo Constante de verbo (ej. {@link \Iteradores\Configuracion\Conf::VERBO_APRENDER}).
     * @return SenalAccion Señal de acción lista para ser enviada al sistema.
     * @since 1.4.9
     */
    public function traducir_a_senal(int $verbo): SenalAccion
    {
        return new SenalAccion($verbo, $this->origen);
    }

    /**
     * Extrae el verbo de una señal de acción.
     *
     * @param SenalAccion $senal Señal de acción recibida.
     * @return int Verbo contenido en la señal.
     * @since 1.4.9
     */
    public function traducir_a_verbo(SenalAccion $senal): int
    {
        return $senal->verbo();
    }
}