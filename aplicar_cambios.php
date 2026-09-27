<?php
/**
 * Aplicador de cambios automáticos — Proyecto Iteradores.
 *
 * Tanda v1.5piloto.67: eliminacion completa de la ficha medica del backend (fix).
 *
 * Uso:
 *   php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;

// ============================================================
// Cambios a aplicar
// ============================================================

$cambios = [

    // ============================================================
    // Venta.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Bump de version a 1.5piloto.67',
        'buscar' => [' * @version   1.5piloto.58'],
        'reemplazar' => [' * @version   1.5piloto.67'],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Ventas/Venta.php',
        'descripcion' => 'Quitar guardar_ficha_salud en confirmar_venta_actual',
        'buscar' => [
            '        $nodo_pasajero = obtener_o_crear_pasajero($nombre_dueno, $dni_pasajero, $datos_pasajero);',
            '        if (!$nodo_pasajero) {',
            '            return [\'exito\' => false, \'error\' => \'No se pudo crear el pasajero para el asiento \' . ($indice_asiento + 1)];',
            '        }',
            '        // Guardar ficha de salud si viene',
            '        if (isset($datos_pasajero[\'salud\']) && is_array($datos_pasajero[\'salud\'])) {',
            '            guardar_ficha_salud($nombre_dueno, $dni_pasajero, $datos_pasajero[\'salud\']);',
            '        }',
            '',
            '        // Cambiar estado del asiento real a vendido',
        ],
        'reemplazar' => [
            '        $nodo_pasajero = obtener_o_crear_pasajero($nombre_dueno, $dni_pasajero, $datos_pasajero);',
            '        if (!$nodo_pasajero) {',
            '            return [\'exito\' => false, \'error\' => \'No se pudo crear el pasajero para el asiento \' . ($indice_asiento + 1)];',
            '        }',
            '',
            '        // Cambiar estado del asiento real a vendido',
        ],
    ],

    // ============================================================
    // ViajeAsientos.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'Bump de version a 1.5piloto.67',
        'buscar' => [' * @version   1.5piloto.41'],
        'reemplazar' => [' * @version   1.5piloto.67'],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'Quitar guardar_ficha_salud en reservar_asiento_micro',
        'buscar' => [
            '        $nodo_asiento->eliminar_adyacente(\'pasajero\');',
            '        $nodo_asiento->_adyacente_en($nodo_pasajero, \'pasajero\');',
            '',
            '        // Guardar ficha de salud solo si el viaje la muestra',
            '        $opciones = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);',
            '        if (($opciones[\'mostrar_ficha_medica\'] ?? \'0\') === \'1\'',
            '            && isset($datos_pasajero[\'salud\']) && is_array($datos_pasajero[\'salud\'])) {',
            '            guardar_ficha_salud($nombre_dueno, $datos_pasajero[\'dni\'], $datos_pasajero[\'salud\']);',
            '        }',
            '    }',
        ],
        'reemplazar' => [
            '        $nodo_asiento->eliminar_adyacente(\'pasajero\');',
            '        $nodo_asiento->_adyacente_en($nodo_pasajero, \'pasajero\');',
            '    }',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
        'descripcion' => 'Quitar guardar_ficha_salud en asignar_pasajero_a_reserva',
        'buscar' => [
            '    $nodo_asiento->_adyacente_en($nodo_pasajero, \'pasajero\');',
            '',
            '    // Guardar ficha de salud solo si el viaje la muestra',
            '    $opciones = obtener_opciones_avanzadas_viaje($nombre_dueno, $nombre_viaje);',
            '    if (($opciones[\'mostrar_ficha_medica\'] ?? \'0\') === \'1\'',
            '        && isset($datos_pasajero[\'salud\']) && is_array($datos_pasajero[\'salud\'])) {',
            '        guardar_ficha_salud($nombre_dueno, $datos_pasajero[\'dni\'], $datos_pasajero[\'salud\']);',
            '    }',
            '',
            '    actualizar_contadores_micro($nodo_micro);',
        ],
        'reemplazar' => [
            '    $nodo_asiento->_adyacente_en($nodo_pasajero, \'pasajero\');',
            '',
            '    actualizar_contadores_micro($nodo_micro);',
        ],
    ],

    // ============================================================
    // Pasajero.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Bump de version a 1.5piloto.67',
        'buscar' => [' * @version   1.5piloto.66'],
        'reemplazar' => [' * @version   1.5piloto.67'],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Quitar bloque de ficha_salud en formatear_pasajero',
        'buscar' => [
            '            \'es_imagen\' => strpos($tipo_mime, \'image/\') === 0,',
            '            \'es_pdf\' => $tipo_mime === \'application/pdf\',',
            '        ];',
            '    }',
            '',
            '    $ficha = $nodo_pasajero->adyacente(\'ficha_salud\');',
            '    $datos[\'ficha_salud\'] = null;',
            '    if ($ficha) {',
            '        $ficha_salud = [];',
            '',
            '        // Campos simples',
            '        $ficha_salud[\'grupo_sanguineo\'] = $ficha->adyacente(\'grupo_sanguineo\') ? $ficha->adyacente(\'grupo_sanguineo\')->dato() : \'\';',
            '        $ficha_salud[\'obra_social\'] = $ficha->adyacente(\'obra_social\') ? $ficha->adyacente(\'obra_social\')->dato() : \'\';',
            '        $ficha_salud[\'regimenes_comida\'] = $ficha->adyacente(\'regimenes_comida\') ? $ficha->adyacente(\'regimenes_comida\')->dato() : \'\';',
            '        $ficha_salud[\'observaciones\'] = $ficha->adyacente(\'observaciones\') ? $ficha->adyacente(\'observaciones\')->dato() : \'\';',
            '',
            '        // Categorías que antes eran listas, ahora string',
            '        foreach ([\'enfermedades\', \'medicamentos\', \'impedimentos\', \'alergias\'] as $cat) {',
            '            $raiz_cat = $ficha->adyacente($cat);',
            '            $items = [];',
            '            if ($raiz_cat) {',
            '                $actual_item = hmi($raiz_cat);',
            '                while ($actual_item) {',
            '                    $items[] = $actual_item->dato();',
            '                    $actual_item = hd($actual_item);',
            '                }',
            '                if (!empty($items)) {',
            '                    // Si había lista, la concatenamos en un string',
            '                    $ficha_salud[$cat] = implode(\'; \', $items);',
            '                } else {',
            '                    // Si no tiene hijos, puede que ya sea string en el dato del nodo',
            '                    $ficha_salud[$cat] = $raiz_cat->dato() ?? \'\';',
            '                }',
            '            } else {',
            '                $ficha_salud[$cat] = \'\';',
            '            }',
            '        }',
            '',
            '        $datos[\'ficha_salud\'] = $ficha_salud;',
            '    }',
            '',
            '    return $datos;',
            '}',
        ],
        'reemplazar' => [
            '            \'es_imagen\' => strpos($tipo_mime, \'image/\') === 0,',
            '            \'es_pdf\' => $tipo_mime === \'application/pdf\',',
            '        ];',
            '    }',
            '',
            '    return $datos;',
            '}',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Pasajeros/Pasajero.php',
        'descripcion' => 'Quitar funcion guardar_ficha_salud',
        'buscar' => [
            '/**',
            ' * Guarda la ficha de salud de un pasajero.',
            ' * Ahora todos los campos son strings simples (excepto grupo sanguíneo que también es string).',
            ' * Las antiguas listas se convierten a string si se recibe un array.',
            ' */',
            'function guardar_ficha_salud(string $nombre_dueno, string $dni, array $salud): void {',
            '    $nodo_pasajero = obtener_pasajero_nodo_por_dni($nombre_dueno, $dni);',
            '    if (!$nodo_pasajero) return;',
            '',
            '    $ficha = $nodo_pasajero->adyacente(\'ficha_salud\');',
            '    if (!$ficha) {',
            '        $ficha = Nodo::crear_con_dato(\'\');',
            '        $nodo_pasajero->_adyacente_en($ficha, \'ficha_salud\');',
            '    }',
            '',
            '    // Campos simples',
            '    $campos_simples = [\'grupo_sanguineo\', \'obra_social\', \'regimenes_comida\', \'observaciones\'];',
            '    foreach ($campos_simples as $campo) {',
            '        if (isset($salud[$campo])) {',
            '            $valor = trim($salud[$campo]);',
            '            $nodo_campo = $ficha->adyacente($campo);',
            '            if ($nodo_campo) {',
            '                if ($valor === \'\') $ficha->eliminar_adyacente($campo);',
            '                else $nodo_campo->_dato($valor);',
            '            } else {',
            '                if ($valor !== \'\') $ficha->_adyacente_en(Nodo::crear_con_dato($valor), $campo);',
            '            }',
            '        }',
            '    }',
            '',
            '    // Categorías de lista convertidas a string',
            '    $categorias = [\'enfermedades\', \'medicamentos\', \'impedimentos\', \'alergias\'];',
            '    foreach ($categorias as $cat) {',
            '        $raiz = $ficha->adyacente($cat);',
            '        if (!$raiz) {',
            '            $raiz = Nodo::crear_con_dato(\'\');',
            '            $ficha->_adyacente_en($raiz, $cat);',
            '        }',
            '',
            '        // Limpiar posibles hijos antiguos',
            '        while ($hijo = hmi($raiz)) {',
            '            eliminar_hmi($raiz);',
            '        }',
            '',
            '        // Obtener valor (string o array)',
            '        $valor = \'\';',
            '        if (isset($salud[$cat])) {',
            '            if (is_array($salud[$cat])) {',
            '                $valor = implode(\'; \', array_map(\'trim\', $salud[$cat]));',
            '            } else {',
            '                $valor = trim($salud[$cat]);',
            '            }',
            '        }',
            '        $raiz->_dato($valor);',
            '    }',
            '',
            '    Controlador::guardar(Conf::NOMBRE_APP);',
            '}',
            '',
            '/**',
            ' * Obtiene un pasajero por DNI con sus ventas.',
            ' */',
        ],
        'reemplazar' => [
            '/**',
            ' * Obtiene un pasajero por DNI con sus ventas.',
            ' */',
        ],
    ],

    // ============================================================
    // Impresion.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Bump de version a 1.5piloto.67',
        'buscar' => [' * @version   1.5piloto.65'],
        'reemplazar' => [' * @version   1.5piloto.67'],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Quitar branch ficha_salud en generar_impresion',
        'buscar' => [
            'function generar_impresion(string $tipo, string $id_venta, string $dni_filtro = \'\', string $numero_cupon = \'\'): void {',
            '    if ($tipo === \'ficha_salud\') {',
            '        // Para ficha de salud, id_venta contendrá el nombre_dueno y dni_filtro el dni',
            '        imprimir_ficha_salud($id_venta, $dni_filtro);',
            '        return;',
            '    }',
            '    $venta = obtener_venta_por_id($id_venta);',
        ],
        'reemplazar' => [
            'function generar_impresion(string $tipo, string $id_venta, string $dni_filtro = \'\', string $numero_cupon = \'\'): void {',
            '    $venta = obtener_venta_por_id($id_venta);',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Impresion/Impresion.php',
        'descripcion' => 'Quitar funcion imprimir_ficha_salud (con @page de la v64)',
        'buscar' => [
            '/**',
            ' * Imprime la ficha de salud completa de un pasajero.',
            ' */',
            'function imprimir_ficha_salud(string $nombre_dueno, string $dni): void {',
            '    $pasajero = obtener_pasajero_por_dni($nombre_dueno, $dni);',
            '    if (!$pasajero) {',
            '        echo "Pasajero no encontrado";',
            '        return;',
            '    }',
            '',
            '    $ficha = $pasajero[\'ficha_salud\'] ?? null;',
            '    $logo_ruta = \'./Aplicacion/LogoPeque.png\';',
            '',
            '    echo \'<!DOCTYPE html>\';',
            '    echo \'<html lang="es">\';',
            '    echo \'<head><meta charset="UTF-8"><title>Ficha de salud</title>\';',
            '    echo \'<style>',
            '        @page {',
            '            @bottom-center {',
            '                content: "Hoja " counter(page) " de " counter(pages);',
            '                font-size: 10px;',
            '                color: #4a3a2a;',
            '                font-family: "Segoe UI", Arial, sans-serif;',
            '            }',
            '        }',
            '        body {',
            '            font-family: "Segoe UI", Arial, sans-serif;',
            '            margin: 0;',
            '            padding: 20px;',
            '            background: white;',
            '            color: black;',
            '            font-size: 12px;',
            '        }',
            '        .ficha {',
            '            max-width: 800px;',
            '            margin: 0 auto;',
            '            border: 2px solid black;',
            '            border-radius: 8px;',
            '            background: white;',
            '            overflow: hidden;',
            '        }',
            '        .membrete {',
            '            display: flex;',
            '            align-items: center;',
            '            justify-content: flex-start;',
            '            border-bottom: 2px solid black;',
            '            padding: 10px 15px;',
            '        }',
            '        .membrete img {',
            '            width: 60px;',
            '            height: 60px;',
            '            object-fit: contain;',
            '            filter: grayscale(100%);',
            '            margin-right: 15px;',
            '        }',
            '        .membrete-texto {',
            '            font-size: 18px;',
            '            font-weight: bold;',
            '            letter-spacing: 1px;',
            '        }',
            '        .contenido {',
            '            padding: 20px;',
            '        }',
            '        .encabezado {',
            '            text-align: center;',
            '            margin-bottom: 20px;',
            '        }',
            '        .encabezado h1 {',
            '            margin: 0;',
            '            font-size: 20px;',
            '        }',
            '        .seccion {',
            '            border: 1px solid black;',
            '            border-radius: 6px;',
            '            padding: 15px;',
            '            margin-bottom: 15px;',
            '        }',
            '        .seccion h3 {',
            '            margin-top: 0;',
            '            margin-bottom: 10px;',
            '            border-bottom: 1px solid black;',
            '            padding-bottom: 5px;',
            '            font-size: 15px;',
            '        }',
            '        .fila {',
            '            margin-bottom: 5px;',
            '            font-size: 14px;',
            '        }',
            '        .fila strong {',
            '            display: inline-block;',
            '            min-width: 140px;',
            '        }',
            '        .lista {',
            '            margin-left: 20px;',
            '            list-style-type: disc;',
            '        }',
            '        @media print {',
            '            body { padding: 0; background: white; }',
            '            .ficha { border: 2px solid black; box-shadow: none; }',
            '        }',
            '    </style>\';',
            '    echo \'</head><body>\';',
            '',
            '    echo \'<div class="ficha">\';',
            '    echo \'<div class="membrete">\';',
            '    echo \'<img src="\' . htmlspecialchars($logo_ruta) . \'" alt="Logo">\';',
            '    echo \'<div class="membrete-texto">Parroquia Nuestra Señora del Carmen - Tres Arroyos</div>\';',
            '    echo \'</div>\';',
            '',
            '    echo \'<div class="contenido">\';',
            '    echo \'<div class="encabezado"><h1>Ficha de salud</h1></div>\';',
            '',
            '    // Datos personales',
            '    echo \'<div class="seccion">\';',
            '    echo \'<h3>Datos del pasajero</h3>\';',
            '    $nombre_completo = $pasajero[\'nombre_completo\']',
            '        ?? formatear_nombre_completo($pasajero[\'apellido\'] ?? \'\', $pasajero[\'nombres\'] ?? \'\');',
            '    echo \'<div class="fila"><strong>Nombre completo:</strong> \' . htmlspecialchars($nombre_completo) . \'</div>\';',
            '    echo \'<div class="fila"><strong>DNI:</strong> \' . htmlspecialchars(formatear_dni_con_puntos($pasajero[\'dni\'])) . \'</div>\';',
            '    if (!empty($pasajero[\'celular\'])) echo \'<div class="fila"><strong>Celular:</strong> \' . htmlspecialchars($pasajero[\'celular\']) . \'</div>\';',
            '    if (!empty($pasajero[\'celular_emergencia\'])) echo \'<div class="fila"><strong>Emergencia:</strong> \' . htmlspecialchars($pasajero[\'celular_emergencia\']) . \'</div>\';',
            '    if (!empty($pasajero[\'email\'])) echo \'<div class="fila"><strong>Email:</strong> \' . htmlspecialchars($pasajero[\'email\']) . \'</div>\';',
            '    if (!empty($pasajero[\'fecha_nacimiento\'])) echo \'<div class="fila"><strong>Fecha nacimiento:</strong> \' . htmlspecialchars(formatear_fecha_visible($pasajero[\'fecha_nacimiento\'])) . \'</div>\';',
            '    $dir = trim(($pasajero[\'direccion\'] ?? \'\') . \', \' . ($pasajero[\'localidad\'] ?? \'\'));',
            '    if (!empty($dir)) echo \'<div class="fila"><strong>Dirección:</strong> \' . htmlspecialchars($dir) . \'</div>\';',
            '    echo \'</div>\';',
            '',
            '    if ($ficha) {',
            '        echo \'<div class="seccion">\';',
            '        echo \'<h3>Datos de salud</h3>\';',
            '',
            '        // Grupo sanguíneo',
            '        if (!empty($ficha[\'grupo_sanguineo\'])) {',
            '            echo \'<div class="fila"><strong>Grupo sanguíneo:</strong> \' . htmlspecialchars($ficha[\'grupo_sanguineo\']) . \'</div>\';',
            '        }',
            '        // Obra social',
            '        if (!empty($ficha[\'obra_social\'])) {',
            '            echo \'<div class="fila"><strong>Obra social o prepaga (incluya numero de emergencias si corresponde):</strong> \' . htmlspecialchars($ficha[\'obra_social\']) . \'</div>\';',
            '        }',
            '        // Alergias',
            '        if (!empty($ficha[\'alergias\'])) {',
            '            echo \'<div class="fila"><strong>Alergias:</strong> \' . htmlspecialchars($ficha[\'alergias\']) . \'</div>\';',
            '        }',
            '        // Enfermedades',
            '        if (!empty($ficha[\'enfermedades\'])) {',
            '            echo \'<div class="fila"><strong>Enfermedades:</strong> \' . htmlspecialchars($ficha[\'enfermedades\']) . \'</div>\';',
            '        }',
            '        // Medicamentos',
            '        if (!empty($ficha[\'medicamentos\'])) {',
            '            echo \'<div class="fila"><strong>Medicamentos:</strong> \' . htmlspecialchars($ficha[\'medicamentos\']) . \'</div>\';',
            '        }',
            '        // Impedimentos',
            '        if (!empty($ficha[\'impedimentos\'])) {',
            '            echo \'<div class="fila"><strong>Impedimentos:</strong> \' . htmlspecialchars($ficha[\'impedimentos\']) . \'</div>\';',
            '        }',
            '        // Regímenes especiales de comida',
            '        if (!empty($ficha[\'regimenes_comida\'])) {',
            '            echo \'<div class="fila"><strong>¿Sigue algún regimen especial de comida?:</strong> \' . htmlspecialchars($ficha[\'regimenes_comida\']) . \'</div>\';',
            '        }',
            '        // Algún otro dato',
            '        if (!empty($ficha[\'observaciones\'])) {',
            '            echo \'<div class="fila"><strong>Algún otro dato que considere importante:</strong> \' . htmlspecialchars($ficha[\'observaciones\']) . \'</div>\';',
            '        }',
            '',
            '        echo \'</div>\';',
            '    } else {',
            '        echo \'<p>No hay ficha de salud registrada.</p>\';',
            '    }',
            '',
            '    echo \'</div>\'; // cierre contenido',
            '    echo \'</div>\'; // cierre ficha',
            '',
            '    echo \'<script>window.onload = function() { window.print(); }</script>\';',
            '    echo \'</body></html>\';',
            '}',
            '',
            '/**',
            ' * Devuelve el nombre real de un usuario con fallback al nombre de',
        ],
        'reemplazar' => [
            '/**',
            ' * Devuelve el nombre real de un usuario con fallback al nombre de',
        ],
    ],

    // ============================================================
    // Enrutador.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Bump de version a 1.5piloto.67',
        'buscar' => [' * @version   1.5piloto.66'],
        'reemplazar' => [' * @version   1.5piloto.67'],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Quitar subaccion guardar_ficha',
        'buscar' => [
            '                case \'guardar_ficha\':',
            '                    $nombre_dueno = $post[\'nombre_dueno\'] ?? \'\';',
            '                    $dni = $post[\'dni\'] ?? \'\';',
            '                    $ficha_json = $post[\'ficha\'] ?? \'[]\';',
            '                    $ficha = json_decode($ficha_json, true);',
            '                    if (!is_array($ficha)) $ficha = [\'enfermedades\' => [], \'medicamentos\' => [], \'impedimentos\' => []];',
            '                    if (empty($nombre_dueno) || empty($dni)) {',
            '                        responder_json([\'exito\' => false, \'error\' => \'Dueño y DNI son obligatorios\']);',
            '                    }',
            '                    guardar_ficha_salud($nombre_dueno, $dni, $ficha);',
            '                    responder_json([\'exito\' => true]);',
            '                    break;',
            '                case \'eliminar\':',
        ],
        'reemplazar' => [
            '                case \'eliminar\':',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Enrutador.php',
        'descripcion' => 'Quitar mostrar_ficha_medica de guardar_opciones_avanzadas',
        'buscar' => [
            '                    $opciones = [',
            '                        \'mostrar_ficha_medica\' => $post[\'mostrar_ficha_medica\'] ?? \'0\',',
            '                        \'restriccion_edad\' => $post[\'restriccion_edad\'] ?? \'0\',',
        ],
        'reemplazar' => [
            '                    $opciones = [',
            '                        \'restriccion_edad\' => $post[\'restriccion_edad\'] ?? \'0\',',
        ],
    ],

    // ============================================================
    // ViajeOpciones.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
        'descripcion' => 'Bump de version a 1.5piloto.67',
        'buscar' => [' * @version   1.5piloto.63'],
        'reemplazar' => [' * @version   1.5piloto.67'],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
        'descripcion' => 'Quitar mostrar_ficha_medica del array defaults',
        'buscar' => [
            '    $defaults = [',
            '        \'mostrar_ficha_medica\' => \'0\',',
            '        \'restriccion_edad\' => \'0\',',
        ],
        'reemplazar' => [
            '    $defaults = [',
            '        \'restriccion_edad\' => \'0\',',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
        'descripcion' => 'Quitar mostrar_ficha_medica del array campos',
        'buscar' => [
            '    $campos = [',
            '        \'mostrar_ficha_medica\',',
            '        \'restriccion_edad\',',
        ],
        'reemplazar' => [
            '    $campos = [',
            '        \'restriccion_edad\',',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
        'descripcion' => 'Quitar calcular mostrar_ficha en guardar_opciones_avanzadas_viaje',
        'buscar' => [
            '    // Sanitizar valores',
            '    $mostrar_ficha = ($opciones[\'mostrar_ficha_medica\'] ?? \'0\') === \'1\' ? \'1\' : \'0\';',
            '    $restriccion = ($opciones[\'restriccion_edad\'] ?? \'0\') === \'1\' ? \'1\' : \'0\';',
        ],
        'reemplazar' => [
            '    // Sanitizar valores',
            '    $restriccion = ($opciones[\'restriccion_edad\'] ?? \'0\') === \'1\' ? \'1\' : \'0\';',
        ],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/ViajeOpciones.php',
        'descripcion' => 'Quitar actualizar campo mostrar_ficha_medica',
        'buscar' => [
            '    _actualizar_o_crear_campo($nodo_opciones, \'mostrar_ficha_medica\', $mostrar_ficha);',
            '    _actualizar_o_crear_campo($nodo_opciones, \'restriccion_edad\', $restriccion);',
        ],
        'reemplazar' => [
            '    _actualizar_o_crear_campo($nodo_opciones, \'restriccion_edad\', $restriccion);',
        ],
    ],

    // ============================================================
    // Viaje.php
    // ============================================================

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Bump de version a 1.5piloto.67',
        'buscar' => [' * @version   1.5piloto.63'],
        'reemplazar' => [' * @version   1.5piloto.67'],
    ],

    [
        'tipo' => 'reemplazar',
        'archivo' => 'Aplicacion/Viajes/Viaje.php',
        'descripcion' => 'Quitar mostrar_ficha_medica del array opciones en guardar_viaje_completo',
        'buscar' => [
            '    $opciones = [',
            '        \'mostrar_ficha_medica\' => $datos[\'mostrar_ficha_medica\'] ?? \'0\',',
            '        \'restriccion_edad\' => $datos[\'restriccion_edad\'] ?? \'0\',',
        ],
        'reemplazar' => [
            '    $opciones = [',
            '        \'restriccion_edad\' => $datos[\'restriccion_edad\'] ?? \'0\',',
        ],
    ],

];

// ============================================================
// Runner
// ============================================================

echo "=== Aplicador de cambios ===\n\n";

function detectar_eol(string $contenido): string {
    return (strpos($contenido, "\r\n") !== false) ? "\r\n" : "\n";
}
function normalizar_a_unix(string $contenido): string {
    return str_replace("\r\n", "\n", $contenido);
}
function normalizar_a_original(string $contenido, string $eol): string {
    if ($eol === "\n") return $contenido;
    return str_replace("\n", "\r\n", $contenido);
}
function contar_ocurrencias(string $contenido, string $bloque): int {
    if ($bloque === '') return 0;
    $count = 0;
    $offset = 0;
    while (($pos = strpos($contenido, $bloque, $offset)) !== false) {
        $count++;
        $offset = $pos + strlen($bloque);
    }
    return $count;
}

$creaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado (faltan campos).\n";
        exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $lista) { $total_reemplazos += count($lista); }

echo "[INFO] " . count($creaciones) . " archivo(s) a crear, "
    . $total_reemplazos . " reemplazo(s) en "
    . count($reemplazos_por_archivo) . " archivo(s).\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista_cambios) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) {
        $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel";
        foreach ($lista_cambios as $c) $bloques_fallidos[] = "  - {$c['descripcion']}";
        continue;
    }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }

    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;

    foreach ($lista_cambios as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $ocurrencias = contar_ocurrencias($contenido, $buscar_str);
        if ($ocurrencias === 0) {
            $bloques_fallidos[] = "$archivo_rel: bloque no encontrado - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        if ($ocurrencias > 1) {
            $bloques_fallidos[] = "$archivo_rel: bloque ambiguo ($ocurrencias ocurrencias) - {$cambio['descripcion']}";
            $hubo_error = true; continue;
        }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = normalizar_a_original($contenido, $eol);
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    echo "Se detectaron " . count($bloques_fallidos) . " problema(s). No se escribió ningún archivo.\n\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    echo "\nSugerencia: revisá que el bloque a buscar coincida exactamente con el archivo actual.\n";
    exit(1);
}

foreach ($archivos_a_escribir as $ruta_abs => $contenido_final) {
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    echo "[OK] " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
}

foreach ($creaciones as $creacion) {
    $ruta_abs = $raiz_proyecto . '/' . $creacion['archivo'];
    $dir_destino = dirname($ruta_abs);
    if (!is_dir($dir_destino)) mkdir($dir_destino, 0777, true);
    $contenido_nuevo = implode("\n", $creacion['contenido']);
    $ya_existia = file_exists($ruta_abs);
    if (file_put_contents($ruta_abs, $contenido_nuevo) === false) {
        echo "[FALLO] No se pudo crear: {$creacion['archivo']}\n"; continue;
    }
    $accion = $ya_existia ? 'sobrescrito' : 'creado';
    echo "[OK] {$creacion['archivo']} ($accion)\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos nuevos:   " . count($creaciones) . "\n";
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";