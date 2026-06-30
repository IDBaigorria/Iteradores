<?php

namespace Iteradores\Nodos;

use Iteradores\Nodos\Matriz2x2;

/**
 * NodoPrimo – Identidad prima canónica e indivisible.
 *
 * Representa la unidad atómica del grafo de aprendizaje. Su identidad
 * matricial es `[[p, 1], [1, 1]]` donde `p` es un número primo.
 *
 * Los NodoPrimo forman el **alfabeto** de cada fase:
 * - En fase 0 son bytes (0‑255).
 * - En fases superiores son conceptos que han ascendido desde una fase
 *   inferior y se representan con un nuevo número primo.
 *
 * ## Pool de primos libres
 * Cada NodoPrimo se agrega automáticamente al pool de su fase al ser
 * creado. El método {@link NodoNumerico::siguiente_primo_libre} los
 * consume cuando un nodo compuesto necesita ascender.
 *
 * ## Inmutabilidad lógica
 * Las entradas `a`, `c`, `d` son inmutables. La entrada `b` es un
 * canvas de contexto mutable (ver {@link Matriz2x2}) que permite
 * "pintar" el nodo con la pertenencia a conjuntos sin alterar su
 * identidad prima nuclear.
 *
 * @extends NodoNumerico
 * @implements IdentidadNumerica (heredado)
 * @see Matriz2x2
 * @see NodoConjunto
 */
class NodoPrimo extends NodoNumerico
{
    /**
     * Número primo representado por este nodo.
     *
     * @var int
     */
    private int $numero_primo;

    /**
     * Constructor protegido.
     *
     * @param int $numero_primo Número primo a encapsular.
     */
    protected function __construct(int $numero_primo)
    {
        parent::__construct();
        $this->numero_primo = $numero_primo;
        $this->ordenado = true;
        $this->identidad = Matriz2x2::crear_prima($numero_primo);
    }

    /**
     * Crea internamente un NodoPrimo (llamado por NodoNumerico).
     *
     * @param int $numero_primo Número primo validado.
     * @param int $capacidad Capacidad máxima de energía.
     * @param float $fuga Fuga de energía por ciclo.
     * @return NodoPrimo
     * @internal
     */
    public static function _crear_interno(
        int $numero_primo,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): NodoPrimo {
        $nodo = new self($numero_primo);
        // El factory padre (NodoElectrico::crear) ya fue llamado en el constructor de NodoNumerico.
        // Ajustamos capacidad y fuga.
        $nodo->capacidad = $capacidad;
        $nodo->fuga = $fuga;
        // Agregar al pool de primos libres de la fase actual.
        $fase = self::$fase ?? '0';
        if (!isset(self::$primos_libres_por_fase[$fase])) {
            self::$primos_libres_por_fase[$fase] = [];
        }
        self::$primos_libres_por_fase[$fase][] = $nodo;
        return $nodo;
    }

    /**
     * Devuelve el número primo del nodo.
     *
     * @return int
     */
    public function numero_primo(): int
    {
        return $this->numero_primo;
    }

    /**
     * Siempre retorna true.
     *
     * @return bool
     */
    public function es_primo(): bool
    {
        return true;
    }

    /**
     * Intento de factorización bloqueado.
     *
     * @return void
     * @throws \BadMethodCallException
     */
    public function factorizar(): void
    {
        self::_error('Un NodoPrimo no puede factorizarse en su misma fase.');
    }
}