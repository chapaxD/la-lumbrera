<?php
include_once "encabezado.php";
include_once "funciones.php";
require_once __DIR__ . '/db_config.php';

$resultados = [];
$resultados[] = "Usando base de datos existente: " . DB_NAME;

// Conectar usando funciones.php (soporta SSL para TiDB Cloud)
$bd = conectarBaseDatos();

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
        tipoCorte    INT          NOT NULL DEFAULT 0,
        tipoVenta    VARCHAR(20)  NOT NULL DEFAULT 'NORMAL',
        idComboPlantilla BIGINT UNSIGNED NULL DEFAULT NULL
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
        estado          ENUM('pendiente','listo','entregado') NOT NULL DEFAULT 'pendiente',
        detalle_json    LONGTEXT NULL DEFAULT NULL
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
        adelanto       DECIMAL(8,2) NOT NULL DEFAULT 0,
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

    "clientes" => "CREATE TABLE IF NOT EXISTS clientes(
        id         INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        nombre     VARCHAR(150) NOT NULL,
        apellido   VARCHAR(150) DEFAULT NULL,
        telefono   VARCHAR(30)  DEFAULT NULL,
        email      VARCHAR(150) DEFAULT NULL,
        nit        VARCHAR(30)  DEFAULT NULL,
        direccion  VARCHAR(255) DEFAULT NULL,
        notas      TEXT,
        created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    "facturas" => "CREATE TABLE IF NOT EXISTS facturas(
        id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        numero           INT UNSIGNED    NOT NULL,
        fechaHora        DATETIME        NOT NULL,
        nitComprador     VARCHAR(20)     NOT NULL DEFAULT '99001',
        nombreComprador  VARCHAR(150)    NOT NULL DEFAULT 'SIN NOMBRE',
        codigoControl    VARCHAR(100)    DEFAULT NULL,
        subtotal         DECIMAL(10,2)   NOT NULL DEFAULT 0,
        descuentos       DECIMAL(10,2)   NOT NULL DEFAULT 0,
        baseCredito      DECIMAL(10,2)   NOT NULL DEFAULT 0,
        iva              DECIMAL(10,2)   NOT NULL DEFAULT 0,
        total            DECIMAL(10,2)   NOT NULL DEFAULT 0,
        nota             TEXT,
        idVenta          BIGINT UNSIGNED DEFAULT NULL,
        idUsuario        BIGINT UNSIGNED NOT NULL,
        estado           ENUM('EMITIDA','ANULADA') NOT NULL DEFAULT 'EMITIDA',
        createdAt        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_numero_factura (numero)
    ) ENGINE=InnoDB;",

    "factura_items" => "CREATE TABLE IF NOT EXISTS factura_items(
        id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        idFactura       BIGINT UNSIGNED NOT NULL,
        cantidad        INT UNSIGNED    NOT NULL DEFAULT 1,
        descripcion     VARCHAR(255)    NOT NULL,
        precioUnitario  DECIMAL(10,2)   NOT NULL DEFAULT 0,
        descuento       DECIMAL(10,2)   NOT NULL DEFAULT 0,
        subtotal        DECIMAL(10,2)   NOT NULL DEFAULT 0
    ) ENGINE=InnoDB;",

    // Tabla para registrar el despiece de materia prima en parrilla
    "despiece_parrilla" => "CREATE TABLE IF NOT EXISTS despiece_parrilla(
        id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        fecha DATETIME NOT NULL,
        materia_prima VARCHAR(100) NOT NULL,
        cantidad_recibida_kg DECIMAL(6,2) NOT NULL,
        tipo_corte VARCHAR(100) NOT NULL,
        gramos_porcion INT NOT NULL,
        cantidad_porciones INT NOT NULL,
        desperdicio_g INT NOT NULL,
        sobras_g INT NOT NULL,
        usuario VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB;",

    // Despiece parrilla: un lote (materia prima, kg recibidos) y varias líneas (reparto + porciones 250/350 + d/s) que deben cuadrar
    "despiece_parrilla_lote" => "CREATE TABLE IF NOT EXISTS despiece_parrilla_lote(
        id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        fecha DATETIME NOT NULL,
        usuario VARCHAR(100) NOT NULL,
        materia_prima VARCHAR(120) NOT NULL DEFAULT '',
        total_kg_recibido DECIMAL(8,3) NOT NULL
    ) ENGINE=InnoDB;",

    "despiece_parrilla_linea" => "CREATE TABLE IF NOT EXISTS despiece_parrilla_linea(
        id            INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        id_lote       INT UNSIGNED NOT NULL,
        id_insumo     INT UNSIGNED NULL,
        materia_prima VARCHAR(120) NOT NULL,
        kg_asignado   DECIMAL(8,3) NOT NULL,
        porciones_250 INT UNSIGNED NOT NULL DEFAULT 0,
        porciones_350 INT UNSIGNED NOT NULL DEFAULT 0,
        desperdicio_g INT UNSIGNED NOT NULL DEFAULT 0,
        sobras_g      INT UNSIGNED NOT NULL DEFAULT 0,
        porciones     INT UNSIGNED NOT NULL DEFAULT 0,
        gramos_porcion INT UNSIGNED NOT NULL DEFAULT 0,
        INDEX idx_despiece_lote (id_lote)
    ) ENGINE=InnoDB;",

    // Receta fija: insumo “padre” → componentes con cantidad por unidad vendida
    "insumo_receta_componente" => "CREATE TABLE IF NOT EXISTS insumo_receta_componente(
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        idInsumoPadre BIGINT UNSIGNED NOT NULL,
        idInsumoHijo BIGINT UNSIGNED NOT NULL,
        cantidad DECIMAL(10,3) NOT NULL DEFAULT 1,
        INDEX idx_receta_padre (idInsumoPadre)
    ) ENGINE=InnoDB;",

    // Plantillas de menú / combo (slots y opciones por insumo del catálogo)
    "combo_plantilla" => "CREATE TABLE IF NOT EXISTS combo_plantilla(
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        nombre VARCHAR(120) NOT NULL,
        descripcion VARCHAR(255) DEFAULT '',
        descuento_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
        activo TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB;",

    "combo_plantilla_slot" => "CREATE TABLE IF NOT EXISTS combo_plantilla_slot(
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        id_plantilla BIGINT UNSIGNED NOT NULL,
        etiqueta VARCHAR(80) NOT NULL,
        orden SMALLINT NOT NULL DEFAULT 0,
        INDEX idx_combo_slot_p (id_plantilla)
    ) ENGINE=InnoDB;",

    "combo_plantilla_opcion" => "CREATE TABLE IF NOT EXISTS combo_plantilla_opcion(
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        id_slot BIGINT UNSIGNED NOT NULL,
        id_insumo BIGINT UNSIGNED NOT NULL,
        INDEX idx_combo_op_slot (id_slot)
    ) ENGINE=InnoDB;",

];

foreach ($tablas as $nombre => $sql) {
    $bd->exec($sql);
    $resultados[] = "Tabla '$nombre' lista";
}

// Migraciones: ampliar enums y columnas sin romper datos existentes
$migraciones = [
    "reservas_estado_enum"  => "ALTER TABLE reservas MODIFY estado ENUM('PENDIENTE','CONFIRMADA','CANCELADA','COMPLETADA','SENTADA','NO-SHOW') DEFAULT 'PENDIENTE'",
    "reservas_adelanto_col" => "ALTER TABLE reservas ADD COLUMN IF NOT EXISTS adelanto DECIMAL(8,2) NOT NULL DEFAULT 0",
    "items_orden_acompanamiento_col" => "ALTER TABLE items_orden ADD COLUMN IF NOT EXISTS acompanamiento_listo TINYINT(1) NOT NULL DEFAULT 0",
    "ventas_idmesa_varchar"         => "ALTER TABLE ventas MODIFY idMesa VARCHAR(20) NOT NULL",
    "reservas_idmesa_varchar"       => "ALTER TABLE reservas MODIFY idMesa VARCHAR(20)",
    // Versiones viejas de MySQL no soportan IF NOT EXISTS en ADD COLUMN; si la columna ya existe, el catch omite el error.
    "despiece_linea_id_insumo"     => "ALTER TABLE despiece_parrilla_linea ADD COLUMN id_insumo INT UNSIGNED NULL DEFAULT NULL AFTER id_lote",
    // Recetas y combos (bases ya creadas sin estas columnas/tablas)
    "insumos_tipoVenta"            => "ALTER TABLE insumos ADD COLUMN tipoVenta VARCHAR(20) NOT NULL DEFAULT 'NORMAL'",
    "insumos_idComboPlantilla"     => "ALTER TABLE insumos ADD COLUMN idComboPlantilla BIGINT UNSIGNED NULL DEFAULT NULL",
    "items_orden_detalle_json"     => "ALTER TABLE items_orden ADD COLUMN detalle_json LONGTEXT NULL DEFAULT NULL",
    "despiece_lote_materia_prima"  => "ALTER TABLE despiece_parrilla_lote ADD COLUMN IF NOT EXISTS materia_prima VARCHAR(120) NOT NULL DEFAULT '' AFTER usuario",
    // Porciones flexibles por insumo (sistema nuevo — backward compat con porciones_250/350)
    "despiece_linea_porciones"     => "ALTER TABLE despiece_parrilla_linea ADD COLUMN  porciones      INT UNSIGNED NOT NULL DEFAULT 0",
    "despiece_linea_gramos_porcion" => "ALTER TABLE despiece_parrilla_linea ADD COLUMN  gramos_porcion INT UNSIGNED NOT NULL DEFAULT 0",

    // ── ÍNDICES DE RENDIMIENTO ─────────────────────────────────────────────────
    // Críticos para TiDB Cloud gratuito: evitan full-table-scan en todas las
    // consultas de reportes, dashboard y SSE de cocina.
    "idx_ventas_fecha"              => "ALTER TABLE ventas ADD INDEX idx_ventas_fecha (fecha)",
    "idx_ventas_idUsuario"          => "ALTER TABLE ventas ADD INDEX idx_ventas_idUsuario (idUsuario)",
    "idx_insumos_venta_idVenta"     => "ALTER TABLE insumos_venta ADD INDEX idx_insumos_venta_idVenta (idVenta)",
    "idx_insumos_venta_idInsumo"    => "ALTER TABLE insumos_venta ADD INDEX idx_insumos_venta_idInsumo (idInsumo)",
    "idx_items_orden_idOrden"       => "ALTER TABLE items_orden ADD INDEX idx_items_orden_idOrden (idOrden)",
    "idx_items_orden_idInsumo"      => "ALTER TABLE items_orden ADD INDEX idx_items_orden_idInsumo (idInsumo)",
    "idx_historial_stock_fecha"     => "ALTER TABLE historial_stock ADD INDEX idx_historial_stock_fecha (fecha)",
    "idx_historial_stock_idInsumo"  => "ALTER TABLE historial_stock ADD INDEX idx_historial_stock_idInsumo (idInsumo)",
    "idx_ordenes_activas_tipo_ref"  => "ALTER TABLE ordenes_activas ADD UNIQUE INDEX idx_ordenes_tipo_ref (tipo, referencia)",
    "idx_reservas_fecha"            => "ALTER TABLE reservas ADD INDEX idx_reservas_fecha (fecha)",
    "idx_cancelaciones_fecha"       => "ALTER TABLE cancelaciones ADD INDEX idx_cancelaciones_fecha (fecha)",
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

