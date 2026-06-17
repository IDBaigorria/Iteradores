<?php

interface Comunicador {

    /**
     * Envío sin esperar respuesta
     */
    public function enviar(string $destino, mixed $mensaje = null, array $opciones = []): void;

    /**
     * Envío esperando respuesta
     */
    public function solicitar(string $destino, mixed $mensaje = null, array $opciones = []): mixed;

    /**
     * Escuchar eventos / mensajes (si aplica)
     */
    public function escuchar(callable $callback): void;

    /**
     * Cerrar recursos
     */
    public function cerrar(): void;

    /**
     * Identificador del comunicador
     */
    public function estado(): string;
}
