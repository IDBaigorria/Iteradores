<?php
namespace Iteradores\Iteradores;

use Iteradores\Configuracion\Entorno;
use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Nodos\NodoPrimo;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoElectrico;

/**
 * Antena Común – Comunicación multifase de una sola matriz.
 *
 * ## Responsabilidad
 * Gestiona el vocabulario de **señales comunes** (señales de una única matriz,
 * `marcado = false`) a lo largo de todas las fases del sistema.
 * Es un **singleton**: existe una única instancia global accesible mediante
 * {@link AntenaComun::antena()}.
 *
 * ## Dipolos multifase
 * Los dipolos se almacenan en un mapa de dos niveles:
 * ```
 * dipolos[string $fase][string $par] = array<{matrices: Matriz2x2[], nodo: NodoNumerico}>
 * ```
 * - **`$fase`**: fase global actual (ej. `'Talamo:0'`), obtenida de {@link NodoElectrico::fase()}.
 * - **`$par`**: fase de origen/destino de la señal (ej. `'Talamo:0'`).
 * Cada par puede contener múltiples asociaciones (matriz única → nodo).
 *
 * ## Almacenamiento de la señal (solo en aprendizaje)
 * La señal completa se guarda en el dato `'contenido'` del {@link NodoPrimo}
 * **exclusivamente durante el aprendizaje trivial en recepción**. Una vez
 * aprendida, la asociación matriz→nodo es inmutable y no se sobrescribe.
 *
 * ## Aprendizaje trivial bidireccional automático
 * - **Recepción:** si una matriz no está registrada en el par, se crea un nuevo
 *   {@link NodoPrimo} con {@link NodoPrimo::siguiente_primo_libre()} (fase actual),
 *   se añade el dipolo y se guarda la señal original en `'contenido'` del nodo.
 * - **Emisión:** si el nodo no está en el par destino, se crea un dipolo con la
 *   matriz de identidad del nodo (sin almacenar señal en el nodo). Luego se emite
 *   una señal con dicha matriz.
 *
 * ## Validación de tipo de señal
 * Solo acepta señales con `marcado === false`. Si recibe una señal marcada,
 * retorna `null`.
 *
 * ## Singleton
 * - {@link antena()} devuelve la instancia única.
 * - {@link reiniciar()} la destruye (solo en entorno de pruebas).
 *
 * @author Ignacio David Baigorria
 *
 * @package Iteradores\Iteradores
 * @since 1.4.8
 * @version 1.4.8
 */
class AntenaComun extends Objeto
{
    /**
     * Instancia única del singleton.
     * @var self|null
     */
    private static ?self $instancia = null;

    /**
     * Dipolos organizados por fase y par.
     *
     * Estructura:
     * [
     *     'Talamo:0' => [
     *         'Talamo:1' => [
     *             ['matrices' => [Matriz2x2], 'nodo' => NodoNumerico],
     *             ...
     *         ],
     *         ...
     *     ],
     *     ...
     * ]
     *
     * @var array<string, array<string, array<int, array{matrices: Matriz2x2[], nodo: NodoNumerico}>>>
     */
    private array $dipolos = [];

    /**
     * Constructor privado (singleton).
     */
    private function __construct()
    {
        // Inicialización vacía
    }

    /**
     * Devuelve la instancia única de la Antena Común.
     *
     * @return self
     * @since 1.4.8
     */
    public static function antena(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Destruye la instancia actual (solo en entorno de pruebas).
     *
     * @return void
     * @since 1.4.8
     */
    public static function reiniciar(): void
    {
        if (!Entorno::permite_pruebas()) {
            self::_error('reiniciar() solo está disponible en entorno de pruebas.');
            return;
        }
        self::$instancia = null;
    }

    /**
     * Devuelve la fase global actual.
     *
     * @return string
     * @since 1.4.8
     */
    private function fase_actual(): string
    {
        return NodoElectrico::fase();
    }

    /**
     * Registra un nuevo dipolo en una fase y par concretos.
     *
     * @param string        $fase     Fase global.
     * @param string        $par      Identificador del par (fase origen/destino).
     * @param Matriz2x2[]   $matrices Secuencia de matrices (exactamente una).
     * @param NodoNumerico  $nodo     Nodo asociado.
     * @return void
     * @since 1.4.8
     */
    public function _dipolo(string $fase, string $par, array $matrices, NodoNumerico $nodo): void
    {
        if (!isset($this->dipolos[$fase])) {
            $this->dipolos[$fase] = [];
        }
        if (!isset($this->dipolos[$fase][$par])) {
            $this->dipolos[$fase][$par] = [];
        }
        $this->dipolos[$fase][$par][] = [
            'matrices' => $matrices,
            'nodo'     => $nodo,
        ];
    }

    // ═══════════════════════════════════════════
    // RECEPCIÓN
    // ═══════════════════════════════════════════

    /**
     * Recibe una señal común y devuelve el nodo asociado.
     *
     * Solo procesa señales con `marcado === false`. Utiliza la fase global
     * actual para indexar los dipolos y la fase de origen de la señal como
     * par de recepción.
     *
     * Si no encuentra coincidencia, activa el aprendizaje trivial: crea un
     * nuevo NodoPrimo con {@link NodoPrimo::siguiente_primo_libre()}, registra
     * el dipolo y guarda la señal completa en el dato `'contenido'` del nodo.
     * Si ya existe, **no** modifica el contenido previamente guardado.
     *
     * @param Senal $senal Señal recibida (debe tener `marcado = false`).
     * @return NodoNumerico|null Nodo encontrado o aprendido, o null si falla.
     */
    public function recibir(Senal $senal): ?NodoNumerico
    {
        if ($senal->marcado() !== false) {
            return null;
        }

        $matrices = $senal->matrices();
        if (empty($matrices)) {
            return null;
        }

        $matriz = $matrices[0];
        $fase   = $this->fase_actual();
        $par    = $senal->fase_origen();

        // Buscar coincidencia existente
        if (isset($this->dipolos[$fase][$par])) {
            foreach ($this->dipolos[$fase][$par] as $dipolo) {
                if ($dipolo['matrices'][0]->es_igual($matriz)) {
                    // No se actualiza la señal guardada
                    return $dipolo['nodo'];
                }
            }
        }

        // Aprendizaje trivial
        $nodo_primo = NodoPrimo::siguiente_primo_libre();
        if ($nodo_primo === null) {
            self::_error('No se pudo obtener un NodoPrimo libre para aprendizaje trivial.');
            return null;
        }

        // Guardar la señal completa SOLO en el aprendizaje
        $nodo_primo->_dato($senal, 'contenido');

        // Registrar dipolo
        if (!isset($this->dipolos[$fase])) {
            $this->dipolos[$fase] = [];
        }
        if (!isset($this->dipolos[$fase][$par])) {
            $this->dipolos[$fase][$par] = [];
        }
        $this->dipolos[$fase][$par][] = [
            'matrices' => [$matriz],
            'nodo'     => $nodo_primo,
        ];

        return $nodo_primo;
    }

    // ═══════════════════════════════════════════
    // EMISIÓN
    // ═══════════════════════════════════════════

    /**
     * Emite una señal común hacia una fase destino.
     *
     * La señal emitida siempre tendrá `marcado = false`. Utiliza la fase
     * global actual como fase de origen de la señal.
     *
     * - Si el nodo es un **NodoPrimo**, fue aprendido por aprendizaje trivial
     *   y contiene una señal completa en `'contenido'`. Esa señal se retorna
     *   directamente.
     * - Si el nodo **no es primo** (es un nodo compuesto), se construye una
     *   nueva señal con su {@link NodoNumerico::identidad()}, se registra el
     *   dipolo en el par destino y se retorna esa señal. No se modifica el nodo.
     *
     * @param NodoNumerico $nodo         Nodo a transmitir.
     * @param string       $fase_destino Fase de destino (formato `dominio:numero`).
     * @return Senal|null Señal lista para enviar, o null si el nodo no tiene identidad.
     * @since 1.4.8
     */
    public function emitir(NodoNumerico $nodo, string $fase_destino): ?Senal
    {
        $fase = $this->fase_actual();
        $par  = $fase_destino;

        // Caso 1: NodoPrimo → señal aprendida en recepción
        if ($nodo->es_primo()) {
            $senal_guardada = $nodo->dato('contenido');
            if ($senal_guardada instanceof Senal) {
                return $senal_guardada;
            }
            self::_error('El NodoPrimo no contiene una señal en "contenido".');
            return null;
        }

        // Caso 2: Nodo compuesto (no primo)
        $matriz_identidad = $nodo->identidad();
        if ($matriz_identidad === null) {
            self::_error('El nodo no tiene identidad matricial.');
            return null;
        }

        // Crear señal (sin guardar en el nodo)
        $senal_nueva = new Senal([$matriz_identidad], $fase, false);

        // Registrar dipolo para futuras referencias
        if (!isset($this->dipolos[$fase])) {
            $this->dipolos[$fase] = [];
        }
        if (!isset($this->dipolos[$fase][$par])) {
            $this->dipolos[$fase][$par] = [];
        }
        $this->dipolos[$fase][$par][] = [
            'matrices' => [$matriz_identidad],
            'nodo'     => $nodo,
        ];

        return $senal_nueva;
    }
}