<?php
namespace Iteradores\Controlador;

use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Nodos\NodoPrimo;

/**
 * Aplanador de señales procesadas.
 *
 * Convierte una {@link Senal} que ya ha sido procesada por un
 * {@link ProcesadorDeDominio} en una lista plana de {@link Matriz2x2}
 * del tálamo, apta para ser traducida a bytes por
 * {@link \Iteradores\Compuertas\MapeoBytesMatrices}.
 *
 * ## Responsabilidad
 *
 * Recorrer los elementos procesados de la señal y, para cada
 * {@link NodoNumerico} (patrón compuesto), descender recursivamente
 * a través de su p‑grama hasta alcanzar los primos atómicos. De cada
 * primo atómico se extrae la matriz original del tálamo (almacenada
 * en el dato multidimensional `'abajo'` durante el ascenso de fase)
 * o, en su defecto, la propia matriz identidad del primo.
 *
 * ## Uso típico
 *
 * ```php
 * $senal = new Senal(...);
 * $proc->procesar($senal);
 * $matrices = AplanadorSenal::aplanar($senal);
 * foreach ($matrices as $m) {
 *     $byte = MapeoBytesMatrices::matriz_a_byte($m);
 *     ...
 * }
 * ```
 *
 * @package Iteradores\Controlador
 * @since 1.4.6
 * @version 1.4.6
 * @see Senal
 * @see ProcesadorDeDominio
 * @see \Iteradores\Compuertas\MapeoBytesMatrices
 */
class AplanadorSenal
{
    /**
     * Aplana una señal procesada en una secuencia de matrices del tálamo.
     *
     * ## Algoritmo
     *
     * 1. Recorre los elementos procesados de la señal (ver
     *    {@link Senal::obtener_elementos_procesados()}).
     * 2. Para cada elemento:
     *    - Si es una {@link Matriz2x2} suelta, la agrega directamente
     *      al resultado.
     *    - Si es un {@link NodoNumerico}, invoca
     *      {@link descender_nodo()} para aplanarlo recursivamente.
     *
     * @param Senal $senal Señal ya procesada por un dominio.
     * @return Matriz2x2[] Lista de matrices del tálamo.
     */
    public static function aplanar(Senal $senal): array
    {
        $resultado = [];

        foreach ($senal->elementos_procesados() as $elemento) {
            if ($elemento instanceof Matriz2x2) {
                // Es una matriz que no fue capturada por ningún patrón.
                $resultado[] = $elemento;
            } elseif ($elemento instanceof NodoNumerico) {
                // Es un patrón compuesto (o un primo del dominio).
                // Hay que descenderlo hasta obtener las matrices del tálamo.
                self::descender_nodo($elemento, $resultado);
            }
        }

        return $resultado;
    }

    /**
     * Desciende recursivamente un nodo compuesto y acumula las matrices
     * del tálamo en el array de resultado.
     *
     * ## Cómo se asegura el uso de los primos correctos por fase
     *
     * Para cada factor del p‑grama se invoca
     * {@link NodoNumerico::crear_primo()}. Este método devuelve el
     * nodo que corresponde a ese número primo en el dominio, respetando
     * la fase en la que fue creado. Si el nodo no existe, se crea uno
     * nuevo en la fase activa actual (la fase de salida del dominio).
     *
     * ## Algoritmo
     *
     * 1. Obtiene el p‑grama del nodo.
     * 2. Omite la marca de tipo (`1` para paralelo, `-1` para deshacer)
     *    si está presente al inicio del array.
     * 3. Para cada factor restante:
     *    a. Obtiene el nodo del dominio con
     *       {@link NodoNumerico::crear_primo()}.
     *    b. Si es un {@link NodoPrimo} atómico, intenta extraer la
     *       matriz original del dato `'abajo'`. Si no existe, usa la
     *       propia identidad del primo.
     *    c. Si es un nodo compuesto, se llama recursivamente.
     *
     * @param NodoNumerico $nodo      Nodo compuesto a descender.
     * @param Matriz2x2[]  &$resultado Array donde se acumulan las matrices.
     * @return void
     */
    private static function descender_nodo(NodoNumerico $nodo, array &$resultado): void
    {
        $pgrama = $nodo->pgrama();

        // Quitar marca de tipo si existe
        if (!empty($pgrama) && in_array($pgrama[0], [1, -1], true)) {
            array_shift($pgrama);
        }

        foreach ($pgrama as $primo) {
            $nodo_factor = NodoNumerico::crear_primo($primo);
            if ($nodo_factor === null) continue;

            if ($nodo_factor instanceof NodoPrimo) {
                $paquete = $nodo_factor->dato('abajo');
                if (
                    is_array($paquete) &&
                    isset($paquete['matriz_original']) &&
                    $paquete['matriz_original'] instanceof Matriz2x2
                ) {
                    $resultado[] = $paquete['matriz_original'];
                } else {
                    $resultado[] = $nodo_factor->identidad();
                }
            } else {
                self::descender_nodo($nodo_factor, $resultado);
            }
        }
    }
}