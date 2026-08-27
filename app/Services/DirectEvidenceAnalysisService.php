<?php

namespace App\Services;

use App\Models\EvidenceFile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class DirectEvidenceAnalysisService
{
    private string $aiProvider;

    private string $clamAvUrl;

    private string $ollamaUrl;

    private string $ollamaModel;

    private EvidenceScanService $scanService;

    /**
     * Per-stage processing latencies captured during process().
     *
     * @var array{scan_ms: int, analysis_ms: int, total_ms: int}
     */
    private array $timings = [
        'scan_ms' => 0,
        'analysis_ms' => 0,
        'total_ms' => 0,
    ];

    private bool $parseFailed = false;

    private const MAX_FILE_SIZE = 20 * 1024 * 1024;

    /**
     * Character budget for text routed into the prompt, sized to fit the
     * Ollama num_ctx of 8192 tokens (roughly 24,000 characters).
     */
    private const TEXT_BUDGET_CHARS = 24000;

    private const EVIDENCE_CONTENT_HEADING = 'Evidence file content:';

    public function __construct(?EvidenceScanService $scanService = null)
    {
        $this->scanService = $scanService ?? app(EvidenceScanService::class);
        $this->aiProvider = config('services.ai.provider', 'ollama');
        $this->clamAvUrl = env('CLAMAV_API_URL', 'http://localhost:9000');
        $this->ollamaUrl = config('services.ollama.url', 'http://localhost:11434');
        $this->ollamaModel = config('services.ollama.model', 'llava:7b');
    }

    private function scanWithClamAV(EvidenceFile $evidence): string
    {
        try {
            $fileContents = Storage::disk('public')->get($evidence->file_path);
            if (! $fileContents) {
                return 'failed';
            }

            $response = Http::timeout(30)
                ->attach('file', $fileContents, $evidence->original_filename)
                ->post("{$this->clamAvUrl}/scan");

            $result = $response->json();

            // clamav-rest reports infections as HTTP 406 with
            // {"Status":"FOUND","Description":"<VirusName>"}, so the body must
            // be inspected before the HTTP status code.
            $clamStatus = is_array($result) ? ($result['Status'] ?? null) : null;
            $description = is_array($result) ? ($result['Description'] ?? '') : '';

            if ($clamStatus === 'FOUND') {
                $virusName = $description ?: 'Malware detected by ClamAV scan';
                Log::warning("SECURITY: Infected file detected by ClamAV. Quarantining evidence_file_id: {$evidence->id}");

                $this->scanService->quarantine($evidence, $virusName, $result);

                return 'infected';
            }

            if (! $response->successful() || $clamStatus === 'ERROR') {
                Log::warning("ClamAV scan HTTP {$response->status()} for evidence_file_id: {$evidence->id}");
                $this->scanService->recordScan($evidence, 'failed', ['status' => $response->status()]);

                return 'failed';
            }

            $evidence->update([
                'scan_status' => 'clean',
                'scan_details' => $result,
            ]);

            $this->scanService->recordScan($evidence, 'clean', $result);

            Log::info("ClamAV scan clean for evidence_file_id: {$evidence->id}");

            return 'clean';
        } catch (\Exception $e) {
            Log::warning('ClamAV scan unavailable (proceeding without scan): '.$e->getMessage());

            return 'clean';
        }
    }

    public function process(EvidenceFile $evidence): EvidenceFile
    {
        $totalStarted = microtime(true);
        $this->timings = ['scan_ms' => 0, 'analysis_ms' => 0, 'total_ms' => 0];

        $evidence->update(['scan_status' => 'processing', 'ai_analysis_status' => 'processing']);

        try {
            $scanStarted = microtime(true);
            $scanResult = $this->scanWithClamAV($evidence);
            $this->timings['scan_ms'] = $this->elapsedMs($scanStarted);

            if ($scanResult === 'infected') {
                $this->timings['total_ms'] = $this->elapsedMs($totalStarted);

                return $this->saveTimings($evidence->fresh());
            }

            $fileContents = Storage::disk('public')->get($evidence->file_path);
            if (! $fileContents) {
                throw new \RuntimeException("File not found at {$evidence->file_path}");
            }

            if (strlen($fileContents) > self::MAX_FILE_SIZE) {
                throw new \RuntimeException('File exceeds 20MB limit for AI analysis.');
            }

            $requirementText = $this->getRequirementText($evidence);

            $aiConfigured = $this->aiProvider === 'ollama';

            $reportFields = [];
            if ($aiConfigured) {
                $aiStarted = microtime(true);
                $result = $this->callLlm($fileContents, $evidence->original_filename, $requirementText);
                $this->timings['analysis_ms'] = $this->elapsedMs($aiStarted);
                $observations = $result['observations'] ?? 'No observations generated.';
                $recommendations = $result['recommendations'] ?? 'No recommendations generated.';
                $reportFields = array_filter(array_merge($result['report_fields'] ?? [], [
                    'observation' => $observations,
                    'recommended_action' => $recommendations,
                    'evidence_provided' => $evidence->original_filename,
                ]), fn ($v) => $v !== null);
            } else {
                $observations = 'AI analysis skipped: no AI provider configured.';
                $recommendations = 'Set AI_PROVIDER=ollama in .env and ensure Ollama is running to enable AI analysis.';
            }

            $this->timings['total_ms'] = $this->elapsedMs($totalStarted);

            $evidence->update([
                'scan_status' => $scanResult === 'failed' ? 'clean' : $scanResult,
                'ai_observations' => $observations,
                'ai_recommendations' => $recommendations,
                'ai_gaps' => $result['gaps'] ?? [],
                'ai_analysis_status' => $aiConfigured ? 'awaiting_review' : 'completed',
                'ai_parse_fallback' => $aiConfigured ? ($this->parseFailed ? 1 : 0) : 0,
                'analysis_report_data' => $reportFields ?: null,
            ]);

            $evidence->fresh()->recordAnalysisVersion('ai_analysis');

            Log::info("Direct evidence analysis completed for evidence_file_id: {$evidence->id}");

            return $this->saveTimings($evidence->fresh());
        } catch (\Exception $e) {
            Log::error("Direct evidence analysis failed for evidence_file_id {$evidence->id}: ".$e->getMessage());
            $evidence->update([
                'ai_analysis_status' => 'failed',
                'scan_status' => 'failed',
                'ai_observations' => 'Analysis error: '.$e->getMessage(),
                'ai_recommendations' => 'Analysis could not be completed. Please check the file and try again.',
                'ai_gaps' => [],
            ]);
            $evidence->fresh()->recordAnalysisVersion('ai_analysis');
            $this->timings['total_ms'] = $this->elapsedMs($totalStarted);

            return $this->saveTimings($evidence->fresh());
        }
    }

    /**
     * The per-stage latencies captured during process().
     *
     * @return array{scan_ms: int, analysis_ms: int, total_ms: int}
     */
    public function timings(): array
    {
        return $this->timings;
    }

    /**
     * Whether the most recent LLM response required regex fallback parsing.
     */
    public function parseFallbackStatus(): bool
    {
        return $this->parseFailed;
    }

    /**
     * Persist the captured per-stage latencies onto the evidence row.
     */
    private function saveTimings(EvidenceFile $evidence): EvidenceFile
    {
        $evidence->update([
            'scan_ms' => $this->timings['scan_ms'],
            'analysis_ms' => $this->timings['analysis_ms'],
            'total_ms' => $this->timings['total_ms'],
        ]);

        return $evidence->fresh();
    }

    private function elapsedMs(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }

    private function callLlm(string $fileContents, string $fileName, string $requirementText): array
    {
        $parts = $this->buildPromptParts($fileContents, $fileName, $requirementText);

        $raw = $this->callLlmApi($parts);

        return $this->parseLlmResponse($raw);
    }

    /**
     * Build the Ollama request parts for an evidence file: the static prompt
     * followed by the file content routed by its detected MIME type.
     *
     * Only genuine images (image/*) are emitted as inlineData vision parts.
     * PDFs have their text layer extracted first, and text-like files are
     * decoded to a UTF-8 string; both are truncated to the prompt budget and
     * passed as a plain text part so non-vision models still receive the
     * evidence. Anything else keeps the inlineData behaviour and is logged.
     *
     * @return array<int, array{text?: string, inlineData?: array{mimeType: string, data: string}}>
     */
    private function buildPromptParts(string $fileContents, string $fileName, string $requirementText): array
    {
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
5. For the 'gaps' field: Identify specific, concrete gaps where the evidence fails to demonstrate compliance with the requirement. Return a JSON array of objects, each with a "gap" string and a "severity" string (high/medium/low). If the evidence fully satisfies the requirement, return an empty array [].
6. These fields are a DRAFT for a human auditor to review and edit before anything is finalized — never invent evidence, and if the evidence is insufficient to judge, say so explicitly in "non_compliant_details" rather than guessing.
7. Only one of "compliant_description" or "non_compliant_details" should be populated, matching "is_compliant"; leave the other as an empty string.
8. "gap_category" must be exactly one of: Policy, Technical, Process, Organizational, Physical, or an empty string if not applicable.
9. "risk_rating" must be exactly one of: None, Low, Medium, High.
10. "observations", "gap_description", and "recommendations" must NOT repeat the same sentence as each other. "observations" is a CURRENT STATE description ONLY — factually state what exists in the file (what document/screenshot/record it is, what it shows), with no evaluation, no judgment words, and no mention of gaps or missing items. "gap_description" separately names the specific compliance gap that follows from that current state. "recommendations" separately describes the concrete fix. Each field carries different information — never merge them.
11. If "is_compliant" is false, "gap_category", "risk_rating", and "impact_assessment" must each contain a real, non-empty value — never leave any of them blank or "None" when a gap actually exists.
12. "status" must be exactly one of: compliant, partial, non_compliant — your three-class classification of THIS evidence against the requirement. It must agree with the rest of your answer: no gaps and the requirement demonstrated means "compliant"; a stated deficiency that leaves part of the requirement unmet means "partial"; the requirement violated or the evidence insufficient to demonstrate it means "non_compliant".

The structure below is a FORMAT TEMPLATE ONLY. Every bracketed placeholder describes what
to put there — it is NOT sample content, and none of its wording may appear in your answer.
Copying any placeholder text verbatim is a failure to follow these instructions.

NO markdown formatting, NO code blocks, NO backticks. Just the raw JSON with every placeholder
replaced by your own analysis of THIS SPECIFIC evidence file:
{"observations": "<numbered list of what you actually observed in this file, referencing concrete details from it>", "recommendations": "<numbered list of concrete next steps, or the single word None>", "gaps": [{"gap": "<a specific gap you found>", "severity": "high|medium|low"}], "status": "compliant|partial|non_compliant", "is_compliant": <true or false>, "risk_rating": "None|Low|Medium|High", "gap_description": "<same gap as above, or empty string if none>", "impact_assessment": "<business impact of this specific gap, or empty string if none>", "gap_category": "Policy|Technical|Process|Organizational|Physical|<empty string>", "non_compliant_details": "<what is missing from THIS file, or empty string if compliant>", "compliant_description": "<how THIS file satisfies the requirement, or empty string if not compliant>", "remediation_plan": "<concrete steps to close the gap, or empty string if none>", "test_results": "<factual description of what THIS file actually contains>", "meets_standard": <true or false>}
PROMPT;

        $mimeType = (new \finfo)->buffer($fileContents, \FILEINFO_MIME_TYPE) ?: 'application/octet-stream';

        if (str_starts_with($mimeType, 'image/')) {
            return array_merge([['text' => $prompt]], [
                ['inlineData' => ['mimeType' => $mimeType, 'data' => base64_encode($fileContents)]],
            ]);
        }

        if ($mimeType === 'application/pdf') {
            $pdfText = trim($this->extractPdfText($fileContents));

            if ($pdfText === '') {
                Log::info("PDF '{$fileName}' has no extractable text layer; treating it as scanned and falling back to image processing.");

                return array_merge([['text' => $prompt]], [
                    ['inlineData' => ['mimeType' => $mimeType, 'data' => base64_encode($fileContents)]],
                ]);
            }

            return array_merge([['text' => $prompt]], [
                ['text' => self::EVIDENCE_CONTENT_HEADING."\n".$this->truncateText($pdfText)],
            ]);
        }

        if (str_starts_with($mimeType, 'text/')
            || in_array($mimeType, ['application/json', 'application/xml'], true)) {
            return array_merge([['text' => $prompt]], [
                ['text' => self::EVIDENCE_CONTENT_HEADING."\n".$this->truncateText($this->toUtf8($fileContents))],
            ]);
        }

        Log::warning("Unhandled evidence MIME type '{$mimeType}' for '{$fileName}'; sending the file as inline image data.");

        return array_merge([['text' => $prompt]], [
            ['inlineData' => ['mimeType' => $mimeType, 'data' => base64_encode($fileContents)]],
        ]);
    }

    /**
     * Extract the text layer from a PDF. Returns an empty string when the
     * document is scanned or cannot be parsed, so callers can fall back to
     * vision processing.
     */
    private function extractPdfText(string $fileContents): string
    {
        try {
            $document = (new Parser)->parseContent($fileContents);

            return $document->getText() ?? '';
        } catch (\Exception $e) {
            Log::warning('PDF text layer extraction failed: '.$e->getMessage());

            return '';
        }
    }

    /**
     * Trim text to the prompt character budget so it fits num_ctx 8192.
     */
    private function truncateText(string $text): string
    {
        $text = trim($text);

        return mb_strlen($text) <= self::TEXT_BUDGET_CHARS
            ? $text
            : mb_substr($text, 0, self::TEXT_BUDGET_CHARS);
    }

    /**
     * Decode raw bytes to a UTF-8 string, best-effort for legacy encodings.
     */
    private function toUtf8(string $bytes): string
    {
        if (mb_check_encoding($bytes, 'UTF-8')) {
            return $bytes;
        }

        return mb_convert_encoding($bytes, 'UTF-8', 'ISO-8859-1');
    }

    /**
     * Heuristic for whether a model name advertises vision capability.
     */
    private function looksLikeVisionModel(string $model): bool
    {
        $lower = strtolower($model);

        return str_contains($lower, 'llava')
            || str_contains($lower, 'vision')
            || str_contains($lower, 'bakllava')
            || str_contains($lower, 'minicpm')
            || str_contains($lower, 'moondream')
            || str_contains($lower, 'multimodal')
            || str_contains($lower, '-vl');
    }

    public function extractFromPdf(string $fileContent, string $mimeType, string $prompt): ?array
    {
        if ($this->aiProvider !== 'ollama') {
            return null;
        }

        $base64 = base64_encode($fileContent);

        try {
            $raw = $this->callLlmApi([
                ['inlineData' => ['mimeType' => $mimeType, 'data' => $base64]],
                ['text' => $prompt],
            ]);

            $cleaned = trim($raw);
            $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
            $cleaned = preg_replace('/\s*```$/', '', $cleaned);

            $decoded = json_decode($cleaned, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('AI extractFromPdf JSON decode failed: '.json_last_error_msg());

                return null;
            }

            return $decoded;
        } catch (\Exception $e) {
            Log::error('AI extractFromPdf failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Core AI API call: dispatches to the configured provider (Ollama).
     * Returns the raw response text.
     */
    public function callLlmApi(array $parts): string
    {
        return $this->callOllama($parts);
    }

    /**
     * Call Ollama API (local, data never leaves the server).
     */
    private function callOllama(array $parts): string
    {
        $textParts = [];
        $images = [];

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $textParts[] = $part['text'];
            }
            if (isset($part['inlineData']['data'])) {
                $images[] = $part['inlineData']['data'];
            }
        }

        $prompt = implode("\n", $textParts);

        if (! empty($images) && ! $this->looksLikeVisionModel($this->ollamaModel)) {
            Log::warning("Sending image parts to model '{$this->ollamaModel}' which does not look like a vision model; the image data may be ignored.");
        }

        $payload = [
            'model' => $this->ollamaModel,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.1,
                'num_ctx' => 8192,
            ],
        ];

        if (! empty($images)) {
            $payload['images'] = $images;
        }

        try {
            $response = Http::timeout(config('services.ollama.timeout', 300))
                ->post("{$this->ollamaUrl}/api/generate", $payload);

            if (! $response->successful()) {
                $status = $response->status();
                $body = $response->body();
                throw new \RuntimeException("Ollama API error ({$status}): {$body}");
            }

            $json = $response->json();
            $text = $json['response'] ?? null;

            if ($text === null) {
                throw new \RuntimeException('Empty Ollama response');
            }

            return $text;
        } catch (ConnectionException $e) {
            throw new \RuntimeException('Cannot connect to Ollama at '.$this->ollamaUrl.'. Is Ollama running? '.$e->getMessage());
        }
    }

    /**
     * Parse the raw LLM response text into a structured array.
     */
    private function parseLlmResponse(string $rawText): array
    {
        $text = trim($rawText);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $this->parseFailed = false;

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->parseFailed = true;

            return [
                'observations' => $this->extractField($text, 'observations') ?? 'Unable to parse response.',
                'recommendations' => $this->extractField($text, 'recommendations') ?? 'Unable to parse response.',
                'gaps' => [],
                'report_fields' => [],
            ];
        }

        return [
            'observations' => $decoded['observations'] ?? 'No observations generated.',
            'recommendations' => $decoded['recommendations'] ?? 'No recommendations generated.',
            'gaps' => $decoded['gaps'] ?? [],
            // Structured draft fields for the Gap Assessment review form. The auditor
            // reviews and edits every one of these before anything is pushed to the
            // Gap Assessment — see EvidenceController::reviewAndSendToGapAssessment.
            'report_fields' => [
                'status' => self::normalizeStatus($decoded['status'] ?? null),
                'is_compliant' => $decoded['is_compliant'] ?? null,
                'risk_rating' => self::normalizeEnum($decoded['risk_rating'] ?? null, ['None', 'Low', 'Medium', 'High']),
                'gap_description' => $decoded['gap_description'] ?? null,
                'impact_assessment' => $decoded['impact_assessment'] ?? null,
                'gap_category' => self::normalizeEnum($decoded['gap_category'] ?? null, ['Policy', 'Technical', 'Process', 'Organizational', 'Physical']),
                'non_compliant_details' => $decoded['non_compliant_details'] ?? null,
                'compliant_description' => $decoded['compliant_description'] ?? null,
                'remediation_plan' => $decoded['remediation_plan'] ?? null,
                'test_results' => $decoded['test_results'] ?? null,
                'meets_standard' => $decoded['meets_standard'] ?? null,
            ],
        ];
    }

    /**
     * Case-insensitively match a model-returned value against the exact-cased enum
     * the frontend <select> expects. Vision models are inconsistent about casing
     * ("process" vs "Process"), and a near-miss silently fails to select any option
     * in the review form rather than erroring, so this must run before persistence.
     */
    public static function normalizeEnum(?string $value, array $allowed): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        foreach ($allowed as $option) {
            if (strcasecmp(trim($value), $option) === 0) {
                return $option;
            }
        }

        return null;
    }

    /**
     * Normalize the model's three-class compliance status to one of the canonical
     * verdict values. Common model phrasings are mapped first, then the exact
     * enum check is applied. A null return means the model did not supply a
     * usable status and the evaluation runner should fall back to the gap-count
     * heuristic.
     */
    public static function normalizeStatus(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $aliases = [
            'fully_compliant' => 'compliant',
            'fully compliant' => 'compliant',
            'complies' => 'compliant',
            'partially_compliant' => 'partial',
            'partially compliant' => 'partial',
            'partial compliance' => 'partial',
            'non_compliant' => 'non_compliant',
            'non-compliant' => 'non_compliant',
            'not compliant' => 'non_compliant',
        ];

        $normalised = strtolower(trim($value));

        return $aliases[$normalised] ?? self::normalizeEnum($normalised, ['compliant', 'partial', 'non_compliant']);
    }

    private function extractField(string $text, string $field): ?string
    {
        $pattern = '/"'.preg_quote($field, '/').'"\s*:\s*"((?:[^"\\\\]|\\\\.|\\n)*)"/s';
        if (preg_match($pattern, $text, $matches)) {
            return stripcslashes($matches[1]);
        }

        return null;
    }

    private function getRequirementText(EvidenceFile $evidence): string
    {
        if ($evidence->frameworkControl && ! empty($evidence->frameworkControl->requirement_description)) {
            return $evidence->frameworkControl->requirement_description;
        }
        if ($evidence->requirement && ! empty($evidence->requirement->req_description)) {
            return $evidence->requirement->req_description;
        }

        $controlId = $evidence->frameworkControl->control_id ?? $evidence->requirement->req_num ?? 'unknown';

        // The control exists but has no requirement text on file — telling the model this
        // explicitly (rather than silently substituting a vague placeholder) keeps it from
        // treating a blank requirement as "nothing to compare against" and defaulting to
        // generic output.
        return "No requirement description is on file for control {$controlId}. Do not assume what ".
            'the requirement covers. Base your analysis strictly on what the evidence file itself '.
            'demonstrates, and state in your observations that the requirement text was unavailable.';
    }
}
