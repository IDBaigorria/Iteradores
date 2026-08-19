<?php
/**
 * Ventana — Iteradores Neuronales.
 *
 * Representa una ventana de sintonía como entidad de primer nivel con
 * identidad matricial 2×2. Las ventanas pueden ser **atómicas** (un solo
 * primo, fase 0) o **compuestas** (producto de varias atómicas, fase > 0).
 *
 * Cada ventana vive en el **universo de spines**, separado del universo de
 * dominios operativos. Su clave es la matriz 2×2 resultante del producto
 * no conmutativo de las matrices canónicas de sus factores.
 *
 * ## Estructura
 *
 * - `matriz`: la matriz 2×2 $M(W)$ (entradas $a, b, c, d$).
 * - `p_grama`: secuencia ordenada de primos componentes.
 * - `fase_spin`: fase en el universo de spines (0 atómica, >0 compuesta).
 * - `frecuencia`: contador de nodos que referencian esta ventana.
 * - `energia_total`: suma de energías de nodos afiliados.
 * - `nodos_afiliados`: conjunto de identificadores de nodos.
 * - `vector_promedio`: promedio normalizado de vectores de activación
 *   bajo los cuales fue usada ($\hat{v}_{pool}$).
 *
 * @package Iteradores\Tiempo
 * @author Ignacio David Baigorria
 * @since 1.5.1
 * @version 1.5.1
 */

namespace Iteradores\Tiempo;

use Iteradores\Nucleo\Objeto;

class Ventana extends Objeto
{
    /**
     * Matriz 2×2 de la ventana.
     *
     * @var array{a: int, b: int, c: int, d: int}
     */
    private array $matriz;

    /**
     * Secuencia ordenada de primos componentes.
     *
     * @var array<int>
     */
    private array $p_grama;

    /**
     * Fase en el universo de spines.
     *
     * 0 = atómica, 1 = binaria, 2 = ternaria, etc.
     *
     * @var int
     */
    private int $fase_spin;

    /**
     * Contador de nodos que referencian esta ventana.
     *
     * @var int
     */
    private int $frecuencia = 0;

    /**
     * Suma de energías de nodos afiliados.
     *
     * @var float
     */
    private float $energia_total = 0.0;

    /**
     * Conjunto de identificadores de nodos afiliados.
     *
     * @var array<string>
     */
    private array $nodos_afiliados = [];

    /**
     * Promedio normalizado de vectores de activación.
     *
     * @var array{x: float, y: float, z: float}|null
     */
    private ?array $vector_promedio = null;

    /**
     * Construye una ventana atómica o compuesta.
     *
     * @param array{a: int, b: int, c: int, d: int} $matriz   Matriz 2×2.
     * @param array<int>                            $p_grama   Primos componentes.
     * @param int                                   $fase_spin Fase en el universo de spines.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function __construct(array $matriz, array $p_grama, int $fase_spin = 0)
    {
        $this->matriz = $matriz;
        $this->p_grama = $p_grama;
        $this->fase_spin = $fase_spin;
    }

    // ═══════════════════════════════════════════════════════
    // ACCESORES
    // ═══════════════════════════════════════════════════════

    /**
     * Devuelve la matriz 2×2.
     *
     * @return array{a: int, b: int, c: int, d: int}
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function matriz(): array
    {
        return $this->matriz;
    }

    /**
     * Devuelve la clave serializada de la matriz.
     *
     * Útil para indexar en mapas hash (PoolVentanas).
     *
     * @return string Formato "a,b,c,d".
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function clave(): string
    {
        return "{$this->matriz['a']},{$this->matriz['b']},{$this->matriz['c']},{$this->matriz['d']}";
    }

    /**
     * Devuelve el p-grama.
     *
     * @return array<int>
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function p_grama(): array
    {
        return $this->p_grama;
    }

    /**
     * Devuelve la fase en el universo de spines.
     *
     * @return int
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function fase_spin(): int
    {
        return $this->fase_spin;
    }

    /**
     * Devuelve la frecuencia de referencia.
     *
     * @return int
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function frecuencia(): int
    {
        return $this->frecuencia;
    }

    /**
     * Devuelve la energía total acumulada.
     *
     * @return float
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function energia_total(): float
    {
        return $this->energia_total;
    }

    /**
     * Devuelve los nodos afiliados.
     *
     * @return array<string>
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function nodos_afiliados(): array
    {
        return $this->nodos_afiliados;
    }

    /**
     * Devuelve el vector promedio.
     *
     * @return array{x: float, y: float, z: float}|null
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function vector_promedio(): ?array
    {
        return $this->vector_promedio;
    }

    // ═══════════════════════════════════════════════════════
    // MUTADORES (gestión de afiliación y aprendizaje)
    // ═══════════════════════════════════════════════════════

    /**
     * Afilia un nodo a esta ventana.
     *
     * Incrementa la frecuencia y acumula la energía del nodo.
     *
     * @param string $id_nodo Identificador único del nodo.
     * @param float  $energia Energía del nodo al momento de afiliar.
     * @return void
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function afiliar(string $id_nodo, float $energia): void
    {
        if (!in_array($id_nodo, $this->nodos_afiliados, true)) {
            $this->nodos_afiliados[] = $id_nodo;
        }
        $this->frecuencia++;
        $this->energia_total += $energia;
    }

    /**
     * Desafilia un nodo de esta ventana.
     *
     * @param string $id_nodo Identificador único del nodo.
     * @return void
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function desafiliar(string $id_nodo): void
    {
        $this->nodos_afiliados = array_values(
            array_diff($this->nodos_afiliados, [$id_nodo])
        );
    }

    /**
     * Actualiza el vector promedio mediante tironeo global.
     *
     * $$\hat{v}_{pool} \leftarrow \hat{v}_{pool} + \beta_{global}(\hat{V}_{actual} - \hat{v}_{pool})$$
     *
     * @param array{x: float, y: float, z: float} $vector_actual Vector de activación del pulso.
     * @param float                             $beta         Tasa de aprendizaje global (0 < β ≤ 1).
     * @return void
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function tironear(array $vector_actual, float $beta = 0.1): void
    {
        if ($this->vector_promedio === null) {
            $this->vector_promedio = $vector_actual;
            return;
        }

        $vx = $this->vector_promedio['x'] + $beta * ($vector_actual['x'] - $this->vector_promedio['x']);
        $vy = $this->vector_promedio['y'] + $beta * ($vector_actual['y'] - $this->vector_promedio['y']);
        $vz = $this->vector_promedio['z'] + $beta * ($vector_actual['z'] - $this->vector_promedio['z']);

        $magnitud = sqrt($vx * $vx + $vy * $vy + $vz * $vz);
        if ($magnitud < 1e-9) {
            $this->vector_promedio = ['x' => 0.0, 'y' => 0.0, 'z' => 1.0];
            return;
        }

        $this->vector_promedio = [
            'x' => $vx / $magnitud,
            'y' => $vy / $magnitud,
            'z' => $vz / $magnitud,
        ];
    }

    // ═══════════════════════════════════════════════════════
    // ÁLGEBRA MATRICIAL ESTÁTICA
    // ═══════════════════════════════════════════════════════

    /**
     * Construye la matriz canónica para un número primo.
     *
     * $$M(p) = \begin{pmatrix} p & 0 \\ 1 & 1 \end{pmatrix}$$
     *
     * @param int $primo Número primo (positivo o negativo).
     * @return array{a: int, b: int, c: int, d: int}
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public static function matriz_canonica(int $primo): array
    {
        return [
            'a' => $primo,
            'b' => 0,
            'c' => 1,
            'd' => 1,
        ];
    }

    /**
     * Multiplica dos matrices 2×2.
     *
     * El producto es no conmutativo: $M_1 \cdot M_2 \neq M_2 \cdot M_1$.
     *
     * @param array{a: int, b: int, c: int, d: int} $m1
     * @param array{a: int, b: int, c: int, d: int} $m2
     * @return array{a: int, b: int, c: int, d: int}
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public static function producto(array $m1, array $m2): array
    {
        return [
            'a' => $m1['a'] * $m2['a'] + $m1['b'] * $m2['c'],
            'b' => $m1['a'] * $m2['b'] + $m1['b'] * $m2['d'],
            'c' => $m1['c'] * $m2['a'] + $m1['d'] * $m2['c'],
            'd' => $m1['c'] * $m2['b'] + $m1['d'] * $m2['d'],
        ];
    }

    /**
     * Determinante de una matriz 2×2.
     *
     * @param array{a: int, b: int, c: int, d: int} $m
     * @return int
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public static function determinante(array $m): int
    {
        return $m['a'] * $m['d'] - $m['b'] * $m['c'];
    }
}
