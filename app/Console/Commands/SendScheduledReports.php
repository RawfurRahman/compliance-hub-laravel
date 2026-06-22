<?php

namespace App\Console\Commands;

use App\Models\ReportSchedule;
use App\Mail\ComplianceReportMail;
use App\Services\ReportExportService;
use App\Services\ReportGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compliance:send-scheduled-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch pending compliance report schedules via email';

    protected $exportService;
    protected $reportService;

    /**
     * Create a new command instance.
     */
    public function __construct(ReportExportService $exportService, ReportGenerationService $reportService)
    {
        parent::__construct();
        $this->exportService = $exportService;
        $this->reportService = $reportService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting scheduled report processing...');

        $now = now();
        $schedules = ReportSchedule::with('project')
            ->where('next_run_at', '<=', $now)
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No pending schedules found.');
            return 0;
        }

        $this->info("Processing {$schedules->count()} schedules...");

        foreach ($schedules as $schedule) {
            $project = $schedule->project;
            if (!$project) {
                $this->error("Project not found for schedule ID: {$schedule->id}. Skipping.");
                continue;
            }

            try {
                $attachmentsData = [];
                $formats = [];
                if ($schedule->format === 'pdf' || $schedule->format === 'both') {
                    $formats[] = 'pdf';
                }
                if ($schedule->format === 'html' || $schedule->format === 'both') {
                    $formats[] = 'html';
                }

                if (in_array('pdf', $formats)) {
                    $pdfContent = $this->exportService->generatePdfContent($project, $schedule->report_type);
                    $fileName = "{$project->name}-" . str_replace('_', '-', $schedule->report_type) . "-" . $now->format('Y-m-d') . ".pdf";
                    $attachmentsData[] = [
                        'data' => $pdfContent,
                        'name' => $fileName,
                        'mime' => 'application/pdf',
                    ];
                }

                if (in_array('html', $formats)) {
                    $htmlContent = $this->exportService->generateHtmlContent($project, $schedule->report_type);
                    $fileName = "{$project->name}-" . str_replace('_', '-', $schedule->report_type) . "-" . $now->format('Y-m-d') . ".html";
                    $attachmentsData[] = [
                        'data' => $htmlContent,
                        'name' => $fileName,
                        'mime' => 'text/html',
                    ];
                }

                $availableReports = $this->reportService->getAvailableReports($project);
                $reportLabel = collect($availableReports)->firstWhere('type', $schedule->report_type)['label'] ?? ucwords(str_replace('_', ' ', $schedule->report_type));

                $emails = array_map('trim', explode(',', $schedule->recipient_email));

                Mail::to($emails)->send(new ComplianceReportMail(
                    $project->name,
                    $reportLabel,
                    "This is an automated scheduled delivery of your compliance report.",
                    $attachmentsData
                ));

                // Update schedule tracking
                $schedule->last_sent_at = now();
                $schedule->calculateNextRun();
                $schedule->save();

                $this->info("Successfully sent scheduled report ID: {$schedule->id} for Project: {$project->name}");
            } catch (\Exception $e) {
                $this->error("Failed to process schedule ID: {$schedule->id}. Error: " . $e->getMessage());
            }
        }

        $this->info('Finished processing scheduled reports.');
        return 0;
    }
}
