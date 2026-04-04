<?php
include_once "encabezado.php";
require_once __DIR__ . '/db_config.php';

$host     = DB_HOST;
$usuario  = DB_USER;
$password = DB_PASS;
$resultados = [];

// 1. Crear la base de datos si no existe (omitido en hosting compartido donde la BD ya existe)
try {
    $conexion = new PDO("mysql:host=$host", $usuario, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $creada = $conexion->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    if ($creada) $resultados[] = "Base de datos creada correctamente";
    $conexion = null;
} catch (PDOException $e) {
    // En hosting compartido (InfinityFree) la BD ya existe, se continua
    $resultados[] = "Usando base de datos existente: " . DB_NAME;
}

// 2. Conectar a la base de datos
$bd = new PDO("mysql:host=$host;dbname=" . DB_NAME . ";charset=utf8mb4", $usuario, $password);
$bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$bd->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

// 3. Crear todas las tablas
$tablas = [

    "categorias" => "CREATE TABLE IF NOT EXISTS categorias(
        id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        tipo        ENUM('PLATILLO','BEBIDA') NOT NULL,
        nombre      VARCHAR(50)  NOT NULL,
        descripcion VARCHAR(255)
    ) ENGINE=InnoDB;",

    "insumos" => "CREATE TABLE IF NOT EXISTS insumos(
        id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        codigo       VARCHAR(100) NOT NULL,
        nombre       VARCHAR(100) NOT NULL,
        descripcion  VARCHAR(255) NOT NULL,
        precio       DECIMAL(8,2) NOT NULL DEFAULT 0,
        tipo         ENUM('PLATILLO','BEBIDA') NOT NULL,
        categoria    BIGINT UNSIGNED NOT NULL,
        stock        DECIMAL(10,2) NOT NULL DEFAULT 0,
        stockMinimo  DECIMAL(10,2) NOT NULL DEFAULT 0,
        stockMateria DECIMAL(10,2) NOT NULL DEFAULT 0,
        tipoCorte    TINYINT      NOT NULL DEFAULT 0
    ) ENGINE=InnoDB;",

    "informacion_negocio" => "CREATE TABLE IF NOT EXISTS informacion_negocio(
        nombre              VARCHAR(100),
        telefono            VARCHAR(15),
        numeroMesas         TINYINT,
        logo                VARCHAR(255),
        direccion           VARCHAR(255),
        nit_emisor          VARCHAR(50)  DEFAULT NULL,
        razon_social        VARCHAR(150) DEFAULT NULL,
        actividad           VARCHAR(255) DEFAULT NULL,
        ciudad              VARCHAR(100) DEFAULT NULL,
        num_autorizacion    VARCHAR(100) DEFAULT NULL,
        fecha_limite_emision DATE         DEFAULT NULL
    ) ENGINE=InnoDB;",

    "usuarios" => "CREATE TABLE IF NOT EXISTS usuarios(
        id       BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        correo   VARCHAR(100) NOT NULL,
        nombre   VARCHAR(100) NOT NULL,
        telefono VARCHAR(20)  NOT NULL,
        password VARCHAR(255) NOT NULL,
        rol      VARCHAR(20)  NOT NULL DEFAULT 'mesero'
    ) ENGINE=InnoDB;",

    "ventas" => "CREATE TABLE IF NOT EXISTS ventas(
        id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        idMesa         TINYINT        NOT NULL,
        cliente        VARCHAR(100),
        fecha          DATETIME       NOT NULL,
        total          DECIMAL(8,2)   NOT NULL,
        pagado         DECIMAL(8,2)   NOT NULL,
        idUsuario      BIGINT UNSIGNED NOT NULL,
        metodoPago     VARCHAR(20)    DEFAULT 'EFECTIVO',
        montoEfectivo  DECIMAL(8,2)   DEFAULT 0,
        montoTarjeta   DECIMAL(8,2)   DEFAULT 0,
        montoQR        DECIMAL(8,2)   DEFAULT 0,
        tipo_orden     VARCHAR(20)    DEFAULT 'LOCAL',
        direccion      VARCHAR(255)   DEFAULT NULL,
        telefono       VARCHAR(20)    DEFAULT NULL,
        estado_delivery VARCHAR(20)   DEFAULT NULL
    ) ENGINE=InnoDB;",

    "insumos_venta" => "CREATE TABLE IF NOT EXISTS insumos_venta(
        id       BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        idInsumo BIGINT UNSIGNED NOT NULL,
        precio   DECIMAL(8,2)   NOT NULL,
        cantidad INT            NOT NULL,
        idVenta  BIGINT UNSIGNED
    ) ENGINE=InnoDB;",

    "historial_stock" => "CREATE TABLE IF NOT EXISTS historial_stock(
        id       BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        idInsumo BIGINT UNSIGNED NOT NULL,
        idUsuario BIGINT UNSIGNED,
        cantidad DECIMAL(10,2)  NOT NULL,
        tipo     ENUM('VENTA','COMPRA','AJUSTE','MERMA','CANCELACION') NOT NULL,
        nota     VARCHAR(255)   DEFAULT NULL,
        fecha    DATETIME       NOT NULL
    ) ENGINE=InnoDB;",

    "ordenes_activas" => "CREATE TABLE IF NOT EXISTS ordenes_activas(
        id         BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        tipo       ENUM('LOCAL','DELIVERY') NOT NULL DEFAULT 'LOCAL',
        referencia VARCHAR(50)  NOT NULL,
        atiende    VARCHAR(100),
        idUsuario  BIGINT UNSIGNED,
        total      DECIMAL(8,2) DEFAULT 0,
        estado     VARCHAR(20)  DEFAULT 'libre',
        cliente    VARCHAR(100),
        direccion  VARCHAR(255),
        telefono   VARCHAR(20),
        created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
        tipo_orden VARCHAR(20)  DEFAULT 'LOCAL'
    ) ENGINE=InnoDB;",

    "items_orden" => "CREATE TABLE IF NOT EXISTS items_orden(
        id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        idOrden         BIGINT UNSIGNED NOT NULL,
        idInsumo        BIGINT UNSIGNED,
        codigo          VARCHAR(100),
        nombre          VARCHAR(100) NOT NULL,
        precio          DECIMAL(8,2) NOT NULL,
        caracteristicas VARCHAR(255),
        cantidad        INT          NOT NULL DEFAULT 1,
        estado          ENUM('pendiente','listo','entregado') NOT NULL DEFAULT 'pendiente'
    ) ENGINE=InnoDB;",

    "reservas" => "CREATE TABLE IF NOT EXISTS reservas(
        id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        nombre_cliente VARCHAR(100) NOT NULL,
        telefono       VARCHAR(20),
        fecha          DATE         NOT NULL,
        hora           TIME         NOT NULL,
        personas       TINYINT,
        idMesa         TINYINT,
        notas          VARCHAR(255),
        idUsuario      BIGINT UNSIGNED,
        estado         ENUM('PENDIENTE','CONFIRMADA','CANCELADA','COMPLETADA') NOT NULL DEFAULT 'PENDIENTE'
    ) ENGINE=InnoDB;",

    "menu_dia" => "CREATE TABLE IF NOT EXISTS menu_dia(
        id        BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        idInsumo  BIGINT UNSIGNED NOT NULL,
        diaSemana VARCHAR(20)     NOT NULL
    ) ENGINE=InnoDB;",

    "caja_diaria" => "CREATE TABLE IF NOT EXISTS caja_diaria(
        id                 BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        idUsuarioApertura  BIGINT UNSIGNED NOT NULL,
        montoApertura      DECIMAL(8,2)    NOT NULL DEFAULT 0,
        fechaApertura      DATETIME        NOT NULL,
        estado             ENUM('ABIERTA','CERRADA') NOT NULL DEFAULT 'ABIERTA',
        idUsuarioCierre    BIGINT UNSIGNED,
        montoCierre        DECIMAL(8,2),
        ventasTotales      DECIMAL(10,2),
        gastosTotales      DECIMAL(10,2),
        ventasTarjeta      DECIMAL(10,2),
        ventasQR           DECIMAL(10,2),
        fechaCierre        DATETIME
    ) ENGINE=InnoDB;",

    "gastos_caja" => "CREATE TABLE IF NOT EXISTS gastos_caja(
        id        BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        idCaja    BIGINT UNSIGNED  NOT NULL,
        concepto  VARCHAR(255)     NOT NULL,
        monto     DECIMAL(8,2)     NOT NULL,
        fecha     DATETIME         NOT NULL,
        idUsuario BIGINT UNSIGNED
    ) ENGINE=InnoDB;",

    "cancelaciones" => "CREATE TABLE IF NOT EXISTS cancelaciones(
        id         BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        tipo       ENUM('LOCAL','DELIVERY') NOT NULL,
        referencia VARCHAR(50)  NOT NULL,
        idOrden    BIGINT UNSIGNED,
        idUsuario  BIGINT UNSIGNED,
        motivo     TEXT,
        fecha      DATETIME NOT NULL
    ) ENGINE=InnoDB;",

    "reportes_cocina" => "CREATE TABLE IF NOT EXISTS reportes_cocina(
        id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        idInsumo     BIGINT UNSIGNED,
        nombreInsumo VARCHAR(100) NOT NULL,
        tipo         VARCHAR(50),
        nota         TEXT,
        idUsuario    BIGINT UNSIGNED,
        fecha        DATETIME    NOT NULL,
        resuelto     TINYINT(1)  NOT NULL DEFAULT 0
    ) ENGINE=InnoDB;",

];

foreach ($tablas as $nombre => $sql) {
    $bd->exec($sql);
    $resultados[] = "Tabla '$nombre' lista";
}

// Migraciones: ampliar enums y columnas sin romper datos existentes
$migraciones = [
    "reservas_estado_enum" => "ALTER TABLE reservas MODIFY estado ENUM('PENDIENTE','CONFIRMADA','CANCELADA','COMPLETADA','SENTADA','NO-SHOW') DEFAULT 'PENDIENTE'",
];
foreach ($migraciones as $nombre => $sql) {
    try {
        $bd->exec($sql);
        $resultados[] = "Migración '$nombre' aplicada";
    } catch (Exception $e) {
        $resultados[] = "Migración '$nombre' omitida: " . $e->getMessage();
    }
}

// 4. Crear carpeta mesas_ocupadas si no existe
$carpetaMesas = __DIR__ . '/mesas_ocupadas';
if (!is_dir($carpetaMesas)) {
    mkdir($carpetaMesas, 0755, true);
    $resultados[] = "Carpeta mesas_ocupadas creada";
}

// 5. Insertar usuario administrador por defecto si no hay ninguno
$total = $bd->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
if ((int)$total === 0) {
    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
    $bd->prepare("INSERT INTO usuarios (correo, nombre, telefono, password, rol) VALUES (?, ?, ?, ?, 'admin')")
       ->execute(['admin@admin.com', 'Administrador', '0000000000', $passwordHash]);
    $resultados[] = "Usuario admin creado — correo: admin@admin.com / contraseña: admin123 (¡cámbiala tras el primer login!)";
}

$bd = null;
echo json_encode($resultados);

