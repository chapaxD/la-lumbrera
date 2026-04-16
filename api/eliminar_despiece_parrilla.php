<?php
include_once "funciones.php";
include_once __DIR__ . "/verificar_token.php";
header('Content-Type: application/json');

if (($tokenDatos['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Solo los administradores pueden eliminar registros de despiece.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$idLote = isset($data['id_lote']) ? (int) $data['id_lote'] : 0;
if ($idLote <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'id_lote inválido']);
    exit;
}

$bd = conectarBaseDatos();
$bd->beginTransaction();
try {
    $stSel = $bd->prepare(
        "SELECT id_insumo, materia_prima, porciones_250, porciones_350, porciones, gramos_porcion, sobras_g FROM despiece_parrilla_linea WHERE id_lote = ?"
    );
    $stSel->execute([$idLote]);
    $lineasLote = $stSel->fetchAll(PDO::FETCH_ASSOC);

    $stLoteEx = $bd->prepare("SELECT fecha FROM despiece_parrilla_lote WHERE id = ?");
    $stLoteEx->execute([$idLote]);
    $filaLote = $stLoteEx->fetch(PDO::FETCH_ASSOC);
    if (!$filaLote) {
        $bd->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'No existe ese registro de despiece.']);
        exit;
    }
    $fechaHist = date('Y-m-d H:i:s');
    $idUserTok = isset($tokenDatos['idUsuario']) ? (int) $tokenDatos['idUsuario'] : null;
    if ($idUserTok <= 0) {
        $idUserTok = null;
    }

    $stUpdRev = $bd->prepare("UPDATE insumos SET stock = GREATEST(0, stock - ?) WHERE id = ?");
    $stHistRev = $bd->prepare(
        "INSERT INTO historial_stock (idInsumo, idUsuario, cantidad, tipo, nota, fecha) VALUES (?, ?, ?, 'AJUSTE', ?, ?)"
    );
    foreach ($lineasLote as $ln) {
        $uds = unidadesStockDesdeLineaDespiece(
            $ln['porciones_250'] ?? 0,
            $ln['porciones_350'] ?? 0,
            $ln['porciones']     ?? 0   // nuevo campo, prioritario
        );
        if ($uds <= 0) {
            continue;
        }
        $stUpdRev->execute([$uds, $ln['id_insumo']]);
        $mp = isset($ln['materia_prima']) ? $ln['materia_prima'] : '';
        $stHistRev->execute([
            $ln['id_insumo'],
            $idUserTok,
            -$uds,
            "Reversa despiece parrilla lote #{$idLote} · {$mp} (-{$uds} u.)",
            $fechaHist,
        ]);
    }

    $stLineas = $bd->prepare("DELETE FROM despiece_parrilla_linea WHERE id_lote = ?");
    $stLineas->execute([$idLote]);

    $stLote = $bd->prepare("DELETE FROM despiece_parrilla_lote WHERE id = ?");
    $stLote->execute([$idLote]);
    if ($stLote->rowCount() === 0) {
        $bd->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'No existe ese registro de despiece.']);
        exit;
    }
    $bd->commit();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    $bd->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo eliminar.']);
}
