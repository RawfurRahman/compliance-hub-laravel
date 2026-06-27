<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCI DSS Gap Assessment Report - {{ $project->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none; }
            @page { size: A4; margin: 1.5cm; }
        }
        body { font-family: 'Times New Roman', Times, serif; color: #000; font-size: 11pt; }
        .report-container { max-width: 8.5in; margin: auto; padding: 1in; background: white; }
        h1, h2, h3, h4 { font-weight: bold; color: #000; }
        h1 { font-size: 22pt; margin-bottom: 0.5rem; }
        h2 { font-size: 16pt; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid #ccc; padding-bottom: 0.5rem; }
        h3 { font-size: 14pt; margin-top: 1.5rem; margin-bottom: 0.75rem; }
        h4 { font-size: 12pt; margin-top: 1rem; margin-bottom: 0.5rem; }
        p, li { font-size: 11pt; line-height: 1.4; margin-bottom: 0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 1rem; margin-bottom: 1rem; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background-color: #e2e8f0; font-weight: bold; }
        .section-break { page-break-before: always; }
    </style>
</head>
<body class="bg-gray-200">

    <div class="fixed top-4 right-4 no-print">
        <button onclick="window.print()" class="px-6 py-3 bg-sky-500 text-white font-semibold rounded-lg shadow-lg hover:bg-sky-600 transition-transform transform hover:scale-105">
            <i class="fas fa-print mr-2"></i>Print or Save as PDF
        </button>
    </div>

    <div class="report-container">
        <div class="text-center">
            <h1>Payment Card Industry (PCI) Data Security Standard (DSS)</h1>
            <h2>Gap Assessment & Remediation Report</h2>
            <p class="text-lg">Project: <strong>{{ $project->name }}</strong></p>
        </div>

        <!-- Compliance metrics -->
        <h2 class="mt-12">1. Finding Compliance Status Summary</h2>
        <div class="grid grid-cols-2 gap-6 my-6">
            <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-lg">
                <span class="text-xs uppercase tracking-wider text-slate-400 font-bold block">Overall Finding Compliance</span>
                <span class="text-3xl font-extrabold text-indigo-700 mt-2 block">{{ $complianceMetrics['compliance_percentage'] }}%</span>
            </div>
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg">
                <span class="text-xs uppercase tracking-wider text-slate-400 font-bold block">Total Controls Tracked</span>
                <span class="text-3xl font-extrabold text-slate-700 mt-2 block">{{ $complianceMetrics['total_requirements'] }}</span>
            </div>
        </div>

        <table class="my-6">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Total Controls</th>
                    <th>Pending (Gaps)</th>
                    <th>Done (Compliant)</th>
                    <th>Finding Compliance %</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $dept)
                    @php
                        $deptControls = $dept->gapControls;
                        $total = $deptControls->count();
                        $done = $deptControls->where('status', 'Done')->count();
                        $pending = $total - $done;
                        $pct = $total > 0 ? round(($done / $total) * 100) : 0;
                    @endphp
                    <tr>
                        <td><strong>{{ $dept->name }}</strong></td>
                        <td class="text-center">{{ $total }}</td>
                        <td class="text-center text-rose-600 font-bold">{{ $pending }}</td>
                        <td class="text-center text-emerald-600">{{ $done }}</td>
                        <td class="text-center"><strong>{{ $pct }}%</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center italic">No department data found. Please run an Excel import.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Outstanding Gaps Section -->
        <h2 class="mt-12 section-break">2. Detailed Gap Analysis (Pending Controls)</h2>
        <p class="mb-4">The following requirements have been identified as outstanding gaps. Immediate remediation is required to satisfy compliance requirements.</p>

        @forelse($departments as $dept)
            @php
                $gaps = $dept->gapControls->where('status', 'Pending');
            @endphp
            @if($gaps->isNotEmpty())
                <h3 class="mt-6 font-bold text-slate-800 border-b border-slate-200 pb-1">{{ $dept->name }} Gaps</h3>
                <table>
                    <thead>
                        <tr class="bg-rose-50/50">
                            <th class="w-24">Control ID</th>
                            <th>Requirement Description</th>
                            <th>Required Evidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gaps as $gap)
                            <tr>
                                <td><span class="font-bold text-rose-600">{{ $gap->control_id }}</span></td>
                                <td>{{ $gap->requirement_description }}</td>
                                <td class="italic text-slate-500">{{ $gap->required_evidence ?: 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @empty
            <p class="italic">No gaps found.</p>
        @endforelse

        <!-- Compliant Controls Section -->
        <h2 class="mt-12 section-break">3. Documented Controls & Evidence (Done Controls)</h2>
        <p class="mb-4">The following controls have been successfully implemented and validated with appropriate evidence.</p>

        @forelse($departments as $dept)
            @php
                $compliant = $dept->gapControls->where('status', 'Done');
            @endphp
            @if($compliant->isNotEmpty())
                <h3 class="mt-6 font-bold text-slate-800 border-b border-slate-200 pb-1">{{ $dept->name }} Compliant</h3>
                <table>
                    <thead>
                        <tr class="bg-emerald-50/50">
                            <th class="w-24">Control ID</th>
                            <th>Requirement Description</th>
                            <th>Linked Evidence Files</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($compliant as $control)
                            <tr>
                                <td><span class="font-bold text-emerald-600">{{ $control->control_id }}</span></td>
                                <td>{{ $control->requirement_description }}</td>
                                <td>
                                    @forelse($control->evidenceFiles as $file)
                                        <div class="text-xs text-slate-700">
                                            <i class="far fa-file-alt mr-1"></i> {{ $file->original_filename }}
                                        </div>
                                    @empty
                                        <span class="text-xs italic text-slate-400">No file attached (Status set to Done)</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @empty
            <p class="italic">No compliant controls found.</p>
        @endforelse

    </div>

</body>
</html>
