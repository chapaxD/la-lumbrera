<?php

$usuario = json_decode(file_get_contents("php://input"));
if(!$usuario) exit("No se encontraron datos");
include_once "encabezado.php";
include_once "funciones.php";
include_once "jwt_utils.php";
$respuesta = iniciarSesion($usuario->correo, $usuario->password);

if($respuesta){

	$datosUsuario = [
		"nombreUsuario" => $respuesta->nombre,
		"idUsuario" => $respuesta->id,
        "rol" => $respuesta->rol ?? 'mesero'
	];

	$token = crearJWT($datosUsuario);

	$verificaPass = verificarPassword("PacoHunterDev", $respuesta->id);
	if($verificaPass) {
		echo json_encode(["resultado" => "cambia", "datos" => $datosUsuario, "token" => $token]);
		return;
	}

	echo json_encode(["resultado" => true, "datos" => $datosUsuario, "token" => $token]);
} else {
	echo json_encode(["resultado" => false]);
}
