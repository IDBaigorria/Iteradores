<?php

namespace Iteradores\Nodos;

use Iteradores\Nodos\Matriz2x2;

/**
 * NodoPrimo – Nodo numérico cuya identidad es una matriz prima.
 *
 * Representa una unidad atómica e indivisible dentro de una fase.
 * Su identidad matricial tiene la forma canónica `[[p, 0], [1, 1]]`
 * donde **p** es un número primo. Esta matriz es **no conmutativa**
 * y permite que la multiplicación preserve el orden de los factores.
 *
 * Los NodoPrimo son los ladrillos fundamentales del grafo de aprendizaje:
 * - En fase 0 representan bytes individuales (0-255).
 * - En fases superiores representan conceptos que han ascendido
 *   desde fases inferiores.
 *
 * ## Pool de primos libres
 * Cada NodoPrimo recién creado se agrega automáticamente al pool de
 * primos libres de su fase actual, quedando disponible para representar
 * composiciones ascendidas. Al asignársele un dato en la dimensión
 * `'abajo'` se considera ocupado y se retira del pool.
 *
 * ## Factorización
 * Un NodoPrimo **no puede descomponerse** en factores dentro de su misma
 * fase. Cualquier intento de factorización devuelve una alerta.
 *
 * @package Iteradores\Nodos
 * @version 1.4.2
 * @since 1.4.2
 * @author Ignacio David Baigorria
 * @extends NodoNumerico
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