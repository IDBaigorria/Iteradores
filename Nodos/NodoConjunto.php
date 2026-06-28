<?php

namespace Iteradores\Nodos;

use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Matriz2x2;

/**
 * NodoConjunto – Concepto semántico con identidad negativa inmutable.
 *
 * Representa un **concepto abstracto** (color semántico) dentro del grafo
 * de aprendizaje. A diferencia de {@link NodoParalelo} que agrupa señales
 * simultáneas, un NodoConjunto define una categoría semántica cuyos
 * miembros pueden evolucionar con el tiempo sin alterar su identidad.
 *
 * ## Identidad negativa
 * La identidad se genera con un contador global decreciente a partir de -1.
 * La forma canónica es `[[-n, 0], [1, 1]]`. Esto garantiza:
 * - **Unicidad**: cada concepto tiene una identidad irrepetible.
 * - **Inmutabilidad**: agregar o quitar miembros no cambia la matriz.
 * - **Separación espectral**: los positivos representan estructura;
 *   los negativos representan significado.
 *
 * ## Nombre del concepto
 * El nombre se asigna mediante {@link _nombre()} después de la creación.
 * Una vez asignado, el concepto se registra en el **diccionario global**
 * de conceptos, permitiendo su recuperación por nombre.
 *
 * ## Gestión de miembros
 * Los miembros se agregan y quitan mediante enlaces adyacentes de doble vía
 * con pesos de pertenencia (dimensión `'pertenencia'`). Esto permite que
 * el conjunto evolucione sin perder su identidad ni su capacidad de
 * ascender entre fases.
 *
 * @package Iteradores\Nodos
 * @version 1.4.2
 * @since 1.4.2
 * @author Ignacio David Baigorria
 * @extends NodoNumerico
 */
class NodoConjunto extends NodoNumerico
{
    /**
     * Diccionario global de conceptos.
     * [nombre => NodoConjunto]
     *
     * @var array<string, NodoConjunto>
     */
    private static array $diccionario = [];

    /**
     * Contador para generar identidades negativas únicas.
     *
     * @var int
     */
    private static int $siguiente_negativo = -1;

    /**
     * Constructor protegido.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->ordenado = false;
    }

    /**
     * Crea internamente un NodoConjunto (llamado por NodoNumerico).
     *
     * @param int $capacidad
     * @param float $fuga
     * @return NodoConjunto
     * @internal
     */
    public static function _crear_interno(
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): NodoConjunto {
        $identidad = Matriz2x2::crear_negativa(self::$siguiente_negativo);
        self::$siguiente_negativo--;

        $nodo = new self();
        $nodo->capacidad = $capacidad;
        $nodo->fuga = $fuga;
        $nodo->identidad = $identidad;
        return $nodo;
    }

    /**
     * Asigna un nombre al concepto y lo registra en el diccionario.
     *
     * @param string $nombre Nombre del concepto (ej. "vocales").
     * @return void
     * @throws \RuntimeException si el nombre ya existe en el diccionario.
     */
    public function _nombre(string $nombre): void
    {
        if (isset(self::$diccionario[$nombre])) {
            self::_error("El concepto '{$nombre}' ya existe en el diccionario.");
            return;
        }
        $this->_dato($nombre, 'nombre_concepto');
        self::$diccionario[$nombre] = $this;
    }

    /**
     * Devuelve el nombre del concepto.
     *
     * @return string
     */
    public function nombre(): string
    {
        return $this->dato('nombre_concepto') ?? 'sin_nombre';
    }

    /**
     * Agrega un miembro al concepto (enlaces de doble vía).
     *
     * @param NodoNumerico $miembro
     * @return void
     */
    public function agregar_miembro(NodoNumerico $miembro): void
    {
        $this->_adyacente_con_peso($miembro, 'miembro_' . $miembro->id(), 1.0, 'pertenencia');
        $miembro->_adyacente_con_peso($this, 'conjunto_' . $this->id(), 1.0, 'pertenencia');
    }

    /**
     * Quita un miembro del concepto.
     *
     * @param NodoNumerico $miembro
     * @return void
     */
    public function quitar_miembro(NodoNumerico $miembro): void
    {
        $this->eliminar_adyacente('miembro_' . $miembro->id());
        $miembro->eliminar_adyacente('conjunto_' . $this->id());
    }

    /**
     * Devuelve los miembros actuales del concepto.
     *
     * @return NodoNumerico[]
     */
    public function miembros(): array
    {
        $miembros = [];
        $adyacentes = $this->adyacentes();
        if ($adyacentes) {
            foreach ($adyacentes as $nombre => $nodo) {
                if (str_starts_with($nombre, 'miembro_')) {
                    $miembros[] = $nodo;
                }
            }
        }
        return $miembros;
    }

    /**
     * Obtiene un concepto por su nombre (o null si no existe).
     *
     * @param string $nombre
     * @return NodoConjunto|null
     */
    public static function obtener(string $nombre): ?NodoConjunto
    {
        return self::$diccionario[$nombre] ?? null;
    }

    /**
     * Lista todos los conceptos registrados en el diccionario.
     *
     * @return array<string, NodoConjunto>
     */
    public static function listar_todos(): array
    {
        return self::$diccionario;
    }
}