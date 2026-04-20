<?php
class ComunicadorFactory {

    public static function crear(string $tipo, $host=null, $port=null): Comunicador {
        return match ($tipo) {
            'http'     => new ComunicadorHTTP(),
            'sistema'  => new ComunicadorSistema(),
            'websocket'  => new ComunicadorWebSocket($host, $port),
            default    => throw new Exception("Tipo no soportado")
        };
    }
}