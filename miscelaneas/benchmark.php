<?php
function benchmark($etiqueta, $callback) {
    $inicioTiempo = hrtime(true);
    $inicioMem = memory_get_usage(true);

    $callback();

    $finTiempo = hrtime(true);
    $finMem = memory_get_usage(true);

    $tiempo = ($finTiempo - $inicioTiempo) / 1e6; // ms
    $memoria = ($finMem - $inicioMem) / 1024; // KB

    echo "$etiqueta: {$tiempo} ms | ΔMemoria: {$memoria} KB<br>";
}

?>