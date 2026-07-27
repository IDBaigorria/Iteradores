<?php
namespace Iteradores\Controlador;

use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoNumerico;
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Iteradores\AntenaComun;
use Iteradores\Controlador\Senal;
use Iteradores\Configuracion\Conf;

/**
 * Procesador de Dominio: coordina la antena de fase 0 de un dominio.
 *
 * A partir de la versión 1.4.8, **solo se utiliza la antena de fase 0**.
 * El bucle voraz entre fases ha sido eliminado; las señales recibidas ya
 * vienen preempaquetadas en forma de NodoPrimo por el Controlador, y la
 * antena de fase 0 las captura directamente. El ascenso a fases superiores
 * será reintroducido más adelante por el futuro Iterador.
 *
 * Incorpora el mecanismo de **sapiencia** (proporción de matrices capturadas
 * sobre el total procesado) y el aprendizaje trivial en fase 0.
 *
 * @package Iteradores\Controlador
 * @since 1.4.5
 * @version 1.4.8
 */
class ProcesadorDeDominio extends Objeto
{
    /**
     * Nombre del dominio (ej. 'Archivo', 'Talamo', 'texto').
     *
     * @var string
     * @since 1.4.8
     */
    private string $dominio;

    /**
     * Dirección del subdominio: 'entrada' o 'salida'.
     *
     * @var string
     */
    private string $direccion;

    /**
     * Antenas del procesador, indexadas por número de fase.
     *
     * Internamente, la fase completa se construye como
     * `{$dominio}:{$direccion}:{$numero}`.
     *
     * @var array<int, AntenaComun>
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
     * Elementos capturados durante el último recibir_senal().
     *
     * Cada elemento es un array asociativo:
     *   - 'nodo' => NodoNumerico
     *   - 'fase' => int    (siempre 0 en esta versión)
     *
     * @var array<int, array{nodo: NodoNumerico, fase: int}>
     * @since 1.4.8
     */
    private array $elementos_recibidos = [];

    /**
     * Matrices capturadas por patrones existentes durante el último procesamiento.
     *
     * @var int
     * @since 1.4.8
     */
    private int $matrices_capturadas = 0;

    /**
     * Matrices aprendidas (aprendizaje trivial) durante el último procesamiento.
     *
     * @var int
     * @since 1.4.8
     */
    private int $matrices_aprendidas = 0;

    /**
     * Sapiencia calculada en el último procesamiento (valor entre 0.0 y 1.0).
     *
     * @var float
     * @since 1.4.8
     */
    private float $sapiencia_ultima = 1.0;

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
     * @param string $dominio   Nombre del dominio.
     * @param string $direccion 'entrada' o 'salida'.
     * @since 1.4.5
     * @version 1.4.8
     */
    public function __construct(string $dominio, string $direccion)
    {
        $this->dominio   = $dominio;
        $this->direccion = $direccion;
        $this->antenas   = [];
    }

    /**
     * Construye la clave de fase completa a partir del número de fase.
     *
     * @param int $fase Número de fase.
     * @return string Clave de fase (ej. 'Archivo:entrada:0').
     */
    private function prefijar_fase(int $fase): string
    {
        return "{$this->dominio}:{$this->direccion}:{$fase}";
    }

    /**
     * Obtiene la antena para una fase específica, creándola si no existe.
     *
     * @param int $fase Número de fase (sin prefijo).
     * @return AntenaComun
     * @since 1.4.5
     * @version 1.4.8
     */
    public function antena(int $fase): AntenaComun
    {
        if (!isset($this->antenas[$fase])) {
            $fase_completa = $this->prefijar_fase($fase);
           // $this->antenas[$fase] = new AntenaComun($fase_completa);
        }
        return $this->antenas[$fase];
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
     * Inicializa la antena de fase 0 con los dipolos de acción y marcado.
     *
     * Registra como dipolos los primos de marcado (índices 256‑259)
     * y los primos de acción (índices 260‑264) definidos en
     * {@link \Iteradores\Conf::PRIMOS_PRECARGADOS}.
     * Estos dipolos permiten al Tálamo (y a cualquier dominio que los
     * necesite) reconocer instantáneamente los NodoPrimo que el
     * Controlador inyecta durante la ejecución de comandos.
     *
     * @return void
     * @since 1.4.8
     */
    public function inicializar_acciones(): void
    {
        $primos = Conf::PRIMOS_PRECARGADOS;
        // Registrar marcadores (256..259) y acciones (260..264)
        for ($indice = 256; $indice <= 264; $indice++) {
            $numero_primo = $primos[$indice];
            $nodo_primo   = NodoNumerico::crear_primo($numero_primo);
            if ($nodo_primo !== null) {
                $matriz_identidad = $nodo_primo->identidad();
                $this->antena(0)->_dipolo([$matriz_identidad], $nodo_primo);
            }
        }
    }

    /**
     * Recibe una señal y la procesa exclusivamente con la antena de fase 0.
     *
     * Ya no existe bucle voraz entre fases. Cada matriz de la señal se
     * intenta capturar con la antena de fase 0. Si no es reconocida,
     * se activa el aprendizaje trivial (creación de un nuevo NodoPrimo
     * y registro de su dipolo en la misma fase 0).
     *
     * @param Senal $senal Señal a procesar.
     * @return void
     * @since 1.4.8
     */
    public function recibir_senal(Senal $senal): void
    {
        $this->elementos_recibidos   = [];
        $this->matrices_capturadas   = 0;
        $this->matrices_aprendidas   = 0;

        $matrices = $senal->matrices();
        $total    = count($matrices);
        $i        = 0;

        // Aseguramos que exista la antena de fase 0
        $antena_fase_0 = $this->antena(0);

        // Fase actual antes de cualquier cambio
        $fase_anterior = NodoElectrico::fase();

        while ($i < $total) {
            [$longitud, $patron] = $antena_fase_0->intentar_capturar($senal, $i);

            if ($longitud > 0 && $patron !== null) {
                // Captura exitosa en fase 0
                $this->elementos_recibidos[] = [
                    'nodo' => $patron,
                    'fase' => 0,
                ];
                $this->matrices_capturadas += $longitud;
                $i += $longitud;
            } else {
                // ─── Aprendizaje trivial (fase 0) ───
                $fase_dominio_cero = $this->prefijar_fase(0);
                NodoElectrico::_fase(self::$token, $fase_dominio_cero);

                if (!NodoNumerico::contador_fase_existe($fase_dominio_cero)) {
                    NodoNumerico::inicializar_contador_fase($fase_dominio_cero, 256);
                }

                $numero = NodoNumerico::siguiente_primo_positivo($fase_dominio_cero);
                $primo  = NodoNumerico::crear_primo($numero);
                if ($primo) {
                    // Registrar el dipolo en la antena de fase 0
                    $antena_fase_0->_dipolo([$matrices[$i]], $primo);

                    $this->elementos_recibidos[] = [
                        'nodo' => $primo,
                        'fase' => 0,
                    ];
                }

                $this->matrices_aprendidas++;
                NodoElectrico::_fase(self::$token, $fase_anterior);
                $i++;
            }
        }

        // Restaurar fase original
        NodoElectrico::_fase(self::$token, $fase_anterior);

        // Calcular sapiencia
        $total_matrices = $this->matrices_capturadas + $this->matrices_aprendidas;
        $this->sapiencia_ultima = ($total_matrices > 0)
            ? $this->matrices_capturadas / $total_matrices
            : 1.0;
    }

    /**
     * Devuelve los elementos recibidos durante el último {@link recibir_senal()}.
     *
     * @return array<int, array{nodo: NodoNumerico, fase: int}>
     * @since 1.4.8
     */
    public function elementos_recibidos(): array
    {
        return $this->elementos_recibidos;
    }


    /**
     * Devuelve la sapiencia calculada en el último procesamiento.
     *
     * @return float Valor entre 0.0 (todo aprendido) y 1.0 (todo capturado).
     * @since 1.4.8
     */
    public function sapiencia(): float
    {
        return $this->sapiencia_ultima;
    }


    /**
     * Devuelve el nombre del dominio.
     *
     * @return string
     * @since 1.4.8
     */
    public function dominio(): string
    {
        return $this->dominio;
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
     * @return array<int, AntenaComun>
     */
    public function antenas(): array
    {
        return $this->antenas;
    }
}