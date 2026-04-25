<?php
// lib/clientes.php - Gestión de Clientes

$NOMBRES_GENERICOS = ['mostrador', 'sin nombre', 'sin nombr', 's/n', '99001', 'consumidor final', 'cf', ''];

function esNombreGenerico($nombre)
{
    global $NOMBRES_GENERICOS;
    return in_array(strtolower(trim((string)$nombre)), $NOMBRES_GENERICOS);
}

function obtenerClientes($q = '')
{
    $bd = conectarBaseDatos();
    if ($q !== '') {
        $like = '%' . $q . '%';
        $stmt = $bd->prepare(
            "SELECT id, nombre, apellido, telefono, email, nit, direccion, notas
             FROM clientes
             WHERE LOWER(nombre) LIKE LOWER(?) OR LOWER(apellido) LIKE LOWER(?) OR LOWER(nit) LIKE LOWER(?)
             ORDER BY nombre ASC
             LIMIT 20"
        );
        $stmt->execute([$like, $like, $like]);
    } else {
        $stmt = $bd->query(
            "SELECT id, nombre, apellido, telefono, email, nit, direccion, notas
             FROM clientes ORDER BY nombre ASC"
        );
    }
    return $stmt->fetchAll();
}

function registrarCliente($data)
{
    if (esNombreGenerico($data->nombre ?? '')) {
        return ['ok' => false, 'error' => 'Nombre genérico no permitido'];
    }
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare(
        "INSERT INTO clientes (nombre, apellido, telefono, email, nit, direccion, notas)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        trim($data->nombre),
        trim($data->apellido   ?? ''),
        trim($data->telefono   ?? ''),
        trim($data->email      ?? ''),
        trim($data->nit        ?? ''),
        trim($data->direccion  ?? ''),
        trim($data->notas      ?? ''),
    ]);
    return ['ok' => true, 'id' => $bd->lastInsertId()];
}

function editarCliente($data)
{
    if (esNombreGenerico($data->nombre ?? '')) {
        return ['ok' => false, 'error' => 'Nombre genérico no permitido'];
    }
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare(
        "UPDATE clientes SET nombre=?, apellido=?, telefono=?, email=?, nit=?, direccion=?, notas=?
         WHERE id=?"
    );
    $stmt->execute([
        trim($data->nombre),
        trim($data->apellido   ?? ''),
        trim($data->telefono   ?? ''),
        trim($data->email      ?? ''),
        trim($data->nit        ?? ''),
        trim($data->direccion  ?? ''),
        trim($data->notas      ?? ''),
        $data->id,
    ]);
    return ['ok' => true];
}

function eliminarCliente($id)
{
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    return ['ok' => true];
}
