<?php

namespace Tests\Unit;

use App\Services\DirectEvidenceAnalysisService;
use App\Services\EvaluationSyntheticFileFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EvidencePromptRoutingTest extends TestCase
{
    private const PNG_1PX_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2+nUAAAAASUVORK5CYII=';

    private const EVIDENCE_CONTENT_HEADING = 'Evidence file content:';

    private DirectEvidenceAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DirectEvidenceAnalysisService;
    }

    private function textBudget(): int
    {
        return (new \ReflectionClass(DirectEvidenceAnalysisService::class))
            ->getConstant('TEXT_BUDGET_CHARS');
    }

    /**
     * @return array<int, array{text?: string, inlineData?: array<mixed>}>
     */
    private function buildParts(string $contents, string $fileName, string $requirement): array
    {
        $method = new \ReflectionMethod(DirectEvidenceAnalysisService::class, 'buildPromptParts');
        $method->setAccessible(true);

        return $method->invoke($this->service, $contents, $fileName, $requirement);
    }

    #[Test]
    public function a_png_is_routed_as_inline_image_data_and_not_into_the_text_part(): void
    {
        $png = base64_decode(self::PNG_1PX_BASE64);
        $parts = $this->buildParts($png, 'evidence.png', 'Requirement');

        $this->assertCount(2, $parts);
        $this->assertArrayHasKey('inlineData', $parts[1]);
        $this->assertSame('image/png', $parts[1]['inlineData']['mimeType']);
        $this->assertSame(base64_encode($png), $parts[1]['inlineData']['data']);
        $this->assertStringNotContainsString(base64_encode($png), $parts[0]['text']);
        $this->assertArrayNotHasKey('inlineData', $parts[0]);
    }

    #[Test]
    public function a_utf8_text_file_is_routed_as_text_without_inline_data(): void
    {
        $marker = 'TXT-MARKER-4E2A-RATIONALE';
        $contents = "Plain evidence text.\n".$marker."\nSecond line.";
        $parts = $this->buildParts($contents, 'evidence.txt', 'Requirement');

        $this->assertCount(2, $parts);
        $this->assertStringContainsString(self::EVIDENCE_CONTENT_HEADING, $parts[1]['text']);
        $this->assertStringContainsString($marker, $parts[1]['text']);

        foreach ($parts as $part) {
            $this->assertArrayNotHasKey('inlineData', $part);
        }
    }

    #[Test]
    public function a_text_bearing_pdf_is_routed_as_extracted_text(): void
    {
        $marker = 'PDF-TEXT-MARKER-9B3C';
        $factory = new EvaluationSyntheticFileFactory;
        $pdf = $factory->generate('policy_page', '8.2.1', $marker.' rationale embedded in the text layer.');

        $parts = $this->buildParts($pdf, 'policy.pdf', 'Requirement');

        $this->assertCount(2, $parts);
        $this->assertArrayNotHasKey('inlineData', $parts[1]);
        $this->assertStringContainsString(self::EVIDENCE_CONTENT_HEADING, $parts[1]['text']);
        $this->assertStringContainsString($marker, $parts[1]['text']);
    }

    #[Test]
    public function an_over_budget_text_file_is_truncated_instead_of_passed_whole(): void
    {
        $budget = $this->textBudget();
        $endMarker = 'END-OF-FILE-MARKER';
        $contents = str_repeat('a', $budget + 1000).' '.$endMarker;

        $parts = $this->buildParts($contents, 'big.txt', 'Requirement');

        $this->assertCount(2, $parts);
        $this->assertStringStartsWith(self::EVIDENCE_CONTENT_HEADING, $parts[1]['text']);

        $body = substr($parts[1]['text'], strlen(self::EVIDENCE_CONTENT_HEADING) + 1);
        $this->assertSame($budget, mb_strlen($body));
        $this->assertStringNotContainsString($endMarker, $body);
    }
}
