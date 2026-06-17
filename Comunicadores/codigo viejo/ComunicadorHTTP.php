<?php
class ComunicadorHTTP implements Comunicador {

    public function enviar(string $destino, mixed $mensaje = null, array $opciones = []): void {
        $this->request($destino, $mensaje, $opciones);
    }

    public function solicitar(string $destino, mixed $mensaje = null, array $opciones = []): mixed {
        return $this->request($destino, $mensaje, $opciones);
    }

    protected function request(string $url, mixed $data, array $opciones) {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
        }

        $resp = curl_exec($ch);
        curl_close($ch);

        return json_decode($resp, true) ?? $resp;
    }

    /**
     * Modo servidor: manejar GET/POST
     */
    public function escuchar(callable $callback): void {
        $callback([
            'method' => $_SERVER['REQUEST_METHOD'],
            'get'    => $_GET,
            'post'   => $_POST,
            'raw'    => file_get_contents("php://input"),
        ]);
    }

    public function cerrar(): void {}

    public function estado(): string {
        return "http";
    }
}
