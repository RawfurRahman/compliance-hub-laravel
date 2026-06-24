<?php

namespace App\Modules\Compliance\Services;

use App\Modules\Compliance\Models\ControlEvidence;
use Illuminate\Support\Collection;

class ControlEvidenceService
{
    public function attachToControl(int $controlId, array $data): ControlEvidence
    {
        return ControlEvidence::create(array_merge($data, ['control_id' => $controlId]));
    }

    public function getCurrentEvidence(int $controlId): Collection
    {
        return ControlEvidence::current()
            ->where('control_id', $controlId)
            ->with('collector')
            ->latest()
            ->get();
    }

    public function supersede(int $evidenceId): ControlEvidence
    {
        $evidence = ControlEvidence::findOrFail($evidenceId);
        $evidence->update(['is_current' => false]);

        return $evidence;
    }

    public function getForControl(int $controlId): Collection
    {
        return ControlEvidence::where('control_id', $controlId)
            ->with('collector')
            ->latest()
            ->get();
    }
}
