<?php
abstract class ApiExterna {

    protected Comunicador $comunicador;

    public function __construct(Comunicador $comunicador) {
        $this->comunicador = $comunicador;
    }

    abstract protected function autenticar(array &$opciones): void;

    protected function request(string $url, mixed $data = null): mixed {
        $opciones = [];
        $this->autenticar($opciones);
        return $this->comunicador->solicitar($url, $data, $opciones);
    }
}
