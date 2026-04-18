<?php
// Garantizar cabeceras CORS lo antes posible para evitar errores de preflight/error
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('content-type: application/json; charset=utf-8');

// Responder OPTIONS (preflight CORS) inmediatamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

// Zona horaria de Bolivia (UTC-4)
date_default_timezone_set('America/La_Paz');

// Evitar que errores PHP contaminen la respuesta JSON
ini_set('display_errors', 0);
ini_set('html_errors', 0);
error_reporting(E_ALL);

function enviarRespuestaError($mensaje, $archivo, $linea, $codigo = 500) {
    if (!headers_sent()) {
        http_response_code($codigo);
        header('Access-Control-Allow-Origin: *');
        header('content-type: application/json; charset=utf-8');
    }
    echo json_encode([
        'error'   => $mensaje,
        'archivo' => basename($archivo),
        'linea'   => $linea
    ]);
}

// Capturar cualquier excepción/error no capturado y devolverlo como JSON
set_exception_handler(function(\Throwable $e) {
    enviarRespuestaError($e->getMessage(), $e->getFile(), $e->getLine());
});

// Capturar errores fatales que no llegan al exception handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        enviarRespuestaError($error['message'], $error['file'], $error['line']);
    }
});