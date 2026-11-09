<?php
$menu = 'control';
$tituloPagina = 'Cierres de caja y arqueos';
include __DIR__ . '/_abrir.php';
?>

<div class="pantalla-cabecera" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
  <div>
    <h1 style="margin:0; font-size:1.6rem; color:#2a2420;">Cierres y arqueos de caja</h1>
    <p style="margin:4px 0 0; color:#64748b; font-size:0.9rem;">Registro secuencial de turnos, fondos de apertura y montos declarados al cierre.</p>
  </div>
</div>

<form method="GET" action="index.php" class="filtro-barra" style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; background:#fff; padding:15px; border-radius:10px; border:1px solid #e2e8f0;">
  <input type="hidden" name="action" value="turnos_caja">
  
  <div>
    <label style="display:block; font-size:0.8rem; color:#64748b; margin-bottom:4px;">Cajero</label>
    <select name="cajero" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
      <option value="">Todos los cajeros</option>
      <?php foreach ($cajeros as $c): ?>
        <option value="<?= htmlspecialchars($c->cedula) ?>" <?= (isset($_GET['cajero']) && $_GET['cajero'] === $c->cedula) ? 'selected' : '' ?>>
          <?= htmlspecialchars($c->nombre . ' ' . $c->apellido . ' (' . $c->cedula . ')') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label style="display:block; font-size:0.8rem; color:#64748b; margin-bottom:4px;">Desde</label>
    <input type="date" name="desde" value="<?= htmlspecialchars($_GET['desde'] ?? '') ?>" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
  </div>

  <div>
    <label style="display:block; font-size:0.8rem; color:#64748b; margin-bottom:4px;">Hasta</label>
    <input type="date" name="hasta" value="<?= htmlspecialchars($_GET['hasta'] ?? '') ?>" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
  </div>

  <div style="align-self:flex-end;">
    <button type="submit" class="btn btn-pri" style="padding:9px 18px; border-radius:6px; cursor:pointer;">Filtrar</button>
    <a href="index.php?action=turnos_caja" class="btn btn-sec" style="padding:9px 14px; border-radius:6px; text-decoration:none; margin-left:6px;">Limpiar</a>
  </div>
</form>

<div style="background:#fff; border-radius:10px; border:1px solid #e2e8f0; overflow:hidden;">
  <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.9rem;">
    <thead>
      <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569;">
        <th style="padding:12px 16px;">Turno</th>
        <th style="padding:12px 16px;">Cajero</th>
        <th style="padding:12px 16px;">Apertura</th>
        <th style="padding:12px 16px;">Cierre</th>
        <th style="padding:12px 16px;">Fondo inicial</th>
        <th style="padding:12px 16px;">Cierre declarado</th>
        <th style="padding:12px 16px;">Estado</th>
        <th style="padding:12px 16px; text-align:center;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($turnos)): ?>
        <tr><td colspan="8" style="padding:24px; text-align:center; color:#94a3b8;">No hay turnos registrados en este rango.</td></tr>
      <?php else: foreach ($turnos as $t): ?>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:12px 16px; font-weight:bold;">#<?= (int)$t->numero_turno ?></td>
          <td style="padding:12px 16px;">
            <b><?= htmlspecialchars($t->cajero_nombre . ' ' . $t->cajero_apellido) ?></b><br>
            <small style="color:#64748b;"><?= htmlspecialchars($t->cedula_cajero) ?></small>
          </td>
          <td style="padding:12px 16px;">
            <?= date('d/m/Y g:i a', strtotime($t->fecha_apertura)) ?><br>
            <small style="color:#64748b;">Tasa: <?= number_format($t->tasa_apertura, 2, ',', '.') ?> Bs/$</small>
          </td>
          <td style="padding:12px 16px;">
            <?= $t->fecha_cierre ? date('d/m/Y g:i a', strtotime($t->fecha_cierre)) : '<span style="color:#f59e0b; font-weight:600;">En curso</span>' ?>
          </td>
          <td style="padding:12px 16px;">
            $<?= number_format($t->fondo_usd, 2, ',', '.') ?><br>
            <small style="color:#64748b;"><?= number_format($t->fondo_bs, 2, ',', '.') ?> Bs</small>
          </td>
          <td style="padding:12px 16px;">
            <?php if ($t->estado === 'cerrado'): ?>
              $<?= number_format($t->cierre_usd, 2, ',', '.') ?><br>
              <small style="color:#64748b;"><?= number_format($t->cierre_bs, 2, ',', '.') ?> Bs</small>
            <?php else: ?>
              <span style="color:#94a3b8;">--</span>
            <?php endif; ?>
          </td>
          <td style="padding:12px 16px;">
            <?php if ($t->estado === 'cerrado'): ?>
              <span style="display:inline-block; padding:4px 8px; font-size:0.75rem; border-radius:999px; background:#dcfce7; color:#15803d; font-weight:600;">Cerrado</span>
            <?php else: ?>
              <span style="display:inline-block; padding:4px 8px; font-size:0.75rem; border-radius:999px; background:#fef3c7; color:#b45309; font-weight:600;">Abierto</span>
            <?php endif; ?>
          </td>
          <td style="padding:12px 16px; text-align:center;">
            <?php if ($t->estado === 'cerrado'): ?>
              <a href="index.php?action=exportar_pdf&tipo=cierre_caja&cedula=<?= urlencode($t->cedula_cajero) ?>&turno=<?= (int)$t->numero_turno ?>" target="_blank" style="display:inline-block; padding:6px 10px; background:#f1f5f9; border-radius:6px; color:#334155; text-decoration:none; font-size:0.8rem; font-weight:600;">
                Imprimir comprobante
              </a>
            <?php else: ?>
              <span style="color:#94a3b8; font-size:0.8rem;">En turno</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/_cerrar.php'; ?>