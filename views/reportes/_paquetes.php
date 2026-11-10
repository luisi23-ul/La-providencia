<?php
function rp_fecha_larga(string $fecha, bool $conDia = true): string
{
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $t = strtotime($fecha);
    return ($conDia ? $dias[date('w', $t)] . ' ' : '') . date('j', $t) . ' de ' . $meses[date('n', $t) - 1] . ' de ' . date('Y', $t);
}

function rp_periodo(string $desde, string $hasta): string
{
    if ($desde === '' && $hasta === '') return 'Todas las fechas';
    if ($desde === '') return 'Hasta el ' . rp_fecha_larga($hasta, false);
    if ($hasta === '') return 'Desde el ' . rp_fecha_larga($desde, false);
    if ($desde === $hasta) return rp_fecha_larga($desde);
    return 'Del ' . rp_fecha_larga($desde, false) . ' al ' . rp_fecha_larga($hasta, false);
}

function rp_dinero($v): string
{
    return '$ ' . number_format((float) $v, 2, ',', '.');
}

function rp_bs($v): string
{
    return 'Bs ' . number_format((float) $v, 2, ',', '.');
}

function rp_agrupar(array $filas, callable $clave): array
{
    $grupos = [];
    foreach ($filas as $f) $grupos[$clave($f)][] = $f;
    return $grupos;
}

function paquete_reporte(string $tipo, array $reporte, float $tasa, PDO $db, array $extra = []): array
{
    $datos = array_map(fn($d) => (object) $d, (array) ($reporte['datos'] ?? []));
    $g = fn(string $k) => trim((string) ($_GET[$k] ?? ''));
    $desde = $g('desde');
    $hasta = $g('hasta');

    $sis = null;
    try {
        $sis = $db->query('SELECT * FROM sistema LIMIT 1')->fetch(PDO::FETCH_OBJ) ?: null;
    } catch (Throwable $e) {
    }
    $rol = (string) ($_SESSION['rol'] ?? '');
    $p = [
        'empresa' => [
            'nombre_empresa' => $sis->nombre_empresa ?? '',
            'razon_social'   => $sis->razon_social ?? '',
            'rif'            => $sis->rif ?? '',
            'direccion'      => $sis->direccion ?? '',
            'logo_path'      => $sis->logo_path ?? '',
        ],
        'elaborado' => trim(($_SESSION['nombre'] ?? '') . ($rol !== '' ? ' (' . ucfirst($rol) . ')' : '')),
        'titulo' => 'Reporte',
        'subtitulo' => '',
        'resumen' => [],
        'columnas' => [],
        'grupos' => [],
        'totales' => [],
    ];

    // -------------------------------------------------------------
    // INVENTARIO / ESTADÍSTICAS BÁSICAS
    // -------------------------------------------------------------
    if ($tipo === 'inventario' || ($tipo === 'estadisticas' && !$extra)) {
        $stock = $g('stock');
        $cat = $g('categoria');
        $estado = fn($d) => (float) $d->stock <= 0 ? 'agot' : ((float) $d->stock <= (float) ($d->stock_minimo ?? 0) ? 'bajo' : 'ok');
        $lista = array_values(array_filter($datos, function ($d) use ($stock, $cat, $estado) {
            if ($stock === 'bajo' && $estado($d) === 'ok') return false;
            if ($stock === 'agotado' && $estado($d) !== 'agot') return false;
            if ($cat !== '' && ($d->slug_categoria ?? '') !== $cat) return false;
            return true;
        }));
        $p['titulo'] = 'Reporte de inventario';
        $p['subtitulo'] = implode(' · ', array_filter([
            $stock === 'bajo' ? 'Solo stock bajo' : ($stock === 'agotado' ? 'Solo agotados' : 'Todos los productos'),
            $cat !== '' && $lista ? 'Categoría: ' . ($lista[0]->nombre_categoria ?? $cat) : '',
        ]));
        $p['columnas'] = [
            ['texto' => 'Código', 'fijo' => true, 'ancho' => 14], ['texto' => 'Producto', 'ancho' => 25], ['texto' => 'Categoría', 'ancho' => 15],
            ['texto' => 'Stock', 'numero' => true, 'decimales' => 0, 'ancho' => 9],
            ['texto' => 'Costo $', 'numero' => true, 'ancho' => 11], ['texto' => 'Precio $', 'numero' => true, 'ancho' => 11],
            ['texto' => 'Valor a costo $', 'numero' => true, 'ancho' => 15],
        ];
        $tStock = 0; $tCosto = 0; $tPrecio = 0;
        $porCat = rp_agrupar($lista, fn($d) => trim((string) ($d->nombre_categoria_padre ?? '')) ?: (trim((string) ($d->nombre_categoria ?? '')) ?: 'Sin categoría'));
        ksort($porCat, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($porCat as $nombre => $items) {
            $filas = []; $sStock = 0; $sValor = 0;
            foreach ($items as $d) {
                $valor = (float) $d->stock * (float) ($d->costo_compra ?? 0);
                $filas[] = [$d->codigo, $d->nombre_producto, $d->nombre_categoria ?? '', (float) $d->stock, (float) ($d->costo_compra ?? 0), (float) $d->precio, $valor];
                $sStock += (float) $d->stock; 
                $sValor += $valor;
                $tPrecio += (float) $d->stock * (float) $d->precio;
            }
            $tStock += $sStock; $tCosto += $sValor;
            $p['grupos'][] = ['titulo' => $nombre . ' · ' . count($items) . (count($items) === 1 ? ' producto' : ' productos'), 'filas' => $filas, 'subtotal' => ['Subtotal', '', '', $sStock, '', '', $sValor]];
        }
        $p['totales'] = ['Total general', '', '', $tStock, '', '', $tCosto];
        $bajos = count(array_filter($lista, fn($d) => $estado($d) !== 'ok'));
        $agot = count(array_filter($lista, fn($d) => $estado($d) === 'agot'));
        $p['resumen'] = [
            ['texto' => 'Productos', 'valor' => (string) count($lista), 'nota' => 'en ' . count($porCat) . (count($porCat) === 1 ? ' categoría' : ' categorías')],
            ['texto' => 'Stock bajo', 'valor' => (string) $bajos, 'nota' => $agot . ($agot === 1 ? ' agotado' : ' agotados')],
            ['texto' => 'Valor a costo', 'valor' => rp_dinero($tCosto), 'nota' => (int) $tStock . ' unidades'],
            ['texto' => 'Valor a precio', 'valor' => rp_dinero($tPrecio), 'nota' => 'margen ' . ($tPrecio > 0 ? round((($tPrecio - $tCosto) / $tPrecio) * 100) : 0) . ' %'],
        ];
        return $p;
    }

    // -------------------------------------------------------------
    // HISTORIAL CLIENTE
    // -------------------------------------------------------------
    if ($tipo === 'cliente') {
        $cli = (object) ($reporte['cliente'] ?? []);
        $etiquetas = ['pendiente' => 'Pendiente de pago', 'por_verificar' => 'Por verificar', 'pagado' => 'Pagada, por retirar', 'retirado' => 'Retirada', 'entregado' => 'Retirada', 'completado' => 'Completada', 'cancelado' => 'Anulada', 'anulado' => 'Anulada'];
        $estadoF = ['pagadas' => 'Solo compras pagadas', 'pendientes' => 'Solo compras con saldo pendiente'][$g('estado')] ?? '';
        $p['titulo'] = 'Historial de compras del cliente';
        $p['subtitulo'] = implode(' · ', array_filter([trim((string) ($cli->nombre_razon_social ?? '')), trim((string) ($cli->cedula ?? $g('cedula'))), rp_periodo($desde, $hasta), $estadoF]));
        $p['columnas'] = [
            ['texto' => 'Producto', 'ancho' => 36], ['texto' => 'Detalles', 'ancho' => 26], ['texto' => 'Cant.', 'numero' => true, 'decimales' => 0, 'ancho' => 8],
            ['texto' => 'Precio $', 'numero' => true, 'ancho' => 14], ['texto' => 'Subtotal $', 'numero' => true, 'ancho' => 16],
        ];
        $t = 0; $saldo = 0; $n = 0; $conSaldo = 0;
        foreach ($datos as $c) {
            $anulada = in_array($c->estado ?? '', ['cancelado', 'anulado'], true);
            $total = (float) ($c->total ?? 0);
            $debe = max(0, $total - (float) ($c->pagado ?? 0));
            $filas = [];
            foreach ((array) ($c->productos ?? []) as $pr) {
                $pr = (object) $pr;
                $det = array_filter([
                    trim((string) ($pr->talla ?? '')) !== '' ? 'Talla ' . mb_strtoupper(trim((string) $pr->talla)) : '',
                    trim((string) ($pr->color ?? '')),
                    trim((string) ($pr->modelo ?? '')),
                ]);
                $filas[] = [(string) ($pr->producto_nombre ?? ''), implode(' · ', $det), (int) ($pr->cantidad ?? 0), (float) ($pr->precio_unitario ?? 0), (float) ($pr->subtotal ?? 0)];
            }
            $fecha = !empty($c->fecha) ? rp_fecha_larga(date('Y-m-d', strtotime($c->fecha)), false) . ', ' . date('g:i a', strtotime($c->fecha)) : '';
            $origen = ($c->origen_venta ?? '') === 'online' ? 'Online' : 'Tienda';
            $nota = $anulada ? 'Anulada, no suma' : ($debe > 0.004 ? 'Debe ' . rp_dinero($debe) : 'Pagada');
            $p['grupos'][] = [
                'titulo' => implode(' · ', array_filter([(string) ($c->numero_orden ?? ''), $fecha, $origen, $etiquetas[$c->estado ?? ''] ?? ucfirst((string) ($c->estado ?? ''))])),
                'filas' => $filas,
                'subtotal' => ['Total de la compra', $nota, '', '', $total],
            ];
            if ($anulada) continue;
            $n++; $t += $total; $saldo += $debe;
            if ($debe > 0.004) $conSaldo++;
        }
        $p['totales'] = ['Total general', $n . ($n === 1 ? ' compra' : ' compras'), '', '', $t];
        $p['resumen'] = [
            ['texto' => 'Compras', 'valor' => (string) $n, 'nota' => 'en el período'],
            ['texto' => 'Total comprado', 'valor' => rp_dinero($t), 'nota' => rp_bs($t * $tasa)],
            ['texto' => 'Compra promedio', 'valor' => rp_dinero($n ? $t / $n : 0), 'nota' => rp_bs(($n ? $t / $n : 0) * $tasa)],
            ['texto' => 'Saldo pendiente', 'valor' => rp_dinero($saldo), 'nota' => $conSaldo ? $conSaldo . ($conSaldo === 1 ? ' compra por cobrar' : ' compras por cobrar') : 'Todo pagado'],
        ];
        return $p;
    }

    // -------------------------------------------------------------
    // CIERRES DE CAJA POR PERÍODO (HISTORIAL)
    // -------------------------------------------------------------
    if ($tipo === 'cierre_caja') {
        $turnos = array_map(fn($t) => (object) $t, (array) ($reporte['turnos'] ?? $reporte['datos'] ?? []));
        $p['titulo'] = 'Reporte de cierres de caja';
        $p['subtitulo'] = rp_periodo($desde, $hasta);
        $p['columnas'] = [
            ['texto' => 'Turno', 'fijo' => true, 'ancho' => 10],
            ['texto' => 'Cajero', 'ancho' => 20],
            ['texto' => 'Apertura', 'ancho' => 16],
            ['texto' => 'Cierre', 'ancho' => 16],
            ['texto' => 'Fondo $', 'numero' => true, 'ancho' => 10],
            ['texto' => 'Fondo Bs', 'numero' => true, 'ancho' => 14],
            ['texto' => 'Cierre $', 'numero' => true, 'ancho' => 10],
            ['texto' => 'Cierre Bs', 'numero' => true, 'ancho' => 14],
        ];

        $filas = [];
        $totFondoU = 0;
        $totFondoB = 0;
        $totCierreU = 0;
        $totCierreB = 0;

        foreach ($turnos as $t) {
            $numT   = (int) ($t->numero_turno ?? 0);
            $cajero = trim(ucwords(mb_strtolower((string) ($t->cajero ?? '')))) ?: (string) ($t->cedula_cajero ?? 'Cajero');
            $fAp    = (string) ($t->fecha_apertura ?? '');
            $fCi    = (string) ($t->fecha_cierre ?? 'Sin cierre');
            $fU     = (float) ($t->fondo_usd ?? 0);
            $fB     = (float) ($t->fondo_bs ?? 0);
            $cU     = (float) ($t->cierre_usd ?? 0);
            $cB     = (float) ($t->cierre_bs ?? 0);

            $totFondoU  += $fU;
            $totFondoB  += $fB;
            $totCierreU += $cU;
            $totCierreB += $cB;

            $filas[] = [
                'Turno #' . $numT,
                $cajero,
                $fAp,
                $fCi,
                $fU,
                $fB,
                $cU,
                $cB
            ];
        }

        $p['grupos'][] = [
            'titulo'   => 'Historial de turnos cerrados · ' . count($turnos) . (count($turnos) === 1 ? ' turno' : ' turnos'),
            'filas'    => $filas,
        ];

        $p['totales'] = ['Total general', '', '', '', $totFondoU, $totFondoB, $totCierreU, $totCierreB];
        $p['resumen'] = [
            ['texto' => 'Turnos cerrados', 'valor' => (string) count($turnos), 'nota' => 'en el período'],
            ['texto' => 'Total $ contado', 'valor' => rp_dinero($totCierreU), 'nota' => 'al cierre'],
            ['texto' => 'Total Bs contado', 'valor' => rp_bs($totCierreB), 'nota' => 'al cierre'],
        ];

        return $p;
    }

    // -------------------------------------------------------------
    // CIERRE DEL DÍA (TODOS LOS TURNOS Y VENTAS DE LA FECHA)
    // -------------------------------------------------------------
    if ($tipo === 'cierre' && (isset($reporte['turnos']) || !isset($reporte['turno']))) {
        $dia = (string) ($reporte['fecha'] ?? '') ?: ($g('fecha') ?: date('Y-m-d'));
        $f = fn($v) => number_format((float) $v, 2, ',', '.');
        $estado = fn($d) => abs($d) < 0.005 ? 'Cuadra' : ($d > 0 ? 'Sobra ' : 'Falta ') . $f(abs($d));
        $hora = fn($t) => date('g:i a', $t);
        $turnos = array_map(fn($t) => (object) $t, (array) ($reporte['turnos'] ?? []));
        usort($turnos, fn($a, $b) => strcmp((string) ($a->fecha_apertura ?? ''), (string) ($b->fecha_apertura ?? '')));
        
        $fondoU = 0; $fondoB = 0; $contU = 0; $contB = 0; $enCurso = false; $partes = [];
        foreach ($turnos as $t) {
            $ab = ($t->estado ?? '') === 'abierto' || empty($t->fecha_cierre);
            $ta = strtotime((string) ($t->fecha_apertura ?? ''));
            $tc = $ab ? 0 : strtotime((string) $t->fecha_cierre);
            $nom = trim(ucwords(mb_strtolower((string) ($t->cajero ?? '')))) ?: (string) ($t->cedula_cajero ?? '');
            $partes[] = 'turno ' . (int) ($t->numero_turno ?? 0) . ' de ' . $nom . ', ' . ($ab
                ? 'desde las ' . $hora($ta) . ' (en curso)'
                : 'de ' . $hora($ta) . ' a ' . (date('Y-m-d', $tc) !== date('Y-m-d', $ta) ? date('d/m', $tc) . ' ' : '') . $hora($tc));
            $fondoU += (float) ($t->fondo_usd ?? 0); 
            $fondoB += (float) ($t->fondo_bs ?? 0);
            if ($ab) {
                $enCurso = true;
            } else { 
                $contU += (float) ($t->cierre_usd ?? 0); 
                $contB += (float) ($t->cierre_bs ?? 0); 
            }
        }
        $p['titulo'] = 'Cierre del día';
        $p['subtitulo'] = rp_fecha_larga($dia) . ' · ' . ($partes ? ucfirst(implode('; ', $partes)) : 'Sin turnos de caja');

        $cobU = 0; $cobB = 0; $tU = 0; $tB = 0; $ventas = []; $anuladas = []; $porMetodo = [];
        foreach ($datos as $d) {
            $esBs = in_array(mb_strtoupper(trim((string) ($d->moneda ?? ''))), ['BS', 'VES'], true);
            $monto = (float) ($d->monto ?? 0);
            $tc = (float) ($d->tasa_cambio ?? 0);
            $u = $esBs ? ($tc > 0 ? $monto / $tc : 0) : $monto;
            $b = $esBs ? $monto : $monto * $tc;
            $orden = (string) ($d->numero_orden ?? '');
            if (in_array((string) ($d->estado_venta ?? ''), ['cancelado', 'anulado'], true)) {
                $anuladas[$orden] = ($anuladas[$orden] ?? 0) + $u;
                continue;
            }
            $ventas[$orden] = true;
            $tU += $u; $tB += $b;
            $nombreM = (string) ($d->metodo_nombre ?? '') ?: (string) ($d->codigo_metodo ?? 'Otro');
            if (stripos((string) ($d->codigo_metodo ?? '') . ' ' . $nombreM, 'efectivo') !== false) {
                if ($esBs) $cobB += $monto; else $cobU += $monto;
            }
            $porMetodo[$nombreM][] = [strtotime((string) ($d->fecha_pago ?? '')), $orden, (string) ($d->cliente ?? ''), implode(' · ', array_filter([(string) ($d->banco ?? ''), (string) ($d->referencia ?? '')])), $u, $b];
        }
        $espU = $fondoU + $cobU; $espB = $fondoB + $cobB;

        $p['columnas'] = [
            ['texto' => 'Hora', 'fijo' => true, 'ancho' => 20], ['texto' => 'N.º venta', 'fijo' => true, 'ancho' => 20], ['texto' => 'Cliente', 'ancho' => 22],
            ['texto' => 'Referencia', 'ancho' => 26], ['texto' => 'Monto $', 'numero' => true, 'ancho' => 16], ['texto' => 'Monto Bs', 'numero' => true, 'ancho' => 20],
        ];
        if ($turnos) {
            $p['grupos'][] = [
                'titulo' => 'Arqueo del día',
                'columnas' => [['texto' => 'Concepto', 'ancho' => 50], ['texto' => '$', 'numero' => true, 'ancho' => 22], ['texto' => 'Bs', 'numero' => true, 'ancho' => 28]],
                'filas' => [
                    ['Fondo inicial', $fondoU, $fondoB],
                    ['Cobrado en efectivo', $cobU, $cobB],
                    ['Esperado en caja', $espU, $espB],
                    $enCurso ? ['Contado al cerrar', 'En curso', 'En curso'] : ['Contado al cerrar', $contU, $contB],
                ],
                'subtotal' => $enCurso ? ['Diferencia', 'En curso', 'En curso'] : ['Diferencia', $estado($contU - $espU), $estado($contB - $espB)],
            ];
        }
        $rango = fn($n) => stripos($n, 'efectivo') === false ? 2 : (preg_match('/bs|bol/i', $n) ? 1 : 0);
        uksort($porMetodo, fn($a, $b) => [$rango($a), $a] <=> [$rango($b), $b]);
        foreach ($porMetodo as $nombreM => $items) {
            usort($items, fn($a, $b) => $a[0] <=> $b[0]);
            $su = array_sum(array_column($items, 4)); $sb = array_sum(array_column($items, 5));
            $p['grupos'][] = [
                'titulo' => $nombreM . ' · ' . count($items) . (count($items) === 1 ? ' pago' : ' pagos'),
                'filas' => array_map(fn($i) => [$i[0] ? $hora($i[0]) : '', $i[1], $i[2], $i[3], $i[4], $i[5]], $items),
                'subtotal' => ['Subtotal', '', '', '', $su, $sb],
            ];
        }
        if ($porMetodo) $p['totales'] = ['Total general', '', '', '', $tU, $tB];
        $nV = count($ventas); $nP = array_sum(array_map('count', $porMetodo)); $nA = count($anuladas);
        $p['resumen'] = [
            ['texto' => 'Total del día', 'valor' => rp_dinero($tU), 'nota' => rp_bs($tB)],
            ['texto' => 'Efectivo contado', 'valor' => $enCurso ? 'En curso' : rp_dinero($contU), 'nota' => $enCurso ? 'Esperado ' . rp_dinero($espU) : rp_bs($contB)],
            ['texto' => 'N.º de ventas', 'valor' => (string) $nV, 'nota' => $nP . ($nP === 1 ? ' pago' : ' pagos')],
            ['texto' => 'Ventas anuladas', 'valor' => (string) $nA, 'nota' => $nA ? rp_dinero(array_sum($anuladas)) . ', no suman' : 'Ninguna'],
        ];
        return $p;
    }

    // -------------------------------------------------------------
    // ARQUEO INDIVIDUAL DE UN TURNO CONCRETO
    // -------------------------------------------------------------
    if ($tipo === 'cierre' && isset($reporte['turno'])) {
        $tu = (object) ($reporte['turno'] ?? []);
        $abierto = ($tu->estado ?? '') === 'abierto' || empty($tu->fecha_cierre);
        $ta = strtotime((string) ($tu->fecha_apertura ?? ''));
        $tc = $abierto ? null : strtotime((string) $tu->fecha_cierre);
        $f = fn($v) => number_format((float) $v, 2, ',', '.');
        $estado = fn($d, $fmt) => abs($d) < 0.005 ? 'Cuadra' : ($d > 0 ? 'Sobra ' : 'Falta ') . $fmt(abs($d));
        $nombre = trim(ucwords(mb_strtolower((string) ($reporte['cajero'] ?? '')))) ?: (string) ($tu->cedula_cajero ?? '');
        $horario = !$ta ? '' : ($tc
            ? rp_fecha_larga(date('Y-m-d', $ta)) . ', de ' . date('g:i a', $ta) . ' a ' . (date('Y-m-d', $tc) !== date('Y-m-d', $ta) ? rp_fecha_larga(date('Y-m-d', $tc), false) . ', ' : '') . date('g:i a', $tc)
            : rp_fecha_larga(date('Y-m-d', $ta)) . ', abierto desde las ' . date('g:i a', $ta));
        $p['titulo'] = $abierto ? 'Arqueo parcial' : 'Cierre de caja';
        $p['subtitulo'] = implode(' · ', array_filter([
            'Turno ' . (int) ($tu->numero_turno ?? 0) . ' de ' . $nombre,
            $horario,
            !empty($tu->tasa_apertura) ? 'Tasa al abrir ' . rp_bs($tu->tasa_apertura) : '',
        ]));
        $cobU = 0; $cobB = 0; $cobBu = 0; $nU = 0; $nB = 0; $otros = [];
        foreach ($datos as $d) {
            $esBs = in_array(mb_strtoupper(trim((string) ($d->moneda ?? ''))), ['BS', 'VES'], true);
            if (stripos((string) ($d->metodo_nombre ?? ''), 'efectivo') !== false) {
                if ($esBs) { $cobB += (float) ($d->total_monto ?? 0); $cobBu += (float) ($d->total_usd ?? 0); $nB += (int) ($d->cantidad_transacciones ?? 0); }
                else { $cobU += (float) ($d->total_monto ?? 0); $nU += (int) ($d->cantidad_transacciones ?? 0); }
            } else {
                $otros[] = [(string) ($d->metodo_nombre ?? ''), (int) ($d->cantidad_transacciones ?? 0), (float) ($d->total_usd ?? 0), (float) ($d->total_bs ?? 0)];
            }
        }
        $fondoU = (float) ($tu->fondo_usd ?? 0); $fondoB = (float) ($tu->fondo_bs ?? 0);
        $contU = (float) ($tu->cierre_usd ?? 0); $contB = (float) ($tu->cierre_bs ?? 0);
        $espU = $fondoU + $cobU; $espB = $fondoB + $cobB;
        $caja = function (string $titulo, string $col, float $fondo, float $cob, int $n, float $esp, float $cont) use ($abierto, $estado, $f) {
            $filas = [['Fondo inicial', $fondo], ['Cobrado en efectivo (' . $n . ($n === 1 ? ' pago)' : ' pagos)'), $cob], ['Esperado en caja', $esp]];
            if (!$abierto) $filas[] = ['Contado al cerrar', $cont];
            return [
                'titulo' => $titulo,
                'columnas' => [['texto' => 'Concepto', 'ancho' => 60], ['texto' => $col, 'numero' => true, 'ancho' => 40]],
                'filas' => $filas,
                'subtotal' => ['Diferencia', $abierto ? 'Turno abierto' : $estado($cont - $esp, $f)],
            ];
        };
        $p['grupos'][] = $caja('Caja en dólares', 'Monto $', $fondoU, $cobU, $nU, $espU, $contU);
        $p['grupos'][] = $caja('Caja en bolívares', 'Monto Bs', $fondoB, $cobB, $nB, $espB, $contB);
        $oU = array_sum(array_column($otros, 2)); $oB = array_sum(array_column($otros, 3)); $oN = array_sum(array_column($otros, 1));
        if ($otros) {
            $p['grupos'][] = [
                'titulo' => 'Cobros que no entran a la caja',
                'columnas' => [['texto' => 'Método', 'ancho' => 40], ['texto' => 'Pagos', 'numero' => true, 'decimales' => 0, 'ancho' => 14], ['texto' => 'Monto $', 'numero' => true, 'ancho' => 22], ['texto' => 'Monto Bs', 'numero' => true, 'ancho' => 24]],
                'filas' => $otros,
                'subtotal' => ['Total', $oN, $oU, $oB],
            ];
        }
        $p['resumen'] = [
            ['texto' => 'Efectivo $ contado', 'valor' => $abierto ? 'En curso' : rp_dinero($contU), 'nota' => $abierto ? 'Esperado ' . rp_dinero($espU) : $estado($contU - $espU, 'rp_dinero')],
            ['texto' => 'Efectivo Bs contado', 'valor' => $abierto ? 'En curso' : rp_bs($contB), 'nota' => $abierto ? 'Esperado ' . rp_bs($espB) : $estado($contB - $espB, 'rp_bs')],
            ['texto' => 'Pago móvil y transferencia', 'valor' => rp_dinero($oU), 'nota' => $oN . ($oN === 1 ? ' pago' : ' pagos') . ', ' . rp_bs($oB)],
            ['texto' => 'Total cobrado', 'valor' => rp_dinero($cobU + $cobBu + $oU), 'nota' => ($nU + $nB + $oN) . (($nU + $nB + $oN) === 1 ? ' pago aprobado' : ' pagos aprobados')],
        ];
        return $p;
    }

    // -------------------------------------------------------------
    // RETIROS Y PENDIENTES
    // -------------------------------------------------------------
    if ($tipo === 'retiros' || $tipo === 'pendientes') {
        $etiquetas = ['pagado' => 'Por retirar', 'por_retirar' => 'Por retirar', 'retirado' => 'Retirado', 'entregado' => 'Entregado', 'pendiente' => 'Pendiente de pago', 'por_verificar' => 'Por verificar'];
        $p['titulo'] = $tipo === 'retiros' ? 'Reporte de retiros' : 'Reporte de pagos pendientes';
        $p['subtitulo'] = $tipo === 'retiros' ? ($g('estado') === 'entregado' ? 'Pedidos entregados' : 'Pedidos listos para retirar') : 'Ventas con pago pendiente o por verificar';
        $p['columnas'] = [
            ['texto' => 'N.º venta', 'fijo' => true, 'ancho' => 14], ['texto' => 'Cliente', 'ancho' => 24], ['texto' => 'Hora', 'fijo' => true, 'ancho' => 10], ['texto' => 'Estado', 'ancho' => 16],
            ['texto' => 'Total $', 'numero' => true, 'ancho' => 12], ['texto' => 'Total Bs', 'numero' => true, 'ancho' => 16],
        ];
        $t = 0;
        $porDia = rp_agrupar($datos, fn($d) => date('Y-m-d', strtotime($d->fecha)));
        krsort($porDia);
        foreach ($porDia as $dia => $items) {
            $filas = []; $s = 0;
            foreach ($items as $d) {
                $filas[] = [$d->numero_orden ?? $d->id ?? '', $d->nombre ?? '', date('g:i a', strtotime($d->fecha)), $etiquetas[$d->estado ?? ''] ?? ucfirst((string) ($d->estado ?? '')), (float) $d->total, (float) $d->total * $tasa];
                $s += (float) $d->total;
            }
            $t += $s;
            $p['grupos'][] = ['titulo' => rp_fecha_larga($dia) . ' · ' . count($items) . (count($items) === 1 ? ' pedido' : ' pedidos'), 'filas' => $filas, 'subtotal' => ['Subtotal del día', '', '', '', $s, $s * $tasa]];
        }
        $p['totales'] = ['Total general', '', '', '', $t, $t * $tasa];
        $viejo = $datos ? min(array_map(fn($d) => strtotime($d->fecha), $datos)) : 0;
        $p['resumen'] = [
            ['texto' => $tipo === 'retiros' ? 'Pedidos' : 'Ventas pendientes', 'valor' => (string) count($datos), 'nota' => 'en ' . count($porDia) . (count($porDia) === 1 ? ' día' : ' días')],
            ['texto' => $tipo === 'retiros' ? 'Total de los pedidos' : 'Total por cobrar', 'valor' => rp_dinero($t), 'nota' => rp_bs($t * $tasa)],
            ['texto' => 'El más antiguo', 'valor' => $viejo ? date('d/m/Y', $viejo) : '—', 'nota' => $viejo ? 'hace ' . max(0, (int) floor((time() - $viejo) / 86400)) . ' días' : ''],
        ];
        return $p;
    }

    // -------------------------------------------------------------
    // KARDEX / MOVIMIENTOS
    // -------------------------------------------------------------
    if ($tipo === 'kardex' || $tipo === 'movimientos') {
        $nombres = ['venta_tienda' => 'Venta en tienda', 'venta_online' => 'Venta online', 'compra' => 'Compra', 'anulacion_compra' => 'Compra anulada', 'ajuste' => 'Ajuste', 'ajuste_entrada' => 'Ajuste (entrada)', 'ajuste_salida' => 'Ajuste (salida)', 'devolucion' => 'Devolución'];
        $mov = $g('movimiento') ?: $g('tipo_movimiento');
        $p['titulo'] = 'Reporte de movimientos de inventario';
        $p['subtitulo'] = rp_periodo($desde, $hasta) . ($mov !== '' ? ' · ' . ($nombres[$mov] ?? ucfirst(str_replace('_', ' ', $mov))) : '');
        $p['columnas'] = [
            ['texto' => 'Fecha', 'ancho' => 21], ['texto' => 'Movimiento', 'ancho' => 17], ['texto' => 'Referencia', 'ancho' => 16], ['texto' => 'Usuario', 'ancho' => 14],
            ['texto' => 'Cantidad', 'numero' => true, 'decimales' => 0, 'ancho' => 10], ['texto' => 'Stock final', 'numero' => true, 'decimales' => 0, 'ancho' => 11],
        ];
        $entradas = 0; $salidas = 0;
        $porProd = rp_agrupar($datos, fn($d) => ($d->nombre_producto ?? '') . ' · ' . ($d->codigo_producto ?? ''));
        ksort($porProd, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($porProd as $nombre => $items) {
            $filas = []; $neto = 0;
            foreach ($items as $d) {
                $c = (float) $d->cantidad;
                $filas[] = [date('d/m/Y g:i a', strtotime($d->fecha_hora)), $nombres[$d->tipo_movimiento ?? ''] ?? ucfirst(str_replace('_', ' ', (string) ($d->tipo_movimiento ?? ''))), $d->referencia ?? '', $d->usuario ?? '', $c, (float) ($d->stock_resultante ?? 0)];
                $neto += $c;
                if ($c > 0) $entradas += $c; else $salidas += -$c;
            }
            $p['grupos'][] = ['titulo' => $nombre, 'filas' => $filas, 'subtotal' => ['Movimiento neto', '', '', '', $neto, '']];
        }
        $p['totales'] = ['Total general', '', '', '', $entradas - $salidas, ''];
        $p['resumen'] = [
            ['texto' => 'Movimientos', 'valor' => (string) count($datos), 'nota' => count($porProd) . (count($porProd) === 1 ? ' producto' : ' productos')],
            ['texto' => 'Entradas', 'valor' => '+' . (int) $entradas, 'nota' => 'unidades'],
            ['texto' => 'Salidas', 'valor' => '−' . (int) $salidas, 'nota' => 'unidades'],
            ['texto' => 'Neto', 'valor' => (($entradas - $salidas) >= 0 ? '+' : '−') . abs((int) ($entradas - $salidas)), 'nota' => 'unidades'],
        ];
        return $p;
    }

    // -------------------------------------------------------------
    // COBRADO POR MÉTODO
    // -------------------------------------------------------------
    if ($tipo === 'metodo' || $tipo === 'metodo_pago') {
        $metodo = $g('metodo');
        $nombreMetodo = '';
        if ($metodo !== '' && $metodo !== 'todos') {
            try {
                $st = $db->prepare('SELECT nombre FROM metodos_pago WHERE codigo_metodo = ? LIMIT 1');
                $st->execute([$metodo]);
                $nombreMetodo = (string) ($st->fetchColumn() ?: $metodo);
            } catch (Throwable $e) {
                $nombreMetodo = $metodo;
            }
        }
        $p['titulo'] = 'Lo cobrado' . ($nombreMetodo !== '' ? ' con ' . $nombreMetodo : ' por método de pago');
        $p['subtitulo'] = rp_periodo($desde, $hasta) . ($g('banco') !== '' ? ' · ' . $g('banco') : '');
        $p['columnas'] = [
            ['texto' => 'N.º venta', 'fijo' => true, 'ancho' => 15], ['texto' => 'Hora', 'fijo' => true, 'ancho' => 9], ['texto' => 'Cliente', 'ancho' => 14], ['texto' => 'Método', 'ancho' => 14],
            ['texto' => 'Banco y referencia', 'ancho' => 19], ['texto' => 'Monto', 'numero' => true, 'ancho' => 12], ['texto' => 'Monto Bs', 'numero' => true, 'ancho' => 17],
        ];
        $t = 0; $tBs = 0;
        $porDia = rp_agrupar($datos, fn($d) => date('Y-m-d', strtotime($d->fecha)));
        krsort($porDia);
        foreach ($porDia as $dia => $items) {
            $filas = []; $s = 0; $sBs = 0;
            foreach ($items as $d) {
                $filas[] = [$d->id ?? '', date('g:i a', strtotime($d->fecha)), $d->nombre ?? '', $d->metodo_nombre ?? '', implode(' · ', array_filter([$d->banco ?? '', $d->referencia ?? ''])), (float) $d->total, (float) ($d->total_bs ?? 0)];
                $s += (float) $d->total; $sBs += (float) ($d->total_bs ?? 0);
            }
            $t += $s; $tBs += $sBs;
            $p['grupos'][] = ['titulo' => rp_fecha_larga($dia) . ' · ' . count($items) . (count($items) === 1 ? ' pago' : ' pagos'), 'filas' => $filas, 'subtotal' => ['Subtotal del día', '', '', '', '', $s, $sBs]];
        }
        $p['totales'] = ['Total general', '', '', '', '', $t, $tBs];
        $p['resumen'] = [
            ['texto' => 'Pagos', 'valor' => (string) count($datos), 'nota' => 'en ' . count($porDia) . (count($porDia) === 1 ? ' día' : ' días')],
            ['texto' => 'Total cobrado', 'valor' => rp_bs($tBs), 'nota' => 'monto: ' . number_format($t, 2, ',', '.')],
            ['texto' => 'Pago promedio', 'valor' => rp_bs($datos ? $tBs / count($datos) : 0), 'nota' => ''],
        ];
        return $p;
    }

    // -------------------------------------------------------------
    // ESTADÍSTICAS GENERALES
    // -------------------------------------------------------------
    if ($tipo === 'estadisticas') {
        $res = (object) ($extra['resumen'] ?? []);
        $dias = array_map(fn($d) => (object) $d, (array) ($extra['dias'] ?? []));
        $top = array_map(fn($d) => (object) $d, (array) ($extra['top'] ?? []));
        $desde = $extra['desde'] ?? $desde;
        $hasta = $extra['hasta'] ?? $hasta;
        $vendido = (float) ($res->vendido ?? 0);
        $ventas = (int) ($res->ventas ?? 0);
        $ganancia = (float) ($res->ganancia ?? 0);
        $tienda = (float) ($res->tienda ?? 0);
        $p['titulo'] = 'Reporte de estadísticas';
        $p['subtitulo'] = rp_periodo((string) $desde, (string) $hasta);
        $p['columnas'] = [['texto' => 'Fecha', 'ancho' => 26], ['texto' => 'Pedidos', 'numero' => true, 'decimales' => 0, 'ancho' => 10], ['texto' => 'Tienda $', 'numero' => true, 'ancho' => 13], ['texto' => 'Online $', 'numero' => true, 'ancho' => 13], ['texto' => 'Total $', 'numero' => true, 'ancho' => 13]];
        $filas = []; $sp = 0; $st = 0; $so = 0; $sv = 0;
        foreach ($dias as $d) {
            $filas[] = [rp_fecha_larga($d->fecha), (int) $d->total_pedidos, (float) $d->tienda, (float) $d->online, (float) $d->total_dia];
            $sp += (int) $d->total_pedidos; $st += (float) $d->tienda; $so += (float) $d->online; $sv += (float) $d->total_dia;
        }
        $p['grupos'][] = ['titulo' => 'Ventas por día', 'filas' => $filas, 'subtotal' => ['Total del período', $sp, $st, $so, $sv]];
        if ($top) {
            $filasTop = [];
            foreach ($top as $d) $filasTop[] = [$d->nombre_producto, $d->categoria ?? '', (int) $d->unidades, (float) $d->monto, (float) $d->ganancia];
            $p['grupos'][] = [
                'titulo' => 'Productos más vendidos',
                'columnas' => [['texto' => 'Producto'], ['texto' => 'Categoría'], ['texto' => 'Unidades', 'numero' => true, 'decimales' => 0], ['texto' => 'Vendido $', 'numero' => true], ['texto' => 'Ganancia $', 'numero' => true]],
                'filas' => $filasTop,
            ];
        }
        $p['resumen'] = [
            ['texto' => 'Vendido', 'valor' => rp_dinero($vendido), 'nota' => rp_bs($vendido * $tasa)],
            ['texto' => 'Ventas', 'valor' => (string) $ventas, 'nota' => 'promedio ' . rp_dinero($ventas ? $vendido / $ventas : 0)],
            ['texto' => 'Ganancia', 'valor' => rp_dinero($ganancia), 'nota' => 'margen ' . ($vendido > 0 ? round(($ganancia / $vendido) * 100) : 0) . ' %'],
            ['texto' => 'Tienda / online', 'valor' => ($vendido > 0 ? round(($tienda / $vendido) * 100) : 0) . ' % / ' . ($vendido > 0 ? (100 - round(($tienda / $vendido) * 100)) : 0) . ' %', 'nota' => 'del total vendido'],
        ];
        return $p;
    }

    // -------------------------------------------------------------
    // CLIENTES
    // -------------------------------------------------------------
    if ($tipo === 'clientes') {
        $est = $g('estado');
        $p['titulo'] = 'Directorio de clientes';
        $p['subtitulo'] = $est === '1' || $est === 'activo' ? 'Clientes activos' : ($est === '0' || $est === 'inactivo' ? 'Clientes inactivos' : 'Todos los clientes');
        $p['columnas'] = [
            ['texto' => 'Cédula', 'fijo' => true, 'ancho' => 15], ['texto' => 'Nombre', 'ancho' => 22], ['texto' => 'Teléfono', 'fijo' => true, 'ancho' => 15], ['texto' => 'Correo', 'ancho' => 26],
            ['texto' => 'Compras', 'numero' => true, 'decimales' => 0, 'ancho' => 10], ['texto' => 'Gastado $', 'numero' => true, 'ancho' => 12],
        ];
        $correo = fn($c) => str_ends_with((string) $c, '@providencia.local') ? '' : (string) $c;
        $n = 0; $gas = 0; $top = null;
        $porEstado = rp_agrupar($datos, fn($d) => (string) ($d->estado_nombre ?? 'Activo'));
        ksort($porEstado);
        foreach ($porEstado as $estado => $items) {
            usort($items, fn($a, $b) => strcasecmp((string) $a->nombre, (string) $b->nombre));
            $filas = []; $sc = 0; $sg = 0;
            foreach ($items as $d) {
                $cedula = in_array($d->cedula ?? '', ['SIN_USUARIO', 'raiz'], true) ? '' : (string) $d->cedula;
                $filas[] = [$cedula, (string) ($d->nombre ?? ''), (string) ($d->telefono ?? ''), $correo($d->correo ?? ''), (int) ($d->total_compras ?? 0), (float) ($d->total_gastado ?? 0)];
                $sc += (int) ($d->total_compras ?? 0);
                $sg += (float) ($d->total_gastado ?? 0);
                if ($cedula !== '' && (!$top || (float) $d->total_gastado > (float) $top->total_gastado)) $top = $d;
            }
            $n += $sc;
            $gas += $sg;
            $p['grupos'][] = ['titulo' => $estado . 's · ' . count($items) . (count($items) === 1 ? ' cliente' : ' clientes'), 'filas' => $filas, 'subtotal' => ['Subtotal', '', '', '', $sc, $sg]];
        }
        $p['totales'] = ['Total general', '', '', '', $n, $gas];
        $p['resumen'] = [
            ['texto' => 'Clientes', 'valor' => (string) count($datos), 'nota' => count($porEstado['Activo'] ?? []) . ' activos'],
            ['texto' => 'Compras', 'valor' => (string) $n, 'nota' => 'promedio ' . rp_dinero($n ? $gas / $n : 0)],
            ['texto' => 'Total gastado', 'valor' => rp_dinero($gas), 'nota' => rp_bs($gas * $tasa)],
            ['texto' => 'Mejor cliente', 'valor' => $top ? rp_dinero($top->total_gastado) : 'Sin compras', 'nota' => $top ? (string) $top->nombre : ''],
        ];
        return $p;
    }

    // -------------------------------------------------------------
    // PROVEEDORES
    // -------------------------------------------------------------
    if ($tipo === 'proveedores') {
        $est = $g('estado');
        $p['titulo'] = 'Directorio de proveedores';
        $p['subtitulo'] = $est === '1' || $est === 'activo' ? 'Proveedores activos' : ($est === '0' || $est === 'inactivo' ? 'Proveedores inactivos' : 'Todos los proveedores');
        $p['columnas'] = [
            ['texto' => 'RIF / Cédula', 'fijo' => true, 'ancho' => 16], ['texto' => 'Razón social', 'ancho' => 24], ['texto' => 'Teléfono', 'fijo' => true, 'ancho' => 17], ['texto' => 'Correo', 'ancho' => 31],
            ['texto' => 'Compras', 'numero' => true, 'decimales' => 0, 'ancho' => 12],
        ];
        $n = 0; $conCompras = 0;
        $porEstado = rp_agrupar($datos, fn($d) => (string) ($d->estado ?? 'Activo'));
        ksort($porEstado);
        foreach ($porEstado as $estado => $items) {
            usort($items, fn($a, $b) => strcasecmp((string) $a->razon_social, (string) $b->razon_social));
            $filas = []; $sc = 0;
            foreach ($items as $d) {
                $filas[] = [(string) ($d->rif_cedula ?? $d->rif ?? ''), (string) ($d->razon_social ?? ''), (string) ($d->telefono ?? ''), (string) ($d->correo ?? ''), (int) ($d->compras ?? 0)];
                $sc += (int) ($d->compras ?? 0);
                if ((int) ($d->compras ?? 0) > 0) $conCompras++;
            }
            $n += $sc;
            $p['grupos'][] = ['titulo' => $estado . 's · ' . count($items) . (count($items) === 1 ? ' proveedor' : ' proveedores'), 'filas' => $filas, 'subtotal' => ['Subtotal', '', '', '', $sc]];
        }
        $p['totales'] = ['Total general', '', '', '', $n];
        $p['resumen'] = [
            ['texto' => 'Proveedores', 'valor' => (string) count($datos), 'nota' => count($porEstado['Activo'] ?? []) . ' activos'],
            ['texto' => 'Con compras', 'valor' => (string) $conCompras, 'nota' => 'de ' . count($datos)],
            ['texto' => 'Compras', 'valor' => (string) $n, 'nota' => 'registradas'],
        ];
        return $p;
    }

    // -------------------------------------------------------------
    // COMPRAS
    // -------------------------------------------------------------
    if ($tipo === 'compras') {
        $p['titulo'] = 'Reporte de compras';
        $p['subtitulo'] = rp_periodo($desde, $hasta);
        $p['columnas'] = [
            ['texto' => 'Factura', 'fijo' => true, 'ancho' => 14], ['texto' => 'Proveedor', 'ancho' => 26], ['texto' => 'Artículos', 'numero' => true, 'decimales' => 0, 'ancho' => 11],
            ['texto' => 'Estado', 'ancho' => 13], ['texto' => 'Total $', 'numero' => true, 'ancho' => 13], ['texto' => 'Total Bs', 'numero' => true, 'ancho' => 17],
        ];
        $dia = function ($d) {
            $f = DateTime::createFromFormat('d/m/Y', (string) $d->fecha);
            return $f ? $f->format('Y-m-d') : date('Y-m-d', strtotime((string) $d->fecha));
        };
        $anulada = fn($d) => stripos((string) ($d->estado ?? ''), 'anul') !== false;
        $t = 0; $tb = 0; $uds = 0; $nAnul = 0;
        $porDia = rp_agrupar($datos, $dia);
        krsort($porDia);
        foreach ($porDia as $fecha => $items) {
            $filas = []; $s = 0; $sb = 0;
            foreach ($items as $d) {
                $u = (int) preg_replace('/\D/', '', (string) ($d->articulos ?? '0'));
                $filas[] = [(string) ($d->numero_factura ?? ''), (string) ($d->proveedor ?? ''), $u, (string) ($d->estado ?? ''), (float) $d->total, (float) ($d->total_bs ?? 0)];
                if ($anulada($d)) { $nAnul++; continue; }
                $s += (float) $d->total;
                $sb += (float) ($d->total_bs ?? 0);
                $uds += $u;
            }
            $t += $s;
            $tb += $sb;
            $p['grupos'][] = ['titulo' => rp_fecha_larga($fecha) . ' · ' . count($items) . (count($items) === 1 ? ' compra' : ' compras'), 'filas' => $filas, 'subtotal' => ['Subtotal del día', '', '', '', $s, $sb]];
        }
        $p['totales'] = ['Total general', '', '', '', $t, $tb];
        $p['resumen'] = [
            ['texto' => 'Compras', 'valor' => (string) count($datos), 'nota' => $nAnul ? $nAnul . ($nAnul === 1 ? ' anulada, no suma' : ' anuladas, no suman') : 'en ' . count($porDia) . (count($porDia) === 1 ? ' día' : ' días')],
            ['texto' => 'Total comprado', 'valor' => rp_dinero($t), 'nota' => rp_bs($tb)],
            ['texto' => 'Artículos', 'valor' => (string) $uds, 'nota' => 'unidades recibidas'],
        ];
        return $p;
    }

    $p['titulo'] = ucfirst(mb_strtolower((string) ($reporte['titulo'] ?? 'Reporte')));
    $p['subtitulo'] = rp_periodo($desde, $hasta);
    $claves = $datos ? array_keys(get_object_vars($datos[0])) : [];
    $p['columnas'] = array_map(fn($k) => ucfirst(str_replace('_', ' ', $k)), $claves);
    $p['grupos'][] = ['titulo' => '', 'filas' => array_map(fn($d) => array_map(fn($k) => is_scalar($d->$k) ? $d->$k : '', $claves), $datos)];
    return $p;
}