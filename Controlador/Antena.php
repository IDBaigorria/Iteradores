<?php
namespace Iteradores\Controlador;

use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Controlador\Senal;

/**
 * Antena: gestor del vocabulario (patrones) de una fase dentro de un dominio.
 *
 * Almacena un conjunto de patrones (NodoNumerico) y, ante una señal entrante,
 * intenta capturar la subsecuencia de matrices crudas más larga que coincida
 * exactamente con la secuencia de matrices de alguno de sus patrones.
 *
 * La captura es voraz y no modifica la señal salvo para avanzar el índice
 * de consumo; nunca inserta nuevas matrices en la señal.
 *
 * @package Iteradores\Controlador
 * @since 1.4.5
 * @version 1.4.5
 */
class Antena extends Objeto
{
    /**
     * Fase a la que pertenece esta antena.
     *
     * @var int
     */
    private int $_fase;

    /**
     * Lista de patrones registrados en esta antena.
     *
     * @var NodoNumerico[]
     */
    private array $_patrones;

    /**
     * Caché de las secuencias de matrices de cada patrón,
     * en el mismo orden que _patrones.
     *
     * @var Matriz2x2[][]
     */
    private array $_secuencias;

    /**
     * Constructor.
     *
     * @param int $fase Fase a la que pertenece la antena.
     */
    public function __construct(int $fase)
    {
        $this->_fase = $fase;
        $this->_patrones = [];
        $this->_secuencias = [];
    }

    /**
     * Registra un nodo numérico como patrón en esta antena.
     *
     * Verifica que el nodo tenga identidad en la fase de la antena
     * y precalcula su secuencia de matrices para acelerar las capturas.
     *
     * @param NodoNumerico $nodo Nodo a registrar como patrón.
     * @return void
     */
    public function _patron(NodoNumerico $nodo): void
    {
        // Validar que el nodo posea identidad en la fase de esta antena.
        if ($nodo->identidad()->es_igual(Matriz2x2::inicial())) {
            self::_error(
                "El nodo no tiene identidad en la fase {$this->_fase}."
            );
            return;
        }

        $secuencia = $nodo->secuencia_de_matrices();
        if (empty($secuencia)) {
            self::_error(
                "La secuencia de matrices del patrón está vacía. No se registra."
            );
            return;
        }

        $this->_patrones[] = $nodo;
        $this->_secuencias[] = $secuencia;
    }

    /**
     * Intenta capturar una porción de la señal usando el vocabulario de patrones.
     *
     * Recorre la porción no consumida de la señal y busca el prefijo más largo
     * que coincida exactamente con la secuencia de algún patrón (priorizando
     * las secuencias de mayor longitud). Si encuentra una coincidencia, consume
     * esa cantidad de matrices en la señal y retorna true.
     *
     * @param Senal $senal Señal sobre la que se intenta la captura.
     * @return bool true si se realizó una captura, false en caso contrario.
     */
    public function intentar_capturar(Senal $senal): bool
    {
        $no_consumidas = $senal->no_consumidas();
        $total = count($no_consumidas);

        if ($total === 0) {
            return false;
        }

        // Ordenar patrones por longitud de secuencia descendente.
        $indices = array_keys($this->_patrones);
        usort($indices, function ($a, $b) {
            return count($this->_secuencias[$b]) - count($this->_secuencias[$a]);
        });

        foreach ($indices as $idx) {
            $secuencia = $this->_secuencias[$idx];
            $longitud = count($secuencia);

            if ($longitud > $total) {
                continue;
            }

            // Comparar elemento a elemento con el prefijo de la señal.
            $coincide = true;
            for ($i = 0; $i < $longitud; $i++) {
                if (!$no_consumidas[$i]->es_igual($secuencia[$i])) {
                    $coincide = false;
                    break;
                }
            }

            if ($coincide) {
                $patron = $this->_patrones[$idx];
                $senal->consumir($longitud, $patron);
                return true;
            }
        }

        return false;
    }

    /**
     * Devuelve la fase de la antena.
     *
     * @return int
     */
    public function fase(): int
    {
        return $this->_fase;
    }

    /**
     * Devuelve la lista de patrones registrados.
     *
     * @return NodoNumerico[]
     */
    public function patrones(): array
    {
        return $this->_patrones;
    }
}