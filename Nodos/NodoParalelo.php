<?php

namespace Iteradores\Nodos;

use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Matriz2x2;

/**
 * NodoParalelo – Sincronización de componentes simultáneos.
 *
 * Representa un **grupo de señales o comandos que ocurren en el mismo
 * instante lógico** (por ejemplo, todos los dominios activos en un único
 * pulso del Tálamo). A diferencia de una secuencia ordenada, los
 * componentes de un NodoParalelo no tienen un orden predefinido: el
 * sistema los trata como un conjunto simultáneo cuya identidad es
 * **conmutativa**.
 *
 * ## Identidad matricial
 *
 * La identidad de un NodoParalelo se construye como:
 * ```
 * M_marca × M(c₁) × M(c₂) × … × M(cₚ)
 * ```
 * donde los componentes se ordenan **canónicamente** (según la
 * representación textual de sus matrices identidad) antes de la
 * multiplicación. Esto garantiza que el producto sea el mismo
 * independientemente del orden en que se pasen los componentes a la
 * fábrica.
 *
 * ## Marca de sincronización
 *
 * La matriz `[[1, 1], [0, 1]]` (definida en {@link Conf::MATRIZ_MARCA_CONJUNTO})
 * se antepone al producto de los componentes para **marcar algebraicamente**
 * que este nodo es una sincronización y no una secuencia común.
 *
 * - La entrada `b = 1` permite que el nodo sea pintado posteriormente por
 *   conjuntos sin alterar su estructura.
 * - La entrada `c = 0` distingue la marca de las matrices canónicas de los
 *   primos (que siempre tienen `c = 1`).
 *
 * ## Cantidad prima de componentes
 *
 * Solo se permiten grupos cuya **cantidad de componentes sea un número
 * primo**. Esta restricción:
 *
 * - Mantiene la coherencia algebraica con el resto del sistema, que opera
 *   con p‑gramas primos.
 * - Evita la ambigüedad en la factorización: un grupo de 4 podría
 *   confundirse con dos grupos de 2, pero 3 o 5 no admiten esa ambigüedad.
 * - Facilita el ascenso de fase, ya que los primos son las unidades
 *   atómicas que ascienden.
 *
 * ## Relación con el resto de la jerarquía
 *
 * - Hereda de {@link NodoNumerico} e implementa {@link IdentidadNumerica}
 *   (a través de la herencia).
 * - Su propiedad `ordenado` es `false`, lo que lo distingue de las
 *   secuencias creadas con {@link NodoNumerico::crear_numerico()}.
 * - Puede ser **pintado** por {@link NodoConjunto} a través del canvas `b`
 *   de su matriz identidad, igual que cualquier otro nodo.
 *
 * @package Iteradores\Nodos
 * @version 1.4.3
 * @since 1.4.2
 * @author Ignacio David Baigorria
 * @extends NodoNumerico
 * @see Matriz2x2
 * @see NodoConjunto
 * @see Conf::MATRIZ_MARCA_CONJUNTO
 */
class NodoParalelo extends NodoNumerico
{
    /**
     * Constructor protegido.
     *
     * Inicializa el nodo con `ordenado = false`, reflejando su naturaleza
     * de conjunto simultáneo sin orden preestablecido.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->ordenado = false;
    }

    /**
     * Crea internamente un NodoParalelo (invocado por {@link NodoNumerico::crear_paralelo()}).
     *
     * ## Proceso de construcción
     *
     * 1. **Validación de cantidad prima:** si el número de componentes no es
     *    primo, se registra un error y se retorna `null`.
     * 2. **Ordenación canónica:** los componentes se ordenan según la
     *    representación textual de sus identidades matriciales. Esto asegura
     *    que el producto sea conmutativo.
     * 3. **Cálculo de la matriz identidad:** se multiplica la marca de
     *    sincronización ({@link Conf::MATRIZ_MARCA_CONJUNTO}) por las
     *    identidades de los componentes en orden canónico.
     * 4. **Asignación al nodo:** se crea la instancia, se le asigna la
     *    matriz resultante y se enlazan los componentes mediante adyacentes
     *    con nombres `componente_1`, `componente_2`, …, `componente_p`.
     *
     * @param NodoNumerico[] $componentes Componentes del grupo (cantidad prima).
     * @param int            $capacidad   Capacidad máxima de energía.
     * @param float          $fuga        Fuga de energía por ciclo.
     * @return NodoParalelo|null El nuevo nodo, o null si la cantidad no es prima.
     * @internal
     */
    public static function _crear_interno(
        array $componentes,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoParalelo {
        $cantidad = count($componentes);
        if (!NodoNumerico::es_numero_primo($cantidad)) {
            self::_error('La cantidad de componentes debe ser un número primo.');
            return null;
        }

        // Ordenar canónicamente.
        usort($componentes, function (NodoNumerico $a, NodoNumerico $b) {
            return strcmp((string) $a->identidad(), (string) $b->identidad());
        });

        // Calcular matriz con marca.
        $marca = self::obtener_matriz_marca();
        $matriz = $marca;
        foreach ($componentes as $comp) {
            $matriz = $matriz->multiplicar($comp->identidad());
        }

        // Crear nodo y asignar identidad en fase actual.
        $nodo = new self();
        $nodo->capacidad = $capacidad;
        $nodo->fuga = $fuga;
        $nodo->_identidad($matriz);

        // Enlazar componentes.
        for ($i = 0; $i < $cantidad; $i++) {
            $nodo->_adyacente_en($componentes[$i], 'componente_' . ($i + 1), true);
        }

        return $nodo;
    }

    /**
     * Devuelve la marca de sincronización cacheada.
     *
     * La marca es la matriz `[[1, 1], [0, 1]]` definida en
     * {@link Conf::MATRIZ_MARCA_CONJUNTO}. Se cachea estáticamente para
     * evitar recrearla en cada llamada.
     *
     * @return Matriz2x2
     */
    private static function obtener_matriz_marca(): Matriz2x2
    {
        static $marca = null;
        if ($marca === null) {
            $m = Conf::MATRIZ_MARCA_CONJUNTO; // [[1,1],[0,1]]
            $marca = new Matriz2x2($m[0][0], $m[0][1], $m[1][0], $m[1][1]);
        }
        return $marca;
    }
}