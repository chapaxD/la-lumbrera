<?php
include_once "funciones.php";

$fechaInicio = isset($_GET['fechaInicio']) ? $_GET['fechaInicio'] : null;
$fechaFin = isset($_GET['fechaFin']) ? $_GET['fechaFin'] : null;

$lotes = obtenerDespieceParrilla($fechaInicio, $fechaFin);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Despiece de Parrilla</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Reporte de Despiece de Parrilla</h2>
    <form method="get">
        <label>Fecha inicio: <input type="date" name="fechaInicio" value="<?= htmlspecialchars((string) $fechaInicio) ?>"></label>
        <label>Fecha fin: <input type="date" name="fechaFin" value="<?= htmlspecialchars((string) $fechaFin) ?>"></label>
        <button type="submit">Filtrar</button>
    </form>
    <br>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Total kg rec.</th>
                <th>Usuario</th>
                <th>Id insumo</th>
                <th>Materia / corte</th>
                <th>Kg línea</th>
                <th>Porc. 250 g</th>
                <th>Porc. 350 g</th>
                <th>Desp. (g)</th>
                <th>Sobras (g)</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($lotes)): ?>
            <tr><td colspan="10">No hay registros</td></tr>
        <?php else: ?>
            <?php foreach ($lotes as $lote): ?>
                <?php if (!empty($lote['lineas'])): ?>
                    <?php $n = count($lote['lineas']); $i = 0; ?>
                    <?php foreach ($lote['lineas'] as $ln): ?>
                        <tr>
                            <?php if ($i === 0): ?>
                                <td rowspan="<?= (int) $n ?>"><?= htmlspecialchars((string) $lote['fecha']) ?></td>
                                <td rowspan="<?= (int) $n ?>"><?= htmlspecialchars((string) $lote['total_kg_recibido']) ?></td>
                                <td rowspan="<?= (int) $n ?>"><?= htmlspecialchars((string) $lote['usuario']) ?></td>
                            <?php endif; ?>
                            <td><?= isset($ln['id_insumo']) && $ln['id_insumo'] !== null && $ln['id_insumo'] !== '' ? htmlspecialchars((string) $ln['id_insumo']) : '—' ?></td>
                            <td><?= htmlspecialchars((string) $ln['materia_prima']) ?></td>
                            <td><?= htmlspecialchars((string) $ln['kg_asignado']) ?></td>
                            <td><?= htmlspecialchars((string) $ln['porciones_250']) ?></td>
                            <td><?= htmlspecialchars((string) $ln['porciones_350']) ?></td>
                            <td><?= htmlspecialchars((string) $ln['desperdicio_g']) ?></td>
                            <td><?= htmlspecialchars((string) $ln['sobras_g']) ?></td>
                        </tr>
                        <?php $i++; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $lote['fecha']) ?></td>
                        <td><?= htmlspecialchars((string) $lote['total_kg_recibido']) ?></td>
                        <td><?= htmlspecialchars((string) $lote['usuario']) ?></td>
                        <td colspan="7">Sin líneas</td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
