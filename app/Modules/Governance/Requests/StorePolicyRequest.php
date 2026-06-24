<?php

namespace App\Modules\Governance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain_id' => 'nullable|exists:domains,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'owner_user_id' => 'nullable|exists:users,id',
            'department' => 'nullable|string|max:100',
            'business_unit' => 'nullable|string|max:100',
        ];
    }
}
