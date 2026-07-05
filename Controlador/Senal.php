<?php
namespace Iteradores\Controlador;

use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoNumerico;
/**
 * Señal: estructura ligera de comunicación entre dominios.
 *
 * Encapsula una sucesión de matrices de identidad (Matriz2x2) y mantiene
 * un índice de consumo que indica cuántas matrices crudas ya han sido
 * procesadas por las antenas.
 *
 * La señal es mutable; las capturas realizadas por Antena modifican la
 * misma instancia, avanzando el índice y registrando los patrones o
 * matrices consumidas.
 *
 * @package Iteradores
 * @since 1.4.5
 * @version 1.4.5
 */
class Senal extends Objeto
{
    /**
     * Lista original de matrices que componen la señal.
     *
     * @var Matriz2x2[]
     */
    private array $_matrices_crudas;

    /**
     * Cantidad de matrices crudas que ya han sido consumidas.
     *
     * @var int
     */
    private int $_indice_consumido;

    /**
     * Ítems ya procesados. Cada elemento puede ser:
     *   - Matriz2x2: cuando no fue capturada por ningún patrón.
     *   - NodoNumerico: cuando un patrón capturó una subsecuencia.
     *
     * @var array
     */
    private array $_elementos_procesados;

    /**
     * Constructor.
     *
     * @param Matriz2x2[] $matrices Crudas iniciales (opcional).
     */
    public function __construct(array $matrices = [])
    {
        $this->_matrices_crudas = $matrices;
        $this->_indice_consumido = 0;
        $this->_elementos_procesados = [];
    }

    /**
     * Añade una matriz al final de la señal cruda.
     *
     * @param Matriz2x2 $matriz
     * @return void
     */
    public function _matriz(Matriz2x2 $matriz): void
    {
        $this->_matrices_crudas[] = $matriz;
    }

    /**
     * Devuelve el total de matrices crudas (sin importar cuántas se han consumido).
     *
     * @return int
     */
    public function longitud_cruda(): int
    {
        return count($this->_matrices_crudas);
    }

    /**
     * Devuelve la cantidad de matrices crudas que aún no han sido consumidas.
     *
     * @return int
     */
    public function longitud_no_consumida(): int
    {
        return count($this->_matrices_crudas) - $this->_indice_consumido;
    }

    /**
     * Obtiene la porción no consumida de las matrices crudas.
     *
     * @return Matriz2x2[]
     */
    public function no_consumidas(): array
    {
        return array_slice(
            $this->_matrices_crudas,
            $this->_indice_consumido
        );
    }

    /**
     * Consume una cantidad de matrices crudas, registrando el patrón que las capturó.
     *
     * Si se proporciona un patrón (NodoNumerico), se añade ese nodo como un único
     * elemento procesado. En caso contrario, se añaden individualmente las matrices
     * consumidas como elementos procesados.
     *
     * @param int              $longitud Cantidad de matrices a consumir.
     * @param NodoNumerico|null $patron  Patrón que capturó la subsecuencia (o null).
     * @return void
     */
    public function consumir(int $longitud, ?NodoNumerico $patron = null): void
    {
        $disponibles = $this->longitud_no_consumida();
        if ($longitud > $disponibles) {
            self::_error(
                "No se pueden consumir {$longitud} matrices. Solo hay {$disponibles} disponibles."
            );
            return;
        }

        if ($patron !== null) {
            // Captura realizada por un patrón
            $this->_elementos_procesados[] = $patron;
        } else {
            // Sin patrón, se agregan las matrices crudas una a una
            $porcion = array_slice(
                $this->_matrices_crudas,
                $this->_indice_consumido,
                $longitud
            );
            foreach ($porcion as $matriz) {
                $this->_elementos_procesados[] = $matriz;
            }
        }

        $this->_indice_consumido += $longitud;
    }

    /**
     * Devuelve el índice actual de consumo.
     *
     * @return int
     */
    public function indice_consumido(): int
    {
        return $this->_indice_consumido;
    }

    /**
     * Devuelve todos los elementos procesados hasta el momento.
     *
     * @return array
     */
    public function obtener_elementos_procesados(): array
    {
        return $this->_elementos_procesados;
    }

    /**
     * Devuelve las matrices crudas completas (incluye las ya consumidas).
     *
     * Útil para auditoría o reconstrucción.
     *
     * @return Matriz2x2[]
     */
    public function obtener_crudas(): array
    {
        return $this->_matrices_crudas;
    }

    /**
     * Construye una nueva señal a partir de los elementos procesados.
     *
     * Las matrices crudas de la nueva señal serán las matrices de identidad
     * de cada elemento: para un patrón, su identidad_por_fase en la fase actual;
     * para una matriz suelta, ella misma.
     *
     * Este método se usará más adelante para envío entre dominios.
     *
     * @param int $fase Fase en la que se obtienen las matrices de identidad de los patrones.
     * @return Senal
     * @since 1.4.5
     * @todo Implementar una vez que el enrutamiento entre dominios esté activo.
     */
    public function generar_senal_de_salida(int $fase): Senal
    {
        $matrices_salida = [];
        foreach ($this->_elementos_procesados as $item) {
            if ($item instanceof NodoNumerico) {
                // TODO: validar que el nodo tenga identidad en la fase dada
                $matrices_salida[] = $item->identidad();
            } else {
                $matrices_salida[] = $item;
            }
        }
        return new self($matrices_salida);
    }
}