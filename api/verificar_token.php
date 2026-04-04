<?php
// Incluir en cada endpoint protegido con:
//   include_once "verificar_token.php";
// Si el token es inválido o falta, devuelve 401 y corta la ejecución.
// Si es válido, deja disponible $tokenDatos con: idUsuario, nombreUsuario, rol.

include_once __DIR__ . '/jwt_utils.php';

function obtenerTokenDelHeader() {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = $headers['Authorization'] ?? '';
    } else {
        $header = '';
    }

    if (preg_match('/Bearer\s+(.+)$/i', $header, $matches)) {
        return $matches[1];
    }
    return null;
}

$_jwt = obtenerTokenDelHeader();

if (!$_jwt) {
    http_response_code(401);
    echo json_encode(['error' => 'Token requerido']);
    exit;
}

$tokenDatos = validarJWT($_jwt);

if (!$tokenDatos) {
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido o expirado']);
    exit;
}
