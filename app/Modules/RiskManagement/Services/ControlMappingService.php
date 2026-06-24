<?php

namespace App\Modules\RiskManagement\Services;

use App\Models\FrameworkControl;
use App\Models\Control;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskControlMapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ControlMappingService
{
    private float $minConfidence;
    private int $suggestionLimit;
    private float $fuzzyWeight;
    private float $keywordWeight;
    private float $presenceBonus;

    public function __construct()
    {
        $this->minConfidence   = config('rmm.matching.min_confidence', 15.0);
        $this->suggestionLimit = config('rmm.matching.suggestion_limit', 10);
        $this->fuzzyWeight     = config('rmm.matching.fuzzy_weight', 0.40);
        $this->keywordWeight   = config('rmm.matching.keyword_weight', 0.25);
        $this->presenceBonus   = config('rmm.matching.presence_bonus', 20.0);
    }

    /**
     * Suggest framework controls that match a given risk entry or free-text query.
     *
     * @param RiskRegister|string $source  RiskRegister model OR a free-text description
     * @param int|null            $limit
     * @param int|null            $frameworkId  Restrict suggestions to a specific framework
     * @return Collection  [{framework_control, confidence_score, match_type, ...}]
     */
    public function suggest(RiskRegister|string $source, ?int $limit = null, ?int $frameworkId = null): Collection
    {
        $limit ??= $this->suggestionLimit;

        $query = $source instanceof RiskRegister
            ? $this->buildQueryFromRisk($source)
            : $source;

        $query = $this->normalizeText($query);

        $controls = $this->loadCandidates($frameworkId);

        $scores = $controls->map(fn (FrameworkControl $fc) => [
            'framework_control' => $fc,
            'confidence_score'  => $this->computeCombinedScore($query, $fc),
        ])
        ->filter(fn ($s) => $s['confidence_score'] >= $this->minConfidence)
        ->sortByDesc('confidence_score')
        ->values()
        ->take($limit);

        return $scores;
    }

    /**
     * Suggest both framework controls AND local controls.
     */
    public function suggestAll(RiskRegister|string $source, ?int $limit = null, ?int $frameworkId = null): array
    {
        $frameworkSuggestions = $this->suggest($source, $limit, $frameworkId);

        $localSuggestions = $this->suggestLocalControls($source, $limit);

        return [
            'framework_controls' => $frameworkSuggestions,
            'local_controls'     => $localSuggestions,
        ];
    }

    /**
     * Suggest local (internal) controls.
     */
    public function suggestLocalControls(RiskRegister|string $source, ?int $limit = null): Collection
    {
        $limit ??= $this->suggestionLimit;

        $query = $source instanceof RiskRegister
            ? $this->buildQueryFromRisk($source)
            : $source;

        $query = $this->normalizeText($query);
        $controls = Control::where('is_active', true)->get();

        return $controls
            ->map(fn (Control $c) => [
                'control'          => $c,
                'confidence_score' => $this->computeLocalScore($query, $c),
            ])
            ->filter(fn ($s) => $s['confidence_score'] >= $this->minConfidence)
            ->sortByDesc('confidence_score')
            ->values()
            ->take($limit);
    }

    /**
     * Persist a suggested mapping.
     */
    public function createSuggestion(int $riskRegisterId, int $frameworkControlId, ?int $controlId = null, ?float $confidence = null): RiskControlMapping
    {
        return RiskControlMapping::updateOrCreate(
            [
                'risk_register_id'     => $riskRegisterId,
                'framework_control_id' => $frameworkControlId,
            ],
            [
                'control_id'       => $controlId,
                'mapping_status'   => 'suggested',
                'confidence_score' => $confidence,
                'mapped_by'        => Auth::id(),
                'mapped_at'        => now(),
            ]
        );
    }

    /**
     * Confirm a suggested mapping (user accepted).
     */
    public function confirmMapping(int $mappingId): ?RiskControlMapping
    {
        $mapping = RiskControlMapping::findOrFail($mappingId);
        $mapping->update([
            'mapping_status' => 'confirmed',
            'mapped_by'      => Auth::id(),
            'mapped_at'      => now(),
        ]);
        return $mapping->fresh();
    }

    /**
     * Reject a suggested mapping.
     */
    public function rejectMapping(int $mappingId): ?RiskControlMapping
    {
        $mapping = RiskControlMapping::findOrFail($mappingId);
        $mapping->update([
            'mapping_status' => 'rejected',
            'mapped_by'      => Auth::id(),
            'mapped_at'      => now(),
        ]);
        return $mapping->fresh();
    }

    /**
     * Manually map a risk to a control (overrides any existing suggestion).
     */
    public function manualMap(int $riskRegisterId, int $frameworkControlId, ?int $controlId = null, ?string $notes = null): RiskControlMapping
    {
        return RiskControlMapping::updateOrCreate(
            [
                'risk_register_id'     => $riskRegisterId,
                'framework_control_id' => $frameworkControlId,
            ],
            [
                'control_id'       => $controlId,
                'mapping_status'   => 'confirmed',
                'confidence_score' => 100.0,
                'notes'            => $notes,
                'mapped_by'        => Auth::id(),
                'mapped_at'        => now(),
            ]
        );
    }

    public function unmap(int $riskRegisterId, int $frameworkControlId): void
    {
        RiskControlMapping::where('risk_register_id', $riskRegisterId)
            ->where('framework_control_id', $frameworkControlId)
            ->delete();
    }

    /* ------------------------------------------------------------------ */
    /*  Internal — Scoring                                                 */
    /* ------------------------------------------------------------------ */

    private function computeCombinedScore(string $query, FrameworkControl $fc): float
    {
        $target = $this->normalizeText(
            implode(' ', array_filter([
                $fc->control_id,
                $fc->domain,
                $fc->requirement_description,
                $fc->required_evidence ?? '',
                $fc->control_name,
            ]))
        );

        $fuzzyScore = $this->fuzzyScore($query, $target);
        $keywordScore = $this->keywordScore($query, $target);
        $exactBoost = $this->exactMatchBoost($query, $fc);
        $presenceBonus = $this->keywordPresenceBonus($query, $target);

        return round(min(100.0, ($fuzzyScore * $this->fuzzyWeight) + ($keywordScore * $this->keywordWeight) + $exactBoost + $presenceBonus), 2);
    }

    private function computeLocalScore(string $query, Control $c): float
    {
        $target = $this->normalizeText(
            implode(' ', array_filter([
                $c->code ?? $c->control_code,
                $c->title ?? $c->name,
                $c->description ?? '',
            ]))
        );

        $fuzzyScore = $this->fuzzyScore($query, $target);
        $keywordScore = $this->keywordScore($query, $target);
        $presenceBonus = $this->keywordPresenceBonus($query, $target);

        return round(min(100.0, ($fuzzyScore * $this->fuzzyWeight) + ($keywordScore * $this->keywordWeight) + $presenceBonus), 2);
    }

    /**
     * Bonus for each overlapping keyword token between query and target.
     */
    private function keywordPresenceBonus(string $query, string $target): float
    {
        $qTokens = array_unique($this->extractTokens($query));
        $tTokens = array_unique($this->extractTokens($target));

        if (empty($qTokens) || empty($tTokens)) {
            return 0.0;
        }

        $matchCount = count(array_intersect($qTokens, $tTokens));

        return $matchCount * $this->presenceBonus;
    }

    /**
     * Tokenize a string into significant words (extracted from keywordScore).
     */
    private function extractTokens(string $text): array
    {
        $stopWords = [
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            'of', 'by', 'with', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'can', 'could',
            'shall', 'should', 'may', 'might', 'must', 'not', 'no', 'nor', 'none',
            'i', 'you', 'he', 'she', 'it', 'we', 'they', 'this', 'that', 'these', 'those',
            'its', 'their', 'our', 'your', 'his', 'her', 'all', 'each', 'every',
            'any', 'some', 'such', 'only', 'own', 'same', 'so', 'than', 'too',
            'very', 'just', 'about', 'above', 'after', 'again', 'against', 'below',
            'between', 'both', 'down', 'during', 'from', 'into', 'more', 'less',
            'most', 'much', 'near', 'now', 'off', 'once', 'onto', 'out', 'over',
            'then', 'through', 'under', 'up', 'upon', 'when', 'where', 'while', 'why',
        ];

        return array_filter(
            preg_split('/\W+/', strtolower($text)),
            fn ($t) => strlen($t) > 2 && !in_array($t, $stopWords, true)
        );
    }

    /**
     * Fuzzy string matching using Levenshtein and similar_text.
     * Returns a score 0-100.
     */
    private function fuzzyScore(string $query, string $target): float
    {
        if (empty($query) || empty($target)) {
            return 0.0;
        }

        $lev = levenshtein($query, $target);
        $maxLen = max(strlen($query), strlen($target));
        $levScore = $maxLen > 0 ? (1 - ($lev / $maxLen)) * 100 : 0;

        similar_text($query, $target, $simPct);

        return ($levScore * 0.6) + ($simPct * 0.4);
    }

    /**
     * Keyword token overlap scoring.
     * Strips common stop words and counts intersecting tokens.
     */
    private function keywordScore(string $query, string $target): float
    {
        $qTokens = $this->extractTokens($query);
        $tTokens = $this->extractTokens($target);

        if (empty($qTokens) || empty($tTokens)) {
            return 0.0;
        }

        $intersect = array_intersect($qTokens, $tTokens);
        $union = array_unique(array_merge($qTokens, $tTokens));

        $jaccard = count($union) > 0 ? count($intersect) / count($union) : 0;

        $coverage = count($qTokens) > 0
            ? count(array_unique($intersect)) / count(array_unique($qTokens))
            : 0;

        return min(100.0, (($jaccard * 0.5) + ($coverage * 0.5)) * 100);
    }

    /**
     * Bonus if the control_id or domain appears directly in the query.
     */
    private function exactMatchBoost(string $query, FrameworkControl $fc): float
    {
        $boost = 0.0;

        if ($fc->control_id && str_contains($query, strtolower($fc->control_id))) {
            $boost += 20.0;
        }

        if ($fc->domain && str_contains($query, strtolower($fc->domain))) {
            $boost += 10.0;
        }

        if ($fc->pci_dss_ref && str_contains($query, strtolower($fc->pci_dss_ref))) {
            $boost += 15.0;
        }

        if ($fc->iso_ref && str_contains($query, strtolower($fc->iso_ref))) {
            $boost += 15.0;
        }

        return $boost;
    }

    /* ------------------------------------------------------------------ */
    /*  Internal — Helpers                                                  */
    /* ------------------------------------------------------------------ */

    private function buildQueryFromRisk(RiskRegister $risk): string
    {
        return implode(' ', array_filter([
            $risk->asset_process_service ?? '',
            $risk->threats ? (is_array($risk->threats) ? implode(' ', $risk->threats) : $risk->threats) : '',
            $risk->vulnerabilities ? (is_array($risk->vulnerabilities) ? implode(' ', $risk->vulnerabilities) : $risk->vulnerabilities) : '',
            $risk->existing_control ?? '',
            $risk->proposed_control ?? '',
            $risk->category ?? '',
            $risk->department ?? '',
        ]));
    }

    private function loadCandidates(?int $frameworkId = null): Collection
    {
        $query = FrameworkControl::with('framework');

        if ($frameworkId) {
            $query->where('framework_id', $frameworkId);
        }

        return $query->get();
    }

    private function normalizeText(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim(mb_strtolower($text));
    }
}
