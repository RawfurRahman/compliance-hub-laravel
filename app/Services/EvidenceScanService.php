<?php

namespace App\Services;

use App\Models\EvidenceFile;
use App\Models\EvidenceScanLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EvidenceScanService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_CLEAN = 'clean';

    public const STATUS_INFECTED = 'infected';

    public const STATUS_FAILED = 'failed';

    /**
     * Persist a scan log row for an evidence file.
     */
    public function recordScan(
        EvidenceFile $evidence,
        string $status,
        ?array $scanDetails = null,
        ?string $virusName = null,
        ?bool $quarantined = false,
        ?string $quarantinePath = null,
    ): EvidenceScanLog {
        return EvidenceScanLog::create([
            'evidence_file_id' => $evidence->id,
            'project_id' => $evidence->project_id,
            'user_id' => $evidence->user_id,
            'original_filename' => $evidence->original_filename,
            'file_path' => $evidence->file_path,
            'scan_status' => $status,
            'virus_name' => $virusName,
            'scan_details' => $scanDetails,
            'quarantined' => $quarantined,
            'quarantine_path' => $quarantinePath,
            'scanned_at' => now(),
        ]);
    }

    /**
     * Handle an infected file: move the physical file to a private
     * quarantine location, keep the EvidenceFile record for traceability,
     * and write a scan log entry. Never deletes.
     */
    public function quarantine(EvidenceFile $evidence, string $virusName, ?array $scanDetails = null): EvidenceScanLog
    {
        $sourceDisk = Storage::disk('public');
        $destinationDisk = Storage::disk('quarantine');
        $quarantinePath = null;

        if ($sourceDisk->exists($evidence->file_path)) {
            try {
                $quarantinePath = $this->buildQuarantinePath($evidence);
                $destinationDisk->put($quarantinePath, $sourceDisk->get($evidence->file_path));
                $sourceDisk->delete($evidence->file_path);
                Log::warning("EvidenceFile ID {$evidence->id} quarantined to {$quarantinePath} (virus: {$virusName})");
            } catch (\Exception $e) {
                Log::error("Failed to move evidence_file_id {$evidence->id} to quarantine: ".$e->getMessage());
                $quarantinePath = null;
            }
        } else {
            Log::warning("EvidenceFile ID {$evidence->id} physical file not found (already removed?), recording quarantine metadata only.");
        }

        $evidence->update([
            'scan_status' => self::STATUS_INFECTED,
            'scan_details' => $scanDetails ?? $evidence->scan_details,
            'ai_analysis_status' => 'skipped_due_to_scan',
            'file_path' => $quarantinePath ?: $evidence->file_path,
        ]);

        return $this->recordScan(
            $evidence,
            self::STATUS_INFECTED,
            $scanDetails,
            $virusName,
            true,
            $quarantinePath,
        );
    }

    /**
     * Aggregate ClamAV scan statistics for the dashboard.
     */
    public function stats(): array
    {
        $logs = EvidenceScanLog::query();

        $totalScanned = (clone $logs)->count();
        $clean = (clone $logs)->where('scan_status', self::STATUS_CLEAN)->count();
        $infected = (clone $logs)->where('scan_status', self::STATUS_INFECTED)->count();
        $quarantined = (clone $logs)->where('quarantined', true)->count();
        $pending = (clone $logs)->where('scan_status', self::STATUS_PENDING)->count();
        $processing = (clone $logs)->where('scan_status', self::STATUS_PROCESSING)->count();
        $failed = (clone $logs)->where('scan_status', self::STATUS_FAILED)->count();

        return [
            'total_scanned' => $totalScanned,
            'clean' => $clean,
            'infected' => $infected,
            'quarantined' => $quarantined,
            'pending' => $pending,
            'processing' => $processing,
            'failed' => $failed,
        ];
    }

    /**
     * Recent quarantined files for the dashboard panel.
     */
    public function recentQuarantined(int $limit = 10)
    {
        return EvidenceScanLog::query()
            ->with(['project'])
            ->where('quarantined', true)
            ->latest('scanned_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Build a unique quarantine storage path under a per-evidence subfolder.
     */
    private function buildQuarantinePath(EvidenceFile $evidence): string
    {
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $evidence->original_filename) ?: 'evidence';

        return 'evidence_'.$evidence->id.'/'.now()->format('Ymd_His').'_'.$safeName;
    }
}
