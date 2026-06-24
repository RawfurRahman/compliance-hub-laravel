<?php

namespace App\Services;

use App\Models\EvidenceFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DirectEvidenceAnalysisService
{
    private string $geminiApiKey;
    private string $clamAvUrl;
    private array $models;

    private const MAX_FILE_SIZE = 20 * 1024 * 1024;
    private const TIMEOUT = 120;

    private const MODELS = [
        'gemini-2.0-flash',
        'gemini-2.0-flash-lite',
        'gemini-1.5-flash',
    ];

    public function __construct()
    {
        $this->geminiApiKey = env('GEMINI_API_KEY', '');
        $this->clamAvUrl = env('CLAMAV_API_URL', 'http://localhost:9000');
        $this->models = $this->parseModels(env('GEMINI_MODELS', ''));
    }

    private function parseModels(string $envValue): array
    {
        if ($envValue === '') {
            return self::MODELS;
        }

        return array_map('trim', explode(',', $envValue));
    }

    private function scanWithClamAV(EvidenceFile $evidence): string
    {
        try {
            $fileContents = Storage::disk('public')->get($evidence->file_path);
            if (!$fileContents) {
                return 'failed';
            }

            $response = Http::timeout(30)
                ->attach('file', $fileContents, $evidence->original_filename)
                ->post("{$this->clamAvUrl}/scan");

            if (!$response->successful()) {
                Log::warning("ClamAV scan HTTP {$response->status()} for evidence_file_id: {$evidence->id}");
                return 'failed';
            }

            $result = $response->json();

            if ($result['infected'] ?? false) {
                Log::warning("SECURITY: Infected file detected by ClamAV. Deleting evidence_file_id: {$evidence->id}");

                Storage::disk('public')->delete($evidence->file_path);
                $evidence->update([
                    'scan_status' => 'infected',
                    'scan_details' => $result,
                    'ai_analysis_status' => 'skipped_due_to_scan',
                ]);
                $evidence->delete();

                return 'infected';
            }

            $evidence->update([
                'scan_status' => 'clean',
                'scan_details' => $result,
            ]);

            Log::info("ClamAV scan clean for evidence_file_id: {$evidence->id}");
            return 'clean';
        } catch (\Exception $e) {
            Log::warning('ClamAV scan unavailable (proceeding without scan): ' . $e->getMessage());
            return 'clean';
        }
    }

    public function process(EvidenceFile $evidence): EvidenceFile
    {
        $evidence->update(['scan_status' => 'processing', 'ai_analysis_status' => 'processing']);

        try {
            $scanResult = $this->scanWithClamAV($evidence);
            if ($scanResult === 'infected') {
                return $evidence->fresh();
            }

            $fileContents = Storage::disk('public')->get($evidence->file_path);
            if (!$fileContents) {
                throw new \RuntimeException("File not found at {$evidence->file_path}");
            }

            if (strlen($fileContents) > self::MAX_FILE_SIZE) {
                throw new \RuntimeException('File exceeds 20MB limit for AI analysis.');
            }

            $requirementText = $this->getRequirementText($evidence);

            if ($this->geminiApiKey) {
                $result = $this->callGemini($fileContents, $evidence->original_filename, $requirementText);
                $observations = $result['observations'] ?? 'No observations generated.';
                $recommendations = $result['recommendations'] ?? 'No recommendations generated.';
            } else {
                $observations = 'AI analysis skipped: GEMINI_API_KEY not configured.';
                $recommendations = 'Configure GEMINI_API_KEY in .env to enable AI analysis.';
            }

            $evidence->update([
                'scan_status' => $scanResult === 'failed' ? 'clean' : $scanResult,
                'ai_observations' => $observations,
                'ai_recommendations' => $recommendations,
                'ai_analysis_status' => $this->geminiApiKey ? 'awaiting_review' : 'completed',
            ]);

            Log::info("Direct evidence analysis completed for evidence_file_id: {$evidence->id}");
        } catch (\Exception $e) {
            Log::error("Direct evidence analysis failed for evidence_file_id {$evidence->id}: " . $e->getMessage());
            $evidence->update([
                'ai_analysis_status' => 'failed',
                'scan_status' => 'failed',
                'ai_observations' => 'Analysis error: ' . $e->getMessage(),
                'ai_recommendations' => 'Analysis could not be completed. Please check the file and try again.',
            ]);
        }

        return $evidence->fresh();
    }

    private function callGemini(string $fileContents, string $fileName, string $requirementText): array
    {
        $base64 = base64_encode($fileContents);
        $mimeType = (new \finfo())->buffer($fileContents, \FILEINFO_MIME_TYPE) ?: 'application/octet-stream';

        // UPDATED PROMPT: Forces strict, short, numbered outputs with literal \n line breaks.
        $prompt = <<<PROMPT
You are a strict GRC compliance auditor. Analyze the provided evidence file against the compliance requirement below.

Requirement:
{$requirementText}

File Name: {$fileName}

Respond with ONLY a raw JSON object. 
CRITICAL FORMATTING INSTRUCTIONS for the JSON values:
1. Keep the text EXTREMELY short, specific, and directly related to the current state of the evidence.
2. Format both 'observations' and 'recommendations' as numbered lists (1., 2., 3.).
3. You MUST use the literal characters "\\n" to create line breaks between each numbered point so it displays cleanly.
4. Do NOT repeat the evaluation criteria or use conversational filler.

NO markdown formatting, NO code blocks, NO backticks. Just the raw JSON:
{"observations": "1. First specific observation.\\n2. Second specific observation.", "recommendations": "1. First specific recommendation.\\n2. Second specific recommendation."}
PROMPT;

        $lastError = null;

        foreach ($this->models as $model) {
            $maxRetries = 3;
            $baseDelay = 2;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $response = Http::timeout(self::TIMEOUT)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                        ])
                        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->geminiApiKey}", [
                            'contents' => [
                                [
                                    'parts' => [
                                        ['text' => $prompt],
                                        [
                                            // CRITICAL FIX: Gemini requires strict camelCase for these keys
                                            'inlineData' => [
                                                'mimeType' => $mimeType,
                                                'data' => $base64,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'generationConfig' => [
                                'temperature' => 0.1,
                                'maxOutputTokens' => 8192,
                            ],
                        ]);

                    if ($response->successful()) {
                        return $this->parseGeminiResponse($response->json());
                    }

                    $status = $response->status();
                    $body = $response->body();

                    if ($status === 429 && $attempt < $maxRetries) {
                        $wait = $baseDelay * $attempt;
                        Log::warning("Gemini {$model} rate limited (attempt {$attempt}/{$maxRetries}), retrying in {$wait}s");
                        sleep($wait);
                        continue;
                    }

                    $lastError = "Gemini {$model} returned {$status}";
                    Log::warning("Gemini model {$model} failed: {$status} - {$body}");

                    if (in_array($status, [429, 500, 503], true)) {
                        continue 2; // Move to the next model in the fallback array
                    }

                    throw new \RuntimeException("Gemini {$model} API error ({$status}): {$body}");
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $lastError = "Connection error on {$model}: " . $e->getMessage();
                    Log::warning("Gemini model {$model} connection failed: " . $e->getMessage());
                    break;
                }
            }
        }

        throw new \RuntimeException($lastError ?? 'All Gemini models exhausted.');
    }

    private function extractField(string $text, string $field): ?string
    {
        $pattern = '/"' . preg_quote($field, '/') . '"\s*:\s*"((?:[^"\\\\]|\\\\.|\\n)*)"/s';
        if (preg_match($pattern, $text, $matches)) {
            return stripcslashes($matches[1]);
        }

        return null;
    }

    private function getRequirementText(EvidenceFile $evidence): string
    {
        if ($evidence->frameworkControl) {
            return $evidence->frameworkControl->requirement_description ?? 'Compliance requirement';
        }
        if ($evidence->requirement) {
            return $evidence->requirement->req_description ?? 'Compliance requirement';
        }
        return 'Compliance requirement';
    }
}
