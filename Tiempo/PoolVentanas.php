<?php
/**
 * Pool de Ventanas — Iteradores Neuronales.
 *
 * El `PoolVentanas` es el **tejido conectivo** del sistema. Es un mapa
 * global único en toda la instancia que almacena todas las ventanas
 * compuestas que han aparecido, indexadas por su matriz 2×2.
 *
 * ## Funciones
 *
 * - **Cuerpo calloso**: conecta nodos de distintos dominios que comparten
 *   la misma llave matricial, habilitando comunicación lateral (resonancia
 *   inter-dominio) sin pasar por un tálamo central.
 * - **Tironeo global**: cada ventana ajusta su `vector_promedio` hacia el
 *   contexto típico bajo el cual se usa.
 * - **Precarga predictiva**: cuando un nodo se activa bajo una llave, el
 *   Pool consulta los `nodos_afiliados` de otros dominios y les inyecta
 *   energía si la distancia espaciotemporal es menor que el umbral de
 *   resonancia.
 *
 * ## Estructura
 *
 * ```
 * PoolVentanas: Map<clave_matricial, VentanaGlobal>
 * ```
 *
 * @package Iteradores\Tiempo
 * @author Ignacio David Baigorria
 * @since 1.5.1
 * @version 1.5.1
 */

namespace Iteradores\Tiempo;

use Iteradores\Nucleo\Objeto;

class PoolVentanas extends Objeto
{
    /**
     * Instancia singleton del pool.
     *
     * @var PoolVentanas|null
     */
    private static ?PoolVentanas $instancia = null;

    /**
     * Mapa de ventanas indexadas por clave matricial serializada.
     *
     * @var array<string, Ventana>
     */
    private array $pool = [];

    /**
     * Umbral de distancia para resonancia inter-dominio.
     *
     * @var float
     */
    private float $umbral_resonancia = 0.5;

    /**
     * Factor de decaimiento para resonancia ($\lambda$).
     *
     * @var float
     */
    private float $lambda_resonancia = 1.0;

    /**
     * Coeficiente de tironeo global ($\beta_{global}$).
     *
     * @var float
     */
    private float $beta_global = 0.05;

    /**
     * Constructor privado (singleton).
     *
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private function __construct()
    {
        parent::__construct();
    }

    /**
     * Devuelve la instancia única del pool.
     *
     * @return PoolVentanas
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public static function instancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Reinicia el singleton (útil para pruebas).
     *
     * @return void
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public static function reiniciar(): void
    {
        self::$instancia = null;
    }

    // ═══════════════════════════════════════════════════════
    // ACCESO AL POOL
    // ═══════════════════════════════════════════════════════

    /**
     * Obtiene una ventana del pool por su clave matricial.
     *
     * @param array{a: int, b: int, c: int, d: int} $matriz Matriz 2×2.
     * @return Ventana|null
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function obtener(array $matriz): ?Ventana
    {
        $clave = "{$matriz['a']},{$matriz['b']},{$matriz['c']},{$matriz['d']}";
        return $this->pool[$clave] ?? null;
    }

    /**
     * Registra una ventana en el pool.
     *
     * Si la ventana ya existe (misma clave), no la reemplaza.
     *
     * @param Ventana $ventana Ventana a registrar.
     * @return Ventana La ventana registrada (la existente o la nueva).
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function registrar(Ventana $ventana): Ventana
    {
        $clave = $ventana->clave();

        if (isset($this->pool[$clave])) {
            return $this->pool[$clave];
        }

        $this->pool[$clave] = $ventana;
        return $ventana;
    }

    /**
     * Devuelve todas las ventanas del pool.
     *
     * @return array<string, Ventana>
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function todas(): array
    {
        return $this->pool;
    }

    /**
     * Devuelve la cantidad de ventanas almacenadas.
     *
     * @return int
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function cantidad(): int
    {
        return count($this->pool);
    }

    // ═══════════════════════════════════════════════════════
    // TIRONEO GLOBAL
    // ═══════════════════════════════════════════════════════

    /**
     * Actualiza el vector promedio de una ventana mediante tironeo global.
     *
     * Si la ventana no está en el pool, la registra primero.
     *
     * @param Ventana                             $ventana Ventana compuesta.
     * @param array{x: float, y: float, z: float} $vector  Vector de activación actual.
     * @return Ventana La ventana actualizada.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function tironear(Ventana $ventana, array $vector): Ventana
    {
        $existente = $this->registrar($ventana);
        $existente->tironear($vector, $this->beta_global);
        return $existente;
    }

    /**
     * Aplica tironeo de campo sobre un subconjunto aleatorio de ventanas.
     *
     * Cada ventana seleccionada se ajusta sutilmente hacia el vector actual,
     * con fuerza decreciente según la distancia entre su `vector_promedio` y
     * el momento presente.
     *
     * $$\hat{w} \leftarrow \hat{w} + \gamma \cdot f(m_w, d_w) \cdot (\hat{V}_{actual} - \hat{w})$$
     *
     * @param array{x: float, y: float, z: float} $vector_actual Vector de activación.
     * @param float                             $gamma        Tasa de tironeo de campo.
     * @param int                               $muestra      Tamaño del subconjunto.
     * @return void
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function tironeo_campo(
        array $vector_actual,
        float $gamma = 0.01,
        int $muestra = 100
    ): void {
        $ventanas = array_values($this->pool);
        $total = count($ventanas);

        if ($total === 0) {
            return;
        }

        $indices = array_rand($ventanas, min($muestra, $total));
        if (!is_array($indices)) {
            $indices = [$indices];
        }

        foreach ($indices as $i) {
            $v = $ventanas[$i];
            $vp = $v->vector_promedio();

            if ($vp === null) {
                continue;
            }

            $dx = $vector_actual['x'] - $vp['x'];
            $dy = $vector_actual['y'] - $vp['y'];
            $dz = $vector_actual['z'] - $vp['z'];
            $distancia = sqrt($dx * $dx + $dy * $dy + $dz * $dz);

            $f = $v->energia_total() / (1.0 + $this->lambda_resonancia * $distancia * $distancia);
            $v->tironear($vector_actual, $gamma * $f);
        }
    }

    // ═══════════════════════════════════════════════════════
    // RESONANCIA INTER-DOMINIO
    // ═══════════════════════════════════════════════════════

    /**
     * Consulta nodos afiliados de otros dominios bajo la misma llave.
     *
     * Cuando un nodo $N$ se activa con energía $E_N$ bajo la llave $M(W)$,
     * el sistema obtiene los nodos afiliados de la ventana. Para cada nodo
     * $N'$ en un dominio diferente, si la distancia espaciotemporal $d$ es
     * menor que el umbral, se inyecta energía:
     *
     * $$E_{N'} \leftarrow E_{N'} + \alpha \cdot E_N \cdot e^{-\lambda d}$$
     *
     * @param Ventana $ventana      Ventana compuesta actual.
     * @param string  $dominio_origen Dominio del nodo que se activa.
     * @param float   $energia      Energía del nodo activado.
     * @param float   $alfa         Coeficiente de resonancia.
     * @return array<string, float> Mapa de id_nodo => energía inyectada.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function resonar(
        Ventana $ventana,
        string $dominio_origen,
        float $energia,
        float $alfa = 0.1
    ): array {
        $existente = $this->obtener($ventana->matriz());
        if ($existente === null) {
            return [];
        }

        $inyectado = [];
        foreach ($existente->nodos_afiliados() as $id_nodo) {
            // Extraer dominio del id_nodo (asume formato "dominio::id")
            $partes = explode('::', $id_nodo, 2);
            $dominio_nodo = $partes[0] ?? '';

            if ($dominio_nodo === $dominio_origen || $dominio_nodo === '') {
                continue;
            }

            // Distancia espaciotemporal simplificada (placeholder)
            $d = 0.0; // El cálculo real requiere nacimiento del nodo
            if ($d < $this->umbral_resonancia) {
                $energia_inyectada = $alfa * $energia * exp(-$this->lambda_resonancia * $d);
                $inyectado[$id_nodo] = $energia_inyectada;
            }
        }

        return $inyectado;
    }

    // ═══════════════════════════════════════════════════════
    // CONFIGURACIÓN
    // ═══════════════════════════════════════════════════════

    /**
     * Establece el umbral de resonancia.
     *
     * @param float $umbral
     * @return void
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function umbral_resonancia(float $umbral): void
    {
        $this->umbral_resonancia = $umbral;
    }

    /**
     * Establece el coeficiente de tironeo global.
     *
     * @param float $beta
     * @return void
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function beta_global(float $beta): void
    {
        $this->beta_global = $beta;
    }
}
