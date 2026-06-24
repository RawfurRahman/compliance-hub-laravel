<?php

namespace App\Modules\Governance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSLARuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'policy_id' => 'nullable|exists:policies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_event' => 'required|string|max:50',
            'action_type' => 'required|string|max:50',
            'sla_hours' => 'required|integer|min:1',
            'escalation_interval_hours' => 'nullable|integer|min:1',
            'escalation_user_id' => 'nullable|exists:users,id',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
