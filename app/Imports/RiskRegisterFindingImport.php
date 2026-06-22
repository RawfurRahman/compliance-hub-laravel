<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;

/**
 * RiskRegisterFindingImport
 *
 * Reads one sheet of the Risk Register Excel workbook and collects the rows.
 * Heading row is set to 3 (so data starts at row 4).
 */
class RiskRegisterFindingImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public Collection $rows;

    public function __construct()
    {
        $this->rows = new Collection();
    }

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }

    public function headingRow(): int
    {
        return 3;
    }
}
