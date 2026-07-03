<?php

namespace Iteradores\Nodos;

use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Matriz2x2;

/**
 * NodoPrimo – Identidad prima canónica e indivisible.
 *
 * Representa la **unidad atómica** del grafo de aprendizaje. Cada NodoPrimo
 * encapsula un número primo y lo expresa matricialmente mediante la forma
 * canónica `[[p, 1], [1, 1]]` (ver {@link Matriz2x2::crear_prima()}).
 *
 * Estos nodos constituyen el **alfabeto** de cada fase:
 * - En la fase 0 son los bytes (valores 0‑255).
 * - En fases superiores son conceptos que han ascendido desde una fase
 *   inferior y se representan con un nuevo número primo.
 *
 * ## Responsabilidades principales
 *
 * 1. **Identidad prima inmutable (salvo canvas `b`)**
 *    Las entradas `a`, `c` y `d` de su {@link Matriz2x2} son fijas. Solo la
 *    entrada `b` (canvas de pertenencia) puede modificarse para reflejar
 *    la pertenencia a conjuntos, sin alterar la identidad nuclear del nodo.
 *
 * 2. **Pool de primos libres por fase**
 *    Para optimizar el ascenso de nodos compuestos, la clase mantiene un
 *    conjunto de instancias reutilizables por fase (`$primos_libres_por_fase`).
 *    Los métodos {@link siguiente_primo_libre()} y {@link devolver_primo_libre()}
 *    gestionan este pool.
 *
 * 3. **Factorización bloqueada en su propia fase**
 *    Un NodoPrimo no puede descomponerse dentro de la misma fase. El método
 *    {@link factorizar()} lanza una excepción. La factorización solo tiene
 *    sentido al descender de fase, donde se recupera la matriz compuesta
 *    original desde el dato `'abajo'`.
 *
 * ## Herencia y polimorfismo
 *
 * Sobrescribe {@link NodoNumerico::es_primo()} para devolver `true`. Esto
 * permite consultar la naturaleza atómica de cualquier nodo de la jerarquía
 * sin necesidad de verificar su clase concreta.
 *
 * ## Relación con el canvas de contexto
 *
 * El canvas `b` de su matriz identidad puede ser pintado por {@link NodoConjunto}
 * para indicar pertenencia. A su vez, el NodoPrimo puede pintar a los conjuntos
 * a los que pertenece, creando un **entrelazamiento de conjunto** verificable
 * en O(1) mediante el operador módulo.
 *
 * @package Iteradores\Nodos
 * @version 1.4.3
 * @since 1.4.2
 * @author Ignacio David Baigorria
 * @extends NodoNumerico
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
     * Pool de primos libres disponibles para reutilización, indexado por fase.
     *
     * Cada entrada es un array de instancias de NodoPrimo que pueden ser
     * reclamadas mediante {@link siguiente_primo_libre()}.
     *
     * @var array<string, NodoPrimo[]>
     */
    private static array $primos_libres_por_fase = [];

    /**
     * Límite máximo de primos libres que se permite generar en cada fase.
     *
     * Se configura con {@link inicializar_fase()}. Si no se establece un
     * límite, se asume `PHP_INT_MAX` (sin límite).
     *
     * @var array<string, int>
     */
    private static array $limites_por_fase = [];

    /**
     * Constructor protegido.
     *
     * Inicializa el nodo con el número primo indicado, asigna la matriz
     * canónica correspondiente y marca el nodo como ordenado (aunque un
     * primo no es una secuencia, internamente se trata como componente
     * unitario ordenado).
     *
     * @param int $numero_primo Número primo a encapsular.
     */
    protected function __construct(int $numero_primo)
    {
        parent::__construct();
        $this->numero_primo = $numero_primo;
        $this->ordenado = true;
        $this->_identidad(Matriz2x2::crear_prima($numero_primo));
    }

    /**
     * Crea internamente un NodoPrimo **sin añadirlo al pool de libres**.
     *
     * Este método es invocado por {@link NodoNumerico::crear_primo()} y por
     * {@link siguiente_primo_libre()} cuando el pool está vacío. El nodo
     * creado no se registra automáticamente en el pool; si se desea
     * reciclarlo más tarde, debe llamarse explícitamente a
     * {@link devolver_primo_libre()}.
     *
     * @param int   $numero_primo Número primo validado.
     * @param int   $capacidad    Capacidad máxima de energía.
     * @param float $fuga         Fuga de energía por ciclo.
     * @return NodoPrimo
     * @internal
     */
    public static function _crear_interno(
        int $numero_primo,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): NodoPrimo {
        $nodo = new self($numero_primo);
        $nodo->capacidad = $capacidad;
        $nodo->fuga = $fuga;
        // Ya no se añade al pool aquí.
        return $nodo;
    }

    /**
     * Devuelve el número primo encapsulado por el nodo.
     *
     * @return int
     */
    public function numero_primo(): int { return $this->numero_primo; }

    /**
     * Indica que el nodo es un NodoPrimo (identidad atómica).
     *
     * Sobrescribe {@link NodoNumerico::es_primo()} para devolver `true`.
     *
     * @return bool
     */
    public function es_primo(): bool { return true; }

    /**
     * Intento de factorización bloqueado a nivel de la misma fase.
     *
     * Los NodoPrimo no pueden descomponerse dentro de su propia fase.
     * La factorización solo es posible al descender de fase, donde se
     * utiliza la información almacenada en el dato multidimensional
     * (por ejemplo, `dato('matriz_compuesta', 'abajo')`).
     *
     * @return void
     * @throws \BadMethodCallException Siempre lanza esta excepción.
     */
    public function factorizar(): void
    {
        throw new \BadMethodCallException('Un NodoPrimo no puede factorizarse en su misma fase.');
    }

    // ═══════════════════════════════════════════
    // GESTIÓN DE PRIMOS LIBRES
    // ═══════════════════════════════════════════

    /**
     * Inicializa la reserva de primos libres para una fase determinada.
     *
     * Establece el límite máximo de primos que se pueden generar en esa fase
     * y asegura que el pool exista (aunque esté vacío).
     *
     * @param string $fase  Nombre de la fase.
     * @param int    $limite Cantidad máxima de primos libres en la fase.
     * @return void
     */
    public static function inicializar_fase(string $fase, int $limite): void
    {
        self::$limites_por_fase[$fase] = $limite;
        if (!isset(self::$primos_libres_por_fase[$fase])) {
            self::$primos_libres_por_fase[$fase] = [];
        }
    }

    /**
     * Devuelve el siguiente NodoPrimo libre en la fase indicada.
     *
     * El algoritmo de selección es:
     * 1. Si hay nodos disponibles en el pool de la fase, extrae y retorna
     *    el primero (FIFO).
     * 2. Si el pool está vacío pero no se ha alcanzado el límite de primos
     *    configurado para la fase, crea un nuevo NodoPrimo usando
     *    {@link NodoNumerico::siguiente_primo_positivo()} y lo retorna
     *    **sin añadirlo al pool**.
     * 3. Si se ha alcanzado el límite, retorna `null`.
     *
     * @param string|null $fase Fase de trabajo (null = fase actual).
     * @return NodoPrimo|null Un NodoPrimo listo para usar, o null si se
     *                        agotó el límite de primos en la fase.
     */
    public static function siguiente_primo_libre(?string $fase = null): ?NodoPrimo
    {
        $fase = $fase ?? self::$fase;

        if (!isset(self::$primos_libres_por_fase[$fase])) {
            self::$primos_libres_por_fase[$fase] = [];
        }

        // Si hay libres, devolvemos el primero.
        if (!empty(self::$primos_libres_por_fase[$fase])) {
            return array_shift(self::$primos_libres_por_fase[$fase]);
        }

        // Si no hay límite configurado o no se alcanzó, crear uno nuevo.
        $limite = self::$limites_por_fase[$fase] ?? PHP_INT_MAX;
        if ($limite > 0) {
            $primo = NodoNumerico::siguiente_primo_positivo($fase);
            return self::_crear_interno($primo);  // no se añade al pool
        }

        return null;
    }

    /**
     * Devuelve un NodoPrimo al pool de libres de una fase.
     *
     * Tras invocar este método, el nodo podrá ser reclamado nuevamente por
     * {@link siguiente_primo_libre()} en la misma fase. Es útil para reciclar
     * primos que ya no están en uso (por ejemplo, tras descender un nodo
     * compuesto a su fase original).
     *
     * @param NodoPrimo $nodo Nodo a devolver al pool.
     * @param string|null $fase Fase a la que se devuelve (null = fase actual).
     * @return void
     */
    public static function devolver_primo_libre(NodoPrimo $nodo, ?string $fase = null): void
    {
        $fase = $fase ?? self::$fase;
        if (!isset(self::$primos_libres_por_fase[$fase])) {
            self::$primos_libres_por_fase[$fase] = [];
        }
        self::$primos_libres_por_fase[$fase][] = $nodo;
    }
}