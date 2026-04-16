<?php
include_once "funciones.php";
header('Content-Type: application/json');

// Asegurar columnas nuevas (idempotente — solo corre 1 vez por proceso)
_asegurarColumnasDespieceLinea();

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "JSON inválido"]);
    exit;
}

$fecha           = isset($data['fecha'])              ? normalizarFechaDespieceMysql($data['fecha']) : '';
$usuario         = isset($data['usuario'])             ? trim((string) $data['usuario'])            : '';
$idUsuarioStock  = null;
if (!empty($data['id_usuario'])) {
    $tmp = (int) $data['id_usuario'];
    if ($tmp > 0) $idUsuarioStock = $tmp;
}
$materiaPrimaLote = isset($data['materia_prima'])          ? trim((string) $data['materia_prima'])       : '';
$totalKg          = isset($data['total_kg_recibido'])      ? (float) $data['total_kg_recibido']          : 0;
$lineas           = isset($data['lineas']) && is_array($data['lineas']) ? $data['lineas'] : [];

if ($fecha === '' || $usuario === '') {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Faltan fecha o usuario"]);
    exit;
}
if ($materiaPrimaLote === '') {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Falta la materia prima recibida en el lote"]);
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

$tolKgTotal    = 0.02;
$tolGramosLinea = 8;

$sumaKgAsignados = 0.0;
$lineasNorm = [];

foreach ($lineas as $i => $ln) {
    $idInsumo      = isset($ln['id_insumo'])      ? (int)   $ln['id_insumo']      : 0;
    $mp            = isset($ln['materia_prima'])   ? trim((string) $ln['materia_prima']) : '';
    $kgAsig        = isset($ln['kg_asignado'])     ? (float) $ln['kg_asignado']    : 0;
    $gramosPorcion = isset($ln['gramos_porcion'])  ? (int)   $ln['gramos_porcion'] : 0;
    $porciones     = isset($ln['porciones'])       ? (int)   $ln['porciones']      : 0;
    $des           = isset($ln['desperdicio_g'])   ? (int)   $ln['desperdicio_g']  : 0;
    $sob           = isset($ln['sobras_g'])        ? (int)   $ln['sobras_g']       : 0;

    // Compat. hacia atrás: si no traen el nuevo sistema, leer los campos viejos
    $p250          = isset($ln['porciones_250'])   ? (int)   $ln['porciones_250']  : 0;
    $p350          = isset($ln['porciones_350'])   ? (int)   $ln['porciones_350']  : 0;

    if ($idInsumo <= 0) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Línea " . ($i + 1) . ": elegí un insumo del catálogo."]);
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

    $esperadoG = (int) round($kgAsig * 1000);

    // --- Validación: el desglose debe cuadrar con los kg asignados ---
    if ($gramosPorcion > 0) {
        // Sistema nuevo: porciones flexibles
        if ($gramosPorcion < 1) {
            http_response_code(400);
            echo json_encode(["ok" => false, "error" => "Línea " . ($i + 1) . " ($mp): el tamaño de porción debe ser mayor que 0 g"]);
            exit;
        }
        // sobras = lo que queda después de porciones + desperdicio (ya calculado en frontend)
        // Re-calculamos server-side para no confiar ciegamente en el cliente
        $gramosUsados = $porciones * $gramosPorcion + $des;
        $sobCalc      = $esperadoG - $gramosUsados;

        if ($sobCalc < 0) {
            http_response_code(400);
            echo json_encode([
                "ok"    => false,
                "error" => "Línea " . ($i + 1) . " ($mp): las porciones + desperdicio ({$gramosUsados}g) exceden los kg asignados ({$esperadoG}g)."
            ]);
            exit;
        }
        // Aceptar el sob del cliente si está dentro de la tolerancia; si no, usar el calculado
        if (abs($sob - $sobCalc) > $tolGramosLinea) {
            $sob = $sobCalc; // corrección silenciosa
        }
    } else {
        // Sistema viejo: porciones_250 + porciones_350 (fallback backward compat)
        if ($p250 === 0 && $p350 === 0 && $porciones === 0) {
            http_response_code(400);
            echo json_encode(["ok" => false, "error" => "Línea " . ($i + 1) . " ($mp): configurá el tamaño de porción (g) del insumo en el catálogo."]);
            exit;
        }
        $gramosPorcionesOld = $p250 * 250 + $p350 * 350;
        $gramosTotalLinea   = $gramosPorcionesOld + $des + $sob;
        if (abs($gramosTotalLinea - $esperadoG) > $tolGramosLinea) {
            http_response_code(400);
            echo json_encode([
                "ok"    => false,
                "error" => "Línea " . ($i + 1) . " ($mp) no cuadra: total desglose = {$gramosTotalLinea}g, esperado = {$esperadoG}g."
            ]);
            exit;
        }
        // Mapear al nuevo sistema: usar campo porciones con suma antigua
        $porciones     = $p250 + $p350;
        $gramosPorcion = ($porciones > 0) ? (int) round($gramosPorcionesOld / $porciones) : 0;
    }

    $sumaKgAsignados += $kgAsig;
    $lineasNorm[] = [
        'id_insumo'      => $idInsumo,
        'materia_prima'  => $mp,
        'kg_asignado'    => $kgAsig,
        'gramos_porcion' => $gramosPorcion,
        'porciones'      => $porciones,
        'desperdicio_g'  => $des,
        'sobras_g'       => $sob,
        // compatibilidad backward en BD
        'porciones_250'  => $p250,
        'porciones_350'  => $p350,
    ];
}

if (abs($sumaKgAsignados - $totalKg) > $tolKgTotal) {
    http_response_code(400);
    echo json_encode([
        "ok"    => false,
        "error" => "Los kg asignados por línea (" . round($sumaKgAsignados, 3) . " kg) no coinciden con el total recibido (" . round($totalKg, 3) . " kg)."
    ]);
    exit;
}

$bd = conectarBaseDatos();

$stExisteInsumo = $bd->prepare("SELECT id, nombre FROM insumos WHERE id = ? LIMIT 1");
foreach ($lineasNorm as $i => $row) {
    $stExisteInsumo->execute([$row['id_insumo']]);
    if (!$stExisteInsumo->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Línea " . ($i + 1) . ": el insumo no existe en el catálogo."]);
        exit;
    }
}

$bd->beginTransaction();
try {
    // Insertar lote
    $stLote = $bd->prepare(
        "INSERT INTO despiece_parrilla_lote (fecha, usuario, materia_prima, total_kg_recibido) VALUES (?, ?, ?, ?)"
    );
    $stLote->execute([$fecha, $usuario, $materiaPrimaLote, $totalKg]);
    $idLote = (int) $bd->lastInsertId();

    // Insertar líneas con las nuevas columnas
    $stLn = $bd->prepare(
        "INSERT INTO despiece_parrilla_linea
        (id_lote, id_insumo, materia_prima, kg_asignado,
         porciones_250, porciones_350, desperdicio_g, sobras_g,
         porciones, gramos_porcion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
            $row['porciones'],
            $row['gramos_porcion'],
        ]);
    }

    // Actualizar stock: +porciones al insumo correspondiente
    $stUpdStock = $bd->prepare("UPDATE insumos SET stock = stock + ? WHERE id = ?");
    $stHist     = $bd->prepare(
        "INSERT INTO historial_stock (idInsumo, idUsuario, cantidad, tipo, nota, fecha) VALUES (?, ?, ?, 'AJUSTE', ?, ?)"
    );
    foreach ($lineasNorm as $row) {
        $uds = unidadesStockDesdeLineaDespiece(
            $row['porciones_250'],
            $row['porciones_350'],
            $row['porciones']       // nuevo campo prioritario
        );
        if ($uds <= 0) continue;

        $stUpdStock->execute([$uds, $row['id_insumo']]);

        $gp   = $row['gramos_porcion'];
        $nota = "Despiece parrilla lote #{$idLote} · {$row['materia_prima']} (+{$uds} u. × {$gp}g/porc.)";
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
    echo json_encode(["ok" => false, "error" => "Error al guardar: " . $e->getMessage()]);
}
