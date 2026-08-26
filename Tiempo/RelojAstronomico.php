<?php
/**
 * Reloj Astronómico — Iteradores Neuronales.
 *
 * Genera un **ramillete de espines** (vectores individuales por astro) que
 * representan de forma determinista la configuración celeste para cualquier
 * ubicación geográfica e instante de tiempo.
 *
 * A partir de la versión 1.5.2, el marco de referencia es
 * **galáctico‑eclíptico**. Los espines cósmicos (Sol, Luna, Júpiter, eje
 * terrestre) se expresan en un marco común a todo el planeta, de modo que
 * dos observadores en distintos lugares comparten exactamente esos vectores.
 * La ubicación geográfica queda representada únicamente por el espin
 * `centro_tierra`, que apunta hacia el centro del planeta.
 *
 * Esta separación permite comparar tiempo puro o espacio puro sin que una
 * señal contamine a la otra, y deja el sistema preparado para, en el futuro,
 * parametrizar otros planetas cambiando únicamente las constantes locales.
 *
 * El cálculo utiliza modelos orbitales simplificados con órbitas circulares
 * que generan ciclos día/noche, fases lunares, estaciones, la precesión nodal
 * lunar (18.6 años), el ciclo de Júpiter (~11.86 años) y el bamboleo del eje
 * terrestre (~25 800 años), sin necesidad de efemérides de alta precisión.
 *
 * @package Iteradores\Tiempo
 * @author Ignacio David Baigorria
 * @since 1.3.5
 * @version 1.5.2
 */

namespace Iteradores\Tiempo;

use Iteradores\Configuracion\Conf;
use Iteradores\Nucleo\Objeto;
use Iteradores\Tiempo\interfaces\ProveedorEspines;

include_once(__DIR__ . '/interfaces/ProveedorEspines.php');

class RelojAstronomico extends Objeto implements ProveedorEspines
{
    /**
     * Latitud configurada para esta instancia (en grados, -90 a 90).
     *
     * @var float
     */
    private float $latitud;

    /**
     * Longitud configurada para esta instancia (en grados, -180 a 180).
     *
     * @var float
     */
    private float $longitud;

    /**
     * Último tiempo Unix para el que se calculó el ramillete.
     *
     * @var int|null
     */
    private ?int $ultimo_tiempo_unix = null;

    /**
     * Último ramillete de espines calculado (caché).
     *
     * @var array<array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}>|null
     */
    private ?array $ultimo_espines = null;

    /**
     * Escalas temporales y astros relevantes para medir distancias sin
     * ambigüedad por enrollamiento de fase.
     *
     * @var array<string, string[]>
     * @since 1.5.2
     */
    private const ESCALAS_TEMPORALES = [
        'segundos' => ['centro_tierra'],
        'horas'    => ['centro_tierra'],
        'dias'     => ['sol'],
        'semanas'  => ['sol'],
        'meses'    => ['sol'],
        'anios'    => ['jupiter'],
        'decadas'  => ['eje_terrestre'],
        'siglos'   => ['eje_terrestre'],
        'milenios' => ['eje_terrestre'],
    ];

    /**
     * Constructor.
     *
     * @param float $latitud  Latitud en grados (-90 a 90).
     * @param float $longitud Longitud en grados (-180 a 180).
     */
    public function __construct(float $latitud, float $longitud)
    {
        $this->latitud  = $latitud;
        $this->longitud = $longitud;
    }

    /**
     * Actualiza la ubicación geográfica del reloj.
     *
     * @param float $latitud  Nueva latitud en grados.
     * @param float $longitud Nueva longitud en grados.
     * @return void
     */
    public function _ubicacion(float $latitud, float $longitud): void
    {
        $this->latitud  = $latitud;
        $this->longitud = $longitud;
        $this->ultimo_tiempo_unix = null;
        $this->ultimo_espines     = null;
    }

    /**
     * Devuelve el ramillete de espines para todos los astros registrados.
     *
     * @param int|null $tiempo_unix Tiempo Unix. Si es null, usa time().
     * @return array<array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}>
     */
    public function espines(?int $tiempo_unix = null): array
    {
        $ts = $tiempo_unix ?? time();

        if ($this->ultimo_tiempo_unix === $ts && $this->ultimo_espines !== null) {
            return $this->ultimo_espines;
        }

        $this->ultimo_tiempo_unix = $ts;
        $this->ultimo_espines = self::calcular_espines($this->latitud, $this->longitud, $ts);

        return $this->ultimo_espines;
    }

    /**
     * Devuelve el espin de un astro específico.
     *
     * @param string   $astro       Nombre del astro.
     * @param int|null $tiempo_unix Tiempo Unix.
     * @return array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}|null
     */
    public function espin(string $astro, ?int $tiempo_unix = null): ?array
    {
        $ts = $tiempo_unix ?? time();

        if (!isset(Conf::RELOJ_ASTROS[$astro])) {
            self::_error("Astro '{$astro}' no está registrado en el reloj.");
            return null;
        }

        $lat_rad = deg2rad($this->latitud);
        $lon_rad = deg2rad($this->longitud);
        $vector  = self::calcular_espin_astro($astro, $ts, $lat_rad, $lon_rad);

        return [
            'nombre' => $astro,
            'tipo'   => Conf::RELOJ_ASTROS[$astro]['tipo'],
            'masa'   => Conf::RELOJ_ASTROS[$astro]['masa'],
            'vector' => $vector,
        ];
    }

    /**
     * Devuelve el ramillete filtrado por escala temporal.
     *
     * @param string   $escala      Escala temporal.
     * @param int|null $tiempo_unix Tiempo Unix.
     * @return array
     */
    public function espines_por_escala(string $escala, ?int $tiempo_unix = null): array
    {
        $espines = $this->espines($tiempo_unix);
        $nombres = self::ESCALAS_TEMPORALES[$escala] ?? array_keys(Conf::RELOJ_ASTROS);
        return array_values(array_filter(
            $espines,
            fn($e) => in_array($e['nombre'], $nombres, true)
        ));
    }

    // ═══════════════════════════════════════════════════════════
    // CÁLCULOS INTERNOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Calcula el ramillete de espines completo.
     *
     * @param float $latitud  Latitud en grados.
     * @param float $longitud Longitud en grados.
     * @param int   $ts       Tiempo Unix.
     * @return array
     */
    private static function calcular_espines(float $latitud, float $longitud, int $ts): array
    {
        $lat_rad = deg2rad($latitud);
        $lon_rad = deg2rad($longitud);

        $espines = [];
        foreach (Conf::RELOJ_ASTROS as $nombre => $config) {
            $vector = self::calcular_espin_astro($nombre, $ts, $lat_rad, $lon_rad);
            $espines[] = [
                'nombre' => $nombre,
                'tipo'   => $config['tipo'],
                'masa'   => $config['masa'],
                'vector' => $vector,
            ];
        }

        return $espines;
    }

    /**
     * Calcula el vector unitario de un astro específico.
     *
     * @param string $nombre  Nombre del astro.
     * @param int    $ts      Tiempo Unix.
     * @param float  $lat_rad Latitud en radianes.
     * @param float  $lon_rad Longitud en radianes.
     * @return array
     */
    private static function calcular_espin_astro(
        string $nombre,
        int $ts,
        float $lat_rad,
        float $lon_rad
    ): array {
        switch ($nombre) {
            case 'sol':
                return self::transformar_a_galactico(
                    self::calcular_vector_sol_ecliptico($ts)
                );
            case 'luna':
                return self::transformar_a_galactico(
                    self::calcular_vector_luna_ecliptico($ts)
                );
            case 'jupiter':
                return self::transformar_a_galactico(
                    self::calcular_vector_jupiter_ecliptico($ts)
                );
            case 'eje_terrestre':
                return self::transformar_a_galactico(
                    self::calcular_vector_eje_terrestre_ecliptico($ts)
                );
            case 'centro_tierra':
                return self::transformar_a_galactico(
                    self::calcular_vector_centro_tierra_ecliptico($lat_rad, $lon_rad, $ts)
                );
            default:
                self::_error("Astro '{$nombre}' no implementado.");
                return self::normalizar([0.0, 0.0, 1.0]);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // MARCO GALÁCTICO‑ECLÍPTICO
    // ═══════════════════════════════════════════════════════════

    /**
     * Convierte un vector del marco eclíptico al marco galáctico‑eclíptico.
     *
     * El marco de destino está definido por:
     * - $\hat{e}_1$: proyección del centro galáctico sobre el plano eclíptico.
     * - $\hat{e}_3$: norte de la eclíptica.
     * - $\hat{e}_2$: completa la terna ortonormal.
     *
     * @param array{x: float, y: float, z: float} $v Vector en marco eclíptico.
     * @return array Vector en marco galáctico‑eclíptico.
     */
    private static function transformar_a_galactico(array $v): array
    {
        $lon_gal = deg2rad(Conf::RELOJ_GALACTICO_LONGITUD);

        // Base ortonormal en coordenadas eclípticas
        $e1 = [cos($lon_gal), sin($lon_gal), 0.0];
        $e3 = [0.0, 0.0, 1.0];
        $e2 = [-sin($lon_gal), cos($lon_gal), 0.0];

        $resultado = [
            $v['x'] * $e1[0] + $v['y'] * $e1[1] + $v['z'] * $e1[2],
            $v['x'] * $e2[0] + $v['y'] * $e2[1] + $v['z'] * $e2[2],
            $v['x'] * $e3[0] + $v['y'] * $e3[1] + $v['z'] * $e3[2],
        ];

        return self::normalizar($resultado);
    }

    /**
     * Normaliza un vector 3D.
     *
     * @param array $v Vector de tres componentes.
     * @return array Vector unitario.
     */
    private static function normalizar(array $v): array
    {
        $magnitud = sqrt($v[0] ** 2 + $v[1] ** 2 + $v[2] ** 2);
        if ($magnitud < 1e-9) {
            return ['x' => 0.0, 'y' => 0.0, 'z' => 1.0];
        }
        return [
            'x' => $v[0] / $magnitud,
            'y' => $v[1] / $magnitud,
            'z' => $v[2] / $magnitud,
        ];
    }

    /**
     * Construye un vector unitario en coordenadas eclípticas.
     *
     * @param float $longitud Longitud eclíptica en radianes.
     * @param float $latitud  Latitud eclíptica en radianes.
     * @return array Vector eclíptico.
     */
    private static function vector_ecliptico(float $longitud, float $latitud): array
    {
        return self::normalizar([
            cos($latitud) * cos($longitud),
            cos($latitud) * sin($longitud),
            sin($latitud),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // MODELOS ORBITALES SIMPLIFICADOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Calcula el vector del Sol en coordenadas eclípticas.
     *
     * @param int $ts Tiempo Unix.
     * @return array Vector eclíptico.
     */
    private static function calcular_vector_sol_ecliptico(int $ts): array
    {
        $angulo_anual = 2.0 * M_PI * fmod($ts, Conf::RELOJ_SEGUNDOS_POR_ANIO)
            / Conf::RELOJ_SEGUNDOS_POR_ANIO;

        $longitud = fmod($angulo_anual, 2.0 * M_PI);
        $latitud  = 0.0;

        return self::vector_ecliptico($longitud, $latitud);
    }

    /**
     * Calcula el vector de la Luna en coordenadas eclípticas.
     *
     * @param int $ts Tiempo Unix.
     * @return array Vector eclíptico.
     */
    private static function calcular_vector_luna_ecliptico(int $ts): array
    {
        $angulo_sinodico = 2.0 * M_PI * fmod($ts, Conf::RELOJ_SEGUNDOS_POR_MES_SINODICO)
            / Conf::RELOJ_SEGUNDOS_POR_MES_SINODICO;
        $angulo_nodal = 2.0 * M_PI * fmod(
            $ts,
            Conf::RELOJ_PERIODO_PRECESION_NODAL * Conf::RELOJ_SEGUNDOS_POR_ANIO
        ) / (Conf::RELOJ_PERIODO_PRECESION_NODAL * Conf::RELOJ_SEGUNDOS_POR_ANIO);

        $longitud = fmod($angulo_sinodico, 2.0 * M_PI);
        $latitud  = deg2rad(Conf::RELOJ_INCLINACION_LUNAR)
                    * sin($angulo_sinodico)
                    * cos($angulo_nodal);

        return self::vector_ecliptico($longitud, $latitud);
    }

    /**
     * Calcula el vector de Júpiter en coordenadas eclípticas.
     *
     * @param int $ts Tiempo Unix.
     * @return array Vector eclíptico.
     */
    private static function calcular_vector_jupiter_ecliptico(int $ts): array
    {
        $periodo_jupiter = Conf::RELOJ_SEGUNDOS_POR_ANIO * Conf::RELOJ_PERIODO_JUPITER_ANIOS;

        $angulo_tierra  = 2.0 * M_PI * fmod($ts, Conf::RELOJ_SEGUNDOS_POR_ANIO)
            / Conf::RELOJ_SEGUNDOS_POR_ANIO;
        $angulo_jupiter = 2.0 * M_PI * fmod($ts, $periodo_jupiter)
            / $periodo_jupiter;

        // Posiciones heliocéntricas en el plano eclíptico (UA)
        $tierra  = [cos($angulo_tierra),  sin($angulo_tierra),  0.0];
        $jupiter = [5.2 * cos($angulo_jupiter), 5.2 * sin($angulo_jupiter), 0.0];

        $relativo = [
            $jupiter[0] - $tierra[0],
            $jupiter[1] - $tierra[1],
            0.0,
        ];

        return self::normalizar($relativo);
    }

    /**
     * Calcula el vector del eje terrestre en coordenadas eclípticas.
     *
     * @param int $ts Tiempo Unix.
     * @return array Vector eclíptico.
     */
    private static function calcular_vector_eje_terrestre_ecliptico(int $ts): array
    {
        $periodo_precesion = Conf::RELOJ_SEGUNDOS_POR_ANIO * Conf::RELOJ_PERIODO_PRECESION_ANIOS;
        $theta = 2.0 * M_PI * fmod($ts, $periodo_precesion) / $periodo_precesion;

        $longitud = M_PI / 2.0 + $theta;
        $latitud  = M_PI / 2.0 - deg2rad(Conf::RELOJ_INCLINACION_ECLIPTICA);

        return self::vector_ecliptico($longitud, $latitud);
    }

    /**
     * Calcula el vector hacia el centro de la Tierra en coordenadas eclípticas.
     *
     * @param float $lat_rad Latitud del observador en radianes.
     * @param float $lon_rad Longitud del observador en radianes.
     * @param int   $ts      Tiempo Unix.
     * @return array Vector eclíptico.
     */
    private static function calcular_vector_centro_tierra_ecliptico(
        float $lat_rad,
        float $lon_rad,
        int $ts
    ): array {
        $eje_planetario = self::calcular_vector_eje_terrestre_ecliptico($ts);

        // Convertir a array numérico para operaciones vectoriales
        $eje = [$eje_planetario['x'], $eje_planetario['y'], $eje_planetario['z']];
        $z_ecliptica = [0.0, 0.0, 1.0];

        // Producto vectorial y normalización
        $e1_eq = self::normalizar(self::producto_vectorial($eje, $z_ecliptica));

        // ⚠️ Convertir $e1_eq a array numérico para usar índices
        $e1_eq = [$e1_eq['x'], $e1_eq['y'], $e1_eq['z']];

        $e2_eq = self::producto_vectorial($eje, $e1_eq);

        $theta = self::tiempo_sidereo_local($ts, $lon_rad);

        $arriba = [
            cos($lat_rad) * cos($theta) * $e1_eq[0]
                + cos($lat_rad) * sin($theta) * $e2_eq[0]
                + sin($lat_rad) * $eje[0],
            cos($lat_rad) * cos($theta) * $e1_eq[1]
                + cos($lat_rad) * sin($theta) * $e2_eq[1]
                + sin($lat_rad) * $eje[1],
            cos($lat_rad) * cos($theta) * $e1_eq[2]
                + cos($lat_rad) * sin($theta) * $e2_eq[2]
                + sin($lat_rad) * $eje[2],
        ];

        $centro = [-$arriba[0], -$arriba[1], -$arriba[2]];
        return self::normalizar($centro);
    }

    /**
     * Producto vectorial entre dos vectores 3D.
     *
     * @param array $a Primer vector.
     * @param array $b Segundo vector.
     * @return array Producto vectorial.
     */
    private static function producto_vectorial(array $a, array $b): array
    {
        return [
            $a[1] * $b[2] - $a[2] * $b[1],
            $a[2] * $b[0] - $a[0] * $b[2],
            $a[0] * $b[1] - $a[1] * $b[0],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // TIEMPO SIDÉREO LOCAL
    // ═══════════════════════════════════════════════════════════

    /**
     * Calcula el Tiempo Sidéreo Local (LST) en radianes.
     *
     * @param int   $ts      Tiempo Unix.
     * @param float $lon_rad Longitud del observador en radianes.
     * @return float LST en radianes (0 a 2π).
     */
    private static function tiempo_sidereo_local(int $ts, float $lon_rad): float
    {
        $dias_desde_j2000 = ($ts / Conf::RELOJ_SEGUNDOS_POR_DIA) - 10957.5;
        $gmst_deg = fmod(280.46061837 + 360.98564736629 * $dias_desde_j2000, 360.0);
        if ($gmst_deg < 0) {
            $gmst_deg += 360.0;
        }
        $gmst_rad = deg2rad($gmst_deg);

        $lst = fmod($gmst_rad + $lon_rad, 2.0 * M_PI);
        return $lst < 0 ? $lst + 2.0 * M_PI : $lst;
    }
}