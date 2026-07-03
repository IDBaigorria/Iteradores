<?php

namespace Iteradores\Nodos;

use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Matriz2x2;

/**
 * NodoConjunto – Concepto semántico con identidad negativa y pintura contextual.
 *
 * Representa un **concepto o color semántico** dentro del grafo de aprendizaje
 * (por ejemplo, "vocales", "verbos", "dominio HTML"). A diferencia de las
 * secuencias y los primos, un NodoConjunto **no representa una estructura**
 * sino un **significado compartido** por un grupo de nodos.
 *
 * ## Espectro negativo: separación entre estructura y significado
 *
 * El sistema de identidades numéricas divide el universo en dos espectros:
 *
 * | Espectro   | Forma canónica      | Representa                |
 * |------------|---------------------|---------------------------|
 * | Positivo   | `[[p, 1], [1, 1]]` | Estructura (NodoPrimo)    |
 * | Negativo   | `[[-p, 1], [1, 1]]`| Significado (NodoConjunto)|
 *
 * La matriz negativa se construye con {@link Matriz2x2::crear_negativa_prima()}
 * a partir de un número primo `p`. La entrada `a = -p` marca el nodo como
 * perteneciente al espectro semántico, mientras que `b = 1` mantiene el canvas
 * de contexto listo para ser pintado.
 *
 * Esta separación es fundamental:
 * - Las matrices positivas codifican **cómo se combinan** los elementos.
 * - Las matrices negativas codifican **qué son** esos elementos.
 * - Ambas coexisten en el mismo álgebra sin colisionar.
 *
 * ## Pintura bidireccional (entrelazamiento de conjunto)
 *
 * La pertenencia a un conjunto no se almacena en una tabla externa, sino que
 * se **codifica directamente en las matrices identidad** de los involucrados
 * mediante una operación reversible de pintura:
 *
 * 1. **Del conjunto al miembro:**  
 *    `miembro->identidad()->pintar(conjunto->primo_contexto())`  
 *    El miembro multiplica su canvas `b` por el `primo_contexto` del conjunto.
 *
 * 2. **Del miembro al conjunto (si el miembro es NodoPrimo):**  
 *    `conjunto->identidad()->pintar(miembro->numero_primo())`  
 *    El conjunto multiplica su canvas `b` por el número primo del miembro.
 *
 * Esta pintura es **simétrica y reversible**: se puede despintar (dividir)
 * cuando el miembro abandona el conjunto, restaurando los valores originales
 * de `b`. La verificación de pertenencia es O(1) mediante el operador módulo:
 *
 * ```
 * // ¿El nodo $nodo pertenece a este conjunto?
 * $nodo->identidad()->b % $this->primo_contexto == 0
 * ```
 *
 * ## Diccionario global de conceptos
 *
 * Los conjuntos pueden ser **nombrados** mediante {@link _nombre()}. Al
 * asignar un nombre, el conjunto se registra en un diccionario estático
 * global que permite recuperarlo posteriormente con {@link obtener()}.
 *
 * - Un conjunto sin nombre es **efímero**: puede crearse para agrupar
 *   temporalmente nodos durante el aprendizaje y ser reciclado después.
 * - Un conjunto con nombre es **persistente**: representa un concepto
 *   semántico descubierto por el sistema y validado (normalmente por un
 *   humano), y se conserva en el diccionario.
 *
 * @package Iteradores\Nodos
 * @version 1.4.3
 * @since 1.4.2
 * @author Ignacio David Baigorria
 * @extends NodoNumerico
 * @see Matriz2x2
 * @see NodoPrimo
 */
class NodoConjunto extends NodoNumerico
{
    /**
     * Primo de contexto usado para pintar a los miembros del conjunto.
     *
     * Este número primo único identifica al conjunto en el canvas `b` de
     * todos sus miembros. La pertenencia se verifica comprobando si `b`
     * del miembro es divisible por este valor.
     *
     * @var int
     */
    private int $primo_contexto;

    /**
     * Diccionario global de conceptos.
     *
     * Asocia nombres de conceptos (ej. "vocales") a sus instancias de
     * NodoConjunto. Solo los conjuntos nombrados con {@link _nombre()}
     * se registran aquí.
     *
     * @var array<string, NodoConjunto>
     */
    private static array $diccionario = [];

    /**
     * Constructor protegido.
     *
     * Inicializa el conjunto como no ordenado (`ordenado = false`), reflejando
     * que los miembros de un concepto no tienen un orden preestablecido.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->ordenado = false;
    }

    /**
     * Crea internamente un NodoConjunto (llamado por {@link NodoNumerico::crear_conjunto()}).
     *
     * El proceso de creación:
     * 1. Crea una nueva instancia.
     * 2. Obtiene un nuevo número primo del contador negativo mediante
     *    {@link NodoNumerico::siguiente_primo_negativo()}.
     * 3. Construye la identidad negativa prima con {@link Matriz2x2::crear_negativa_prima()}.
     * 4. Asigna la matriz al nodo y la enlaza.
     *
     * El conjunto nace **vacío**: los miembros se agregan posteriormente
     * con {@link agregar_miembro()}.
     *
     * @param int   $capacidad Capacidad máxima de energía.
     * @param float $fuga      Fuga de energía por ciclo.
     * @return NodoConjunto
     * @internal
     */
    public static function _crear_interno(
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): NodoConjunto {
        $nodo = new self();
        $nodo->capacidad = $capacidad;
        $nodo->fuga = $fuga;

        // Asignar identidad negativa prima.
        $primo = NodoNumerico::siguiente_primo_negativo();
        $nodo->primo_contexto = $primo;
        $nodo->_identidad(Matriz2x2::crear_negativa_prima($primo));

        return $nodo;
    }

    /**
     * Asigna un nombre al concepto y lo registra en el diccionario global.
     *
     * Si el nombre ya existe en el diccionario, se emite un error del sistema
     * y no se modifica nada. En caso contrario, el concepto se almacena en el
     * dato multidimensional (`'nombre_concepto'`) y se registra en el
     * diccionario para su recuperación posterior con {@link obtener()}.
     *
     * @param string $nombre Nombre del concepto (ej. "vocales").
     * @return void
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
     * @return string El nombre asignado, o `'sin_nombre'` si nunca se nombró.
     */
    public function nombre(): string
    {
        return $this->dato('nombre_concepto') ?? 'sin_nombre';
    }

    /**
     * Devuelve el primo de contexto del conjunto.
     *
     * Este valor es el factor que se multiplica en el canvas `b` de los
     * miembros al ser agregados, y permite verificar la pertenencia.
     *
     * @return int
     */
    public function primo_contexto(): int
    {
        return $this->primo_contexto;
    }

    /**
     * Agrega un miembro al concepto mediante **pintura bidireccional**.
     *
     * Realiza dos operaciones:
     *
     * 1. **Pinta al miembro:** multiplica el canvas `b` de la matriz
     *    identidad del miembro por el {@link primo_contexto()} del conjunto.
     *    Esto marca al miembro como perteneciente a este conjunto.
     *
     * 2. **Pinta al conjunto (si el miembro es {@link NodoPrimo}):**
     *    multiplica el canvas `b` de la matriz identidad del conjunto por
     *    el {@link NodoPrimo::numero_primo()} del miembro. Esto marca al
     *    conjunto como contenedor de ese miembro.
     *
     * Si el miembro no es un NodoPrimo (por ejemplo, es un NodoParalelo o
     * una secuencia), solo se realiza la pintura del miembro, no la inversa.
     *
     * @param NodoNumerico $miembro Nodo a agregar al concepto.
     * @return void
     * @see quitar_miembro()
     * @see tiene_miembro()
     */
    public function agregar_miembro(NodoNumerico $miembro): void
    {
        // Pintar al miembro con el primo del conjunto.
        $miembro->identidad()->pintar($this->primo_contexto);

        // Pintar al conjunto con el primo del miembro (si es NodoPrimo).
        if ($miembro instanceof NodoPrimo) {
            $this->identidad()->pintar($miembro->numero_primo());
        }
    }

    /**
     * Quita un miembro del concepto mediante **despintura bidireccional**.
     *
     * Revierte las operaciones de {@link agregar_miembro()}:
     *
     * 1. Despinta al miembro dividiendo su canvas `b` por el `primo_contexto`.
     * 2. Si el miembro es un NodoPrimo, despinta al conjunto dividiendo su
     *    canvas `b` por el `numero_primo` del miembro.
     *
     * Si el miembro no estaba pintado (no pertenecía al conjunto), la
     * operación {@link Matriz2x2::despintar()} registrará un error del
     * sistema pero no modificará la matriz.
     *
     * @param NodoNumerico $miembro Nodo a quitar del concepto.
     * @return void
     * @see agregar_miembro()
     */
    public function quitar_miembro(NodoNumerico $miembro): void
    {
        $miembro->identidad()->despintar($this->primo_contexto);

        if ($miembro instanceof NodoPrimo) {
            $this->identidad()->despintar($miembro->numero_primo());
        }
    }

    /**
     * Verifica si un nodo es miembro del conjunto.
     *
     * La verificación es O(1): simplemente comprueba si el canvas `b` de la
     * matriz identidad del nodo es divisible por el {@link primo_contexto()}.
     *
     * @param NodoNumerico $nodo Nodo a verificar.
     * @return bool `true` si el nodo pertenece al conjunto.
     */
    public function tiene_miembro(NodoNumerico $nodo): bool
    {
        return $nodo->identidad()->b % $this->primo_contexto === 0;
    }

    /**
     * Obtiene un concepto del diccionario global por su nombre.
     *
     * @param string $nombre Nombre del concepto.
     * @return NodoConjunto|null El concepto, o `null` si no existe.
     */
    public static function obtener(string $nombre): ?NodoConjunto
    {
        return self::$diccionario[$nombre] ?? null;
    }

    /**
     * Lista todos los conceptos registrados en el diccionario global.
     *
     * @return array<string, NodoConjunto>
     */
    public static function listar_todos(): array
    {
        return self::$diccionario;
    }
}