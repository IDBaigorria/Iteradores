<?php

namespace Iteradores\Nodos;

use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Matriz2x2;

/**
 * NodoParalelo – Sincronización de componentes simultáneos.
 *
 * Agrupa señales que ocurren **al mismo tiempo** (por ejemplo, los
 * dominios activos en un mismo pulso del Tálamo). Su identidad se
 * calcula como:
 * `M_marca × M(c1) × M(c2) × ... × M(cp)`
 * donde los componentes se ordenan canónicamente antes de multiplicar.
 *
 * ## Marca de sincronización
 * La marca `[[1, 1], [0, 1]]` diferencia a un NodoParalelo de una
 * secuencia común. Su entrada `b=1` permite que el nodo sea pintado
 * posteriormente por conjuntos.
 *
 * ## Cantidad prima
 * El número de componentes debe ser un número primo. Esto limita el
 * tamaño del grupo y mantiene la coherencia algebraica con el resto
 * del sistema.
 *
 * @extends NodoNumerico
 * @implements IdentidadNumerica (heredado)
 * @see Matriz2x2
 * @see NodoConjunto
 */
class NodoParalelo extends NodoNumerico
{
    /**
     * Constructor protegido.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->ordenado = false;
    }

    /**
     * Crea internamente un NodoParalelo (llamado por NodoNumerico).
     *
     * @param NodoNumerico[] $componentes Componentes del grupo (cantidad prima).
     * @param int $capacidad
     * @param float $fuga
     * @return NodoParalelo|null
     * @internal
     */
    public static function _crear_interno(
        array $componentes,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoParalelo {
        $cantidad = count($componentes);
        if (!self::es_primo($cantidad)) {
            self::_error('La cantidad de componentes debe ser un número primo.');
            return null;
        }

        // Ordenar canónicamente.
        usort($componentes, function (NodoNumerico $a, NodoNumerico $b) {
            return strcmp((string) $a->identidad(), (string) $b->identidad());
        });

        // Calcular matriz con marca.
        $marca = self::obtener_matriz_marca_sincronizacion();
        $matriz = $marca;
        foreach ($componentes as $comp) {
            $matriz = $matriz->multiplicar($comp->identidad());
        }

        $clave = (string) $matriz;
        if (isset(self::$indice_identidad[$clave])) {
            return self::$indice_identidad[$clave]; // ya existe
        }

        $nodo = new self();
        $nodo->capacidad = $capacidad;
        $nodo->fuga = $fuga;
        $nodo->identidad = $matriz;

        // Enlazar componentes.
        for ($i = 0; $i < $cantidad; $i++) {
            $nodo->_adyacente_en($componentes[$i], 'componente_' . ($i + 1), true);
        }

        self::$indice_identidad[$clave] = $nodo;
        return $nodo;
    }

    /**
     * Devuelve la marca de sincronización cacheada.
     *
     * @return Matriz2x2
     */
    private static function obtener_matriz_marca_sincronizacion(): Matriz2x2
    {
        static $marca = null;
        if ($marca === null) {
            $m = Conf::MATRIZ_MARCA_CONJUNTO; // [[1,1],[0,1]]
            $marca = new Matriz2x2($m[0][0], $m[0][1], $m[1][0], $m[1][1]);
        }
        return $marca;
    }
}