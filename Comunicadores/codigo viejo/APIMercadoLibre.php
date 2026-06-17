<?php
class ApiMercadoLibre extends ApiExterna {

    protected string $accessToken;

    public function __construct(Comunicador $comunicador, string $accessToken) {
        parent::__construct($comunicador);
        $this->accessToken = $accessToken;
    }

    protected function autenticar(array &$opciones): void {
        $opciones['headers']['Authorization'] =
            "Bearer {$this->accessToken}";
    }

    public function obtenerUsuario() {
        return $this->request(
            "https://api.mercadolibre.com/users/me"
        );
    }

    public function publicarProducto(array $producto) {
        return $this->request(
            "https://api.mercadolibre.com/items",
            $producto
        );
    }
}
