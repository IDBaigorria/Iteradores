<?php

namespace Iteradores\Nodos;

use Iteradores\Nucleo\Objeto;

/**
 * Clase Matriz2x2
 *
 * Valor inmutable que representa una matriz cuadrada de 2×2 con entradas enteras.
 *
 * Esta clase es la piedra angular del sistema de identidades numéricas no conmutativas.
 * Permite representar números primos y compuestos como matrices, de modo que el producto
 * preserve el orden de los factores. Junto con {@link NodoNumerico} y {@link NodoPrimo},
 * forma la base del mecanismo de ascenso/descenso por fases.
 *
 * ## Uso como identidad
 * Cada nodo del framework posee una `Matriz2x2` que lo identifica de manera única.
 * Dos nodos con matrices diferentes representan composiciones distintas, incluso si
 * sus determinantes coinciden (por ejemplo, `[[6,0],[4,1]]` vs `[[6,0],[3,1]]`).
 *
 * ## Inmutabilidad
 * Las propiedades son de solo lectura. Una vez construida, la matriz no cambia.
 * Esto permite usarla como clave de índice sin riesgo de efectos colaterales.
 *
 * ## Factoría de primos
 * El método estático {@link crear_prima()} construye la matriz canónica asociada a un
 * número primo `p`: `[[p, 0], [1, 1]]`. Esta forma garantiza la no conmutatividad
 * del producto:
 * - `M(2) * M(3) = [[6,0],[4,1]]`
 * - `M(3) * M(2) = [[6,0],[3,1]]`
 *
 * ## Rendimiento
 * El determinante y la representación en cadena se precalculan en el constructor.
 *
 * @package Iteradores\Nodos
 * @version 1.4.0
 * @since 1.4.2
 * @author Ignacio David Baigorria
 * @extends Objeto
 * @see NodoNumerico
 * @see NodoPrimo
 */
class Matriz2x2 extends Objeto
{
    /** @var int Entrada superior izquierda */
    public readonly int $a;

    /** @var int Entrada superior derecha */
    public  int $b;  // ← ahora mutable

    /** @var int Entrada inferior izquierda */
    public readonly int $c;

    /** @var int Entrada inferior derecha */
    public readonly int $d;

    /** @var int Determinante precalculado */
    private int $determinante;

    /** @var string Representación en cadena precalculada */
    private string $cadena;

    /**
     * Construye una nueva matriz 2×2 inmutable.
     *
     * Precalcula el determinante y la cadena de representación para acceso rápido.
     *
     * @param int $a Fila 0, columna 0
     * @param int $b Fila 0, columna 1
     * @param int $c Fila 1, columna 0
     * @param int $d Fila 1, columna 1
     */
    public function __construct(int $a, int $b, int $c, int $d)
    {
       $this->a = $a;
        $this->b = $b;
        $this->c = $c;
        $this->d = $d;
        $this->recalcular();
    }

   /**
     * Recalcula la representación en cadena cacheada tras un cambio en b.
     * @return void
     */
    private function recalcular(): void
    {
        $this->cadena = "[[{$this->a},{$this->b}],[{$this->c},{$this->d}]]";
    }

    /**
     * Devuelve la matriz neutra (neutro multiplicativo).
     *
     * `[[1, 0], [0, 1]]`
     *
     * @return Matriz2x2
     */
    public static function neutra(): Matriz2x2
    {
        return new self(1, 0, 0, 1);
    }
    /**
     * Crea la matriz canónica para un concepto (espectro negativo).
     * Forma: `[[-n, 1], [1, 1]]`
     *
     * @param int $n Entero negativo
     * @return Matriz2x2
     */
    public static function crear_negativa(int $n): Matriz2x2
    {
        return new self($n, 1, 1, 1);
    }
    /**
     * Crea la matriz canónica para un número primo.
     * Forma: `[[p, 1], [1, 1]]`
     *
     * @param int $p Número primo
     * @return Matriz2x2
     */
    public static function crear_prima(int $p): Matriz2x2
    {
        return new self($p, 1, 1, 1);
    }

    /**
     * Construye una matriz a partir de un array [a, b, c, d].
     *
     * @param array $arr Array de 4 enteros
     * @return Matriz2x2
     * @throws \InvalidArgumentException si el array no tiene 4 elementos
     */
    public static function desde_array(array $arr): Matriz2x2
    {
        if (count($arr) !== 4) {
            throw new \InvalidArgumentException('El array debe contener exactamente 4 elementos.');
        }
        return new self(
            (int) $arr[0],
            (int) $arr[1],
            (int) $arr[2],
            (int) $arr[3]
        );
    }

    /**
     * Multiplica esta matriz por otra (this * otra).
     *
     * El orden de la multiplicación es fundamental para la no conmutatividad.
     *
     * @param Matriz2x2 $otra Matriz a la derecha del producto
     * @return Matriz2x2 Nueva matriz resultado
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
     * Devuelve el determinante precalculado de la matriz.
     *
     * `det = a*d - b*c`
     *
     * @return int
     */
    public function determinante(): int
    {
        return $this->determinante;
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

    /**
     * Representación canónica en string, precalculada en el constructor.
     *
     * Formato: `"[[a,b],[c,d]]"`
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->cadena;
    }

    /**
     * Encuentra el menor número primo estrictamente mayor que $n.
     *
     * Utiliza una prueba de divisibilidad simple (división hasta √candidato).
     * Es adecuada para los números pequeños (hasta unos pocos miles) que maneja el framework.
     *
     * @param int $n Valor de partida
     * @return int Siguiente número primo después de $n
     * @since 1.4.0
     */
    public static function siguiente_numero_primo(int $n): int
    {
        $candidato = $n + 1;
        while (true) {
            if (self::es_primo($candidato)) {
                return $candidato;
            }
            $candidato++;
        }
    }

    /**
     * Verifica si un número entero positivo es primo.
     *
     * @param int $numero
     * @return bool
     * @internal Usada por siguiente_numero_primo()
     */
    private static function es_primo(int $numero): bool
    {
        if ($numero < 2) {
            return false;
        }
        if ($numero === 2) {
            return true;
        }
        if ($numero % 2 === 0) {
            return false;
        }
        $limite = (int) sqrt($numero);
        for ($i = 3; $i <= $limite; $i += 2) {
            if ($numero % $i === 0) {
                return false;
            }
        }
        return true;
    }
    /**
     * "Pinta" la matriz añadiendo un factor primo a la entrada `b`.
     *
     * Esta operación es el núcleo del entrelazamiento contextual:
     * codifica la pertenencia del nodo a un conjunto o contexto.
     *
     * @param int $primo Factor a multiplicar en `b`.
     * @return void
     * @see despintar()
     */
    public function pintar(int $primo): void
    {
        $this->b *= $primo;
        $this->recalcular();
    }

    /**
     * "Despinta" la matriz dividiendo la entrada `b` por el primo indicado.
     *
     * Revierte la operación de {@link pintar()}.
     *
     * @param int $primo Factor a eliminar de `b`.
     * @return void
     * @throws \InvalidArgumentException si el primo no divide a `b`.
     */
    public function despintar(int $primo): void
    {
        if ($this->b % $primo !== 0) {
            throw new \InvalidArgumentException("El primo {$primo} no está presente en b.");
        }
        $this->b /= $primo;
        $this->recalcular();
    }
}