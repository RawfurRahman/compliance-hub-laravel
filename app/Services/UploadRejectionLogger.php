<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Writes file-upload validation rejections to the activity log.
 *
 * Logging is best-effort: a failure to log must never mask the validation
 * rejection itself.
 */
class UploadRejectionLogger
{
    /**
     * Validate a request, writing an activity-log entry if validation fails.
     *
     * @return array<string, mixed> the validated data
     */
    public static function validate(Request $request, array $rules, string $action, array $messages = []): array
    {
        try {
            return $request->validate($rules, $messages);
        } catch (ValidationException $e) {
            static::log($request, $action, $e->errors());

            throw $e;
        }
    }

    /**
     * Persist a rejection entry to the activity log.
     */
    public static function log(
        Request $request,
        string $action,
        array $errors,
        ?string $contextType = null,
        ?int $contextId = null
    ): void {
        try {
            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'action' => $action,
                'description' => 'File upload rejected: '.static::flatten($errors),
                'details' => [
                    'context_type' => $contextType,
                    'context_id' => $contextId,
                    'original_filename' => $request->file('file')?->getClientOriginalName(),
                    'upload_mime' => $request->file('file')?->getMimeType(),
                    'errors' => $errors,
                ],
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            // Never mask the validation rejection with a logging failure.
        }
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    private static function flatten(array $errors): string
    {
        $lines = [];

        foreach ($errors as $messages) {
            foreach ((array) $messages as $message) {
                $lines[] = (string) $message;
            }
        }

        return implode('; ', $lines);
    }
}
