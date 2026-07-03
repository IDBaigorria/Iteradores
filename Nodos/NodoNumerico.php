<?php

namespace Iteradores\Nodos;

use Iteradores\Configuracion\Conf;
use Iteradores\Configuracion\Entorno;
use Iteradores\Nodos\Interfaces\IdentidadNumerica;
use Iteradores\Nodos\Interfaces\FabricaDeNodosNumericos;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoElectrico;

include_once(__DIR__."/Interfaces/FabricaDeNodosNumericos.php");
include_once(__DIR__."/Interfaces/IdentidadNumerica.php");

/**
 * NodoNumerico – Orquestador central de identidades numéricas.
 *
 * Clase abstracta que actúa como **punto de creación y reciclaje** de todos los nodos
 * con identidad matricial. Es la implementación base de la que heredan
 * {@link NodoPrimo}, {@link NodoParalelo} y {@link NodoConjunto}.
 *
 * ## Responsabilidades principales
 *
 * 1. **Identidad multifase**  
 *    Cada instancia mantiene un mapa `identidad_por_fase[fase]` que asocia una
 *    {@link Matriz2x2} distinta a cada fase de trabajo. Esto permite que un mismo
 *    nodo represente una secuencia en la fase `a`, un conjunto en la fase `b` y un
 *    primo en la fase `c`, sin que las mutaciones del canvas `b` en una fase
 *    interfieran en las demás.
 *
 * 2. **Caché global de primos**  
 *    La lista estática `$primos_conocidos` crece con cada nuevo primo descubierto,
 *    compartida por todas las fases. Métodos como {@link es_numero_primo()} y
 *    {@link siguiente_numero_primo()} la consultan y expanden, evitando recalcular
 *    primalidad para números ya conocidos.
 *
 * 3. **Contadores multifase de primos**  
 *    Dos mapas `$ultimo_primo_positivo_por_fase` y `$ultimo_primo_negativo_por_fase`
 *    guardan el último primo asignado en cada fase para los espectros positivo
 *    (nodos primos) y negativo (conjuntos). Así cada fase posee su propio espacio
 *    de identidades independiente, esencial para el ascenso/descenso jerárquico.
 *
 * 4. **Pool de nodos libres**  
 *    Para evitar la creación innecesaria de instancias, la clase mantiene un
 *    conjunto de nodos reutilizables por fase (`$nodos_libres_por_fase`).
 *    Las fábricas obtienen nodos mediante {@link tomar_nodo_libre()} y los
 *    devuelven con {@link devolver_nodo_libre()} una vez que dejan de usarse
 *    (por ejemplo, tras ascender a otra fase).
 *
 * ## Entrelazamiento de conjunto (pintura)
 *
 * La entrada `b` de la {@link Matriz2x2} actúa como **canvas de pertenencia**.
 * Cuando un {@link NodoConjunto} agrega un miembro, ambos se «pintan» mutuamente:
 * el miembro multiplica su `b` por el primo de contexto del conjunto, y el conjunto
 * multiplica su `b` por el número primo del miembro. La verificación de pertenencia
 * es O(1) mediante el operador módulo, sin necesidad de índices externos.
 *
 * @package Iteradores\Nodos
 * @version 1.4.3
 * @since 1.4.2
 * @author Ignacio David Baigorria
 * @extends NodoElectrico
 * @implements FabricaDeNodosNumericos
 * @implements IdentidadNumerica
 */
class NodoNumerico extends NodoElectrico implements FabricaDeNodosNumericos, IdentidadNumerica
{
    /**
     * Indica si el nodo representa una secuencia ordenada.
     *
     * - `true`  → secuencia (producto no conmutativo de factores).
     * - `false` → conjunto o paralelo (producto conmutativo con marca).
     *
     * @var bool
     */
    protected bool $ordenado;

    /**
     * Identidad matricial del nodo, indexada por fase.
     *
     * Estructura:
     * ```
     * [
     *   'fase_a' => Matriz2x2,
     *   'fase_b' => Matriz2x2,
     *   ...
     * ]
     * ```
     *
     * @var array<string, Matriz2x2>
     * @see Matriz2x2
     */
    private array $identidad_por_fase = [];

    // ═══════════════════════════════════════════
    // CACHÉ DE PRIMOS (GLOBAL)
    // ═══════════════════════════════════════════

    /**
     * Caché global de números primos conocidos.
     *
     * Se inicializa con `[2, 3]` y crece bajo demanda. Compartida por todas las fases.
     *
     * @var int[]
     */
    private static array $primos_conocidos = [2, 3];

    /**
     * Último número primo positivo asignado en cada fase.
     *
     * Usado por {@link siguiente_primo_positivo()} para generar primos únicos.
     *
     * @var array<string, int>
     */
    protected static array $ultimo_primo_positivo_por_fase = [];

    /**
     * Último número primo (positivo) usado para crear identidades negativas
     * de conjuntos en cada fase.
     *
     * Usado por {@link siguiente_primo_negativo()} para generar primos únicos.
     *
     * @var array<string, int>
     */
    protected static array $ultimo_primo_negativo_por_fase = [];

    // ═══════════════════════════════════════════
    // POOL DE NODOS LIBRES
    // ═══════════════════════════════════════════

    /**
     * Nodos libres disponibles para reutilización, agrupados por fase.
     *
     * Cada entrada contiene un array de instancias de {@link NodoNumerico}
     * (o subclases) que pueden ser reasignadas en esa fase.
     *
     * @var array<string, NodoNumerico[]>
     */
    protected static array $nodos_libres_por_fase = [];

    /**
     * Constructor protegido.
     *
     * Inicializa la identidad de la fase actual con {@link Matriz2x2::inicial()}
     * y enlaza la matriz al nodo para sincronización directa.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->identidad_por_fase[self::$fase] = Matriz2x2::inicial();
        $this->identidad_por_fase[self::$fase]->_nodo($this);
    }

    // ═══════════════════════════════════════════
    // IDENTIDAD MULTIFASE
    // ═══════════════════════════════════════════

    /**
     * Obtiene la identidad matricial del nodo en la fase indicada.
     *
     * Si no existe una matriz para la fase solicitada, devuelve
     * {@link Matriz2x2::inicial()} (matriz semilla `[[1,1],[1,2]]`).
     *
     * @param string|null $fase Fase de trabajo (null = fase actual del sistema).
     * @return Matriz2x2
     */
    public function identidad(?string $fase = null): Matriz2x2
    {
        $fase = $fase ?? self::$fase;
        return $this->identidad_por_fase[$fase] ?? Matriz2x2::inicial();
    }

    /**
     * Asigna la identidad matricial del nodo en la fase indicada.
     *
     * Solo se permite en entorno de pruebas (ver {@link Entorno::permite_pruebas()}).
     * Además de almacenar la matriz, establece la referencia inversa con
     * {@link Matriz2x2::_nodo()} para sincronización directa.
     *
     * @param Matriz2x2 $matriz
     * @param string|null $fase
     * @return void
     */
    public function _identidad(Matriz2x2 $matriz, ?string $fase = null): void
    {
        $fase = $fase ?? self::$fase;
        $this->identidad_por_fase[$fase] = $matriz;
        $matriz->_nodo($this);
    }

    /**
     * Indica si el nodo representa una secuencia ordenada.
     *
     * @return bool
     */
    public function ordenado(): bool
    {
        return $this->ordenado;
    }

    /**
     * Indica si el nodo es un {@link NodoPrimo} (identidad atómica).
     *
     * Por defecto retorna `false`. La subclase {@link NodoPrimo} sobrescribe
     * este método para devolver `true`.
     *
     * @return bool
     */
    public function es_primo(): bool
    {
        return false;
    }

    // ═══════════════════════════════════════════
    // PRIMALIDAD (CACHÉ GLOBAL)
    // ═══════════════════════════════════════════

    /**
     * Verifica si un número entero es primo.
     *
     * Utiliza la caché global {@link $primos_conocidos}. Si el número no está
     * en la caché, se expande generando primos consecutivos hasta alcanzarlo
     * o descartarlo.
     *
     * @param int $numero Número a evaluar.
     * @return bool `true` si es primo, `false` en caso contrario.
     * @see siguiente_numero_primo()
     */
    public static function es_numero_primo(int $numero): bool
    {
        if ($numero < 2) return false;
        if (in_array($numero, self::$primos_conocidos, true)) return true;
        $max = end(self::$primos_conocidos);
        while ($max < $numero) {
            $max = self::calcular_siguiente_primo($max);
            self::$primos_conocidos[] = $max;
            if ($max === $numero) return true;
        }
        return false;
    }

    /**
     * Devuelve el menor número primo estrictamente mayor que `$n`.
     *
     * Utiliza y expande la caché global si es necesario.
     *
     * @param int $n Valor de partida.
     * @return int Siguiente número primo.
     */
    public static function siguiente_numero_primo(int $n): int
    {
        foreach (self::$primos_conocidos as $p) {
            if ($p > $n) return $p;
        }
        $candidato = end(self::$primos_conocidos);
        while ($candidato <= $n) {
            $candidato = self::calcular_siguiente_primo($candidato);
            self::$primos_conocidos[] = $candidato;
        }
        return $candidato;
    }

    /**
     * Calcula el primo inmediatamente superior a `$n` sin utilizar caché.
     *
     * @param int $n
     * @return int
     * @internal
     */
    private static function calcular_siguiente_primo(int $n): int
    {
        $candidato = $n + 1;
        while (true) {
            if (self::es_primo_simple($candidato)) return $candidato;
            $candidato++;
        }
    }

    /**
     * Test de primalidad simple (sin caché), para uso interno.
     *
     * @param int $num Número a evaluar.
     * @return bool
     * @internal
     */
    private static function es_primo_simple(int $num): bool
    {
        if ($num < 2) return false;
        if ($num === 2) return true;
        if ($num % 2 === 0) return false;
        $limite = (int) sqrt($num);
        for ($i = 3; $i <= $limite; $i += 2) {
            if ($num % $i === 0) return false;
        }
        return true;
    }

    // ═══════════════════════════════════════════
    // CONTADORES MULTIFASE
    // ═══════════════════════════════════════════

    /**
     * Devuelve el siguiente número primo disponible para **nodos primos**
     * (espectro positivo) en la fase indicada.
     *
     * Avanza el contador correspondiente y actualiza la caché.
     *
     * @param string|null $fase Fase de trabajo (null = fase actual).
     * @return int Nuevo número primo.
     * @see NodoPrimo
     */
    public static function siguiente_primo_positivo(?string $fase = null): int
    {
        $fase = $fase ?? self::$fase;
        $ultimo = self::$ultimo_primo_positivo_por_fase[$fase] ?? 2;
        $nuevo = self::siguiente_numero_primo($ultimo);
        self::$ultimo_primo_positivo_por_fase[$fase] = $nuevo;
        return $nuevo;
    }

    /**
     * Devuelve el siguiente número primo (positivo) para crear identidades
     * negativas de **conjuntos** en la fase indicada.
     *
     * @param string|null $fase
     * @return int Nuevo número primo (positivo) para un conjunto negativo.
     * @see NodoConjunto
     */
    public static function siguiente_primo_negativo(?string $fase = null): int
    {
        $fase = $fase ?? self::$fase;
        $ultimo = self::$ultimo_primo_negativo_por_fase[$fase] ?? 2;
        $nuevo = self::siguiente_numero_primo($ultimo);
        self::$ultimo_primo_negativo_por_fase[$fase] = $nuevo;
        return $nuevo;
    }

    // ═══════════════════════════════════════════
    // POOL DE NODOS LIBRES
    // ═══════════════════════════════════════════

    /**
     * Toma un nodo libre del pool para la fase indicada.
     *
     * Si el pool está vacío, crea una nueva instancia llamando a
     * {@link NodoElectrico::crear()}. Al tomar un nodo del pool, se limpia
     * su identidad anterior en esa fase por seguridad.
     *
     * @param string|null $fase
     * @return NodoNumerico Nodo reutilizado o recién creado.
     */
    public static function tomar_nodo_libre(?string $fase = null): NodoNumerico
    {
        $fase = $fase ?? self::$fase;
        if (!isset(self::$nodos_libres_por_fase[$fase])) {
            self::$nodos_libres_por_fase[$fase] = [];
        }
        if (!empty(self::$nodos_libres_por_fase[$fase])) {
            $nodo = array_shift(self::$nodos_libres_por_fase[$fase]);
            // Limpiar identidad anterior (por seguridad).
            unset($nodo->identidad_por_fase[$fase]);
            return $nodo;
        }
        return parent::crear();
    }

    /**
     * Devuelve un nodo al pool de libres de la fase para su futura reutilización.
     *
     * @param NodoNumerico $nodo
     * @param string|null $fase
     * @return void
     */
    public static function devolver_nodo_libre(NodoNumerico $nodo, ?string $fase = null): void
    {
        $fase = $fase ?? self::$fase;
        if (!isset(self::$nodos_libres_por_fase[$fase])) {
            self::$nodos_libres_por_fase[$fase] = [];
        }
        self::$nodos_libres_por_fase[$fase][] = $nodo;
    }

    // ═══════════════════════════════════════════
    // FÁBRICAS
    // ═══════════════════════════════════════════

    /**
     * Crea un nodo numérico compuesto (secuencia ordenada de p‑grama).
     *
     * La cantidad de componentes debe ser un número primo. La identidad
     * resultante es el **producto matricial no conmutativo** de las identidades
     * de los componentes en el orden proporcionado.
     *
     * Se toma un nodo del pool (o se crea uno nuevo) y se le enlazan los
     * componentes mediante adyacentes `factor_1`, `factor_2`, etc.
     *
     * @param NodoNumerico[] $componentes Componentes de la secuencia (cantidad prima).
     * @param int            $capacidad   Capacidad máxima de energía.
     * @param float          $fuga        Fuga de energía por ciclo.
     * @return NodoNumerico|null El nuevo nodo, o `null` si la cantidad no es prima.
     * @see NodoParalelo
     */
    public static function crear_numerico(
        array $componentes,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoNumerico {
        $cantidad = count($componentes);
        if (!self::es_numero_primo($cantidad)) {
            self::_error('La cantidad de componentes debe ser un número primo.');
            return null;
        }

        // Calcular identidad como producto en orden.
        $matriz = $componentes[0]->identidad();
        for ($i = 1; $i < $cantidad; $i++) {
            $matriz = $matriz->multiplicar($componentes[$i]->identidad());
        }

        $nodo = self::tomar_nodo_libre();
        $nodo->_identidad($matriz);
        $nodo->ordenado = true;
        $nodo->capacidad = $capacidad;
        $nodo->fuga = $fuga;

        // Enlazar componentes.
        for ($i = 0; $i < $cantidad; $i++) {
            $nodo->_adyacente_en($componentes[$i], 'factor_' . ($i + 1), true);
        }

        return $nodo;
    }

    /**
     * Crea un nodo primo con el número primo indicado.
     *
     * @param int   $primo      Número primo.
     * @param int   $capacidad  Capacidad máxima de energía.
     * @param float $fuga       Fuga de energía por ciclo.
     * @return NodoPrimo|null  El NodoPrimo creado, o `null` si el número no es primo.
     * @see NodoPrimo
     */
    public static function crear_primo(
        int $primo,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoPrimo {
        if (!self::es_numero_primo($primo)) {
            self::_error("El número {$primo} no es primo.");
            return null;
        }
        return NodoPrimo::_crear_interno($primo, $capacidad, $fuga);
    }

    /**
     * Crea un nodo de sincronización (paralelo) con los componentes dados.
     *
     * La cantidad de componentes debe ser un número primo. La identidad es
     * el producto conmutativo (orden canónico) antecedido por la marca de
     * sincronización {@link Conf::MATRIZ_MARCA_CONJUNTO}.
     *
     * @param NodoNumerico[] $componentes Componentes (cantidad prima).
     * @param int            $capacidad
     * @param float          $fuga
     * @return NodoParalelo|null
     * @see NodoParalelo
     */
    public static function crear_paralelo(
        array $componentes,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoParalelo {
        return NodoParalelo::_crear_interno($componentes, $capacidad, $fuga);
    }

    /**
     * Crea un nuevo concepto semántico (conjunto) vacío.
     *
     * El conjunto nace sin miembros; se irá poblando mediante pintura
     * a través de {@link NodoConjunto::agregar_miembro()}.
     *
     * @param int   $capacidad Capacidad máxima de energía.
     * @param float $fuga      Fuga de energía por ciclo.
     * @return NodoConjunto
     * @see NodoConjunto
     */
    public static function crear_conjunto(
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): NodoConjunto {
        return NodoConjunto::_crear_interno($capacidad, $fuga);
    }
}