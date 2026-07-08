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
 * intenta capturar la subsecuencia de matrices más larga que coincida exactamente
 * con la secuencia de alguno de sus patrones.
 *
 * A partir de la versión 1.4.7, la antena **no modifica la señal**. En su lugar
 * devuelve la longitud capturada y el patrón correspondiente. El avance del
 * índice de consumo y el registro de elementos procesados se trasladan al
 * {@link \Iteradores\Controlador\ProcesadorDeDominio} y al futuro Iterador.
 *
 * @package Iteradores\Controlador
 * @since 1.4.5
 * @version 1.4.7
 */
class Antena extends Objeto
{
    /**
     * Fase a la que pertenece esta antena (puede ser un string con prefijo de dominio).
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
     * @since 1.4.5
     */
    public function _patron(NodoNumerico $nodo): void
    {
        $secuencia = $nodo->secuencia_de_matrices();
        if (empty($secuencia)) {
            echo "FALLO al registrar patrón con p‑grama: " . json_encode($nodo->pgrama()) . "\n";
            self::_error("La secuencia de matrices del patrón está vacía. No se registra.");
            return;
        }
        echo "OK p‑grama: " . json_encode($nodo->pgrama()) . "\n";
        $this->patrones[] = $nodo;
        $this->secuencias[] = $secuencia;
    }

    /**
     * Intenta capturar una porción de la señal a partir de un índice dado.
     *
     * Busca, entre las matrices de la señal desde `indice_actual` en adelante,
     * el prefijo más largo que coincida exactamente con la secuencia de algún
     * patrón (priorizando las secuencias de mayor longitud). Si encuentra una
     * coincidencia, devuelve la longitud consumida y el patrón capturado.
     *
     * **No modifica la señal ni almacena estado de consumo.**
     *
     * @param Senal $senal         Señal sobre la que se intenta la captura.
     * @param int   $indice_actual Índice desde donde comenzar a buscar.
     * @return array{0: int, 1: NodoNumerico|null} [longitud consumida, patrón capturado].
     *         Si no hubo captura, la longitud es 0 y el patrón es null.
     * @since 1.4.5
     * @version 1.4.7
     */
    public function intentar_capturar(Senal $senal, int $indice_actual): array
    {
        $matrices = $senal->matrices();
        $porcion = array_slice($matrices, $indice_actual);
        $total = count($porcion);

        if ($total === 0) {
            return [0, null];
        }

        // Ordenar patrones por longitud de secuencia descendente.
        $indices = array_keys($this->patrones);
        usort($indices, function ($a, $b) {
            return count($this->secuencias[$b]) - count($this->secuencias[$a]);
        });

        $ultimo_indice = count($this->patrones) - 1;

        foreach ($indices as $idx) {
            // Ignorar el último patrón registrado (evita auto‑captura durante aprendizaje)
            if ($idx === $ultimo_indice) {
                continue;
            }

            $secuencia = $this->secuencias[$idx];
            $longitud = count($secuencia);

            if ($longitud > $total) {
                continue;
            }

            // Comparar elemento a elemento con el prefijo de la señal.
            $coincide = true;
            for ($i = 0; $i < $longitud; $i++) {
                if (!$porcion[$i]->es_igual($secuencia[$i])) {
                    $coincide = false;
                    break;
                }
            }

            if ($coincide) {
                return [$longitud, $this->patrones[$idx]];
            }
        }

        return [0, null];
    }

    /**
     * Emite una señal a partir de una lista de p‑gramas registrados en esta antena.
     *
     * Busca cada p‑grama en el vocabulario, concatena las secuencias de matrices
     * de todos los patrones encontrados y devuelve una nueva señal con el resultado.
     * Si algún p‑grama no está registrado, la emisión falla y retorna null.
     *
     * El aprendizaje trivial asegura que todo byte de entrada tenga su patrón
     * elemental en fase 0, por lo que cualquier p‑grama bien formado podrá
     * ser traducido sin intervención adicional.
     *
     * @param array[] $pgramas Lista de p‑gramas a emitir (cada uno es un array de enteros).
     * @return Senal|null Señal emitida o null si algún p‑grama no está registrado.
     * @since 1.4.7
     */
    public function emitir(array $pgramas): ?Senal
    {
        $matrices = [];

        foreach ($pgramas as $pgrama) {
            $encontrado = false;
            foreach ($this->patrones as $patron) {
                if ($patron->pgrama() === $pgrama) {
                    // ⚡ Leer la matriz original guardada en 'abajo'
                    $paquete_abajo = $patron->dato('abajo');
                    if ($paquete_abajo && isset($paquete_abajo['matriz_original']) && $paquete_abajo['matriz_original'] instanceof Matriz2x2) {
                        $matrices[] = $paquete_abajo['matriz_original'];
                    } else {
                        // Fallback: usar la secuencia de identidad del patrón
                        foreach ($patron->secuencia_de_matrices() as $matriz) {
                            $matrices[] = $matriz;
                        }
                    }
                    $encontrado = true;
                    break;
                }
            }
            $paquete_abajo = $patron->dato('abajo');
            echo "DEBUG EMITIR: pgrama=" . json_encode($pgrama) . " abajo=" . var_export($paquete_abajo, true) . "\n";
            if (!$encontrado) {
                return null; // algún p‑grama no está en el vocabulario
            }
        }

        return new Senal($matrices);
    }

    /**
     * Devuelve la fase de la antena.
     *
     * @return string
     * @since 1.4.5
     */
    public function fase(): string
    {
        return $this->fase;
    }

    /**
     * Devuelve la lista de patrones registrados.
     *
     * @return NodoNumerico[]
     * @since 1.4.5
     */
    public function patrones(): array
    {
        return $this->patrones;
    }
}