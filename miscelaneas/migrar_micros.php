<?php
/**
 * Script temporal de migración: convierte el enlace 'empresa' de los micros
 * para que apunten al nodo empresa real en lugar de a un string suelto.
 *
 * Uso: index.php?migrar_micros=1
 * Se puede eliminar después de ejecutarlo.
 *
 * @package   Iteradores
 * @since     1.5piloto.27
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;

function migrar_micros_empresa(): array {
    $resultado = [
        'duenos_procesados' => 0,
        'micros_procesados' => 0,
        'micros_migrados' => 0,
        'micros_sin_cambio' => 0,
        'micros_sin_empresa' => 0,
        'micros_sin_match' => 0,
    ];

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) {
        echo "No hay usuarios registrados.\n";
        return $resultado;
    }

    foreach ((array)$raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nodo_nivel = $nodo_dueno->adyacente('nivel');
        if (!$nodo_nivel || $nodo_nivel->dato() !== 'dueno') continue;

        $resultado['duenos_procesados']++;

        $nodo_empresas = $nodo_dueno->adyacente('empresas');
        $nodo_viajes = $nodo_dueno->adyacente('viajes');
        if (!$nodo_viajes) continue;

        foreach ((array)$nodo_viajes->adyacentes() as $nombre_viaje => $nodo_viaje) {
            $nodo_micros = $nodo_viaje->adyacente('micros');
            if (!$nodo_micros) continue;

            foreach ((array)$nodo_micros->adyacentes() as $nombre_micro => $nodo_micro) {
                $resultado['micros_procesados']++;

                $nodo_empresa_actual = $nodo_micro->adyacente('empresa');
                if (!$nodo_empresa_actual) {
                    $resultado['micros_sin_empresa']++;
                    continue;
                }

                $identificador = $nodo_empresa_actual->dato();
                if ($identificador === '') {
                    $resultado['micros_sin_empresa']++;
                    continue;
                }

                // Si ya es el nodo empresa real (está dentro del contenedor), no hay nada que hacer
                $nodo_empresa_real = $nodo_empresas ? $nodo_empresas->adyacente($identificador) : null;
                if (!$nodo_empresa_real) {
                    $resultado['micros_sin_match']++;
                    continue;
                }

                // Comparar por id: si el actual ya apunta al real, no hay cambio
                if ($nodo_empresa_actual->id() === $nodo_empresa_real->id()) {
                    $resultado['micros_sin_cambio']++;
                    continue;
                }

                // Reemplazar el enlace 'empresa' apuntando al nodo empresa real
                $nodo_micro->_adyacente_en($nodo_empresa_real, 'empresa', true);
                $resultado['micros_migrados']++;
            }
        }
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return $resultado;
}