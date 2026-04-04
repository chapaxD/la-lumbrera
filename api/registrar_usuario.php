<?php
include_once "encabezado.php";
include_once "verificar_token.php";
$usuario = json_decode(file_get_contents("php://input"));
if (!$usuario) {
    http_response_code(500);
    exit;
}

include_once "funciones.php";
$defecto = "Admin12345";
$usuario->password = password_hash($defecto, PASSWORD_DEFAULT);

$resultado = registrarUsuario($usuario);
echo json_encode($resultado);


