<?php

namespace App\Modules\Governance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOwnershipRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'role' => 'required|string|max:50',
            'department' => 'nullable|string|max:100',
            'business_unit' => 'nullable|string|max:100',
            'is_primary' => 'sometimes|boolean',
        ];
    }
}
