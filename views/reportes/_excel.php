<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

function reporte_excel(array $r): Spreadsheet
{
    $e = (object) ($r['empresa'] ?? []);
    $nombre = trim((string) ($e->nombre_empresa ?? ''));
    $razon = trim((string) ($e->razon_social ?? ''));
    $cols = array_map(fn($c) => (is_array($c) ? $c : ['texto' => $c]) + ['numero' => false, 'decimales' => 2], $r['columnas'] ?? []);
    $n = max(count($cols), 1);
    $L = fn(int $i) => Coordinate::stringFromColumnIndex($i + 1);
    $ult = $L($n - 1);

    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $fecha = $r['fecha'] ?? date('j') . ' de ' . $meses[date('n') - 1] . ' de ' . date('Y');
    $hora = $r['hora'] ?? date('g:i') . (date('G') < 12 ? ' a. m.' : ' p. m.');

    $libro = new Spreadsheet();
    $libro->getProperties()->setCreator($nombre)->setTitle($r['titulo'] ?? 'Reporte');
    $libro->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10)->getColor()->setRGB('2A2420');
    $h = $libro->getActiveSheet();
    $h->setTitle(mb_substr(preg_replace('#[\\\\/?*\[\]:]#', '', $r['titulo'] ?? 'Reporte'), 0, 31));
    $h->setShowGridlines(false);
    $h->getDefaultRowDimension()->setRowHeight(15);

    $logo = trim((string) ($e->logo_path ?? ''));
    if ($logo !== '' && !is_file($logo)) $logo = __DIR__ . '/../../' . ltrim($logo, '/');
    if ($logo !== '' && is_file($logo)) {
        $d = new Drawing();
        $d->setPath($logo);
        $d->setHeight(44);
        $d->setCoordinates('A1');
        $d->setOffsetX(4);
        $d->setOffsetY(4);
        $d->setWorksheet($h);
    } else {
        $h->setCellValue('A1', $nombre);
        $h->getStyle('A1')->getFont()->setName('Cambria')->setBold(true)->setSize(16);
    }
    $c = $n >= 4 ? 'C' : 'B';
    $h->setCellValue($c . '1', $razon !== '' ? $razon : $nombre);
    $h->getStyle($c . '1')->getFont()->setBold(true)->setSize(11);
    if (!empty($e->rif)) $h->setCellValue($c . '2', 'RIF ' . $e->rif);
    $h->setCellValue($c . '3', (string) ($e->direccion ?? ''));
    $h->getStyle($c . '2:' . $c . '3')->getFont()->getColor()->setRGB('5B5148');
    $h->setCellValue($ult . '1', 'ELABORADO');
    $h->getStyle($ult . '1')->getFont()->setBold(true)->setSize(8)->getColor()->setRGB('8C4A2F');
    $h->setCellValue($ult . '2', $fecha);
    $h->setCellValue($ult . '3', $hora);
    $h->getStyle($ult . '2')->getFont()->setBold(true);
    $h->getStyle($ult . '3')->getFont()->getColor()->setRGB('5B5148');
    $h->getStyle($ult . '1:' . $ult . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $h->setCellValue('A5', $r['titulo'] ?? 'Reporte');
    $h->getStyle('A5')->getFont()->setName('Cambria')->setSize(16)->setBold(true);
    $h->getRowDimension(5)->setRowHeight(28);
    $h->getStyle("A5:{$ult}5")->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('2A2420');
    if (!empty($r['subtitulo'])) {
        $h->setCellValue('A6', $r['subtitulo']);
        $h->getStyle('A6')->getFont()->getColor()->setRGB('5B5148');
        if ($n > 1) $h->mergeCells("A6:{$ult}6");
        $h->getStyle('A6')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $h->getRowDimension(6)->setRowHeight(15 * max(1, (int) ceil(mb_strlen($r['subtitulo']) / 105)));
    }

    $f = 8;
    if (!empty($r['resumen'])) {
        $ancho = max(1, intdiv($n, 2));
        foreach (array_values($r['resumen']) as $k => $dato) {
            $c1 = $L(($k % 2) * $ancho);
            $c2 = $L(($k % 2) * $ancho + $ancho - 1);
            $base = $f + intdiv($k, 2) * 3;
            foreach ([0, 1, 2] as $d) if ($c1 !== $c2) $h->mergeCells($c1 . ($base + $d) . ':' . $c2 . ($base + $d));
            $h->setCellValue($c1 . $base, mb_strtoupper((string) ($dato['texto'] ?? '')));
            $h->setCellValueExplicit($c1 . ($base + 1), (string) ($dato['valor'] ?? ''), DataType::TYPE_STRING);
            $h->setCellValue($c1 . ($base + 2), (string) ($dato['nota'] ?? ''));
            $h->getStyle($c1 . $base)->getFont()->setBold(true)->setSize(8)->getColor()->setRGB('8C4A2F');
            $h->getStyle($c1 . ($base + 1))->getFont()->setSize(15);
            $h->getStyle($c1 . ($base + 2))->getFont()->setSize(10)->getColor()->setRGB('4A413A');
            $h->getStyle("$c1$base:$c2" . ($base + 2))->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_CENTER);
            $h->getStyle("$c1$base:$c2" . ($base + 2))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E3D8CA');
            $h->getRowDimension($base)->setRowHeight(18);
            $h->getRowDimension($base + 1)->setRowHeight(22);
            $h->getRowDimension($base + 2)->setRowHeight(17);
        }
        $f += (int) ceil(count($r['resumen']) / 2) * 3 + 1;
    }
    $primera = $f;

    $norm = fn(array $cs) => array_map(fn($c) => (is_array($c) ? $c : ['texto' => $c]) + ['numero' => false, 'decimales' => 2], $cs);
    $nums = fn(array $cs) => array_keys(array_filter($cs, fn($c) => $c['numero']));
    $escribir = function (array $valores, int $fila, array $cs) use ($h, $L, $nums) {
        $numeros = $nums($cs);
        foreach (array_values($valores) as $i => $v) {
            $celda = $L($i) . $fila;
            if (in_array($i, $numeros, true)) {
                if (is_numeric($v)) $h->setCellValue($celda, (float) $v);
                else $h->setCellValueExplicit($celda, (string) $v, DataType::TYPE_STRING);
                $dec = (int) $cs[$i]['decimales'];
                $h->getStyle($celda)->getNumberFormat()->setFormatCode($dec > 0 ? '#,##0.' . str_repeat('0', $dec) : '#,##0');
                $h->getStyle($celda)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            } else {
                $h->setCellValueExplicit($celda, (string) $v, DataType::TYPE_STRING);
            }
        }
    };
    $titulos = function (int $fila, array $cs) use ($h, $L, $nums) {
        foreach ($cs as $i => $c) $h->setCellValue($L($i) . $fila, mb_strtoupper($c['texto']));
        $fin = $L(max(count($cs), 1) - 1);
        $numeros = $nums($cs);
        $h->getStyle("A$fila:$fin$fila")->applyFromArray([
            'font' => ['bold' => true, 'size' => 8.5, 'color' => ['rgb' => '5B5148']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F0E8']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CFC2B2']]],
        ]);
        foreach ($numeros as $i) $h->getStyle($L($i) . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $h->getRowDimension($fila)->setRowHeight(19);
    };
    $sumas = function (array $valores, int $fila, int $desde, array $cs) use ($h, $L, $nums) {
        foreach ($nums($cs) as $i) {
            if (!is_numeric($valores[$i] ?? '') || $fila <= $desde) continue;
            $h->setCellValue($L($i) . $fila, '=SUBTOTAL(9,' . $L($i) . $desde . ':' . $L($i) . ($fila - 1) . ')');
        }
    };

    $altoPagina = (($n > 7 ? 8.5 : 11) - 1.3) * 72 - 10;
    $usado = 15 * ($primera - 2) + 30;
    $grupos = $r['grupos'] ?? [];
    if (!$grupos) {
        $h->setCellValue("A$f", 'No hay datos para los filtros elegidos.');
        $h->getStyle("A$f")->getFont()->getColor()->setRGB('5B5148');
        $f += 2;
    }
    foreach ($grupos as $g) {
        $conTitulo = ($g['titulo'] ?? '') !== '';
        $altoGrupo = ($conTitulo ? 22 : 0) + 19 + 15 * (count($g['filas'] ?? []) + 2);
        if ($usado + $altoGrupo > $altoPagina && $f > $primera) {
            $h->setBreak('A' . ($f - 1), Worksheet::BREAK_ROW);
            $usado = 0;
        }
        $usado += $altoGrupo;
        if ($conTitulo) {
            $h->setCellValue("A$f", $g['titulo']);
            $h->getStyle("A$f")->getFont()->setBold(true)->setSize(11);
            $h->getStyle("A$f")->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
            $h->getRowDimension($f)->setRowHeight(22);
            $f++;
        }
        $gc = isset($g['columnas']) ? $norm($g['columnas']) : $cols;
        $titulos($f++, $gc);
        $ini = $f;
        foreach ($g['filas'] ?? [] as $fila) {
            $escribir($fila, $f, $gc);
            $h->getStyle("A$f:$ult$f")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('E3D8CA');
            $f++;
        }
        if (!empty($g['subtotal'])) {
            $escribir($g['subtotal'], $f, $gc);
            $sumas(array_values($g['subtotal']), $f, $ini, $gc);
            $h->getStyle("A$f:$ult$f")->applyFromArray([
                'font' => ['bold' => true],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CFC2B2']]],
            ]);
        }
        $f += 2;
    }
    if (!empty($r['totales'])) {
        $tot = array_values($r['totales']);
        $tot[0] = mb_strtoupper((string) $tot[0]);
        $escribir($tot, $f, $cols);
        $sumas($tot, $f, $primera, $cols);
        $h->getStyle("A$f:$ult$f")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2A2420']]],
        ]);
        $h->getStyle("A$f")->getFont()->getColor()->setRGB('8C4A2F');
    }
    foreach ($cols as $i => $c) $h->getColumnDimension($L($i))->setWidth($c['ancho'] ?? max(12, min(28, mb_strlen($c['texto']) + 6)));
    if (!empty($r['elaborado'])) {
        $h->setCellValue('A' . ($f + 2), 'Elaborado por ' . $r['elaborado']);
        $h->getStyle('A' . ($f + 2))->getFont()->setSize(8)->getColor()->setRGB('8A7F74');
    }

    $ps = $h->getPageSetup();
    $ps->setPaperSize(PageSetup::PAPERSIZE_LETTER);
    $ps->setOrientation($n > 7 ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT);
    $ps->setFitToWidth(1);
    $ps->setFitToHeight(0);
    $h->getPageMargins()->setTop(0.6)->setBottom(0.7)->setLeft(0.5)->setRight(0.5);
    $h->getHeaderFooter()->setOddFooter('&L&8' . str_replace('&', '&&', $nombre) . '&R&8Página &P de &N');
    $h->setSelectedCell('A1');
    return $libro;
}

function reporte_excel_descargar(array $r, string $archivo): void
{
    $libro = reporte_excel($r);
    while (ob_get_level()) ob_end_clean();
    $archivo = preg_replace('/[^\w\- .]/u', '', $archivo) ?: 'reporte';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $archivo . '.xlsx"');
    header('Cache-Control: max-age=0');
    (new Xlsx($libro))->save('php://output');
    exit;
}
