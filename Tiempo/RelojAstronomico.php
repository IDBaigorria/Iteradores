<?php
namespace Iteradores\Tiempo;

use Iteradores\Configuracion\Conf;
use Iteradores\Tiempo\interfaces\ProveedorVectorGravitacional;
use Iteradores\Nucleo\Objeto;

require_once("interfaces\ProveedorVectorGravitacional.php");

/**
 * Reloj Astronómico del framework.
 *
 * Implementa {@link ProveedorVectorGravitacional} y proporciona un vector
 * gravitacional normalizado (x, y, z) que representa de forma determinista
 * la configuración del cielo local (Sol y Luna) para cualquier ubicación
 * geográfica e instante de tiempo (pasado, presente o futuro).
 *
 * El cálculo utiliza un modelo geométrico simplificado con órbitas circulares
 * que genera ciclos día/noche, fases lunares, estaciones y la precesión nodal
 * lunar (18.6 años) sin necesidad de efemérides de alta precisión.
 *
 * ## Rol en el sistema
 *
 * - Los iteradores obtienen el vector actual a través del Controlador y lo
 *   usan para "marcar" los pesos de las aristas que recorren (huella temporal).
 * - La distancia entre el vector almacenado en un peso y el vector actual
 *   mide la "antigüedad" relativa de ese recuerdo.
 * - Permite realizar predicciones buscando en el grafo pesos cuyos vectores
 *   sean cercanos a una configuración futura simulada.
 *
 * @package Iteradores\Tiempo
 * @since 1.3.5
 * @implements ProveedorVectorGravitacional
 */
class RelojAstronomico extends Objeto implements ProveedorVectorGravitacional
{
    // ═══════════════════════════════════════════════════════
    // ESTADO INTERNO PARA CACHÉ DE CÓMPUTOS
    // ═══════════════════════════════════════════════════════

    /**
     * Latitud configurada para esta instancia (en grados, -90 a 90).
     * @var float
     */
    private float $latitud;

    /**
     * Longitud configurada para esta instancia (en grados, -180 a 180).
     * @var float
     */
    private float $longitud;

    /**
     * Último timestamp para el que se calculó el vector (Unix).
     * Se usa para la caché de instancia. `null` si no se ha calculado ninguno.
     * @var int|null
     */
    private ?int $ultimo_timestamp = null;

    /**
     * Último vector calculado (caché). Se invalida al cambiar la ubicación
     * o al consultar con un timestamp distinto.
     * @var array{x: float, y: float, z: float}|null
     */
    private ?array $ultimo_vector = null;

    /**
     * Construye un reloj con estado ligado a una ubicación fija.
     *
     * @param float $latitud  Latitud en grados (-90 a 90).
     * @param float $longitud Longitud en grados (-180 a 180).
     */
    public function __construct(float $latitud, float $longitud)
    {
        $this->latitud = $latitud;
        $this->longitud = $longitud;
    }

    // ═══════════════════════════════════════════════════════
    // MÉTODOS PÚBLICOS (Interfaz ProveedorVectorGravitacional)
    // ═══════════════════════════════════════════════════════

    /**
     * @inheritdoc
     */
    public function vector(?int $timestamp = null): array
    {
        $ts = $timestamp ?? time();

        // Si el timestamp no cambió, devolver el vector cacheado
        if ($this->ultimo_timestamp === $ts && $this->ultimo_vector !== null) {
            return $this->ultimo_vector;
        }

        $this->ultimo_timestamp = $ts;
        $this->ultimo_vector = self::calcular_vector($this->latitud, $this->longitud, $ts);

        return $this->ultimo_vector;
    }

    /**
     * @inheritdoc
     */
    public static function vector_gravitacional(
        float $latitud,
        float $longitud,
        ?int $timestamp = null
    ): array {
        $ts = $timestamp ?? time();
        return self::calcular_vector($latitud, $longitud, $ts);
    }

    /**
     * @inheritdoc
     */
    public function _ubicacion(float $latitud, float $longitud): void
    {
        $this->latitud = $latitud;
        $this->longitud = $longitud;

        // Invalidar caché para forzar recálculo con las nuevas coordenadas
        $this->ultimo_timestamp = null;
        $this->ultimo_vector = null;
    }

    // ═══════════════════════════════════════════════════════
    // CÁLCULOS INTERNOS
    // ═══════════════════════════════════════════════════════

    /**
     * Calcula el vector gravitacional combinado (Sol + Luna) para una
     * ubicación e instante dados.
     *
     * @param float $latitud  Latitud en grados.
     * @param float $longitud Longitud en grados.
     * @param int   $ts       Timestamp Unix.
     * @return array{x: float, y: float, z: float} Vector unitario.
     */
    private static function calcular_vector(float $latitud, float $longitud, int $ts): array
    {
        $lat_rad = deg2rad($latitud);
        $lon_rad = deg2rad($longitud);

        $lst = self::tiempo_sidereo_local($ts, $lon_rad);

        $vector_sol  = self::vector_astro($ts, $lat_rad, $lst, true);
        $vector_luna = self::vector_astro($ts, $lat_rad, $lst, false);

        $alfa = Conf::RELOJ_ALFA_SOL;
        $beta = Conf::RELOJ_BETA_LUNA;

        $x = $alfa * $vector_sol['x'] + $beta * $vector_luna['x'];
        $y = $alfa * $vector_sol['y'] + $beta * $vector_luna['y'];
        $z = $alfa * $vector_sol['z'] + $beta * $vector_luna['z'];

        $magnitud = sqrt($x * $x + $y * $y + $z * $z);
        if ($magnitud < 1e-9) {
            // Vector neutro (hacia arriba) cuando ambos astros están
            // simultáneamente en el cenit (caso teórico extremo)
            return ['x' => 0.0, 'y' => 0.0, 'z' => 1.0];
        }

        return [
            'x' => $x / $magnitud,
            'y' => $y / $magnitud,
            'z' => $z / $magnitud,
        ];
    }

    /**
     * Calcula el Tiempo Sidéreo Local (LST) en radianes.
     *
     * El LST es el ángulo horario del punto vernal para un observador
     * en una longitud dada. Se utiliza para convertir coordenadas ecuatoriales
     * en coordenadas horizontales locales.
     *
     * @param int   $ts      Timestamp Unix.
     * @param float $lon_rad Longitud del observador en radianes.
     * @return float LST en radianes (0 a 2π).
     */
    private static function tiempo_sidereo_local(int $ts, float $lon_rad): float
    {
        // Días julianos desde J2000 (01-ene-2000 12:00 UTC)
        $dias_desde_j2000 = ($ts / Conf::RELOJ_SEGUNDOS_POR_DIA) - 10957.5;
        $gmst_deg = fmod(280.46061837 + 360.98564736629 * $dias_desde_j2000, 360.0);
        if ($gmst_deg < 0) {
            $gmst_deg += 360.0;
        }
        $gmst_rad = deg2rad($gmst_deg);

        $lst = fmod($gmst_rad + $lon_rad, 2.0 * M_PI);
        return $lst < 0 ? $lst + 2.0 * M_PI : $lst;
    }

    /**
     * Calcula el vector unitario de un astro (Sol o Luna) en el sistema
     * de coordenadas horizontales locales.
     *
     * El vector resultante está orientado de modo que:
     * - `x` apunta hacia el Este.
     * - `y` apunta hacia el Norte.
     * - `z` apunta hacia el Cenit (arriba).
     *
     * @param int   $ts      Timestamp Unix.
     * @param float $lat_rad Latitud del observador en radianes.
     * @param float $lst     Tiempo Sidéreo Local en radianes.
     * @param bool  $es_sol  `true` para el Sol, `false` para la Luna.
     * @return array{x: float, y: float, z: float} Vector unitario.
     */
    private static function vector_astro(int $ts, float $lat_rad, float $lst, bool $es_sol): array
    {
        if ($es_sol) {
            // El Sol recorre 360° en un año juliano
            $angulo_anual = 2.0 * M_PI * fmod($ts, Conf::RELOJ_SEGUNDOS_POR_ANIO) / Conf::RELOJ_SEGUNDOS_POR_ANIO;
            $ar = fmod($angulo_anual, 2.0 * M_PI);
            $declinacion = deg2rad(Conf::RELOJ_INCLINACION_ECLIPTICA) * sin($angulo_anual);
        } else {
            // La Luna: período sinódico (~29.53 días) + precesión nodal (18.6 años)
            $angulo_sinodico = 2.0 * M_PI * fmod($ts, Conf::RELOJ_SEGUNDOS_POR_MES_SINODICO) / Conf::RELOJ_SEGUNDOS_POR_MES_SINODICO;
            $angulo_nodal = 2.0 * M_PI * fmod($ts, Conf::RELOJ_PERIODO_PRECESION_NODAL * Conf::RELOJ_SEGUNDOS_POR_ANIO)
                / (Conf::RELOJ_PERIODO_PRECESION_NODAL * Conf::RELOJ_SEGUNDOS_POR_ANIO);

            $longitud_ecliptica = $angulo_sinodico;
            $declinacion_max = deg2rad(Conf::RELOJ_INCLINACION_ECLIPTICA + Conf::RELOJ_INCLINACION_LUNAR);
            $declinacion = $declinacion_max * sin($longitud_ecliptica) * cos($angulo_nodal);
            $ar = $longitud_ecliptica;
        }

        // Ángulo horario local: H = LST - AR
        $angulo_horario = fmod($lst - $ar, 2.0 * M_PI);
        if ($angulo_horario < 0) {
            $angulo_horario += 2.0 * M_PI;
        }

        // Conversión a altitud y azimut
        $sin_alt = sin($declinacion) * sin($lat_rad)
                 + cos($declinacion) * cos($lat_rad) * cos($angulo_horario);
        $altitud = asin(max(-1.0, min(1.0, $sin_alt)));

        $cos_alt = cos($altitud);
        if (abs($cos_alt) < 1e-9) {
            // Astro en el cenit o nadir: azimut indefinido, vector puramente vertical
            return ['x' => 0.0, 'y' => 0.0, 'z' => $sin_alt > 0 ? 1.0 : -1.0];
        }

        $sin_az = -cos($declinacion) * sin($angulo_horario) / $cos_alt;
        $cos_az = (sin($declinacion) - sin($lat_rad) * sin($altitud)) / (cos($lat_rad) * $cos_alt);
        $azimut = atan2($sin_az, $cos_az);

        // Vector cartesiano local
        $x = cos($altitud) * sin($azimut);  // Este
        $y = cos($altitud) * cos($azimut);  // Norte
        $z = sin($altitud);                  // Cenit

        // Normalización final (por posibles errores de redondeo)
        $magnitud = sqrt($x * $x + $y * $y + $z * $z);
        if ($magnitud < 1e-9) {
            return ['x' => 0.0, 'y' => 0.0, 'z' => 1.0];
        }

        return [
            'x' => $x / $magnitud,
            'y' => $y / $magnitud,
            'z' => $z / $magnitud,
        ];
    }
}