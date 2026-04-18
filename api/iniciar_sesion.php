<?php
include_once "encabezado.php";
include_once "funciones.php";
include_once "jwt_utils.php";

$usuario = json_decode(file_get_contents("php://input"));

if (!$usuario) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(["resultado" => false, "mensaje" => "No se encontraron datos"]);
    }
    exit;
}

$respuesta = iniciarSesion($usuario->correo, $usuario->password);

if ($respuesta) {
    $datosUsuario = [
        "nombreUsuario" => $respuesta->nombre,
        "idUsuario" => $respuesta->id,
        "rol" => $respuesta->rol ?? 'mesero'
    ];

    $token = crearJWT($datosUsuario);

    // Verificación de password por defecto
    if (verificarPassword("PacoHunterDev", $respuesta->id)) {
        echo json_encode(["resultado" => "cambia", "datos" => $datosUsuario, "token" => $token]);
        return;
    }

    echo json_encode(["resultado" => true, "datos" => $datosUsuario, "token" => $token]);
} else {
    echo json_encode(["resultado" => false]);
}
