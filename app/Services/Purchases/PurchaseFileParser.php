<?php

namespace App\Services\Purchases;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PurchaseFileParser
{
    public function parse(string $path, int $supplierId, array $options = []): array
    {
        $extension = $this->detectExtension($path, $options);

        if ($extension === 'csv') {
            return $this->parseCsv($path, $supplierId, $options);
        }

        if ($extension === 'xlsx') {
            return $this->parseXlsx($path, $supplierId, $options);
        }

        return [
            'items' => [],
            'errors' => [
                ['row' => 0, 'message' => 'Unsupported file type.'],
            ],
        ];
    }

    private function detectExtension(string $path, array $options): string
    {
        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
        $candidate = $extension;

        if ($candidate === '' && ! empty($options['extension'])) {
            $candidate = Str::lower((string) $options['extension']);
        }

        if ($candidate === '' && ! empty($options['original_name'])) {
            $candidate = Str::lower(pathinfo((string) $options['original_name'], PATHINFO_EXTENSION));
        }

        if ($candidate === 'csv' || $candidate === 'xlsx') {
            return $candidate;
        }

        // сначала проверяем на xlsx, чтобы не поймать случайную запятую в бинарнике
        if ($this->looksLikeXlsx($path)) {
            return 'xlsx';
        }

        if ($this->looksLikeCsv($path)) {
            return 'csv';
        }

        return '';
    }

    private function looksLikeCsv(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return false;
        }

        $sample = fread($handle, 2048);
        fclose($handle);

        if ($sample === false || $sample === '') {
            return false;
        }

        if (substr($sample, 0, 3) === "\xEF\xBB\xBF") {
            $sample = substr($sample, 3);
        }

        $lines = preg_split("/\r\n|\n|\r/", trim($sample));
        $firstLine = $lines[0] ?? '';

        return $firstLine !== '' && (str_contains($firstLine, ',') || str_contains($firstLine, ';'));
    }

    private function looksLikeXlsx(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return false;
        }

        $signature = fread($handle, 4);
        fclose($handle);

        return $signature === "PK\x03\x04";
    }

    private function parseCsv(string $path, int $supplierId, array $options): array
    {
        $content = file_get_contents($path);
        $content = $this->normalizeEncoding($content);

        $lines = preg_split("/\r\n|\n|\r/", trim($content));
        $delimiter = $this->detectDelimiter($lines[0] ?? '');

        $rows = [];
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $rows[] = str_getcsv($line, $delimiter);
        }

        return $this->parseRows($rows, $supplierId, $options);
    }

    private function parseXlsx(string $path, int $supplierId, array $options): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('Sheet1') ?? $spreadsheet->getActiveSheet();
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = (int) $sheet->getHighestRow();
        $rows = $sheet->rangeToArray("A1:{$highestColumn}{$highestRow}", null, true, false);

        return $this->parseRows($rows, $supplierId, $options);
    }

    private function parseRows(array $rows, int $supplierId, array $options): array
    {
        $errors = [];
        $items = [];

        if (empty($rows)) {
            return ['items' => [], 'errors' => [['row' => 0, 'message' => 'Empty file.']]];
        }

        $rowOffset = 1;
        while (! empty($rows) && $this->isRowEmpty($rows[0])) {
            array_shift($rows);
            $rowOffset++;
        }

        if (empty($rows)) {
            return ['items' => [], 'errors' => [['row' => 0, 'message' => 'Empty file.']]];
        }

        $header = array_map(fn ($value) => $this->normalizeHeader($value), array_shift($rows));
        $indexes = $this->buildIndexMap($header);

        // Absolute fallback: if после всех попыток не нашли name/price, используем позиции первых колонок
        if (empty($indexes['name']) || empty($indexes['price'])) {
            $indexes['external_code'] = $indexes['external_code'] ?? [0];
            $indexes['name'] = $indexes['name'] ?? [1];
            $indexes['price'] = $indexes['price'] ?? [2];
            $indexes['unit'] = $indexes['unit'] ?? [3];
            $indexes['qty'] = $indexes['qty'] ?? [4];
            $indexes['category'] = $indexes['category'] ?? [5];
        }

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + $rowOffset + 1;
            if ($this->isRowEmpty($row)) {
                continue;
            }
            $normalizedRow = $this->normalizeRow($row);

            $name = $this->getValue($normalizedRow, $indexes['name'] ?? []);
            $priceRaw = $this->getValue($normalizedRow, $indexes['price'] ?? []);
            $externalCode = $this->getValue($normalizedRow, $indexes['external_code'] ?? []);
            $unit = $this->getValue($normalizedRow, $indexes['unit'] ?? []);
            $qty = $this->getValue($normalizedRow, $indexes['qty'] ?? []);
            $category = $this->getValue($normalizedRow, $indexes['category'] ?? []);
            $name = $this->normalizeText($name);
            $externalCode = $this->normalizeText($externalCode);
            $unit = $this->normalizeText($unit);
            $category = $this->normalizeText($category);

            if ($name === '') {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => 'Missing name',
                    'row_data' => $normalizedRow,
                ];
                continue;
            }

            $priceNumber = $this->normalizePrice($priceRaw);
            if ($priceNumber === null) {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => 'Invalid price',
                    'row_data' => $normalizedRow,
                ];
                continue;
            }

            $priceVat = $priceNumber;

            $internalBase = $externalCode !== ''
                ? 'INV|'.$externalCode
                : 'NAME|'.$name.'|'.$unit;
            $internalCode = 'SUP-'.$supplierId.'-'.substr(sha1($internalBase), 0, 12);

            $items[] = [
                'external_code' => $externalCode !== '' ? $externalCode : null,
                'internal_code' => $internalCode,
                'name' => $name,
                'unit' => $unit !== '' ? $unit : null,
                'category' => $category !== '' ? $category : null,
                'qty' => $qty !== '' ? $this->normalizePrice($qty) : null,
                'price_raw' => $priceNumber,
                'price_vat' => $priceVat,
                'row_hash' => sha1($supplierId.'|'.$internalCode.'|'.$name.'|'.$priceVat.'|'.$unit),
            ];
        }

        return [
            'items' => $items,
            'errors' => $errors,
        ];
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value === null) {
                continue;
            }
            if (is_string($value) && trim($value) === '') {
                continue;
            }
            if (! is_string($value)) {
                $stringValue = (string) $value;
                if (trim($stringValue) === '') {
                    continue;
                }
            }

            return false;
        }

        return true;
    }

    private function normalizeRow(array $row): array
    {
        return array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
    }

    private function normalizeHeader($value): string
    {
        if ($value === null) {
            return '';
        }

        $value = is_string($value) ? $value : (string) $value;
        $value = Str::lower(trim($value));
        $value = str_replace(['_', '/', '\\', '(', ')'], ' ', $value);
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value);
        $value = preg_replace('/\s+/', ' ', trim($value));

        return $value;
    }

    private function normalizeText($value): string
    {
        if ($value === null) {
            return '';
        }

        $text = is_string($value) ? $value : (string) $value;
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return $text;
    }

    private function normalizePrice($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = is_string($value) ? $value : (string) $value;
        $text = str_replace([' ', "\u{00A0}"], '', $text);
        $text = str_replace(',', '.', $text);
        $text = preg_replace('/[^0-9\.\-]/', '', $text);

        if ($text === '' || $text === '-' || $text === '.') {
            return null;
        }

        return (float) $text;
    }

    private function detectDelimiter(string $line): string
    {
        $delimiters = [',', ';', "\t"];
        $best = ',';
        $maxCount = 0;

        foreach ($delimiters as $delimiter) {
            $count = substr_count($line, $delimiter);
            if ($count > $maxCount) {
                $maxCount = $count;
                $best = $delimiter;
            }
        }

        return $best;
    }

    private function buildIndexMap(array $header): array
    {
        $map = [
            'internal_code' => ['internal_code', 'code', 'sku', 'артикул'],
            'external_code' => [
                'external_code',
                'external_code(if_exist)',
                'external code if exist',
                'external code(if exist)',
                'invoice code',
                'код накладной',
                'код товару з накладної',
                'код товара из накладной',
                'код товара',
                'код товару',
                'код_товара',
                'код_товару_постачальника(якщо_є)',
                'код товару постачальника якщо є',
                'код товару постачальника(якщо є)',
                'код товару постачальника (якщо є)',
            ],
            'name' => [
                'name',
                'наименование',
                'название',
                'товар',
                'найменування',
                'товар/услуга',
                'товар/послуга',
                'товари(роботи,послуги)',
                'товари (роботи, послуги)',
                'товари роботи послуги',
            ],
            'price' => ['price', 'цена', 'ціна', 'вартість', 'стоимость'],
            'unit' => ['unit', 'unit if exist', 'unit(if_exist)', 'unit(if exist)', 'ед', 'ед.', 'ед.изм', 'од', 'од.', 'од.вим', 'одиниці'],
            'qty' => ['qty', 'quantity', 'quantity if exist', 'quantity(if_exist)', 'quantity(if exist)', 'количество', 'кількість'],
            'vat_percent' => ['vat', 'vat_percent', 'ндс', 'пдв'],
            'category' => [
                'category',
                'category if exist',
                'category(if_exist)',
                'category(if exist)',
                'категория',
                'категорія',
                'категорія,',
                'категорія якщо є',
                'категорія(якщо_є)',
                'категорія (якщо є)',
            ],
        ];

        $indexes = [];
        $normalizedHeader = array_map(fn ($value) => $this->normalizeHeader($value), $header);

        foreach ($map as $key => $aliases) {
            foreach ($aliases as $alias) {
                $aliasNormalized = $this->normalizeHeader($alias);
                foreach ($normalizedHeader as $index => $headerValue) {
                    if ($headerValue === '') {
                        continue;
                    }

                    if ($headerValue === $aliasNormalized || str_contains($headerValue, $aliasNormalized) || str_contains($aliasNormalized, $headerValue)) {
                        $indexes[$key][] = $index;
                        break 2;
                    }
                }
            }
        }

        // Fallback: template order external_code, name, price, unit, qty, category
        if (empty($indexes['name']) && count($normalizedHeader) >= 3) {
            $templateMatch =
                $this->normalizeHeader($header[0] ?? '') === 'external code if exist' &&
                $this->normalizeHeader($header[1] ?? '') === 'name' &&
                $this->normalizeHeader($header[2] ?? '') === 'price';

            if ($templateMatch) {
                $indexes['external_code'][] = 0;
                $indexes['name'][] = 1;
                $indexes['price'][] = 2;
                if (isset($normalizedHeader[3])) {
                    $indexes['unit'][] = 3;
                }
                if (isset($normalizedHeader[4])) {
                    $indexes['qty'][] = 4;
                }
                if (isset($normalizedHeader[5])) {
                    $indexes['category'][] = 5;
                }
            }
        }

        return $indexes;
    }

    private function getValue(array $row, array $indexes): string
    {
        foreach ($indexes as $index) {
            if (array_key_exists($index, $row) && $row[$index] !== null) {
                return (string) $row[$index];
            }
        }

        return '';
    }

    private function normalizeEncoding(string $content): string
    {
        $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1251', 'ISO-8859-1'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            $converted = @iconv($encoding, 'UTF-8//IGNORE', $content);
            if ($converted !== false) {
                return $converted;
            }
        }

        return $content;
    }
}
