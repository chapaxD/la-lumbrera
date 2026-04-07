<?php
require_once 'api/db_config.php';
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "ALTER TABLE reservas ADD COLUMN IF NOT EXISTS adelanto DECIMAL(8,2) NOT NULL DEFAULT 0";
    $pdo->exec($sql);
    echo "COLUMNA ADELANTO AGREGADA CORRECTAMENTE";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
