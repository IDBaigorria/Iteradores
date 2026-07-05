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
 * Clase base de la que heredan {@link NodoPrimo} y {@link NodoParalelo}.
 * Actúa como **punto de creación y reciclaje** de todos los nodos con
 * identidad matricial, y gestiona el ascenso y descenso entre fases.
 *
 * ## Responsabilidades principales
 *
 * 1. **Identidad única e inmutable**
 *    Cada instancia posee una {@link Matriz2x2} que la identifica de forma
 *    unívoca. Esta matriz se asigna en la creación y **no depende de la fase**.
 *
 * 2. **P‑grama único**
 *    El array `$pgrama` almacena la lista exacta de factores que componen
 *    el nodo. Es la **única fuente de verdad** sobre la identidad compuesta.
 *    Las marcas especiales al inicio del array indican el tipo:
 *    - `1`: paralelo (sincronización de componentes simultáneos).
 *    - `-1`: secuencia de deshacer (todos los factores son comandos destructivos).
 *    - Sin marca: secuencia de hacer (comandos constructivos).
 *
 * 3. **Caché global de primos**
 *    La lista estática `$primos_conocidos` crece con cada nuevo primo descubierto,
 *    compartida por todas las fases. Métodos como {@link es_numero_primo()} y
 *    {@link siguiente_numero_primo()} la consultan y expanden.
 *
 * 4. **Contador multifase de primos**
 *    El mapa `$ultimo_primo_positivo_por_fase` guarda el último primo asignado en
 *    cada fase para los comandos constructivos (positivos) y destructivos (negativos).
 *
 * 5. **Pool de nodos libres**
 *    Para evitar la creación innecesaria de instancias, la clase mantiene un
 *    conjunto de nodos reutilizables por fase (`$nodos_libres_por_fase`).
 *    Las fábricas obtienen nodos mediante {@link tomar_nodo_libre()} y los
 *    devuelven con {@link devolver_nodo_libre()}.
 *
 * 6. **Ascenso y descenso entre fases**
 *    El método {@link ascender()} promociona un nodo compuesto a la fase superior,
 *    guardando su p‑grama y el nombre de la fase actual en un {@link NodoPrimo}
 *    libre. No libera el nodo actual. El método estático {@link descender()}
 *    reconstruye el nodo compuesto en la fase original a partir del p‑grama
 *    guardado. Las marcas `1` y `-1` determinan el tipo de composición.
 *
 * ## Identidad matricial inmutable
 *
 * A partir de la versión 1.4.5, la {@link Matriz2x2} es completamente inmutable
 * (`b = 0` fijo) y **única por nodo**. Ya no se mantienen identidades distintas
 * por fase.
 *
 * @package Iteradores\Nodos
 * @version 1.4.5
 * @since 1.4.2
 * @author Ignacio David Baigorria
 * @extends NodoElectrico
 * @implements FabricaDeNodosNumericos
 * @implements IdentidadNumerica
 * @see Matriz2x2
 * @see NodoPrimo
 * @see NodoParalelo
 */
class NodoNumerico extends NodoElectrico implements FabricaDeNodosNumericos, IdentidadNumerica
{
    /**
     * Identidad matricial del nodo (única).
     *
     * @var Matriz2x2
     * @see Matriz2x2
     */
    private Matriz2x2 $identidad;

    /**
     * P‑grama de factores (único).
     *
     * Almacena la secuencia exacta de identificadores que componen el nodo.
     * Es la **única fuente de verdad** para el ascenso/descenso.
     *
     * - **Secuencia:** `[p₁, p₂, …, pₚ]`
     * - **Paralelo:** `[1, p₁, p₂, …, pₚ]` (primos en orden canónico)
     *
     * @var int[]
     */
    private array $pgrama;

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
     * Último número primo asignado en cada fase.
     *
     * Usado por {@link siguiente_primo_positivo()} para generar primos únicos,
     * tanto para comandos constructivos como destructivos.
     *
     * @var array<string, int>
     */
    protected static array $ultimo_primo_positivo_por_fase = [];

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
     * Inicializa la identidad con {@link Matriz2x2::inicial()} y el p‑grama
     * como array vacío. Enlaza la matriz al nodo.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->identidad = Matriz2x2::inicial();
        $this->identidad->_nodo($this);
        $this->pgrama = [];
    }

    // ═══════════════════════════════════════════
    // IDENTIDAD ÚNICA
    // ═══════════════════════════════════════════

    /**
     * Obtiene la identidad matricial del nodo.
     *
     * @return Matriz2x2
     */
    public function identidad(): Matriz2x2
    {
        return $this->identidad;
    }

    /**
     * Asigna la identidad matricial del nodo.
     *
     * Solo se permite en entorno de pruebas (ver {@link Entorno::permite_pruebas()}).
     * Establece la referencia inversa con {@link Matriz2x2::_nodo()}.
     *
     * @param Matriz2x2 $matriz
     * @return void
     */
    public function _identidad(Matriz2x2 $matriz): void
    {
        $this->identidad = $matriz;
        $matriz->_nodo($this);
    }

    // ═══════════════════════════════════════════
    // P-GRAMA ÚNICO
    // ═══════════════════════════════════════════

    /**
     * Obtiene el p‑grama de factores del nodo.
     *
     * @return int[] Lista de identificadores, o array vacío si no tiene.
     */
    public function pgrama(): array
    {
        return $this->pgrama;
    }

    /**
     * Asigna el p‑grama de factores.
     *
     * @param int[] $pgrama Lista de identificadores.
     * @return void
     */
    public function _pgrama(array $pgrama): void
    {
        $this->pgrama = $pgrama;
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
     * en la fase indicada.
     *
     * Avanza el contador correspondiente y actualiza la caché. El mismo
     * contador se usa para comandos constructivos (positivos) y destructivos
     * (negativos), ya que ambos comparten el mismo espacio de identidades
     * primas; el signo lo determina el llamante al crear la matriz.
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

    // ═══════════════════════════════════════════
    // POOL DE NODOS LIBRES
    // ═══════════════════════════════════════════

    /**
     * Toma un nodo libre del pool para la fase indicada.
     *
     * Si el pool está vacío, crea una nueva instancia llamando a
     * {@link NodoElectrico::crear()}. Al tomar un nodo del pool, se limpian
     * su identidad y p‑grama anteriores por seguridad.
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
            // Limpiar identidad y p‑grama anteriores (por seguridad).
            $nodo->identidad = Matriz2x2::inicial();
            $nodo->pgrama = [];
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
    // ASCENSO Y DESCENSO ENTRE FASES
    // ═══════════════════════════════════════════

    /**
     * Asciende el nodo compuesto a la fase superior.
     *
     * El proceso de ascenso:
     * 1. Recopila el p‑grama del nodo.
     * 2. Obtiene un {@link NodoPrimo} libre en la fase de destino.
     * 3. Guarda en el dato multidimensional del primo (dimensión `'abajo'`)
     *    un paquete con el p‑grama de factores y el nombre de la fase actual.
     * 4. No libera el nodo actual; esa responsabilidad es del iterador de aprendizaje.
     *
     * @param string $fase_destino Nombre de la fase superior a la que ascender.
     * @return NodoPrimo El NodoPrimo que representa al nodo compuesto en la fase superior.
     * @throws \RuntimeException Si el nodo no tiene p‑grama.
     */
    public function ascender(string $fase_destino): NodoPrimo
    {
        $fase_actual = self::$fase;

        // Obtener el p‑grama del nodo.
        $factores = $this->pgrama();
        if (empty($factores)) {
            self::_error('El nodo no tiene p‑grama para ascender.');
            throw new \RuntimeException('El nodo no tiene p‑grama para ascender.');
        }

        // Obtener NodoPrimo libre en la fase destino.
        self::$fase = $fase_destino;
        $primo_superior = NodoPrimo::siguiente_primo_libre($fase_destino);
        self::$fase = $fase_actual;

        if ($primo_superior === null) {
            self::_error('No hay NodoPrimo libre en la fase destino.');
            throw new \RuntimeException('No hay NodoPrimo libre en la fase destino.');
        }

        // Guardar el p‑grama y el nombre de la fase actual en el primo superior.
        $primo_superior->_dato([
            'factores'    => $factores,
            'fase_origen' => $fase_actual,
        ], 'abajo');

        // El nodo actual NO se devuelve al pool; permanece activo para el iterador.

        return $primo_superior;
    }

    /**
     * Desciende un nodo compuesto desde un NodoPrimo superior a la fase original.
     *
     * El proceso de descenso:
     * 1. Lee el dato `'abajo'` del {@link NodoPrimo} superior, que contiene
     *    el p‑grama de factores y el nombre de la fase origen (guardados por
     *    {@link ascender}).
     * 2. Determina el tipo de composición observando el primer elemento del
     *    p‑grama:
     *    - `1`: paralelo.
     *    - `-1`: secuencia de deshacer.
     *    - otro: secuencia de hacer.
     * 3. Crea los {@link NodoPrimo} correspondientes a cada factor y construye
     *    el nodo compuesto en la fase origen con la fábrica adecuada.
     *
     * @param NodoPrimo $primo_superior El NodoPrimo en la fase superior que
     *                                  contiene el p‑grama y la fase origen.
     * @return NodoNumerico El nodo compuesto reconstruido en la fase origen.
     * @throws \RuntimeException Si el dato 'abajo' no existe, no contiene factores
     *                          o no contiene el nombre de la fase origen.
     */
    public static function descender(NodoPrimo $primo_superior): NodoNumerico
    {
        $paquete = $primo_superior->dato('abajo');

        if ($paquete === null || !isset($paquete['factores']) || !isset($paquete['fase_origen'])) {
            self::_error('El NodoPrimo no contiene un paquete de descenso válido (factores y fase_origen).');
            throw new \RuntimeException('El NodoPrimo no contiene un paquete de descenso válido.');
        }

        $factores = $paquete['factores'];
        $fase_origen = $paquete['fase_origen'];

        // Determinar el tipo de composición según la marca inicial.
        $es_paralelo  = ($factores[0] === 1);
        $es_deshacer  = ($factores[0] === -1);
        if ($es_paralelo || $es_deshacer) {
            array_shift($factores); // quitar la marca (1 o -1)
        }

        // Crear los componentes (NodoPrimo) a partir de los factores reales.
        $componentes = [];
        foreach ($factores as $primo) {
            $componentes[] = self::crear_primo($primo);
        }

        // Cambiar a la fase origen para la creación.
        $fase_actual = self::$fase;
        self::$fase = $fase_origen;

        // Crear el nodo compuesto con la fábrica adecuada.
        if ($es_paralelo) {
            $nodo = self::crear_paralelo($componentes);
        } else {
            $nodo = self::crear_numerico($componentes);
        }

        self::$fase = $fase_actual;
        return $nodo;
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
     * Si todos los componentes son deshaceres (primos negativos), se antepone
     * la marca `-1` al p‑grama.
     *
     * Se toma un nodo del pool (o se crea uno nuevo) y se le asignan la
     * matriz, el p‑grama y la capacidad/fuga.
     * **No se crean enlaces internos**; esa es responsabilidad del iterador.
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
        $factores = [];
        $es_deshacer = true;
        foreach ($componentes as $comp) {
            if ($comp instanceof NodoPrimo) {
                $factores[] = $comp->numero_primo();
                if ($comp->numero_primo() > 0) {
                    $es_deshacer = false;
                }
            }
        }
        for ($i = 1; $i < $cantidad; $i++) {
            $matriz = $matriz->multiplicar($componentes[$i]->identidad());
        }

        // Si todos los componentes son deshaceres, anteponer marca -1.
        if ($es_deshacer && !empty($factores)) {
            array_unshift($factores, -1);
        }

        $nodo = self::tomar_nodo_libre();
        $nodo->_identidad($matriz);
        $nodo->_pgrama($factores);
        $nodo->capacidad = $capacidad;
        $nodo->fuga = $fuga;

        return $nodo;
    }

    /**
     * Crea un nodo primo con el número primo indicado.
     *
     * @param int   $primo      Número primo (positivo para comando constructivo,
     *                          negativo para destructivo).
     * @param int   $capacidad  Capacidad máxima de energía.
     * @param float $fuga       Fuga de energía por ciclo.
     * @return NodoPrimo|null  El NodoPrimo creado, o `null` si el valor absoluto no es primo.
     * @see NodoPrimo
     */
    public static function crear_primo(
        int $primo,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoPrimo {
        if (!self::es_numero_primo(abs($primo))) {
            self::_error("El valor absoluto de {$primo} no es primo.");
            return null;
        }
        return NodoPrimo::_crear_interno($primo, $capacidad, $fuga);
    }

    /**
     * Crea un nodo de sincronización (paralelo) con los componentes dados.
     *
     * La cantidad de componentes debe ser un número primo. La identidad es
     * el producto conmutativo (orden canónico) con la marca `1` antepuesta
     * en el p‑grama.
     *
     * Delega completamente en {@link NodoParalelo::_crear_interno()}.
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

    // ═══════════════════════════════════════════
    // V 1.4.5
    // ═══════════════════════════════════════════

    /**
     * Devuelve la secuencia de matrices de identidad correspondientes a los
     * factores primos del p‑grama en el orden canónico, omitiendo las marcas
     * de sincronización (1, -1).
     *
     * @return Matriz2x2[] Secuencia de matrices del nodo.
     * @since 1.4.5
     */
    public function secuencia_de_matrices(): array
    {
        $matrices = [];

        foreach ($this->pgrama as $p) {
            if ($p === 1 || $p === -1) {
                continue;
            }
            if ($p > 0) {
                $matrices[] = Matriz2x2::crear_prima($p);
            } else {
                $matrices[] = Matriz2x2::crear_negativa_prima(-$p);
            }
        }

        return $matrices;
    }
}