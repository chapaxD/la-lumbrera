<?php
// SSE requiere sus propios headers — NO incluir encabezado.php (setea content-type: application/json)
date_default_timezone_set('America/La_Paz');

// Limpiar TODOS los buffers de salida antes de enviar headers (WAMP tiene output_buffering activado)
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('X-Accel-Buffering: no');     // Desactiva buffer en Nginx/proxies
header('Access-Control-Allow-Origin: *');

include_once __DIR__ . '/jwt_utils.php';

// EventSource no puede enviar headers — el token va como query param
$token = isset($_GET['token']) ? $_GET['token'] : '';
$datosToken = validarJWT($token);

if (!$datosToken) {
    echo "event: auth_error\n";
    echo "data: {\"message\":\"No autorizado\"}\n\n";
    flush();
    exit;
}

$idUsuario = $datosToken['idUsuario'] ?? null;
$rol       = $datosToken['rol']       ?? null;

include_once __DIR__ . '/funciones.php';

set_time_limit(0);
ini_set('output_buffering', 'off');
ini_set('implicit_flush', 1);

$ultimoHash = null;

while (true) {
    if (connection_aborted()) break;

    try {
        $mesas      = obtenerMesas($idUsuario, $rol);
        $deliveries = obtenerDeliveries($idUsuario, $rol);

        $payload = json_encode(['mesas' => $mesas, 'deliveries' => $deliveries]);
        $hash    = md5($payload);

        if ($hash !== $ultimoHash) {
            $ultimoHash = $hash;
            echo "data: $payload\n\n";
        } else {
            // Heartbeat para mantener viva la conexión (evita timeout del proxy)
            echo ": ping\n\n";
        }

        // Forzar flush a nivel PHP + Apache
        if (ob_get_level() > 0) ob_flush();
        flush();
    } catch (Exception $e) {
        echo "event: server_error\n";
        echo "data: {\"message\":\"Error interno\"}\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    sleep(1);
}
