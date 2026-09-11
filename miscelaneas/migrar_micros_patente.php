<?php
use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;

function migrar_micros_patente(): array {
    $resultado = [
        'micros_procesados' => 0,
        'micros_limpiados' => 0,
        'micros_sin_enlace' => 0,
        'micros_sin_copia' => 0,
    ];

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return $resultado;

    foreach ((array)$raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nodo_nivel = $nodo_dueno->adyacente('nivel');
        if (!$nodo_nivel || $nodo_nivel->dato() !== 'dueno') continue;

        $nodo_viajes = $nodo_dueno->adyacente('viajes');
        if (!$nodo_viajes) continue;

        foreach ((array)$nodo_viajes->adyacentes() as $nombre_viaje => $nodo_viaje) {
            $nodo_micros = $nodo_viaje->adyacente('micros');
            if (!$nodo_micros) continue;

            foreach ((array)$nodo_micros->adyacentes() as $nombre_micro => $nodo_micro) {
                $resultado['micros_procesados']++;

                if (!$nodo_micro->adyacente('patente')) {
                    $resultado['micros_sin_enlace']++;
                    continue;
                }

                if (!$nodo_micro->adyacente('vehiculo_copia')) {
                    $resultado['micros_sin_copia']++;
                    continue;
                }

                $nodo_micro->eliminar_adyacente('patente');
                $resultado['micros_limpiados']++;
            }
        }
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return $resultado;
}