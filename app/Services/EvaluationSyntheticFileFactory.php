<?php

namespace App\Services;

/**
 * Generates valid, sanitized synthetic evidence files for the evaluation
 * corpus runner. Each file is produced in a genuinely parseable native format
 * so the ClamAV scan and Ollama analysis stages receive real bytes.
 */
class EvaluationSyntheticFileFactory
{
    private const PNG_WIDTH = 1000;

    private const PNG_HEIGHT = 700;

    /**
     * File content for a corpus evidence type.
     *
     * @param  string  $type  One of the evaluation evidence types
     *                        (screenshot, diagram, policy_page, config_export,
     *                        log_extract).
     * @param  string  $controlId  Framework control identifier (retained for
     *                             API compatibility but never embedded into
     *                             generated artefacts, so the model cannot see it).
     * @param  string  $body  Composed body describing the actual state of the
     *                        control (evidence summary only). It is sanitized
     *                        before being embedded so no credential- or
     *                        PAN-shaped string survives.
     */
    public function generate(string $type, string $controlId, string $body): string
    {
        $safeBody = $this->sanitize($body);

        return match ($type) {
            'screenshot' => $this->makePng($safeBody, false),
            'diagram' => $this->makePng($safeBody, true),
            'policy_page' => $this->makePdf($safeBody),
            'config_export' => $this->makeConfig($safeBody),
            'log_extract' => $this->makeLog($safeBody),
            default => "XYZ Bank Ltd\n{$safeBody}\n",
        };
    }

    /**
     * MIME type for a corpus evidence type.
     */
    public function mimeType(string $type): string
    {
        return match ($type) {
            'screenshot', 'diagram' => 'image/png',
            'policy_page' => 'application/pdf',
            'config_export', 'log_extract' => 'text/plain',
            default => 'application/octet-stream',
        };
    }

    /**
     * File extension for a corpus evidence type.
     */
    public function extension(string $type): string
    {
        return match ($type) {
            'screenshot', 'diagram' => 'png',
            'policy_page' => 'pdf',
            'config_export' => 'txt',
            'log_extract' => 'log',
            default => 'txt',
        };
    }

    /**
     * Redact anything that looks like a real credential or identifier.
     */
    private function sanitize(string $text): string
    {
        $text = preg_replace('/(password|passwd|secret|token|api[_-]?key)\s*[:=]\s*\S+/i', '$1: ********', $text) ?? $text;
        $text = preg_replace('/(\d{16}|\d{4}[- ]\d{4}[- ]\d{4}[- ]\d{4})/', '**** **** **** ****', $text) ?? $text;

        return $text;
    }

    /**
     * Wrap a block of text so no line exceeds roughly $width characters.
     *
     * @return string[]
     */
    private function wrapText(string $text, int $width): array
    {
        $wrapped = wordwrap($text, $width, "\n");

        return explode("\n", $wrapped);
    }

    /**
     * Render the title and wrapped body onto a real, legible PNG bitmap.
     * For diagrams, labelled boxes with connecting lines are drawn below the
     * text so the artefact is visually distinguishable from a plain screenshot.
     */
    private function makePng(string $body, bool $diagram): string
    {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('The gd PHP extension is required to render PNG evidence files. Enable extension=gd in php.ini.');
        }

        $fontPath = resource_path('fonts/DejaVuSans.ttf');
        if (! file_exists($fontPath)) {
            throw new \RuntimeException("TrueType font not found at {$fontPath}");
        }

        $titleFontSize = 24;
        $bodyFontSize = 16;
        $lineSpacing = (int) ($bodyFontSize * 1.5); // 24

        $wrappedLines = $this->wrapText($body, 60); // Reduce wrapping to 60 characters

        // Calculate dynamic canvas height
        $minHeight = self::PNG_HEIGHT; // 700
        $textHeight = count($wrappedLines) * $lineSpacing;

        $diagramHeight = $diagram ? 200 : 0;

        // Title starts around y=50, text starts at y=100. padding bottom 50.
        $calculatedHeight = 100 + $textHeight + $diagramHeight + 50;
        $canvasHeight = max($minHeight, $calculatedHeight);

        $image = imagecreatetruecolor(self::PNG_WIDTH, $canvasHeight);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $grey = imagecolorallocate($image, 100, 100, 100);

        imagefilledrectangle($image, 0, 0, self::PNG_WIDTH, $canvasHeight, $white);

        // Title (larger size)
        imagettftext($image, $titleFontSize, 0, 20, 50, $black, $fontPath, 'XYZ Bank Ltd');

        $y = 100;
        foreach ($wrappedLines as $line) {
            imagettftext($image, $bodyFontSize, 0, 20, $y, $black, $fontPath, $line);
            $y += $lineSpacing;
        }

        if ($diagram) {
            $boxes = [
                ['DMZ', 80],
                ['Application Tier', 380],
                ['Database Tier', 680],
            ];
            $boxWidth = 240;
            $boxHeight = 90;
            $boxY = $y + 50; // Dynamic box Y below text

            foreach ($boxes as $index => [$label, $x]) {
                imagerectangle($image, $x, $boxY, $x + $boxWidth, $boxY + $boxHeight, $black);

                // Get bounding box for label to center it
                $bbox = imagettfbbox($bodyFontSize, 0, $fontPath, $label);
                $labelWidth = $bbox[2] - $bbox[0];
                $labelX = $x + (int) (($boxWidth - $labelWidth) / 2);
                $labelY = $boxY + 50;

                imagettftext($image, $bodyFontSize, 0, $labelX, $labelY, $black, $fontPath, $label);

                if ($index < count($boxes) - 1) {
                    $midY = $boxY + (int) ($boxHeight / 2);
                    imageline($image, $x + $boxWidth, $midY, $x + $boxWidth + 60, $midY, $grey);
                }
            }
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    private function makeConfig(string $summary): string
    {
        $lines = [
            '# XYZ Bank Ltd - sanitized configuration export',
            '# generated: '.now()->toIso8601String(),
            '# WARNING: real credentials are never exported; values below are placeholders.',
            '',
            'admin_password = ********',
            'db_password = ********',
            'api_key = ********',
            'tls_version = TLSv1.2',
            'requires_mfa = yes',
        ];

        foreach ($this->wrapText($summary, 95) as $note) {
            $lines[] = '# notes: '.$note;
        }
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function makeLog(string $summary): string
    {
        $redacted = [
            'user=********',
            'card=**** **** **** ****',
            'ip=10.0.0.0/8 (synthetic)',
            'uuid=********',
        ];

        $lines = [
            '# XYZ Bank Ltd - sanitized log extract',
            '# identifiers redacted for the evaluation corpus.',
            '',
        ];
        foreach ($redacted as $entry) {
            $lines[] = now()->format('Y-m-d H:i:s')." [info] {$entry}";
        }
        foreach ($this->wrapText($summary, 95) as $note) {
            $lines[] = '# notes: '.$note;
        }

        return implode("\n", $lines);
    }

    /**
     * Build a minimal, structurally valid single-page PDF that emits the full
     * body across multiple wrapped text lines instead of truncating it.
     */
    private function makePdf(string $body): string
    {
        $content = 'BT /F1 14 Tf 72 730 Td ('.$this->pdfEscape('XYZ Bank Ltd').") Tj ET\n";

        $y = 690;
        foreach ($this->wrapText($body, 95) as $line) {
            $content .= 'BT /F1 10 Tf 72 '.$y.' Td ('.$this->pdfEscape($line).") Tj ET\n";
            $y -= 14;
        }

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($content)." >>\nstream\n".$content.'endstream',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $num = $index + 1;
            $pdf .= "{$num} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        return $pdf;
    }

    private function pdfEscape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
