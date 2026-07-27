<?php

namespace Iteradores\Nodos;

use Iteradores\Nucleo\Objeto;

/**
 * Matriz2x2 – Identidad matricial compacta e inmutable para p-gramas.
 *
 * Representa una matriz cuadrada de 2×2 con entradas enteras. Es la unidad
 * fundamental de identidad en el sistema de fases de Iteradores. Junto con
 * {@link NodoNumerico} y sus subclases, forma la base del mecanismo de
 * ascenso/descenso y de la codificación no conmutativa de secuencias.
 *
 * ## Espectro numérico
 *
 * El signo de la entrada `a` distingue el tipo de comando que representa
 * la matriz:
 *
 * | Tipo               | Forma canónica      | Uso                         |
 * |--------------------|---------------------|-----------------------------|
 * | **Prima positiva** | `[[p, 0], [1, 1]]` | Comando constructivo (hacer)|
 * | **Prima negativa** | `[[-p, 0], [1, 1]]`| Comando destructivo (deshacer)|
 * | **Inicial**        | `[[1, 0], [1, 1]]` | Semilla de NodoNumerico     |
 * | **Cero**           | `[[0, 0], [1, 1]]` | Delimitador de fin de secuencia (det = 0) |
 *
 * - Las matrices **positivas** representan acciones constructivas.
 * - Las matrices **negativas** representan las correspondientes acciones
 *   destructivas (deshaceres), permitiendo revertir cualquier operación.
 * - La **Matriz Cero** se utiliza como marcador de fin de transmisión
 *   en las comunicaciones nodo a nodo.
 * 
 * ## No conmutatividad y orden
 *
 * El producto de matrices no es conmutativo: `M(A) × M(B) ≠ M(B) × M(A)`.
 * Esta propiedad es explotada por el sistema para **codificar el orden** en
 * las secuencias (p‑gramas). Dos secuencias con los mismos factores pero en
 * distinto orden producen matrices diferentes, aunque sus determinantes
 * coincidan.
 *
 * ## Inmutabilidad
 *
 * Las cuatro entradas de la matriz son inmutables una vez construida la
 * instancia. En particular, `b = 0` es fijo para todas las formas canónicas,
 * lo que garantiza que la matriz solo codifica la identidad estructural de la
 * acción, sin mezclarla con información contextual.
 *
 * ## Referencia al NodoNumerico portador
 *
 * Cada matriz mantiene una referencia al {@link NodoNumerico} que la utiliza
 * como identidad. Esto permite la sincronización directa durante el ascenso y
 * descenso de fase, sin necesidad de búsquedas en índices externos.
 *
 * @package Iteradores\Nodos
 * @version 1.4.8
 * @since 1.4.0
 * @author Ignacio David Baigorria
 * @extends Objeto
 * @see NodoNumerico
 * @see NodoPrimo
 * @see NodoParalelo
 */
class Matriz2x2 extends Objeto
{
    /**
     * Entrada superior izquierda (inmutable).
     *
     * Para las formas canónicas primas, su valor es `p` (positivo) o `-p`
     * (negativo), determinando si la matriz representa un comando
     * constructivo o destructivo.
     *
     * @var int
     */
    public readonly int $a;

    /**
     * Entrada superior derecha (inmutable).
     *
     * Fijada a 0 en todas las formas canónicas del sistema. Al no ser
     * mutable, la matriz solo codifica la identidad estructural de la
     * acción, sin interferencias externas.
     *
     * @var int
     */
    public readonly int $b;

    /**
     * Entrada inferior izquierda (inmutable).
     *
     * @var int
     */
    public readonly int $c;

    /**
     * Entrada inferior derecha (inmutable).
     *
     * @var int
     */
    public readonly int $d;

    /**
     * NodoNumerico que porta esta matriz como identidad.
     *
     * @var NodoNumerico|null
     */
    private ?NodoNumerico $nodo = null;

    // ═══════════════════════════════════════════
    // CONSTRUCTOR
    // ═══════════════════════════════════════════

    /**
     * Construye una nueva matriz 2×2 inmutable.
     *
     * @param int $a Fila 0, columna 0
     * @param int $b Fila 0, columna 1 (siempre 0 en las formas canónicas)
     * @param int $c Fila 1, columna 0
     * @param int $d Fila 1, columna 1
     */
    public function __construct(int $a, int $b, int $c, int $d)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
        $this->d = $d;
    }

    // ═══════════════════════════════════════════
    // GETTERS / SETTERS
    // ═══════════════════════════════════════════

    /**
     * Obtiene el NodoNumerico portador de esta matriz.
     *
     * @return NodoNumerico|null
     */
    public function nodo(): ?NodoNumerico
    {
        return $this->nodo;
    }

    /**
     * Asigna el NodoNumerico portador de esta matriz.
     *
     * @param NodoNumerico $nodo Nodo que porta esta identidad.
     * @return void
     */
    public function _nodo(NodoNumerico $nodo): void
    {
        $this->nodo = $nodo;
    }

    // ═══════════════════════════════════════════
    // FÁBRICAS ESTÁTICAS
    // ═══════════════════════════════════════════

    /**
     * Matriz inicial (semilla) para un NodoNumerico recién creado.
     *
     * Forma: `[[1, 0], [1, 1]]`
     *
     * - Determinante = 1 (neutro multiplicativo, no altera productos).
     * - `b = 0` fijo, como en el resto de formas canónicas.
     * - No es una matriz prima; es el punto de partida antes de que el
     *   nodo reciba una identidad concreta.
     *
     * @return Matriz2x2
     */
    public static function inicial(): Matriz2x2
    {
        return new self(1, 0, 1, 1);
    }

    /**
     * Crea la matriz canónica de un comando constructivo (primo positivo).
     *
     * Forma: `[[p, 0], [1, 1]]`
     *
     * Representa una acción atómica (un NodoPrimo) con número primo `p`.
     *
     * @param int $p Número primo que identifica al comando.
     * @return Matriz2x2
     * @see NodoPrimo
     */
    public static function crear_prima(int $p): Matriz2x2
    {
        return new self($p, 0, 1, 1);
    }

    /**
     * Crea la matriz canónica de un comando destructivo (primo negativo).
     *
     * Forma: `[[-p, 0], [1, 1]]`
     *
     * La entrada `a = -p` sitúa la matriz en el **espectro negativo**,
     * reservado para acciones de deshacer. El valor absoluto `p` es el
     * mismo que el del comando constructivo correspondiente.
     *
     * @param int $p Número primo (positivo) cuyo negativo representa el deshacer.
     * @return Matriz2x2
     * @see NodoPrimo
     */
    public static function crear_negativa_prima(int $p): Matriz2x2
    {
        return new self(-$p, 0, 1, 1);
    }

    /**
     * Matriz identidad algebraica clásica.
     *
     * Forma: `[[1, 0], [0, 1]]`
     *
     * **No se usa en el sistema de identidades** porque su entrada `c = 0`
     * la hace conmutativa con cualquier otra matriz. Se conserva para
     * posibles cálculos auxiliares (rotaciones, transformaciones lineales,
     * etc.) ajenos al mecanismo de secuencias.
     *
     * @return Matriz2x2
     */
    public static function identidad_algebraica(): Matriz2x2
    {
        return new self(1, 0, 0, 1);
    }

    /**
     * Construye una matriz a partir de un array [a, b, c, d].
     *
     * @param int[] $arr Array de exactamente 4 elementos.
     * @return Matriz2x2|null Matriz o null si el array no es válido.
     */
    public static function desde_array(array $arr): ?Matriz2x2
    {
        if (count($arr) !== 4) {
            self::_error('El array debe contener exactamente 4 elementos.');
            return null;
        }
        return new self((int)$arr[0], (int)$arr[1], (int)$arr[2], (int)$arr[3]);
    }

    /**
     * Matriz Cero utilizada como delimitador de fin de secuencia.
     *
     * Forma: `[[0, 0], [1, 1]]` – determinante 0.
     *
     * @return Matriz2x2
     * @since 1.4.8
     */
    public static function cero(): Matriz2x2
    {
        return new self(0, 0, 1, 1);
    }

    // ═══════════════════════════════════════════
    // OPERACIONES MATRICIALES
    // ═══════════════════════════════════════════

    /**
     * Multiplica esta matriz por otra (this × otra).
     *
     * El orden es fundamental: `M(A) × M(B)` no es igual a `M(B) × M(A)`.
     * Esta **no conmutatividad** permite que las secuencias de factores
     * preserven el orden en su identidad matricial.
     *
     * @param Matriz2x2 $otra Matriz a la derecha del producto.
     * @return Matriz2x2 Nueva matriz resultado.
     */
    public function multiplicar(Matriz2x2 $otra): Matriz2x2
    {
        return new self(
            $this->a * $otra->a + $this->b * $otra->c,
            $this->a * $otra->b + $this->b * $otra->d,
            $this->c * $otra->a + $this->d * $otra->c,
            $this->c * $otra->b + $this->d * $otra->d
        );
    }

    /**
     * Calcula el determinante de la matriz.
     *
     * `det = a*d - b*c`
     *
     * Para las formas canónicas del sistema (`b = 0`), el determinante se
     * reduce a `a*d`, simplificando los cálculos.
     *
     * @return int
     */
    public function determinante(): int
    {
        return $this->a * $this->d - $this->b * $this->c;
    }

    /**
     * Compara esta matriz con otra por igualdad exacta de sus cuatro componentes.
     *
     * @param Matriz2x2 $otra
     * @return bool
     */
    public function es_igual(Matriz2x2 $otra): bool
    {
        return $this->a === $otra->a
            && $this->b === $otra->b
            && $this->c === $otra->c
            && $this->d === $otra->d;
    }

    // ═══════════════════════════════════════════
    // REPRESENTACIÓN
    // ═══════════════════════════════════════════

    /**
     * Representación canónica en string.
     *
     * Formato: `"[[a,b],[c,d]]"`. Utilizada como clave en índices y para
     * ordenación canónica de componentes en {@link NodoParalelo}.
     *
     * @return string
     */
    public function a_texto(): string
    {
        return "[[{$this->a},{$this->b}],[{$this->c},{$this->d}]]";
    }

    /**
     * Representación mágica en string (delega en {@link a_texto()}).
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->a_texto();
    }
}