<?php

namespace App\Services;

use App\Modules\RiskManagement\Models\VendorAssessment;
use Illuminate\Support\Facades\Log;

class VendorAssessmentAnalysisService
{
    private DirectEvidenceAnalysisService $gemini;

    public function __construct(?DirectEvidenceAnalysisService $gemini = null)
    {
        $this->gemini = $gemini ?? app(DirectEvidenceAnalysisService::class);
    }

    public function analyze(VendorAssessment $assessment): array
    {
        $assessment->loadMissing('responses', 'vendor');

        if ($assessment->responses->isEmpty()) {
            return [];
        }

        $prompt = $this->buildPrompt($assessment);

        try {
            $raw = $this->gemini->callGeminiApi([['text' => $prompt]]);

            $cleaned = trim($raw);
            $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
            $cleaned = preg_replace('/\s*```$/', '', $cleaned);

            $decoded = json_decode($cleaned, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('VendorAssessment AI JSON decode failed: ' . json_last_error_msg());
                return [];
            }

            $result = [
                'strengths' => $decoded['strengths'] ?? [],
                'weaknesses' => $decoded['weaknesses'] ?? [],
                'suggestions' => $decoded['suggestions'] ?? [],
            ];

            $assessment->update([
                'ai_summary' => $result,
                'ai_summary_generated_at' => now(),
            ]);

            $this->storeSuggestions($assessment, $result['suggestions']);

            return $result;
        } catch (\Exception $e) {
            Log::error('VendorAssessment AI analysis failed: ' . $e->getMessage());
            return [];
        }
    }

    private function buildPrompt(VendorAssessment $assessment): string
    {
        $vendorName = $assessment->vendor?->vendor_name ?? 'Unknown Vendor';
        $type = $assessment->assessment_type ?? 'General';

        $lines = [];
        $lines[] = "You are a GRC vendor risk assessment analyst. Analyze the following vendor questionnaire responses.";
        $lines[] = "";
        $lines[] = "Vendor: {$vendorName}";
        $lines[] = "Assessment Type: {$type}";
        $lines[] = "";

        $unansweredKeys = [];

        foreach ($assessment->responses as $i => $r) {
            $qNum = 'Q' . ($i + 1);
            $section = $r->section ? " [{$r->section}]" : '';
            $answer = $r->response_text ?: 'NOT ANSWERED';
            $score = $r->score !== null ? $r->score : 'N/A';
            $maxScore = $r->max_score ?? 'N/A';

            $lines[] = "{$qNum}{$section}: {$r->question_text}";
            $lines[] = "Answer: {$answer}";
            if ($r->response_text === null || $r->response_text === '') {
                $unansweredKeys[] = $r->question_key;
            }

            $compliance = $r->is_compliant !== null ? ($r->is_compliant ? 'Yes' : 'No') : 'N/A';
            $lines[] = "Score: {$score}/{$maxScore}  Compliant: {$compliance}";
            $lines[] = "";
        }

        $lines[] = "Respond ONLY with a raw JSON object. NO markdown, NO code blocks, NO backticks.";
        $lines[] = "";
        $lines[] = '{';
        $lines[] = '  "strengths": [';
        $lines[] = '    {"strength": "Description of a strength shown by the vendor", "questions": ["Q1", "Q3"]}';
        $lines[] = '  ],';
        $lines[] = '  "weaknesses": [';
        $lines[] = '    {"weakness": "Description of a gap, vague answer, or concerning response", "questions": ["Q2"]}';
        $lines[] = '  ],';
        $lines[] = '  "suggestions": [';
        $lines[] = '    {"question_key": "the_question_key", "suggested_answer": "A plausible suggested answer based on the vendor\\\'s other responses and common industry practices"}';
        $lines[] = '  ]';
        $lines[] = '}';

        return implode("\n", $lines);
    }

    private function storeSuggestions(VendorAssessment $assessment, array $suggestions): void
    {
        if (empty($suggestions)) {
            return;
        }

        foreach ($suggestions as $suggestion) {
            $key = $suggestion['question_key'] ?? null;
            $answer = $suggestion['suggested_answer'] ?? null;

            if (!$key || !$answer) {
                continue;
            }

            $response = $assessment->responses->firstWhere('question_key', $key);

            if ($response && ($response->response_text === null || $response->response_text === '')) {
                $response->update([
                    'ai_suggested_answer' => $answer,
                ]);
            }
        }
    }
}
