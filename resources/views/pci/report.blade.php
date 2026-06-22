<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCI DSS Report on Compliance - {{ $project->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none; }
            @page {
                size: A4;
                margin: 1.5cm;
            }
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            font-size: 11pt;
        }
        .report-container {
            max-width: 8.5in;
            margin: auto;
            padding: 1in;
            background: white;
        }
        h1, h2, h3, h4 {
            font-weight: bold;
            color: #000;
        }
        h1 { font-size: 22pt; margin-bottom: 0.5rem; }
        h2 { font-size: 16pt; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid #ccc; padding-bottom: 0.5rem; }
        h3 { font-size: 14pt; margin-top: 1.5rem; margin-bottom: 0.75rem; }
        h4 { font-size: 12pt; margin-top: 1rem; margin-bottom: 0.5rem; }
        p, li { font-size: 11pt; line-height: 1.4; margin-bottom: 0.5rem; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        th, td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #e2e8f0;
            font-weight: bold;
        }
        .header-table td {
            border: none;
            padding: 2px 0;
            font-size: 11pt;
        }
        .header-table .label {
            font-weight: bold;
            width: 30%;
        }
        .finding-table th, .finding-table td {
            text-align: center;
        }
        .finding-table .req-desc {
            text-align: left;
        }
        .section-break {
            page-break-before: always;
        }
    </style>
</head>
<body class="bg-gray-200">

    <div class="fixed top-4 right-4 no-print">
        <button onclick="window.print()" class="px-6 py-3 bg-sky-500 text-white font-semibold rounded-lg shadow-lg hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-opacity-50 transition-transform transform hover:scale-105">
            <i class="fas fa-print mr-2"></i>Print or Save as PDF
        </button>
    </div>

    <div class="report-container">
        <div class="text-center">
            <h1>Payment Card Industry (PCI) Data Security Standard (DSS)</h1>
            <h2>Report on Compliance</h2>
            <p class="text-lg">Version 4.0.1</p>
        </div>

        <div class="mt-16">
            <p><strong>Entity Name:</strong> {{ optional($project->pciDssDetails)->ae_company_name ?? 'N/A' }}</p>
            <p><strong>Date of Report:</strong> {{ optional($project->pciDssDetails)->date_of_report ?? 'N/A' }}</p>
            <p><strong>Date Assessment Ended:</strong> {{ optional($project->pciDssDetails)->date_assessment_ended ?? 'N/A' }}</p>
        </div>

        @if(!isset($sections) || in_array('executive_summary', $sections))
        <!-- Part I: Assessment Overview -->
        <h2 class="mt-16 section-break">Part I: Assessment Overview</h2>

        <!-- 1. Contact Information -->
        <h3>1. Contact Information and Summary of Results</h3>
        <h4>1.1 Contact Information</h4>
        <table class="header-table">
            <tr><td colspan="2" class="font-bold text-lg">Assessed Entity</td></tr>
            <tr><td class="label">Company name:</td><td>{{ optional($project->pciDssDetails)->ae_company_name ?? 'N/A' }}</td></tr>
            <tr><td class="label">DBA (doing business as):</td><td>{{ optional($project->pciDssDetails)->ae_dba ?? 'N/A' }}</td></tr>
            <tr><td class="label">Mailing address:</td><td>{{ optional($project->pciDssDetails)->ae_mailing_address ?? 'N/A' }}</td></tr>
            <tr><td class="label">Contact name:</td><td>{{ optional($project->pciDssDetails)->ae_contact_name ?? 'N/A' }}</td></tr>
            <tr><td colspan="2" class="font-bold text-lg pt-4">Qualified Security Assessor Company</td></tr>
            <tr><td class="label">Company name:</td><td>{{ optional($project->pciDssDetails)->assessor_company_name ?? 'N/A' }}</td></tr>
            <tr><td class="label">Lead Assessor name:</td><td>{{ optional($project->pciDssDetails)->assessor_lead_name ?? 'N/A' }}</td></tr>
        </table>
        
        <!-- ** NEW SECTION ** -->
        <h3 class="mt-12">1.7 Overall Assessment Result</h3>
        <p><strong>Overall Assessment Result:</strong> {{ optional($project->pciDssDetails)->overall_assessment_result ?? 'Not Determined' }}</p>

        <!-- 2. Business Overview -->
        <h2 class="mt-16">2. Business Overview</h2>
        <h4>2.1 Description of the Entity's Payment Card Business</h4>
        <p>{{ optional($project->pciDssDetails)->business_overview_desc ?? 'N/A' }}</p>
        <h4 class="mt-4">Payment Channels Utilized:</h4>
        <ul>
            @forelse (optional($project->pciDssDetails)->payment_channels ?? [] as $channel)
                <li>{{ $channel }}</li>
            @empty
                <li>No payment channels specified.</li>
            @endforelse
        </ul>
        @endif

        @if(!isset($sections) || in_array('metrics', $sections))
        <!-- ** NEW SECTION ** -->
        <h2 class="mt-16 section-break">3. Description of Scope of Work and Approach Taken</h2>
        <h4>3.1 Assessor's Validation of Defined Scope Accuracy</h4>
        <p>{{ optional($project->pciDssDetails)->scope_validation_activities ?? 'N/A' }}</p>
        <h4 class="mt-4">Excluded Areas:</h4>
        <p>{{ optional($project->pciDssDetails)->scope_excluded_areas ?? 'N/A' }}</p>
        <h4 class="mt-4">Scope Reduction Factors:</h4>
        <p>{{ optional($project->pciDssDetails)->scope_reduction_factors ?? 'N/A' }}</p>
        <h4 class="mt-4">SAQ Eligibility Considerations:</h4>
        <p>{{ optional($project->pciDssDetails)->saq_eligibility ?? 'N/A' }}</p>

        <!-- ** NEW SECTION ** -->
        <h2 class="mt-16 section-break">4. Details About Reviewed Environments</h2>
        <h4>4.1 Segmentation</h4>
        <p><strong>Is segmentation used?</strong> {{ optional($project->pciDssDetails)->segmentation_used ? 'Yes' : 'No' }}</p>
        @if(optional($project->pciDssDetails)->segmentation_used)
            <p><strong>Description of Segmentation:</strong></p>
            <p>{{ optional($project->pciDssDetails)->segmentation_desc ?? 'N/A' }}</p>
        @endif
        
        <h4 class="mt-8">4.2 PCI SSC Validated Products and Solutions</h4>
        <p><strong>Are PCI SSC-validated products or solutions used?</strong> {{ optional($project->pciDssDetails)->pci_ssc_products_used ? 'Yes' : 'No' }}</p>
        @if(optional($project->pciDssDetails)->pci_ssc_products_used && optional($project->pciDssDetails)->pciSscProducts->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Version</th>
                    <th>Vendor</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach(optional($project->pciDssDetails)->pciSscProducts as $product)
                <tr>
                    <td>{{ $product->product_name }}</td>
                    <td>{{ $product->version }}</td>
                    <td>{{ $product->vendor }}</td>
                    <td>{{ $product->description }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        
        <!-- ** NEW SECTION ** -->
        <h2 class="mt-16 section-break">5. Quarterly Scan Results</h2>
        <h4>5.1 Quarterly External Scan Results</h4>
        @if(optional($project->pciDssDetails)->externalScans->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Date of Scan(s)</th>
                    <th>Result</th>
                    <th>Initial Assessment</th>
                </tr>
            </thead>
            <tbody>
                @foreach(optional($project->pciDssDetails)->externalScans as $scan)
                <tr>
                    <td>{{ $scan->scan_date }}</td>
                    <td>{{ $scan->result }}</td>
                    <td>{{ $scan->initial_assessment ? 'Yes' : 'No' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No external scan results recorded.</p>
        @endif

        <h4 class="mt-8">5.3 Quarterly Internal Scan Results</h4>
        @if(optional($project->pciDssDetails)->internalScans->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Date of Scan(s)</th>
                    <th>Result</th>
                    <th>Initial Assessment</th>
                </tr>
            </thead>
            <tbody>
                @foreach(optional($project->pciDssDetails)->internalScans as $scan)
                <tr>
                    <td>{{ $scan->scan_date }}</td>
                    <td>{{ $scan->result }}</td>
                    <td>{{ $scan->initial_assessment ? 'Yes' : 'No' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No internal scan results recorded.</p>
        @endif
        @endif

        @if(!isset($sections) || in_array('detailed_findings', $sections))
        <!-- Part II: Findings and Observations -->
        <h2 class="mt-16 section-break">Part II: Findings and Observations</h2>
        <h3>7. Findings and Observations</h3>
        
        @foreach($requirements as $req)
            @php
                $currentFinding = $findings->get($req->id);
            @endphp
            <div class="mt-8" style="page-break-inside: avoid;">
                <h4>Requirement {{ $req->req_num }}: {{ $req->req_description }}</h4>
                
                <table class="finding-table">
                    <thead>
                        <tr>
                            <th colspan="4">Assessment Findings</th>
                            <th colspan="2">Method(s) Used</th>
                        </tr>
                        <tr>
                            <th>In Place</th>
                            <th>Not Applicable</th>
                            <th>Not Tested</th>
                            <th>Not in Place</th>
                            <th>Compensating Control</th>
                            <th>Customized Approach</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ optional($currentFinding)->assessment_finding == 'In Place' ? 'X' : '' }}</td>
                            <td>{{ optional($currentFinding)->assessment_finding == 'Not Applicable' ? 'X' : '' }}</td>
                            <td>{{ optional($currentFinding)->assessment_finding == 'Not Tested' ? 'X' : '' }}</td>
                            <td>{{ optional($currentFinding)->assessment_finding == 'Not in Place' ? 'X' : '' }}</td>
                            <td>{{ optional($currentFinding)->compensating_control ? 'X' : '' }}</td>
                            <td>{{ optional($currentFinding)->customized_approach ? 'X' : '' }}</td>
                        </tr>
                    </tbody>
                </table>

                <p class="mt-4"><strong>Finding Description:</strong></p>
                <p>{{ optional($currentFinding)->finding_description ?? 'N/A' }}</p>

                <table class="mt-4">
                    <thead>
                        <tr>
                            <th>Testing Procedures</th>
                            <th>Reporting Details: Assessor's Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($req->testing_procedures as $index => $tp)
                            <tr>
                                <td class="w-1/2 req-desc">{{ $tp['procedure'] }}</td>
                                <td class="w-1/2">{{ optional($currentFinding)->assessor_responses[$index] ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
        @endif
    </div>

</body>
</html>
