<?php
// lib/usuarios.php - Gestión de Usuarios y Autenticación

function cambiarPassword($idUsuario, $password)
{
    $bd = conectarBaseDatos();
    $passwordCod = password_hash($password, PASSWORD_DEFAULT);
    $sentencia = $bd->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    return $sentencia->execute([$passwordCod, $idUsuario]);
}

function verificarPassword($password, $idUsuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT password FROM usuarios  WHERE id = ?");
    $sentencia->execute([$idUsuario]);
    $usuario = $sentencia->fetchObject();
    if ($usuario === FALSE) return false;
    elseif ($sentencia->rowCount() == 1) {
        $passwordVerifica = password_verify($password, $usuario->password);
        if ($usuario && $passwordVerifica) {
            return true;
        } else {
            return false;
        }
    }
}

function iniciarSesion($correo, $password)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT * FROM usuarios WHERE correo = ?");
    $sentencia->execute([$correo]);
    $usuario = $sentencia->fetchObject();
    if ($usuario === FALSE) return false;
    elseif ($sentencia->rowCount() == 1) {
        $passwordVerifica = password_verify($password, $usuario->password);
        if ($usuario && $passwordVerifica) {
            return $usuario;
        } else {
            return false;
        }
    }
}

function eliminarUsuario($idUsuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("DELETE FROM usuarios WHERE id = ?");
    return $sentencia->execute([$idUsuario]);
}

function editarUsuario($usuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("UPDATE usuarios SET correo = ?, nombre = ?, telefono = ?, rol = ? WHERE id = ?");
    return $sentencia->execute([$usuario->correo, $usuario->nombre, $usuario->telefono, $usuario->rol ?? 'mesero', $usuario->id]);
}

function obtenerUsuarioPorId($idUsuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT id, correo, nombre, telefono, rol FROM usuarios WHERE id = ?");
    $sentencia->execute([$idUsuario]);
    return $sentencia->fetchObject();
}

function obtenerUsuarios()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT id, correo, nombre, telefono, rol FROM usuarios");
    return $sentencia->fetchAll();
}

function obtenerNumeroUsuarios()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT COUNT(*) AS numeroUsuarios
	FROM usuarios");
    return $sentencia->fetchObject()->numeroUsuarios;
}

function registrarUsuario($usuario)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("INSERT INTO usuarios (correo, nombre, telefono, password, rol) VALUES(?,?,?,?,?)");
    return $sentencia->execute([$usuario->correo, $usuario->nombre, $usuario->telefono, $usuario->password, $usuario->rol ?? 'mesero']);
}
