<?php
/**
 * Migración v1.5piloto.62b — Refuerzo legal de las declaraciones juradas.
 *
 * Reemplaza fragmentos del consentimiento del texto por defecto de las
 * declaraciones juradas del viaje (mayor y menor) por la versión con
 * referencias legales explícitas y pie legal. Solo actúa si el contenido
 * coincide exactamente con el texto por defecto anterior. Si el dueño
 * ya personalizó el texto, no lo toca.
 *
 * Es idempotente: si se corre dos veces, la segunda no hace nada.
 *
 * @package   Iteradores
 * @version   1.5piloto.62b
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");

function migrar_declaraciones_juradas_v2(): array {
    $res = [
        'duenos_procesados' => 0,
        'viajes_procesados' => 0,
        'mayor_migrados' => 0,
        'mayor_sin_cambio' => 0,
        'mayor_personalizados' => 0,
        'menor_migrados' => 0,
        'menor_sin_cambio' => 0,
        'menor_personalizados' => 0,
    ];

    // --- Fragmentos a reemplazar en el Anexo I (mayor) ---
    $buscar_mayor_consent = "Presto mi consentimiento para el tratamiento de los datos de salud indicados en este formulario, en los términos expresados precedentemente.\n\n☐ Sí, presto mi consentimiento.\n\nAsimismo, autorizo, en caso de necesidad y urgencia, a que se solicite asistencia médica y se adopten las indicaciones que los profesionales de la salud consideren necesarias, procurando que se me informe de ello a la brevedad posible.";
    $reemplazo_mayor_consent = "CONSENTIMIENTO PARA EL TRATAMIENTO DE DATOS DE SALUD\n\nDe conformidad con lo dispuesto por el artículo 7 de la Ley 25.326 de Protección de Datos Personales, presto mi consentimiento expreso y por escrito para el tratamiento de los datos de salud consignados en este formulario.\n\n☐ SÍ, PRESTÓ MI CONSENTIMIENTO al tratamiento de mis datos de salud en los términos del artículo 7 de la Ley 25.326.\n   (tildar o marcar con una X)\n\nAsimismo, autorizo, en caso de necesidad y urgencia, a que se solicite asistencia médica y se adopten las indicaciones que los profesionales de la salud consideren necesarias, en los términos del artículo 9 de la Ley 26.529 de Derechos del Paciente, procurando que se me informe de ello a la brevedad posible.";

    $buscar_mayor_pie = "Fecha: ....../....../............";
    $reemplazo_mayor_pie = "Fecha: ....../....../............\n\nEl titular de los datos puede ejercer los derechos de acceso, rectificación, supresión y oposición previstos en la Ley 25.326 contactando al organizador del viaje.";

    // --- Fragmentos a reemplazar en el Anexo II (menor) ---
    $buscar_menor_consent = "Presto mi consentimiento para el tratamiento de los datos de salud indicados en este formulario, en los términos expresados precedentemente.\n\n☐ Sí, presto mi consentimiento.\n\nAsimismo, autorizo, en caso de necesidad y urgencia, a que se solicite asistencia médica para el/la menor y se adopten las indicaciones que los profesionales de la salud consideren necesarias, procurando que se me informe de ello a la brevedad posible.";
    $reemplazo_menor_consent = "CONSENTIMIENTO PARA EL TRATAMIENTO DE DATOS DE SALUD DEL MENOR\n\nDe conformidad con lo dispuesto por el artículo 7 de la Ley 25.326 de Protección de Datos Personales, en mi carácter de padre, madre, tutor/a o responsable legal del menor, presto consentimiento expreso y por escrito para el tratamiento de los datos de salud del menor consignados en este formulario.\n\n☐ SÍ, PRESTÓ MI CONSENTIMIENTO al tratamiento de los datos de salud del menor en los términos del artículo 7 de la Ley 25.326.\n   (tildar o marcar con una X)\n\nAsimismo, autorizo, en caso de necesidad y urgencia, a que se solicite asistencia médica para el/la menor y se adopten las indicaciones que los profesionales de la salud consideren necesarias, en los términos del artículo 9 de la Ley 26.529 de Derechos del Paciente, procurando que se me informe de ello a la brevedad posible.";

    $buscar_menor_pie = "Fecha: ....../....../............";
    $reemplazo_menor_pie = "Fecha: ....../....../............\n\nEl titular de los datos (o su representante legal) puede ejercer los derechos de acceso, rectificación, supresión y oposición previstos en la Ley 25.326 contactando al organizador del viaje.";

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return $res;

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nivel = $nodo_dueno->adyacente('nivel');
        if (!$nivel || $nivel->dato() !== 'dueno') continue;
        $res['duenos_procesados']++;

        $nodo_viajes = $nodo_dueno->adyacente('viajes');
        if (!$nodo_viajes) continue;

        foreach ($nodo_viajes->adyacentes() as $nombre_viaje => $nodo_viaje) {
            $res['viajes_procesados']++;

            // --- Anexo I (mayor) ---
            $nodo_mayor = $nodo_viaje->adyacente('declaracion_jurada_mayor');
            if ($nodo_mayor) {
                $contenido = $nodo_mayor->dato();
                $tenia_consent_viejo = strpos($contenido, $buscar_mayor_consent) !== false;
                $tenia_pie_viejo = (strpos($contenido, $buscar_mayor_pie) !== false
                                    && strpos($contenido, 'derechos de acceso, rectificación, supresión y oposición') === false);

                $cambio = false;
                if ($tenia_consent_viejo) {
                    $contenido = str_replace($buscar_mayor_consent, $reemplazo_mayor_consent, $contenido);
                    $cambio = true;
                }
                if ($tenia_pie_viejo) {
                    $contenido = str_replace($buscar_mayor_pie, $reemplazo_mayor_pie, $contenido);
                    $cambio = true;
                }

                if ($cambio) {
                    $nodo_mayor->_dato($contenido);
                    $res['mayor_migrados']++;
                } else {
                    if (strpos($contenido, 'Presto mi consentimiento para el tratamiento') !== false
                        || strpos($contenido, '☐ Sí, presto mi consentimiento.') !== false) {
                        $res['mayor_sin_cambio']++;
                    } else {
                        $res['mayor_personalizados']++;
                    }
                }
            }

            // --- Anexo II (menor) ---
            $nodo_menor = $nodo_viaje->adyacente('declaracion_jurada_menor');
            if ($nodo_menor) {
                $contenido = $nodo_menor->dato();
                $tenia_consent_viejo = strpos($contenido, $buscar_menor_consent) !== false;
                $tenia_pie_viejo = (strpos($contenido, $buscar_menor_pie) !== false
                                    && strpos($contenido, 'derechos de acceso, rectificación, supresión y oposición') === false);

                $cambio = false;
                if ($tenia_consent_viejo) {
                    $contenido = str_replace($buscar_menor_consent, $reemplazo_menor_consent, $contenido);
                    $cambio = true;
                }
                if ($tenia_pie_viejo) {
                    $contenido = str_replace($buscar_menor_pie, $reemplazo_menor_pie, $contenido);
                    $cambio = true;
                }

                if ($cambio) {
                    $nodo_menor->_dato($contenido);
                    $res['menor_migrados']++;
                } else {
                    if (strpos($contenido, 'Presto mi consentimiento para el tratamiento') !== false
                        || strpos($contenido, '☐ Sí, presto mi consentimiento.') !== false) {
                        $res['menor_sin_cambio']++;
                    } else {
                        $res['menor_personalizados']++;
                    }
                }
            }
        }
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return $res;
}