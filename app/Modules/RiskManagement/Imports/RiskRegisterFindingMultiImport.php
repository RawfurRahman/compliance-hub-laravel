<?php

namespace App\Modules\RiskManagement\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RiskRegisterFindingMultiImport implements WithMultipleSheets
{
    private $import;

    public function __construct($import)
    {
        $this->import = $import;
    }

    public function sheets(): array
    {
        return [
            'Risk Register' => $this->import,
        ];
    }
}
