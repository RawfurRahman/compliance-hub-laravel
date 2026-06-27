<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $framework->name }} {{ $assessment->type }} Report &ndash; {{ $project->name }}</title>
    <style>
        /* Reset & Base styles optimized for DomPDF */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
            color: #1e293b;
            line-height: 1.5;
            background: #ffffff;
        }
        .page { padding: 40px 50px; }

        /* Report Header & Cover style */
        .report-header {
            text-align: center;
            padding: 40px 0 30px;
            border-bottom: 3px solid #0a1e42;
            margin-bottom: 30px;
        }
        .report-header .org-label {
            font-size: 8.5pt;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }
        .report-header h1 {
            font-size: 21pt;
            font-weight: bold;
            color: #0a1e42;
            margin: 12px 0 8px;
            line-height: 1.25;
        }
        .report-header .subtitle {
            font-size: 11pt;
            color: #475569;
            margin-bottom: 12px;
        }
        .report-header .meta {
            font-size: 8.5pt;
            color: #64748b;
        }
        .report-header .meta strong { color: #0a1e42; }

        /* Meta Information Table */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 9pt;
        }
        .meta-table td {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .meta-table td.label-cell {
            width: 25%;
            font-weight: bold;
            background-color: #0a1e42;
            color: #ffffff;
        }

        /* Headings */
        .section-title {
            font-size: 13pt;
            font-weight: bold;
            color: #0a1e42;
            border-bottom: 2px solid #0a1e42;
            padding-bottom: 6px;
            margin: 28px 0 14px;
        }
        .sub-title {
            font-size: 10.5pt;
            font-weight: bold;
            color: #334155;
            margin: 16px 0 8px;
        }
        p { margin-bottom: 8px; color: #334155; font-size: 9.5pt; text-align: justify; }

        /* Summary Table */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9pt;
        }
        .summary-table th {
            background-color: #0a1e42;
            color: #ffffff;
            padding: 8px 12px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #0a1e42;
        }
        .summary-table td {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .summary-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* Detailed Gaps Table */
        .finding-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            page-break-inside: avoid; /* Prevents awkward splits */
        }
        .finding-table td {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
            font-size: 9pt;
        }
        .finding-table td.label-cell {
            background-color: #0a1e42;
            color: #ffffff;
            font-weight: bold;
        }
        .finding-table td.value-cell {
            background-color: #ffffff;
            color: #1e293b;
        }

        /* Badges & Text styles */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-high        { background-color: #fee2e2; color: #991b1b; }
        .badge-medium      { background-color: #fef3c7; color: #92400e; }
        .badge-low         { background-color: #d1fae5; color: #065f46; }
        .badge-none        { background-color: #f1f5f9; color: #475569; }
        
        .badge-open        { background-color: #fee2e2; color: #991b1b; }
        .badge-progress    { background-color: #dbeafe; color: #1e40af; }
        .badge-closed      { background-color: #d1fae5; color: #065f46; }

        .text-high         { color: #dc2626; font-weight: bold; }
        .text-medium       { color: #ea580c; font-weight: bold; }
        .text-low          { color: #16a34a; font-weight: bold; }
        .text-none         { color: #64748b; font-weight: bold; }

        .page-break { page-break-after: always; }

        /* Rich Text overrides for PDF list layout */
        .rich-content ul, .rich-content ol {
            margin-left: 18px;
            margin-top: 4px;
            margin-bottom: 4px;
        }
        .rich-content li {
            margin-bottom: 2px;
        }

        .report-footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #cbd5e1;
            text-align: center;
            font-size: 7.5pt;
            color: #64748b;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ================================================================== --}}
    {{-- COVER / HEADER                                                     --}}
    {{-- ================================================================== --}}
    <div class="report-header">
        <div class="org-label">Compliance Hub &mdash; CONFIDENTIAL AUDIT REPORT</div>
        <h1>{{ $framework->name }} {{ $assessment->type }} Assessment Report</h1>
        <div class="subtitle">Information Security & Compliance Posture Report</div>
        <div class="meta">
            Project: <strong>{{ $project->name }}</strong>
            &nbsp;&bull;&nbsp;
            Generated: <strong>{{ now()->format('d F Y') }}</strong>
        </div>
    </div>

    {{-- Assessment Meta Table --}}
    <table class="meta-table">
        <tr>
            <td class="label-cell">Assessment Type</td>
            <td>{{ $assessment->type }} Assessment</td>
            <td class="label-cell">Framework</td>
            <td>{{ $framework->name }} {{ $framework->version }}</td>
        </tr>
        <tr>
            <td class="label-cell">Project Name</td>
            <td>{{ $project->name }}</td>
            <td class="label-cell">Assessment Period</td>
            <td>{{ $assessment->start_date->format('d M Y') }} &ndash; {{ $assessment->end_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label-cell">Report Status</td>
            <td>{{ $stats['open'] === 0 ? 'Closed (Compliant)' : 'Open' }}</td>
            <td class="label-cell">Classification</td>
            <td>Confidential</td>
        </tr>
    </table>

    @if(!isset($sections) || in_array('executive_summary', $sections))
    {{-- ================================================================== --}}
    {{-- 1. EXECUTIVE SUMMARY                                               --}}
    {{-- ================================================================== --}}
    <div class="section-title">1. Executive Summary</div>

    <div class="sub-title">1.1 Introduction</div>
    <p>
        This formal audit report details the findings from the {{ $framework->name }} {{ $assessment->type }} Assessment conducted for 
        <strong>{{ $project->name }}</strong>. The assessment was performed to measure the organization's adherence 
        to safety and privacy controls mandated by the {{ $framework->name }} framework.
    </p>

    <div class="sub-title">1.2 Scope & Purpose</div>
    <p>
        The scope covers all systems, platforms, and procedural checks assigned under the scope of this project. 
        The purpose of this review is to catalog critical gaps, impact matrices, standard references, and recommend 
        remediation pathways to reach a clean compliant state.
    </p>

    <div class="sub-title">1.3 Limitations</div>
    <p>
        Findings represent a snapshot of the controls in place during the audit window. Continuous monitoring is 
        strongly recommended to ensure operating effectiveness over time.
    </p>

    <div class="page-break"></div>
    @endif

    @if(!isset($sections) || in_array('metrics', $sections) || in_array('table', $sections))
    {{-- ================================================================== --}}
    {{-- 2. SUMMARY OF FINDINGS                                             --}}
    {{-- ================================================================== --}}
    <div class="section-title">2. Summary of Findings</div>

    @if(!isset($sections) || in_array('metrics', $sections))
    <table class="meta-table">
        <thead>
            <tr>
                <th colspan="4" style="background-color: #0a1e42; color: #ffffff; padding: 8px 12px; text-align: left; font-weight: bold;">
                    Postures & Metrics Breakdowns
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="label-cell">Finding Compliance</td>
                <td style="font-weight: bold; color: #16a34a; font-size: 11pt;">{{ $stats['compliancePct'] }}%</td>
                <td class="label-cell">Total Findings</td>
                <td>{{ $stats['total'] }}</td>
            </tr>
            <tr>
                <td class="label-cell">Compliant Gaps</td>
                <td>{{ $stats['compliant'] }}</td>
                <td class="label-cell">Non-Compliant Gaps</td>
                <td>{{ $stats['nonCompliant'] }}</td>
            </tr>
            <tr>
                <td class="label-cell">High Risk Gaps</td>
                <td style="color: #dc2626; font-weight: bold;">{{ $stats['high'] }}</td>
                <td class="label-cell">Medium Risk Gaps</td>
                <td style="color: #ea580c; font-weight: bold;">{{ $stats['medium'] }}</td>
            </tr>
            <tr>
                <td class="label-cell">Low Risk Gaps</td>
                <td style="color: #16a34a; font-weight: bold;">{{ $stats['low'] }}</td>
                <td class="label-cell">Audit Gaps status (Open/Closed/Progress)</td>
                <td>{{ $stats['open'] }} / {{ $stats['closed'] }} / {{ $stats['inProgress'] }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- Summary Table (Strict Requirement) --}}
    @if(!isset($sections) || in_array('table', $sections))
    <div class="sub-title">2.1 Table of Findings Mapping</div>
    <table class="summary-table">
        <thead>
            <tr>
                <th style="width: 8%;">S.N</th>
                <th style="width: 20%;">Ref: Clause</th>
                <th>Requirement Description</th>
                <th style="width: 18%; text-align: center;">Risk Rating</th>
                <th style="width: 18%; text-align: center;">Compliance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($findings as $index => $finding)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold;">{{ $finding->frameworkControl ? $finding->frameworkControl->control_id : 'N/A' }}</td>
                    <td>{{ $finding->frameworkControl ? Str::limit($finding->frameworkControl->requirement_description, 100) : 'N/A' }}</td>
                    <td style="text-align: center;">
                        @if($finding->risk_rating === 'High')
                            <span class="text-high">High</span>
                        @elseif($finding->risk_rating === 'Medium')
                            <span class="text-medium">Medium</span>
                        @elseif($finding->risk_rating === 'Low')
                            <span class="text-low">Low</span>
                        @else
                            <span class="text-none">None</span>
                        @endif
                    </td>
                    <td style="text-align: center; font-weight: bold; color: {{ $finding->is_compliant ? '#16a34a' : '#dc2626' }};">
                        {{ $finding->is_compliant ? 'Compliant' : 'Non-Compliant' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b;">No findings documented.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @endif

    <div class="page-break"></div>
    @endif

    @if(!isset($sections) || in_array('detailed_findings', $sections))
    {{-- ================================================================== --}}
    {{-- 3. DETAILED FINDINGS & OBSERVATIONS                                --}}
    {{-- ================================================================== --}}
    <div class="section-title">3. Detailed Audit Findings Report</div>

    @forelse($findings as $finding)
        <table class="finding-table">
            <tbody>
                <!-- Row 1 -->
                <tr>
                    <td class="w-[18%] label-cell">Control ID:</td>
                    <td class="w-[32%] value-cell" style="font-weight: bold; font-family: monospace;">{{ $finding->frameworkControl ? $finding->frameworkControl->control_id : 'N/A' }}</td>
                    <td class="w-[18%] label-cell">Status:</td>
                    <td class="w-[32%] value-cell">
                        @if($finding->status === 'Open')
                            <span class="badge badge-open">Open</span>
                        @elseif($finding->status === 'In Progress')
                            <span class="badge badge-progress">In Progress</span>
                        @else
                            <span class="badge badge-closed">Closed</span>
                        @endif
                    </td>
                </tr>
                <!-- Row 2 -->
                <tr>
                    <td class="label-cell">Domain:</td>
                    <td class="value-cell" style="font-weight: bold;">{{ $finding->frameworkControl ? $finding->frameworkControl->domain : 'N/A' }}</td>
                    <td class="label-cell">Risk Rating:</td>
                    <td class="value-cell">
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
                <!-- Row 3 -->
                <tr>
                    <td class="label-cell">Control Requirement:</td>
                    <td class="value-cell" colspan="3">
                        {{ $finding->frameworkControl ? $finding->frameworkControl->requirement_description : 'N/A' }}
                    </td>
                </tr>
                <!-- Row 4 -->
                <tr>
                    <td class="label-cell">Current State / Observation</td>
                    <td class="value-cell rich-content" colspan="3">
                        {!! $finding->observation ?: '—' !!}
                    </td>
                </tr>
                <!-- Row 5 -->
                <tr>
                    <td class="label-cell">Gap Description</td>
                    <td class="value-cell rich-content" colspan="3">
                        {!! $finding->gap_description ?: '—' !!}
                    </td>
                </tr>
                <!-- Row 6 -->
                <tr>
                    <td class="label-cell">Impact / Risk</td>
                    <td class="value-cell rich-content" colspan="3">
                        {!! $finding->impact ?: '—' !!}
                    </td>
                </tr>
                <!-- Row 7 -->
                <tr>
                    <td class="label-cell">Recommendation</td>
                    <td class="value-cell rich-content" colspan="3">
                        {!! $finding->recommendation ?: '—' !!}
                    </td>
                </tr>
                <!-- Row 8 -->
                <tr>
                    <td class="label-cell">Linked Evidence</td>
                    <td class="value-cell" colspan="3">
                        @php
                            $ctrlId = $finding->frameworkControl ? $finding->frameworkControl->id : null;
                            $accepted = $ctrlId && isset($acceptedEvidence[$ctrlId]) ? $acceptedEvidence[$ctrlId] : collect();
                        @endphp
                        @forelse($finding->evidence as $e)
                            <div style="margin-bottom: 4px; font-weight: bold; color: #0284c7;">
                                &bull; {{ $e->name }}
                            </div>
                        @empty
                            @if($accepted->isNotEmpty())
                                <span style="color: #64748b; font-style: italic;">Attached via evidence analysis</span>
                            @else
                                <span style="color: #64748b; font-style: italic;">No linked evidence.</span>
                            @endif
                        @endforelse
                        @foreach($accepted as $ef)
                            <div style="margin-bottom: 4px;">
                                <span style="font-weight: bold; color: #16a34a;">&bull; {{ $ef->original_filename }}</span>
                                <span style="font-size: 7.5pt; color: #64748b; margin-left: 4px;">
                                    (accepted evidence analysis)
                                </span>
                            </div>
                        @endforeach
                    </td>
                </tr>
                <!-- Row 9 -->
                <tr>
                    <td class="label-cell">Is Compliant?</td>
                    <td class="value-cell" colspan="3" style="font-weight: bold; color: {{ $finding->is_compliant ? '#16a34a' : '#dc2626' }};">
                        {{ $finding->is_compliant ? 'YES (Compliant)' : 'NO (Non-Compliant)' }}
                    </td>
                </tr>
            </tbody>
        </table>
    @empty
        <p style="text-align: center; color: #64748b; margin-top: 30px;">No detailed findings recorded.</p>
    @endforelse
    @endif

    {{-- Footer --}}
    <div class="report-footer">
        {{ $framework->name }} {{ $assessment->type }} Report &mdash;
        {{ $project->name }} &mdash;
        Confidential
    </div>

</div>
</body>
</html>
