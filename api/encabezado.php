<?php
// Zona horaria de Bolivia (UTC-4)
date_default_timezone_set('America/La_Paz');

// Evitar que errores PHP contaminen la respuesta JSON
ini_set('display_errors', 0);
ini_set('html_errors', 0);
error_reporting(E_ALL);

// Capturar cualquier excepción/error no capturado y devolverlo como JSON
set_exception_handler(function(\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => $e->getMessage(),
        'archivo' => basename($e->getFile()),
        'linea'   => $e->getLine()
    ]);
});

// Capturar errores fatales que no llegan al exception handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'error'   => $error['message'],
            'archivo' => basename($error['file']),
            'linea'   => $error['line']
        ]);
    }
});

// Responder OPTIONS (preflight CORS) inmediatamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('content-type: application/json; charset=utf-8');