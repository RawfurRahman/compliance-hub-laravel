<?php

namespace Tests\Unit;

use App\Services\EvaluationSyntheticFileFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EvaluationSyntheticFileFactoryTest extends TestCase
{
    private const EVIDENCE_TYPES = [
        'screenshot',
        'diagram',
        'policy_page',
        'config_export',
        'log_extract',
    ];

    private const BODY_MARKER = 'BODY-MARKER-9F3A';

    private EvaluationSyntheticFileFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new EvaluationSyntheticFileFactory;
    }

    #[Test]
    #[DataProvider('evidenceTypes')]
    public function generated_files_are_non_empty_and_native(string $type): void
    {
        $controlId = '8.2.1';
        $body = 'Evidence summary describing the control artefact for XYZ Bank Ltd. '
            .self::BODY_MARKER.' is the distinctive body substring that must survive '
            .'into the generated evidence file unchanged.';

        $bytes = $this->factory->generate($type, $controlId, $body);

        $this->assertNotSame('', $bytes, "{$type} must return non-empty bytes");
        $this->assertGreaterThan(0, strlen($bytes), "{$type} must return non-empty bytes");

        if (in_array($type, ['screenshot', 'diagram'], true)) {
            $this->assertSame("\x89PNG", substr($bytes, 0, 4), "{$type} must start with the PNG magic bytes");
            $this->assertGreaterThan(5 * 1024, strlen($bytes), "{$type} PNG must be larger than 5 KB");
        }

        if ($type === 'policy_page') {
            $this->assertStringStartsWith('%PDF', $bytes, 'policy_page must be a valid PDF');
        }

        if (in_array($type, ['config_export', 'log_extract'], true)) {
            $this->assertStringContainsString(self::BODY_MARKER, $bytes, "{$type} must preserve the evidence body substring");
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function evidenceTypes(): array
    {
        return array_map(
            fn (string $type) => [$type],
            array_combine(self::EVIDENCE_TYPES, self::EVIDENCE_TYPES)
        );
    }
}
