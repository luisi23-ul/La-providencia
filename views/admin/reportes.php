<?php
$menu = 'reportes';
$tituloPagina = 'Reportes';
$scripts = ['reportes'];
include __DIR__ . '/_abrir.php';
require_once __DIR__ . '/../../models/MetodoPagoModel.php';

$periodo = ['desde' => $_GET['desde'] ?? date('Y-m-01'), 'hasta' => $_GET['hasta'] ?? date('Y-m-d')];
$diaCierre = $periodo['hasta'] !== '' ? $periodo['hasta'] : date('Y-m-d');
$sel = fn($k) => trim((string) ($_GET[$k] ?? ''));
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$diaCorto = function ($f) use ($meses) {
    $t = strtotime($f);
    if (!$t) return $f;
    return date('j', $t) . ' ' . $meses[date('n', $t) - 1] . (date('Y', $t) !== date('Y') ? ' ' . date('Y', $t) : '');
};

$opClientes = [];
foreach ($db->query('SELECT cedula, nombre_razon_social FROM clientes ORDER BY nombre_razon_social')->fetchAll(PDO::FETCH_OBJ) as $c) $opClientes[(string) $c->cedula] = in_array($c->cedula, ['SIN_USUARIO', 'raiz'], true) ? (string) $c->nombre_razon_social : $c->nombre_razon_social . ' · ' . $c->cedula;
$opMetodos = [];
$modeloMetodos = new MetodoPagoModel($db);
foreach ($modeloMetodos->listar() as $m) $opMetodos[(string) $m->codigo_metodo] = (string) $m->nombre;
$opBancos = [];
foreach ($modeloMetodos->obtenerBancosDisponibles() as $bn) $opBancos[(string) $bn] = (string) $bn;
$opMetodosBanco = array_intersect_key($opMetodos, ['pago_movil' => 1, 'transferencia' => 1]);
$opProveedores = [];
foreach ($db->query('SELECT razon_social FROM proveedores ORDER BY razon_social')->fetchAll(PDO::FETCH_COLUMN) as $pr) $opProveedores[(string) $pr] = (string) $pr;
$opMovimientos = ['compra' => 'Compra', 'venta_tienda' => 'Venta en tienda', 'venta_online' => 'Venta online', 'ajuste' => 'Ajuste por conteo', 'merma' => 'Merma', 'transferencia' => 'Transferencia', 'anulacion_compra' => 'Compra anulada'];

$filtro = fn($nombre, $icono, $todos, $opciones, $buscar = '', $param = '') => compact('nombre', 'icono', 'todos', 'opciones', 'buscar', 'param');
$secciones = [
    'Ventas' => [
        ['Compras de un cliente', 'Todo lo que compró un cliente en el periodo', 'cliente', $periodo + ['cedula' => $sel('cedula')], $filtro('cedula', 'usuarios', 'Elegir cliente', $opClientes, 'Buscar cliente')],
        ['Ventas', 'Cada venta con su cliente, estado, origen y total', 'ventas', $periodo],
        ['Cobrado por método', 'Lo cobrado en el periodo por método de pago', 'metodo', $periodo + ['metodo' => $sel('metodo')], $filtro('metodo', 'tarjeta', 'Todos los métodos', $opMetodos)],
        ['Pagos del banco', 'Pagos móviles y transferencias por banco', 'pagos_banco', $periodo + ['banco' => $sel('banco'), 'metodo' => $sel('metodo_banco')], [$filtro('banco', 'banco', 'Todos los bancos', $opBancos, 'Buscar banco'), $filtro('metodo_banco', 'tarjeta', 'Todos los métodos', $opMetodosBanco, '', 'metodo')]],
        ['Pagos pendientes', 'Ventas que aún no se han pagado', 'pendientes', null],
        ['Pedidos por retirar', 'Pedidos pagados que esperan su retiro', 'retiros', null],
    ],
    'Caja' => [
        ['Cierres de caja', 'Los turnos cerrados con su fondo y lo contado', 'cierre_caja', $periodo],
        ['Cierre del día', 'Arqueo de todos los turnos de un día', 'cierre', ['fecha' => $diaCierre]],
    ],
    'Inventario' => [
        ['Inventario', 'Productos con su stock y precio actuales', 'inventario', ['stock' => $sel('stock')], $filtro('stock', 'caja', 'Todo el stock', ['bajo' => 'Stock bajo', 'agotado' => 'Agotados']), true],
        ['Movimientos', 'Entradas y salidas de stock (kardex)', 'kardex', $periodo + ['movimiento' => $sel('movimiento')], $filtro('movimiento', 'lista', 'Todos los tipos', $opMovimientos)],
    ],
    'Compras' => [
        ['Compras', 'Facturas de compra con proveedor y total', 'compras', $periodo + ['q' => $sel('q')], $filtro('q', 'camion', 'Todos los proveedores', $opProveedores, 'Buscar proveedor')],
        ['Proveedores', 'Lista de proveedores con sus datos de contacto', 'proveedores', null],
    ],
    'Gestión' => [
        ['Clientes', 'Clientes registrados con sus compras', 'clientes', null],
        ['Estadísticas', 'Ventas por día y productos más vendidos', 'estadisticas', $periodo],
    ],
];
if (es_rol('master', $rol)) $secciones['Gestión'][] = ['Auditoría', 'Cambios hechos en el sistema y quién los hizo', 'auditoria', $periodo];
?>
<section class="contenido">
  <header class="cab">
    <div class="cab-txt"><small>Gestión</small><h2>Reportes</h2></div>
  </header>

  <form class="filtros" id="rp-filtros" method="GET" action="index.php" aria-label="Periodo de los reportes">
    <input type="hidden" name="action" value="reportes">
    <?php $periodoFiltro = ['modo' => 'rango', 'defecto' => 'Este mes', 'atajos' => ['Hoy', 'Ayer', '7 días', 'Este mes', 'Mes pasado']]; include __DIR__ . '/_periodo.php'; ?>
    <p class="rp-periodo">Los reportes se descargan <?php if ($periodo['desde'] === $periodo['hasta']): ?>del <b><?= $e($diaCorto($periodo['desde'])) ?></b><?php else: ?>del <b><?= $e($diaCorto($periodo['desde'])) ?></b> al <b><?= $e($diaCorto($periodo['hasta'])) ?></b><?php endif; ?></p>
  </form>

  <section class="tabla-caja" aria-label="Reportes disponibles">
    <div class="tabla-scroll">
      <table class="tabla-resp rp-tabla">
        <thead>
          <tr>
            <th>Reporte</th>
            <th>Qué incluye</th>
            <th class="num">Descargar</th>
          </tr>
        </thead>
        <?php foreach ($secciones as $seccion => $reportes): ?>
        <tbody>
          <tr><th scope="rowgroup" colspan="3"><?= $e($seccion) ?></th></tr>
          <?php foreach ($reportes as $r):
            [$nombre, $incluye, $tipo, $filtros] = $r;
            $fils = isset($r[4]['nombre']) ? [$r[4]] : ($r[4] ?? []);
            $hoy = $filtros === null || !empty($r[5]);
            $falta = $tipo === 'cliente' && $sel('cedula') === ''; ?>
          <tr<?= $tipo === 'cliente' ? ' data-requiere="cedula"' : '' ?><?= $falta ? ' class="rp-espera"' : '' ?>>
            <td class="t-tit" data-label="Reporte"><b><?= $e($nombre) ?></b><?php if ($hoy): ?><span class="rp-hoy">Al día de hoy</span><?php endif; ?></td>
            <td class="rp-que" data-label="Qué incluye">
              <span><?= $e($incluye) ?></span>
              <?php foreach ($fils as $fil): $valor = $sel($fil['nombre']); ?>
              <label class="f-select rp-filtro<?= $valor !== '' ? ' activo' : '' ?>"><svg class="i"><use href="#i-<?= $fil['icono'] ?>"/></svg>
                <select name="<?= $fil['nombre'] ?>" form="rp-filtros" aria-label="<?= $e($fil['todos']) ?> para <?= $e($nombre) ?>" data-rp-filtro<?= $fil['param'] !== '' ? ' data-param="' . $fil['param'] . '"' : '' ?> data-desplegable<?= $fil['buscar'] !== '' ? ' data-buscar="' . $e($fil['buscar']) . '"' : '' ?>>
                  <option value=""><?= $e($fil['todos']) ?></option>
                  <?php foreach ($fil['opciones'] as $k => $t): ?><option value="<?= $e($k) ?>"<?= $valor === (string) $k ? ' selected' : '' ?>><?= $e($t) ?></option><?php endforeach; ?>
                </select>
              </label>
              <?php endforeach; ?>
            </td>
            <td class="t-acc"><div class="acc"><?php $exportar = ['tipo' => $tipo, 'filtros' => $filtros ?? [], 'ver' => true]; include __DIR__ . '/_exportar.php'; ?></div></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <?php endforeach; ?>
      </table>
    </div>
  </section>
</section>
<?php include __DIR__ . '/_cerrar.php'; ?>
