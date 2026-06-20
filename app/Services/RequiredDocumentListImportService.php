<?php

namespace App\Services;

use App\Models\RequiredDocumentList;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use ZipArchive;

class RequiredDocumentListImportService
{
    public function import(UploadedFile $file, int $projectId, int $userId, string $name): RequiredDocumentList
    {
        $path = $file->store("required-document-lists/{$projectId}", 'local');
        $rows = $this->extractRows($file, Storage::disk('local')->path($path));

        if (empty($rows)) {
            throw new RuntimeException('No document requirements could be identified in the uploaded file.');
        }

        return DB::transaction(function () use ($file, $projectId, $userId, $name, $path, $rows) {
            $list = RequiredDocumentList::create([
                'name' => $name,
                'project_id' => $projectId,
                'source_file_name' => $file->getClientOriginalName(),
                'source_file_path' => $path,
                'imported_by' => $userId,
            ]);

            foreach ($rows as $index => $row) {
                $list->documents()->create($row + ['sort_order' => $index + 1]);
            }

            return $list;
        });
    }

    private function extractRows(UploadedFile $file, string $path): array
    {
        return match (strtolower($file->getClientOriginalExtension())) {
            'xlsx', 'xls', 'csv' => $this->extractSpreadsheetRows($path),
            'docx' => $this->extractWordRows($path),
            default => throw new RuntimeException('Only DOCX, XLSX, XLS, and CSV files are supported.'),
        };
    }

    private function extractSpreadsheetRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $rows = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $rows = array_merge($rows, $this->normaliseTabularRows($sheet->toArray('', true, true, false)));
        }

        return $this->uniqueRows($rows);
    }

    private function extractWordRows(string $path): array
    {
        $archive = new ZipArchive();
        if ($archive->open($path) !== true) {
            throw new RuntimeException('The Word document could not be opened.');
        }

        $xml = $archive->getFromName('word/document.xml');
        $archive->close();
        if ($xml === false) {
            throw new RuntimeException('The Word document does not contain readable document content.');
        }

        $document = new \DOMDocument();
        $document->loadXML($xml);
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $rows = [];

        foreach ($xpath->query('//w:tbl/w:tr') as $tableRow) {
            $cells = [];
            foreach ($xpath->query('./w:tc', $tableRow) as $cell) {
                $cells[] = $this->nodeText($xpath, './/w:t', $cell);
            }
            if (array_filter($cells, fn ($cell) => $cell !== '')) {
                $rows[] = $cells;
            }
        }

        $parsedRows = $this->normaliseTabularRows($rows);
        if (!empty($parsedRows)) {
            return $this->uniqueRows($parsedRows);
        }

        $paragraphRows = [];
        foreach ($xpath->query('//w:body/w:p') as $paragraph) {
            $text = $this->nodeText($xpath, './/w:t', $paragraph);
            $isListItem = $xpath->query('./w:pPr/w:numPr', $paragraph)->length > 0;
            if ($text !== '' && ($isListItem || !$xpath->query('//w:tbl', $document)->length)) {
                $paragraphRows[] = ['document_name' => $text];
            }
        }

        return $this->uniqueRows($paragraphRows);
    }

    private function normaliseTabularRows(array $rows): array
    {
        $rows = array_values(array_filter(array_map(fn ($row) => array_map(fn ($value) => trim((string) $value), $row), $rows), fn ($row) => (bool) array_filter($row)));
        if (empty($rows)) {
            return [];
        }

        $headerIndex = null;
        foreach (array_slice($rows, 0, 10, true) as $index => $row) {
            $normalised = array_map(fn ($value) => Str::snake($value), $row);
            if (array_intersect($normalised, ['document_name', 'required_document', 'document', 'name'])) {
                $headerIndex = $index;
                break;
            }
        }

        if ($headerIndex === null) {
            return collect($rows)->map(function (array $row) {
                $values = array_values(array_filter($row));
                return [
                    'document_name' => $values[0] ?? '',
                    'description' => count($values) > 1 ? implode(' | ', array_slice($values, 1)) : null,
                ];
            })->filter(fn ($row) => $row['document_name'] !== '')->values()->all();
        }

        $headers = array_map(fn ($header) => Str::snake($header), $rows[$headerIndex]);
        return collect(array_slice($rows, $headerIndex + 1))->map(function (array $row) use ($headers) {
            $data = array_combine($headers, array_pad($row, count($headers), '')) ?: [];
            $name = $data['document_name'] ?? $data['required_document'] ?? $data['document'] ?? $data['name'] ?? '';
            return [
                'document_name' => trim($name),
                'category' => trim($data['category'] ?? $data['document_category'] ?? '') ?: null,
                'reference' => trim($data['reference'] ?? $data['standard_reference'] ?? $data['clause'] ?? '') ?: null,
                'description' => trim($data['description'] ?? $data['details'] ?? $data['purpose'] ?? '') ?: null,
            ];
        })->filter(fn ($row) => $row['document_name'] !== '')->values()->all();
    }

    private function nodeText(\DOMXPath $xpath, string $query, \DOMNode $context): string
    {
        return trim(collect(iterator_to_array($xpath->query($query, $context)))->map(fn ($node) => $node->textContent)->implode(''));
    }

    private function uniqueRows(array $rows): array
    {
        return collect($rows)
            ->map(fn ($row) => array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row))
            ->filter(fn ($row) => !empty($row['document_name']))
            ->unique(fn ($row) => Str::lower($row['document_name']))
            ->values()
            ->all();
    }
}
