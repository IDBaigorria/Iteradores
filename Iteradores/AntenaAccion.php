<?php
namespace Iteradores\Iteradores;

use Iteradores\Nucleo\Objeto;
use Iteradores\Configuracion\Entorno;
use Iteradores\Nodos\NodoElectrico;

/**
 * Antena de Acción – Propaga verbos de intención entre Iteradores.
 *
 * ## Responsabilidad
 * Gestiona las **señales de acción** ({@link SenalAccion}) a lo largo de
 * todas las fases del sistema. Cada fase tiene una **acción actual**,
 * que es la última señal de acción recibida en esa fase.
 *
 * Es un **singleton multifase**: la instancia única se obtiene con
 * {@link antena()}.
 *
 * ## Funcionamiento
 * - **Recepción:** guarda la señal como acción actual de la fase y
 *   devuelve el número de verbo. Si el verbo es {@link \Iteradores\Configuracion\Conf::VERBO_CIERRE}
 *   (0), solo deja un placeholder para que el futuro Iterador ejecute
 *   las tareas de cierre.
 * - **Emisión:** si se proporciona un verbo, crea una nueva señal, la
 *   almacena como acción actual y la retorna. Si no se proporciona
 *   verbo, retorna la acción actual de la fase (si existe).
 *
 * ## Placeholder para Iterador
 * Los métodos `recibir` y `emitir` contienen marcadores `// TODO: Iterador`
 * donde en el futuro se enganchará la lógica de procesamiento.
 *
 * ## Singleton
 * - {@link antena()} devuelve la instancia única.
 * - {@link reiniciar()} la destruye (solo en entorno de pruebas).
 *
 * @author Ignacio David Baigorria
 *
 * @package Iteradores\Iteradores
 * @since 1.4.9
 * @version 1.4.9
 * @see SenalAccion
 * @see \Iteradores\Configuracion\Conf
 */
class AntenaAccion extends Objeto
{
    /** @var self|null Instancia única del singleton. */
    private static ?self $instancia = null;

    /**
     * Acción actual por fase.
     *
     * Estructura: `[fase => SenalAccion]`
     *
     * @var array<string, SenalAccion>
     */
    private array $accion_actual = [];

    /**
     * Constructor privado (singleton).
     */
    private function __construct()
    {
    }

    /**
     * Devuelve la instancia única de la Antena de Acción.
     *
     * @return self
     * @since 1.4.9
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
     * @since 1.4.9
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
     * @since 1.4.9
     */
    private function fase_actual(): string
    {
        return NodoElectrico::fase();
    }

    // ═══════════════════════════════════════════
    // RECEPCIÓN
    // ═══════════════════════════════════════════

    /**
     * Recibe una señal de acción y actualiza la acción actual de la fase.
     *
     * Guarda la señal en {@link $accion_actual} para la fase actual y
     * devuelve el verbo. Si el verbo es `0` (cierre), se deja un
     * placeholder para que el futuro Iterador ejecute las tareas de cierre.
     *
     * @param SenalAccion $senal Señal de acción recibida.
     * @return int Verbo recibido (constante definida en {@link \Iteradores\Configuracion\Conf}).
     * @since 1.4.9
     */
    public function recibir(SenalAccion $senal): int
    {
        $fase = $this->fase_actual();
        $this->accion_actual[$fase] = $senal;

        $verbo = $senal->verbo();

        // TODO: Iterador – cuando el verbo es 0 (cierre), ejecutar tareas de finalización.

        return $verbo;
    }

    // ═══════════════════════════════════════════
    // EMISIÓN
    // ═══════════════════════════════════════════

    /**
     * Emite una señal de acción.
     *
     * - Si se proporciona un verbo, crea una nueva {@link SenalAccion}, la
     *   almacena como acción actual de la fase y la retorna.
     * - Si no se proporciona verbo, retorna la acción actual de la fase
     *   (si existe), o `null` si no hay acción registrada.
     *
     * @param int|null $verbo Verbo a emitir (constante definida en {@link \Iteradores\Configuracion\Conf}), o null para obtener la acción actual.
     * @return SenalAccion|null Señal de acción emitida, o null si no hay acción actual.
     * @since 1.4.9
     */
    public function emitir(?int $verbo = null): ?SenalAccion
    {
        $fase = $this->fase_actual();

        if ($verbo !== null) {
            $senal = new SenalAccion($verbo, $fase);
            $this->accion_actual[$fase] = $senal;

            // TODO: Iterador – aquí se podría notificar que se ha emitido una acción.

            return $senal;
        }

        return $this->accion_actual[$fase] ?? null;
    }
}