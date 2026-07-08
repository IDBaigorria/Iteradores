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
 * A partir de la versión 1.4.7, el procesador mantiene internamente
 * el índice de consumo y la lista de p‑gramas capturados. La señal ya
 * no es modificada; en su lugar, el procesador registra los elementos
 * procesados y permite emitir una nueva señal con
 * {@link emitir_senal()}.
 *
 * @package Iteradores\Controlador
 * @since 1.4.5
 * @version 1.4.7
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
     * Fase máxima permitida para el ascenso (null = sin límite).
     *
     * @var int|null
     * @since 1.4.7
     */
    private ?int $maxima_fase = null;

    /**
     * Lista de p‑gramas capturados durante el último procesamiento.
     *
     * @var array<int, array<int>>
     * @since 1.4.7
     */
    private array $elementos_procesados = [];

    /**
     * Token de seguridad para operaciones restringidas.
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
     * @param string $medio     Nombre del medio.
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
     * @param NodoNumerico $nodo Nodo a registrar.
     * @param int          $fase Número de fase.
     * @return void
     */
    public function _patron(NodoNumerico $nodo, int $fase): void
    {
        $antena = $this->antena($fase);
        $antena->_patron($nodo);
    }

    /**
     * Establece la fase máxima permitida para el ascenso.
     *
     * @param int|null $fase Fase máxima (0 para el Tálamo, null para ilimitado).
     * @return void
     * @since 1.4.7
     */
    public function establecer_maxima_fase(?int $fase): void
    {
        $this->maxima_fase = $fase;
    }

    /**
     * Procesa una señal aplicando el bucle de captura voraz con reinicio.
     *
     * La señal no se modifica; el índice de consumo es local al método.
     * Los p‑gramas capturados se almacenan en {@link $elementos_procesados}
     * y pueden recuperarse con {@link elementos_procesados()}.
     *
     * @param Senal $senal Señal a procesar.
     * @return void
     * @since 1.4.5
     * @version 1.4.7
     */
    public function procesar(Senal $senal): void
    {
        $this->elementos_procesados = [];
        $matrices = $senal->matrices();
        $total = count($matrices);
        $i = 0;
      //  echo "TTTT".$total;
      //  echo "MMM".count($this->antenas);
        $cont1=1;
        $cont2=1;
        $fase_anterior = NodoElectrico::fase();

        while ($i < $total) {
            // Fases disponibles, orden descendente
            $fases = array_keys($this->antenas);
            rsort($fases);
            //echo "MM2M".count($fases);

            $capturado = false;
           
            foreach ($fases as $num_fase) {
                
                $fase_dominio_cero = $this->prefijar_fase($num_fase);
                NodoElectrico::_fase(self::$token,$fase_dominio_cero);
                // Respetar límite de fase máxima
                if ($this->maxima_fase !== null && $num_fase > $this->maxima_fase) {
                    continue;
                }

                $antena = $this->antenas[$num_fase];
                [$longitud, $patron] = $antena->intentar_capturar($senal, $i);

                if ($longitud > 0 && $patron !== null) {
                    $this->elementos_procesados[] = $patron->pgrama();
                    $i += $longitud;
                    $capturado = true;
                    //echo "SIII";
                    break; // reiniciar desde fase máxima
                }
            }
            $cont1++;
            if (!$capturado) {
                $cont2++;
                // ─── Aprendizaje trivial ───
                //$fase_anterior = NodoElectrico::fase();
                $fase_dominio_cero = $this->prefijar_fase(0);
                NodoElectrico::_fase(self::$token, $fase_dominio_cero);

                if (!NodoNumerico::contador_fase_existe($fase_dominio_cero)) {
                    NodoNumerico::inicializar_contador_fase($fase_dominio_cero, 256);
                }

                $numero = NodoNumerico::siguiente_primo_positivo($fase_dominio_cero);
                $primo = NodoNumerico::crear_primo($numero);
                if ($primo) {
                    $primo->_dato(['matriz_original' => $matrices[$i]], 'abajo');
                    echo "DEBUG APRENDIZAJE: nodo " . $primo->id() . " pgrama=" . json_encode($primo->pgrama()) . " abajo=" . var_export($primo->dato('abajo'), true) . "\n";
                    $this->antena(0)->_patron($primo);
                    $this->elementos_procesados[] = $primo->pgrama();
                }


                $i++;
            }

           // echo "C1:".$cont1."C2:".$cont2."";
        }
        NodoElectrico::_fase(self::$token, $fase_anterior);
      //  echo "YYYY".count($this->elementos_procesados);
    }

    /**
     * Devuelve los p‑gramas capturados durante el último procesamiento.
     *
     * @return array<int, array<int>>
     * @since 1.4.7
     */
    public function elementos_procesados(): array
    {
        return $this->elementos_procesados;
    }

    /**
     * Emite una señal a partir de los p‑gramas capturados.
     *
     * Utiliza la antena de fase 0 para convertir los p‑gramas
     * en sus secuencias de matrices originales.
     *
     * @return Senal|null Señal emitida o null si falla la emisión.
     * @since 1.4.7
     */
    public function emitir_senal(): ?Senal
    {
        $antena = $this->antena(0);
        return $antena->emitir($this->elementos_procesados);
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
     * Devuelve la dirección del subdominio.
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