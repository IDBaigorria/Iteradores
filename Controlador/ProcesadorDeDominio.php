<?php
namespace Iteradores\Controlador;

use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\NodoNumerico;
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
 * @package Iteradores\Controlador
 * @since 1.4.5
 * @version 1.4.5
 */
class ProcesadorDeDominio extends Objeto
{
    /**
     * Nombre identificador del dominio (ej. 'texto:entrada', 'algebra').
     *
     * @var string
     */
    private string $_nombre_dominio;

    /**
     * Antenas del dominio, indexadas por número de fase.
     *
     * @var Antena[]
     */
    private array $_antenas;

    /**
     * Constructor.
     *
     * @param string $nombre_dominio Nombre identificador del dominio.
     */
    public function __construct(string $nombre_dominio)
    {
        $this->_nombre_dominio = $nombre_dominio;
        $this->_antenas = [];
    }

    /**
     * Obtiene la antena para una fase específica, creándola si no existe.
     *
     * @param int $fase Número de fase.
     * @return Antena
     */
    public function antena(int $fase): Antena
    {
        if (!isset($this->_antenas[$fase])) {
            $this->_antenas[$fase] = new Antena($fase);
        }
        return $this->_antenas[$fase];
    }

    /**
     * Registra un nodo como patrón en la antena de la fase indicada.
     *
     * Método de conveniencia que delega en Antena::registrar_patron().
     *
     * @param NodoNumerico $nodo Nodo a registrar como patrón.
     * @param int          $fase Fase en la que se registrará.
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
        if (empty($this->_antenas)) {
            return;
        }

        // Obtener las fases ordenadas de mayor a menor.
        $fases = array_keys($this->_antenas);
        rsort($fases);

        $hubo_captura = true;
        while ($hubo_captura) {
            $hubo_captura = false;
            foreach ($fases as $fase) {
                $antena = $this->_antenas[$fase];
                if ($antena->intentar_capturar($senal)) {
                    $hubo_captura = true;
                    break; // Sale del foreach y reinicia el while desde la fase más alta.
                }
            }
        }
    }

    /**
     * Devuelve el nombre del dominio.
     *
     * @return string
     */
    public function nombre_dominio(): string
    {
        return $this->_nombre_dominio;
    }

    /**
     * Devuelve todas las antenas del dominio.
     *
     * @return Antena[]
     */
    public function antenas(): array
    {
        return $this->_antenas;
    }
}