<?php

namespace App\Modules\Governance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePolicyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'domain_id' => 'nullable|exists:domains,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'content' => 'sometimes|string',
            'owner_user_id' => 'nullable|exists:users,id',
            'department' => 'nullable|string|max:100',
            'business_unit' => 'nullable|string|max:100',
        ];
    }
}
