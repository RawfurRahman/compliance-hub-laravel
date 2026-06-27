<?php

namespace App\Modules\Governance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExceptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'policy_id' => 'required|exists:policies,id',
            'policy_version_id' => 'nullable|exists:policy_versions,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'justification' => 'required|string',
            'effective_date' => 'required|date',
            'expires_at' => 'required|date|after_or_equal:effective_date',
            'department' => 'nullable|string|max:100',
            'risk_acceptance' => 'nullable|string',
        ];
    }
}
