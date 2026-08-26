<?php
namespace Iteradores\Tiempo;

use Iteradores\Configuracion\Conf;
use Iteradores\Nucleo\Objeto;
use Iteradores\Tiempo\interfaces\ProveedorEspines;

/**
 * Reloj Artificial — Iteradores Neuronales.
 *
 * Provee el **Plano Rítmico** de la arquitectura. Genera un ramillete de
 * espines correspondiente a ciclos culturales y artificiales expresados en
 * tiempo universal coordinado (UTC). A diferencia del {@link RelojAstronomico},
 * este reloj no depende de la ubicación geográfica: sus ritmos son globales.
 *
 * Cada ciclo vive en el círculo unitario $S^1$ y se representa como un vector
 * bidimensional proyectado en $z=0$:
 *
 * \[
 * \mathbf{v}(t) = ( \cos(\theta(t)), \sin(\theta(t)), 0 )
 * \]
 *
 * donde $\theta(t) = \phi_0 + 2\pi \frac{t \bmod T}{T}$.
 *
 * La localización geográfica no es responsabilidad de este reloj. La hora
 * local, el día local o el inicio de la semana surgen al combinar estos
 * espines rítmicos con el espín geográfico `centro_tierra` del plano cósmico
 * en el {@link CompositorVentanas}.
 *
 * ## Ciclos puente de largo plazo
 *
 * Para cubrir escalas temporales mayores sin aliasing, se incluyen ciclos
 * puente con períodos de 128 años, década, siglo, milenio y precesión.
 * Estos ciclos permiten medir distancias temporales de forma monótona
 * en rangos que van desde minutos hasta milenios.
 *
 * @package Iteradores\Tiempo
 * @since 1.5.1
 * @version 1.5.2
 */
class RelojArtificial extends Objeto implements ProveedorEspines
{
    /**
     * Ciclos rítmicos registrados.
     *
     * @var array<string, array{periodo: float, fase: float, masa: float, tipo: string}>
     */
    private array $ciclos = [];

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
     * Escalas temporales y ciclos relevantes para medir distancias sin
     * ambigüedad por enrollamiento de fase.
     *
     * @var array<string, string[]>
     * @since 1.5.2
     */
    private const ESCALAS_TEMPORALES = [
        'segundos' => ['minuto'],
        'minutos'  => ['hora'],
        'horas'    => ['dia_noche'],
        'dias'     => ['semana'],
        'semanas'  => ['mes'],
        'meses'    => ['anno'],
        'anios'    => ['ciclo_128'],
        'decadas'  => ['ciclo_siglo'],
        'siglos'   => ['ciclo_milenio'],
        'milenios' => ['ciclo_precesion'],
    ];

    /**
     * Constructor.
     *
     * Inicializa los ciclos de fábrica desde
     * {@link \Iteradores\Configuracion\Conf::RELOJ_CICLOS_RITMICOS}.
     *
     * @since 1.5.1
     * @version 1.5.2
     */
    public function __construct()
    {
        $this->ciclos = Conf::RELOJ_CICLOS_RITMICOS;
    }

    /**
     * Actualiza la ubicación geográfica del proveedor.
     *
     * Este reloj no utiliza ubicación, por lo que el método no produce
     * ningún efecto. Se mantiene por compatibilidad con la interfaz
     * {@link ProveedorEspines}.
     *
     * @param float $latitud  Ignorada.
     * @param float $longitud Ignorada.
     * @return void
     * @since 1.5.2
     */
    public function _ubicacion(float $latitud, float $longitud): void
    {
        // Este proveedor es global y no depende de la ubicación.
    }

    /**
     * Devuelve el ramillete de espines rítmicos para el instante dado.
     *
     * @param int|null $tiempo_unix Tiempo Unix en segundos. Si es null, usa time().
     * @return array<array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}>
     * @since 1.5.1
     * @version 1.5.2
     */
    public function espines(?int $tiempo_unix = null): array
    {
        $ts = $tiempo_unix ?? time();

        if ($this->ultimo_tiempo_unix === $ts && $this->ultimo_espines !== null) {
            return $this->ultimo_espines;
        }

        $espines = [];
        foreach ($this->ciclos as $nombre => $config) {
            $espines[] = $this->calcular_espin_ciclo($nombre, $ts);
        }

        $this->ultimo_tiempo_unix = $ts;
        $this->ultimo_espines = $espines;

        return $espines;
    }

    /**
     * Devuelve el espin de un ciclo específico.
     *
     * @param string   $nombre      Nombre del ciclo.
     * @param int|null $tiempo_unix Tiempo Unix en segundos. Si es null, usa time().
     * @return array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}|null
     * @since 1.5.1
     * @version 1.5.2
     */
    public function espin(string $nombre, ?int $tiempo_unix = null): ?array
    {
        if (!isset($this->ciclos[$nombre])) {
            return null;
        }

        $ts = $tiempo_unix ?? time();
        return $this->calcular_espin_ciclo($nombre, $ts);
    }

    /**
     * Devuelve el ramillete filtrado por escala temporal.
     *
     * @param string   $escala      Una de: 'segundos', 'minutos', 'horas', 'dias', 'semanas', 'meses', 'anios', 'decadas', 'siglos', 'milenios'.
     * @param int|null $tiempo_unix Tiempo Unix en segundos. Si es null, usa time().
     * @return array<array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}>
     * @since 1.5.2
     */
    public function espines_por_escala(string $escala, ?int $tiempo_unix = null): array
    {
        $espines = $this->espines($tiempo_unix);
        $nombres = self::ESCALAS_TEMPORALES[$escala] ?? array_keys($this->ciclos);
        return array_values(array_filter(
            $espines,
            fn($e) => in_array($e['nombre'], $nombres, true)
        ));
    }

    /**
     * Agrega un nuevo ciclo rítmico.
     *
     * @param string $nombre  Nombre único del ciclo.
     * @param float  $periodo Período en segundos (mayor que 0).
     * @param float  $fase    Fase inicial en radianes [0, 2π).
     * @param float  $masa    Peso contextual del ciclo.
     * @param string $tipo    Categoría del ritmo.
     * @return void
     * @since 1.5.1
     * @version 1.5.2
     */
    public function agregar_ciclo(
        string $nombre,
        float $periodo,
        float $fase = 0.0,
        float $masa = 1.0,
        string $tipo = 'Ritmo'
    ): void {
        if ($periodo <= 0.0) {
            self::_error("El período del ciclo '{$nombre}' debe ser mayor que cero.");
            return;
        }

        $this->ciclos[$nombre] = [
            'periodo' => $periodo,
            'fase'    => fmod($fase, 2.0 * M_PI),
            'masa'    => $masa,
            'tipo'    => $tipo,
        ];

        $this->invalidar_cache();
    }

    /**
     * Elimina un ciclo rítmico.
     *
     * @param string $nombre Nombre del ciclo.
     * @return void
     * @since 1.5.1
     * @version 1.5.2
     */
    public function eliminar_ciclo(string $nombre): void
    {
        unset($this->ciclos[$nombre]);
        $this->invalidar_cache();
    }

    /**
     * Devuelve la configuración de un ciclo.
     *
     * @param string $nombre Nombre del ciclo.
     * @return array{periodo: float, fase: float, masa: float, tipo: string}|null
     * @since 1.5.1
     * @version 1.5.2
     */
    public function ciclo(string $nombre): ?array
    {
        return $this->ciclos[$nombre] ?? null;
    }

    /**
     * Devuelve todos los ciclos registrados.
     *
     * @return array<string, array{periodo: float, fase: float, masa: float, tipo: string}>
     * @since 1.5.1
     * @version 1.5.2
     */
    public function ciclos_registrados(): array
    {
        return $this->ciclos;
    }

    /**
     * Descubre un nuevo ciclo a partir de un período estimado.
     *
     * @param string $nombre            Nombre propuesto para el ciclo.
     * @param float  $periodo           Período estimado en segundos.
     * @param int    $tiempo_referencia Tiempo Unix donde el ciclo está en su fase cero.
     * @param float  $masa_inicial      Masa inicial (por defecto 1.0).
     * @return void
     * @since 1.5.1
     * @version 1.5.2
     */
    public function descubrir_ciclo(
        string $nombre,
        float $periodo,
        int $tiempo_referencia,
        float $masa_inicial = 1.0
    ): void {
        $fase = fmod($tiempo_referencia, $periodo) / $periodo * 2.0 * M_PI;
        $this->agregar_ciclo($nombre, $periodo, $fase, $masa_inicial, 'RitmoDescubierto');
    }

    /**
     * Calcula el espín de un ciclo en un instante dado.
     *
     * @param string $nombre Nombre del ciclo.
     * @param int    $ts     Tiempo Unix.
     * @return array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}
     */
    private function calcular_espin_ciclo(string $nombre, int $ts): array
    {
        $config  = $this->ciclos[$nombre];
        $periodo = $config['periodo'];
        $fase    = $config['fase'];
        $masa    = $config['masa'];
        $tipo    = $config['tipo'];

        $angulo = $fase + (fmod($ts, $periodo) / $periodo) * 2.0 * M_PI;

        return [
            'nombre' => $nombre,
            'tipo'   => $tipo,
            'masa'   => $masa,
            'vector' => [
                'x' => cos($angulo),
                'y' => sin($angulo),
                'z' => 0.0,
            ],
        ];
    }

    /**
     * Invalida la caché interna.
     *
     * @return void
     */
    private function invalidar_cache(): void
    {
        $this->ultimo_tiempo_unix = null;
        $this->ultimo_espines     = null;
    }
}