<?php
namespace Iteradores\Controlador\interfaces;

/**
 * Contrato para la compuerta entre el tálamo y los dominios especializados.
 *
 * La compuerta es el mecanismo que traduce comandos de alto nivel del
 * {@link \Iteradores\Controlador\Controlador} (actuando como tálamo) en
 * secuencias de comandos atómicos de dominio (como `leer_byte` o
 * `escribir_byte`), y viceversa.
 *
 * ## Rol futuro
 *
 * - **Ida:** Descompone un comando del tálamo en una secuencia de comandos
 *   atómicos que se inyectan en la fase 0 de un dominio especializado.
 * - **Vuelta:** Recompone los resultados del dominio especializado en un
 *   comando de respuesta para el tálamo.
 *
 * Por ahora esta interfaz es solo documental y no declara métodos.
 *
 * @author Ignacio David Baigorria
 *
 * @package Iteradores\Controlador\interfaces
 * @since 1.3.8
 */
interface CompuertaDominio
{
    // Métodos se definirán en versiones futuras.
}