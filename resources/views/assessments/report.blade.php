<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $type === 'gap' ? 'Gap' : 'Final' }} Assessment Report &ndash; {{ $project->name }}</title>
    <style>
        /* ------------------------------------------------------------------ */
        /* Reset & Base                                                        */
        /* ------------------------------------------------------------------ */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
            color: #1e293b;
            line-height: 1.55;
            background: #ffffff;
        }
        .page { padding: 36px 48px; }

        /* ------------------------------------------------------------------ */
        /* Cover / Header                                                      */
        /* ------------------------------------------------------------------ */
        .report-header {
            text-align: center;
            padding: 36px 0 28px;
            border-bottom: 3px solid #1e3a5f;
            margin-bottom: 28px;
        }
        .report-header .org-label {
            font-size: 9pt;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #64748b;
        }
        .report-header h1 {
            font-size: 20pt;
            font-weight: 800;
            color: #1e3a5f;
            margin: 10px 0 6px;
            line-height: 1.2;
        }
        .report-header .subtitle {
            font-size: 10.5pt;
            color: #475569;
        }
        .report-header .meta {
            margin-top: 14px;
            font-size: 8.5pt;
            color: #94a3b8;
        }
        .report-header .meta strong { color: #475569; }

        /* ------------------------------------------------------------------ */
        /* Meta Info Table                                                     */
        /* ------------------------------------------------------------------ */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9pt;
        }
        .meta-table td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
        }
        .meta-table td.label {
            width: 30%;
            font-weight: 600;
            background-color: #f8fafc;
            color: #374151;
        }

        /* ------------------------------------------------------------------ */
        /* Section Headings                                                    */
        /* ------------------------------------------------------------------ */
        .section-title {
            font-size: 12.5pt;
            font-weight: 700;
            color: #1e3a5f;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 5px;
            margin: 26px 0 12px;
        }
        .sub-title {
            font-size: 10.5pt;
            font-weight: 600;
            color: #334155;
            margin: 14px 0 6px;
        }
        p { margin-bottom: 7px; font-size: 9.5pt; color: #374151; }

        /* ------------------------------------------------------------------ */
        /* Tables                                                              */
        /* ------------------------------------------------------------------ */
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 9pt; }
        thead th {
            background-color: #1e3a5f;
            color: #ffffff;
            padding: 7px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 8.5pt;
            letter-spacing: 0.3px;
        }
        tbody td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        tbody tr:nth-child(even) td { background-color: #f8fafc; }

        /* ------------------------------------------------------------------ */
        /* Stat Summary Boxes                                                  */
        /* ------------------------------------------------------------------ */
        .stat-grid {
            display: table;
            width: 100%;
            margin-bottom: 16px;
        }
        .stat-box {
            display: table-cell;
            width: 25%;
            padding: 12px 10px;
            text-align: center;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .stat-box .stat-value {
            font-size: 22pt;
            font-weight: 800;
            color: #1e3a5f;
            display: block;
        }
        .stat-box .stat-label {
            font-size: 7.5pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ------------------------------------------------------------------ */
        /* Badges                                                              */
        /* ------------------------------------------------------------------ */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 3px;
            font-size: 7.5pt;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .badge-high        { background: #fee2e2; color: #991b1b; }
        .badge-medium      { background: #fef3c7; color: #92400e; }
        .badge-low         { background: #d1fae5; color: #065f46; }
        .badge-none        { background: #f1f5f9; color: #475569; }
        .badge-compliant   { background: #d1fae5; color: #065f46; }
        .badge-partial     { background: #fef3c7; color: #92400e; }
        .badge-noncompliant{ background: #fee2e2; color: #991b1b; }
        .badge-na          { background: #f1f5f9; color: #475569; }
        .badge-open        { background: #fee2e2; color: #991b1b; }
        .badge-progress    { background: #dbeafe; color: #1e40af; }
        .badge-closed      { background: #d1fae5; color: #065f46; }

        /* ------------------------------------------------------------------ */
        /* Finding Cards                                                       */
        /* ------------------------------------------------------------------ */
        .finding-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        .finding-card-header {
            background-color: #1e3a5f;
            color: #ffffff;
            padding: 8px 12px;
            border-radius: 3px 3px 0 0;
        }
        .finding-card-header .fc-serial {
            font-size: 8pt;
            opacity: 0.75;
            margin-bottom: 2px;
        }
        .finding-card-header .fc-title {
            font-size: 10.5pt;
            font-weight: 700;
        }
        .finding-card-body table { margin: 0; }
        .finding-card-body td { border: 1px solid #e2e8f0; }
        .finding-card-body td.label-cell {
            width: 28%;
            font-weight: 600;
            font-size: 8.5pt;
            color: #374151;
            background-color: #f8fafc;
        }

        /* ------------------------------------------------------------------ */
        /* Progress Bar                                                        */
        /* ------------------------------------------------------------------ */
        .progress-bar-wrap {
            background: #e2e8f0;
            border-radius: 4px;
            height: 10px;
            width: 100%;
            margin: 6px 0 12px;
        }
        .progress-bar-fill {
            background: #4f46e5;
            border-radius: 4px;
            height: 10px;
        }

        /* ------------------------------------------------------------------ */
        /* Page Utilities                                                      */
        /* ------------------------------------------------------------------ */
        .page-break { page-break-after: always; }
        .no-break   { page-break-inside: avoid; }

        /* ------------------------------------------------------------------ */
        /* Footer                                                              */
        /* ------------------------------------------------------------------ */
        .report-footer {
            margin-top: 36px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 7.5pt;
            color: #94a3b8;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ================================================================== --}}
    {{-- COVER / HEADER                                                      --}}
    {{-- ================================================================== --}}
    <div class="report-header">
        <div class="org-label">Compliance Hub &mdash; Confidential</div>
        <h1>
            {{ $assessment->framework === 'iso_27001' ? 'ISO 27001:2022' : 'HITRUST CSF' }}
            {{ $type === 'gap' ? 'Gap' : 'Final' }} Assessment Report
        </h1>
        <div class="subtitle">Information Security Management System (ISMS)</div>
        <div class="meta">
            Project: <strong>{{ $project->name }}</strong>
            &nbsp;&bull;&nbsp;
            Generated: <strong>{{ now()->format('d F Y') }}</strong>
            &nbsp;&bull;&nbsp;
            Classification: <strong>Confidential</strong>
        </div>
    </div>

    {{-- Assessment Meta --}}
    <table class="meta-table">
        <tr>
            <td class="label">Assessment Type</td>
            <td>{{ $type === 'gap' ? 'Gap Assessment' : 'Final Assessment' }}</td>
            <td class="label">Framework</td>
            <td>{{ $assessment->framework === 'iso_27001' ? 'ISO 27001:2022' : 'HITRUST CSF' }}</td>
        </tr>
        <tr>
            <td class="label">Project</td>
            <td>{{ $project->name }}</td>
            <td class="label">Assessment Period</td>
            <td>{{ $assessment->start_date->format('d M Y') }} &ndash; {{ $assessment->end_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Report Date</td>
            <td>{{ now()->format('d F Y') }}</td>
            <td class="label">Classification</td>
            <td>Confidential</td>
        </tr>
    </table>

    {{-- ================================================================== --}}
    {{-- 1. EXECUTIVE SUMMARY                                                --}}
    {{-- ================================================================== --}}
    <div class="section-title">1. Executive Summary</div>

    <div class="sub-title">1.1 Introduction</div>
    <p>
        This report presents the findings of a
        {{ $assessment->framework === 'iso_27001' ? 'ISO 27001:2022' : 'HITRUST CSF' }}
        {{ $type === 'gap' ? 'Gap' : 'Final' }} Assessment conducted for
        <strong>{{ $project->name }}</strong>. The assessment was performed to evaluate the
        organisation's current information security posture against the requirements of the
        {{ $assessment->framework === 'iso_27001' ? 'ISO/IEC 27001:2022 standard' : 'HITRUST Common Security Framework (CSF)' }}
        and to identify areas requiring remediation.
    </p>

    <div class="sub-title">1.2 Purpose</div>
    <p>
        The purpose of this {{ $type === 'gap' ? 'Gap' : 'Final' }} Assessment is to identify and document
        {{ $type === 'gap' ? 'gaps between the organisation\'s existing controls and the framework requirements' : 'the current compliance posture and remediation progress against identified gaps' }}.
        The findings and recommendations contained herein are intended to guide the organisation
        in developing a prioritised remediation roadmap.
    </p>

    <div class="sub-title">1.3 Limitations</div>
    <p>
        This assessment is based on information provided by the organisation's personnel
        through interviews, document reviews, and process walkthroughs. The findings reflect
        the state of the ISMS at the time of assessment and may not account for changes
        implemented after the assessment date. This report does not constitute a formal
        certification audit.
    </p>

    <div class="page-break"></div>

    {{-- ================================================================== --}}
    {{-- 2. SUMMARY OF FINDINGS                                              --}}
    {{-- ================================================================== --}}
    <div class="section-title">2. Summary of Findings</div>

    {{-- Stat boxes --}}
    <div class="stat-grid">
        <div class="stat-box">
            <span class="stat-value">{{ $stats['total'] }}</span>
            <span class="stat-label">Total Findings</span>
        </div>
        <div class="stat-box">
            <span class="stat-value" style="color:#065f46;">{{ $stats['compliancePct'] }}%</span>
            <span class="stat-label">Compliance Score</span>
        </div>
        <div class="stat-box">
            <span class="stat-value" style="color:#991b1b;">{{ $stats['high'] }}</span>
            <span class="stat-label">High Risk</span>
        </div>
        <div class="stat-box">
            <span class="stat-value" style="color:#92400e;">{{ $stats['open'] }}</span>
            <span class="stat-label">Open Findings</span>
        </div>
    </div>

    {{-- Compliance progress bar --}}
    <p style="font-size:8.5pt; color:#475569; margin-bottom:4px;">Overall Compliance Score: <strong>{{ $stats['compliancePct'] }}%</strong></p>
    <div class="progress-bar-wrap">
        <div class="progress-bar-fill" style="width:{{ $stats['compliancePct'] }}%;"></div>
    </div>

    {{-- Breakdown table --}}
    <table>
        <thead>
            <tr>
                <th style="width:25%">Category</th>
                <th style="width:25%">Count</th>
                <th style="width:25%">Category</th>
                <th style="width:25%">Count</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="badge badge-compliant">Compliant</span></td>
                <td>{{ $stats['compliant'] }}</td>
                <td><span class="badge badge-high">High Risk</span></td>
                <td>{{ $stats['high'] }}</td>
            </tr>
            <tr>
                <td><span class="badge badge-partial">Partially Compliant</span></td>
                <td>{{ $stats['partial'] }}</td>
                <td><span class="badge badge-medium">Medium Risk</span></td>
                <td>{{ $stats['medium'] }}</td>
            </tr>
            <tr>
                <td><span class="badge badge-noncompliant">Non-Compliant</span></td>
                <td>{{ $stats['nonCompliant'] }}</td>
                <td><span class="badge badge-low">Low Risk</span></td>
                <td>{{ $stats['low'] }}</td>
            </tr>
            <tr>
                <td><span class="badge badge-na">Not Applicable</span></td>
                <td>{{ $stats['na'] }}</td>
                <td><span class="badge badge-open">Open</span> / <span class="badge badge-progress">In Progress</span> / <span class="badge badge-closed">Closed</span></td>
                <td>{{ $stats['open'] }} / {{ $stats['inProgress'] }} / {{ $stats['closed'] }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ================================================================== --}}
    {{-- 3. KEY CRITICAL FINDINGS (HIGH RISK)                                --}}
    {{-- ================================================================== --}}
    <div class="section-title">3. Key Critical Findings (High Risk)</div>

    @if($highFindings->isEmpty())
        <p>No High risk findings were identified during this assessment.</p>
    @else
        <p>
            The following <strong>{{ $highFindings->count() }}</strong> High risk finding(s)
            require immediate management attention and prioritised remediation:
        </p>
        <table>
            <thead>
                <tr>
                    <th style="width:9%">Serial</th>
                    <th style="width:22%">Clause Reference</th>
                    <th style="width:30%">Observation Title</th>
                    <th style="width:20%">Compliance Status</th>
                    <th style="width:19%">Recommendation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($highFindings as $f)
                <tr>
                    <td>{{ $f->serial_no }}</td>
                    <td>{{ $f->clause_reference }}</td>
                    <td>{{ $f->observation_title }}</td>
                    <td>
                        @if($f->compliance_status === 'Compliant')
                            <span class="badge badge-compliant">Compliant</span>
                        @elseif($f->compliance_status === 'Partially Compliant')
                            <span class="badge badge-partial">Partial</span>
                        @elseif($f->compliance_status === 'Non-Compliant')
                            <span class="badge badge-noncompliant">Non-Compliant</span>
                        @else
                            <span class="badge badge-na">N/A</span>
                        @endif
                    </td>
                    <td>{{ $f->recommendation }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="page-break"></div>

    {{-- ================================================================== --}}
    {{-- 4. DETAILED FINDINGS AND RECOMMENDATIONS                            --}}
    {{-- ================================================================== --}}
    <div class="section-title">4. Detailed Findings and Recommendations</div>
    <p>
        The following section provides a comprehensive breakdown of all
        <strong>{{ $stats['total'] }}</strong> findings identified during the assessment.
    </p>

    @foreach($assessment->findings as $finding)
    <div class="finding-card no-break">
        <div class="finding-card-header">
            <div class="fc-serial">{{ $finding->serial_no }} &mdash; {{ $finding->clause_reference }}</div>
            <div class="fc-title">{{ $finding->observation_title }}</div>
        </div>
        <div class="finding-card-body">
            <table>
                <tr>
                    <td class="label-cell">Compliance Status</td>
                    <td>
                        @if($finding->compliance_status === 'Compliant')
                            <span class="badge badge-compliant">Compliant</span>
                        @elseif($finding->compliance_status === 'Partially Compliant')
                            <span class="badge badge-partial">Partially Compliant</span>
                        @elseif($finding->compliance_status === 'Non-Compliant')
                            <span class="badge badge-noncompliant">Non-Compliant</span>
                        @else
                            <span class="badge badge-na">Not Applicable</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label-cell">Risk Rating</td>
                    <td>
                        @if($finding->risk_rating === 'High')
                            <span class="badge badge-high">High</span>
                        @elseif($finding->risk_rating === 'Medium')
                            <span class="badge badge-medium">Medium</span>
                        @elseif($finding->risk_rating === 'Low')
                            <span class="badge badge-low">Low</span>
                        @else
                            <span class="badge badge-none">None</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label-cell">Status</td>
                    <td>
                        @if($finding->status === 'Open')
                            <span class="badge badge-open">Open</span>
                        @elseif($finding->status === 'In Progress')
                            <span class="badge badge-progress">In Progress</span>
                        @else
                            <span class="badge badge-closed">Closed</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label-cell">Current State / Observation</td>
                    <td>{{ $finding->current_state ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Gap Description</td>
                    <td>{{ $finding->gap_description ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Impact / Risk</td>
                    <td>{{ $finding->impact_risk ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Recommendation</td>
                    <td>{{ $finding->recommendation ?: '—' }}</td>
                </tr>
            </table>
        </div>
    </div>
    @endforeach

    {{-- ================================================================== --}}
    {{-- FOOTER                                                              --}}
    {{-- ================================================================== --}}
    <div class="report-footer">
        {{ $assessment->framework === 'iso_27001' ? 'ISO 27001:2022' : 'HITRUST CSF' }}
        {{ $type === 'gap' ? 'Gap' : 'Final' }} Assessment Report &mdash;
        {{ $project->name }} &mdash;
        Generated {{ now()->format('d F Y') }} &mdash; Confidential
    </div>

</div>
</body>
</html>
