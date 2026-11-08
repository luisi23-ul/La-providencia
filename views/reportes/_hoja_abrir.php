<?php
$h = $h ?? fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$empresa = (object) ($empresa ?? []);
$modo = $modo ?? 'imprimir';
$subtitulo = $subtitulo ?? '';
$nombre = trim((string) ($empresa->nombre_empresa ?? ''));
$razon  = trim((string) ($empresa->razon_social ?? ''));
$rif    = trim((string) ($empresa->rif ?? ''));
$dir    = trim((string) ($empresa->direccion ?? ''));
$logo   = trim((string) ($empresa->logo_path ?? ''));
if ($logo !== '' && $modo === 'dompdf' && !preg_match('#^(https?:|data:|[a-zA-Z]:[\\\\/]|/)#', $logo)) {
    $logo = realpath(__DIR__ . '/../../' . ltrim($logo, '/')) ?: $logo;
}

$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$ahora = time();
$fecha = date('j', $ahora) . ' de ' . $meses[date('n', $ahora) - 1] . ' de ' . date('Y', $ahora);
$hora  = date('g:i', $ahora) . (date('G', $ahora) < 12 ? ' a. m.' : ' p. m.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= $h($titulo . ($nombre !== '' ? ' · ' . $nombre : '')) ?></title>
<?php if ($modo !== 'dompdf'): ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
<?php endif; ?>
<style>
@page { size: letter; margin: 16mm 14mm 18mm; }
<?php if ($modo !== 'dompdf'): ?>
@page { @bottom-left { content: "<?= $h(addslashes($nombre)) ?>"; font: 500 8pt 'Jost', Arial, sans-serif; color: #8a7f74; } @bottom-right { content: "Página " counter(page) " de " counter(pages); font: 500 8pt 'Jost', Arial, sans-serif; color: #5b5148; } }
<?php endif; ?>
* { box-sizing: border-box; }
html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
body { margin: 0; background: #fff; color: #2a2420; font: 400 9.5pt/1.45 'Jost', 'DejaVu Sans', Arial, sans-serif; }
.hoja { max-width: 190mm; margin: 0 auto; }
.cab { width: 100%; border-collapse: collapse; margin-bottom: 7mm; }
.cab td { vertical-align: middle; padding: 0; }
.cab-logo { width: 46mm; }
.cab-logo img { display: block; max-width: 42mm; max-height: 18mm; }
.cab-logo .marca { font: 600 20pt/1 'Cormorant Garamond', Georgia, serif; letter-spacing: .06em; text-transform: uppercase; }
.cab-emp { padding-left: 5mm !important; border-left: 1px solid #e3d8ca; font-size: 8.5pt; color: #5b5148; }
.cab-emp b { display: block; font-size: 10pt; font-weight: 600; color: #2a2420; }
.cab-fecha { text-align: right; font-size: 8.5pt; color: #5b5148; white-space: nowrap; }
.cab-fecha span { display: block; font-size: 7.5pt; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: #8c4a2f; }
.cab-fecha b { display: block; font-size: 10pt; font-weight: 500; color: #2a2420; }
.titulo { padding: 4mm 0 4mm; border-top: 2px solid #2a2420; margin-bottom: 4mm; }
.titulo h1 { margin: 0; font: 600 19pt/1.1 'Cormorant Garamond', Georgia, serif; }
.titulo p { margin: 1.5mm 0 0; font-size: 9pt; color: #5b5148; }
.datos { width: 100%; table-layout: fixed; border-collapse: collapse; font-variant-numeric: tabular-nums; }
.datos td, .datos th { overflow-wrap: anywhere; }
.datos-total { margin-top: 2mm; break-before: avoid; page-break-before: avoid; }
.datos.auto { table-layout: auto; }
.datos th { padding: 2.2mm 2.5mm; border-bottom: 1.2px solid #2a2420; text-align: left; font-size: 7.5pt; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #5b5148; }
.datos td { padding: 2mm 2.5mm; border-bottom: 1px solid #eee6db; vertical-align: top; }
.datos .num { text-align: right; white-space: nowrap; }
.datos tr, .datos.junto { page-break-inside: avoid; break-inside: avoid; }
.datos .cols th { background: #f5f0e8; border-bottom: 1px solid #cfc2b2; }
.datos .grupo th { padding: 5mm 2.5mm 1.8mm; border-bottom: 0; background: none; font-size: 10pt; font-weight: 600; letter-spacing: 0; text-transform: none; color: #2a2420; }
main > .datos:first-of-type .grupo th, section + .datos .grupo th { padding-top: 1mm; }
.datos .sub td { border-bottom: 0; padding-top: 1.8mm; padding-bottom: 3mm; font-weight: 600; color: #2a2420; border-top: 1px solid #cfc2b2; }
.datos .sub td:first-child { font-weight: 500; color: #5b5148; }
.datos .total td { padding: 3mm 2.5mm; border-top: 2px solid #2a2420; border-bottom: 0; font-size: 10pt; font-weight: 600; }
.datos .total td:first-child { color: #8c4a2f; letter-spacing: .08em; text-transform: uppercase; font-size: 8.5pt; }
.resumen { width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 6mm; border: 1px solid #e3d8ca; }
.resumen td { padding: 4.2mm 4.5mm 4mm; border-left: 1px solid #e3d8ca; vertical-align: top; }
.resumen span { display: block; font-size: 8pt; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #8c4a2f; }
.resumen b { display: block; margin-top: 1.8mm; font-size: 16pt; line-height: 1.1; font-weight: 500; font-variant-numeric: tabular-nums; white-space: nowrap; }
.resumen small { display: block; margin-top: 1.6mm; min-height: 1.35em; font-size: 9.5pt; line-height: 1.35; color: #4a413a; }
.vacio { padding: 10mm 0; text-align: center; color: #5b5148; }
.firma { margin-top: 6mm; font-size: 8pt; color: #8a7f74; }
.ficha { width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 6mm; border: 1px solid #e3d8ca; }
.ficha td { padding: 3.2mm 4.5mm; border-left: 1px solid #e3d8ca; vertical-align: top; }
.ficha span { display: block; font-size: 7.5pt; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #8c4a2f; }
.ficha b { display: block; margin-top: 1mm; font-size: 10.5pt; font-weight: 600; }
.ficha small { display: block; margin-top: .6mm; font-size: 9pt; line-height: 1.4; color: #4a413a; }
.bloque { margin: 0 0 2.5mm; font-size: 7.5pt; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: #5b5148; }
.cuenta { width: 82mm; margin: 4mm 0 7mm auto; border-collapse: collapse; font-variant-numeric: tabular-nums; }
.cuenta td { padding: 1.6mm 2.5mm; font-size: 9.5pt; }
.cuenta td + td { text-align: right; white-space: nowrap; }
.cuenta .tasa td { font-size: 8.5pt; color: #5b5148; }
.cuenta .grande td { padding-top: 2.6mm; border-top: 2px solid #2a2420; font-size: 11pt; font-weight: 600; }
.cuenta .grande td:first-child { font-size: 8.5pt; letter-spacing: .08em; text-transform: uppercase; color: #8c4a2f; }
.cuenta .bs td { padding-top: .4mm; font-size: 10pt; font-weight: 500; }
.cuenta .saldo td { border-top: 1px solid #cfc2b2; font-weight: 600; }
.cuenta .saldo.ok td + td { color: #2f6b45; }
.cuenta .saldo.pend td + td { color: #b42318; }
.est-pago { display: inline-block; padding: .3mm 2mm; border-radius: 3mm; font-size: 8pt; font-weight: 600; background: #f5f0e8; color: #5b5148; }
.est-pago.ok { background: #e6f1ea; color: #2f6b45; }
.est-pago.agot { background: #fbe9e7; color: #b42318; }
.firmas { width: 100%; table-layout: fixed; border-collapse: collapse; margin-top: 10mm; page-break-inside: avoid; break-inside: avoid; }
.firmas td { padding: 0 6mm 0 0; vertical-align: top; font-size: 8.5pt; color: #5b5148; }
.firmas td + td { padding: 0 0 0 6mm; }
.firmas .linea { height: 10mm; border-bottom: 1px solid #2a2420; margin-bottom: 1.8mm; }
.firmas b { display: block; font-size: 9pt; font-weight: 600; color: #2a2420; }
.aclara { margin-top: 5mm; font-size: 7.5pt; color: #8a7f74; }
.pie-dompdf { position: fixed; left: 0; right: 0; bottom: -11mm; font-size: 8pt; color: #5b5148; }
.pie-dompdf td { padding: 0; }
.pie-dompdf .pag:after { content: "Página " counter(page) " de " counter(pages); }
@media screen { body { background: #ede1d3; padding: 24px 16px; } .hoja { background: #fff; padding: 16mm 14mm; box-shadow: 0 18px 40px -24px rgba(110, 70, 45, .45); } }
</style>
</head>
<body>
<?php if ($modo === 'dompdf'): ?>
<footer class="pie-dompdf"><table width="100%"><tr><td><?= $h($nombre) ?></td><td align="right" class="pag"></td></tr></table></footer>
<?php endif; ?>
<main class="hoja">
  <header>
    <table class="cab" role="presentation">
      <tr>
        <td class="cab-logo"><?php if ($logo !== ''): ?><img src="<?= $h($logo) ?>" alt="<?= $h($nombre) ?>"><?php else: ?><span class="marca"><?= $h($nombre) ?></span><?php endif; ?></td>
        <td class="cab-emp">
          <?php if ($razon !== '' || $nombre !== ''): ?><b><?= $h($razon !== '' ? $razon : $nombre) ?></b><?php endif; ?>
          <?php if ($rif !== ''): ?>RIF <?= $h($rif) ?><br><?php endif; ?>
          <?= $h($dir) ?>
        </td>
        <td class="cab-fecha"><span>Elaborado</span><b><?= $fecha ?></b><?= $hora ?></td>
      </tr>
    </table>
    <section class="titulo">
      <h1><?= $h($titulo) ?></h1>
      <?php if ($subtitulo !== ''): ?><p><?= $h($subtitulo) ?></p><?php endif; ?>
    </section>
  </header>
