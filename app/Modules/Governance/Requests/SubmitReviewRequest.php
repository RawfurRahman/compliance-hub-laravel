<?php

namespace App\Modules\Governance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reviewer_user_id' => 'required|exists:users,id',
            'review_type' => 'sometimes|in:scheduled,ad_hoc,pre_approval',
            'due_date' => 'nullable|date|after_or_equal:today',
            'policy_version_id' => 'nullable|exists:policy_versions,id',
        ];
    }
}
