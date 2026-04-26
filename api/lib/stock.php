<?php
// lib/stock.php - Gestión de Inventario, Recetas y Combos

// ─── INSUMOS / PRODUCTOS ──────────────────────────────────────────────────────

function obtenerInsumos($filtros)
{
    $bd = conectarBaseDatos();
    $valoresAEjecutar = [];
    $sql = "SELECT insumos.*, IFNULL(categorias.nombre, 'NO DEFINIDA') AS categoria
	FROM insumos
	LEFT JOIN categorias ON categorias.id = insumos.categoria WHERE 1 ";

    if ($filtros->tipo != "") {
        $sql .= " AND  insumos.tipo = ?";
        array_push($valoresAEjecutar, $filtros->tipo);
    }

    if ($filtros->categoria != "") {
        $sql .= " AND  insumos.categoria = ?";
        array_push($valoresAEjecutar, $filtros->categoria);
    }

    if ($filtros->nombre != "") {
        $sql .= " AND (LOWER(insumos.nombre) LIKE LOWER(?) OR LOWER(insumos.codigo) LIKE LOWER(?))";
        array_push($valoresAEjecutar, '%' . $filtros->nombre . '%');
        array_push($valoresAEjecutar, '%' . $filtros->nombre . '%');
    }

    $sentencia = $bd->prepare($sql);
    $sentencia->execute($valoresAEjecutar);
    return $sentencia->fetchAll();
}

function obtenerInsumoPorId($idInsumo)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT * FROM insumos WHERE id = ?");
    $sentencia->execute([$idInsumo]);
    $obj = $sentencia->fetchObject();
    if ($obj) {
        $obj->receta = obtenerRecetaComponentesPorPadre($idInsumo);
    }
    return $obj;
}

function obtenerInsumosPorNombre($insumo, $ajustarStockVenta = false)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT insumos.*, IFNULL(categorias.nombre, 'NO DEFINIDA') AS categoria
	FROM insumos
	LEFT JOIN categorias ON categorias.id = insumos.categoria 
	WHERE LOWER(insumos.nombre) LIKE LOWER(?) ");
    $sentencia->execute(['%' . $insumo . '%']);
    $filas = $sentencia->fetchAll();
    if ($ajustarStockVenta) {
        ajustarStockDisponibleVentaEnFilas($bd, $filas);
    }
    return $filas;
}

function registrarInsumo($insumo)
{
    $bd = conectarBaseDatos();
    $tipoVenta = isset($insumo->tipoVenta) ? $insumo->tipoVenta : 'NORMAL';
    $idCombo = property_exists($insumo, 'idComboPlantilla') ? $insumo->idComboPlantilla : null;
    if ($idCombo === '' || $idCombo === false) {
        $idCombo = null;
    }
    $sentencia = $bd->prepare("INSERT INTO insumos (codigo, nombre, descripcion, precio, tipo, categoria, stock, stockMinimo, stockMateria, tipoCorte, tipoVenta, idComboPlantilla) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $ok = $sentencia->execute([$insumo->codigo, $insumo->nombre, $insumo->descripcion, $insumo->precio, $insumo->tipo, $insumo->categoria, $insumo->stock ?? 0, $insumo->stockMinimo ?? 0, $insumo->stockMateria ?? 0, $insumo->tipoCorte ?? 0, $tipoVenta, $idCombo]);
    if (!$ok) {
        return false;
    }
    $nuevoId = (int) $bd->lastInsertId();
    if ($nuevoId > 0 && strtoupper((string) $tipoVenta) === 'RECETA' && isset($insumo->receta)) {
        guardarRecetaInsumo($nuevoId, $insumo->receta);
    }
    return true;
}

function editarInsumo($insumo)
{
    $bd = conectarBaseDatos();

    $antiguo = $bd->prepare("SELECT stock FROM insumos WHERE id = ?");
    $antiguo->execute([$insumo->id]);
    $viejo = $antiguo->fetch();
    $stockViejo = $viejo ? $viejo->stock : 0;

    $tipoVenta = isset($insumo->tipoVenta) ? $insumo->tipoVenta : 'NORMAL';
    $idCombo = property_exists($insumo, 'idComboPlantilla') ? $insumo->idComboPlantilla : null;
    if ($idCombo === '' || $idCombo === false) {
        $idCombo = null;
    }

    $sentencia = $bd->prepare("UPDATE insumos SET tipo = ?, codigo = ?, nombre = ?, descripcion = ?, categoria = ?, precio = ?, stock = ?, stockMinimo = ?, stockMateria = ?, tipoCorte = ?, tipoVenta = ?, idComboPlantilla = ? WHERE id = ?");
    $resultado = $sentencia->execute([$insumo->tipo, $insumo->codigo, $insumo->nombre, $insumo->descripcion, $insumo->categoria, $insumo->precio, $insumo->stock ?? 0, $insumo->stockMinimo ?? 0, $insumo->stockMateria ?? 0, $insumo->tipoCorte ?? 0, $tipoVenta, $idCombo, $insumo->id]);

    if ($resultado) {
        $tvR = strtoupper((string) ($insumo->tipoVenta ?? 'NORMAL'));
        if ($tvR === 'RECETA' && isset($insumo->receta)) {
            guardarRecetaInsumo($insumo->id, $insumo->receta);
        } elseif ($tvR !== 'RECETA') {
            guardarRecetaInsumo($insumo->id, []);
        }
    }

    if ($resultado && isset($insumo->idUsuario)) {
        $diferencia = ($insumo->stock ?? 0) - $stockViejo;
        if ($diferencia != 0) {
            $movimiento = $bd->prepare("INSERT INTO historial_stock (idInsumo, idUsuario, cantidad, tipo, fecha) VALUES (?, ?, ?, 'AJUSTE', ?)");
            $movimiento->execute([$insumo->id, $insumo->idUsuario, $diferencia, date("Y-m-d H:i:s")]);
        }
    }
    return $resultado;
}

function eliminarInsumo($idInsumo)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("DELETE FROM insumos WHERE id = ?");
    return $sentencia->execute([$idInsumo]);
}

function obtenerNumeroInsumos()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT COUNT(*) AS numeroInsumos FROM insumos");
    return $sentencia->fetchObject()->numeroInsumos;
}

function obtenerInsumosBajoStock()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT * FROM insumos WHERE stock <= stockMinimo AND stockMinimo > 0 ORDER BY stock ASC");
    return $sentencia->fetchAll();
}

// ─── CATEGORÍAS ───────────────────────────────────────────────────────────────

function obtenerCategorias()
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT * FROM categorias ORDER BY id DESC");
    return $sentencia->fetchAll();
}

function obtenerCategoriasPorTipo($tipo)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT * FROM categorias WHERE tipo = ?");
    $sentencia->execute([$tipo]);
    return $sentencia->fetchAll();
}

function registrarCategoria($categoria)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("INSERT INTO categorias (tipo, nombre, descripcion) VALUES (?,?,?)");
    return $sentencia->execute([$categoria->tipo, $categoria->nombre, $categoria->descripcion]);
}

function editarCategoria($categoria)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("UPDATE categorias SET tipo = ?, nombre = ?, descripcion = ? WHERE id = ?");
    return $sentencia->execute([$categoria->tipo, $categoria->nombre, $categoria->descripcion, $categoria->id]);
}

function eliminarCategoria($idCategoria)
{
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("DELETE FROM categorias WHERE id = ?");
    return $sentencia->execute([$idCategoria]);
}

// ─── LÓGICA DE STOCK Y RECETAS ─────────────────────────────────────────────────

function _mergeCantidadesMapa(&$dest, $src)
{
    foreach ($src as $id => $q) {
        $iid = (int) $id;
        $qq = (float) $q;
        if ($iid <= 0 || $qq <= 0) continue;
        $dest[$iid] = ($dest[$iid] ?? 0) + $qq;
    }
}

function expandirRecetaInsumo($bd, $idPadre, $multCantidad)
{
    $idPadre = (int) $idPadre;
    $multCantidad = (float) $multCantidad;
    if ($idPadre <= 0 || $multCantidad <= 0) return [];
    $st = $bd->prepare("SELECT idInsumoHijo, cantidad FROM insumo_receta_componente WHERE idInsumoPadre = ?");
    $st->execute([$idPadre]);
    $map = [];
    foreach ($st->fetchAll(PDO::FETCH_OBJ) as $r) {
        $hid = (int) $r->idInsumoHijo;
        $q = (float) $r->cantidad * $multCantidad;
        if ($hid > 0 && $q > 0) {
            $map[$hid] = ($map[$hid] ?? 0) + $q;
        }
    }
    if (count($map) === 0) return [$idPadre => $multCantidad];
    return $map;
}

function expandirComboDesdeDetalle($detalleRaw, $cantidadLinea)
{
    $cantidadLinea = (int) $cantidadLinea;
    if ($cantidadLinea < 1) $cantidadLinea = 1;
    if ($detalleRaw === null || $detalleRaw === '') return [];
    $det = is_string($detalleRaw) ? json_decode($detalleRaw, true) : (is_object($detalleRaw) ? json_decode(json_encode($detalleRaw), true) : $detalleRaw);
    if (!is_array($det)) return [];
    $menus = $det['menus'] ?? [];
    if (!is_array($menus) || count($menus) !== $cantidadLinea) return [];
    $map = [];
    foreach ($menus as $menu) {
        if (!is_array($menu)) continue;
        $slots = $menu['slots'] ?? $menu['s'] ?? [];
        if (!is_array($slots)) continue;
        foreach ($slots as $idInsumoElegido) {
            $hid = (int) $idInsumoElegido;
            if ($hid > 0) $map[$hid] = ($map[$hid] ?? 0) + 1;
        }
    }
    return $map;
}

function expandirNecesidadesLineaPedido($bd, $linea)
{
    $arr = is_array($linea) ? $linea : (array) $linea;
    $id = (int) ($arr['id'] ?? 0);
    if ($id <= 0) return [];
    $cant = isset($arr['cantidad']) ? (float) $arr['cantidad'] : 1;
    if ($cant < 0) $cant = 0;
    $tipoVenta = strtoupper(trim((string) ($arr['tipoVenta'] ?? $arr['tipo_venta'] ?? '')));
    if ($tipoVenta === '') {
        $st = $bd->prepare("SELECT tipoVenta FROM insumos WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_OBJ);
        $tipoVenta = strtoupper($row->tipoVenta ?? 'NORMAL');
    }
    if ($tipoVenta === 'RECETA') return expandirRecetaInsumo($bd, $id, $cant);
    if ($tipoVenta === 'COMBO') {
        $det = $arr['detalleJson'] ?? $arr['detalle_json'] ?? null;
        return expandirComboDesdeDetalle($det, (int) round($cant));
    }
    return [$id => $cant];
}

function expandirNecesidadesDesdeFilaItemOrden($bd, $row)
{
    $o = is_object($row) ? $row : (object) $row;
    $linea = [
        'id' => (int) ($o->idInsumo ?? 0),
        'cantidad' => isset($o->cantidad) ? (float) $o->cantidad : 1,
        'tipoVenta' => $o->tipoVenta ?? null,
        'detalle_json' => $o->detalle_json ?? null,
    ];
    return expandirNecesidadesLineaPedido($bd, $linea);
}

function aplicarDescuentoStockPorMapa($bd, $mapa, $idUsuario, $tipoHistorial = 'VENTA', $nota = null)
{
    foreach ($mapa as $idInsumo => $qty) {
        $iid = (int) $idInsumo;
        $q = (float) $qty;
        if ($iid <= 0 || $q <= 0) continue;
        $bd->prepare("UPDATE insumos SET stock = GREATEST(0, stock - ?) WHERE id = ?")->execute([$q, $iid]);
        $mov = $bd->prepare("INSERT INTO historial_stock (idInsumo, idUsuario, cantidad, tipo, nota, fecha) VALUES (?, ?, ?, ?, ?, ?)");
        $mov->execute([$iid, $idUsuario, -$q, $tipoHistorial, $nota, date('Y-m-d H:i:s')]);
    }
}

function obtenerRecetaComponentesPorPadre($idPadre)
{
    $bd = conectarBaseDatos();
    $st = $bd->prepare("SELECT idInsumoHijo, cantidad FROM insumo_receta_componente WHERE idInsumoPadre = ? ORDER BY idInsumoHijo ASC");
    $st->execute([(int) $idPadre]);
    return $st->fetchAll(PDO::FETCH_OBJ);
}

function guardarRecetaInsumo($idPadre, $receta)
{
    $bd = conectarBaseDatos();
    $idPadre = (int) $idPadre;
    $bd->prepare("DELETE FROM insumo_receta_componente WHERE idInsumoPadre = ?")->execute([$idPadre]);
    if (!is_array($receta) && !is_object($receta)) return true;
    $ins = $bd->prepare("INSERT INTO insumo_receta_componente (idInsumoPadre, idInsumoHijo, cantidad) VALUES (?,?,?)");
    foreach ($receta as $r) {
        $r = is_object($r) ? get_object_vars($r) : (array) $r;
        $hijo = (int) ($r['idInsumoHijo'] ?? $r['id'] ?? 0);
        $cant = isset($r['cantidad']) ? (float) $r['cantidad'] : 1;
        if ($hijo <= 0 || $cant <= 0) continue;
        $ins->execute([$idPadre, $hijo, $cant]);
    }
    return true;
}

// ─── COMBOS Y PLANTILLAS ─────────────────────────────────────────────────────

function obtenerPlantillasCombo()
{
    $bd = conectarBaseDatos();
    $plantillas = $bd->query("SELECT * FROM combo_plantilla ORDER BY nombre ASC")->fetchAll(PDO::FETCH_OBJ);
    foreach ($plantillas as $p) {
        _cargarSlotsPlantillaCombo($bd, $p);
        // Cargar insumos que usan esta plantilla
        $stI = $bd->prepare("SELECT id, nombre FROM insumos WHERE idComboPlantilla = ?");
        $stI->execute([$p->id]);
        $p->insumos_vinculados = $stI->fetchAll();
    }
    return $plantillas;
}

function _cargarSlotsPlantillaCombo($bd, $p)
{
    $st = $bd->prepare("SELECT * FROM combo_plantilla_slot WHERE id_plantilla = ? ORDER BY orden ASC, id ASC");
    $st->execute([(int) $p->id]);
    $p->slots = $st->fetchAll(PDO::FETCH_OBJ);
    foreach ($p->slots as $s) {
        $so = $bd->prepare("SELECT o.id, o.id_insumo, i.nombre AS nombre_insumo, i.stock, i.tipoVenta FROM combo_plantilla_opcion o LEFT JOIN insumos i ON i.id = o.id_insumo WHERE o.id_slot = ? ORDER BY o.id ASC");
        $so->execute([(int) $s->id]);
        $opciones = $so->fetchAll(PDO::FETCH_OBJ);
        foreach ($opciones as $op) {
            $tmpArr = [(object)['id' => $op->id_insumo, 'stock' => $op->stock, 'tipoVenta' => $op->tipoVenta]];
            ajustarStockDisponibleVentaEnFilas($bd, $tmpArr);
            $op->stock = (float)$tmpArr[0]->stock;
            unset($op->tipoVenta);
        }
        $s->opciones = $opciones;
    }
}

function obtenerPlantillaComboPorId($id)
{
    $bd = conectarBaseDatos();
    $st = $bd->prepare("SELECT * FROM combo_plantilla WHERE id = ?");
    $st->execute([(int)$id]);
    $p = $st->fetch(PDO::FETCH_OBJ);
    if (!$p) return null;
    _cargarSlotsPlantillaCombo($bd, $p);
    return $p;
}

function guardarPlantillaCombo($payload)
{
    $bd = conectarBaseDatos();
    $p = (array)$payload;
    $nombre = trim($p['nombre'] ?? '');
    if ($nombre === '') return false;
    $desc = $p['descripcion'] ?? '';
    $descPct = (float)($p['descuento_pct'] ?? 0);
    $activo = (int)($p['activo'] ?? 1);
    $idPlantilla = (int)($p['id'] ?? 0);

    $bd->beginTransaction();
    try {
        if ($idPlantilla > 0) {
            $bd->prepare("UPDATE combo_plantilla SET nombre=?, descripcion=?, descuento_pct=?, activo=? WHERE id=?")
                ->execute([$nombre, $desc, $descPct, $activo, $idPlantilla]);
            $stSlots = $bd->prepare("SELECT id FROM combo_plantilla_slot WHERE id_plantilla = ?");
            $stSlots->execute([$idPlantilla]);
            foreach ($stSlots->fetchAll(PDO::FETCH_COLUMN) as $sid) {
                $bd->prepare("DELETE FROM combo_plantilla_opcion WHERE id_slot = ?")->execute([$sid]);
            }
            $bd->prepare("DELETE FROM combo_plantilla_slot WHERE id_plantilla = ?")->execute([$idPlantilla]);
        } else {
            $bd->prepare("INSERT INTO combo_plantilla (nombre, descripcion, descuento_pct, activo) VALUES (?,?,?,?)")
                ->execute([$nombre, $desc, $descPct, $activo]);
            $idPlantilla = (int)$bd->lastInsertId();
        }

        $slots = $p['slots'] ?? [];
        foreach ($slots as $idx => $slot) {
            $slot = (array)$slot;
            $et = trim($slot['etiqueta'] ?? '');
            if ($et === '') continue;
            $bd->prepare("INSERT INTO combo_plantilla_slot (id_plantilla, etiqueta, orden) VALUES (?,?,?)")
                ->execute([$idPlantilla, $et, $slot['orden'] ?? $idx]);
            $idSlot = $bd->lastInsertId();
            $ops = $slot['opciones'] ?? [];
            foreach ($ops as $op) {
                $op = (array)$op;
                $idi = (int)($op['id_insumo'] ?? $op['idInsumo'] ?? 0);
                if ($idi > 0) $bd->prepare("INSERT INTO combo_plantilla_opcion (id_slot, id_insumo) VALUES (?,?)")->execute([$idSlot, $idi]);
            }
        }
        $bd->commit();
        return $idPlantilla;
    } catch (\Exception $e) {
        $bd->rollBack();
        throw $e;
    }
}

function eliminarPlantillaCombo($id)
{
    $bd = conectarBaseDatos();
    $id = (int)$id;
    $stSlots = $bd->prepare("SELECT id FROM combo_plantilla_slot WHERE id_plantilla = ?");
    $stSlots->execute([$id]);
    foreach ($stSlots->fetchAll(PDO::FETCH_COLUMN) as $sid) {
        $bd->prepare("DELETE FROM combo_plantilla_opcion WHERE id_slot = ?")->execute([$sid]);
    }
    $bd->prepare("DELETE FROM combo_plantilla_slot WHERE id_plantilla = ?")->execute([$id]);
    $bd->prepare("DELETE FROM combo_plantilla WHERE id = ?")->execute([$id]);
    return true;
}

// ─── LÓGICA DE STOCK DISPONIBLE (CONCURRENCIA) ────────────────────────────────

function obtenerMapaStockReservadoEnOrdenesActivas($bd)
{
    $sentencia = $bd->query("
        SELECT io.idInsumo, io.cantidad, io.detalle_json, IFNULL(i.tipoVenta, 'NORMAL') AS tipoVenta
        FROM items_orden io
        INNER JOIN ordenes_activas oa ON oa.id = io.idOrden
        LEFT JOIN insumos i ON i.id = io.idInsumo
        WHERE io.idInsumo IS NOT NULL AND io.idInsumo > 0
    ");
    $mapa = [];
    foreach ($sentencia->fetchAll(PDO::FETCH_OBJ) as $row) {
        $ex = expandirNecesidadesDesdeFilaItemOrden($bd, $row);
        _mergeCantidadesMapa($mapa, $ex);
    }
    return $mapa;
}

function obtenerMapaCantidadesItemsOrden($bd, $idOrden)
{
    $st = $bd->prepare("
        SELECT io.idInsumo, io.cantidad, io.detalle_json, IFNULL(i.tipoVenta, 'NORMAL') AS tipoVenta
        FROM items_orden io
        LEFT JOIN insumos i ON i.id = io.idInsumo
        WHERE io.idOrden = ? AND io.idInsumo IS NOT NULL AND io.idInsumo > 0
    ");
    $st->execute([(int) $idOrden]);
    $m = [];
    foreach ($st->fetchAll(PDO::FETCH_OBJ) as $r) {
        $ex = expandirNecesidadesDesdeFilaItemOrden($bd, $r);
        _mergeCantidadesMapa($m, $ex);
    }
    return $m;
}

function ajustarStockDisponibleVentaEnFilas($bd, $filas)
{
    if (!$filas || count($filas) === 0) return $filas;
    $mapa = obtenerMapaStockReservadoEnOrdenesActivas($bd);
    foreach ($filas as $row) {
        $id = (int) ($row->id ?? 0);
        if ($id <= 0) continue;
        $tv = strtoupper($row->tipoVenta ?? 'NORMAL');
        if ($tv === 'RECETA') {
            $nec = expandirRecetaInsumo($bd, $id, 1);
            if (count($nec) > 0 && !(count($nec) === 1 && isset($nec[$id]))) {
                $minUnidades = null;
                foreach ($nec as $hid => $needPer) {
                    $needPer = (float)$needPer;
                    if ($needPer <= 0) continue;
                    $st = $bd->prepare('SELECT stock FROM insumos WHERE id = ?');
                    $st->execute([(int)$hid]);
                    $inv = $st->fetch(PDO::FETCH_OBJ);
                    $stockFisico = $inv ? (float)$inv->stock : 0;
                    $libre = max(0, $stockFisico - (float)($mapa[(int)$hid] ?? 0));
                    $unidades = (int)floor($libre / $needPer);
                    $minUnidades = ($minUnidades === null) ? $unidades : min($minUnidades, $unidades);
                }
                $row->stock = max(0, (int)($minUnidades ?? 0));
                continue;
            }
        }
        $row->stock = max(0, (int)floor((float)($row->stock ?? 0) - (float)($mapa[$id] ?? 0)));
    }
    return $filas;
}

function validarStockDisponibleParaItemsOrden($bd, $insumosPayload, $idOrdenExcluir = null)
{
    if ($insumosPayload === null || !is_iterable($insumosPayload)) return true;
    $porId = [];
    foreach ($insumosPayload as $insumo) {
        $i = (array)$insumo;
        $id = (int)($i['id'] ?? 0);
        if ($id <= 0) continue;
        $ex = expandirNecesidadesLineaPedido($bd, $i);
        if (strtoupper($i['tipoVenta'] ?? '') === 'COMBO' && count($ex) === 0) return (object) ['error' => 'stock', 'mensaje' => 'Menú combo incompleto.'];
        _mergeCantidadesMapa($porId, $ex);
    }
    if (count($porId) === 0) return true;
    $mapaReservado = obtenerMapaStockReservadoEnOrdenesActivas($bd);
    if ($idOrdenExcluir) {
        $mapaActual = obtenerMapaCantidadesItemsOrden($bd, (int)$idOrdenExcluir);
        foreach ($mapaActual as $idInsumo => $c) $mapaReservado[$idInsumo] = max(0, ($mapaReservado[$idInsumo] ?? 0) - $c);
    }
    foreach ($porId as $idInsumo => $necesita) {
        $stmt = $bd->prepare('SELECT stock, nombre FROM insumos WHERE id = ?');
        $stmt->execute([$idInsumo]);
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$row) return (object) ['error' => 'stock', 'mensaje' => 'Producto no encontrado.'];
        $libre = (float)$row->stock - (float)($mapaReservado[$idInsumo] ?? 0);
        if ($necesita > $libre) {
            $nom = $row->nombre ?? 'El producto';
            return (object) ['error' => 'stock', 'mensaje' => "Stock insuficiente para «{$nom}»: quedan {$libre} disponibles."];
        }
    }
    return true;
}

// ─── HISTORIAL Y MOVIMIENTOS ───────────────────────────────────────────────────

function obtenerHistorialStock($fechaInicio = null, $fechaFin = null, $limite = 500)
{
    $bd = conectarBaseDatos();
    $valores = [];
    $where = '';
    if ($fechaInicio && $fechaFin) {
        $where = 'WHERE h.fecha >= ? AND h.fecha < DATE_ADD(?, INTERVAL 1 DAY)';
        $valores = [$fechaInicio, $fechaFin];
    }
    $sentencia = $bd->prepare("SELECT h.*, i.nombre as insumoNombre, u.nombre as usuarioNombre FROM historial_stock h LEFT JOIN insumos i ON h.idInsumo = i.id LEFT JOIN usuarios u ON h.idUsuario = u.id $where ORDER BY h.fecha DESC LIMIT " . max(1, (int)$limite));
    $sentencia->execute($valores);
    return $sentencia->fetchAll();
}

function registrarCompra($payload)
{
    $bd = conectarBaseDatos();
    foreach ($payload->insumos as $insumo) {
        $bd->prepare("UPDATE insumos SET stock = stock + ? WHERE id = ?")->execute([$insumo->cantidad, $insumo->id]);
        $bd->prepare("INSERT INTO historial_stock (idInsumo, idUsuario, cantidad, tipo, fecha) VALUES (?, ?, ?, 'COMPRA', ?)")->execute([$insumo->id, $payload->idUsuario, $insumo->cantidad, date("Y-m-d H:i:s")]);
    }
    return true;
}

function registrarMerma($payload)
{
    $bd = conectarBaseDatos();
    $ok = $bd->prepare("UPDATE insumos SET stock = GREATEST(0, stock - ?) WHERE id = ?")->execute([$payload->cantidad, $payload->idInsumo]);
    if ($ok) $bd->prepare("INSERT INTO historial_stock (idInsumo, idUsuario, cantidad, tipo, fecha) VALUES (?, ?, ?, 'MERMA', ?)")->execute([$payload->idInsumo, $payload->idUsuario, -$payload->cantidad, date("Y-m-d H:i:s")]);
    return $ok;
}

function producirDesdeMateria($payload)
{
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("SELECT stock, stockMateria, tipoCorte, nombre FROM insumos WHERE id = ?");
    $stmt->execute([$payload->idInsumo]);
    $insumo = $stmt->fetchObject();
    if (!$insumo || $insumo->tipoCorte <= 0) return ['ok' => false, 'error' => 'Sin tasa de conversión'];
    $usoG = $payload->usoMateria * 1000;
    if ($usoG > ($insumo->stockMateria * 1000)) return ['ok' => false, 'error' => 'Sin materia prima suficiente'];
    $porcionesNuevas = (int)floor($usoG / $insumo->tipoCorte);
    if ($porcionesNuevas <= 0) return ['ok' => false, 'error' => 'Cantidad insuficiente'];
    $ok = $bd->prepare("UPDATE insumos SET stock = ?, stockMateria = ? WHERE id = ?")->execute([$insumo->stock + $porcionesNuevas, round($insumo->stockMateria - $payload->usoMateria, 4), $payload->idInsumo]);
    if ($ok) $bd->prepare("INSERT INTO historial_stock (idInsumo, idUsuario, cantidad, tipo, nota, fecha) VALUES (?, ?, ?, 'PRODUCCION', ?, ?)")->execute([$payload->idInsumo, $payload->idUsuario, $porcionesNuevas, "Producidas desde {$payload->usoMateria} kg", date("Y-m-d H:i:s")]);
    return ['ok' => (bool)$ok, 'porcionesNuevas' => $porcionesNuevas];
}

// ─── MENÚ DEL DÍA ─────────────────────────────────────────────────────────────

function obtenerMenuDia($dia, $ajustarStockVenta = false)
{
    $bd = conectarBaseDatos();
    $st = $bd->prepare("SELECT insumos.*, IFNULL(categorias.nombre, 'NO DEFINIDA') AS categoria FROM menu_dia INNER JOIN insumos ON insumos.id = menu_dia.idInsumo LEFT JOIN categorias ON categorias.id = insumos.categoria WHERE menu_dia.diaSemana = ?");
    $st->execute([$dia]);
    $filas = $st->fetchAll();
    if ($ajustarStockVenta) ajustarStockDisponibleVentaEnFilas($bd, $filas);
    return $filas;
}

function guardarMenuDia($idInsumo, $dia)
{
    $bd = conectarBaseDatos();
    $check = $bd->prepare("SELECT id FROM menu_dia WHERE idInsumo = ? AND diaSemana = ?");
    $check->execute([$idInsumo, $dia]);
    if ($check->fetch()) return true;
    return $bd->prepare("INSERT INTO menu_dia (idInsumo, diaSemana) VALUES (?, ?)")->execute([$idInsumo, $dia]);
}

function eliminarDelMenuDia($idInsumo, $dia)
{
    $bd = conectarBaseDatos();
    return $bd->prepare("DELETE FROM menu_dia WHERE idInsumo = ? AND diaSemana = ?")->execute([$idInsumo, $dia]);
}

// ─── UTILIDADES DE COMBO ─────────────────────────────────────────────────────

function construirResumenComboParaCocina($bd, $detalleJsonRaw, $idPlantilla)
{
    $idPlantilla = (int) $idPlantilla;
    if ($idPlantilla <= 0 || !$detalleJsonRaw) return '';
    $det = is_array($detalleJsonRaw) ? $detalleJsonRaw : json_decode((string)$detalleJsonRaw, true);
    if (!is_array($det)) return '';
    $menus = $det['menus'] ?? [];
    if (empty($menus)) return '';
    
    $st = $bd->prepare('SELECT id, etiqueta FROM combo_plantilla_slot WHERE id_plantilla = ? ORDER BY orden ASC, id ASC');
    $st->execute([$idPlantilla]);
    $slotsMeta = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $slotsMeta[(string)$r['id']] = $r['etiqueta'];

    $bloques = [];
    $mi = 1;
    foreach ($menus as $menu) {
        $slots = $menu['slots'] ?? $menu['s'] ?? [];
        $partes = [];
        foreach ($slots as $slotId => $idInsumo) {
            $stNom = $bd->prepare("SELECT nombre FROM insumos WHERE id = ?");
            $stNom->execute([(int)$idInsumo]);
            $nom = $stNom->fetchColumn() ?: '?';
            $partes[] = ($slotsMeta[(string)$slotId] ?? ('Op#' . $slotId)) . ': ' . $nom;
        }
        if (!empty($partes)) $bloques[] = 'M' . $mi . ': ' . implode(' · ', $partes);
        $mi++;
    }
    return implode("\n", $bloques);
}

function _encodeDetalleJsonParaDb($raw)
{
    if ($raw === null || $raw === '') return null;
    return is_string($raw) ? $raw : json_encode($raw, JSON_UNESCAPED_UNICODE);
}
