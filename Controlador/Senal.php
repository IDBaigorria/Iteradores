<?php
namespace Iteradores\Controlador;

use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Controlador\MapeoBytesMatrices;   
use Iteradores\Controlador\AplanadorSenal;       

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
 * @version 1.4.6
 */
class Senal extends Objeto
{
    /**
     * Lista original de matrices que componen la señal.
     *
     * @var Matriz2x2[]
     */
    private array $matrices_crudas;

    /**
     * Cantidad de matrices crudas que ya han sido consumidas.
     *
     * @var int
     */
    private int $indice_consumido;

    /**
     * Ítems ya procesados. Cada elemento puede ser:
     *   - Matriz2x2: cuando no fue capturada por ningún patrón.
     *   - NodoNumerico: cuando un patrón capturó una subsecuencia.
     *
     * @var array
     */
    private array $elementos_procesados;

    /**
     * Constructor.
     *
     * @param Matriz2x2[] $matrices Crudas iniciales (opcional).
     */
    public function __construct(array $matrices = [])
    {
        $this->matrices_crudas = $matrices;
        $this->indice_consumido = 0;
        $this->elementos_procesados = [];
    }

    /**
     * Añade una matriz al final de la señal cruda.
     *
     * @param Matriz2x2 $matriz
     * @return void
     */
    public function _matriz(Matriz2x2 $matriz): void
    {
        $this->matrices_crudas[] = $matriz;
    }

    /**
     * Devuelve el total de matrices crudas (sin importar cuántas se han consumido).
     *
     * @return int
     */
    public function longitud_cruda(): int
    {
        return count($this->matrices_crudas);
    }

    /**
     * Devuelve la cantidad de matrices crudas que aún no han sido consumidas.
     *
     * @return int
     */
    public function longitud_no_consumida(): int
    {
        return count($this->matrices_crudas) - $this->indice_consumido;
    }

    /**
     * Obtiene la porción no consumida de las matrices crudas.
     *
     * @return Matriz2x2[]
     */
    public function no_consumidas(): array
    {
        return array_slice(
            $this->matrices_crudas,
            $this->indice_consumido
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
            $this->elementos_procesados[] = $patron;
        } else {
            // Sin patrón, se agregan las matrices crudas una a una
            $porcion = array_slice(
                $this->matrices_crudas,
                $this->indice_consumido,
                $longitud
            );
            foreach ($porcion as $matriz) {
                $this->elementos_procesados[] = $matriz;
            }
        }

        $this->indice_consumido += $longitud;
    }

    /**
     * Devuelve el índice actual de consumo.
     *
     * @return int
     */
    public function indice_consumido(): int
    {
        return $this->indice_consumido;
    }

    /**
     * Devuelve todos los elementos procesados hasta el momento.
     *
     * @return array
     */
    public function elementos_procesados(): array
    {
        return $this->elementos_procesados;
    }

    /**
     * Devuelve las matrices crudas completas (incluye las ya consumidas).
     *
     * Útil para auditoría o reconstrucción.
     *
     * @return Matriz2x2[]
     */
    public function crudas(): array
    {
        return $this->matrices_crudas;
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
     * @version 1.4.6
     * @todo Implementar una vez que el enrutamiento entre dominios esté activo.
     */
    public function senal_de_salida(int $fase): Senal
    {
        $matrices_salida = [];
        foreach ($this->elementos_procesados as $item) {
            if ($item instanceof NodoNumerico) {
                // TODO: validar que el nodo tenga identidad en la fase dada
                $matrices_salida[] = $item->identidad();
            } else {
                $matrices_salida[] = $item;
            }
        }
        return new self($matrices_salida);
    }

    // ═══════════════════════════════════════════
    // V 1.4.6 – CONVERSIÓN BYTES ↔ SEÑAL
    // ═══════════════════════════════════════════

    /**
     * Construye una señal a partir de una cadena de bytes.
     *
     * Cada byte (0‑255) se traduce a su {@link Matriz2x2} prima canónica
     * utilizando {@link \Iteradores\Compuertas\MapeoBytesMatrices::byte_a_matriz()}.
     *
     * @param string $bytes Cadena de bytes (p. ej. leída de un archivo).
     * @return self Nueva señal con las matrices primas correspondientes.
     * @since 1.4.6
     * @see \Iteradores\Compuertas\MapeoBytesMatrices
     */
    public static function desde_bytes(string $bytes): self
    {
        $matrices = [];
        $longitud = strlen($bytes);
        for ($i = 0; $i < $longitud; $i++) {
            $byte = ord($bytes[$i]);
            $matriz = MapeoBytesMatrices::byte_a_matriz($byte);  // ahora sí está importada
            if ($matriz !== null) {
                $matrices[] = $matriz;
            }
        }
        return new self($matrices);
    }

    /**
     * Convierte una señal procesada de vuelta a una cadena de bytes.
     *
     * Aplana la señal con {@link AplanadorSenal::aplanar()} para obtener
     * las matrices originales del tálamo y luego traduce cada una a su byte
     * con {@link \Iteradores\Compuertas\MapeoBytesMatrices::matriz_a_byte()}.
     *
     * @param Senal $senal Señal ya procesada por un dominio.
     * @return string Cadena de bytes lista para ser escrita o enviada.
     * @since 1.4.6
     * @see AplanadorSenal
     * @see \Iteradores\Compuertas\MapeoBytesMatrices
     */
    public static function a_bytes(Senal $senal): string
    {
        $bytes = '';
        foreach (AplanadorSenal::aplanar($senal) as $matriz) {
            $byte = MapeoBytesMatrices::matriz_a_byte($matriz);
            if ($byte !== null) {
                $bytes .= chr($byte);
            }
        }
        return $bytes;
    }
}