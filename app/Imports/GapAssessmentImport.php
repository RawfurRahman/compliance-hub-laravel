<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\GapControl;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GapAssessmentImport implements WithMultipleSheets
{
    protected $projectId;
    protected $filePath;

    public function __construct(int $projectId, string $filePath)
    {
        $this->projectId = $projectId;
        $this->filePath = $filePath;
    }

    /**
     * Parse sheets dynamically based on the Excel file's worksheets.
     */
    public function sheets(): array
    {
        $sheets = [];
        try {
            $reader = IOFactory::createReaderForFile($this->filePath);
            $sheetNames = $reader->listWorksheetNames($this->filePath);
            
            foreach ($sheetNames as $name) {
                $sheets[$name] = new DepartmentSheetImport($this->projectId, $name);
            }
        } catch (\Exception $e) {
            // Default fallback if loading worksheet names fails
            $sheets[0] = new DepartmentSheetImport($this->projectId, 'General');
        }

        return $sheets;
    }
}

class DepartmentSheetImport implements ToCollection, WithHeadingRow
{
    protected $projectId;
    protected $departmentName;

    public function __construct(int $projectId, string $departmentName)
    {
        $this->projectId = $projectId;
        $this->departmentName = $departmentName;
    }

    /**
     * Import row data from a specific sheet.
     */
    public function collection(Collection $rows)
    {
        // 1. Create or retrieve the department corresponding to the sheet name
        $department = Department::firstOrCreate([
            'name' => trim($this->departmentName)
        ]);

        // 2. Loop through rows and insert/update GapControl models
        foreach ($rows as $row) {
            $controlId = $row['control_id'] ?? $row['controlid'] ?? null;
            if (!$controlId) {
                continue;
            }

            $description = $row['requirement_description'] ?? $row['requirement'] ?? $row['description'] ?? 'No description provided.';
            $evidence = $row['required_evidence'] ?? $row['evidence'] ?? null;
            
            // Map status
            $doneVal = strtolower(trim($row['done'] ?? $row['status'] ?? ''));
            $isDone = in_array($doneVal, ['yes', 'done', '1', 'true', 'x']);
            $status = $isDone ? 'Done' : 'Pending';

            GapControl::updateOrCreate(
                [
                    'project_id' => $this->projectId,
                    'control_id' => trim($controlId),
                ],
                [
                    'department_id' => $department->id,
                    'requirement_description' => $description,
                    'required_evidence' => $evidence,
                    'status' => $status,
                ]
            );
        }
    }
}
