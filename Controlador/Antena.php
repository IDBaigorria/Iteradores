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
 * @version 1.4.6
 */
class Antena extends Objeto
{
    /**
     * Fase a la que pertenece esta antena (ahora puede ser un string con prefijo de dominio).
     *
     * @var string
     */
    private string $fase;

    /**
     * Lista de patrones registrados en esta antena.
     *
     * @var NodoNumerico[]
     */
    private array $patrones;

    /**
     * Caché de las secuencias de matrices de cada patrón,
     * en el mismo orden que patrones.
     *
     * @var Matriz2x2[][]
     */
    private array $secuencias;

    /**
     * Constructor.
     *
     * @param string $fase Fase a la que pertenece la antena (puede ser un número o un string prefijado).
     */
    public function __construct(string $fase)
    {
        $this->fase = $fase;
        $this->patrones = [];
        $this->secuencias = [];
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
                "El nodo no tiene identidad en la fase {$this->fase}."
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

        $this->patrones[] = $nodo;
        $this->secuencias[] = $secuencia;
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
        $indices = array_keys($this->patrones);
        usort($indices, function ($a, $b) {
            return count($this->secuencias[$b]) - count($this->secuencias[$a]);
        });

        foreach ($indices as $idx) {
            $secuencia = $this->secuencias[$idx];
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
                $patron = $this->patrones[$idx];
                $senal->consumir($longitud, $patron);
                return true;
            }
        }

        return false;
    }

    /**
     * Devuelve la fase de la antena.
     *
     * @return string
     */
    public function fase(): string
    {
        return $this->fase;
    }

    /**
     * Devuelve la lista de patrones registrados.
     *
     * @return NodoNumerico[]
     */
    public function patrones(): array
    {
        return $this->patrones;
    }
}