<?php
namespace Iteradores\Controlador;

use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Controlador\Antena;
use Iteradores\Controlador\Senal;

/**
 * Procesador de Dominio: coordina las antenas de un dominio y ejecuta
 * el bucle de captura jerárquico para reducir una señal.
 *
 * Gestiona múltiples antenas (una por fase) y aplica un algoritmo voraz
 * que comienza por las fases más altas. Cuando una antena captura una
 * porción de la señal, se reinicia el recorrido desde la fase máxima,
 * garantizando así que los bocados sean siempre lo más grandes posible.
 *
 * A partir de la versión 1.4.6, el procesador se asocia a un medio
 * y una dirección (entrada/salida). Las fases se prefijan con esta
 * información para evitar colisiones entre dominios y subdominios.
 *
 * @package Iteradores\Controlador
 * @since 1.4.5
 * @version 1.4.6
 */
class ProcesadorDeDominio extends Objeto
{
    /**
     * Nombre del medio (ej. 'Archivo', 'Talamo').
     *
     * @var string
     */
    private string $medio;

    /**
     * Dirección del subdominio: 'entrada' o 'salida'.
     *
     * @var string
     */
    private string $direccion;

    /**
     * Antenas del procesador, indexadas por número de fase (sin prefijo).
     *
     * Internamente, la fase completa se construye como
     * `{$medio}:{$direccion}:{$numero}`.
     *
     * @var array<int, Antena>
     */
    private array $antenas;

    /**
     * Token de seguridad para operaciones restringidas (cambio de fase, etc.).
     *
     * @var string
     * @since 1.4.6
     */
    private static string $token = '';

    /**
     * Recibe el token de seguridad desde el Controlador.
     *
     * @param string $token Token de seguridad.
     * @return void
     * @since 1.4.6
     */
    public static function recibir_token(string $token): void
    {
        self::$token = $token;
    }

    /**
     * Constructor.
     *
     * @param string $medio     Nombre del medio (ej. 'Archivo', 'Talamo').
     * @param string $direccion 'entrada' o 'salida'.
     */
    public function __construct(string $medio, string $direccion)
    {
        $this->medio = $medio;
        $this->direccion = $direccion;
        $this->antenas = [];
    }

    /**
     * Construye la clave de fase completa a partir del número de fase.
     *
     * @param int $fase Número de fase.
     * @return string Clave de fase (ej. 'Archivo:entrada:0').
     */
    private function prefijar_fase(int $fase): string
    {
        return "{$this->medio}:{$this->direccion}:{$fase}";
    }

    /**
     * Obtiene la antena para una fase específica, creándola si no existe.
     *
     * @param int $fase Número de fase (sin prefijo).
     * @return Antena
     */
    public function antena(int $fase): Antena
    {
        if (!isset($this->antenas[$fase])) {
            $fase_completa = $this->prefijar_fase($fase);
            $this->antenas[$fase] = new Antena($fase_completa);
        }
        return $this->antenas[$fase];
    }

    /**
     * Registra un nodo como patrón en la antena de la fase indicada.
     *
     * Método de conveniencia que delega en Antena::_patron().
     *
     * @param NodoNumerico $nodo Nodo a registrar como patrón.
     * @param int          $fase Número de fase.
     * @return void
     */
    public function _patron(NodoNumerico $nodo, int $fase): void
    {
        $antena = $this->antena($fase);
        $antena->_patron($nodo);
    }

    /**
     * Procesa una señal aplicando el bucle de captura voraz con reinicio.
     *
     * Recorre las fases de mayor a menor. Si una antena logra capturar
     * una subsecuencia, se reinicia el recorrido desde la fase más alta.
     * El proceso finaliza cuando ninguna fase puede realizar capturas.
     *
     * @param Senal $senal Señal a procesar (se modifica in-place).
     * @return void
     */
    public function procesar(Senal $senal): void
    {
        // Bucle voraz sobre las fases existentes
        if (!empty($this->antenas)) {
            $fases = array_keys($this->antenas);
            rsort($fases);

            $hubo_captura = true;
            while ($hubo_captura) {
                $hubo_captura = false;
                foreach ($fases as $num_fase) {
                    $antena = $this->antenas[$num_fase];
                    if ($antena->intentar_capturar($senal)) {
                        $hubo_captura = true;
                        break;
                    }
                }
            }
        }

       // ─── Aprendizaje trivial ─────
        $restantes = $senal->no_consumidas();
        if (empty($restantes)) return;

        $fase_anterior = NodoElectrico::fase();
        $fase_dominio_cero = $this->prefijar_fase(0);
        NodoElectrico::_fase(self::$token, $fase_dominio_cero);

        // Si esta fase nunca ha tenido contador, lo inicializamos en 256
        if (!NodoNumerico::contador_fase_existe($fase_dominio_cero)) {
            NodoNumerico::inicializar_contador_fase($fase_dominio_cero, 256);
        }

        foreach ($restantes as $matriz) {
            $numero = NodoNumerico::siguiente_primo_positivo($fase_dominio_cero);
            $primo = NodoNumerico::crear_primo($numero);
            if (!$primo) continue;

            $primo->_dato(['matriz_original' => $matriz], 'abajo');
            echo "DEBUG PROC: abajo recién asignado = " . var_export($primo->dato('abajo'), true) . "<br>";
            $this->antena(0)->_patron($primo);
            $senal->consumir(1, $primo);
            echo "DEBUG PROC: después de consumir, abajo = " . var_export($primo->dato('abajo'), true) . "<br>";
        }

        NodoElectrico::_fase(self::$token, $fase_anterior);

    }

    /**
     * Devuelve el nombre del medio.
     *
     * @return string
     */
    public function medio(): string
    {
        return $this->medio;
    }

    /**
     * Devuelve la dirección del subdominio ('entrada' o 'salida').
     *
     * @return string
     */
    public function direccion(): string
    {
        return $this->direccion;
    }

    /**
     * Devuelve todas las antenas del procesador.
     *
     * @return array<int, Antena>
     */
    public function antenas(): array
    {
        return $this->antenas;
    }
}