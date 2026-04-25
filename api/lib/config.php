<?php
// lib/config.php - Configuración del Local y Utilidades de Archivos

function actualizarInformacionLocal($datos)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("UPDATE informacion_negocio SET nombre = ?, telefono = ?, numeroMesas = ?, logo = ?, direccion = ?, nit_emisor = ?, razon_social = ?, actividad = ?, ciudad = ?, num_autorizacion = ?, fecha_limite_emision = ?, usa_pantalla_parrilla = ?, usa_pantalla_cocina = ?");
    return $sentencia->execute([$datos->nombre, $datos->telefono, $datos->numeroMesas, $datos->logo, $datos->direccion ?? '', $datos->nit_emisor ?? null, $datos->razon_social ?? null, $datos->actividad ?? null, $datos->ciudad ?? null, $datos->num_autorizacion ?? null, $datos->fecha_limite_emision ?? null, $datos->usa_pantalla_parrilla ?? 1, $datos->usa_pantalla_cocina ?? 1]);
}

function registrarInformacionLocal($datos)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("INSERT INTO informacion_negocio (nombre, telefono, numeroMesas, logo, direccion, nit_emisor, razon_social, actividad, ciudad, num_autorizacion, fecha_limite_emision, usa_pantalla_parrilla, usa_pantalla_cocina) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    return $sentencia->execute([$datos->nombre, $datos->telefono, $datos->numeroMesas, $datos->logo, $datos->direccion ?? '', $datos->nit_emisor ?? null, $datos->razon_social ?? null, $datos->actividad ?? null, $datos->ciudad ?? null, $datos->num_autorizacion ?? null, $datos->fecha_limite_emision ?? null, $datos->usa_pantalla_parrilla ?? 1, $datos->usa_pantalla_cocina ?? 1]);
}

function obtenerInformacionLocal()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT * FROM informacion_negocio");
    return $sentencia->fetchAll();
}

function obtenerImagen($imagen)
{
    $imagen = str_replace('data:image/png;base64,', '', $imagen);
    $imagen = str_replace('data:image/jpeg;base64,', '', $imagen);
    $imagen = str_replace(' ', '+', $imagen);
    $data = base64_decode($imagen);
    $file = DIRECTORIO . uniqid() . '.png';

    file_put_contents($file, $data);
    return $file;
}
