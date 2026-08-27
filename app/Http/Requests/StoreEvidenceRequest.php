<?php

namespace App\Http\Requests;

use App\Services\UploadRejectionLogger;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates evidence file uploads at the request boundary.
 *
 * Enforces an extension whitelist (mimes), an agreeing content-type whitelist
 * (mimetypes), and a size cap. This is an additional defence layer in front of
 * the ClamAV scan, not a replacement for it.
 */
class StoreEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Access is governed by the route-level auth middleware.
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', config('uploads.evidence.extensions')),
                'mimetypes:'.implode(',', config('uploads.evidence.mimetypes')),
                'max:'.(int) config('uploads.evidence.max_size_kb'),
            ],
            'description' => 'nullable|string|max:500',
        ];

        // Only the main evidence upload (route carries a {project}) requires a
        // requirement to attach the evidence to.
        if ($this->route('project')) {
            $project = $this->route('project');
            $rules['requirement_id'] = $project->module_type === 'pci_dss'
                ? 'required|exists:pci_dss_requirements,id'
                : 'required|exists:framework_controls,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        $formats = implode(', ', config('uploads.evidence.extensions'));
        $maxMb = (int) config('uploads.evidence.max_size_kb') / 1024;

        return [
            'file.required' => 'Please choose a file to upload.',
            'file.mimes' => "The file must be one of the accepted formats: {$formats}.",
            'file.mimetypes' => "The file content does not match the accepted formats. Allowed: {$formats}.",
            'file.max' => "The file may not be larger than {$maxMb} MB.",
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        UploadRejectionLogger::log(
            $this,
            'evidence.upload.rejected',
            $validator->errors()->toArray(),
            'project',
            $this->route('project')?->id,
        );

        parent::failedValidation($validator);
    }
}
