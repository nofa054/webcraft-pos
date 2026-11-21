<?php

declare(strict_types=1);

namespace App\Helpers;

use ZipArchive;

/**
 * Kelas Exporter Laporan (Excel .xlsx & PDF .pdf)
 * Mendukung PhpSpreadsheet & Dompdf (via Composer) serta Native OpenXML .xlsx & Native PDF Generator
 * sehingga fitur ekspor tetap berjalan 100% meskipun dijalankan tanpa instalasi ekstensi pihak ketiga.
 */
class Exporter
{
    /**
     * Ekspor data tabel ke file Microsoft Excel (.xlsx)
     *
     * @param string $filename Nama file tanpa ekstensi
     * @param string $title Judul Laporan
     * @param string $subtitle Subjudul / Periode Laporan
     * @param array<string> $headers Daftar kolom header
     * @param array<array<int, string|int|float>> $rows Baris data
     * @param array<string, string> $summary Ringkasan di bagian bawah/atas
     */
    public static function toExcel(
        string $filename,
        string $title,
        string $subtitle,
        array $headers,
        array $rows,
        array $summary = []
    ): never {
        $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '.xlsx';

        // Gunakan PhpSpreadsheet jika tersedia di vendor/
        if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            self::exportWithPhpSpreadsheet($safeFilename, $title, $subtitle, $headers, $rows, $summary);
        }

        // Gunakan Native OpenXML (.xlsx) Generator dengan ZipArchive
        if (class_exists('ZipArchive')) {
            self::exportWithNativeXlsx($safeFilename, $title, $subtitle, $headers, $rows, $summary);
        }

        // Fallback terakhir jika ZipArchive tidak aktif
        self::exportXmlSpreadsheet($safeFilename, $title, $subtitle, $headers, $rows, $summary);
    }

    /**
     * Ekspor data tabel ke dokumen PDF (.pdf)
     */
    public static function toPdf(
        string $filename,
        string $title,
        string $subtitle,
        array $headers,
        array $rows,
        array $summary = [],
        array $store = []
    ): never {
        $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '.pdf';

        // Gunakan Dompdf jika tersedia di vendor/
        if (class_exists('\Dompdf\Dompdf')) {
            self::exportWithDompdf($safeFilename, $title, $subtitle, $headers, $rows, $summary, $store);
        }

        // Gunakan Native PDF 1.4 Generator yang menghasilkan file biner .pdf asli siap unduh & cetak
        self::exportWithNativePdf($safeFilename, $title, $subtitle, $headers, $rows, $summary, $store);
    }

    private static function exportWithPhpSpreadsheet(
        string $safeFilename,
        string $title,
        string $subtitle,
        array $headers,
        array $rows,
        array $summary
    ): never {
        $spreadsheetClass = '\PhpOffice\PhpSpreadsheet\Spreadsheet';
        $writerClass = '\PhpOffice\PhpSpreadsheet\Writer\Xlsx';

        $spreadsheet = new $spreadsheetClass();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan POS');

        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('A2', $subtitle);

        $rowNum = 4;
        foreach ($headers as $colIdx => $headerText) {
            $colLetter = self::colLetter($colIdx + 1);
            $sheet->setCellValue($colLetter . $rowNum, $headerText);
        }

        $rowNum++;
        foreach ($rows as $row) {
            foreach (array_values($row) as $colIdx => $cellVal) {
                $colLetter = self::colLetter($colIdx + 1);
                $sheet->setCellValue($colLetter . $rowNum, $cellVal);
            }
            $rowNum++;
        }

        if (!empty($summary)) {
            $rowNum++;
            foreach ($summary as $label => $val) {
                $sheet->setCellValue('A' . $rowNum, $label);
                $sheet->setCellValue('B' . $rowNum, $val);
                $rowNum++;
            }
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Cache-Control: max-age=0');

        $writer = new $writerClass($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private static function exportWithNativeXlsx(
        string $safeFilename,
        string $title,
        string $subtitle,
        array $headers,
        array $rows,
        array $summary
    ): never {
        $tmpFile = tempnam(sys_get_temp_dir(), 'pos_xlsx_');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Laporan" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>');

        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="3">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
        <font><b/><sz val="13"/><name val="Calibri"/></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF1E3A8A"/></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border>
            <left style="thin"><color auto="1"/></left>
            <right style="thin"><color auto="1"/></right>
            <top style="thin"><color auto="1"/></top>
            <bottom style="thin"><color auto="1"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
    <cellXfs count="4">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
    </cellXfs>
</styleSheet>');

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        // Baris 1: Judul
        $sheetXml .= '<row r="1"><c r="A1" s="2" t="inlineStr"><is><t>' . self::xmlEscape($title) . '</t></is></c></row>';
        // Baris 2: Subjudul
        $sheetXml .= '<row r="2"><c r="A2" s="0" t="inlineStr"><is><t>' . self::xmlEscape($subtitle) . '</t></is></c></row>';

        // Baris 4: Header Tabel
        $rowIdx = 4;
        $sheetXml .= '<row r="' . $rowIdx . '">';
        foreach ($headers as $i => $h) {
            $cellRef = self::colLetter($i + 1) . $rowIdx;
            $sheetXml .= '<c r="' . $cellRef . '" s="1" t="inlineStr"><is><t>' . self::xmlEscape((string) $h) . '</t></is></c>';
        }
        $sheetXml .= '</row>';

        // Baris Data
        foreach ($rows as $row) {
            $rowIdx++;
            $sheetXml .= '<row r="' . $rowIdx . '">';
            foreach (array_values($row) as $i => $val) {
                $cellRef = self::colLetter($i + 1) . $rowIdx;
                if (is_int($val) || is_float($val)) {
                    $sheetXml .= '<c r="' . $cellRef . '" s="3" t="n"><v>' . $val . '</v></c>';
                } else {
                    $sheetXml .= '<c r="' . $cellRef . '" s="3" t="inlineStr"><is><t>' . self::xmlEscape((string) $val) . '</t></is></c>';
                }
            }
            $sheetXml .= '</row>';
        }

        // Baris Ringkasan
        if (!empty($summary)) {
            $rowIdx++;
            foreach ($summary as $label => $val) {
                $rowIdx++;
                $sheetXml .= '<row r="' . $rowIdx . '">';
                $sheetXml .= '<c r="A' . $rowIdx . '" s="2" t="inlineStr"><is><t>' . self::xmlEscape((string) $label) . '</t></is></c>';
                $sheetXml .= '<c r="B' . $rowIdx . '" s="2" t="inlineStr"><is><t>' . self::xmlEscape((string) $val) . '</t></is></c>';
                $sheetXml .= '</row>';
            }
        }

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Content-Length: ' . filesize($tmpFile));
        header('Cache-Control: max-age=0');
        readfile($tmpFile);
        @unlink($tmpFile);
        exit;
    }

    private static function exportXmlSpreadsheet(
        string $safeFilename,
        string $title,
        string $subtitle,
        array $headers,
        array $rows,
        array $summary
    ): never {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        echo "\xEF\xBB\xBF";
        echo $title . "\n" . $subtitle . "\n\n";
        echo implode("\t", $headers) . "\n";
        foreach ($rows as $r) {
            echo implode("\t", array_map(fn($v) => str_replace(["\t", "\n", "\r"], ' ', (string) $v), $r)) . "\n";
        }
        if (!empty($summary)) {
            echo "\n";
            foreach ($summary as $k => $v) {
                echo $k . "\t" . $v . "\n";
            }
        }
        exit;
    }

    private static function exportWithDompdf(
        string $safeFilename,
        string $title,
        string $subtitle,
        array $headers,
        array $rows,
        array $summary,
        array $store
    ): never {
        $html = self::buildReportHtml($title, $subtitle, $headers, $rows, $summary, $store);
        $dompdfClass = '\Dompdf\Dompdf';
        $dompdf = new $dompdfClass();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', count($headers) > 6 ? 'landscape' : 'portrait');
        $dompdf->render();
        $dompdf->stream($safeFilename, ['Attachment' => true]);
        exit;
    }

    /**
     * Native PDF 1.4 Generator (menghasilkan dokumen PDF biner multi-halaman yang rapi)
     */
    private static function exportWithNativePdf(
        string $safeFilename,
        string $title,
        string $subtitle,
        array $headers,
        array $rows,
        array $summary,
        array $store
    ): never {
        $storeName = $store['store_name'] ?? 'Webcraft POS UMKM';
        $storeAddress = $store['address'] ?? '';
        $storePhone = $store['phone'] ?? '';

        // Buat halaman Landscape A4 (842 x 595 pt) agar tabel muat dengan leluasa
        $pageW = 842;
        $pageH = 595;
        $marginL = 36;
        $marginR = 36;
        $usableW = $pageW - $marginL - $marginR;

        $colCount = max(1, count($headers));
        // Hitung bobot lebar kolom proporsional
        $colWidths = [];
        for ($i = 0; $i < $colCount; $i++) {
            $maxLen = max(6, mb_strlen((string) $headers[$i]));
            foreach ($rows as $r) {
                $vals = array_values($r);
                $len = mb_strlen((string) ($vals[$i] ?? ''));
                if ($len > $maxLen) {
                    $maxLen = min(38, $len);
                }
            }
            $colWidths[$i] = $maxLen;
        }
        $totalWeight = array_sum($colWidths) ?: 1;
        for ($i = 0; $i < $colCount; $i++) {
            $colWidths[$i] = round(($colWidths[$i] / $totalWeight) * $usableW, 2);
        }

        $pagesContent = [];
        $currentStream = '';
        $y = $pageH - 40;

        $startNewPage = function (int $pageNo) use (
            &$currentStream,
            &$y,
            $pageH,
            $marginL,
            $usableW,
            $storeName,
            $storeAddress,
            $storePhone,
            $title,
            $subtitle,
            $headers,
            $colWidths
        ): void {
            $currentStream = '';
            $y = $pageH - 40;

            // Header Toko
            $currentStream .= "BT /F2 14 Tf {$marginL} {$y} Td (" . self::pdfEscape($storeName) . ") Tj ET\n";
            $y -= 14;
            if ($storeAddress !== '' || $storePhone !== '') {
                $info = trim($storeAddress . ($storePhone ? " | Telp: {$storePhone}" : ''));
                $currentStream .= "BT /F1 9 Tf {$marginL} {$y} Td (" . self::pdfEscape($info) . ") Tj ET\n";
                $y -= 12;
            }

            // Garis pemisah
            $currentStream .= "0.12 0.23 0.54 RG 1.5 w {$marginL} {$y} m " . ($marginL + $usableW) . " {$y} l S\n";
            $y -= 18;

            // Judul Laporan
            $currentStream .= "BT /F2 12 Tf {$marginL} {$y} Td (" . self::pdfEscape($title) . ") Tj ET\n";
            $y -= 13;
            $currentStream .= "BT /F1 9 Tf {$marginL} {$y} Td (" . self::pdfEscape($subtitle . "  |  Halaman {$pageNo}") . ") Tj ET\n";
            $y -= 16;

            // Header Tabel (Kotak Biru Tua)
            $rowH = 20;
            $boxBottom = $y - $rowH + 5;
            $currentStream .= "0.12 0.23 0.54 rg {$marginL} {$boxBottom} {$usableW} {$rowH} re f\n";
            $currentStream .= "1 1 1 rg\n";
            $x = $marginL + 5;
            foreach ($headers as $idx => $hText) {
                $maxChars = max(4, (int) floor($colWidths[$idx] / 5.2));
                $clipped = mb_substr((string) $hText, 0, $maxChars);
                $currentStream .= "BT /F2 8.5 Tf {$x} " . ($y - 9) . " Td (" . self::pdfEscape($clipped) . ") Tj ET\n";
                $x += $colWidths[$idx];
            }
            $currentStream .= "0 0 0 rg\n";
            $y -= ($rowH + 2);
        };

        $pageNo = 1;
        $startNewPage($pageNo);

        $rowH = 17;
        foreach ($rows as $rIdx => $row) {
            if ($y < 75) {
                $pagesContent[] = $currentStream;
                $pageNo++;
                $startNewPage($pageNo);
            }

            $boxBottom = $y - $rowH + 4;
            if ($rIdx % 2 === 1) {
                $currentStream .= "0.95 0.96 0.98 rg {$marginL} {$boxBottom} {$usableW} {$rowH} re f 0 0 0 rg\n";
            }
            // Garis bawah baris tipis
            $currentStream .= "0.85 0.87 0.90 RG 0.4 w {$marginL} {$boxBottom} m " . ($marginL + $usableW) . " {$boxBottom} l S\n";

            $x = $marginL + 5;
            foreach (array_values($row) as $cIdx => $val) {
                if (!isset($colWidths[$cIdx])) {
                    continue;
                }
                $maxChars = max(4, (int) floor($colWidths[$cIdx] / 4.8));
                $text = (string) $val;
                if (mb_strlen($text) > $maxChars) {
                    $text = mb_substr($text, 0, $maxChars - 2) . '..';
                }
                $currentStream .= "BT /F1 8 Tf {$x} " . ($y - 8) . " Td (" . self::pdfEscape($text) . ") Tj ET\n";
                $x += $colWidths[$cIdx];
            }
            $y -= $rowH;
        }

        // Ringkasan di bawah tabel
        if (!empty($summary)) {
            $y -= 10;
            if ($y < 90) {
                $pagesContent[] = $currentStream;
                $pageNo++;
                $startNewPage($pageNo);
            }
            $currentStream .= "BT /F2 10 Tf {$marginL} {$y} Td (Ringkasan Laporan:) Tj ET\n";
            $y -= 14;
            foreach ($summary as $k => $v) {
                if ($y < 45) {
                    $pagesContent[] = $currentStream;
                    $pageNo++;
                    $startNewPage($pageNo);
                }
                $line = "{$k}: {$v}";
                $currentStream .= "BT /F2 9 Tf {$marginL} {$y} Td (" . self::pdfEscape($line) . ") Tj ET\n";
                $y -= 13;
            }
        }

        // Footer Waktu Cetak
        $footerY = 24;
        $printedAt = 'Dicetak pada: ' . date('d/m/Y H:i:s') . ' - Sistem Webcraft POS';
        $currentStream .= "0.4 0.4 0.4 rg BT /F1 8 Tf {$marginL} {$footerY} Td (" . self::pdfEscape($printedAt) . ") Tj ET 0 0 0 rg\n";

        $pagesContent[] = $currentStream;

        // Rakit objek PDF 1.4
        $objects = [];
        // Obj 1: Catalog
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        // Obj 3: Font Helvetica Normal
        $objects[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        // Obj 4: Font Helvetica Bold
        $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        $kids = [];
        $nextObjId = 5;
        foreach ($pagesContent as $stream) {
            $pageObjId = $nextObjId++;
            $contentObjId = $nextObjId++;
            $kids[] = "{$pageObjId} 0 R";

            $objects[$pageObjId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageW} {$pageH}] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentObjId} 0 R >>";
            $len = strlen($stream);
            $objects[$contentObjId] = "<< /Length {$len} >>\nstream\n{$stream}endstream";
        }

        $kidsStr = implode(' ', $kids);
        $pageCount = count($kids);
        $objects[2] = "<< /Type /Pages /Kids [ {$kidsStr} ] /Count {$pageCount} >>";
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $totalObjs = count($objects) + 1;
        $pdf .= "xref\n0 {$totalObjs}\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size {$totalObjs} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $pdf;
        exit;
    }

    private static function buildReportHtml(
        string $title,
        string $subtitle,
        array $headers,
        array $rows,
        array $summary,
        array $store
    ): string {
        $storeName = e($store['store_name'] ?? 'Webcraft POS UMKM');
        $storeAddress = e($store['address'] ?? '');
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
            body { font-family: sans-serif; font-size: 11px; color: #1f2937; }
            h1 { margin: 0 0 4px 0; font-size: 16px; color: #1e3a8a; }
            p.sub { margin: 0 0 12px 0; color: #4b5563; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
            th { background: #1e3a8a; color: #fff; text-align: left; padding: 6px 8px; border: 1px solid #cbd5e1; }
            td { padding: 5px 8px; border: 1px solid #cbd5e1; }
            tr:nth-child(even) td { background: #f8fafc; }
        </style></head><body>';
        $html .= "<h1>{$storeName} - " . e($title) . "</h1>";
        if ($storeAddress !== '') {
            $html .= "<p class='sub'>{$storeAddress}</p>";
        }
        $html .= "<p class='sub'>" . e($subtitle) . "</p><table><thead><tr>";
        foreach ($headers as $h) {
            $html .= '<th>' . e($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr>';
            foreach ($r as $cell) {
                $html .= '<td>' . e($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        if (!empty($summary)) {
            $html .= '<p><strong>Ringkasan:</strong><br>';
            foreach ($summary as $k => $v) {
                $html .= e($k) . ': <strong>' . e($v) . '</strong><br>';
            }
            $html .= '</p>';
        }
        $html .= '</body></html>';
        return $html;
    }

    private static function colLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int) (($index - $mod) / 26);
        }
        return $letter;
    }

    private static function xmlEscape(string $str): string
    {
        return htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function pdfEscape(string $str): string
    {
        $clean = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $str);
        if ($clean === false) {
            $clean = $str;
        }
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $clean);
    }
}

