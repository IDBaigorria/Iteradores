<?php
/**
 * Aplicador — Fix del croquis multi-piso + selección por piso.
 *
 * Motivo: el croquis y todas las acciones de asiento matchean por
 * (fila, columna) sin piso. En un micro con pisos que tienen las
 * mismas filas/columnas (ej. piso 1 filas 1-3, piso 2 filas 1-12),
 * los asientos del piso 1 colisionan con los del piso 2. El
 * `.find()` devuelve el del piso 1 y pinta mal los del piso 2.
 *
 * Fix: agregar `piso` a `obtener_estados_asientos_micro` y a todos
 * los matcheos del frontend. Backend: las 5 acciones de asiento y
 * `cambiar_asiento_pasaje` aceptan `?string $piso = null` (o
 * `piso_vieja`/`piso_nueva`) y matchean por (piso, fila, columna)
 * si viene. Sin piso, comportamiento actual (compatibilidad con
 * el flujo de la pestaña Clientes).
 *
 * Uso (raíz del proyecto): php aplicar_cambios.php
 */

$modo_estricto = true;
$raiz_proyecto = __DIR__;
$version_nueva = '1.5piloto.82h';

$htmls_a_bumpear = ['aplicacion_GET.html'];

$cambios = [

// ============================================================
// 1. ViajeAsientos.php — obtener_estados_asientos_micro:
//    agregar 'piso' a cada asiento
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'obtener_estados_asientos_micro: agregar piso',
    'buscar' => [
        '                $asiento_info = [',
        '                    \'fila\' => $fila ? $fila->dato() : \'\',',
        '                    \'columna\' => $columna ? $columna->dato() : \'\',',
        '                    \'numero\' => $actual->dato(),',
        '                    \'estado\' => $estado ? $estado->dato() : \'libre\',',
    ],
    'reemplazar' => [
        '                $asiento_info = [',
        '                    // Fix v1.5piloto.82h: piso. Necesario para',
        '                    // desambiguar asientos de pisos distintos con',
        '                    // la misma (fila, columna).',
        '                    \'piso\' => (string)$i,',
        '                    \'fila\' => $fila ? $fila->dato() : \'\',',
        '                    \'columna\' => $columna ? $columna->dato() : \'\',',
        '                    \'numero\' => $actual->dato(),',
        '                    \'estado\' => $estado ? $estado->dato() : \'libre\',',
    ],
],

// ============================================================
// 2. ViajeAsientos.php — reservar_asiento_micro: piso opcional
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'reservar_asiento_micro: piso opcional en firma',
    'buscar' => [
        'function reservar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, array $datos_pasajero = [], ?Nodo $nodo_contexto = null): array {',
    ],
    'reemplazar' => [
        'function reservar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, array $datos_pasajero = [], ?Nodo $nodo_contexto = null, ?string $piso = null): array {',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'reservar_asiento_micro: matcheo por piso',
    'buscar' => [
        '    $nodo_asiento = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            $piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$piso) continue;',
        '            $cabeza = $piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            while ($actual && $actual->id() !== $cabeza->id()) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {',
        '                    $nodo_asiento = $actual;',
        '                    break 2;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '            }',
        '        }',
        '    }',
        '',
        '    if (!$nodo_asiento) return [\'exito\' => false, \'error\' => \'Asiento no encontrado\'];',
        '',
        '    $estado = $nodo_asiento->adyacente(\'estado\');',
        '    if ($estado && $estado->dato() !== \'libre\') {',
        '        return [\'exito\' => false, \'error\' => \'El asiento no está libre para reservar\'];',
        '    }',
    ],
    'reemplazar' => [
        '    $nodo_asiento = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            if ($piso !== null && $piso !== \'\' && (string)$i !== (string)$piso) continue;',
        '            $nodo_piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$nodo_piso) continue;',
        '            $cabeza = $nodo_piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            while ($actual && $actual->id() !== $cabeza->id()) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {',
        '                    $nodo_asiento = $actual;',
        '                    break 2;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '            }',
        '        }',
        '    }',
        '',
        '    if (!$nodo_asiento) return [\'exito\' => false, \'error\' => \'Asiento no encontrado\'];',
        '',
        '    $estado = $nodo_asiento->adyacente(\'estado\');',
        '    if ($estado && $estado->dato() !== \'libre\') {',
        '        return [\'exito\' => false, \'error\' => \'El asiento no está libre para reservar\'];',
        '    }',
    ],
],

// ============================================================
// 3. ViajeAsientos.php — asignar_pasajero_a_reserva
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'asignar_pasajero_a_reserva: piso opcional en firma',
    'buscar' => [
        'function asignar_pasajero_a_reserva(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, array $datos_pasajero, ?Nodo $nodo_contexto = null): array {',
    ],
    'reemplazar' => [
        'function asignar_pasajero_a_reserva(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, array $datos_pasajero, ?Nodo $nodo_contexto = null, ?string $piso = null): array {',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'asignar_pasajero_a_reserva: matcheo por piso',
    'buscar' => [
        '    $nodo_asiento = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            $piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$piso) continue;',
        '            $cabeza = $piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            while ($actual && $actual->id() !== $cabeza->id()) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {',
        '                    $nodo_asiento = $actual;',
        '                    break 2;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '            }',
        '        }',
        '    }',
        '',
        '    if (!$nodo_asiento) return [\'exito\' => false, \'error\' => \'Asiento no encontrado\'];',
        '',
        '    $estado = $nodo_asiento->adyacente(\'estado\');',
        '    if (!$estado || $estado->dato() !== \'reservado\') {',
        '        return [\'exito\' => false, \'error\' => \'El asiento no está reservado\'];',
        '    }',
        '',
        '    if ($nodo_asiento->adyacente(\'pasajero\')) {',
        '        return [\'exito\' => false, \'error\' => \'El asiento ya tiene un pasajero asignado\'];',
        '    }',
    ],
    'reemplazar' => [
        '    $nodo_asiento = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            if ($piso !== null && $piso !== \'\' && (string)$i !== (string)$piso) continue;',
        '            $nodo_piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$nodo_piso) continue;',
        '            $cabeza = $nodo_piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            while ($actual && $actual->id() !== $cabeza->id()) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {',
        '                    $nodo_asiento = $actual;',
        '                    break 2;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '            }',
        '        }',
        '    }',
        '',
        '    if (!$nodo_asiento) return [\'exito\' => false, \'error\' => \'Asiento no encontrado\'];',
        '',
        '    $estado = $nodo_asiento->adyacente(\'estado\');',
        '    if (!$estado || $estado->dato() !== \'reservado\') {',
        '        return [\'exito\' => false, \'error\' => \'El asiento no está reservado\'];',
        '    }',
        '',
        '    if ($nodo_asiento->adyacente(\'pasajero\')) {',
        '        return [\'exito\' => false, \'error\' => \'El asiento ya tiene un pasajero asignado\'];',
        '    }',
    ],
],

// ============================================================
// 4. ViajeAsientos.php — liberar_reserva_asiento_micro
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'liberar_reserva_asiento_micro: piso opcional en firma',
    'buscar' => [
        'function liberar_reserva_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, ?Nodo $nodo_contexto = null): array {',
    ],
    'reemplazar' => [
        'function liberar_reserva_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, ?Nodo $nodo_contexto = null, ?string $piso = null): array {',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'liberar_reserva_asiento_micro: matcheo por piso',
    'buscar' => [
        '    $nodo_asiento = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            $piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$piso) continue;',
        '            $cabeza = $piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            while ($actual && $actual->id() !== $cabeza->id()) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {',
        '                    $nodo_asiento = $actual;',
        '                    break 2;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '            }',
        '        }',
        '    }',
        '',
        '    if (!$nodo_asiento) return [\'exito\' => false, \'error\' => \'Asiento no encontrado\'];',
        '',
        '    $estado = $nodo_asiento->adyacente(\'estado\');',
        '    if (!$estado || $estado->dato() !== \'reservado\') {',
        '        return [\'exito\' => false, \'error\' => \'El asiento no está reservado\'];',
        '    }',
        '',
        '    $estado->_dato(\'libre\');',
    ],
    'reemplazar' => [
        '    $nodo_asiento = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            if ($piso !== null && $piso !== \'\' && (string)$i !== (string)$piso) continue;',
        '            $nodo_piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$nodo_piso) continue;',
        '            $cabeza = $nodo_piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            while ($actual && $actual->id() !== $cabeza->id()) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {',
        '                    $nodo_asiento = $actual;',
        '                    break 2;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '            }',
        '        }',
        '    }',
        '',
        '    if (!$nodo_asiento) return [\'exito\' => false, \'error\' => \'Asiento no encontrado\'];',
        '',
        '    $estado = $nodo_asiento->adyacente(\'estado\');',
        '    if (!$estado || $estado->dato() !== \'reservado\') {',
        '        return [\'exito\' => false, \'error\' => \'El asiento no está reservado\'];',
        '    }',
        '',
        '    $estado->_dato(\'libre\');',
    ],
],

// ============================================================
// 5. ViajeAsientos.php — seleccionar_asiento_micro
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'seleccionar_asiento_micro: piso opcional en firma',
    'buscar' => [
        'function seleccionar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, string $nombre_terminal): array {',
    ],
    'reemplazar' => [
        'function seleccionar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, string $nombre_terminal, ?string $piso = null): array {',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'seleccionar_asiento_micro: matcheo por piso',
    'buscar' => [
        '    $nodo_asiento_encontrado = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            $piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$piso) continue;',
        '            $cabeza = $piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            while ($actual && $actual->id() !== $cabeza->id()) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {',
        '                    $nodo_asiento_encontrado = $actual;',
        '                    break 2;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '            }',
        '        }',
        '    }',
        '',
        '    if (!$nodo_asiento_encontrado) return [\'exito\' => false, \'error\' => \'Asiento no encontrado\'];',
    ],
    'reemplazar' => [
        '    $nodo_asiento_encontrado = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            if ($piso !== null && $piso !== \'\' && (string)$i !== (string)$piso) continue;',
        '            $nodo_piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$nodo_piso) continue;',
        '            $cabeza = $nodo_piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            while ($actual && $actual->id() !== $cabeza->id()) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {',
        '                    $nodo_asiento_encontrado = $actual;',
        '                    break 2;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '            }',
        '        }',
        '    }',
        '',
        '    if (!$nodo_asiento_encontrado) return [\'exito\' => false, \'error\' => \'Asiento no encontrado\'];',
    ],
],

// ============================================================
// 6. ViajeAsientos.php — deseleccionar_asiento_micro
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'deseleccionar_asiento_micro: piso opcional en firma',
    'buscar' => [
        'function deseleccionar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, string $nombre_terminal): array {',
    ],
    'reemplazar' => [
        'function deseleccionar_asiento_micro(string $nombre_viaje, string $nombre_micro, string $fila, string $columna, string $nombre_dueno, string $nombre_terminal, ?string $piso = null): array {',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'deseleccionar_asiento_micro: matcheo por piso',
    'buscar' => [
        '    $nodo_asiento = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            $piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$piso) continue;',
        '            $cabeza = $piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            while ($actual && $actual->id() !== $cabeza->id()) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {',
        '                    $nodo_asiento = $actual;',
        '                    break 2;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '            }',
        '        }',
        '    }',
        '',
        '    if (!$nodo_asiento) return [\'exito\' => false, \'error\' => \'Asiento no encontrado\'];',
        '',
        '    $estado = $nodo_asiento->adyacente(\'estado\');',
        '    if (!$estado || $estado->dato() !== \'seleccionado\') {',
        '        return [\'exito\' => false, \'error\' => \'El asiento no está seleccionado\'];',
        '    }',
    ],
    'reemplazar' => [
        '    $nodo_asiento = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            if ($piso !== null && $piso !== \'\' && (string)$i !== (string)$piso) continue;',
        '            $nodo_piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$nodo_piso) continue;',
        '            $cabeza = $nodo_piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            while ($actual && $actual->id() !== $cabeza->id()) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c && $f->dato() === $fila && $c->dato() === $columna) {',
        '                    $nodo_asiento = $actual;',
        '                    break 2;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '            }',
        '        }',
        '    }',
        '',
        '    if (!$nodo_asiento) return [\'exito\' => false, \'error\' => \'Asiento no encontrado\'];',
        '',
        '    $estado = $nodo_asiento->adyacente(\'estado\');',
        '    if (!$estado || $estado->dato() !== \'seleccionado\') {',
        '        return [\'exito\' => false, \'error\' => \'El asiento no está seleccionado\'];',
        '    }',
    ],
],

// ============================================================
// 7. ViajeAsientos.php — cambiar_asiento_pasaje: piso_vieja
//    y piso_nueva opcionales
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'cambiar_asiento_pasaje: firma con piso_vieja y piso_nueva',
    'buscar' => [
        '    bool $dejar_reservado_viejo = false,',
        '    ?Nodo $nodo_contexto = null',
        '): array {',
    ],
    'reemplazar' => [
        '    bool $dejar_reservado_viejo = false,',
        '    ?Nodo $nodo_contexto = null,',
        '    ?string $piso_vieja = null,',
        '    ?string $piso_nueva = null',
        '): array {',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/ViajeAsientos.php',
    'descripcion' => 'cambiar_asiento_pasaje: matcheo por piso en búsqueda',
    'buscar' => [
        '    $nodo_asiento_viejo = null;',
        '    $nodo_asiento_nuevo = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            $piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$piso) continue;',
        '            $cabeza = $piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            $seg = 0;',
        '            while ($actual && $actual->id() !== $cabeza->id() && $seg < 200) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c) {',
        '                    if ($f->dato() === $fila_vieja && $c->dato() === $columna_vieja) $nodo_asiento_viejo = $actual;',
        '                    if ($f->dato() === $fila_nueva && $c->dato() === $columna_nueva) $nodo_asiento_nuevo = $actual;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '                $seg++;',
        '            }',
        '        }',
        '    }',
    ],
    'reemplazar' => [
        '    $nodo_asiento_viejo = null;',
        '    $nodo_asiento_nuevo = null;',
        '    $nodo_asientos = $nodo_copia->adyacente(\'asientos\');',
        '    if ($nodo_asientos) {',
        '        for ($i = 1; $i <= 2; $i++) {',
        '            $piso_actual_num = (string)$i;',
        '            $nodo_piso = $nodo_asientos->adyacente("piso_$i");',
        '            if (!$nodo_piso) continue;',
        '            $cabeza = $nodo_piso->adyacente(\'asientos\');',
        '            if (!$cabeza) continue;',
        '            $actual = $cabeza->adyacente(\'primer\');',
        '            $seg = 0;',
        '            while ($actual && $actual->id() !== $cabeza->id() && $seg < 200) {',
        '                $f = $actual->adyacente(\'fila\');',
        '                $c = $actual->adyacente(\'columna\');',
        '                if ($f && $c) {',
        '                    $match_piso_viejo = ($piso_vieja === null || $piso_vieja === \'\' || $piso_actual_num === (string)$piso_vieja);',
        '                    $match_piso_nuevo = ($piso_nueva === null || $piso_nueva === \'\' || $piso_actual_num === (string)$piso_nueva);',
        '                    if ($match_piso_viejo && $f->dato() === $fila_vieja && $c->dato() === $columna_vieja) $nodo_asiento_viejo = $actual;',
        '                    if ($match_piso_nuevo && $f->dato() === $fila_nueva && $c->dato() === $columna_nueva) $nodo_asiento_nuevo = $actual;',
        '                }',
        '                $actual = $actual->adyacente(\'siguiente\');',
        '                $seg++;',
        '            }',
        '        }',
        '    }',
    ],
],

// ============================================================
// 8. Enrutador.php — 6 acciones de asiento pasan piso
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: seleccionar_asiento pasa piso',
    'buscar' => [
        '                    $resultado = seleccionar_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $nombre_terminal);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
    'reemplazar' => [
        '                    $piso = $post[\'piso\'] ?? null;',
        '                    $resultado = seleccionar_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $nombre_terminal, $piso);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: deseleccionar_asiento pasa piso',
    'buscar' => [
        '                    $resultado = deseleccionar_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $nombre_terminal);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
    'reemplazar' => [
        '                    $piso = $post[\'piso\'] ?? null;',
        '                    $resultado = deseleccionar_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $nombre_terminal, $piso);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: reservar_asiento pasa piso',
    'buscar' => [
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $resultado = reservar_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $datos_pasajero, $nodo_contexto);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
    'reemplazar' => [
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $piso = $post[\'piso\'] ?? null;',
        '                    $resultado = reservar_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $datos_pasajero, $nodo_contexto, $piso);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: asignar_pasajero_reserva pasa piso',
    'buscar' => [
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $resultado = asignar_pasajero_a_reserva($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $datos_pasajero, $nodo_contexto);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
    'reemplazar' => [
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $piso = $post[\'piso\'] ?? null;',
        '                    $resultado = asignar_pasajero_a_reserva($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $datos_pasajero, $nodo_contexto, $piso);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: liberar_reserva_asiento pasa piso',
    'buscar' => [
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $resultado = liberar_reserva_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $nodo_contexto);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
    'reemplazar' => [
        '                    $nodo_contexto = _contexto_solicitante($nombre_solicitante);',
        '                    $piso = $post[\'piso\'] ?? null;',
        '                    $resultado = liberar_reserva_asiento_micro($nombre_viaje, $nombre_micro, $fila, $columna, $nombre_dueno, $nodo_contexto, $piso);',
        '                    responder_json($resultado);',
        '                    break;',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Enrutador.php',
    'descripcion' => 'Enrutador: cambiar_asiento pasa piso_vieja y piso_nueva',
    'buscar' => [
        '                    $resultado = cambiar_asiento_pasaje(',
        '                        $nombre_viaje, $nombre_micro,',
        '                        $fila_vieja, $columna_vieja,',
        '                        $fila_nueva, $columna_nueva,',
        '                        $nombre_dueno, $nombre_solicitante,',
        '                        $dejar_reservado_viejo,',
        '                        $nodo_contexto',
        '                    );',
    ],
    'reemplazar' => [
        '                    $piso_vieja = $post[\'piso_vieja\'] ?? null;',
        '                    $piso_nueva = $post[\'piso_nueva\'] ?? null;',
        '                    $resultado = cambiar_asiento_pasaje(',
        '                        $nombre_viaje, $nombre_micro,',
        '                        $fila_vieja, $columna_vieja,',
        '                        $fila_nueva, $columna_nueva,',
        '                        $nombre_dueno, $nombre_solicitante,',
        '                        $dejar_reservado_viejo,',
        '                        $nodo_contexto,',
        '                        $piso_vieja,',
        '                        $piso_nueva',
        '                    );',
    ],
],

// ============================================================
// 9. viajes-asientos.js — helpers al principio
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'viajes-asientos: helpers de búsqueda por piso',
    'buscar' => [
        'function obtener_dueno_viaje_seleccionado() {',
        '    if (viaje_seleccionado && viaje_seleccionado.dueno) {',
        '        return viaje_seleccionado.dueno;',
        '    }',
        '    return obtener_nombre_dueno_actual();',
        '}',
    ],
    'reemplazar' => [
        'function obtener_dueno_viaje_seleccionado() {',
        '    if (viaje_seleccionado && viaje_seleccionado.dueno) {',
        '        return viaje_seleccionado.dueno;',
        '    }',
        '    return obtener_nombre_dueno_actual();',
        '}',
        '',
        '/**',
        ' * Busca un asiento en un array de estados por (piso, fila, columna).',
        ' * Si `piso` viene vacío o undefined, cae a (fila, columna) —',
        ' * comportamiento anterior (compatibilidad con flujos viejos).',
        ' *',
        ' * Fix v1.5piloto.82h: un micro con pisos distintos puede tener',
        ' * asientos con la misma (fila, columna) en pisos distintos. Sin',
        ' * piso, el .find() matchea el del primer piso.',
        ' */',
        'function _buscar_asiento_en_estados(estados, piso, fila, columna) {',
        '    if (piso !== null && piso !== undefined && piso !== \'\') {',
        '        return estados.find(e => String(e.piso) === String(piso) && e.fila === fila && e.columna === columna);',
        '    }',
        '    return estados.find(e => e.fila === fila && e.columna === columna);',
        '}',
    ],
],

// ============================================================
// 10. viajes-asientos.js — actualizar_colores_asientos
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'actualizar_colores_asientos: matcheo por piso',
    'buscar' => [
        'function actualizar_colores_asientos(estados) {',
        '    const asientosDOM = document.querySelectorAll(\'#croquis_pasaje_micro .seat\');',
        '    asientosDOM.forEach(seat => {',
        '        const fila = seat.dataset.fila;',
        '        const columna = seat.dataset.columna;',
        '        const estadoObj = estados.find(e => e.fila === fila && e.columna === columna);',
    ],
    'reemplazar' => [
        'function actualizar_colores_asientos(estados) {',
        '    const asientosDOM = document.querySelectorAll(\'#croquis_pasaje_micro .seat\');',
        '    asientosDOM.forEach(seat => {',
        '        const piso = seat.dataset.piso;',
        '        const fila = seat.dataset.fila;',
        '        const columna = seat.dataset.columna;',
        '        const estadoObj = _buscar_asiento_en_estados(estados, piso, fila, columna);',
    ],
],

// ============================================================
// 11. viajes-asientos.js — seleccionar_asiento_pasaje
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'seleccionar_asiento_pasaje: firma + piso al backend',
    'buscar' => [
        'async function seleccionar_asiento_pasaje(fila, columna) {',
        '    if (!micro_seleccionado || !viaje_seleccionado) return;',
        '    if (operacion_asiento_en_curso) return;',
        '',
        '    operacion_asiento_en_curso = true;',
        '',
        '    const nombre_dueno = obtener_dueno_viaje_seleccionado();',
        '    const nombre_terminal = usuario_actual.nombre_usuario;',
        '    const nombre_micro = micro_seleccionado;',
        '',
        '    detener_sync_asientos();',
        '',
        '    try {',
        '        const respuesta = await fetch("index.php", {',
        '            method: "POST",',
        '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
        '            body: new URLSearchParams({',
        '                accion: "viajes/seleccionar_asiento",',
        '                nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                nombre_micro: nombre_micro,',
        '                fila,',
        '                columna,',
        '                nombre_dueno,',
        '                nombre_terminal',
        '            })',
        '        });',
        '        const resultado = await respuesta.json();',
        '',
        '        if (resultado.exito) {',
        '            const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
        '            if (asiento) {',
        '                asiento.estado = \'seleccionado\';',
        '                asiento.seleccionado_por = nombre_terminal;',
        '            }',
        '            actualizar_colores_asientos(estados_asientos_actuales);',
        '            refrescar_info_asientos_propios(true);',
        '            mostrar_aviso("Asiento seleccionado", \'exito\');',
        '        } else {',
        '            const estado_actual = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
        '            if (estado_actual && estado_actual.estado === \'seleccionado\' && estado_actual.seleccionado_por === nombre_terminal) {',
        '                mostrar_aviso("Asiento seleccionado", \'exito\');',
        '            } else {',
        '                mostrar_aviso(resultado.error || "No se pudo seleccionar", \'error\');',
        '                await solicitar_estado_asientos();',
        '            }',
        '        }',
        '    } catch (error) {',
        '        console.error("Error en selección:", error);',
        '        mostrar_aviso("Error de comunicación", \'error\');',
        '        await solicitar_estado_asientos();',
        '    } finally {',
        '        operacion_asiento_en_curso = false;',
        '        if (viaje_seleccionado && micro_seleccionado) {',
        '            iniciar_sync_asientos();',
        '        }',
        '    }',
        '}',
    ],
    'reemplazar' => [
        'async function seleccionar_asiento_pasaje(piso, fila, columna) {',
        '    if (!micro_seleccionado || !viaje_seleccionado) return;',
        '    if (operacion_asiento_en_curso) return;',
        '',
        '    operacion_asiento_en_curso = true;',
        '',
        '    const nombre_dueno = obtener_dueno_viaje_seleccionado();',
        '    const nombre_terminal = usuario_actual.nombre_usuario;',
        '    const nombre_micro = micro_seleccionado;',
        '',
        '    detener_sync_asientos();',
        '',
        '    try {',
        '        const respuesta = await fetch("index.php", {',
        '            method: "POST",',
        '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
        '            body: new URLSearchParams({',
        '                accion: "viajes/seleccionar_asiento",',
        '                nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                nombre_micro: nombre_micro,',
        '                piso: (piso !== null && piso !== undefined) ? String(piso) : \'\',',
        '                fila,',
        '                columna,',
        '                nombre_dueno,',
        '                nombre_terminal',
        '            })',
        '        });',
        '        const resultado = await respuesta.json();',
        '',
        '        if (resultado.exito) {',
        '            const asiento = _buscar_asiento_en_estados(estados_asientos_actuales, piso, fila, columna);',
        '            if (asiento) {',
        '                asiento.estado = \'seleccionado\';',
        '                asiento.seleccionado_por = nombre_terminal;',
        '            }',
        '            actualizar_colores_asientos(estados_asientos_actuales);',
        '            refrescar_info_asientos_propios(true);',
        '            mostrar_aviso("Asiento seleccionado", \'exito\');',
        '        } else {',
        '            const estado_actual = _buscar_asiento_en_estados(estados_asientos_actuales, piso, fila, columna);',
        '            if (estado_actual && estado_actual.estado === \'seleccionado\' && estado_actual.seleccionado_por === nombre_terminal) {',
        '                mostrar_aviso("Asiento seleccionado", \'exito\');',
        '            } else {',
        '                mostrar_aviso(resultado.error || "No se pudo seleccionar", \'error\');',
        '                await solicitar_estado_asientos();',
        '            }',
        '        }',
        '    } catch (error) {',
        '        console.error("Error en selección:", error);',
        '        mostrar_aviso("Error de comunicación", \'error\');',
        '        await solicitar_estado_asientos();',
        '    } finally {',
        '        operacion_asiento_en_curso = false;',
        '        if (viaje_seleccionado && micro_seleccionado) {',
        '            iniciar_sync_asientos();',
        '        }',
        '    }',
        '}',
    ],
],

// ============================================================
// 12. viajes-asientos.js — deseleccionar_asiento_pasaje
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'deseleccionar_asiento_pasaje: firma + piso al backend',
    'buscar' => [
        'async function deseleccionar_asiento_pasaje(fila, columna) {',
        '    if (!micro_seleccionado || !viaje_seleccionado) return;',
        '    if (operacion_asiento_en_curso) return;',
        '    if (_venta_en_curso()) {',
        '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de liberar el asiento.\', \'error\');',
        '        return;',
        '    }',
        '',
        '    operacion_asiento_en_curso = true;',
        '',
        '    const nombre_dueno = obtener_dueno_viaje_seleccionado();',
        '    const nombre_terminal = usuario_actual.nombre_usuario;',
        '    const nombre_micro = micro_seleccionado;',
        '',
        '    detener_sync_asientos();',
        '',
        '    try {',
        '        const respuesta = await fetch("index.php", {',
        '            method: "POST",',
        '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
        '            body: new URLSearchParams({',
        '                accion: "viajes/deseleccionar_asiento",',
        '                nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                nombre_micro: nombre_micro,',
        '                fila,',
        '                columna,',
        '                nombre_dueno,',
        '                nombre_terminal',
        '            })',
        '        });',
        '        const resultado = await respuesta.json();',
        '',
        '        if (resultado.exito) {',
        '            const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
        '            if (asiento) {',
        '                asiento.estado = \'libre\';',
        '                asiento.seleccionado_por = null;',
        '            }',
    ],
    'reemplazar' => [
        'async function deseleccionar_asiento_pasaje(piso, fila, columna) {',
        '    if (!micro_seleccionado || !viaje_seleccionado) return;',
        '    if (operacion_asiento_en_curso) return;',
        '    if (_venta_en_curso()) {',
        '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de liberar el asiento.\', \'error\');',
        '        return;',
        '    }',
        '',
        '    operacion_asiento_en_curso = true;',
        '',
        '    const nombre_dueno = obtener_dueno_viaje_seleccionado();',
        '    const nombre_terminal = usuario_actual.nombre_usuario;',
        '    const nombre_micro = micro_seleccionado;',
        '',
        '    detener_sync_asientos();',
        '',
        '    try {',
        '        const respuesta = await fetch("index.php", {',
        '            method: "POST",',
        '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
        '            body: new URLSearchParams({',
        '                accion: "viajes/deseleccionar_asiento",',
        '                nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                nombre_micro: nombre_micro,',
        '                piso: (piso !== null && piso !== undefined) ? String(piso) : \'\',',
        '                fila,',
        '                columna,',
        '                nombre_dueno,',
        '                nombre_terminal',
        '            })',
        '        });',
        '        const resultado = await respuesta.json();',
        '',
        '        if (resultado.exito) {',
        '            const asiento = _buscar_asiento_en_estados(estados_asientos_actuales, piso, fila, columna);',
        '            if (asiento) {',
        '                asiento.estado = \'libre\';',
        '                asiento.seleccionado_por = null;',
        '            }',
    ],
],

// ============================================================
// 13. viajes-asientos.js — deseleccionar_todos_los_propios:
//     pasar piso al backend
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'deseleccionar_todos: piso al backend',
    'buscar' => [
        '                    body: new URLSearchParams({',
        '                        accion: "viajes/deseleccionar_asiento",',
        '                        nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                        nombre_micro: nombre_micro,',
        '                        fila: asiento.fila,',
        '                        columna: asiento.columna,',
        '                        nombre_dueno: nombre_dueno,',
        '                        nombre_terminal: usuario_actual.nombre_usuario',
        '                    })',
    ],
    'reemplazar' => [
        '                    body: new URLSearchParams({',
        '                        accion: "viajes/deseleccionar_asiento",',
        '                        nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                        nombre_micro: nombre_micro,',
        '                        piso: asiento.piso || \'\',',
        '                        fila: asiento.fila,',
        '                        columna: asiento.columna,',
        '                        nombre_dueno: nombre_dueno,',
        '                        nombre_terminal: usuario_actual.nombre_usuario',
        '                    })',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'deseleccionar_todos: piso al backend (liberar reserva)',
    'buscar' => [
        '                    body: new URLSearchParams({',
        '                        accion: "viajes/liberar_reserva_asiento",',
        '                        nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                        nombre_micro: nombre_micro,',
        '                        fila: asiento.fila,',
        '                        columna: asiento.columna,',
        '                        nombre_dueno: nombre_dueno',
        '                    })',
    ],
    'reemplazar' => [
        '                    body: new URLSearchParams({',
        '                        accion: "viajes/liberar_reserva_asiento",',
        '                        nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                        nombre_micro: nombre_micro,',
        '                        piso: asiento.piso || \'\',',
        '                        fila: asiento.fila,',
        '                        columna: asiento.columna,',
        '                        nombre_dueno: nombre_dueno',
        '                    })',
    ],
],

// ============================================================
// 14. viajes-asientos.js — construir_html_tarjeta_asiento:
//     agregar data-piso a los botones
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'construir_html_tarjeta_asiento: data-piso en botones',
    'buscar' => [
        '    if (es_dueno_o_admin && asiento.estado === \'libre\') {',
        '        botones.push(`<button class="btn primary btn-reservar-asiento"${bloqueo_venta} data-fila="${asiento.fila}" data-columna="${asiento.columna}">Reservar para equipo</button>`);',
        '    }',
        '',
        '    if (es_terminal && es_propio) {',
        '        botones.push(`<button class="btn danger btn-liberar-seleccion"${bloqueo_venta} data-fila="${asiento.fila}" data-columna="${asiento.columna}">Liberar</button>`);',
        '    }',
        '',
        '    if (es_dueno_o_admin && asiento.estado === \'reservado\' && !asiento.tiene_pasajero) {',
        '        botones.push(`<button class="btn primary btn-asignar-pasajero"${bloqueo_venta} data-fila="${asiento.fila}" data-columna="${asiento.columna}">Asignar pasajero</button>`);',
        '    }',
        '',
        '    if (es_dueno_o_admin && asiento.estado === \'reservado\') {',
        '        botones.push(`<button class="btn danger btn-liberar-reserva"${bloqueo_venta} data-fila="${asiento.fila}" data-columna="${asiento.columna}">Liberar reserva</button>`);',
        '    }',
        '',
        '    // Ver pasaje: disponible en reservado con pasajero o vendido',
        '    if ((asiento.estado === \'reservado\' && asiento.tiene_pasajero) || asiento.estado === \'vendido\') {',
        '        botones.push(`<button class="btn btn-ver-pasaje-asiento" data-fila="${asiento.fila}" data-columna="${asiento.columna}">Ver pasaje</button>`);',
        '    }',
    ],
    'reemplazar' => [
        '    const piso_attr = ` data-piso="${asiento.piso || \'\'}"`;',
        '    if (es_dueno_o_admin && asiento.estado === \'libre\') {',
        '        botones.push(`<button class="btn primary btn-reservar-asiento"${bloqueo_venta} data-fila="${asiento.fila}" data-columna="${asiento.columna}"${piso_attr}>Reservar para equipo</button>`);',
        '    }',
        '',
        '    if (es_terminal && es_propio) {',
        '        botones.push(`<button class="btn danger btn-liberar-seleccion"${bloqueo_venta} data-fila="${asiento.fila}" data-columna="${asiento.columna}"${piso_attr}>Liberar</button>`);',
        '    }',
        '',
        '    if (es_dueno_o_admin && asiento.estado === \'reservado\' && !asiento.tiene_pasajero) {',
        '        botones.push(`<button class="btn primary btn-asignar-pasajero"${bloqueo_venta} data-fila="${asiento.fila}" data-columna="${asiento.columna}"${piso_attr}>Asignar pasajero</button>`);',
        '    }',
        '',
        '    if (es_dueno_o_admin && asiento.estado === \'reservado\') {',
        '        botones.push(`<button class="btn danger btn-liberar-reserva"${bloqueo_venta} data-fila="${asiento.fila}" data-columna="${asiento.columna}"${piso_attr}>Liberar reserva</button>`);',
        '    }',
        '',
        '    // Ver pasaje: disponible en reservado con pasajero o vendido',
        '    if ((asiento.estado === \'reservado\' && asiento.tiene_pasajero) || asiento.estado === \'vendido\') {',
        '        botones.push(`<button class="btn btn-ver-pasaje-asiento" data-fila="${asiento.fila}" data-columna="${asiento.columna}"${piso_attr}>Ver pasaje</button>`);',
        '    }',
    ],
],

// ============================================================
// 15. viajes-asientos.js — renderizar_tarjetas_asientos:
//     pasar piso a los handlers
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'renderizar_tarjetas_asientos: pasar piso a handlers',
    'buscar' => [
        '    panel.querySelectorAll(\'.btn-reservar-asiento\').forEach(btn => {',
        '        btn.addEventListener(\'click\', () => abrir_modal_reservar_asiento(btn.dataset.fila, btn.dataset.columna));',
        '    });',
        '    panel.querySelectorAll(\'.btn-liberar-seleccion\').forEach(btn => {',
        '        btn.addEventListener(\'click\', () => deseleccionar_asiento_pasaje(btn.dataset.fila, btn.dataset.columna));',
        '    });',
        '    panel.querySelectorAll(\'.btn-asignar-pasajero\').forEach(btn => {',
        '        btn.addEventListener(\'click\', () => abrir_modal_asignar_pasajero(btn.dataset.fila, btn.dataset.columna));',
        '    });',
        '    panel.querySelectorAll(\'.btn-liberar-reserva\').forEach(btn => {',
        '        btn.addEventListener(\'click\', () => liberar_reserva_equipo(btn.dataset.fila, btn.dataset.columna));',
        '    });',
        '    panel.querySelectorAll(\'.btn-ver-pasaje-asiento\').forEach(btn => {',
        '        btn.addEventListener(\'click\', () => ver_pasaje_asiento(btn.dataset.fila, btn.dataset.columna));',
        '    });',
    ],
    'reemplazar' => [
        '    panel.querySelectorAll(\'.btn-reservar-asiento\').forEach(btn => {',
        '        btn.addEventListener(\'click\', () => abrir_modal_reservar_asiento(btn.dataset.piso, btn.dataset.fila, btn.dataset.columna));',
        '    });',
        '    panel.querySelectorAll(\'.btn-liberar-seleccion\').forEach(btn => {',
        '        btn.addEventListener(\'click\', () => deseleccionar_asiento_pasaje(btn.dataset.piso, btn.dataset.fila, btn.dataset.columna));',
        '    });',
        '    panel.querySelectorAll(\'.btn-asignar-pasajero\').forEach(btn => {',
        '        btn.addEventListener(\'click\', () => abrir_modal_asignar_pasajero(btn.dataset.piso, btn.dataset.fila, btn.dataset.columna));',
        '    });',
        '    panel.querySelectorAll(\'.btn-liberar-reserva\').forEach(btn => {',
        '        btn.addEventListener(\'click\', () => liberar_reserva_equipo(btn.dataset.piso, btn.dataset.fila, btn.dataset.columna));',
        '    });',
        '    panel.querySelectorAll(\'.btn-ver-pasaje-asiento\').forEach(btn => {',
        '        btn.addEventListener(\'click\', () => ver_pasaje_asiento(btn.dataset.piso, btn.dataset.fila, btn.dataset.columna));',
        '    });',
    ],
],

// ============================================================
// 16. viajes-asientos.js — abrir_modal_reservar_asiento
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'abrir_modal_reservar_asiento: firma + piso',
    'buscar' => [
        'function abrir_modal_reservar_asiento(fila, columna) {',
        '    if (_venta_en_curso()) {',
        '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de reservar un asiento.\', \'error\');',
        '        return;',
        '    }',
        '    const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
        '    if (!asiento) {',
        '        mostrar_aviso("Asiento no encontrado", \'error\');',
        '        return;',
        '    }',
        '    if (asiento.estado !== \'libre\') {',
        '        mostrar_aviso("El asiento ya no está libre", \'error\');',
        '        actualizar_detalle_viaje_actual();',
        '        return;',
        '    }',
    ],
    'reemplazar' => [
        'function abrir_modal_reservar_asiento(piso, fila, columna) {',
        '    if (_venta_en_curso()) {',
        '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de reservar un asiento.\', \'error\');',
        '        return;',
        '    }',
        '    const asiento = _buscar_asiento_en_estados(estados_asientos_actuales, piso, fila, columna);',
        '    if (!asiento) {',
        '        mostrar_aviso("Asiento no encontrado", \'error\');',
        '        return;',
        '    }',
        '    if (asiento.estado !== \'libre\') {',
        '        mostrar_aviso("El asiento ya no está libre", \'error\');',
        '        actualizar_detalle_viaje_actual();',
        '        return;',
        '    }',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'abrir_modal_reservar_asiento: POST con piso',
    'buscar' => [
        '        const params = {',
        '            accion: "viajes/reservar_asiento",',
        '            nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '            nombre_micro: micro_seleccionado,',
        '            fila,',
        '            columna,',
        '            nombre_dueno',
        '        };',
    ],
    'reemplazar' => [
        '        const params = {',
        '            accion: "viajes/reservar_asiento",',
        '            nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '            nombre_micro: micro_seleccionado,',
        '            piso: (piso !== null && piso !== undefined) ? String(piso) : \'\',',
        '            fila,',
        '            columna,',
        '            nombre_dueno',
        '        };',
    ],
],

// ============================================================
// 17. viajes-asientos.js — abrir_modal_asignar_pasajero
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'abrir_modal_asignar_pasajero: firma + piso',
    'buscar' => [
        'function abrir_modal_asignar_pasajero(fila, columna) {',
        '    if (_venta_en_curso()) {',
        '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de asignar un pasajero.\', \'error\');',
        '        return;',
        '    }',
        '    const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
        '    if (!asiento) {',
        '        mostrar_aviso("Asiento no encontrado", \'error\');',
        '        return;',
        '    }',
        '    if (asiento.estado !== \'reservado\') {',
        '        mostrar_aviso("El asiento ya no está reservado", \'error\');',
        '        actualizar_detalle_viaje_actual();',
        '        return;',
        '    }',
        '    if (asiento.tiene_pasajero) {',
        '        mostrar_aviso("El asiento ya tiene un pasajero asignado", \'error\');',
        '        return;',
        '    }',
    ],
    'reemplazar' => [
        'function abrir_modal_asignar_pasajero(piso, fila, columna) {',
        '    if (_venta_en_curso()) {',
        '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de asignar un pasajero.\', \'error\');',
        '        return;',
        '    }',
        '    const asiento = _buscar_asiento_en_estados(estados_asientos_actuales, piso, fila, columna);',
        '    if (!asiento) {',
        '        mostrar_aviso("Asiento no encontrado", \'error\');',
        '        return;',
        '    }',
        '    if (asiento.estado !== \'reservado\') {',
        '        mostrar_aviso("El asiento ya no está reservado", \'error\');',
        '        actualizar_detalle_viaje_actual();',
        '        return;',
        '    }',
        '    if (asiento.tiene_pasajero) {',
        '        mostrar_aviso("El asiento ya tiene un pasajero asignado", \'error\');',
        '        return;',
        '    }',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'abrir_modal_asignar_pasajero: POST con piso',
    'buscar' => [
        '                accion: "viajes/asignar_pasajero_reserva",',
        '                nombre_viaje: nombre_viaje_asig,',
        '                nombre_micro: nombre_micro_asig,',
        '                fila,',
        '                columna,',
        '                nombre_dueno,',
        '                datos_pasajero: JSON.stringify(r.datos)',
    ],
    'reemplazar' => [
        '                accion: "viajes/asignar_pasajero_reserva",',
        '                nombre_viaje: nombre_viaje_asig,',
        '                nombre_micro: nombre_micro_asig,',
        '                piso: (piso !== null && piso !== undefined) ? String(piso) : \'\',',
        '                fila,',
        '                columna,',
        '                nombre_dueno,',
        '                datos_pasajero: JSON.stringify(r.datos)',
    ],
],

// ============================================================
// 18. viajes-asientos.js — ver_pasaje_asiento
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'ver_pasaje_asiento: firma + piso',
    'buscar' => [
        'function ver_pasaje_asiento(fila, columna) {',
        '    const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
        '    if (!asiento) {',
        '        mostrar_aviso("Asiento no encontrado", \'error\');',
        '        return;',
        '    }',
        '    if (!asiento.tiene_pasajero || !asiento.pasajero) {',
        '        mostrar_aviso("Este asiento no tiene pasajero", \'error\');',
        '        return;',
        '    }',
    ],
    'reemplazar' => [
        'function ver_pasaje_asiento(piso, fila, columna) {',
        '    const asiento = _buscar_asiento_en_estados(estados_asientos_actuales, piso, fila, columna);',
        '    if (!asiento) {',
        '        mostrar_aviso("Asiento no encontrado", \'error\');',
        '        return;',
        '    }',
        '    if (!asiento.tiene_pasajero || !asiento.pasajero) {',
        '        mostrar_aviso("Este asiento no tiene pasajero", \'error\');',
        '        return;',
        '    }',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'ver_pasaje_asiento: pasar piso a cambiar_asiento',
    'buscar' => [
        '        btnCambiar.addEventListener(\'click\', () => {',
        '            abrir_modal_cambiar_asiento(nd, nv, nm, fila, columna);',
        '        });',
    ],
    'reemplazar' => [
        '        btnCambiar.addEventListener(\'click\', () => {',
        '            abrir_modal_cambiar_asiento(nd, nv, nm, fila, columna, null, piso);',
        '        });',
    ],
],

// ============================================================
// 19. viajes-asientos.js — liberar_reserva_equipo
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'liberar_reserva_equipo: firma + piso',
    'buscar' => [
        'async function liberar_reserva_equipo(fila, columna) {',
        '    if (!micro_seleccionado || !viaje_seleccionado) return;',
        '    if (operacion_asiento_en_curso) return;',
        '    if (_venta_en_curso()) {',
        '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de liberar la reserva.\', \'error\');',
        '        return;',
        '    }',
        '',
        '    const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
        '    if (!asiento) {',
        '        mostrar_aviso("Asiento no encontrado", \'error\');',
        '        return;',
        '    }',
    ],
    'reemplazar' => [
        'async function liberar_reserva_equipo(piso, fila, columna) {',
        '    if (!micro_seleccionado || !viaje_seleccionado) return;',
        '    if (operacion_asiento_en_curso) return;',
        '    if (_venta_en_curso()) {',
        '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de liberar la reserva.\', \'error\');',
        '        return;',
        '    }',
        '',
        '    const asiento = _buscar_asiento_en_estados(estados_asientos_actuales, piso, fila, columna);',
        '    if (!asiento) {',
        '        mostrar_aviso("Asiento no encontrado", \'error\');',
        '        return;',
        '    }',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'liberar_reserva_equipo: POST con piso',
    'buscar' => [
        '                accion: "viajes/liberar_reserva_asiento",',
        '                nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                nombre_micro: nombre_micro,',
        '                fila,',
        '                columna,',
        '                nombre_dueno',
        '            })',
        '        });',
        '        const resultado = await respuesta.json();',
        '        if (resultado.exito) {',
        '            mostrar_aviso("Reserva liberada correctamente", \'exito\');',
        '            const asientoLocal = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
    ],
    'reemplazar' => [
        '                accion: "viajes/liberar_reserva_asiento",',
        '                nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                nombre_micro: nombre_micro,',
        '                piso: (piso !== null && piso !== undefined) ? String(piso) : \'\',',
        '                fila,',
        '                columna,',
        '                nombre_dueno',
        '            })',
        '        });',
        '        const resultado = await respuesta.json();',
        '        if (resultado.exito) {',
        '            mostrar_aviso("Reserva liberada correctamente", \'exito\');',
        '            const asientoLocal = _buscar_asiento_en_estados(estados_asientos_actuales, piso, fila, columna);',
    ],
],

// ============================================================
// 20. viajes-asientos.js — reservar_asiento_equipo
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'reservar_asiento_equipo: firma + piso',
    'buscar' => [
        'async function reservar_asiento_equipo(fila, columna) {',
        '    if (!micro_seleccionado || !viaje_seleccionado) return;',
        '    if (operacion_asiento_en_curso) return;',
        '    operacion_asiento_en_curso = true;',
        '    const nombre_dueno = obtener_nombre_dueno_actual();',
        '    const nombre_micro = micro_seleccionado;',
        '    detener_sync_asientos();',
        '',
        '    try {',
        '        const respuesta = await fetch("index.php", {',
        '            method: "POST",',
        '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
        '            body: new URLSearchParams({',
        '                accion: "viajes/reservar_asiento",',
        '                nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                nombre_micro: nombre_micro,',
        '                fila,',
        '                columna,',
        '                nombre_dueno',
        '            })',
        '        });',
        '        const resultado = await respuesta.json();',
        '        if (resultado.exito) {',
        '            mostrar_aviso("Asiento reservado para el equipo", \'exito\');',
        '            const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
    ],
    'reemplazar' => [
        'async function reservar_asiento_equipo(piso, fila, columna) {',
        '    if (!micro_seleccionado || !viaje_seleccionado) return;',
        '    if (operacion_asiento_en_curso) return;',
        '    operacion_asiento_en_curso = true;',
        '    const nombre_dueno = obtener_nombre_dueno_actual();',
        '    const nombre_micro = micro_seleccionado;',
        '    detener_sync_asientos();',
        '',
        '    try {',
        '        const respuesta = await fetch("index.php", {',
        '            method: "POST",',
        '            headers: { "Content-Type": "application/x-www-form-urlencoded" },',
        '            body: new URLSearchParams({',
        '                accion: "viajes/reservar_asiento",',
        '                nombre_viaje: viaje_seleccionado.nombre_viaje,',
        '                nombre_micro: nombre_micro,',
        '                piso: (piso !== null && piso !== undefined) ? String(piso) : \'\',',
        '                fila,',
        '                columna,',
        '                nombre_dueno',
        '            })',
        '        });',
        '        const resultado = await respuesta.json();',
        '        if (resultado.exito) {',
        '            mostrar_aviso("Asiento reservado para el equipo", \'exito\');',
        '            const asiento = _buscar_asiento_en_estados(estados_asientos_actuales, piso, fila, columna);',
    ],
],

// ============================================================
// 21. viajes-asientos.js — renderizar_pasaje_micro:
//     data-piso en seat + onclick con piso
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'renderizar_pasaje_micro: data-piso en seat',
    'buscar' => [
        '                        seat.dataset.fila = asiento.fila;',
        '                        seat.dataset.columna = asiento.columna;',
        '                        seat.dataset.numero = asiento.numero;',
        '                        seat.dataset.estado = asiento.estado;',
        '                        seat.dataset.reservado_por = asiento.reservado_por || \'\';',
    ],
    'reemplazar' => [
        '                        seat.dataset.piso = String(index + 1);',
        '                        seat.dataset.fila = asiento.fila;',
        '                        seat.dataset.columna = asiento.columna;',
        '                        seat.dataset.numero = asiento.numero;',
        '                        seat.dataset.estado = asiento.estado;',
        '                        seat.dataset.reservado_por = asiento.reservado_por || \'\';',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'renderizar_pasaje_micro: onclick con piso',
    'buscar' => [
        '            const seat = event.target.closest(\'.seat\');',
        '            if (!seat) return;',
        '            const fila = seat.dataset.fila;',
        '            const columna = seat.dataset.columna;',
        '            const asiento = estados_asientos_actuales.find(e => e.fila === fila && e.columna === columna);',
        '            if (!asiento) return;',
        '',
        '            const es_terminal = usuario_actual.nivel === \'terminal\';',
        '',
        '            if (es_terminal) {',
        '                const es_propio_clic = es_asiento_propio(asiento);',
        '                const es_libre_clic = asiento.estado === \'libre\';',
        '                const propios = obtener_asientos_propios();',
        '                if (!es_propio_clic && !es_libre_clic && propios.length > 0) {',
        '                    await deseleccionar_todos_los_propios();',
        '                }',
        '            }',
        '',
        '            mostrar_info_asiento(asiento);',
        '',
        '            if (es_terminal) {',
        '                if (asiento.estado === \'reservado\') {',
        '                    mostrar_aviso("Asiento reservado para el equipo", \'info\');',
        '                    return;',
        '                }',
        '                if (asiento.estado === \'libre\') {',
        '                    seleccionar_asiento_pasaje(fila, columna);',
        '                } else if (asiento.estado === \'seleccionado\' && asiento.seleccionado_por === usuario_actual.nombre_usuario) {',
        '                    deseleccionar_asiento_pasaje(fila, columna);',
        '                }',
        '            }',
    ],
    'reemplazar' => [
        '            const seat = event.target.closest(\'.seat\');',
        '            if (!seat) return;',
        '            const piso = seat.dataset.piso;',
        '            const fila = seat.dataset.fila;',
        '            const columna = seat.dataset.columna;',
        '            const asiento = _buscar_asiento_en_estados(estados_asientos_actuales, piso, fila, columna);',
        '            if (!asiento) return;',
        '',
        '            const es_terminal = usuario_actual.nivel === \'terminal\';',
        '',
        '            if (es_terminal) {',
        '                const es_propio_clic = es_asiento_propio(asiento);',
        '                const es_libre_clic = asiento.estado === \'libre\';',
        '                const propios = obtener_asientos_propios();',
        '                if (!es_propio_clic && !es_libre_clic && propios.length > 0) {',
        '                    await deseleccionar_todos_los_propios();',
        '                }',
        '            }',
        '',
        '            mostrar_info_asiento(asiento);',
        '',
        '            if (es_terminal) {',
        '                if (asiento.estado === \'reservado\') {',
        '                    mostrar_aviso("Asiento reservado para el equipo", \'info\');',
        '                    return;',
        '                }',
        '                if (asiento.estado === \'libre\') {',
        '                    seleccionar_asiento_pasaje(piso, fila, columna);',
        '                } else if (asiento.estado === \'seleccionado\' && asiento.seleccionado_por === usuario_actual.nombre_usuario) {',
        '                    deseleccionar_asiento_pasaje(piso, fila, columna);',
        '                }',
        '            }',
    ],
],

// ============================================================
// 22. viajes-asientos.js — abrir_modal_cambiar_asiento
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'abrir_modal_cambiar_asiento: firma + piso_origen',
    'buscar' => [
        'async function abrir_modal_cambiar_asiento(nombre_dueno, nombre_viaje, nombre_micro, fila_origen, columna_origen, on_exito) {',
        '    if (_venta_en_curso()) {',
        '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de cambiar un asiento.\', \'error\');',
        '        return;',
        '    }',
    ],
    'reemplazar' => [
        'async function abrir_modal_cambiar_asiento(nombre_dueno, nombre_viaje, nombre_micro, fila_origen, columna_origen, on_exito, piso_origen = null) {',
        '    if (_venta_en_curso()) {',
        '        mostrar_aviso(\'Hay una venta en curso. Termínala o cancelala antes de cambiar un asiento.\', \'error\');',
        '        return;',
        '    }',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'abrir_modal_cambiar_asiento: buscar origen por piso',
    'buscar' => [
        '    const origen = estados.find(a => String(a.fila) === String(fila_origen) && String(a.columna) === String(columna_origen));',
        '    if (!origen) {',
        '        mostrar_aviso(\'No se encontró el asiento actual\', \'error\');',
        '        return;',
        '    }',
    ],
    'reemplazar' => [
        '    const origen = _buscar_asiento_en_estados(estados, piso_origen, String(fila_origen), String(columna_origen));',
        '    if (!origen) {',
        '        mostrar_aviso(\'No se encontró el asiento actual\', \'error\');',
        '        return;',
        '    }',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'abrir_modal_cambiar_asiento: croquis con piso',
    'buscar' => [
        '                        const a_estado = estados.find(e => String(e.fila) === String(a.fila) && String(e.columna) === String(a.columna));',
        '                        const a_estado_str = a_estado ? a_estado.estado : \'libre\';',
        '                        const a_tiene_pas = a_estado ? (a_estado.tiene_pasajero === true) : false;',
        '                        const es_origen = (String(a.fila) === String(fila_origen) && String(a.columna) === String(columna_origen));',
        '                        const valido = !es_origen && _es_destino_valido({ estado: a_estado_str, tiene_pasajero: a_tiene_pas }, origen.estado, es_terminal);',
        '                        const clases = [\'seat\', `seat-${a_estado_str}`];',
        '                        if (es_origen) clases.push(\'seat-origen\');',
        '                        if (!valido && !es_origen) clases.push(\'seat-deshabilitado\');',
        '                        if (valido) clases.push(\'seat-elegible\');',
        '                        croquis_html += `<div class="${clases.join(\' \')}" data-fila="${a.fila}" data-columna="${a.columna}" data-numero="${a.numero}" data-valido="${valido ? \'1\' : \'0\'}" data-origen="${es_origen ? \'1\' : \'0\'}">${String(a.numero).padStart(2, \'0\')}</div>`;',
    ],
    'reemplazar' => [
        '                        const piso_num = String(index + 1);',
        '                        const a_estado = _buscar_asiento_en_estados(estados, piso_num, String(a.fila), String(a.columna));',
        '                        const a_estado_str = a_estado ? a_estado.estado : \'libre\';',
        '                        const a_tiene_pas = a_estado ? (a_estado.tiene_pasajero === true) : false;',
        '                        const es_origen = (String(a.fila) === String(fila_origen) && String(a.columna) === String(columna_origen) && (piso_origen === null || piso_origen === undefined || piso_origen === \'\' || String(piso_origen) === piso_num));',
        '                        const valido = !es_origen && _es_destino_valido({ estado: a_estado_str, tiene_pasajero: a_tiene_pas }, origen.estado, es_terminal);',
        '                        const clases = [\'seat\', `seat-${a_estado_str}`];',
        '                        if (es_origen) clases.push(\'seat-origen\');',
        '                        if (!valido && !es_origen) clases.push(\'seat-deshabilitado\');',
        '                        if (valido) clases.push(\'seat-elegible\');',
        '                        croquis_html += `<div class="${clases.join(\' \')}" data-piso="${piso_num}" data-fila="${a.fila}" data-columna="${a.columna}" data-numero="${a.numero}" data-valido="${valido ? \'1\' : \'0\'}" data-origen="${es_origen ? \'1\' : \'0\'}">${String(a.numero).padStart(2, \'0\')}</div>`;',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'abrir_modal_cambiar_asiento: guardar piso en estado',
    'buscar' => [
        '        origen_fila: String(fila_origen),',
        '        origen_columna: String(columna_origen),',
        '        origen_numero: origen.numero,',
        '        origen_estado: origen.estado,',
    ],
    'reemplazar' => [
        '        origen_piso: (piso_origen !== null && piso_origen !== undefined) ? String(piso_origen) : \'\',',
        '        origen_fila: String(fila_origen),',
        '        origen_columna: String(columna_origen),',
        '        origen_numero: origen.numero,',
        '        origen_estado: origen.estado,',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => 'abrir_modal_cambiar_asiento: click guarda piso destino',
    'buscar' => [
        '            if (cambiar_asiento_datos_modal) {',
        '                cambiar_asiento_datos_modal.destino = {',
        '                    fila: seat.dataset.fila,',
        '                    columna: seat.dataset.columna,',
        '                    numero: seat.dataset.numero',
        '                };',
        '            }',
    ],
    'reemplazar' => [
        '            if (cambiar_asiento_datos_modal) {',
        '                cambiar_asiento_datos_modal.destino = {',
        '                    piso: seat.dataset.piso || \'\',',
        '                    fila: seat.dataset.fila,',
        '                    columna: seat.dataset.columna,',
        '                    numero: seat.dataset.numero',
        '                };',
        '            }',
    ],
],

// ============================================================
// 23. viajes-asientos.js — _confirmar_cambiar_asiento:
//     mandar piso_vieja y piso_nueva
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'Aplicacion/Viajes/viajes-asientos.js',
    'descripcion' => '_confirmar_cambiar_asiento: POST con piso_vieja y piso_nueva',
    'buscar' => [
        '                nombre_viaje: d.viaje,',
        '                nombre_micro: d.micro,',
        '                fila_vieja: d.origen_fila,',
        '                columna_vieja: d.origen_columna,',
        '                fila_nueva: d.destino.fila,',
        '                columna_nueva: d.destino.columna,',
        '                nombre_dueno: d.dueno,',
    ],
    'reemplazar' => [
        '                nombre_viaje: d.viaje,',
        '                nombre_micro: d.micro,',
        '                piso_vieja: d.origen_piso || \'\',',
        '                piso_nueva: d.destino.piso || \'\',',
        '                fila_vieja: d.origen_fila,',
        '                columna_vieja: d.origen_columna,',
        '                fila_nueva: d.destino.fila,',
        '                columna_nueva: d.destino.columna,',
        '                nombre_dueno: d.dueno,',
    ],
],

// ============================================================
// 24. plan_actual.md — registrar el fix
// ============================================================
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: fix del croquis multi-piso',
    'buscar' => [
        '- **Migración de pasajeros entre micros — Tanda 1',
        '  (v1.5piloto.82e).** Nueva función',
    ],
    'reemplazar' => [
        '- **Fix del croquis multi-piso (v1.5piloto.82h).** El croquis',
        '  y todas las acciones de asiento matcheaban por',
        '  `(fila, columna)` sin `piso`. En un micro con pisos que',
        '  comparten filas/columnas (ej. piso 1 filas 1-3, piso 2',
        '  filas 1-12), los asientos del piso 1 pisaban visualmente a',
        '  los del piso 2 en las mismas coordenadas. Fix: `piso` en',
        '  `obtener_estados_asientos_micro` y en todos los matcheos',
        '  del frontend + backend. Las 5 acciones de asiento y',
        '  `cambiar_asiento_pasaje` aceptan `?string $piso = null`',
        '  (o `piso_vieja`/`piso_nueva`) y matchean por',
        '  `(piso, fila, columna)` si viene. **Pendiente:** el flujo',
        '  de "cambiar de asiento desde la pestaña Clientes"',
        '  (`pasajeros.js`) no pasa `piso`; queda con el bug hasta',
        '  que se adapte.',
        '- **Migración de pasajeros entre micros — Tanda 1',
        '  (v1.5piloto.82e).** Nueva función',
    ],
],
[
    'tipo' => 'reemplazar',
    'archivo' => 'prompts/plan_actual.md',
    'descripcion' => 'plan_actual: versión del cierre',
    'buscar' => [
        '**Última actualización:** v1.5piloto.82f (Tanda 2 del arco de',
        'migración de pasajeros entre micros: frontend. Ver §2 y §4.5).',
    ],
    'reemplazar' => [
        '**Última actualización:** v1.5piloto.82h (fix del croquis',
        'multi-piso + selección por piso. Ver §2 y §4.5).',
    ],
],

];

// ============================================================
// Runner v2 (textual, ver prompt_sistema_scripts_v2.md §14)
// ============================================================

echo "=== Aplicador ===\n\n";

function detectar_eol(string $c): string { return (strpos($c, "\r\n") !== false) ? "\r\n" : "\n"; }
function normalizar_a_unix(string $c): string { return str_replace("\r\n", "\n", $c); }
function normalizar_a_original(string $c, string $e): string { return $e === "\n" ? $c : str_replace("\n", "\r\n", $c); }
function contar_ocurrencias(string $c, string $b): int {
    if ($b === '') return 0;
    $n = 0; $o = 0;
    while (($p = strpos($c, $b, $o)) !== false) { $n++; $o = $p + strlen($b); }
    return $n;
}

if (!isset($htmls_a_bumpear) || !is_array($htmls_a_bumpear)) {
    $htmls_a_bumpear = ['aplicacion_GET.html'];
}
if (!isset($version_nueva)) $version_nueva = '';
$version_nueva = trim((string)$version_nueva);
$bumpear = ($version_nueva !== '');

$creaciones = [];
$eliminaciones = [];
$reemplazos_por_archivo = [];

foreach ($cambios as $cambio) {
    $tipo = $cambio['tipo'] ?? 'reemplazar';
    if ($tipo === 'crear') { $creaciones[] = $cambio; continue; }
    if ($tipo === 'eliminar') { $eliminaciones[] = $cambio; continue; }
    if (!isset($cambio['archivo']) || !isset($cambio['buscar']) || !isset($cambio['reemplazar'])) {
        echo "[FALLO] Cambio mal formado.\n"; exit(1);
    }
    $reemplazos_por_archivo[$cambio['archivo']][] = $cambio;
}

$total_reemplazos = 0;
foreach ($reemplazos_por_archivo as $l) $total_reemplazos += count($l);
echo "[INFO] " . count($reemplazos_por_archivo) . " archivo(s) a reemplazar, "
    . count($creaciones) . " a crear, " . count($eliminaciones) . " a eliminar.\n\n";

$archivos_a_escribir = [];
$bloques_ok = 0;
$bloques_fallidos = [];

foreach ($reemplazos_por_archivo as $archivo_rel => $lista) {
    $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
    if (!file_exists($ruta_abs)) { $bloques_fallidos[] = "Archivo no encontrado: $archivo_rel"; continue; }
    $contenido_original = file_get_contents($ruta_abs);
    if ($contenido_original === false) { $bloques_fallidos[] = "No se pudo leer: $archivo_rel"; continue; }
    $eol = detectar_eol($contenido_original);
    $contenido = normalizar_a_unix($contenido_original);
    $contenido_antes = $contenido;
    $hubo_error = false;
    foreach ($lista as $cambio) {
        $buscar_str = implode("\n", $cambio['buscar']);
        $reemplazar_str = implode("\n", $cambio['reemplazar']);
        $n = contar_ocurrencias($contenido, $buscar_str);
        if ($n === 0) { $bloques_fallidos[] = "$archivo_rel: no encontrado - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        if ($n > 1) { $bloques_fallidos[] = "$archivo_rel: ambiguo ($n) - {$cambio['descripcion']}"; $hubo_error = true; continue; }
        $contenido = str_replace($buscar_str, $reemplazar_str, $contenido);
        $bloques_ok++;
    }
    if (!$hubo_error && $contenido !== $contenido_antes) {
        $archivos_a_escribir[$ruta_abs] = [$contenido, $eol];
    }
}

if ($modo_estricto && !empty($bloques_fallidos)) {
    echo "=== ABORTADO ===\n";
    foreach ($bloques_fallidos as $f) echo "  [FALLO] $f\n";
    exit(1);
}

$archivos_bump_php_js = [];
$archivos_bump_js_css = [];
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    $rel = substr($ruta_abs, strlen($raiz_proyecto) + 1);
    $ext = strtolower(pathinfo($ruta_abs, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $rel;
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $rel;
}
foreach ($creaciones as $cr) {
    $ext = strtolower(pathinfo($cr['archivo'], PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'js') $archivos_bump_php_js[] = $cr['archivo'];
    if ($ext === 'js' || $ext === 'css') $archivos_bump_js_css[] = $cr['archivo'];
}

$bumps_atversion = 0;
$bumps_qv = 0;
$bumps_detalle = [];
$info_bumps = [];

if ($bumpear) {
    foreach (array_unique($archivos_bump_php_js) as $archivo_rel) {
        $ruta_abs = $raiz_proyecto . '/' . $archivo_rel;
        if (!file_exists($ruta_abs)) continue;
        if (isset($archivos_a_escribir[$ruta_abs])) {
            list($contenido, $eol) = $archivos_a_escribir[$ruta_abs];
        } else {
            $contenido_original = file_get_contents($ruta_abs);
            $eol = detectar_eol($contenido_original);
            $contenido = normalizar_a_unix($contenido_original);
        }
        $patron = '/(\*\s+@version\s+)(\S+)/';
        $viejo = '';
        $nuevo = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo) {
            $viejo = $m[2];
            return $m[1] . $version_nueva;
        }, $contenido, 1, $count);
        if ($count === 0) { $info_bumps[] = "$archivo_rel (sin @version)"; continue; }
        if ($viejo === $version_nueva) { continue; }
        $archivos_a_escribir[$ruta_abs] = [normalizar_a_unix($nuevo), $eol];
        $bumps_atversion++;
        $bumps_detalle[] = "$archivo_rel  @version $viejo -> $version_nueva";
    }

    foreach ($htmls_a_bumpear as $html_rel) {
        $html_abs = $raiz_proyecto . '/' . $html_rel;
        if (!file_exists($html_abs)) { $info_bumps[] = "$html_rel (no encontrado)"; continue; }
        if (isset($archivos_a_escribir[$html_abs])) {
            list($html, $eol) = $archivos_a_escribir[$html_abs];
        } else {
            $html_orig = file_get_contents($html_abs);
            $eol = detectar_eol($html_orig);
            $html = normalizar_a_unix($html_orig);
        }
        $html_antes = $html;
        foreach (array_unique($archivos_bump_js_css) as $archivo_rel) {
            $base = basename($archivo_rel);
            $base_esc = preg_quote($base, '/');
            $patron = '/(' . $base_esc . '\?v=)([^"\'\s>]+)/';
            $viejo_qv = '';
            $html = preg_replace_callback($patron, function($m) use ($version_nueva, &$viejo_qv) {
                $viejo_qv = $m[2];
                return $m[1] . $version_nueva;
            }, $html, 1, $c_qv);
            if ($c_qv > 0 && $viejo_qv !== $version_nueva) {
                $bumps_qv++;
                $bumps_detalle[] = "$html_rel  $base ?v= $viejo_qv -> $version_nueva";
            }
        }
        if ($html !== $html_antes) {
            $archivos_a_escribir[$html_abs] = [normalizar_a_unix($html), $eol];
        }
    }
}

$archivos_escritos = 0;
foreach ($archivos_a_escribir as $ruta_abs => $info) {
    list($contenido_unix, $eol) = $info;
    $contenido_final = normalizar_a_original($contenido_unix, $eol);
    if (file_put_contents($ruta_abs, $contenido_final) === false) {
        echo "[FALLO] No se pudo escribir: " . substr($ruta_abs, strlen($raiz_proyecto) + 1) . "\n";
        continue;
    }
    $archivos_escritos++;
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

foreach ($eliminaciones as $elim) {
    $ruta_abs = $raiz_proyecto . '/' . $elim['archivo'];
    if (!file_exists($ruta_abs)) { echo "[INFO] " . $elim['archivo'] . " no existía.\n"; continue; }
    if (unlink($ruta_abs)) echo "[OK] " . $elim['archivo'] . " (eliminado)\n";
    else echo "[FALLO] No se pudo eliminar: " . $elim['archivo'] . "\n";
}

if ($bumpear && (!empty($bumps_detalle) || !empty($info_bumps))) {
    echo "\n--- Bumps ---\n";
    foreach ($bumps_detalle as $d) echo "[BUMP] $d\n";
    foreach ($info_bumps as $i) echo "[INFO] $i\n";
}

echo "\n=== Resumen ===\n";
echo "Bloques aplicados: $bloques_ok\n";
echo "Archivos escritos: $archivos_escritos\n";
if ($bumpear) {
    echo "Bumps @version:    $bumps_atversion\n";
    echo "Bumps ?v=:         $bumps_qv\n";
}
if (!empty($bloques_fallidos)) {
    echo "Fallos: " . count($bloques_fallidos) . "\n";
    foreach ($bloques_fallidos as $f) echo "  - $f\n";
}
echo "\nListo.\n";