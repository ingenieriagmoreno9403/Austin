<?php

namespace App\Exports;

use RuntimeException;
use ZipArchive;

/**
 * Escribe .xlsx en streaming (sin PhpSpreadsheet) para exports grandes.
 * Usa celdas inlineStr / numéricas; compatible con Excel.
 */
class CcBudgetXlsxStreamer
{
    /**
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     * @param  array{widths?: array<int, float|int>, text_columns?: array<int, true>, should_abort?: callable(): bool}  $options
     */
    public function write(string $absolutePath, string $sheetTitle, array $headings, iterable $rows, array $options = []): int
    {
        $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cc_xlsx_'.bin2hex(random_bytes(8));
        if (! @mkdir($tmp) && ! is_dir($tmp)) {
            throw new RuntimeException('No se pudo crear directorio temporal para el Excel.');
        }

        $prevTextColumns = $this->textColumns;
        if (isset($options['text_columns']) && is_array($options['text_columns'])) {
            $this->textColumns = $options['text_columns'];
        }

        try {
            @mkdir($tmp.DIRECTORY_SEPARATOR.'_rels');
            @mkdir($tmp.DIRECTORY_SEPARATOR.'xl');
            @mkdir($tmp.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'_rels');
            @mkdir($tmp.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'worksheets');

            $this->writeFile($tmp.DIRECTORY_SEPARATOR.'[Content_Types].xml', $this->contentTypesXml());
            $this->writeFile($tmp.DIRECTORY_SEPARATOR.'_rels'.DIRECTORY_SEPARATOR.'.rels', $this->rootRelsXml());
            $this->writeFile($tmp.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'workbook.xml', $this->workbookXml($sheetTitle));
            $this->writeFile($tmp.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'_rels'.DIRECTORY_SEPARATOR.'workbook.xml.rels', $this->workbookRelsXml());
            $this->writeFile($tmp.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'styles.xml', $this->stylesXml());

            $sheetPath = $tmp.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'worksheets'.DIRECTORY_SEPARATOR.'sheet1.xml';
            $rowCount = $this->writeSheet($sheetPath, $headings, $rows, $options);

            $this->zipDirectory($tmp, $absolutePath);

            return $rowCount;
        } finally {
            $this->textColumns = $prevTextColumns;
            $this->deleteTree($tmp);
        }
    }

    /**
     * Abre un Excel por partes (cada petición HTTP escribe un tramo).
     *
     * @param  array<int, string>  $headings
     * @param  array{widths?: array<int, float|int>, text_columns?: array<int, true>}  $options
     */
    public function begin(string $workDir, string $sheetTitle, array $headings, array $options = []): void
    {
        $xlsx = $this->xlsxDir($workDir);
        if (! is_dir($xlsx) && ! @mkdir($xlsx, 0755, true) && ! is_dir($xlsx)) {
            throw new RuntimeException('No se pudo crear el directorio del Excel.');
        }

        if (isset($options['text_columns']) && is_array($options['text_columns'])) {
            $this->textColumns = $options['text_columns'];
        }

        @mkdir($xlsx.DIRECTORY_SEPARATOR.'_rels');
        @mkdir($xlsx.DIRECTORY_SEPARATOR.'xl');
        @mkdir($xlsx.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'_rels');
        @mkdir($xlsx.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'worksheets');

        $this->writeFile($xlsx.DIRECTORY_SEPARATOR.'[Content_Types].xml', $this->contentTypesXml());
        $this->writeFile($xlsx.DIRECTORY_SEPARATOR.'_rels'.DIRECTORY_SEPARATOR.'.rels', $this->rootRelsXml());
        $this->writeFile($xlsx.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'workbook.xml', $this->workbookXml($sheetTitle));
        $this->writeFile($xlsx.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'_rels'.DIRECTORY_SEPARATOR.'workbook.xml.rels', $this->workbookRelsXml());
        $this->writeFile($xlsx.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'styles.xml', $this->stylesXml());

        $sheetPath = $xlsx.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'worksheets'.DIRECTORY_SEPARATOR.'sheet1.xml';
        $fh = fopen($sheetPath, 'wb');
        if ($fh === false) {
            throw new RuntimeException('No se pudo escribir la hoja del Excel.');
        }
        fwrite($fh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($fh, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"');
        fwrite($fh, ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">');
        fwrite($fh, '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>');
        fwrite($fh, '<cols>');
        $widths = $options['widths'] ?? [12, 12, 28, 14, 36, 8, 8, 10, 14, 14, 10];
        foreach ($widths as $i => $w) {
            $col = $i + 1;
            fwrite($fh, '<col min="'.$col.'" max="'.$col.'" width="'.$w.'" customWidth="1"/>');
        }
        fwrite($fh, '</cols><sheetData>');
        $this->writeRowXml($fh, 1, $headings, true);
        fclose($fh);

        $this->writeState($workDir, [
            'excel_row' => 2,
            'data_count' => 0,
            'closed' => false,
            'widths' => array_values($widths),
            'text_columns' => $this->textColumns,
        ]);
    }

    /**
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function append(string $workDir, iterable $rows): int
    {
        $state = $this->readState($workDir);
        if (! empty($state['closed'])) {
            throw new RuntimeException('El Excel ya está cerrado.');
        }
        $this->textColumns = [];
        foreach (($state['text_columns'] ?? []) as $col => $on) {
            if ($on) {
                $this->textColumns[(int) $col] = true;
            }
        }

        $sheetPath = $this->xlsxDir($workDir).DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'worksheets'.DIRECTORY_SEPARATOR.'sheet1.xml';
        $fh = fopen($sheetPath, 'ab');
        if ($fh === false) {
            throw new RuntimeException('No se pudo agregar filas al Excel.');
        }

        $excelRow = (int) ($state['excel_row'] ?? 2);
        $added = 0;
        foreach ($rows as $row) {
            $this->writeRowXml($fh, $excelRow, $row, false);
            $excelRow++;
            $added++;
        }
        fflush($fh);
        fclose($fh);

        $state['excel_row'] = $excelRow;
        $state['data_count'] = (int) ($state['data_count'] ?? 0) + $added;
        $this->writeState($workDir, $state);

        return $added;
    }

    public function finish(string $workDir, string $absolutePath): int
    {
        $state = $this->readState($workDir);
        if (empty($state['closed'])) {
            $sheetPath = $this->xlsxDir($workDir).DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'worksheets'.DIRECTORY_SEPARATOR.'sheet1.xml';
            $fh = fopen($sheetPath, 'ab');
            if ($fh === false) {
                throw new RuntimeException('No se pudo cerrar la hoja del Excel.');
            }
            fwrite($fh, '</sheetData></worksheet>');
            fclose($fh);
            $state['closed'] = true;
            $this->writeState($workDir, $state);
        }

        $dir = dirname($absolutePath);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('No se pudo crear la carpeta del archivo Excel.');
        }
        $this->zipDirectory($this->xlsxDir($workDir), $absolutePath);

        return (int) ($state['data_count'] ?? 0);
    }

    public function discard(string $workDir): void
    {
        if ($workDir !== '' && is_dir($workDir)) {
            $this->deleteTree($workDir);
        }
    }

    protected function xlsxDir(string $workDir): string
    {
        return rtrim($workDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'xlsx';
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected function writeState(string $workDir, array $state): void
    {
        if (! is_dir($workDir) && ! @mkdir($workDir, 0755, true) && ! is_dir($workDir)) {
            throw new RuntimeException('No se pudo guardar el avance del Excel.');
        }
        $json = json_encode($state);
        if ($json === false || file_put_contents($workDir.DIRECTORY_SEPARATOR.'state.json', $json) === false) {
            throw new RuntimeException('No se pudo guardar el avance del Excel.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function readState(string $workDir): array
    {
        $path = $workDir.DIRECTORY_SEPARATOR.'state.json';
        $raw = is_file($path) ? file_get_contents($path) : false;
        $state = is_string($raw) ? json_decode($raw, true) : null;
        if (! is_array($state)) {
            throw new RuntimeException('No se encontró el avance del Excel.');
        }

        return $state;
    }

    /**
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     * @param  array{widths?: array<int, float|int>, should_abort?: callable(): bool}  $options
     */
    protected function writeSheet(string $sheetPath, array $headings, iterable $rows, array $options = []): int
    {
        $fh = fopen($sheetPath, 'wb');
        if ($fh === false) {
            throw new RuntimeException('No se pudo escribir la hoja del Excel.');
        }

        fwrite($fh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($fh, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"');
        fwrite($fh, ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">');
        fwrite($fh, '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>');
        fwrite($fh, '<cols>');
        $widths = $options['widths'] ?? [12, 12, 28, 14, 36, 8, 8, 10, 14, 14, 10];
        foreach ($widths as $i => $w) {
            $col = $i + 1;
            fwrite($fh, '<col min="'.$col.'" max="'.$col.'" width="'.$w.'" customWidth="1"/>');
        }
        fwrite($fh, '</cols><sheetData>');

        $excelRow = 1;
        $this->writeRowXml($fh, $excelRow, $headings, true);
        $excelRow++;

        $shouldAbort = isset($options['should_abort']) && is_callable($options['should_abort'])
            ? $options['should_abort']
            : null;

        $dataCount = 0;
        foreach ($rows as $row) {
            if ($shouldAbort && ($dataCount % 200) === 0 && $shouldAbort()) {
                fclose($fh);
                throw new RuntimeException('EXPORT_CANCELLED');
            }
            $this->writeRowXml($fh, $excelRow, $row, false);
            $excelRow++;
            $dataCount++;
            if (($dataCount % 5000) === 0) {
                fflush($fh);
            }
        }

        fwrite($fh, '</sheetData></worksheet>');
        fclose($fh);

        return $dataCount;
    }

    /**
     * Columnas de texto forzado (1-based): COMPANY, CC, NAME CC, CUENTA, NAME CUENTA, ORIGIN.
     * Evita que Excel convierta cuentas largas a notación científica (ej. 6.00061E+11).
     *
     * @var array<int, true>
     */
    protected $textColumns = [
        1 => true,
        2 => true,
        3 => true,
        4 => true,
        5 => true,
        8 => true,
    ];

    /**
     * @param  array<int, mixed>  $values
     */
    protected function writeRowXml($fh, int $rowNum, array $values, bool $header): void
    {
        fwrite($fh, '<row r="'.$rowNum.'">');
        foreach (array_values($values) as $i => $value) {
            $colIdx = $i + 1;
            $col = $this->columnLetter($colIdx);
            $ref = $col.$rowNum;
            // Header = estilo 1 (negro). Texto forzado = estilo 2 (formato @).
            if ($header) {
                $style = ' s="1"';
            } elseif (isset($this->textColumns[$colIdx])) {
                $style = ' s="2"';
            } else {
                $style = '';
            }

            $forceText = $header || isset($this->textColumns[$colIdx]);

            if ($value === null || $value === '') {
                if ($header || $forceText) {
                    fwrite($fh, '<c r="'.$ref.'"'.$style.' t="inlineStr"><is><t></t></is></c>');
                }
                continue;
            }

            // Cuentas / CC / textos: siempre inlineStr (nunca número).
            if ($forceText || is_string($value)) {
                $text = $this->xmlText((string) $value);
                fwrite($fh, '<c r="'.$ref.'"'.$style.' t="inlineStr"><is><t>'.$text.'</t></is></c>');
                continue;
            }

            if (is_int($value) || is_float($value)) {
                fwrite($fh, '<c r="'.$ref.'"'.$style.'><v>'.$this->formatNumber($value).'</v></c>');
                continue;
            }

            $text = $this->xmlText((string) $value);
            fwrite($fh, '<c r="'.$ref.'"'.$style.' t="inlineStr"><is><t>'.$text.'</t></is></c>');
        }
        fwrite($fh, '</row>');
    }

    protected function formatNumber($value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        $n = (float) $value;
        $s = rtrim(rtrim(sprintf('%.10F', $n), '0'), '.');

        return $s === '-0' ? '0' : $s;
    }

    protected function xmlText(string $value): string
    {
        // Quitar controles inválidos en XML 1.0.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    protected function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    protected function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    protected function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    protected function workbookXml(string $title): string
    {
        $safe = $this->xmlText(mb_substr(trim($title) !== '' ? trim($title) : 'Budget', 0, 31));

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$safe.'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    protected function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    protected function stylesXml(): string
    {
        // Excel exige fills[0]=none y fills[1]=gray125; el negro custom va en fills[2].
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1">'
            .'<numFmt numFmtId="164" formatCode="@"/>'
            .'</numFmts>'
            .'<fonts count="2">'
            .'<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF111827"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            .'</cellStyleXfs>'
            .'<cellXfs count="3">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/></xf>'
            // Estilo texto (@) para CUENTA / CC y evitar 6.00061E+11
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    protected function writeFile(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('No se pudo escribir archivo temporal del Excel.');
        }
    }

    protected function zipDirectory(string $sourceDir, string $zipPath): void
    {
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo Excel (.xlsx).');
        }

        $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $filePath = $file->getPathname();
            $local = substr($filePath, strlen($sourceDir) + 1);
            $local = str_replace(DIRECTORY_SEPARATOR, '/', $local);
            if ($file->isDir()) {
                $zip->addEmptyDir($local);
            } else {
                $zip->addFile($filePath, $local);
            }
        }

        $zip->close();

        if (! is_file($zipPath) || filesize($zipPath) < 64) {
            throw new RuntimeException('El archivo Excel quedó vacío o incompleto.');
        }
    }

    protected function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
    }
}
