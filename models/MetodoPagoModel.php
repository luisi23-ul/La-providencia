<?php
$menu = 'metodos_pago';
$tituloPagina = 'Lo cobrado - ' . ($metodo->nombre ?? 'Método');
include __DIR__ . '/admin/_abrir.php';

$e = $e ?? fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$usd = $usd ?? fn($n) => '$' . number_format((float)($n ?? 0), 2, '.', ',');
$bs = $bs ?? fn($n) => number_format((float)($n ?? 0), 2, ',', '.') . ' Bs';

$rangoActivo = $_GET['rango'] ?? 'este_mes';
$urlRango = fn($r) => 'index.php?' . http_build_query(array_merge($_GET, ['rango' => $r]));
?>
<section class="contenido">
  <?php if (!empty($aviso)): ?>
    <div class="aviso-exito" style="background:#dcfce7; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:1rem; font-weight:500;">
      <?= $e($aviso) ?>
    </div>
  <?php endif; ?>

  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
    <div style="display:flex; align-items:center; gap:10px;">
      <a href="index.php?action=metodos_pago" class="ic-btn" aria-label="Volver"><svg class="i"><use href="#i-volver"/></svg></a>
      <h2 style="margin:0;"><?= $e($metodo->nombre ?? 'Este método') ?></h2>
    </div>
    <div class="cab-acc">
      <a href="index.php?action=exportar_excel&tipo=cobros&metodo=<?= (int)$metodo->id ?>" class="btn btn-sec">
        <svg class="i"><use href="#i-archivo"/></svg> Excel
      </a>
    </div>
  </div>

  <!-- BOTONES RÁPIDOS Y FILTROS -->
  <div class="caja" style="margin-bottom:1.5rem; padding:14px;">
    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:12px;">
      <!-- RANGOS RÁPIDOS -->
      <nav class="f-grupo" aria-label="Rango de fecha">
        <a href="<?= $e($urlRango('hoy')) ?>" class="f-op" aria-current="<?= $rangoActivo === 'hoy' ? 'page' : 'false' ?>">Hoy</a>
        <a href="<?= $e($urlRango('ayer')) ?>" class="f-op" aria-current="<?= $rangoActivo === 'ayer' ? 'page' : 'false' ?>">Ayer</a>
        <a href="<?= $e($urlRango('7dias')) ?>" class="f-op" aria-current="<?= $rangoActivo === '7dias' ? 'page' : 'false' ?>">7 días</a>
        <a href="<?= $e($urlRango('este_mes')) ?>" class="f-op" aria-current="<?= $rangoActivo === 'este_mes' ? 'page' : 'false' ?>">Este mes</a>
      </nav>

      <!-- FORMULARIO DE FILTROS ESPECÍFICOS -->
      <form method="GET" action="index.php" style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
        <input type="hidden" name="action" value="ver_reporte">
        <input type="hidden" name="metodo" value="<?= (int)$metodo->id ?>">

        <!-- BANCO -->
        <select name="banco" onchange="this.form.submit()" style="padding:6px 10px; border-radius:6px; border:1px solid #cbd5e1; font-size:13px;">
          <option value="">Todos los bancos</option>
          <?php foreach ($bancosDisponibles as $b): ?>
            <option value="<?= $e($b) ?>" <?= ($bancoSel === $b) ? 'selected' : '' ?>><?= $e($b) ?></option>
          <?php endforeach; ?>
        </select>

        <!-- ESTADO -->
        <select name="estado" onchange="this.form.submit()" style="padding:6px 10px; border-radius:6px; border:1px solid #cbd5e1; font-size:13px;">
          <option value="">Todos los estados</option>
          <option value="aprobado" <?= ($estadoSel === 'aprobado') ? 'selected' : '' ?>>Solo Aprobados</option>
          <option value="pendiente" <?= ($estadoSel === 'pendiente') ? 'selected' : '' ?>>Pendientes por verificar</option>
        </select>

        <!-- FECHAS PERSONALIZADAS -->
        <input type="date" name="desde" value="<?= $e($desde) ?>" style="padding:5px 8px; border-radius:6px; border:1px solid #cbd5e1; font-size:13px;">
        <input type="date" name="hasta" value="<?= $e($hasta) ?>" style="padding:5px 8px; border-radius:6px; border:1px solid #cbd5e1; font-size:13px;">
        <button type="submit" class="btn btn-sec btn-peq">Filtrar</button>

        <?php if (!empty($bancoSel) || !empty($estadoSel) || $rangoActivo !== 'este_mes'): ?>
          <a href="index.php?action=ver_reporte&metodo=<?= (int)$metodo->id ?>" class="f-quitar" style="font-size:12px;">Quitar filtros</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- TARJETAS SUPERIORES DE TOTALES -->
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; margin-bottom:1.5rem;">
    <div class="caja" style="padding:16px;">
      <small style="color:#64748b;">Cobrado</small>
      <h3 style="margin:4px 0 0 0; font-size:1.5rem; color:#166534;"><?= $usd($totales->total_usd ?? 0) ?></h3>
      <span style="font-size:12px; color:#64748b;"><?= $bs($totales->total_bs ?? 0) ?></span>
    </div>

    <div class="caja" style="padding:16px;">
      <small style="color:#64748b;">Ventas / Cobros</small>
      <h3 style="margin:4px 0 0 0; font-size:1.5rem;"><?= (int)($totales->total_ventas ?? 0) ?></h3>
      <span style="font-size:12px; color:#64748b;">
        Promedio: <?= $usd(($totales->total_ventas ?? 0) > 0 ? ($totales->total_usd / $totales->total_ventas) : 0) ?>
      </span>
    </div>

    <div class="caja" style="padding:16px;">
      <small style="color:#64748b;">Días con cobros</small>
      <h3 style="margin:4px 0 0 0; font-size:1.5rem;"><?= (int)($totales->dias_con_cobros ?? 0) ?></h3>
      <span style="font-size:12px; color:#64748b;">En el rango filtrado</span>
    </div>
  </div>

  <!-- TABLA DETALLADA DE COBROS CON RESPONSABLES -->
  <section class="tabla-caja">
    <div class="tabla-scroll">
      <table style="width:100%;">
        <thead>
          <tr>
            <th class="c-media">Venta</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Banco / Referencia</th>
            <th class="num">Monto ($)</th>
            <th class="num">Monto (Bs)</th>
            <th>Cobrado por</th>
            <th>Verificado por</th>
            <th class="num">Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($listaPagos)): ?>
            <tr>
              <td colspan="9" style="text-align:center; padding:2rem; color:#64748b;">
                No hubo cobros con este método bajo los filtros seleccionados.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($listaPagos as $pago): 
              $esAprobado = strtolower($pago->estado_verificacion ?? '') === 'aprobado';
              $montoBs = (float)$pago->monto * (float)$pago->tasa_cambio;
            ?>
              <tr>
                <td class="cod c-media">#<?= (int)$pago->id_venta ?></td>
                <td>
                  <?= date('d/m/Y', strtotime($pago->fecha_pago)) ?><br>
                  <small style="color:#64748b;"><?= date('h:i A', strtotime($pago->fecha_pago)) ?></small>
                </td>
                <td><b><?= $e($pago->cliente_nombre) ?></b></td>
                <td>
                  <b><?= $e($pago->banco ?? 'Caja') ?></b>
                  <?php if (!empty($pago->referencia)): ?>
                    <br><small style="color:#64748b;">Ref: <?= $e($pago->referencia) ?></small>
                  <?php endif; ?>
                </td>
                <td class="num"><b><?= $usd($pago->monto) ?></b></td>
                <td class="num"><?= $bs($montoBs) ?></td>
                <td>
                  <span class="chip" style="font-size:0.75rem; background:#f1f5f9;">
                    <?= $e($pago->cajero_nombre) ?>
                  </span>
                </td>
                <td>
                  <?php if ($esAprobado): ?>
                    <span class="chip" style="font-size:0.75rem; background:#dcfce7; color:#166534;">
                      <?= $e($pago->verificador_nombre) ?>
                    </span>
                  <?php else: ?>
                    <form method="POST" action="index.php?action=confirmar_pago" style="display:inline;">
                      <input type="hidden" name="id_pago" value="<?= (int)$pago->id ?>">
                      <input type="hidden" name="id_metodo" value="<?= (int)$metodo->id ?>">
                      <button type="submit" class="btn btn-sec btn-peq" style="color:#166534; border-color:#86efac;">
                        Aprobar
                      </button>
                    </form>
                  <?php endif; ?>
                </td>
                <td class="num">
                  <span style="font-weight:600; font-size:12px; color:<?= $esAprobado ? '#166534' : '#ca8a04' ?>;">
                    <?= $esAprobado ? 'Aprobado' : 'Pendiente' ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>
<?php include __DIR__ . '/admin/_cerrar.php'; ?>