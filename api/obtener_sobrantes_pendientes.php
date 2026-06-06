<?php
include_once "encabezado.php";
include_once "funciones.php";

// Devuelve lotes de los últimos N días que tengan sobrante_kg > 0 o líneas con sobras_g > 0
// Se usan para mostrar el banner "Sobrante disponible" en el formulario de despiece

$dias = isset($_GET['dias']) ? max(1, min(30, (int) $_GET['dias'])) : 7;

$bd = conectarBaseDatos();

// Lotes con sobrante de pieza entera sin cortar
$stLotes = $bd->prepare("
    SELECT id, fecha, materia_prima, total_kg_recibido, sobrante_kg, usuario
    FROM despiece_parrilla_lote
    WHERE sobrante_kg > 0
      AND DATE(fecha) >= DATE(NOW() - INTERVAL ? DAY)
    ORDER BY fecha DESC
    LIMIT 20
");
$stLotes->execute([$dias]);
$lotes = $stLotes->fetchAll(PDO::FETCH_ASSOC);

// Líneas con sobras_g > 0 de los mismos días (para mostrar el detalle por corte)
$stLineas = $bd->prepare("
    SELECT l.id, l.id_lote, l.materia_prima, l.sobras_g, l.gramos_porcion,
           lo.fecha, lo.materia_prima AS lote_nombre
    FROM despiece_parrilla_linea l
    JOIN despiece_parrilla_lote lo ON lo.id = l.id_lote
    WHERE l.sobras_g > 0
      AND DATE(lo.fecha) >= DATE(NOW() - INTERVAL ? DAY)
    ORDER BY lo.fecha DESC, l.id ASC
    LIMIT 60
");
$stLineas->execute([$dias]);
$lineas = $stLineas->fetchAll(PDO::FETCH_ASSOC);

// Agrupar líneas por id_lote
$lineasPorLote = [];
foreach ($lineas as $ln) {
    $lineasPorLote[(int)$ln['id_lote']][] = $ln;
}

// Construir resultado combinado: un ítem por cada lote con sobrante
// + ítems de lotes que solo tienen sobras en líneas (sobrante_kg = 0)
$resultado = [];

// Lotes con pieza entera sobrante
$idLotesYaIncluidos = [];
foreach ($lotes as $lote) {
    $idLote = (int) $lote['id'];
    $idLotesYaIncluidos[$idLote] = true;
    $resultado[] = [
        'id_lote'          => $idLote,
        'fecha'            => $lote['fecha'],
        'materia_prima'    => $lote['materia_prima'],
        'sobrante_lote_kg' => (float) $lote['sobrante_kg'],
        'sobras_lineas_kg' => isset($lineasPorLote[$idLote])
            ? array_sum(array_column($lineasPorLote[$idLote], 'sobras_g')) / 1000
            : 0,
        'total_reutilizable_kg' => (float) $lote['sobrante_kg'] + (
            isset($lineasPorLote[$idLote])
                ? array_sum(array_column($lineasPorLote[$idLote], 'sobras_g')) / 1000
                : 0
        ),
        'lineas_con_sobras' => $lineasPorLote[$idLote] ?? [],
    ];
}

// Lotes que solo tienen sobras en líneas (sobrante_kg = 0 pero líneas con sobras)
foreach ($lineasPorLote as $idLote => $lns) {
    if (isset($idLotesYaIncluidos[$idLote])) continue;
    $primerLn = $lns[0];
    $sobrasKg = array_sum(array_column($lns, 'sobras_g')) / 1000;
    if ($sobrasKg <= 0) continue;
    $resultado[] = [
        'id_lote'               => $idLote,
        'fecha'                 => $primerLn['fecha'],
        'materia_prima'         => $primerLn['lote_nombre'],
        'sobrante_lote_kg'      => 0,
        'sobras_lineas_kg'      => $sobrasKg,
        'total_reutilizable_kg' => $sobrasKg,
        'lineas_con_sobras'     => $lns,
    ];
}

// Ordenar por fecha desc
usort($resultado, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));

echo json_encode(['ok' => true, 'datos' => $resultado], JSON_UNESCAPED_UNICODE);
