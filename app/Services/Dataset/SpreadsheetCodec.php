<?php

namespace App\Services\Dataset;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class SpreadsheetCodec
{
    public const MAX_UPLOAD_BYTES = 5242880; // 5 MB

    public const MAX_XML_BYTES = 16777216; // 16 MB uncompressed XML guard

    public const MAX_COLS = 64;

    public const MAX_SHEETS = 12;

    public static function normalizeHeader(string $value): string
    {
        $clean = preg_replace('/^\x{FEFF}/u', '', trim($value)) ?? '';
        $clean = mb_strtolower($clean, 'UTF-8');
        $clean = preg_replace('/[^a-z0-9]+/u', '_', $clean) ?? '';

        return trim($clean, '_');
    }

    public static function safeCellValue(mixed $value): string
    {
        $raw = (string) ($value ?? '');
        $str = trim($raw);
        if ($str !== '' && (preg_match('/^[=+\-@|]/', $str) || preg_match('/^[\t\r\n]/', $raw)) && ! is_numeric($str)) {
            return "'".$str;
        }

        return $str;
    }

    public static function cleanImportedValue(mixed $value): string
    {
        $str = trim((string) ($value ?? ''));
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str) ?? '';
        if (str_starts_with($str, "'") && isset($str[1]) && in_array($str[1], ['=', '+', '-', '@', '|'], true)) {
            $str = substr($str, 1);
        }

        return trim($str);
    }

    public static function buildXlsxFile(array $sheets): string
    {
        $path = tempnam(sys_get_temp_dir(), 'aksara_xlsx_');
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal menyiapkan arsip lembar kerja Excel.');
        }

        $overrides = '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $workbookSheets = '';
        $workbookRels = '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        foreach (array_values($sheets) as $idx => $sheet) {
            $num = $idx + 1;
            $name = self::sanitizeSheetName((string) ($sheet['name'] ?? 'Sheet'.$num));
            $headers = array_values($sheet['headers'] ?? []);
            $rows = array_values($sheet['rows'] ?? []);

            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$num.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $workbookSheets .= '<sheet name="'.self::xml($name).'" sheetId="'.$num.'" r:id="rId'.$num.'"/>';
            $workbookRels .= '<Relationship Id="rId'.$num.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$num.'.xml"/>';
            $zip->addFromString('xl/worksheets/sheet'.$num.'.xml', self::buildWorksheetXml($headers, $rows));
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'.$overrides.'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.$workbookSheets.'</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$workbookRels.'</Relationships>');
        $zip->addFromString('xl/styles.xml', self::buildStylesXml());
        $zip->close();

        return $path;
    }

    public static function downloadXlsx(array $sheets, string $filename)
    {
        $path = self::buildXlsxFile($sheets);
        $safeName = preg_replace('/[^A-Za-z0-9_\-. ]+/', '_', $filename) ?: 'dataset_sekolah';
        if (! str_ends_with(strtolower($safeName), '.xlsx')) {
            $safeName .= '.xlsx';
        }

        return response()->download($path, $safeName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public static function downloadCsv(array $headers, array $rows, string $filename)
    {
        $safeName = preg_replace('/[^A-Za-z0-9_\-. ]+/', '_', $filename) ?: 'dataset_sekolah';
        if (! str_ends_with(strtolower($safeName), '.csv')) {
            $safeName .= '.csv';
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, array_map([self::class, 'safeCellValue'], array_values($headers)));
        foreach ($rows as $row) {
            fputcsv($stream, array_map([self::class, 'safeCellValue'], array_values($row)));
        }
        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$safeName.'"',
        ]);
    }

    public static function readUploaded(UploadedFile $file): array
    {
        if (! $file->isValid() || $file->getSize() > self::MAX_UPLOAD_BYTES || $file->getSize() <= 0) {
            throw ValidationException::withMessages(['file' => 'Pilih berkas .xlsx atau .csv yang valid (maksimal 5 MB).']);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext === 'xlsx') {
            return ['format' => 'xlsx', 'sheets' => self::readXlsxPath($file->getRealPath())];
        }
        if ($ext === 'csv' || $ext === 'txt') {
            return ['format' => 'csv', 'sheets' => ['CSV' => self::readCsvPath($file->getRealPath())]];
        }

        throw ValidationException::withMessages(['file' => 'Format berkas tidak didukung. Gunakan berkas .xlsx atau .csv resmi AKSARA.']);
    }

    public static function readXlsxPath(string $realPath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($realPath) !== true) {
            throw ValidationException::withMessages(['file' => 'Berkas Excel (.xlsx) rusak atau tidak dapat dibuka.']);
        }

        try {
            $totalXmlBytes = 0;
            $workbookXml = self::readZipEntrySafe($zip, 'xl/workbook.xml', $totalXmlBytes);
            $relsXml = self::readZipEntrySafe($zip, 'xl/_rels/workbook.xml.rels', $totalXmlBytes);
            if ($workbookXml === null || $relsXml === null) {
                throw ValidationException::withMessages(['file' => 'Struktur berkas Excel (.xlsx) tidak lengkap.']);
            }

            $sharedStrings = self::parseSharedStrings(self::readZipEntrySafe($zip, 'xl/sharedStrings.xml', $totalXmlBytes));
            $relMap = self::parseWorkbookRels($relsXml);
            $wbDom = self::loadXmlSafe($workbookXml);
            $wbXp = new DOMXPath($wbDom);
            $wbXp->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $wbXp->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

            $sheets = [];
            $sheetCount = 0;
            foreach ($wbXp->query('//s:sheets/s:sheet') as $sheetNode) {
                $sheetCount++;
                if ($sheetCount > self::MAX_SHEETS) {
                    throw ValidationException::withMessages(['file' => 'Jumlah lembar kerja pada berkas Excel melebihi batas (maksimal '.self::MAX_SHEETS.' lembar).']);
                }
                $name = trim($sheetNode->getAttribute('name'));
                $rId = $sheetNode->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id')
                    ?: $sheetNode->getAttribute('r:id');
                $target = $relMap[$rId] ?? null;
                if (! $target) {
                    continue;
                }
                $entryName = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.ltrim($target, '/');
                $sheetXml = self::readZipEntrySafe($zip, $entryName, $totalXmlBytes);
                if ($sheetXml !== null) {
                    $sheets[$name] = self::parseWorksheetXml($sheetXml, $sharedStrings);
                }
            }

            if (! $sheets) {
                throw ValidationException::withMessages(['file' => 'Tidak ditemukan lembar kerja pada berkas Excel (.xlsx).']);
            }

            return $sheets;
        } finally {
            $zip->close();
        }
    }

    public static function readCsvPath(string $realPath): array
    {
        $raw = file_get_contents($realPath);
        if ($raw === false || trim($raw) === '' || str_contains($raw, "\0") || ! mb_check_encoding($raw, 'UTF-8')) {
            throw ValidationException::withMessages(['file' => 'Berkas CSV harus berupa teks UTF-8 yang valid dan memiliki baris judul kolom.']);
        }
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        $firstLine = strtok($raw, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $raw);
        rewind($stream);

        $headerLine = fgetcsv($stream, 0, $delimiter, '"', '');
        $headers = array_map(fn ($h) => self::normalizeHeader((string) $h), $headerLine ?: []);
        $rows = [];
        $lineNo = 1;

        while (($values = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            $lineNo++;
            $cleaned = array_map([self::class, 'cleanImportedValue'], $values);
            if (implode('', $cleaned) === '') {
                continue;
            }
            $assoc = ['_row' => $lineNo];
            foreach ($headers as $idx => $key) {
                if ($key !== '') {
                    $assoc[$key] = $cleaned[$idx] ?? '';
                }
            }
            $rows[] = $assoc;
        }
        fclose($stream);

        return ['headers' => array_values(array_filter($headers)), 'rows' => $rows];
    }

    private static function buildWorksheetXml(array $headers, array $rows): string
    {
        $colCount = max(1, count($headers));
        $colsXml = '<cols><col min="1" max="'.$colCount.'" width="22" customWidth="1"/></cols>';
        $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.$colsXml.'<sheetData>';

        $allRows = array_merge([$headers], $rows);
        foreach ($allRows as $rIdx => $row) {
            $rowNum = $rIdx + 1;
            $style = $rIdx === 0 ? ' s="1"' : '';
            $xml .= '<row r="'.$rowNum.'">';
            foreach (array_values($row) as $cIdx => $val) {
                $ref = self::colLetters($cIdx).$rowNum;
                $safe = $rIdx === 0 ? (string) $val : self::safeCellValue($val);
                $xml .= '<c r="'.$ref.'"'.$style.' t="inlineStr"><is><t xml:space="preserve">'.self::xml($safe).'</t></is></c>';
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private static function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FF1A2842"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD7E4F2"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
            .'</styleSheet>';
    }

    private static function parseSharedStrings(?string $xml): array
    {
        if (! $xml) {
            return [];
        }
        $dom = self::loadXmlSafe($xml);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $strings = [];
        foreach ($xp->query('//s:si') as $si) {
            $text = '';
            foreach ($xp->query('.//s:t', $si) as $t) {
                $text .= $t->textContent;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private static function parseWorkbookRels(string $xml): array
    {
        $dom = self::loadXmlSafe($xml);
        $map = [];
        foreach ($dom->getElementsByTagName('Relationship') as $rel) {
            $map[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
        }

        return $map;
    }

    private static function parseWorksheetXml(string $xml, array $sharedStrings): array
    {
        $dom = self::loadXmlSafe($xml);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $headers = [];
        $rows = [];
        foreach ($xp->query('//s:sheetData/s:row') as $rIdx => $rowNode) {
            $rowNum = (int) ($rowNode->getAttribute('r') ?: ($rIdx + 1));
            $cells = [];
            $seqCol = 0;
            foreach ($xp->query('./s:c', $rowNode) as $cNode) {
                $ref = $cNode->getAttribute('r');
                $colIdx = $ref !== '' ? self::colIndexFromRef($ref) : $seqCol;
                $seqCol = $colIdx + 1;
                if ($colIdx >= self::MAX_COLS) {
                    continue;
                }
                $type = $cNode->getAttribute('t');
                $val = '';
                if ($type === 'inlineStr') {
                    foreach ($xp->query('.//s:t', $cNode) as $tNode) {
                        $val .= $tNode->textContent;
                    }
                } else {
                    $vNode = $xp->query('./s:v', $cNode)->item(0);
                    $rawVal = $vNode ? $vNode->textContent : '';
                    $val = $type === 's' ? ($sharedStrings[(int) $rawVal] ?? '') : $rawVal;
                }
                $cells[$colIdx] = self::cleanImportedValue($val);
            }

            if ($rowNum === 1 || empty($headers)) {
                $maxCol = min(self::MAX_COLS - 1, $cells ? max(array_keys($cells)) : -1);
                for ($i = 0; $i <= $maxCol; $i++) {
                    $headers[$i] = self::normalizeHeader($cells[$i] ?? '');
                }

                continue;
            }

            if (implode('', $cells) === '') {
                continue;
            }
            $assoc = ['_row' => $rowNum];
            foreach ($headers as $idx => $key) {
                if ($key !== '') {
                    $assoc[$key] = $cells[$idx] ?? '';
                }
            }
            $rows[] = $assoc;
        }

        return ['headers' => array_values(array_filter($headers)), 'rows' => $rows];
    }

    private static function readZipEntrySafe(ZipArchive $zip, string $entry, int &$totalXmlBytes = 0): ?string
    {
        $stat = $zip->statName($entry);
        if ($stat === false) {
            return null;
        }
        $entrySize = (int) ($stat['size'] ?? 0);
        if ($entrySize > self::MAX_XML_BYTES || ($totalXmlBytes + $entrySize) > self::MAX_XML_BYTES) {
            throw ValidationException::withMessages(['file' => 'Ukuran isi berkas Excel terlalu besar dan melebihi batas keamanan sistem.']);
        }
        $content = $zip->getFromName($entry);
        if (! is_string($content)) {
            return null;
        }
        $totalXmlBytes += strlen($content);
        if ($totalXmlBytes > self::MAX_XML_BYTES) {
            throw ValidationException::withMessages(['file' => 'Ukuran isi berkas Excel terlalu besar dan melebihi batas keamanan sistem.']);
        }

        return $content;
    }

    private static function loadXmlSafe(string $xml): DOMDocument
    {
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
            throw ValidationException::withMessages(['file' => 'Berkas tidak dapat diproses karena struktur datanya tidak didukung.']);
        }
        $dom = new DOMDocument;
        if (! @$dom->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA | LIBXML_COMPACT)) {
            throw ValidationException::withMessages(['file' => 'Isi berkas Excel tidak dapat dibaca. Pastikan berkas tidak rusak.']);
        }

        return $dom;
    }

    private static function sanitizeSheetName(string $name): string
    {
        $clean = preg_replace('/[:\\\\\/?*\[\]]+/', '_', trim($name)) ?: 'Sheet';

        return mb_substr($clean, 0, 31, 'UTF-8');
    }

    private static function colLetters(int $index): string
    {
        $letters = '';
        $n = $index;
        while ($n >= 0) {
            $letters = chr(($n % 26) + 65).$letters;
            $n = intdiv($n, 26) - 1;
        }

        return $letters;
    }

    private static function colIndexFromRef(string $ref): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($ref)) ?: 'A';
        $idx = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $idx = $idx * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $idx - 1);
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

