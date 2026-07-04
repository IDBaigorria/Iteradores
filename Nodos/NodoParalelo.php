<?php

namespace Iteradores\Nodos;

use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Matriz2x2;

/**
 * NodoParalelo – Sincronización de componentes simultáneos.
 *
 * Representa un **grupo de comandos o señales que ocurren en el mismo
 * instante lógico**. A diferencia de una secuencia ordenada, los componentes
 * de un NodoParalelo no tienen un orden predefinido: el sistema los trata
 * como un conjunto simultáneo cuya identidad es **conmutativa**.
 *
 * ## Identidad y p‑grama
 *
 * La identidad de un NodoParalelo se construye a partir de su **p‑grama**,
 * que siempre comienza con el marcador `1` seguido de los factores primos
 * ordenados canónicamente:
 *
 * ```
 * p‑grama = [1, p₁, p₂, …, pₚ]   (primos en orden canónico)
 * matriz  = M(1) × M(p₁) × M(p₂) × … × M(pₚ)
 * ```
 *
 * donde `M(1)` es {@link Matriz2x2::inicial()} (`[[1,0],[1,1]]`). El `1`
 * al inicio del p‑grama es el **marcador de sincronización**: no es un primo
 * y nunca aparecerá como identificador de un comando atómico. Su presencia
 * permite distinguir algebraicamente un paralelo de una secuencia sin
 * necesidad de un flag separado.
 *
 * ## Cantidad prima de componentes
 *
 * Solo se permiten grupos cuya **cantidad de componentes sea un número
 * primo**. Esta restricción:
 *
 * - Mantiene la coherencia algebraica con el resto del sistema, que opera
 *   con p‑gramas primos.
 * - Evita la ambigüedad en la descomposición: un grupo de 4 podría
 *   confundirse con dos grupos de 2, pero 3 o 5 no admiten esa ambigüedad.
 * - Facilita el ascenso de fase, ya que los primos son las unidades
 *   atómicas que ascienden.
 *
 * ## Relación con el resto de la jerarquía
 *
 * - Hereda de {@link NodoNumerico} e implementa {@link IdentidadNumerica}
 *   (a través de la herencia).
 * - Su p‑grama comienza con `1`, lo que lo distingue de las secuencias
 *   creadas con {@link NodoNumerico::crear_numerico()}.
 * - Puede ascender a una fase superior mediante {@link NodoNumerico::ascender()},
 *   que guarda su p‑grama en un {@link NodoPrimo} de la fase destino.
 *
 * @package Iteradores\Nodos
 * @version 1.4.4
 * @since 1.4.2
 * @author Ignacio David Baigorria
 * @extends NodoNumerico
 * @see Matriz2x2
 */
class NodoParalelo extends NodoNumerico
{
    /**
     * Constructor protegido.
     */
    protected function __construct()
    {
        parent::__construct();
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
     *    que el p‑grama y la matriz sean conmutativos.
     * 3. **Construcción del p‑grama:** se crea el array `[1, p₁, p₂, …, pₚ]`
     *    donde `pᵢ` son los números primos de los componentes ya ordenados.
     * 4. **Cálculo de la matriz identidad:** se multiplica {@link Matriz2x2::inicial()}
     *    (que actúa como `M(1)`) por las identidades de los componentes en
     *    orden canónico.
     * 5. **Asignación al nodo:** se crea la instancia, se le asigna la
     *    matriz, el p‑grama y la capacidad/fuga. **No se crean enlaces
     *    internos**; esa es responsabilidad del iterador.
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

        // Ordenar canónicamente por representación textual de la identidad.
        usort($componentes, function (NodoNumerico $a, NodoNumerico $b) {
            return strcmp((string) $a->identidad(), (string) $b->identidad());
        });

        // Construir p‑grama: [1, p₁, p₂, …, pₚ]
        $pgrama = [1];
        foreach ($componentes as $comp) {
            if ($comp instanceof NodoPrimo) {
                $pgrama[] = $comp->numero_primo();
            }
        }

        // Calcular matriz: M(1) × M(p₁) × M(p₂) × … × M(pₚ)
        $matriz = Matriz2x2::inicial();  // M(1) = [[1,0],[1,1]]
        foreach ($componentes as $comp) {
            $matriz = $matriz->multiplicar($comp->identidad());
        }

        // Crear nodo y asignar identidad y p‑grama en fase actual.
        $nodo = new self();
        $nodo->capacidad = $capacidad;
        $nodo->fuga = $fuga;
        $nodo->_identidad($matriz);
        $nodo->_pgrama($pgrama);

        return $nodo;
    }
}