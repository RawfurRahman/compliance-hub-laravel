<?php

namespace App\Modules\RiskManagement\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * RiskRegisterFindingImport
 *
 * Reads one sheet of the Risk Register Excel workbook and collects the rows.
 * Heading row is set to 3 (so data starts at row 4).
 */
class RiskRegisterFindingImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public Collection $rows;

    public function __construct()
    {
        $this->rows = new Collection;
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
