<?php

namespace Iteradores\Nodos;
use Iteradores\Configuracion\Conf;
use Iteradores\Configuracion\Entorno;
use Iteradores\Nodos\Interfaces\IdentidadNumerica;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Nodos\Interfaces\FabricaDeNodosNumericos;

include_once('Interfaces/FabricaDeNodosNumericos.php');

/**
 * NodoNumerico – Nodo eléctrico con identidad matricial 2×2.
 *
 * Representa tanto **secuencias ordenadas** como **conjuntos desordenados**
 * dentro del grafo de aprendizaje. Es la base de la inteligencia emergente
 * del framework Iteradores.
 *
 * ## Visión general: del léxico al concepto
 *
 * La arquitectura de fases permite que el sistema escale en comprensión:
 *
 * - **Nivel Léxico (Fase 0 y 1):** Se detectan p-gramas de bytes, donde p es
 *   un número primo pequeño (2, 3, 5, 7...). Los NodoNumerico con `ordenado = true`
 *   representan concatenaciones de exactamente p factores. Su identidad es el
 *   producto matricial de los factores en orden.
 *
 * - **Nivel Morfológico (Fases intermedias):** Un iterador especialista agrupa
 *   nodos que cumplen el mismo rol (ej. vocales, consonantes, espacios) en
 *   NodoNumerico con `ordenado = false`. La matriz resultante incluye una
 *   **marca de conjunto** (`Conf::MATRIZ_MARCA_CONJUNTO`) que la distingue de
 *   cualquier secuencia.
 *
 * - **Nivel Conceptual (Fases altas):** Los conjuntos ascienden y se vuelven
 *   NodoPrimo en fases superiores, donde pueden combinarse para formar
 *   conceptos más abstractos (sílabas, palabras, significados).
 *
 * ## Los dos roles del Conjunto
 *
 * La clase distingue dos naturalezas complementarias para los conjuntos:
 *
 * 1.  **Rol Operativo (Sincronización):** Agrupa señales que ocurren
 *     simultáneamente en un mismo pulso de tiempo (ej. dominios activos en
 *     el Tálamo). Se construye con la fábrica {@link crear_conjunto()},
 *     que aplica la marca de conjunto y multiplica las identidades
 *     ordenadas canónicamente. Este es el mecanismo implementado.
 *
 * 2.  **Rol Cognitivo (Descubrimiento Semántico):** Agrupa nodos que
 *     comparten un comportamiento o significado (ej. todas las vocales).
 *     **No se construye con una fábrica**, sino que emerge de la topología
 *     del grafo mediante el **patrón "Color"** (ver más abajo).
 *
 * ## El patrón "Color": descubrimiento semántico sin modificar la clase
 *
 * Un iterador especialista puede detectar que varios nodos comparten un
 * rol semántico (por ejemplo, son intercambiables en ciertos contextos,
 * o sus energías y pesos forman un clúster). En lugar de crear un nuevo
 * NodoNumerico para representar el conjunto, "pinta" los nodos existentes
 * y teje una red de atajos entre ellos usando **exclusivamente las
 * capacidades ya implementadas** en {@link NodoElectrico}:
 *
 * | Herramienta actual       | Rol en el patrón "Color"                 |
 * |--------------------------|------------------------------------------|
 * | `_dato('color', 'vocal')`| Etiqueta semántica del nodo              |
 * | `_adyacente_con_peso()`  | Teje la red entre nodos del mismo color  |
 * | `peso('vocal')`          | Refuerza la pertenencia al conjunto      |
 *
 * **Ventaja clave:** los nodos no necesitan saber que pertenecen a un
 * conjunto. El iterador simplemente les asigna una dimensión de dato y
 * crea los enlaces. La semántica emerge de la topología, no de una clase
 * especial. Cuando un nodo asciende, sus colores se propagan a la fase
 * superior a través de `dato('colores', [...])`.
 *
 * ## Máquina de inferencia: cómo razonará el Tálamo
 *
 * El patrón "Color" habilita patrones de razonamiento avanzados sobre
 * el grafo, todos basados en las primitivas de **energía**, **pesos** y
 * **adyacentes**:
 *
 * - **Resonancia Semántica:** Al activar un nodo, el Tálamo inyecta energía
 *   a todos los nodos del mismo color, "precalentándolos".
 * - **Predicción Contextual:** En un contexto dado, el Tálamo aumenta el
 *   peso de los enlaces que salen de los nodos de un cierto color.
 * - **Generalización:** Una regla aprendida sobre un nodo puede aplicarse
 *   a todos los nodos de su mismo color.
 * - **Colapso y Decisión:** En caso de ambigüedad, dos colores compiten
 *   inyectando energía a sus caminos. El primero en superar un umbral
 *   gana y silencia al otro.
 * - **Síntesis Creativa:** En fases altas, el Tálamo puede crear un nuevo
 *   color combinando dos existentes (ej. `música` + `matemáticas`).
 *
 * ## Energía: ¿multidimensional?
 *
 * Se ha decidido **no** implementar energía multidimensional por fase en
 * esta versión. La energía sigue siendo un valor escalar por fase.
 * La separación de contextos se logra mediante fases distintas o mediante
 * los pesos en los enlaces. Si en el futuro se requiere un aislamiento
 * total entre dimensiones de energía, se podrá extender la propiedad
 * `energia` a un array, pero por ahora la complejidad añadida no se
 * justifica.
 *
 * ## p-gramas primos
 *
 * A diferencia de los bigramas tradicionales, el sistema opera con p-gramas
 * donde p es un número primo pequeño (2, 3, 5, 7...). Esto evita la explosión
 * binaria (crecimiento exponencial de la profundidad del grafo) al permitir
 * aplanar estructuras:
 *
 * - Un trigrama (p=3) no necesita ser una jerarquía de dos bigramas; puede
 *   ser un solo NodoNumerico con tres factores directos.
 * - Un pentagrama (p=5) captura patrones más largos en un solo nivel.
 * - La restricción a primos mantiene la elegancia algebraica y simplifica la
 *   factorización.
 *
 * ## Mecanismo de identidad
 *
 * | `ordenado` | Operación interna                                                 | Resultado                                                             |
 * |------------|-------------------------------------------------------------------|-----------------------------------------------------------------------|
 * | `true`     | `M(factor1) × M(factor2) × ... × M(factorP)`                      | Producto no conmutativo que preserva el orden                         |
 * | `false`    | `M_marca × M(comp1) × M(comp2) × ... × M(compP)` (orden canónico) | Producto con prefijo de tipo, conmutativo respecto a los componentes  |
 *
 * La **marca de conjunto** (`Conf::MATRIZ_MARCA_CONJUNTO`) es una matriz
 * constante `[[1, 1], [0, 1]]` que actúa como firma algebraica de "conjunto".
 *
 * ## Ascenso entre fases
 *
 * Cuando un NodoNumerico acumula suficiente energía y es promocionado:
 * - Se busca un NodoPrimo libre en la fase superior mediante
 *   {@link siguiente_primo_libre()}.
 * - El NodoPrimo recibe la matriz identidad del NodoNumerico como dato
 *   en la dimensión `'abajo'`.
 * - El NodoNumerico guarda una referencia al NodoPrimo en `dato('arriba')`.
 *
 * @package Iteradores\Nodos
 * @implements FabricaDeNodosNumericos
 * @implements IdentidadNumerica
 * @version 1.4.2
 * @since 1.4.2
 * @author Ignacio David Baigorria
 * @extends NodoElectrico
 */
class NodoNumerico extends NodoElectrico implements FabricaDeNodosNumericos, IdentidadNumerica
{
    /**
     * Indica si el nodo representa una secuencia ordenada (true)
     * o un conjunto desordenado (false).
     *
     * @var bool
     */
    protected bool $ordenado;

    /**
     * Identidad matricial del nodo.
     *
     * @var Matriz2x2
     */
    private Matriz2x2 $identidad;

    /**
     * Índice global de identidades.
     * [string => NodoNumerico]
     *
     * @var array
     */
    protected static array $indice_identidad = [];

    /**
     * Pool de primos libres por fase.
     * [fase => [NodoPrimo, ...]]
     *
     * @var array
     */
    private static array $primos_libres_por_fase = [];

    /**
     * Límite máximo de primos por fase.
     * [fase => int]
     *
     * @var array
     */
    private static array $limites_por_fase = [];

    /**
     * Siguiente número primo a usar para crear un nuevo NodoPrimo.
     * [fase => int]
     *
     * @var array
     */
    private static array $siguiente_primo_a_crear = [];

    // -----------------------------------------------------------------
    // INTERFAZ FABRICADENODOSNUMERICOS
    // -----------------------------------------------------------------

    /**
     * Cache de la matriz de marca para conjuntos.
     * Se inicializa una sola vez.
     *
     * @var Matriz2x2|null
     */
    private static ?Matriz2x2 $matriz_marca_conjunto = null;

    /**
     * Devuelve la matriz de marca de conjunto (cacheada).
     *
     * @return Matriz2x2
     */
    private static function obtener_matriz_marca(): Matriz2x2
    {
        if (self::$matriz_marca_conjunto === null) {
            $m = Conf::MATRIZ_MARCA_CONJUNTO;
            self::$matriz_marca_conjunto = new Matriz2x2($m[0][0], $m[0][1], $m[1][0], $m[1][1]);
        }
        return self::$matriz_marca_conjunto;
    }

     /**
     * Constructor protegido. Inicializa identidad neutra por defecto.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->identidad = Matriz2x2::neutra();
    }

    /**
     * Devuelve la matriz identidad del nodo.
     *
     * @return Matriz2x2
     */
    public function identidad(): Matriz2x2
    {
        return $this->identidad;
    }

    /**
     * Indica si el nodo es una secuencia ordenada.
     *
     * @return bool
     */
    public function ordenado(): bool
    {
        return $this->ordenado;
    }

    /**
     * Setter controlado para pruebas.
     *
     * @param Matriz2x2 $identidad
     * @return void
     */
    public function _identidad(Matriz2x2 $identidad): void
    {
        if (!Entorno::permite_pruebas()) {
            self::_alerta('_identidad() solo disponible en entorno de pruebas.');
            return;
        }
        $this->identidad = $identidad;
    }

    /**
     * Verifica si un número es primo.
     *
     * @param int $n
     * @return bool
     */
    protected static function es_primo(int $n): bool
    {
        if ($n < 2) return false;
        if ($n === 2) return true;
        if ($n % 2 === 0) return false;
        for ($i = 3; $i * $i <= $n; $i += 2) {
            if ($n % $i === 0) return false;
        }
        return true;
    }

    // ═════════════════════════════════════════════
    // FÁBRICAS PÚBLICAS
    // ═════════════════════════════════════════════

    /**
     * Crea un nodo numérico con una identidad no prima.
     *
     * @param Matriz2x2 $identidad Identidad matricial (determinante no primo).
     * @param int $capacidad Capacidad máxima de energía.
     * @param float $fuga Fuga de energía por ciclo.
     * @return NodoNumerico|null
     */
    public static function crear_numerico(
        Matriz2x2 $identidad,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoNumerico {
        $det = $identidad->determinante();
        if (self::es_primo($det)) {
            self::_error('La identidad proporcionada corresponde a un número primo.');
            return null;
        }

        $clave = (string) $identidad;
        if (isset(self::$indice_identidad[$clave])) {
            return self::$indice_identidad[$clave];
        }

        $nodo = parent::crear($capacidad, $fuga);
        $nodo->identidad = $identidad;
        $nodo->ordenado = true;
        self::$indice_identidad[$clave] = $nodo;
        return $nodo;
    }

    /**
     * Crea (o recupera) un nodo primo con el número primo indicado.
     *
     * @param int $primo Número primo (ej. 2, 3, 5...).
     * @param int $capacidad
     * @param float $fuga
     * @return NodoPrimo|null
     */
    public static function crear_primo(
        int $primo,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoPrimo {
        if (!self::es_primo($primo)) {
            self::_error("El número {$primo} no es primo.");
            return null;
        }

        $identidad = Matriz2x2::crear_prima($primo);
        $clave = (string) $identidad;

        if (isset(self::$indice_identidad[$clave])) {
            return self::$indice_identidad[$clave];
        }

        $nodo = NodoPrimo::_crear_interno($primo, $capacidad, $fuga);
        self::$indice_identidad[$clave] = $nodo;
        return $nodo;
    }

    /**
     * Crea un nodo de sincronización con los componentes dados.
     *
     * @param NodoNumerico[] $componentes Array de nodos (cantidad prima).
     * @param int $capacidad
     * @param float $fuga
     * @return NodoParalelo|null
     */
    public static function crear_paralelo(
        array $componentes,
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): ?NodoParalelo {
        return NodoParalelo::_crear_interno($componentes, $capacidad, $fuga);
    }

    /**
     * Crea un nuevo concepto semántico (sin nombre).
     *
     * @param int $capacidad
     * @param float $fuga
     * @return NodoConjunto
     */
    public static function crear_conjunto(
        int $capacidad = Conf::CAPACIDAD_NODO_ELECTRICO,
        float $fuga = Conf::FUGA_NODO_ELECTRICO
    ): NodoConjunto {
        return NodoConjunto::_crear_interno($capacidad, $fuga);
    }

    /**
     * Recupera un nodo del índice global por su identidad.
     *
     * @param Matriz2x2 $identidad
     * @return NodoNumerico|null
     */
    public static function nodo_por_identidad(Matriz2x2 $identidad): ?NodoNumerico
    {
        return self::$indice_identidad[(string) $identidad] ?? null;
    }
}