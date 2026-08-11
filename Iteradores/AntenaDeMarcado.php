<?php
namespace Iteradores\Iteradores;

use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Nodos\NodoPrimo;
use Iteradores\Configuracion\Entorno;

/**
 * Antena de Marcado – Empaqueta señales completas como un único NodoPrimo.
 *
 * ## Responsabilidad
 * Gestiona las **señales de marcado** (`marcado = true`) a lo largo de
 * todas las fases del sistema. Cada par (fase de origen/destino) se asocia
 * con un **único {@link NodoPrimo} fijo** que actúa como contenedor de la
 * **señal completa** (matrices + fase de origen). La señal se guarda como
 * dato multidimensional del nodo, en la dimensión `'contenido'`.
 *
 * Es un **singleton**: la instancia única se obtiene con {@link antena()}.
 *
 * ## Dipolos multifase
 * Los dipolos se almacenan en un array de dos niveles:
 * ```
 * dipolos[fase][par] = NodoPrimo
 * ```
 * - **`fase`**: fase global actual (ej. `'Talamo:0'`), obtenida de
 *   {@link \Iteradores\Nodos\NodoElectrico::fase()}.
 * - **`par`**: fase de origen/destino de la señal (ej. `'Talamo:0'`).
 *
 * ## Aprendizaje trivial bidireccional automático
 * - **Recepción:** si no existe un marcador para el par, se obtiene un nuevo
 *   {@link NodoPrimo} con {@link NodoPrimo::siguiente_primo_libre()} (fase
 *   actual) y se asigna permanentemente. Luego se guarda la **señal completa**
 *   en `'contenido'` del nodo.
 * - **Emisión:** si el nodo no está registrado en el par destino, se asigna
 *   automáticamente (aprendizaje inverso). Luego se recupera y retorna la
 *   señal almacenada en `'contenido'`.
 *
 * ## Validación de tipo de señal
 * Solo acepta señales con `marcado === true`. Si recibe una señal común,
 * retorna `null`.
 *
 * ## Singleton
 * - {@link antena()} devuelve la instancia única.
 * - {@link reiniciar()} la destruye (solo en entorno de pruebas, verificado
 *   con {@link \Iteradores\Configuracion\Entorno::permite_pruebas()}).
 *
 * @author Ignacio David Baigorria
 *
 * @package Iteradores\Iteradores
 * @see AntenaComun
 * @see NodoPrimo
 * @since 1.4.8
 * @version 1.4.8
 */
class AntenaDeMarcado extends Objeto
{
    /** @var self|null Instancia única del singleton. */
    private static ?self $instancia = null;

    /**
     * Dipolos organizados por fase y par.
     *
     * Estructura:
     * [
     *     'Talamo:0' => [
     *         'Talamo:1' => NodoPrimo,
     *         ...
     *     ],
     *     ...
     * ]
     *
     * @var array<string, array<string, NodoPrimo>>
     */
    private array $dipolos = [];

    /**
     * Constructor privado (singleton).
     */
    private function __construct()
    {
    }

    /**
     * Devuelve la instancia única de la Antena de Marcado.
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
        return \Iteradores\Nodos\NodoElectrico::fase();
    }

    // ═══════════════════════════════════════════
    // RECEPCIÓN CON APRENDIZAJE TRIVIAL
    // ═══════════════════════════════════════════

    /**
     * Recibe una señal de marcado y la almacena completa en el NodoPrimo del par.
     *
     * Solo procesa señales con `marcado === true`. Si el par no tiene
     * marcador, se crea uno nuevo con {@link NodoPrimo::siguiente_primo_libre()}
     * y se asigna permanentemente. Luego se guarda la **señal completa**
     * en la dimensión `'contenido'` del nodo.
     *
     * @param Senal $senal Señal recibida (debe tener `marcado === true`).
     * @return NodoPrimo|null Nodo marcador, o null si la señal no es de marcado o falla.
     * @since 1.4.8
     */
    public function recibir(Senal $senal): ?NodoPrimo
    {
        // Validar tipo de señal
        if ($senal->marcado() !== true) {
            return null;
        }

        $fase = $this->fase_actual();
        $par  = $senal->fase_origen();

        // Asegurar que exista el nivel de fase
        if (!isset($this->dipolos[$fase])) {
            $this->dipolos[$fase] = [];
        }

        // Obtener o crear el nodo marcador para este par
        if (!isset($this->dipolos[$fase][$par])) {
            $nodo = NodoPrimo::siguiente_primo_libre();  // usa fase actual
            if ($nodo === null) {
                self::_error('No se pudo obtener un NodoPrimo libre para marcado.');
                return null;
            }
            $this->dipolos[$fase][$par] = $nodo;
        }

        $nodo_marcador = $this->dipolos[$fase][$par];

        // Guardar la señal completa dentro del nodo
        $nodo_marcador->_dato($senal, 'contenido');

        return $nodo_marcador;
    }

    // ═══════════════════════════════════════════
    // EMISIÓN CON APRENDIZAJE TRIVIAL INVERSO
    // ═══════════════════════════════════════════

    /**
     * Emite una señal de marcado a partir del contenido del nodo.
     *
     * La señal emitida tendrá `marcado = true` (conserva el tipo original).
     * Si el nodo no está registrado en el par destino, se asigna automáticamente
     * (aprendizaje inverso). Luego se recupera la señal almacenada en `'contenido'`
     * y se retorna tal cual (ya contiene sus propias matrices y fase de origen).
     *
     * @param NodoNumerico $nodo         Nodo cuyo contenido se emitirá.
     * @param string       $fase_destino Fase de destino (formato `dominio:numero`).
     * @return Senal|null Señal emitida, o null si el nodo no tiene contenido válido.
     * @since 1.4.8
     */
    public function emitir(NodoNumerico $nodo, string $fase_destino): ?Senal
    {
        $fase = $this->fase_actual();
        $par  = $fase_destino;

        // Asegurar que exista el nivel de fase
        if (!isset($this->dipolos[$fase])) {
            $this->dipolos[$fase] = [];
        }

        // Aprendizaje trivial inverso: registrar el nodo en el par destino si no existe
        if (!isset($this->dipolos[$fase][$par])) {
            $this->dipolos[$fase][$par] = $nodo;
        }

        // Recuperar la señal completa almacenada
        $contenido = $nodo->dato('contenido');
        if (!($contenido instanceof Senal)) {
            self::_error('El nodo no contiene una señal válida en "contenido".');
            return null;
        }

        return $contenido;  // ya es una Senal con sus propios fase_origen y marcado
    }
}