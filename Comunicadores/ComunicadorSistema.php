<?php
class ComunicadorSistema implements Comunicador {

    public function enviar(string $destino, mixed $mensaje = null, array $opciones = []): void {
        exec($destino . ' ' . escapeshellarg((string)$mensaje));
    }

    public function solicitar(string $destino, mixed $mensaje = null, array $opciones = []): mixed {
        return shell_exec($destino . ' ' . escapeshellarg((string)$mensaje));
    }

    public function escuchar(callable $callback): void {
        throw new Exception("Escucha no soportada en sistema simple");
    }

    public function cerrar(): void {}

    public function estado(): string {
        return "sistema";
    }
}
