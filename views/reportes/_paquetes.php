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
            ['texto' => 'Código', 'ancho' => 12], ['texto' => 'Producto', 'ancho' => 28], ['texto' => 'Categoría', 'ancho' => 16],
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
                $sStock += (float) $d->stock; $sValor += $valor;
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
            ['texto' => 'Valor a precio', 'valor' => rp_dinero($tPrecio), 'nota' => 'margen ' . ($tPrecio > 0 ? round(($tPrecio - $tCosto) / $tPrecio * 100) : 0) . ' %'],
        ];
        return $p;
    }

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

    if ($tipo === 'retiros' || $tipo === 'pendientes') {
        $etiquetas = ['pagado' => 'Por retirar', 'por_retirar' => 'Por retirar', 'retirado' => 'Retirado', 'entregado' => 'Entregado', 'pendiente' => 'Pendiente de pago', 'por_verificar' => 'Por verificar'];
        $p['titulo'] = $tipo === 'retiros' ? 'Reporte de retiros' : 'Reporte de pagos pendientes';
        $p['subtitulo'] = $tipo === 'retiros' ? ($g('estado') === 'entregado' ? 'Pedidos entregados' : 'Pedidos listos para retirar') : 'Ventas con pago pendiente o por verificar';
        $p['columnas'] = [
            ['texto' => 'N.º venta', 'ancho' => 14], ['texto' => 'Cliente', 'ancho' => 24], ['texto' => 'Hora', 'ancho' => 10], ['texto' => 'Estado', 'ancho' => 16],
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
            ['texto' => 'Hora', 'ancho' => 10], ['texto' => 'N.º venta', 'ancho' => 13], ['texto' => 'Cliente', 'ancho' => 20], ['texto' => 'Método', 'ancho' => 14],
            ['texto' => 'Banco y referencia', 'ancho' => 24], ['texto' => 'Monto', 'numero' => true, 'ancho' => 13], ['texto' => 'Monto Bs', 'numero' => true, 'ancho' => 16],
        ];
        $t = 0; $tBs = 0;
        $porDia = rp_agrupar($datos, fn($d) => date('Y-m-d', strtotime($d->fecha)));
        krsort($porDia);
        foreach ($porDia as $dia => $items) {
            $filas = []; $s = 0; $sBs = 0;
            foreach ($items as $d) {
                $filas[] = [date('g:i a', strtotime($d->fecha)), $d->id ?? '', $d->nombre ?? '', $d->metodo_nombre ?? '', implode(' · ', array_filter([$d->banco ?? '', $d->referencia ?? ''])), (float) $d->total, (float) ($d->total_bs ?? 0)];
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
            ['texto' => 'Ganancia', 'valor' => rp_dinero($ganancia), 'nota' => 'margen ' . ($vendido > 0 ? round($ganancia / $vendido * 100) : 0) . ' %'],
            ['texto' => 'Tienda / online', 'valor' => ($vendido > 0 ? round($tienda / $vendido * 100) : 0) . ' % / ' . ($vendido > 0 ? 100 - round($tienda / $vendido * 100) : 0) . ' %', 'nota' => 'del total vendido'],
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
