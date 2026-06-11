<?php
// ============================================================
// TEST DE COMANDO (PHP)
// ============================================================
// Estado de implementación:
// ✅ = implementado y probado
// ⏳ = pendiente / incompleto
// ❌ = no implementado
// ============================================================
require_once "Nodos/NodoElectrico.php";
require_once "Nodos/Nodo.php";
require_once "Controlador/Controlador.php";
require_once "Configuracion/Entorno.php";
require_once "Configuracion/Configuracion.php";

use Iteradores\Nodos\NodoElectrico;
use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Entorno;
use Iteradores\Configuracion\Conf;

// Forzar modo desarrollo si no está definido
if (!Entorno::es_desarrollo()) {
    echo "⚠️ Las pruebas deberían ejecutarse en entorno DESARROLLO<br><br>";
}

// ──────────────────────────────────────────────────────────
// 7. PRUEBAS EXHAUSTIVAS DEL SISTEMA DE COMANDOS
// ──────────────────────────────────────────────────────────
echo "\n🔹 Pruebas del sistema de comandos<br>";

Controlador::ejecutar_prueba(function($token) {
    // 7.1 Ejecutar el comando debug:imprimir en modo CONSOLA
    echo "▶ 7.1 Ejecutar 'debug:imprimir' en modo CONSOLA<br>";
    Entorno::establecer_salida(Entorno::SALIDA_CONSOLA);
    $resultado = Controlador::ejecutar_comando('debug:imprimir');
    echo "   Resultado: " . var_export($resultado, true) . " (debe ser true)<br>";

    // 7.2 Ejecutar el comando en modo HTML
    echo "▶ 7.2 Ejecutar 'debug:imprimir' en modo HTML<br>";
    Entorno::establecer_salida(Entorno::SALIDA_HTML);
    Controlador::ejecutar_comando('debug:imprimir');

    // 7.3 Intentar ejecutar un comando inexistente
    echo "▶ 7.3 Ejecutar comando inexistente<br>";
    $res_inexistente = Controlador::ejecutar_comando('comando:inexistente');
    echo "   Resultado: " . var_export($res_inexistente, true) . " (debe ser null)<br>";
    echo "   (Debe aparecer un error en el log)<br>";
    NodoElectrico::imprimir_errores();
    NodoElectrico::limpiar_errores();

    // 7.4 Probar deshacer con pila vacía
    echo "▶ 7.4 Deshacer con historial vacío<br>";
    $res_deshacer = Controlador::deshacer_ultimo();
    echo "   Resultado: " . var_export($res_deshacer, true) . " (debe ser null)<br>";
    echo "   (Debe aparecer una alerta 'No hay comandos para deshacer')<br>";
    NodoElectrico::imprimir_alertas();
    NodoElectrico::limpiar_alertas();

    // 7.5 Registrar un comando en caliente (post-inicialización)
    echo "▶ 7.5 Registrar comando en tiempo de ejecución<br>";
    $registrado = Controlador::registrar_comando(
        'prueba:eco',
        function($token, ...$args) { return implode(', ', $args); },
        null,
        false
    );
    echo "   Registro: " . var_export($registrado, true) . " (debe ser true)<br>";
    // Ejecutar el nuevo comando con argumentos
    $eco = Controlador::ejecutar_comando('prueba:eco', 'Hola', 'Mundo');
    echo "   Ejecución con argumentos: " . var_export($eco, true) . " (debe ser 'Hola, Mundo')<br>";

    // 7.6 Probar que un comando de desarrollo no se registra en producción
    echo "▶ 7.6 Bloqueo de comando de desarrollo en producción<br>";
    Entorno::establecer_modo(Entorno::MODO_PRODUCCION);
    $registrado_prod = Controlador::registrar_comando(
        'debug:temp', function() {}, null, true
    );
    echo "   Registro en producción: " . var_export($registrado_prod, true) . " (debe ser false)<br>";
    Entorno::establecer_modo(Entorno::MODO_DESARROLLO);

    // 7.7 Probar encolar_comando post-inicialización (registro inmediato)
    echo "▶ 7.7 Encolar comando después de inicializar (registro inmediato)<br>";
    $clase_anonima = new class implements \Iteradores\Comandos\Comando {
        public static function nombre(): string { return 'anonimo:test'; }
        public static function solo_desarrollo(): bool { return false; }
        public function ejecutar(string $token, ...$args) { return 'OK'; }
        public function reversa(): ?callable { return null; }
    };
    Controlador::encolar_comando($clase_anonima);
    $res_anonimo = Controlador::ejecutar_comando('anonimo:test');
    echo "   Resultado: " . var_export($res_anonimo, true) . " (debe ser 'OK')<br>";

    echo "✅ Pruebas de comandos completadas<br>";
});


?>