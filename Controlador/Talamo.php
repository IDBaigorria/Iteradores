<?php
namespace Iteradores\Controlador;

use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoNumerico;

/**
 * Tálamo: dominio de traducción entre bytes y el alfabeto universal de matrices.
 *
 * Es un {@link ProcesadorDeDominio} limitado a la fase 0 (nunca asciende)
 * y precargado con los 256 patrones que mapean cada byte a su matriz prima.
 * Actúa como singleton gestionado por el {@link Controlador}.
 *
 * @package Iteradores\Controlador
 * @since 1.4.7
 */
class Talamo extends ProcesadorDeDominio
{
    /**
     * @var self|null Instancia única.
     */
    private static ?self $instancia = null;

    /**
     * Constructor privado para garantizar el singleton.
     */
    private function __construct()
    {
        parent::__construct('Talamo', 'entrada');
        $this->establecer_maxima_fase(0);
    }

    /**
     * Obtiene la instancia única del Tálamo, inicializándola si es necesario.
     *
     * @return self
     * //DS: yo la llamaria talamo() //
     */
    public static function obtener(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Precarga los 256 patrones byte↔matriz mediante aprendizaje trivial.
     *
     * Crea una señal con las matrices primas correspondientes a los bytes 0‑255
     * y la pasa a {@link procesar}. Como la antena de fase 0 está vacía,
     * cada byte dispara el aprendizaje trivial y registra su patrón.
     *
     * @return void
     * //DS: calcular todos esos esos nodos primos de entrada puede hacer a un retrazo 
     * // notable, creo que es mucho mas rapido colocar directamente un array con los primos 300
     * // numeros primos conocidos, y si son mas de 256 porque planeo usar
     */
    public function precargar(): void
    {
        // Solo precargar si la antena 0 no tiene patrones todavía
       // echo "que!".(string)count(NodoNumerico::$primos_conocidos);
        if (count($this->antena(0)->patrones()) >= 256) {
          //  echo "que2!";
            return;
        }
     //   echo "que!3";
        $matrices = [];
        for ($b = 0; $b < 256; $b++) {
            $primo = NodoNumerico::$primos_conocidos[$b] ?? null;
            echo "Byte $b -> primo: " . ($primo ?? 'NULL') . "\n";
            if ($primo === null) {
                echo "  ERROR: No hay primo para el byte $b\n";
                continue;
            }
            $matriz = Matriz2x2::crear_prima($primo);
            $matrices[] = $matriz;
        }
        echo "Total matrices generadas: " . count($matrices) . "\n";
      //  echo "que5!".(string)count(NodoNumerico::$primos_conocidos);
        $senal = new Senal($matrices);
        $this->procesar($senal);
    }

    /**
     * Traduce una cadena de bytes a una señal en el alfabeto del Tálamo.
     *
     * @param string $bytes
     * @return Senal
     */
    public function traducir_entrada(string $bytes): Senal
    {
        $matrices = [];
        $longitud = strlen($bytes);
        for ($i = 0; $i < $longitud; $i++) {
            $byte = ord($bytes[$i]);
            $primo = NodoNumerico::$primos_conocidos[$byte];
            $matrices[] = Matriz2x2::crear_prima($primo);
        }
        $senal = new Senal($matrices);
        $this->procesar($senal);
        return $this->emitir_senal(); // devuelve una nueva Senal traducida
    }

    /**
     * Traduce una señal del alfabeto del sistema de vuelta a bytes.
     *
     * @param Senal $senal
     * @return string
     */
    public function traducir_salida(Senal $senal): string
    {
        $this->procesar($senal);
        $pgramas = $this->elementos_procesados();
        $bytes = '';
        foreach ($pgramas as $pgrama) {
            $numero = $pgrama[0]; // el p‑grama en el Tálamo es siempre [byte]
            // Buscar el byte correspondiente al primo $numero
            $byte = array_search($numero, NodoNumerico::$primos_conocidos, true);
            if ($byte !== false) {
                $bytes .= chr($byte);
            }
        }
        return $bytes;
    }
}