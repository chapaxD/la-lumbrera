---
applyTo: "api/**/*.php"
---

# Convenciones de la API PHP

## Cabecera requerida en cada archivo (endpoints protegidos)

```php
<?php
require_once 'encabezado.php';      // Establece CORS + Content-Type: application/json
require_once 'verificar_token.php'; // Valida el Bearer JWT; define $tokenDatos
$pdo = conectarBaseDatos();          // De funciones.php — singleton PDO
```

Los endpoints públicos (ej. `iniciar_sesion.php`) omiten `verificar_token.php`.

## Formato de respuesta

Siempre retornar JSON — nunca hacer echo de texto plano:

```php
echo json_encode(["resultado" => true, "datos" => $rows, "mensaje" => "OK"]);
echo json_encode(["resultado" => false, "error" => "Descripción del error"]);
```

## Lectura de entrada

```php
$datos = json_decode(file_get_contents("php://input"));
$nombre = $datos->nombre ?? null;
```

Nunca usar `$_POST` ni `$_GET` en endpoints JSON.

## Base de datos

- Usar `$pdo = conectarBaseDatos()` — NO crear un nuevo PDO directamente.
- Usar sentencias preparadas con placeholders con nombre: `:nombre`, `:id`.
- Envolver en `try { ... } catch (PDOException $e) { ... }`.

## Contexto de autenticación

Tras ejecutar `verificar_token.php`, `$tokenDatos` contiene:
- `$tokenDatos->idUsuario`
- `$tokenDatos->rol`
- `$tokenDatos->nombre`

## Lógica de negocio

Preferir colocar las consultas SQL en `api/lib/<dominio>.php` en lugar de escribirlas directamente en el archivo del endpoint.
