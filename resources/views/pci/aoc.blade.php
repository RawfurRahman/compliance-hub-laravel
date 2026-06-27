<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCI DSS Attestation of Compliance - {{ $project->name }}</title>
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
        .header-table td { border: none; padding: 2px 0; font-size: 11pt; }
        .header-table .label { font-weight: bold; width: 30%; }
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
            <h2>Attestation of Compliance (AOC)</h2>
            <p class="text-lg font-bold">Version 4.0.1</p>
        </div>

        <div class="mt-12 p-4 bg-slate-50 border border-slate-200 rounded-lg">
            <h3 class="mt-0">Validation Status Summary</h3>
            <p>Based on the results documented in the Report on Compliance (ROC) dated <strong>{{ optional($project->pciDssDetails)->date_of_report ?? 'N/A' }}</strong>, the assessed entity is attested as:</p>
            <p class="mt-4 text-center">
                @if($complianceMetrics['is_compliant'])
                    <span class="px-6 py-2 text-lg font-bold bg-emerald-100 text-emerald-800 rounded-full border border-emerald-300">
                        <i class="fas fa-check-circle mr-2"></i> COMPLIANT
                    </span>
                @else
                    <span class="px-6 py-2 text-lg font-bold bg-rose-100 text-rose-800 rounded-full border border-rose-300">
                        <i class="fas fa-exclamation-triangle mr-2"></i> NON-COMPLIANT
                    </span>
                @endif
            </p>
        </div>

        <!-- Section 1: Contact Info -->
        <h2 class="mt-12">Section 1: Assessed Entity & Assessor Details</h2>
        <table class="header-table">
            <tr><td colspan="2" class="font-bold text-md border-b border-slate-200 pb-1">Assessed Entity Info</td></tr>
            <tr><td class="label">Company Name:</td><td>{{ optional($project->pciDssDetails)->ae_company_name ?? 'N/A' }}</td></tr>
            <tr><td class="label">DBA:</td><td>{{ optional($project->pciDssDetails)->ae_dba ?? 'N/A' }}</td></tr>
            <tr><td class="label">Mailing Address:</td><td>{{ optional($project->pciDssDetails)->ae_mailing_address ?? 'N/A' }}</td></tr>
            <tr><td class="label">Contact Person:</td><td>{{ optional($project->pciDssDetails)->ae_contact_name ?? 'N/A' }}</td></tr>
            <tr><td colspan="2" class="font-bold text-md border-b border-slate-200 pt-4 pb-1">Assessor Info</td></tr>
            <tr><td class="label">Assessor Company:</td><td>{{ optional($project->pciDssDetails)->assessor_company_name ?? 'N/A' }}</td></tr>
            <tr><td class="label">Lead Assessor:</td><td>{{ optional($project->pciDssDetails)->assessor_lead_name ?? 'N/A' }}</td></tr>
        </table>

        <!-- Section 2: Business Overview -->
        <h2 class="mt-12 section-break">Section 2: Scope of Assessment</h2>
        <h4>Description of Payment Business:</h4>
        <p>{{ optional($project->pciDssDetails)->business_overview_desc ?? 'N/A' }}</p>

        <h4 class="mt-6">Utilized Channels:</h4>
        <ul>
            @forelse (optional($project->pciDssDetails)->payment_channels ?? [] as $channel)
                <li><i class="fas fa-caret-right mr-2 text-sky-500"></i>{{ $channel }}</li>
            @empty
                <li>No payment channels specified.</li>
            @endforelse
        </ul>

        <!-- Section 3: Summary of Findings -->
        <h2 class="mt-12">Section 3: Finding Compliance Metrics Summary</h2>
        <table>
            <thead>
                <tr>
                    <th>Assessment Dimension</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Requirements Assessed</td>
                    <td>{{ $complianceMetrics['total_requirements'] }}</td>
                    <td>100%</td>
                </tr>
                <tr>
                    <td>Requirements: In Place</td>
                    <td>{{ $complianceMetrics['passed'] }}</td>
                    <td>{{ $complianceMetrics['total_requirements'] > 0 ? round(($complianceMetrics['passed'] / $complianceMetrics['total_requirements']) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td>Requirements: Not Applicable</td>
                    <td>{{ $complianceMetrics['not_applicable'] }}</td>
                    <td>{{ $complianceMetrics['total_requirements'] > 0 ? round(($complianceMetrics['not_applicable'] / $complianceMetrics['total_requirements']) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td>Requirements: Not Tested</td>
                    <td>{{ $complianceMetrics['not_tested'] }}</td>
                    <td>{{ $complianceMetrics['total_requirements'] > 0 ? round(($complianceMetrics['not_tested'] / $complianceMetrics['total_requirements']) * 100, 1) : 0 }}%</td>
                </tr>
                <tr :class="{'bg-rose-50': {{ $complianceMetrics['failed'] }} > 0}">
                    <td><strong>Requirements: Not in Place</strong></td>
                    <td><strong>{{ $complianceMetrics['failed'] }}</strong></td>
                    <td><strong>{{ $complianceMetrics['total_requirements'] > 0 ? round(($complianceMetrics['failed'] / $complianceMetrics['total_requirements']) * 100, 1) : 0 }}%</strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Section 4: Attestation signatures -->
        <h2 class="mt-12 section-break">Section 4: Attestation Signatures</h2>
        <p>I attest that the validation status of the assessed entity is based on a rigorous assessment of all applicable requirements detailed in the Report on Compliance (ROC).</p>

        <table class="mt-12" style="border: none;">
            <tr style="border: none;">
                <td style="border: none; width: 50%; vertical-align: bottom;">
                    <div style="border-top: 1px solid black; height: 80px; margin-top: 40px;">
                        <p style="margin-top: 4px;"><strong>Qualified Security Assessor Signature</strong></p>
                    </div>
                </td>
                <td style="border: none; width: 50%; text-align: right; vertical-align: bottom;">
                    <div style="border-top: 1px solid black; height: 80px; margin-top: 40px;">
                        <p style="margin-top: 4px;"><strong>Date</strong></p>
                    </div>
                </td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;">
                    {{ optional($project->pciDssDetails)->assessor_lead_name ?? '_______________________' }}
                </td>
                <td style="border: none; text-align: right;">
                    {{ optional($project->pciDssDetails)->date_of_report ?? '_______________________' }}
                </td>
            </tr>
        </table>

        <table class="mt-12" style="border: none;">
            <tr style="border: none;">
                <td style="border: none; width: 50%; vertical-align: bottom;">
                    <div style="border-top: 1px solid black; height: 80px; margin-top: 40px;">
                        <p style="margin-top: 4px;"><strong>Entity Representative Acceptance Signature</strong></p>
                    </div>
                </td>
                <td style="border: none; width: 50%; text-align: right; vertical-align: bottom;">
                    <div style="border-top: 1px solid black; height: 80px; margin-top: 40px;">
                        <p style="margin-top: 4px;"><strong>Date</strong></p>
                    </div>
                </td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;">
                    {{ optional($project->pciDssDetails)->ae_contact_name ?? '_______________________' }}
                </td>
                <td style="border: none; text-align: right;">
                    _______________________
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
