<?php
include_once "funciones.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "JSON inválido"]);
    exit;
}

$fecha = isset($data['fecha']) ? normalizarFechaDespieceMysql($data['fecha']) : '';
$usuario = isset($data['usuario']) ? trim((string) $data['usuario']) : '';
$idUsuarioStock = null;
if (!empty($data['id_usuario'])) {
    $tmp = (int) $data['id_usuario'];
    if ($tmp > 0) {
        $idUsuarioStock = $tmp;
    }
}
$totalKg = isset($data['total_kg_recibido']) ? (float) $data['total_kg_recibido'] : 0;
$lineas = isset($data['lineas']) && is_array($data['lineas']) ? $data['lineas'] : [];

if ($fecha === '' || $usuario === '') {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Faltan fecha o usuario"]);
    exit;
}
if ($totalKg <= 0) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "El total recibido (kg) debe ser mayor que 0"]);
    exit;
}
if (count($lineas) < 1) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Agregá al menos una línea de despiece"]);
    exit;
}

$gPorcion250 = 250;
$gPorcion350 = 350;
$tolKgTotal = 0.02;
$tolGramosLinea = 8;

$sumaKgAsignados = 0.0;
$lineasNorm = [];

foreach ($lineas as $i => $ln) {
    $idInsumo = isset($ln['id_insumo']) ? (int) $ln['id_insumo'] : 0;
    $mp = isset($ln['materia_prima']) ? trim((string) $ln['materia_prima']) : '';
    $kgAsig = isset($ln['kg_asignado']) ? (float) $ln['kg_asignado'] : 0;
    $p250 = isset($ln['porciones_250']) ? (int) $ln['porciones_250'] : 0;
    $p350 = isset($ln['porciones_350']) ? (int) $ln['porciones_350'] : 0;
    $des = isset($ln['desperdicio_g']) ? (int) $ln['desperdicio_g'] : 0;
    $sob = isset($ln['sobras_g']) ? (int) $ln['sobras_g'] : 0;

    if ($idInsumo <= 0) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Línea " . ($i + 1) . ": elegí un insumo del catálogo (búsqueda en la lista)."]);
        exit;
    }
    if ($mp === '') {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Línea " . ($i + 1) . ": falta el nombre del insumo"]);
        exit;
    }
    if ($kgAsig <= 0) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Línea " . ($i + 1) . " ($mp): los kg asignados deben ser mayores que 0"]);
        exit;
    }

    $gramosPorciones = $p250 * $gPorcion250 + $p350 * $gPorcion350;
    $gramosTotalLinea = $gramosPorciones + $des + $sob;
    $esperadoG = (int) round($kgAsig * 1000);

    if (abs($gramosTotalLinea - $esperadoG) > $tolGramosLinea) {
        http_response_code(400);
        echo json_encode([
            "ok" => false,
            "error" => "Línea " . ($i + 1) . " ($mp) no cuadra: porciones 250g/350g + desperdicio + sobras = {$gramosTotalLinea} g y debería ser {$esperadoG} g ({$kgAsig} kg asignados)."
        ]);
        exit;
    }

    $sumaKgAsignados += $kgAsig;
    $lineasNorm[] = [
        'id_insumo' => $idInsumo,
        'materia_prima' => $mp,
        'kg_asignado' => $kgAsig,
        'porciones_250' => $p250,
        'porciones_350' => $p350,
        'desperdicio_g' => $des,
        'sobras_g' => $sob,
    ];
}

if (abs($sumaKgAsignados - $totalKg) > $tolKgTotal) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "error" => "Los kg asignados por línea (" . round($sumaKgAsignados, 3) . " kg) no coinciden con el total recibido (" . round($totalKg, 3) . " kg)."
    ]);
    exit;
}

$bd = conectarBaseDatos();

$stExisteInsumo = $bd->prepare("SELECT id, nombre FROM insumos WHERE id = ? LIMIT 1");
foreach ($lineasNorm as $i => $row) {
    $stExisteInsumo->execute([$row['id_insumo']]);
    $filaIns = $stExisteInsumo->fetch(PDO::FETCH_ASSOC);
    if (!$filaIns) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Línea " . ($i + 1) . ": el insumo no existe en el catálogo."]);
        exit;
    }
}

$bd->beginTransaction();
try {
    $stLote = $bd->prepare(
        "INSERT INTO despiece_parrilla_lote (fecha, usuario, total_kg_recibido) VALUES (?, ?, ?)"
    );
    $stLote->execute([$fecha, $usuario, $totalKg]);
    $idLote = (int) $bd->lastInsertId();

    $stLn = $bd->prepare(
        "INSERT INTO despiece_parrilla_linea
        (id_lote, id_insumo, materia_prima, kg_asignado, porciones_250, porciones_350, desperdicio_g, sobras_g)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($lineasNorm as $row) {
        $stLn->execute([
            $idLote,
            $row['id_insumo'],
            $row['materia_prima'],
            $row['kg_asignado'],
            $row['porciones_250'],
            $row['porciones_350'],
            $row['desperdicio_g'],
            $row['sobras_g'],
        ]);
    }

    $stUpdStock = $bd->prepare("UPDATE insumos SET stock = stock + ? WHERE id = ?");
    $stHist = $bd->prepare(
        "INSERT INTO historial_stock (idInsumo, idUsuario, cantidad, tipo, nota, fecha) VALUES (?, ?, ?, 'AJUSTE', ?, ?)"
    );
    foreach ($lineasNorm as $row) {
        $uds = unidadesStockDesdeLineaDespiece(
            $row['porciones_250'],
            $row['porciones_350']
        );
        if ($uds <= 0) {
            continue;
        }
        $stUpdStock->execute([$uds, $row['id_insumo']]);
        $nota = "Despiece parrilla lote #{$idLote} · {$row['materia_prima']} (+{$uds} u.: {$row['porciones_250']}×250g + {$row['porciones_350']}×350g)";
        $stHist->execute([
            $row['id_insumo'],
            $idUsuarioStock,
            $uds,
            $nota,
            $fecha,
        ]);
    }

    $bd->commit();
    echo json_encode(["ok" => true, "id_lote" => $idLote]);
} catch (Exception $e) {
    $bd->rollBack();
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Error al guardar. ¿Ejecutaste crear_tablas para las tablas nuevas?"]);
}
