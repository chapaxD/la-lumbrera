<?php
// JWT nativo con PHP puro — no requiere librerías externas

// Cambia esta clave por una cadena larga, aleatoria y secreta
define('JWT_SECRET', 'B0tan3r0_V3nt4s_S3cr3t_K3y_2026!');
define('JWT_EXPIRATION', 43200); // 12 horas en segundos

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}

function crearJWT($datosUsuario) {
    $header = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64url_encode(json_encode([
        'iat' => time(),
        'exp' => time() + JWT_EXPIRATION,
        'idUsuario' => $datosUsuario['idUsuario'],
        'nombreUsuario' => $datosUsuario['nombreUsuario'],
        'rol' => $datosUsuario['rol']
    ]));
    $firma = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$firma";
}

function validarJWT($jwt) {
    $partes = explode('.', $jwt);
    if (count($partes) !== 3) return false;

    [$header, $payload, $firma] = $partes;

    $firmaEsperada = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if (!hash_equals($firmaEsperada, $firma)) return false;

    $datos = json_decode(base64url_decode($payload), true);
    if (!$datos || $datos['exp'] < time()) return false;

    return $datos;
}
