<?php

namespace Iteradores\Nodos;

use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Matriz2x2;

/**
 * NodoConjunto – Concepto semántico con identidad negativa y pintura contextual.
 *
 * Representa un **color semántico** (por ejemplo "vocales", "verbos",
 * "dominio HTML"). Su identidad es una matriz negativa inmutable
 * `[[-n, 1], [1, 1]]`.
 *
 * ## Diccionario global
 * Un índice estático asocia el nombre del concepto a su instancia,
 * evitando duplicados. El método {@link _nombre()} registra el concepto
 * tras ser etiquetado (normalmente por un humano).
 *
 * ## Pintura y entrelazamiento cuántico
 * El NodoConjunto "pinta" a sus miembros multiplicando la entrada `b`
 * de sus matrices identidad por un número primo único. Esto permite:
 * - **Pertenencia O(1):** `miembro->identidad()->b % primo == 0`.
 * - **Intersección O(1):** `b % (primo1 * primo2) == 0`.
 * - **Propagación global:** el contexto viaja con la matriz al ascender.
 *
 * La pintura es reversible (despintar) y no altera las entradas `a`, `c`, `d`
 * de la identidad nuclear del miembro. Los enlaces con pesos multidimensionales
 * complementan la pertenencia binaria con el *grado* de asociación.
 *
 * ## Miembros dinámicos
 * Los métodos {@link agregar_miembro} y {@link quitar_miembro} gestionan
 * la membresía creando/eliminando enlaces de doble vía y aplicando la
 * pintura/despintura sobre la matriz del miembro.
 *
 * @extends NodoNumerico
 * @implements IdentidadNumerica (heredado)
 * @see Matriz2x2
 * @see NodoNumerico
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