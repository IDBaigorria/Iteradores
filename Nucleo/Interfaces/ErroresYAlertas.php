<?php
namespace Iteradores\Nucleo\Interfaces;
require("Errores.php");
require("Alertas.php");

/**
 * Define la interfaz para el manejo de mensajes de alertas y errores dentro del sistema
 * (Extiene Alertas y a Errores)
 *
 * Las clases que implementen esta interfaz deberán proporcionar mecanismos
 * para registrar, imprimir y obtener alertas y errores formateados.
 * @extends Errores
 * @extends Alertas
 * @package Iteradores\Nucleo\Interfaces
 */
interface ErroresYAlertas extends Errores, Alertas
{
  /**
   * Activa la recolección de errores y alertas en el sistema.
   * 
   * Esta función permite que los errores registrados mediante _error() y las alertas
	 * registradss mediante _alerta()
	 * se agreguen a la listas/pilsa centralizada para su posterior análisis o visualización.
   * 
   * @see Nucleo\Alertas::activar_alertas()
   * @see Nucleo\Errores::activar_errores()
   *
   * @return void
   */
  public static function activar_errores_y_alertas();

  /**
   * Desactiva la recolección de mensajes de error y de alerta en el sistema.
   * 
   * Una vez desactivada, los errores registrados mediante _error() y las alertas
	 * registradas mediante _alerta()
	 * se agreguen a la listas/pilsa centralizada para su posterior análisis o visualización.
   * 
   * @see Nucleo\Alertas::desactivar_alertas()
   * @see Nucleo\Errores::desactivar_errores()
   *
   * @return void
   */
  public static function desactivar_errores_y_alertas();

}
?>