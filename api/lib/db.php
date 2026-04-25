<?php
// lib/db.php - Conexión y Esquema de Base de Datos

function conectarBaseDatos()
{
    static $instance = null;
    if ($instance !== null) {
        return $instance;
    }

    require_once __DIR__ . '/../db_config.php';
    $host    = DB_HOST;
    $db      = DB_NAME;
    $user    = DB_USER;
    $pass    = DB_PASS;
    $port    = DB_PORT;
    $charset = DB_CHARSET;

    $options = [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (DB_SSL) {
        // Bundle CA del sistema (Debian/Ubuntu en Render/Docker)
        $caBundle = '/etc/ssl/certs/ca-certificates.crt';
        if (file_exists($caBundle)) {
            $options[\PDO::MYSQL_ATTR_SSL_CA] = $caBundle;
        }
        $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    try {
        $instance = new \PDO($dsn, $user, $pass, $options);
        $instance->exec("SET time_zone = '+00:00'");
        return $instance;
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}

function verificarTablas()
{
    $bd = conectarBaseDatos();
    $sentencia  = $bd->query("SELECT COUNT(*) AS resultado FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'botanero_ventas'");
    return $sentencia->fetchAll();
}

// Funciones de aseguramiento de esquema (Migraciones internas)

function _asegurarCreatedAtOrdenes()
{
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ordenes_activas' AND COLUMN_NAME='created_at'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE ordenes_activas ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _asegurarTipoOrdenOrdenes()
{
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ordenes_activas' AND COLUMN_NAME='tipo_orden'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE ordenes_activas ADD COLUMN tipo_orden VARCHAR(20) NOT NULL DEFAULT 'LOCAL'");
            $bd->exec("UPDATE ordenes_activas SET tipo_orden = 'DELIVERY' WHERE tipo = 'DELIVERY'");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _asegurarTipoItemsOrden()
{
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items_orden' AND COLUMN_NAME='tipo'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE items_orden ADD COLUMN tipo VARCHAR(30) NOT NULL DEFAULT 'PLATILLO'");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _asegurarPagadoItemsOrden()
{
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items_orden' AND COLUMN_NAME='pagado'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE items_orden ADD COLUMN pagado TINYINT(1) NOT NULL DEFAULT 0");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _asegurarAcompanamientoItemsOrden()
{
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items_orden' AND COLUMN_NAME='acompanamiento_listo'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE items_orden ADD COLUMN acompanamiento_listo TINYINT(1) NOT NULL DEFAULT 0");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _asegurarTipoVentaInsumos()
{
    static $verificado = false;
    if ($verificado) {
        return;
    }
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='insumos' AND COLUMN_NAME='tipoVenta'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE insumos ADD COLUMN tipoVenta VARCHAR(20) NOT NULL DEFAULT 'NORMAL'");
        }
        $existe2 = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='insumos' AND COLUMN_NAME='idComboPlantilla'")->fetchColumn();
        if (!$existe2) {
            $bd->exec("ALTER TABLE insumos ADD COLUMN idComboPlantilla BIGINT UNSIGNED NULL DEFAULT NULL");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _asegurarDetalleJsonItemsOrden()
{
    static $verificado = false;
    if ($verificado) {
        return;
    }
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $existe = $bd->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='items_orden' AND COLUMN_NAME='detalle_json'")->fetchColumn();
        if (!$existe) {
            $bd->exec("ALTER TABLE items_orden ADD COLUMN detalle_json LONGTEXT NULL DEFAULT NULL");
        }
    } catch (\Exception $e) { /* silencioso */
    }
}

function _asegurarColumnasDespieceLinea() {
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;
    try {
        $bd = conectarBaseDatos();
        $bd->exec("
            ALTER TABLE despiece_parrilla_linea
              ADD COLUMN IF NOT EXISTS porciones       INT UNSIGNED NOT NULL DEFAULT 0,
              ADD COLUMN IF NOT EXISTS gramos_porcion  INT UNSIGNED NOT NULL DEFAULT 0
        ");
    } catch (\Exception $e) { /* silencioso — si ya existen no falla */ }
}
