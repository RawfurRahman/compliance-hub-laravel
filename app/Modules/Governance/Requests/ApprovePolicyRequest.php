<?php

namespace App\Modules\Governance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovePolicyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'comments' => 'nullable|string|max:2000',
        ];
    }
}
