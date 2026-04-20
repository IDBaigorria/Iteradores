<?php
class ComunicadorWebSocket implements Comunicador {

    protected $socket;

    public function __construct(string $host, int $port) {
        $this->socket = fsockopen($host, $port);
    }

    public function enviar(string $destino, mixed $mensaje = null, array $opciones = []): void {
        fwrite($this->socket, json_encode($mensaje));
    }

    public function solicitar(string $destino, mixed $mensaje = null, array $opciones = []): mixed {
        $this->enviar($destino, $mensaje);
        return fgets($this->socket);
    }

    public function escuchar(callable $callback): void {
        while (!feof($this->socket)) {
            $data = fgets($this->socket);
            if ($data) {
                $callback($data);
            }
        }
    }

    public function cerrar(): void {
        fclose($this->socket);
    }

    public function estado(): string {
        return "websocket";
    }
}
